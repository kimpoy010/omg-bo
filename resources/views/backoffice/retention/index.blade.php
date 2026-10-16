@extends('layouts.app')
@section('title', 'Betting Retention')

@section('content')
<form method="GET" class="bg-white rounded-lg shadow p-4 mb-4 flex flex-wrap items-end gap-3">
    <div>
        <label class="block text-xs font-medium text-gray-500 mb-1">Cohort From</label>
        <input type="date" name="from" value="{{ $from }}" class="border border-gray-300 rounded px-3 py-2 text-sm">
    </div>
    <div>
        <label class="block text-xs font-medium text-gray-500 mb-1">Cohort To</label>
        <input type="date" name="to" value="{{ $to }}" class="border border-gray-300 rounded px-3 py-2 text-sm">
    </div>
    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-4 py-2 rounded-lg">Search</button>
</form>

<p class="text-xs text-gray-500 mb-3">
    Each row is a cohort of players whose very first casino bet fell on that date. The percentage is how many of
    them placed another bet exactly N days later.
</p>

<div class="bg-white rounded-lg shadow overflow-x-auto">
    <table class="w-full text-sm border-collapse">
        <thead>
            <tr>
                <th class="bg-gray-50 text-left px-4 py-3 border-b font-semibold text-gray-600">Cohort Date</th>
                <th class="bg-gray-50 text-right px-4 py-3 border-b font-semibold text-gray-600">New Bettors</th>
                @foreach ($offsets as $offset)
                    <th class="bg-gray-50 text-right px-4 py-3 border-b font-semibold text-gray-600">Day {{ $offset }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 border-b">{{ $row['day'] }}</td>
                    <td class="px-4 py-3 border-b text-right">{{ number_format($row['cohort_size']) }}</td>
                    @foreach ($offsets as $offset)
                        <td class="px-4 py-3 border-b text-right">
                            {{ $row['retained'][$offset] !== null ? $row['retained'][$offset] . '%' : '—' }}
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr><td colspan="{{ 2 + count($offsets) }}" class="px-4 py-10 text-center text-gray-400">No data for this range.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
