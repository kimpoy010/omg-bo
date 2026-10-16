@extends('layouts.app')
@section('title', request()->routeIs('backoffice.order-records.index') ? 'Order Records' : 'Game Records')

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
    <div>
        <label class="block text-xs font-medium text-gray-500 mb-1">Type</label>
        <select name="type" class="border border-gray-300 rounded px-3 py-2 text-sm">
            <option value="">All</option>
            @foreach ($typeLabels as $value => $label)
                <option value="{{ $value }}" @selected((string) $type === (string) $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs font-medium text-gray-500 mb-1">Username</label>
        <input type="text" name="search" value="{{ $search }}" class="border border-gray-300 rounded px-3 py-2 text-sm">
    </div>
    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-4 py-2 rounded-lg">Search</button>
</form>

<div class="bg-white rounded-lg shadow overflow-x-auto">
    <table class="w-full text-sm border-collapse">
        <thead>
            <tr>
                <th class="bg-gray-50 text-left px-4 py-3 border-b font-semibold text-gray-600">Time</th>
                <th class="bg-gray-50 text-left px-4 py-3 border-b font-semibold text-gray-600">Order ID</th>
                <th class="bg-gray-50 text-left px-4 py-3 border-b font-semibold text-gray-600">Type</th>
                <th class="bg-gray-50 text-left px-4 py-3 border-b font-semibold text-gray-600">Player</th>
                <th class="bg-gray-50 text-left px-4 py-3 border-b font-semibold text-gray-600">Game ID</th>
                <th class="bg-gray-50 text-right px-4 py-3 border-b font-semibold text-gray-600">Money</th>
                <th class="bg-gray-50 text-right px-4 py-3 border-b font-semibold text-gray-600">Balance After</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($records as $r)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 border-b text-gray-500 whitespace-nowrap">{{ $r->created_at->format('Y-m-d H:i:s') }}</td>
                    <td class="px-4 py-3 border-b font-mono text-xs text-gray-500">{{ $r->order_id }}</td>
                    <td class="px-4 py-3 border-b">
                        <span class="inline-block text-xs font-semibold px-2 py-0.5 rounded bg-indigo-50 text-indigo-700">
                            {{ $typeLabels[$r->type] ?? $r->type }}
                        </span>
                    </td>
                    <td class="px-4 py-3 border-b">{{ $r->user->username ?? '—' }}</td>
                    <td class="px-4 py-3 border-b text-gray-500">{{ $r->game_id ?? '—' }}</td>
                    <td class="px-4 py-3 border-b text-right font-mono {{ $r->money < 0 ? 'text-red-600' : 'text-green-700' }}">
                        {{ number_format((float) $r->money, 4) }}
                    </td>
                    <td class="px-4 py-3 border-b text-right font-mono text-gray-700">{{ number_format((float) $r->balance_after, 4) }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-4 py-10 text-center text-gray-400">No records found.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="p-4">{{ $records->links() }}</div>
</div>
@endsection
