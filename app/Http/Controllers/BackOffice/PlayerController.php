<?php

namespace App\Http\Controllers\BackOffice;

use App\Http\Controllers\Controller;
use App\Models\MerchantCreditWallet;
use App\Models\Player;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlayerController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search', ''));

        // Player accounts only — staff roles (webmaster, superadmin, admin,
        // declarator) and agents aren't players.
        $cut = MerchantCreditWallet::resetCutoff();

        $players = Player::query()
            ->when($search !== '', fn ($q) => $q->where('username', 'like', "%{$search}%"))
            // Raw sums stay signed (bets negative, wins positive) — flipped for
            // display in the view — so this avoids needing a DB::raw column
            // expression inside withSum().
            ->withSum(['omgTransactions as bet_raw' => fn ($q) => $q->where('id', '>', $cut)->where('type', 1)], 'money')
            ->withSum(['omgTransactions as win_raw' => fn ($q) => $q->where('id', '>', $cut)->where('type', 3)], 'money')
            ->withMax(['omgTransactions as last_bet_at' => fn ($q) => $q->where('id', '>', $cut)], 'created_at')
            ->withCount(['omgTransactions as bet_count' => fn ($q) => $q->where('id', '>', $cut)->where('type', 1)])
            ->orderByRaw('bet_raw IS NULL, bet_raw ASC') // most negative (highest wagered) first
            ->paginate(25)
            ->withQueryString();

        return view('backoffice.players.index', compact('players', 'search'));
    }
}
