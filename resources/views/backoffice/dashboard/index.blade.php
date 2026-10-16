@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
@php
    $maxTrend = max(1, ...$trendValues);
    $maxNew   = max(1, ...$newPlayerValues);
@endphp

<div class="mb-6 bg-white rounded-lg shadow p-5 flex items-center justify-between">
    <div>
        <div class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Merchant Credit Balance</div>
        <div class="text-2xl font-bold {{ $merchantCredit->balance <= 0 ? 'text-red-600' : 'text-gray-800' }} mt-1">
            ₱{{ number_format((float) $merchantCredit->balance, 2) }}
        </div>
        @if ($merchantCredit->balance <= 0)
            <p class="text-xs text-red-600 mt-1">Depleted — players cannot launch or continue casino games until credits are added.</p>
        @elseif ($merchantCredit->last_reconciled_at)
            <p class="text-xs text-gray-400 mt-1">Last updated {{ $merchantCredit->last_reconciled_at->diffForHumans() }}</p>
        @else
            <p class="text-xs text-gray-400 mt-1">No activity yet.</p>
        @endif
    </div>
    <a href="{{ route('backoffice.credits.index') }}"
       class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition-colors">
        Manage Credits
    </a>
</div>

<div class="bo-stats mb-6">
    @foreach ([
        ['label' => "Today's Bet Amount", 'value' => '₱' . number_format($stats['bet_amount'], 2)],
        ['label' => "Today's Win/Loss (player)", 'value' => '₱' . number_format($stats['win_loss'], 2)],
        ['label' => "Today's Merchant Profit", 'value' => ($stats['profit'] < 0 ? '-' : '') . '₱' . number_format(abs($stats['profit']), 2), 'class' => $stats['profit'] > 0 ? 'text-green-600' : ($stats['profit'] < 0 ? 'text-red-600' : 'text-gray-800')],
        ['label' => 'Number of Bets', 'value' => number_format($stats['bet_count'])],
        ['label' => 'Betting Users', 'value' => number_format($stats['betting_users'])],
        ['label' => 'Online Users', 'value' => number_format($stats['online_users'])],
        ['label' => 'Registered Users', 'value' => number_format($stats['registered_users'])],
        ['label' => 'New Players Today', 'value' => number_format($stats['new_players_today'])],
        ['label' => "Today's RTP", 'value' => $stats['rtp'] === null ? '—' : number_format($stats['rtp'], 2) . '%'],
    ] as $card)
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-xs text-gray-500">{{ $card['label'] }}</div>
            <div class="text-xl font-bold {{ $card['class'] ?? 'text-gray-800' }} mt-1">{{ $card['value'] }}</div>
        </div>
    @endforeach
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <div class="lg:col-span-2">
<div class="bg-white rounded-lg shadow p-5">
        <div class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-4">Bet Volume — Last 7 Days</div>
        <div class="flex items-end gap-3" style="height:160px;">
            @foreach ($trendLabels as $i => $day)
                @php $pct = $trendValues[$i] > 0 ? max(4, round($trendValues[$i] / $maxTrend * 100)) : 2; @endphp
                <div class="flex-1 flex flex-col items-center justify-end h-full">
                    <div class="text-[10px] text-gray-500 mb-1">₱{{ number_format($trendValues[$i], 0) }}</div>
                    <div class="w-full bg-indigo-500 rounded-t" style="height:{{ $pct }}%;"></div>
                    <div class="text-[10px] text-gray-400 mt-1">{{ \Illuminate\Support\Carbon::parse($day)->format('M j') }}</div>
                </div>
            @endforeach
        </div>
    </div>
    </div>

    <div class="space-y-6">
        <div class="bg-white rounded-lg shadow p-5">
            <div class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-4">New Players — Last 7 Days</div>
            <div class="flex items-end gap-2" style="height:100px;">
                @foreach ($trendLabels as $i => $day)
                    @php $pct = $newPlayerValues[$i] > 0 ? max(6, round($newPlayerValues[$i] / $maxNew * 100)) : 2; @endphp
                    <div class="flex-1 flex flex-col items-center justify-end h-full" title="{{ $day }}: {{ $newPlayerValues[$i] }}">
                        <div class="text-[10px] text-gray-500 mb-1">{{ $newPlayerValues[$i] }}</div>
                        <div class="w-full bg-emerald-500 rounded-t" style="height:{{ $pct }}%;"></div>
                        <div class="text-[10px] text-gray-400 mt-1">{{ \Illuminate\Support\Carbon::parse($day)->format('j') }}</div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-5">
            <div class="flex items-center justify-between mb-3">
                <div class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Player Controls</div>
                <a href="{{ route('backoffice.patron.index') }}" class="text-xs text-indigo-600 hover:underline">Manage</a>
            </div>
            <dl class="text-sm space-y-1.5">
                <div class="flex justify-between"><dt class="text-gray-500">Being nurtured</dt><dd class="font-semibold">{{ $patron['nurture'] }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Being killed</dt><dd class="font-semibold">{{ $patron['kill'] }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">New-player auto nurturing</dt><dd class="font-semibold {{ $patron['auto'] ? 'text-green-600' : 'text-gray-400' }}">{{ $patron['auto'] ? 'On' : 'Off' }}</dd></div>
            </dl>
        </div>
    </div>
</div>

<div class="flex items-center justify-between mb-3">
    <h2 class="text-sm font-semibold text-gray-700">Game Performance</h2>
    <div class="flex gap-1">
        @foreach ($periods as $key => $label)
            <a href="{{ route('backoffice.dashboard', ['period' => $key]) }}"
               class="text-xs px-3 py-1.5 rounded-full {{ $period === $key ? 'bg-indigo-600 text-white' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50' }}">{{ $label }}</a>
        @endforeach
    </div>
</div>

@php
    $money = fn ($v) => ($v < 0 ? '-' : '') . '₱' . number_format(abs($v), 2);
    $th = 'bg-gray-50 px-4 py-3 border-b font-semibold text-gray-600';
@endphp

<div class="bg-white rounded-lg shadow overflow-x-auto mb-6">
    <div class="px-4 py-3 border-b text-sm font-semibold text-gray-700">By provider</div>
    <table class="w-full text-sm border-collapse">
        <thead><tr>
            <th class="{{ $th }} text-left">Provider</th><th class="{{ $th }} text-right">Games</th><th class="{{ $th }} text-right">Bets</th>
            <th class="{{ $th }} text-right">Bet</th><th class="{{ $th }} text-right">Win</th><th class="{{ $th }} text-right">GGR</th><th class="{{ $th }} text-right">RTP</th>
        </tr></thead>
        <tbody>
        @forelse ($providers as $p)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 border-b font-medium">{{ $p['name'] }}</td>
                <td class="px-4 py-3 border-b text-right">{{ $p['games'] }}</td>
                <td class="px-4 py-3 border-b text-right">{{ number_format($p['bets']) }}</td>
                <td class="px-4 py-3 border-b text-right font-mono">{{ $money($p['bet']) }}</td>
                <td class="px-4 py-3 border-b text-right font-mono">{{ $money($p['win']) }}</td>
                <td class="px-4 py-3 border-b text-right font-mono {{ $p['ggr'] < 0 ? 'text-red-600' : 'text-green-700' }}">{{ $money($p['ggr']) }}</td>
                <td class="px-4 py-3 border-b text-right">{{ $p['rtp'] === null ? '—' : number_format($p['rtp'], 2) . '%' }}</td>
            </tr>
        @empty
            <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400">No game activity in this period.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

<div class="bg-white rounded-lg shadow overflow-x-auto">
    <div class="px-4 py-3 border-b text-sm font-semibold text-gray-700">By game <span class="font-normal text-gray-400">— top 20 by bet; "Target" is the internal RTP set in Game Config</span></div>
    <table class="w-full text-sm border-collapse">
        <thead><tr>
            <th class="{{ $th }} text-left">Game</th><th class="{{ $th }} text-left">Provider</th><th class="{{ $th }} text-right">Players</th><th class="{{ $th }} text-right">Bets</th>
            <th class="{{ $th }} text-right">Bet</th><th class="{{ $th }} text-right">Win</th><th class="{{ $th }} text-right">GGR</th>
            <th class="{{ $th }} text-right">RTP</th><th class="{{ $th }} text-right">Target</th>
        </tr></thead>
        <tbody>
        @forelse ($games->take(20) as $g)
            @php $over = $g['rtp'] !== null && $g['target'] !== null && $g['bets'] >= 30 && $g['rtp'] > $g['target'] + 5; @endphp
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 border-b font-medium">{{ $g['name'] }}</td>
                <td class="px-4 py-3 border-b text-gray-500">{{ $g['provider'] }}</td>
                <td class="px-4 py-3 border-b text-right">{{ number_format($g['players']) }}</td>
                <td class="px-4 py-3 border-b text-right">{{ number_format($g['bets']) }}</td>
                <td class="px-4 py-3 border-b text-right font-mono">{{ $money($g['bet']) }}</td>
                <td class="px-4 py-3 border-b text-right font-mono">{{ $money($g['win']) }}</td>
                <td class="px-4 py-3 border-b text-right font-mono {{ $g['ggr'] < 0 ? 'text-red-600' : 'text-green-700' }}">{{ $money($g['ggr']) }}</td>
                <td class="px-4 py-3 border-b text-right {{ $over ? 'text-red-600 font-semibold' : '' }}" @if ($over) title="More than 5 points above target over {{ $g['bets'] }} bets" @endif>{{ $g['rtp'] === null ? '—' : number_format($g['rtp'], 2) . '%' }}</td>
                <td class="px-4 py-3 border-b text-right text-gray-500">{{ $g['target'] === null ? '—' : rtrim(rtrim(number_format($g['target'], 2), '0'), '.') . '%' }}</td>
            </tr>
        @empty
            <tr><td colspan="9" class="px-4 py-8 text-center text-gray-400">No game activity in this period.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
