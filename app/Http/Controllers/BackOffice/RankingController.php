<?php

namespace App\Http\Controllers\BackOffice;

use App\Http\Controllers\Controller;
use App\Models\OmgTransaction;
use App\Models\MerchantCreditWallet;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class RankingController extends Controller
{
    public function index(Request $request): View
    {
        $from = $request->input('from') ? Carbon::parse($request->input('from'))->startOfDay() : Carbon::today();
        $to   = $request->input('to') ? Carbon::parse($request->input('to'))->endOfDay() : Carbon::today()->endOfDay();
        $tab  = $request->input('tab', 'win'); // win | loss | bet

        // Cap the range — a GROUP BY over an unbounded window is the kind
        // of thing that's fine today and a slow page once the table grows.
        if ($from->diffInDays($to) > 90) {
            $from = $to->copy()->subDays(90)->startOfDay();
        }

        $base = OmgTransaction::where('omg_transactions.id', '>', MerchantCreditWallet::resetCutoff())
            ->whereBetween('omg_transactions.created_at', [$from, $to])
            ->join('players', 'players.id', '=', 'omg_transactions.user_id')
            ->groupBy('omg_transactions.user_id', 'players.username');

        $rows = match ($tab) {
            'loss' => (clone $base)
                ->selectRaw('players.username, SUM(omg_transactions.money) as metric')
                ->havingRaw('SUM(omg_transactions.money) < 0')
                ->orderBy('metric', 'asc'),
            'bet' => (clone $base)
                ->selectRaw('players.username, SUM(ABS(omg_transactions.money)) as metric')
                ->where('omg_transactions.type', 1)
                ->orderByDesc('metric'),
            default => (clone $base) // win
                ->selectRaw('players.username, SUM(omg_transactions.money) as metric')
                ->havingRaw('SUM(omg_transactions.money) > 0')
                ->orderByDesc('metric'),
        };

        $rows = $rows->limit(50)->get();

        return view('backoffice.rankings.index', [
            'rows' => $rows,
            'tab'  => $tab,
            'from' => $from->toDateString(),
            'to'   => $to->toDateString(),
        ]);
    }
}
