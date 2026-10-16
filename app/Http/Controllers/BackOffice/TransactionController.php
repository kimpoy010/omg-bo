<?php

namespace App\Http\Controllers\BackOffice;

use App\Http\Controllers\Controller;
use App\Models\OmgTransaction;
use App\Models\MerchantCreditWallet;
use App\Models\Player;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

// Serves both "Game Records" and "Order Records" in the nav — our single
// omg_transactions table already carries everything both of those views
// in the reference back office showed (order_id, type, round_id, money,
// game_id), so one controller covers both with a different default type
// filter rather than maintaining two near-identical queries.
class TransactionController extends Controller
{
    private const TYPE_LABELS = [
        1 => 'Bet', 2 => 'Cancel', 3 => 'Win', 4 => 'Round End', 5 => 'Luck Win', 7 => 'Bonus',
    ];

    public function index(Request $request): View
    {
        $from   = $request->input('from') ? Carbon::parse($request->input('from'))->startOfDay() : Carbon::today();
        $to     = $request->input('to') ? Carbon::parse($request->input('to'))->endOfDay() : Carbon::today()->endOfDay();
        $type   = $request->input('type', '');
        $search = trim((string) $request->input('search', ''));

        // Cap the range — pagination bounds each page's fetch, but an
        // unbounded date filter still means an unbounded COUNT(*) scan for
        // the paginator itself.
        if ($from->diffInDays($to) > 90) {
            $from = $to->copy()->subDays(90)->startOfDay();
        }

        $query = OmgTransaction::with('user')->where('id', '>', MerchantCreditWallet::resetCutoff())->whereBetween('created_at', [$from, $to]);

        if ($type !== '') {
            $query->where('type', (int) $type);
        }

        if ($search !== '') {
            $matchIds = Player::where('username', 'like', "%{$search}%")->pluck('id');
            $query->whereIn('user_id', $matchIds);
        }

        $records = $query->orderByDesc('created_at')->paginate(50)->withQueryString();

        return view('backoffice.transactions.index', [
            'records'    => $records,
            'from'       => $from->toDateString(),
            'to'         => $to->toDateString(),
            'type'       => $type,
            'search'     => $search,
            'typeLabels' => self::TYPE_LABELS,
        ]);
    }
}
