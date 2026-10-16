@extends('layouts.app')
@section('title', 'Player List')

@section('content')
<form method="GET" class="mb-4">
    <input type="text" name="search" value="{{ $search }}" placeholder="Search username…"
           class="border border-gray-300 rounded px-3 py-2 text-sm w-72">
    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-4 py-2 rounded-lg ml-2">Search</button>
</form>

<div class="bg-white rounded-lg shadow overflow-x-auto">
    <table class="w-full text-sm border-collapse">
        <thead>
            <tr>
                <th class="bg-gray-50 text-left px-4 py-3 border-b font-semibold text-gray-600">Username</th>
                <th class="bg-gray-50 text-right px-4 py-3 border-b font-semibold text-gray-600">Lifetime Valid Bet</th>
                <th class="bg-gray-50 text-right px-4 py-3 border-b font-semibold text-gray-600">Lifetime Win</th>
                <th class="bg-gray-50 text-right px-4 py-3 border-b font-semibold text-gray-600">Net Win/Loss</th>
                <th class="bg-gray-50 text-right px-4 py-3 border-b font-semibold text-gray-600">Bet Count</th>
                <th class="bg-gray-50 text-left px-4 py-3 border-b font-semibold text-gray-600">Last Bet</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($players as $player)
                @php
                    $bet = abs((float) ($player->bet_raw ?? 0));
                    $win = (float) ($player->win_raw ?? 0);
                @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 border-b font-medium text-gray-800">{{ $player->username }}</td>
                    <td class="px-4 py-3 border-b text-right font-mono">{{ number_format($bet, 2) }}</td>
                    <td class="px-4 py-3 border-b text-right font-mono">{{ number_format($win, 2) }}</td>
                    <td class="px-4 py-3 border-b text-right font-mono {{ ($win - $bet) >= 0 ? 'text-green-700' : 'text-red-600' }}">
                        {{ number_format($win - $bet, 2) }}
                    </td>
                    <td class="px-4 py-3 border-b text-right">{{ number_format($player->bet_count ?? 0) }}</td>
                    <td class="px-4 py-3 border-b text-gray-500">{{ $player->last_bet_at ? \Illuminate\Support\Carbon::parse($player->last_bet_at)->format('Y-m-d H:i') : '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-10 text-center text-gray-400">No players found.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="p-4">{{ $players->links() }}</div>
</div>
@endsection
