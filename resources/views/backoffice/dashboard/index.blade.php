@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
@php
    $maxTrend = max(1, ...$trendValues);
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
    ] as $card)
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-xs text-gray-500">{{ $card['label'] }}</div>
            <div class="text-xl font-bold {{ $card['class'] ?? 'text-gray-800' }} mt-1">{{ $card['value'] }}</div>
        </div>
    @endforeach
</div>

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
@endsection
