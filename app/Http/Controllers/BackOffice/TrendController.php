<?php

namespace App\Http\Controllers\BackOffice;

use App\Http\Controllers\Controller;
use App\Models\OmgTransaction;
use App\Models\MerchantCreditWallet;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class TrendController extends Controller
{
    public function index(Request $request): View
    {
        $from = $request->input('from')
            ? Carbon::parse($request->input('from'))->startOfDay()
            : Carbon::today()->subDays(6);
        $to = $request->input('to') ? Carbon::parse($request->input('to'))->endOfDay() : Carbon::today()->endOfDay();

        // Cap the range — each extra day adds a GROUP BY bucket plus a PHP
        // loop iteration; nothing here needs to support an unbounded scan.
        if ($from->diffInDays($to) > 90) {
            $from = $to->copy()->subDays(90)->startOfDay();
        }

        $byDay = OmgTransaction::where('id', '>', MerchantCreditWallet::resetCutoff())
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('DATE(created_at) as day')
            ->selectRaw("SUM(CASE WHEN type = 1 THEN ABS(money) ELSE 0 END) as bet_amount")
            ->selectRaw("SUM(CASE WHEN type = 1 THEN 1 ELSE 0 END) as bet_count")
            ->selectRaw("SUM(CASE WHEN type = 3 THEN money ELSE 0 END) as win_amount")
            ->selectRaw("COUNT(DISTINCT CASE WHEN type = 1 THEN user_id END) as betting_users")
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        $rows = [];
        $cursor = $from->copy();
        $guard  = 0;
        while ($cursor->lte($to) && $guard++ < 366) {
            $day = $cursor->toDateString();
            $r   = $byDay[$day] ?? null;
            $rows[] = [
                'day'           => $day,
                'bet_amount'    => (float) ($r->bet_amount ?? 0),
                'bet_count'     => (int) ($r->bet_count ?? 0),
                'win_amount'    => (float) ($r->win_amount ?? 0),
                'betting_users' => (int) ($r->betting_users ?? 0),
            ];
            $cursor->addDay();
        }

        $maxBet = max(1, ...array_column($rows, 'bet_amount'));

        return view('backoffice.trend.index', [
            'rows'   => $rows,
            'maxBet' => $maxBet,
            'from'   => $from->toDateString(),
            'to'     => $to->toDateString(),
        ]);
    }
}
