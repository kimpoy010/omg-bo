<?php

namespace App\Http\Controllers\BackOffice;

use App\Http\Controllers\Controller;
use App\Models\BackOfficeOperationLog;
use App\Models\PatronAutoConfig;
use App\Models\PatronControl;
use App\Models\Player;
use App\Services\OmgApiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

// Player nurturing / killing. Each action is pushed to OMG first; only when OMG
// accepts it do we record it locally (patron_controls / patron_auto_configs).
class PatronController extends Controller
{
    private const MAX_AMOUNT = 100000000;
    private const MAX_PLAYERS = 100;

    public function index(): View
    {
        return view('backoffice.patron.index', [
            'auto'    => PatronAutoConfig::get(),
            'active'  => PatronControl::with(['player', 'setBy'])->where('status', 'active')->latest()->paginate(25, ['*'], 'active_page'),
            'history' => PatronControl::with('player')->where('status', 'cancelled')->latest('cancelled_at')->limit(20)->get(),
            'types'   => PatronControl::PATRON_TYPES,
        ]);
    }

    public function store(Request $request, OmgApiService $omg): RedirectResponse
    {
        $data = $request->validate([
            'usernames'   => ['required', 'string', 'max:5000'],
            'kind'        => ['required', Rule::in(['nurture', 'kill'])],
            'patron_type' => ['required_if:kind,nurture', 'nullable', 'integer', Rule::in(array_keys(PatronControl::PATRON_TYPES))],
            'amount'      => ['required', 'numeric', 'min:0.01', 'max:' . self::MAX_AMOUNT],
        ]);

        $players = $this->resolvePlayers($data['usernames']);
        if (is_string($players)) {
            return back()->withInput()->withErrors(['usernames' => $players]);
        }

        $nurture     = $data['kind'] === 'nurture';
        $patronType  = $nurture ? (int) $data['patron_type'] : null;

        try {
            $omg->setPatronControl(
                $players->pluck('id')->implode(','),
                $nurture ? PatronControl::TYPE_NURTURE : PatronControl::TYPE_KILL,
                (float) $data['amount'],
                $patronType,
            );
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', 'OMG API error: ' . $e->getMessage());
        }

        DB::transaction(function () use ($players, $data, $patronType) {
            // A new setting replaces any active one for the same player.
            $this->markCancelled($players->pluck('id')->all());

            foreach ($players as $player) {
                PatronControl::create([
                    'user_id'        => $player->id,
                    'kind'           => $data['kind'],
                    'patron_type'    => $patronType,
                    'amount'         => $data['amount'],
                    'status'         => 'active',
                    'set_by_user_id' => Auth::id(),
                ]);
            }
        });

        BackOfficeOperationLog::record('patron_set', ucfirst($data['kind']) . ' ' . $players->pluck('username')->implode(', ') . ' @ ' . $data['amount']);

        return back()->with('success', ucfirst($data['kind']) . " applied to {$players->count()} player(s).");
    }

    public function cancel(Request $request, OmgApiService $omg): RedirectResponse
    {
        $data = $request->validate(['usernames' => ['required', 'string', 'max:5000']]);

        $players = $this->resolvePlayers($data['usernames']);
        if (is_string($players)) {
            return back()->withInput()->withErrors(['usernames' => $players]);
        }

        try {
            $omg->cancelPatronControl($players->pluck('id')->implode(','));
        } catch (\RuntimeException $e) {
            return back()->with('error', 'OMG API error: ' . $e->getMessage());
        }

        $this->markCancelled($players->pluck('id')->all());

        BackOfficeOperationLog::record('patron_cancel', $players->pluck('username')->implode(', '));

        return back()->with('success', "Nurturing/killing cancelled for {$players->count()} player(s).");
    }

    public function auto(Request $request, OmgApiService $omg): RedirectResponse
    {
        $data = $request->validate([
            'enabled'     => ['required', 'boolean'],
            'amount'      => ['required_if:enabled,1', 'nullable', 'numeric', 'min:0.01', 'max:' . self::MAX_AMOUNT],
            'patron_type' => ['required_if:enabled,1', 'nullable', 'integer', Rule::in(array_keys(PatronControl::PATRON_TYPES))],
            'cancel_all'  => ['nullable', 'boolean'],
        ]);

        $enabled = (bool) $data['enabled'];

        try {
            $omg->setAutoPatron(
                $enabled,
                $enabled ? (float) $data['amount'] : null,
                $enabled ? (int) $data['patron_type'] : null,
                ! $enabled && $request->boolean('cancel_all'),
            );
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', 'OMG API error: ' . $e->getMessage());
        }

        $auto = PatronAutoConfig::get();
        $auto->enabled = $enabled;
        if ($enabled) {
            $auto->amount      = $data['amount'];
            $auto->patron_type = (int) $data['patron_type'];
        }
        $auto->updated_by = Auth::id();
        $auto->save();

        BackOfficeOperationLog::record('patron_auto', $enabled ? "On @ {$data['amount']}" : 'Off' . ($request->boolean('cancel_all') ? ' (cancel all)' : ''));

        return back()->with('success', 'New-player auto nurturing ' . ($enabled ? 'enabled.' : 'disabled.'));
    }

    /** @return \Illuminate\Support\Collection<int, Player>|string  players, or an error message */
    private function resolvePlayers(string $input): \Illuminate\Support\Collection|string
    {
        $names = collect(preg_split('/[\s,;]+/', $input, -1, PREG_SPLIT_NO_EMPTY))->unique()->values();

        if ($names->isEmpty()) {
            return 'Enter at least one username.';
        }
        if ($names->count() > self::MAX_PLAYERS) {
            return 'At most ' . self::MAX_PLAYERS . ' players at a time.';
        }

        $players = Player::whereIn('username', $names)->get();
        $missing = $names->diff($players->pluck('username'));

        return $missing->isNotEmpty() ? 'Unknown player(s): ' . $missing->implode(', ') : $players;
    }

    private function markCancelled(array $playerIds): void
    {
        PatronControl::whereIn('user_id', $playerIds)->where('status', 'active')
            ->update(['status' => 'cancelled', 'cancelled_by_user_id' => Auth::id(), 'cancelled_at' => Carbon::now()]);
    }
}
