@extends('layouts.app')
@section('title', 'Game Config')

@section('content')
<div class="mb-4 bg-amber-50 border border-amber-200 text-amber-800 text-sm px-4 py-3 rounded-lg">
    Changes here don't take effect on their own — each one is sent to the webmaster as a change request,
    who applies it in OMG's back office.
</div>

@if ($loadError)
    <div class="mb-4 bg-red-50 border border-red-200 text-red-800 text-sm px-4 py-3 rounded-lg">Couldn't load the game list from OMG: {{ $loadError }}</div>
@endif

<form method="GET" class="bg-white rounded-lg shadow p-4 mb-4 flex flex-wrap items-end gap-3">
    <div>
        <label class="block text-xs font-medium text-gray-500 mb-1">Provider</label>
        <select name="provider" class="border border-gray-300 rounded px-3 py-2 text-sm">
            <option value="">All</option>
            @foreach ($providers as $p)
                <option value="{{ $p }}" @selected($provider === $p)>{{ $p }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs font-medium text-gray-500 mb-1">Game</label>
        <input type="text" name="search" value="{{ $search }}" placeholder="Name or ID" class="border border-gray-300 rounded px-3 py-2 text-sm">
    </div>
    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-4 py-2 rounded-lg">Search</button>
</form>

<div class="bg-white rounded-lg shadow overflow-x-auto">
    <table class="w-full text-sm border-collapse">
        <thead>
            <tr>
                <th class="bg-gray-50 text-left px-4 py-3 border-b font-semibold text-gray-600">Game</th>
                <th class="bg-gray-50 text-left px-4 py-3 border-b font-semibold text-gray-600">Game ID</th>
                <th class="bg-gray-50 text-left px-4 py-3 border-b font-semibold text-gray-600">Provider</th>
                <th class="bg-gray-50 text-left px-4 py-3 border-b font-semibold text-gray-600">Requested Status</th>
                <th class="bg-gray-50 text-left px-4 py-3 border-b font-semibold text-gray-600">Update Status</th>
                <th class="bg-gray-50 px-4 py-3 border-b"></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($games as $game)
                @php $status = $configs[$game['id']]->settings['game_status'] ?? null; @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 border-b font-medium text-gray-800">{{ $game['name'] }}</td>
                    <td class="px-4 py-3 border-b font-mono text-xs text-gray-500">{{ $game['id'] }}</td>
                    <td class="px-4 py-3 border-b">{{ $game['provider'] }}</td>
                    <td class="px-4 py-3 border-b">{{ \App\Support\GameConfigFields::display('game_status', $status) }}</td>
                    <td class="px-4 py-3 border-b">
                        @if ($u = $updates[$game['id']] ?? null)
                            @if ($u['processing'])
                                <span class="inline-block bg-amber-100 text-amber-700 text-xs font-semibold px-2 py-0.5 rounded-full">Processing</span>
                            @else
                                <span class="inline-block bg-green-100 text-green-700 text-xs font-semibold px-2 py-0.5 rounded-full">Processed</span>
                            @endif
                        @else
                            <span class="text-gray-300">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 border-b text-right">
                        <a href="{{ route('backoffice.game-config.edit', $game['id']) }}" class="text-indigo-600 hover:underline text-xs font-medium">Edit</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-10 text-center text-gray-400">No games found (or the OMG game list couldn't be loaded).</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
