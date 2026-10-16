<?php

namespace App\Http\Controllers\BackOffice;

use App\Http\Controllers\Controller;
use App\Models\BackOfficeOperationLog;
use App\Models\GameConfig;
use App\Models\GameConfigChangeRequest;
use App\Services\CasinoGameMapper;
use App\Services\GameConfigChangeService;
use App\Services\OmgApiService;
use App\Support\GameConfigFields;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

// Game Ctrl. OMG has no API for per-game config, so saving here only
// records change requests (see GameConfigChangeService) for the webmaster
// to apply by hand in OMG's own back office.
class GameConfigController extends Controller
{
    public function index(Request $request): View
    {
        $games    = $this->games();
        $provider = (string) $request->input('provider', '');
        $search   = trim((string) $request->input('search', ''));

        $providers = $games->pluck('provider')->unique()->sort()->values();

        $games = $games
            ->when($provider !== '', fn ($c) => $c->where('provider', $provider))
            ->when($search !== '', fn ($c) => $c->filter(fn ($g) => stripos($g['name'], $search) !== false || $g['id'] === $search))
            ->values();

        $configs = GameConfig::whereIn('game_id', $games->pluck('id'))->get()->keyBy('game_id');
        // What the merchant sees for each game's recent changes: "processing" for
        // the first 3 minutes after they save, then "processed". (Display only —
        // the webmaster's own pending/applied tracking is separate.) Changes older
        // than a day drop off the list.
        $updates = GameConfigChangeRequest::whereIn('status', ['pending', 'applied'])
            ->where('created_at', '>=', now()->subDay())
            ->get(['game_id', 'created_at'])
            ->groupBy('game_id')
            ->map(fn ($rows) => [
                'n'          => $rows->count(),
                'processing' => $rows->contains(fn ($r) => $r->created_at->gt(now()->subMinutes(3))),
            ]);

        return view('backoffice.game-config.index', compact('games', 'providers', 'provider', 'search', 'configs', 'updates'));
    }

    public function edit(string $gameId): View
    {
        $game = $this->findGame($gameId);
        $config = GameConfig::where('game_id', $gameId)->first();

        return view('backoffice.game-config.edit', [
            'game'     => $game,
            'settings' => $config?->settings ?? [],
            'fields'   => GameConfigFields::all(),
            'pending'  => GameConfigChangeRequest::where('game_id', $gameId)->where('status', 'pending')->get()->keyBy('field'),
        ]);
    }

    public function update(Request $request, string $gameId, GameConfigChangeService $service): RedirectResponse
    {
        $game = $this->findGame($gameId);
        $input = $request->only(array_keys(GameConfigFields::all()));
        $request->validate($this->rules());

        $count = $service->record($game, $input, Auth::id());

        if ($count > 0) {
            BackOfficeOperationLog::record('game_config_change', "{$game['name']}: {$count} change(s) requested");
        }

        return redirect()->route('backoffice.game-config.index')
            ->with('success', $count > 0 ? "{$count} change(s) submitted to the webmaster for {$game['name']}." : 'No changes detected.');
    }

    public function batch(Request $request): View
    {
        $games = $this->games();

        return view('backoffice.game-config.batch', [
            'games'     => $games->groupBy('provider')->sortKeys(),
            'fields'    => collect(GameConfigFields::all())->only(['game_status', 'internal_rtp', 'min_bet', 'max_bet', 'max_reward_multiple', 'new_player_rtp']),
        ]);
    }

    public function batchUpdate(Request $request, GameConfigChangeService $service): RedirectResponse
    {
        $fieldKeys = ['game_status', 'internal_rtp', 'min_bet', 'max_bet', 'max_reward_multiple', 'new_player_rtp'];
        $request->validate(['game_ids' => 'required|array|min:1', 'game_ids.*' => 'string'] + $this->rules());

        $input = array_filter($request->only($fieldKeys), fn ($v) => $v !== null && $v !== '');
        if ($input === []) {
            return back()->withErrors(['game_ids' => 'Fill in at least one field to change.'])->withInput();
        }

        $byId = $this->games()->keyBy('id');
        $total = 0;
        $adminId = Auth::id();

        foreach ($request->input('game_ids') as $id) {
            if ($game = $byId->get($id)) {
                $total += $service->record($game, $input, $adminId);
            }
        }

        BackOfficeOperationLog::record('game_config_batch', count($request->input('game_ids')) . " game(s), {$total} change(s) requested");

        return redirect()->route('backoffice.game-config.index')
            ->with('success', "{$total} change(s) submitted to the webmaster.");
    }

    private function rules(): array
    {
        $rules = [];
        foreach (GameConfigFields::all() as $key => $def) {
            $rules[$key] = $def['type'] === 'select'
                ? ['nullable', 'in:' . implode(',', array_keys($def['options']))]
                : ['nullable', 'numeric', 'min:0'];
        }

        return $rules;
    }

    // Own cache key — 'omg_game_list' holds the player lobby's transformed
    // shape, not this normalized one.
    private function games()
    {
        $games = Cache::remember('backoffice_omg_games', 300, function () {
            try {
                $raw = app(OmgApiService::class)->getGameList();
            } catch (\Throwable) {
                return [];
            }

            return collect($raw)->map(fn ($g) => [
                'id'       => (string) ($g['gameid'] ?? $g['game_id'] ?? $g['id'] ?? ''),
                'name'     => $g['name'] ?? $g['game_name'] ?? 'Unknown',
                'provider' => CasinoGameMapper::mapProvider((int) ($g['platform_sub_brand'] ?? 0)),
            ])->filter(fn ($g) => $g['id'] !== '')->sortBy([['provider', 'asc'], ['name', 'asc']])->values()->all();
        });

        if ($games === []) {
            Cache::forget('backoffice_omg_games'); // don't pin a failed fetch for 5 minutes
        }

        return collect($games);
    }

    private function findGame(string $gameId): array
    {
        return $this->games()->firstWhere('id', $gameId) ?? abort(404);
    }
}
