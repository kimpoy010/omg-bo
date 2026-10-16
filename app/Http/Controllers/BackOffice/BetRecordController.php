<?php

namespace App\Http\Controllers\BackOffice;

use App\Http\Controllers\Controller;
use App\Models\OmgTransaction;
use App\Models\Player;
use App\Services\OmgApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

// Live bet records straight from OMG (GetGameRecordList), as opposed to
// Game Records, which reads our own omg_transactions. Useful to cross-check the
// two: each row shows whether we have a bet transaction for the same round.
class BetRecordController extends Controller
{
    private const SIZES = [25, 50, 100, 200];

    public function index(Request $request, OmgApiService $omg): View
    {
        // All times are UTC, as OMG's server and API are.
        $to   = $this->parse($request->input('to'))   ?? Carbon::now('UTC')->startOfMinute();
        $from = $this->parse($request->input('from')) ?? $to->copy()->subDay();

        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }
        // OMG rejects windows over 24 hours (error 20014).
        $clamped = false;
        if ($from->diffInSeconds($to) > 86400) {
            $from    = $to->copy()->subDay();
            $clamped = true;
        }

        $size = in_array((int) $request->input('size'), self::SIZES, true) ? (int) $request->input('size') : 50;
        $page = max(1, (int) $request->input('page', 1));
        $search = trim((string) $request->input('search', ''));
        $gameId = trim((string) $request->input('game_id', ''));

        $error = null;
        $rows  = collect();
        $player = null;

        if ($search !== '') {
            $player = Player::where('username', $search)->first();
            if (! $player) {
                $error = "No player named \"{$search}\".";
            }
        }

        if ($error === null) {
            $params = [
                'page'       => $page,
                'size'       => $size,
                'start_time' => $from->getTimestamp(),
                'end_time'   => $to->getTimestamp(),
            ];
            if ($player) {
                $params['uname'] = (string) $player->id;
            }
            if ($gameId !== '') {
                $params['game_id'] = $gameId;
            }

            try {
                $rows = collect($omg->getGameRecords($params)['data']);
            } catch (\RuntimeException $e) {
                $error = 'OMG API error: ' . $e->getMessage();
            }
        }

        // Match each OMG round against our own bet transactions, and resolve usernames.
        $roundIds = $rows->pluck('round_id')->map(fn ($v) => (string) $v)->all();
        $known    = $roundIds === [] ? collect() : OmgTransaction::where('type', 1)->whereIn('round_id', $roundIds)->pluck('round_id')->flip();
        $names    = Player::whereIn('id', $rows->pluck('account_id')->filter()->map(fn ($v) => (int) $v)->unique())->pluck('username', 'id');
        $games    = collect(Cache::get('backoffice_omg_games', []))->pluck('name', 'id');

        $records = $rows->map(fn ($r) => [
            'time'     => Carbon::createFromTimestampUTC((int) ($r['create_time'] ?? 0)),
            'round_id' => (string) ($r['round_id'] ?? ''),
            'player'   => $names[(int) ($r['account_id'] ?? 0)] ?? ($r['account'] ?? '—'),
            'game'     => $games[(string) ($r['game_id'] ?? '')] ?? ($r['game_id'] ?? '—'),
            'bet'      => (float) ($r['bet'] ?? 0),
            'win'      => (float) ($r['win'] ?? 0),
            'before'   => (float) ($r['enter_money'] ?? 0),
            'after'    => (float) ($r['after_settlement_money'] ?? 0),
            'free'     => (int) ($r['small_game_type'] ?? 0) === 2,
            'matched'  => $known->has((string) ($r['round_id'] ?? '')),
        ]);

        return view('backoffice.bet-records.index', [
            'records'  => $records,
            'from'     => $from->format('Y-m-d\TH:i'),
            'to'       => $to->format('Y-m-d\TH:i'),
            'size'     => $size,
            'sizes'    => self::SIZES,
            'page'     => $page,
            'hasNext'  => $rows->count() >= $size,
            'search'   => $search,
            'gameId'   => $gameId,
            'error'    => $error,
            'clamped'  => $clamped,
            'totals'   => [
                'bet' => $records->sum('bet'),
                'win' => $records->sum('win'),
            ],
        ]);
    }

    private function parse(?string $value): ?Carbon
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse($value, 'UTC')->utc()->startOfMinute();
        } catch (\Throwable) {
            return null;
        }
    }
}
