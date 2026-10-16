@extends('layouts.app')
@section('title', 'Trend Data')

@section('content')
<form method="GET" class="bg-white rounded-lg shadow p-4 mb-4 flex flex-wrap items-end gap-3">
    <div>
        <label class="block text-xs font-medium text-gray-500 mb-1">From</label>
        <input type="date" name="from" value="{{ $from }}" class="border border-gray-300 rounded px-3 py-2 text-sm">
    </div>
    <div>
        <label class="block text-xs font-medium text-gray-500 mb-1">To</label>
        <input type="date" name="to" value="{{ $to }}" class="border border-gray-300 rounded px-3 py-2 text-sm">
    </div>
    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-4 py-2 rounded-lg">Search</button>
</form>

<div class="bg-white rounded-lg shadow p-5 mb-4">
    <div class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-4">Bet Volume</div>
    <div class="flex items-end gap-2 overflow-x-auto" style="height:160px;">
        @foreach ($rows as $row)
            @php $pct = $row['bet_amount'] > 0 ? max(4, round($row['bet_amount'] / $maxBet * 100)) : 2; @endphp
            <div class="flex flex-col items-center justify-end h-full" style="min-width:36px;">
                <div class="w-full bg-indigo-500 rounded-t" style="height:{{ $pct }}%;width:20px;"></div>
                <div class="text-[9px] text-gray-400 mt-1 whitespace-nowrap">{{ \Illuminate\Support\Carbon::parse($row['day'])->format('M j') }}</div>
            </div>
        @endforeach
    </div>
</div>

<div class="bg-white rounded-lg shadow overflow-x-auto">
    <table class="w-full text-sm border-collapse">
        <thead>
            <tr>
                <th class="bg-gray-50 text-left px-4 py-3 border-b font-semibold text-gray-600">Date</th>
                <th class="bg-gray-50 text-right px-4 py-3 border-b font-semibold text-gray-600">Bet Amount</th>
                <th class="bg-gray-50 text-right px-4 py-3 border-b font-semibold text-gray-600">Bet Count</th>
                <th class="bg-gray-50 text-right px-4 py-3 border-b font-semibold text-gray-600">Win Amount</th>
                <th class="bg-gray-50 text-right px-4 py-3 border-b font-semibold text-gray-600">Betting Users</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 border-b">{{ $row['day'] }}</td>
                    <td class="px-4 py-3 border-b text-right font-mono">{{ number_format($row['bet_amount'], 2) }}</td>
                    <td class="px-4 py-3 border-b text-right">{{ number_format($row['bet_count']) }}</td>
                    <td class="px-4 py-3 border-b text-right font-mono">{{ number_format($row['win_amount'], 2) }}</td>
                    <td class="px-4 py-3 border-b text-right">{{ number_format($row['betting_users']) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
