<?php

namespace App\Http\Controllers\Webmaster;

use App\Http\Controllers\Controller;
use App\Models\GameConfigChangeRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

// Webmaster-only to-do list of game-config changes the merchant has asked
// for in the casino back office. OMG has no API for these, so the webmaster
// applies each by hand in OMG's own back office, then marks it applied here.
class GameConfigChangeController extends Controller
{
    public function index(Request $request): View
    {
        $status = (string) $request->input('status', 'pending');
        $game   = trim((string) $request->input('game', ''));

        $changes = GameConfigChangeRequest::with(['requestedBy', 'appliedBy'])
            ->when(in_array($status, ['pending', 'applied'], true), fn ($q) => $q->where('status', $status))
            ->when($game !== '', fn ($q) => $q->where(fn ($w) => $w->where('game_name', 'like', "%{$game}%")->orWhere('game_id', $game)))
            ->orderBy('game_name')->orderBy('id')
            ->paginate(50)->withQueryString();

        return view('webmaster.game-config-changes.index', [
            'changes'      => $changes,
            'status'       => $status,
            'game'         => $game,
            'pendingCount' => GameConfigChangeRequest::where('status', 'pending')->count(),
        ]);
    }

    public function toggle(GameConfigChangeRequest $change): RedirectResponse
    {
        $applying = $change->status === 'pending';

        $change->update([
            'status'     => $applying ? 'applied' : 'pending',
            'applied_by' => $applying ? auth()->id() : null,
            'applied_at' => $applying ? now() : null,
        ]);

        return back()->with('success', $applying ? 'Marked as applied.' : 'Moved back to pending.');
    }

    public function applyAll(Request $request): RedirectResponse
    {
        $data = $request->validate(['ids' => 'required|array|min:1', 'ids.*' => 'integer']);

        $count = GameConfigChangeRequest::whereIn('id', $data['ids'])->where('status', 'pending')
            ->update(['status' => 'applied', 'applied_by' => auth()->id(), 'applied_at' => now()]);

        return back()->with('success', "{$count} change(s) marked as applied.");
    }
}
