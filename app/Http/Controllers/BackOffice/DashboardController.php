<?php

namespace App\Http\Controllers\BackOffice;

use App\Http\Controllers\Controller;
use App\Models\GameConfig;
use App\Models\MerchantCreditWallet;
use App\Models\PatronAutoConfig;
use App\Models\PatronControl;
use App\Models\OmgTransaction;
use App\Models\Player;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Services\GameCatalog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    // All figures come straight from our own omg_transactions table (every
    // bet/win/cancel we've ever processed via the OMG callback) — no OMG
    // merchant-API access is needed for this page, see the project notes
    // in the conversation this was built from.
    public function index(Request $request, GameCatalog $catalog): View
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

        $stats['new_players_today'] = Player::where('created_at', '>=', $todayStart)->count();
        $stats['ggr']               = $stats['profit'];
        $stats['rtp']               = $betAmount > 0 ? round($winAmount / $betAmount * 100, 2) : null;

        // New registrations per day, same 7-day window as the bet trend.
        $newPlayers = Player::where('created_at', '>=', Carbon::today()->subDays(6))
            ->select(DB::raw('DATE(created_at) as day'), DB::raw('COUNT(*) as total'))
            ->groupBy('day')->pluck('total', 'day');
        $newPlayerValues = array_map(fn ($day) => (int) ($newPlayers[$day] ?? 0), $trendLabels);

        // Per-game / per-provider performance over a selectable window.
        $periods = ['today' => 'Today', '7d' => 'Last 7 days', '30d' => 'Last 30 days'];
        $period  = array_key_exists($request->input('period'), $periods) ? $request->input('period') : 'today';
        $since   = match ($period) {
            '7d'    => Carbon::today()->subDays(6),
            '30d'   => Carbon::today()->subDays(29),
            default => $todayStart,
        };

        [$games, $providers] = $this->gamePerformance($since, $catalog);

        $patron = [
            'nurture' => PatronControl::where('status', 'active')->where('kind', 'nurture')->count(),
            'kill'    => PatronControl::where('status', 'active')->where('kind', 'kill')->count(),
            'auto'    => PatronAutoConfig::get()->enabled,
        ];

        return view('backoffice.dashboard.index', compact(
            'stats', 'trendLabels', 'trendValues', 'merchantCredit',
            'newPlayerValues', 'periods', 'period', 'games', 'providers', 'patron'
        ));
    }

    // Bet / win / GGR / RTP per game, rolled up by provider. The "target" RTP is
    // the internal RTP the merchant has asked for in Game Config (if any), so a
    // game running well above it shows up. Games OMG no longer lists keep their
    // ID as the name and fall under "Other".
    private function gamePerformance(Carbon $since, GameCatalog $catalog): array
    {
        $rows = OmgTransaction::where('id', '>', MerchantCreditWallet::resetCutoff())
            ->where('created_at', '>=', $since)
            ->whereNotNull('game_id')
            ->selectRaw('game_id')
            ->selectRaw('SUM(CASE WHEN type = 1 THEN ABS(money) ELSE 0 END) as bet_amount')
            ->selectRaw('SUM(CASE WHEN type = 1 THEN 1 ELSE 0 END) as bet_count')
            ->selectRaw('SUM(CASE WHEN type = 3 THEN money ELSE 0 END) as win_amount')
            ->selectRaw('COUNT(DISTINCT CASE WHEN type = 1 THEN user_id END) as players')
            ->groupBy('game_id')
            ->get();

        $catalogById = $catalog->all()->keyBy('id');
        $configs     = GameConfig::whereIn('game_id', $rows->pluck('game_id')->map(fn ($id) => (string) $id))->get()->keyBy('game_id');

        $games = $rows->map(function ($r) use ($catalogById, $configs) {
            $id  = (string) $r->game_id;
            $bet = (float) $r->bet_amount;
            $win = (float) $r->win_amount;

            return [
                'id'       => $id,
                'name'     => $catalogById[$id]['name'] ?? "Game {$id}",
                'provider' => $catalogById[$id]['provider'] ?? 'Other',
                'bets'     => (int) $r->bet_count,
                'players'  => (int) $r->players,
                'bet'      => $bet,
                'win'      => $win,
                'ggr'      => $bet - $win,
                'rtp'      => $bet > 0 ? round($win / $bet * 100, 2) : null,
                'target'   => isset($configs[$id]->settings['internal_rtp']) ? (float) $configs[$id]->settings['internal_rtp'] : null,
            ];
        })->sortByDesc('bet')->values();

        $providers = $games->groupBy('provider')->map(function ($g, $name) {
            $bet = $g->sum('bet');
            $win = $g->sum('win');

            return [
                'name'  => $name,
                'games' => $g->count(),
                'bets'  => $g->sum('bets'),
                'bet'   => $bet,
                'win'   => $win,
                'ggr'   => $bet - $win,
                'rtp'   => $bet > 0 ? round($win / $bet * 100, 2) : null,
            ];
        })->sortByDesc('bet')->values();

        return [$games, $providers];
    }
}
