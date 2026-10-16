<?php

namespace App\Http\Controllers\BackOffice;

use App\Http\Controllers\Controller;
use App\Models\OmgTransaction;
use App\Models\MerchantCreditWallet;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

// Classic cohort retention: group players by the day of their first-ever
// bet, then check whether each cohort also has a bet exactly N days later.
// Computed in PHP over per-cohort sets rather than one big SQL query — the
// underlying queries are scoped to stay cheap as omg_transactions grows,
// see the comments below.
class RetentionController extends Controller
{
    private const OFFSETS = [1, 3, 7, 15, 30];

    public function index(Request $request): View
    {
        $from = $request->input('from')
            ? Carbon::parse($request->input('from'))->startOfDay()
            : Carbon::today()->subDays(13);
        $to = $request->input('to') ? Carbon::parse($request->input('to'))->endOfDay() : Carbon::today()->endOfDay();

        // Cap the range — this page does per-user PHP work per cohort day,
        // nothing here needs to support an unbounded scan.
        if ($from->diffInDays($to) > 90) {
            $from = $to->copy()->subDays(90)->startOfDay();
        }

        $maxOffset = max(self::OFFSETS);

        // Each user's first-ever bet date is a lifetime fact (can't be
        // computed from a date-scoped query — a cohort is defined by when a
        // player *started*), but grouped by user_id this scans at most one
        // row per user thanks to the (type, user_id, created_at) index, not
        // one row per transaction — stays cheap as the table grows.
        $resetCutoff = MerchantCreditWallet::resetCutoff();
        $firstBetByUser = OmgTransaction::where('id', '>', $resetCutoff)
            ->where('type', 1)
            ->selectRaw('user_id, DATE(MIN(created_at)) as first_day')
            ->groupBy('user_id')
            ->havingRaw('MIN(created_at) BETWEEN ? AND ?', [$from, $to])
            ->pluck('first_day', 'user_id');

        $cohorts = [];
        foreach ($firstBetByUser as $userId => $day) {
            $cohorts[$day][] = $userId;
        }

        $cohortUserIds = array_keys($firstBetByUser->all());

        // Only the cohort users, and only the window we actually need
        // (through cohort_to + the longest offset) — not every bet-day ever
        // placed by every player, which is what made this unbounded before.
        $betDaysByUser = $cohortUserIds === []
            ? collect()
            : OmgTransaction::where('id', '>', $resetCutoff)
                ->where('type', 1)
                ->whereIn('user_id', $cohortUserIds)
                ->whereBetween('created_at', [$from, $to->copy()->addDays($maxOffset)->endOfDay()])
                ->selectRaw('user_id, DATE(created_at) as bet_day')
                ->distinct()
                ->get()
                ->groupBy('user_id')
                ->map(fn ($rows) => $rows->pluck('bet_day')->flip());

        $rows = [];
        $cursor = $from->copy();
        $guard  = 0;
        while ($cursor->lte($to) && $guard++ < 366) {
            $day        = $cursor->toDateString();
            $cohortIds  = $cohorts[$day] ?? [];
            $cohortSize = count($cohortIds);

            $retained = [];
            foreach (self::OFFSETS as $offset) {
                $targetDay = Carbon::parse($day)->addDays($offset)->toDateString();
                $count = 0;
                foreach ($cohortIds as $uid) {
                    if (isset($betDaysByUser[$uid]) && $betDaysByUser[$uid]->has($targetDay)) {
                        $count++;
                    }
                }
                $retained[$offset] = $cohortSize > 0 ? round($count / $cohortSize * 100, 1) : null;
            }

            $rows[] = ['day' => $day, 'cohort_size' => $cohortSize, 'retained' => $retained];
            $cursor->addDay();
        }

        return view('backoffice.retention.index', [
            'rows'    => array_reverse($rows),
            'from'    => $from->toDateString(),
            'to'      => $to->toDateString(),
            'offsets' => self::OFFSETS,
        ]);
    }
}
