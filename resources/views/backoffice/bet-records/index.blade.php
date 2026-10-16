@extends('layouts.app')
@section('title', 'OMG Bet Records')

@section('content')
<p class="text-sm text-gray-500 mb-4 max-w-3xl">
    Bet records fetched live from OMG. All times are <strong>UTC</strong>, and OMG allows at most 24 hours per search.
    "In our records" shows whether the round also exists in our own transactions.
</p>

<form method="GET" class="bg-white rounded-lg shadow p-4 mb-4 flex flex-wrap items-end gap-3">
    <div>
        <label class="block text-xs font-medium text-gray-500 mb-1">From (UTC)</label>
        <input type="datetime-local" name="from" value="{{ $from }}" class="border border-gray-300 rounded px-3 py-2 text-sm">
    </div>
    <div>
        <label class="block text-xs font-medium text-gray-500 mb-1">To (UTC)</label>
        <input type="datetime-local" name="to" value="{{ $to }}" class="border border-gray-300 rounded px-3 py-2 text-sm">
    </div>
    <div>
        <label class="block text-xs font-medium text-gray-500 mb-1">Username</label>
        <input type="text" name="search" value="{{ $search }}" placeholder="all players" class="border border-gray-300 rounded px-3 py-2 text-sm">
    </div>
    <div>
        <label class="block text-xs font-medium text-gray-500 mb-1">Game ID</label>
        <input type="text" name="game_id" value="{{ $gameId }}" placeholder="all games" class="border border-gray-300 rounded px-3 py-2 text-sm w-28">
    </div>
    <div>
        <label class="block text-xs font-medium text-gray-500 mb-1">Per page</label>
        <select name="size" class="border border-gray-300 rounded px-3 py-2 text-sm">
            @foreach ($sizes as $s)
                <option value="{{ $s }}" @selected($size === $s)>{{ $s }}</option>
            @endforeach
        </select>
    </div>
    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-4 py-2 rounded-lg">Search</button>
</form>

@if ($clamped)
    <div class="mb-4 bg-amber-50 border border-amber-200 text-amber-800 text-sm px-4 py-3 rounded-lg">The range was longer than 24 hours, so it was shortened to the 24 hours ending at "To".</div>
@endif
@if ($error)
    <div class="mb-4 bg-red-50 border border-red-200 text-red-800 text-sm px-4 py-3 rounded-lg">{{ $error }}</div>
@endif

<div class="grid grid-cols-3 gap-4 mb-4 max-w-2xl">
    <div class="bg-white rounded-lg shadow p-4">
        <div class="text-xs text-gray-500">Bet (this page)</div>
        <div class="text-lg font-bold">{{ number_format($totals['bet'], 2) }}</div>
    </div>
    <div class="bg-white rounded-lg shadow p-4">
        <div class="text-xs text-gray-500">Win (this page)</div>
        <div class="text-lg font-bold">{{ number_format($totals['win'], 2) }}</div>
    </div>
    <div class="bg-white rounded-lg shadow p-4">
        <div class="text-xs text-gray-500">Player win/loss</div>
        @php $wl = $totals['win'] - $totals['bet']; @endphp
        <div class="text-lg font-bold {{ $wl < 0 ? 'text-red-600' : 'text-green-700' }}">{{ number_format($wl, 2) }}</div>
    </div>
</div>

<div class="bg-white rounded-lg shadow overflow-x-auto">
    <table class="w-full text-sm border-collapse">
        <thead>
            <tr>
                @foreach (['Time (UTC)' => 'left', 'Player' => 'left', 'Game' => 'left', 'Round' => 'left', 'Bet' => 'right', 'Win' => 'right', 'W/L' => 'right', 'Balance before' => 'right', 'Balance after' => 'right', 'In our records' => 'left'] as $h => $align)
                    <th class="bg-gray-50 text-{{ $align }} px-4 py-3 border-b font-semibold text-gray-600">{{ $h }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
        @forelse ($records as $r)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 border-b text-gray-500 whitespace-nowrap">{{ $r['time']->format('Y-m-d H:i:s') }}</td>
                <td class="px-4 py-3 border-b">{{ $r['player'] }}</td>
                <td class="px-4 py-3 border-b text-gray-600">{{ $r['game'] }}@if ($r['free']) <span class="ml-1 text-[10px] bg-purple-50 text-purple-700 px-1.5 py-0.5 rounded">Free spins</span>@endif</td>
                <td class="px-4 py-3 border-b font-mono text-xs text-gray-500">{{ $r['round_id'] }}</td>
                <td class="px-4 py-3 border-b text-right font-mono">{{ number_format($r['bet'], 2) }}</td>
                <td class="px-4 py-3 border-b text-right font-mono">{{ number_format($r['win'], 2) }}</td>
                @php $d = $r['win'] - $r['bet']; @endphp
                <td class="px-4 py-3 border-b text-right font-mono {{ $d < 0 ? 'text-red-600' : 'text-green-700' }}">{{ number_format($d, 2) }}</td>
                <td class="px-4 py-3 border-b text-right font-mono text-gray-500">{{ number_format($r['before'], 2) }}</td>
                <td class="px-4 py-3 border-b text-right font-mono text-gray-500">{{ number_format($r['after'], 2) }}</td>
                <td class="px-4 py-3 border-b">
                    @if ($r['matched'])
                        <span class="text-xs text-green-700">Yes</span>
                    @else
                        <span class="text-xs text-amber-600">Not found</span>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="10" class="px-4 py-10 text-center text-gray-400">{{ $error ? 'No data.' : 'No records in this window.' }}</td></tr>
        @endforelse
        </tbody>
    </table>
    <div class="p-4 flex items-center justify-between text-sm">
        <span class="text-gray-400">Page {{ $page }}</span>
        <div class="flex gap-2">
            @if ($page > 1)
                <a href="{{ request()->fullUrlWithQuery(['page' => $page - 1]) }}" class="px-3 py-1.5 rounded border border-gray-300 hover:bg-gray-50">Previous</a>
            @endif
            @if ($hasNext)
                <a href="{{ request()->fullUrlWithQuery(['page' => $page + 1]) }}" class="px-3 py-1.5 rounded border border-gray-300 hover:bg-gray-50">Next</a>
            @endif
        </div>
    </div>
</div>
@endsection
