<?php

namespace App\Http\Controllers\BackOffice;

use App\Http\Controllers\Controller;
use App\Models\BackOfficeOperationLog;
use App\Models\OmgRtpSetting;
use App\Models\Player;
use App\Services\OmgApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

// Per-player RTP overrides: pushes the setting to OMG and records it in
// omg_rtp_settings (set_by_user_id = the merchant who made the change).
class RtpController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->input('search');

        $settings = OmgRtpSetting::with(['user', 'setBy'])
            ->when($search, fn ($q) => $q->whereHas('user', fn ($u) => $u->where('username', 'like', "%{$search}%")))
            ->latest('updated_at')
            ->paginate(25)
            ->withQueryString();

        $rtpOptions = OmgApiService::rtpOptions();

        return view('backoffice.rtp.index', compact('settings', 'rtpOptions', 'search'));
    }

    // Username autocomplete for the "Set Player RTP Control" form.
    public function searchPlayers(Request $request): JsonResponse
    {
        $q = trim((string) $request->input('q', ''));

        if ($q === '') {
            return response()->json([]);
        }

        $like = addcslashes($q, '%_\\');

        return response()->json(
            Player::query()
                ->where('username', 'like', "%{$like}%")
                ->orderBy('username')
                ->limit(15)
                ->pluck('username')
        );
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'username'     => 'required|string|exists:players,username',
            'switch'       => 'required|in:1,2',
            'rtp'          => 'required_if:switch,2|nullable|integer|in:' . implode(',', array_keys(OmgApiService::rtpOptions())),
            'control_type' => 'required|in:0,1',
        ]);

        $user = Player::where('username', $data['username'])->firstOrFail();
        $switch      = (int) $data['switch'];
        $rtp         = $switch === 2 ? (int) $data['rtp'] : 9000;
        $controlType = (int) $data['control_type'];

        try {
            app(OmgApiService::class)->setRtpControl((string) $user->id, $switch, $rtp, $controlType);
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', 'OMG API error: ' . $e->getMessage());
        }

        OmgRtpSetting::updateOrCreate(
            ['user_id' => $user->id],
            [
                'switch'                     => $switch,
                'control_type'               => $controlType,
                'rtp'                        => $rtp,
                'set_by_user_id' => Auth::id(),
            ]
        );

        $label = $switch === 1 ? 'disabled' : 'set to ' . OmgApiService::rtpOptions()[$rtp];
        BackOfficeOperationLog::record('rtp_control', "{$user->username}: RTP {$label}");

        return back()->with('success', "RTP control for {$user->username} {$label}.");
    }

    public function destroy(Player $user): RedirectResponse
    {
        try {
            app(OmgApiService::class)->setRtpControl((string) $user->id, 1);
        } catch (\RuntimeException $e) {
            return back()->with('error', 'OMG API error: ' . $e->getMessage());
        }

        OmgRtpSetting::where('user_id', $user->id)->update([
            'switch'                     => 1,
            'set_by_user_id' => Auth::id(),
        ]);

        BackOfficeOperationLog::record('rtp_control', "{$user->username}: RTP control removed");

        return back()->with('success', "RTP control for {$user->username} removed.");
    }
}
