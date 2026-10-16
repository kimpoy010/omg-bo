<?php

namespace App\Http\Controllers\BackOffice;

use App\Http\Controllers\Controller;
use App\Models\MerchantCreditWallet;
use App\Models\OmgTransaction;
use App\Models\Player;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    // All figures come straight from our own omg_transactions table (every
    // bet/win/cancel we've ever processed via the OMG callback) — no OMG
    // merchant-API access is needed for this page, see the project notes
    // in the conversation this was built from.
    public function index(): View
    {
        $todayStart = Carbon::today();
        $todayRows  = OmgTransaction::where('id', '>', MerchantCreditWallet::resetCutoff())->where('created_at', '>=', $todayStart)->get();

        $betRows  = $todayRows->where('type', 1); // debit — the "valid bet" type
        $winRows  = $todayRows->where('type', 3);

        $betAmount = $betRows->sum(fn ($r) => abs((float) $r->money));
        $winAmount = $winRows->sum(fn ($r) => (float) $r->money);

        $stats = [
            'bet_count'       => $betRows->count(),
            'bet_amount'      => $betAmount,
            'win_amount'      => $winAmount,
            'win_loss'        => $winAmount - $betAmount, // player's perspective: positive = players up
            'profit'          => $betAmount - $winAmount, // merchant's perspective: bets kept minus wins paid
            'betting_users'   => $betRows->pluck('user_id')->unique()->count(),
            'online_users'    => Player::where('last_seen_at', '>=', now()->subMinutes(5))->count(),
            'registered_users' => Player::count(),
        ];

        // Last 7 days of bet volume, for the trend chart.
        $trend = OmgTransaction::where('id', '>', MerchantCreditWallet::resetCutoff())
            ->where('type', 1)
            ->where('created_at', '>=', Carbon::today()->subDays(6))
            ->select(DB::raw('DATE(created_at) as day'), DB::raw('SUM(ABS(money)) as total'))
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        $trendLabels = [];
        $trendValues = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = Carbon::today()->subDays($i)->toDateString();
            $trendLabels[] = $day;
            $trendValues[] = (float) ($trend[$day]->total ?? 0);
        }

        $merchantCredit = MerchantCreditWallet::get();

        return view('backoffice.dashboard.index', compact('stats', 'trendLabels', 'trendValues', 'merchantCredit'));
    }
}
