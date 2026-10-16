@extends('layouts.app')
@section('title', 'Rankings')

@section('content')
<div class="flex items-center gap-2 mb-4">
    @foreach (['win' => 'Win Money List', 'loss' => 'Loss Money List', 'bet' => 'Bet Amount List'] as $key => $label)
        <a href="{{ route('backoffice.rankings.index', ['tab' => $key, 'from' => $from, 'to' => $to]) }}"
           class="px-4 py-2 rounded-lg text-sm font-semibold {{ $tab === $key ? 'bg-indigo-600 text-white' : 'bg-white text-gray-600 border border-gray-200' }}">
            {{ $label }}
        </a>
    @endforeach
</div>

<form method="GET" class="bg-white rounded-lg shadow p-4 mb-4 flex flex-wrap items-end gap-3">
    <input type="hidden" name="tab" value="{{ $tab }}">
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

<div class="bg-white rounded-lg shadow overflow-x-auto">
    <table class="w-full text-sm border-collapse">
        <thead>
            <tr>
                <th class="bg-gray-50 text-left px-4 py-3 border-b font-semibold text-gray-600">Rank</th>
                <th class="bg-gray-50 text-left px-4 py-3 border-b font-semibold text-gray-600">Player</th>
                <th class="bg-gray-50 text-right px-4 py-3 border-b font-semibold text-gray-600">
                    {{ $tab === 'bet' ? 'Bet Amount' : ($tab === 'loss' ? 'Loss Value' : 'Profit Value') }}
                </th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $i => $row)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 border-b font-bold {{ $i === 0 ? 'text-red-600' : 'text-gray-600' }}">NO.{{ $i + 1 }}</td>
                    <td class="px-4 py-3 border-b">{{ $row->username }}</td>
                    <td class="px-4 py-3 border-b text-right font-mono {{ $tab === 'loss' ? 'text-red-600' : 'text-green-700' }}">
                        {{ number_format((float) $row->metric, 2) }}
                    </td>
                </tr>
            @empty
                <tr><td colspan="3" class="px-4 py-10 text-center text-gray-400">No data for this range.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
