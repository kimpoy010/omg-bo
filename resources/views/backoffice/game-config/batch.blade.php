@extends('layouts.app')
@section('title', 'Batch Changes')

@section('content')
<form method="POST" action="{{ route('backoffice.game-config.batch.update') }}" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    @csrf
    <div class="bg-white rounded-lg shadow p-5">
        <div class="text-sm font-semibold text-gray-700 mb-3">1. Select games</div>
        <input type="search" id="game-search" placeholder="Search games or providers…" autocomplete="off"
               class="w-full border border-gray-300 rounded px-3 py-2 text-sm mb-1">
        <div class="flex items-center justify-between text-xs text-gray-400 mb-3">
            <span id="game-selected-count">0 selected</span>
            <button type="button" id="game-clear" class="text-indigo-600 hover:underline">Clear selection</button>
        </div>
        <div id="game-list" class="max-h-[28rem] overflow-y-auto space-y-3">
            @foreach ($games as $provider => $list)
                <div class="bo-provider" data-provider-name="{{ strtolower($provider) }}">
                    <label class="flex items-center gap-2 text-sm font-semibold text-gray-700">
                        <input type="checkbox" class="bo-provider-toggle">
                        {{ $provider }} <span class="text-xs font-normal text-gray-400">({{ $list->count() }})</span>
                    </label>
                    <div class="ml-6 mt-1 grid grid-cols-1 gap-0.5">
                        @foreach ($list as $game)
                            <label class="bo-game flex items-center gap-2 text-xs text-gray-600" data-name="{{ strtolower($game['name']) }}">
                                <input type="checkbox" name="game_ids[]" value="{{ $game['id'] }}" @checked(in_array($game['id'], old('game_ids', [])))>
                                {{ $game['name'] }}
                            </label>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-5 space-y-4 self-start">
        <div class="text-sm font-semibold text-gray-700">2. What to change <span class="font-normal text-gray-400">(blank = leave alone)</span></div>
        @foreach ($fields as $key => $def)
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ $def['label'] }}</label>
                @if ($def['type'] === 'select')
                    <select name="{{ $key }}" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                        <option value="">— unchanged —</option>
                        @foreach ($def['options'] as $value => $label)
                            <option value="{{ $value }}" @selected((string) old($key) === (string) $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                @else
                    <input type="number" name="{{ $key }}" step="{{ $def['step'] }}" min="0" value="{{ old($key) }}"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                @endif
            </div>
        @endforeach
        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-4 py-2 rounded-lg">Submit Changes</button>
    </div>
</form>

<script>
(function () {
    var search   = document.getElementById('game-search');
    var counter  = document.getElementById('game-selected-count');
    var groups   = Array.prototype.slice.call(document.querySelectorAll('.bo-provider'));

    function visibleGames(group) {
        return Array.prototype.slice.call(group.querySelectorAll('.bo-game')).filter(function (g) { return g.style.display !== 'none'; });
    }

    function updateCount() {
        var n = document.querySelectorAll('input[name="game_ids[]"]:checked').length;
        counter.textContent = n + ' selected';
        // Provider box reflects only the games currently shown under it.
        groups.forEach(function (group) {
            var shown = visibleGames(group).map(function (g) { return g.querySelector('input'); });
            var toggle = group.querySelector('.bo-provider-toggle');
            toggle.checked = shown.length > 0 && shown.every(function (c) { return c.checked; });
        });
    }

    function applyFilter() {
        var q = search.value.trim().toLowerCase();
        groups.forEach(function (group) {
            var providerMatch = q !== '' && group.dataset.providerName.indexOf(q) !== -1;
            var any = false;
            group.querySelectorAll('.bo-game').forEach(function (g) {
                var show = q === '' || providerMatch || g.dataset.name.indexOf(q) !== -1;
                g.style.display = show ? '' : 'none';
                any = any || show;
            });
            group.style.display = any ? '' : 'none';
        });
        updateCount();
    }

    groups.forEach(function (group) {
        // Provider checkbox selects/clears only the games visible under the current search.
        group.querySelector('.bo-provider-toggle').addEventListener('change', function () {
            var on = this.checked;
            visibleGames(group).forEach(function (g) { g.querySelector('input').checked = on; });
            updateCount();
        });
    });

    document.querySelectorAll('input[name="game_ids[]"]').forEach(function (c) { c.addEventListener('change', updateCount); });
    document.getElementById('game-clear').addEventListener('click', function () {
        document.querySelectorAll('input[name="game_ids[]"]').forEach(function (c) { c.checked = false; });
        updateCount();
    });
    search.addEventListener('input', applyFilter);
    // Don't let Enter in the search box submit the whole form.
    search.addEventListener('keydown', function (e) { if (e.key === 'Enter') e.preventDefault(); });

    updateCount();
})();
</script>
@endsection
