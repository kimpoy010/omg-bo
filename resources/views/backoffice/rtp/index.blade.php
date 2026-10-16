@extends('layouts.app')
@section('title', 'RTP Control')

@section('content')
<div class="bg-white rounded-lg shadow p-5 mb-6 max-w-xl">
    <div class="text-sm font-semibold text-gray-700 mb-3">Set Player RTP Control</div>
    <form method="POST" action="{{ route('backoffice.rtp.store') }}" class="space-y-3">
        @csrf
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Username</label>
            <input type="text" name="username" id="rtp-username" value="{{ old('username') }}" required
                   list="rtp-players" autocomplete="off" placeholder="Start typing a username…"
                   class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
            <datalist id="rtp-players"></datalist>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Control</label>
            <select name="switch" id="switch-select" onchange="document.getElementById('rtp-field').style.display = this.value === '2' ? 'block' : 'none'"
                    class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                <option value="1">Off (no override)</option>
                <option value="2">On — apply a fixed RTP</option>
            </select>
        </div>
        <div id="rtp-field" style="display:none;">
            <label class="block text-xs font-medium text-gray-500 mb-1">RTP</label>
            <select name="rtp" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                @foreach ($rtpOptions as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Control Type</label>
            <select name="control_type" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                <option value="0">Soft</option>
                <option value="1">Hard</option>
            </select>
        </div>
        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-4 py-2 rounded-lg">
            Apply
        </button>
    </form>
</div>

<form method="GET" class="mb-4">
    <input type="text" name="search" value="{{ $search }}" placeholder="Search username…"
           class="border border-gray-300 rounded px-3 py-2 text-sm w-72">
    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-4 py-2 rounded-lg ml-2">Search</button>
</form>

<div class="bg-white rounded-lg shadow overflow-x-auto">
    <table class="w-full text-sm border-collapse">
        <thead>
            <tr>
                <th class="bg-gray-50 text-left px-4 py-3 border-b font-semibold text-gray-600">Player</th>
                <th class="bg-gray-50 text-left px-4 py-3 border-b font-semibold text-gray-600">Status</th>
                <th class="bg-gray-50 text-left px-4 py-3 border-b font-semibold text-gray-600">RTP</th>
                <th class="bg-gray-50 text-left px-4 py-3 border-b font-semibold text-gray-600">Control Type</th>
                <th class="bg-gray-50 text-left px-4 py-3 border-b font-semibold text-gray-600">Set By</th>
                <th class="bg-gray-50 text-left px-4 py-3 border-b font-semibold text-gray-600">Updated</th>
                <th class="bg-gray-50 px-4 py-3 border-b"></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($settings as $setting)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 border-b font-medium text-gray-800">{{ $setting->user->username ?? '—' }}</td>
                    <td class="px-4 py-3 border-b">
                        @if ($setting->isActive())
                            <span class="inline-block bg-green-100 text-green-700 text-xs px-2 py-0.5 rounded-full font-medium">Active</span>
                        @else
                            <span class="inline-block bg-gray-100 text-gray-500 text-xs px-2 py-0.5 rounded-full font-medium">Off</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 border-b">{{ $setting->isActive() ? $setting->rtpPercent() : '—' }}</td>
                    <td class="px-4 py-3 border-b">{{ $setting->control_type === 1 ? 'Hard' : 'Soft' }}</td>
                    <td class="px-4 py-3 border-b text-gray-500">
                        {{ $setting->setBy?->name ?? $setting->setBy?->username ?? '—' }}
                    </td>
                    <td class="px-4 py-3 border-b text-gray-500">{{ $setting->updated_at->format('Y-m-d H:i') }}</td>
                    <td class="px-4 py-3 border-b text-right">
                        @if ($setting->isActive())
                            <form method="POST" action="{{ route('backoffice.rtp.destroy', $setting->user) }}"
                                  onsubmit="return confirm('Remove RTP control for {{ $setting->user->username }}?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-600 hover:underline text-xs font-medium">Remove</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-4 py-10 text-center text-gray-400">No RTP overrides set.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="p-4">{{ $settings->links() }}</div>
</div>

<script>
(function () {
    var input = document.getElementById('rtp-username');
    var list  = document.getElementById('rtp-players');
    var url   = @json(route('backoffice.rtp.players'));
    var timer = null, seq = 0;

    input.addEventListener('input', function () {
        clearTimeout(timer);
        var q = input.value.trim();
        if (!q) { list.innerHTML = ''; return; }

        timer = setTimeout(function () {
            var mine = ++seq;
            fetch(url + '?q=' + encodeURIComponent(q), { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
                .then(function (r) { return r.ok ? r.json() : []; })
                .then(function (names) {
                    if (mine !== seq) return; // a newer keystroke superseded this response
                    list.innerHTML = '';
                    names.forEach(function (n) {
                        var o = document.createElement('option');
                        o.value = n;
                        list.appendChild(o);
                    });
                })
                .catch(function () {});
        }, 200);
    });
})();
</script>
@endsection
