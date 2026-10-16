@extends('layouts.app')
@section('title', 'Operation Log')

@section('content')
<form method="GET" class="mb-4 flex items-center gap-2">
    <select name="action" class="border border-gray-300 rounded px-3 py-2 text-sm">
        <option value="">All actions</option>
        @foreach ($actions as $a)
            <option value="{{ $a }}" @selected($action === $a)>{{ $a }}</option>
        @endforeach
    </select>
    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-4 py-2 rounded-lg">Filter</button>
</form>

<div class="bg-white rounded-lg shadow overflow-x-auto">
    <table class="w-full text-sm border-collapse">
        <thead>
            <tr>
                <th class="bg-gray-50 text-left px-4 py-3 border-b font-semibold text-gray-600">Time</th>
                <th class="bg-gray-50 text-left px-4 py-3 border-b font-semibold text-gray-600">Admin</th>
                <th class="bg-gray-50 text-left px-4 py-3 border-b font-semibold text-gray-600">Action</th>
                <th class="bg-gray-50 text-left px-4 py-3 border-b font-semibold text-gray-600">Description</th>
                <th class="bg-gray-50 text-left px-4 py-3 border-b font-semibold text-gray-600">IP</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($logs as $log)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 border-b text-gray-500 whitespace-nowrap">{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                    <td class="px-4 py-3 border-b">{{ $log->admin?->name ?? $log->admin?->username ?? '—' }}</td>
                    <td class="px-4 py-3 border-b">
                        <span class="inline-block text-xs font-semibold px-2 py-0.5 rounded bg-indigo-50 text-indigo-700">{{ $log->action }}</span>
                    </td>
                    <td class="px-4 py-3 border-b text-gray-600">{{ $log->description ?? '—' }}</td>
                    <td class="px-4 py-3 border-b text-gray-400 font-mono text-xs">{{ $log->ip_address ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-4 py-10 text-center text-gray-400">No activity logged yet.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="p-4">{{ $logs->links() }}</div>
</div>
@endsection
