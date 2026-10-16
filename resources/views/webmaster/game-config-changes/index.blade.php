@extends('layouts.app')
@section('title', 'Game Config Changes')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Game Config Changes</h1>
    <p class="text-sm text-gray-500 mt-0.5">
        Changes the merchant has requested in the casino back office. Apply each in OMG's back office, then mark it applied here.
        <strong>{{ $pendingCount }}</strong> pending.
    </p>
</div>

@if (session('success'))
    <div class="mb-4 bg-green-50 border border-green-200 text-green-800 text-sm px-4 py-3 rounded-lg">{{ session('success') }}</div>
@endif

<div class="flex flex-wrap items-center gap-2 mb-4">
    @foreach (['pending' => 'Pending', 'applied' => 'Applied', 'all' => 'All'] as $value => $label)
        <a href="{{ route('webmaster.game-config-changes.index', ['status' => $value, 'game' => $game]) }}"
           class="px-4 py-2 rounded-lg text-sm font-semibold {{ $status === $value ? 'bg-blue-600 text-white' : 'bg-white text-gray-600 border border-gray-200' }}">{{ $label }}</a>
    @endforeach
    <form method="GET" class="ml-auto flex gap-2">
        <input type="hidden" name="status" value="{{ $status }}">
        <input type="text" name="game" value="{{ $game }}" placeholder="Game name or ID" class="border border-gray-300 rounded px-3 py-2 text-sm">
        <button class="bg-gray-700 text-white text-sm px-4 py-2 rounded-lg">Filter</button>
    </form>
</div>

<form method="POST" action="{{ route('webmaster.game-config-changes.apply-all') }}">
    @csrf
    <div class="bg-white rounded-lg shadow overflow-x-auto">
        <table class="w-full text-sm border-collapse">
            <thead>
                <tr>
                    <th class="bg-gray-50 px-4 py-3 border-b w-8"><input type="checkbox" onclick="document.querySelectorAll('.chg').forEach(c => c.checked = this.checked)"></th>
                    <th class="bg-gray-50 text-left px-4 py-3 border-b font-semibold text-gray-600">Game</th>
                    <th class="bg-gray-50 text-left px-4 py-3 border-b font-semibold text-gray-600">Setting</th>
                    <th class="bg-gray-50 text-left px-4 py-3 border-b font-semibold text-gray-600">Previous</th>
                    <th class="bg-gray-50 text-left px-4 py-3 border-b font-semibold text-gray-600">Change To</th>
                    <th class="bg-gray-50 text-left px-4 py-3 border-b font-semibold text-gray-600">Requested</th>
                    <th class="bg-gray-50 text-left px-4 py-3 border-b font-semibold text-gray-600">Status</th>
                    <th class="bg-gray-50 px-4 py-3 border-b"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($changes as $c)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 border-b">
                            @if ($c->status === 'pending')<input type="checkbox" class="chg" name="ids[]" value="{{ $c->id }}">@endif
                        </td>
                        <td class="px-4 py-3 border-b">
                            <div class="font-medium text-gray-800">{{ $c->game_name }}</div>
                            <div class="text-xs text-gray-400">{{ $c->provider }} · ID {{ $c->game_id }}</div>
                        </td>
                        <td class="px-4 py-3 border-b">{{ \App\Support\GameConfigFields::label($c->field) }}</td>
                        <td class="px-4 py-3 border-b text-gray-500">{{ \App\Support\GameConfigFields::display($c->field, $c->previous_value) }}</td>
                        <td class="px-4 py-3 border-b font-semibold text-gray-800">{{ \App\Support\GameConfigFields::display($c->field, $c->new_value) }}</td>
                        <td class="px-4 py-3 border-b text-gray-500 whitespace-nowrap">{{ $c->created_at->format('Y-m-d H:i') }}</td>
                        <td class="px-4 py-3 border-b">
                            @if ($c->status === 'applied')
                                <span class="inline-block bg-green-100 text-green-700 text-xs font-semibold px-2 py-0.5 rounded-full">Applied</span>
                                <div class="text-[11px] text-gray-400 mt-0.5">{{ $c->appliedBy?->username }} · {{ $c->applied_at?->format('M j H:i') }}</div>
                            @else
                                <span class="inline-block bg-amber-100 text-amber-700 text-xs font-semibold px-2 py-0.5 rounded-full">Pending</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 border-b text-right whitespace-nowrap">
                            <button type="submit" form="toggle-{{ $c->id }}" class="text-xs font-medium {{ $c->status === 'pending' ? 'text-blue-600' : 'text-gray-400' }} hover:underline">
                                {{ $c->status === 'pending' ? 'Mark applied' : 'Undo' }}
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-4 py-10 text-center text-gray-400">Nothing here.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4 flex items-center justify-between">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded">Mark selected as applied</button>
            <div>{{ $changes->links() }}</div>
        </div>
    </div>
</form>

{{-- Per-row toggle forms live outside the bulk form (nested forms are invalid HTML); buttons point at them via form="". --}}
@foreach ($changes as $c)
    <form id="toggle-{{ $c->id }}" method="POST" action="{{ route('webmaster.game-config-changes.toggle', $c) }}">@csrf</form>
@endforeach
@endsection
