@extends('layouts.app')
@section('title', 'Nurturing & Killing')

@section('content')
@if (session('error'))
    <div class="mb-4 bg-red-50 border border-red-200 text-red-800 text-sm px-4 py-3 rounded-lg">{{ session('error') }}</div>
@endif

<p class="text-sm text-gray-500 mb-4 max-w-3xl">
    Nurturing steers selected players toward winning until they reach the amount you set; killing does the opposite.
    The control ends automatically once the amount is reached. Amounts granted this way are billed separately and are not netted against overall win/loss.
</p>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    {{-- Set / cancel for specific players --}}
    <div class="bg-white rounded-lg shadow p-5">
        <div class="text-sm font-semibold text-gray-700 mb-3">Set Player Nurturing / Killing</div>
        <form method="POST" action="{{ route('backoffice.patron.store') }}" class="space-y-3">
            @csrf
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Usernames</label>
                <textarea name="usernames" rows="3" required placeholder="one or more, separated by commas or new lines"
                          class="w-full border border-gray-300 rounded px-3 py-2 text-sm @error('usernames') border-red-400 @enderror">{{ old('usernames') }}</textarea>
                @error('usernames') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Action</label>
                    <select name="kind" id="patron-kind" class="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                            onchange="document.getElementById('patron-type-wrap').style.display = this.value === 'nurture' ? 'block' : 'none'">
                        <option value="nurture" @selected(old('kind') === 'nurture')>Nurture</option>
                        <option value="kill" @selected(old('kind') === 'kill')>Kill</option>
                    </select>
                </div>
                <div id="patron-type-wrap" style="display: {{ old('kind', 'nurture') === 'nurture' ? 'block' : 'none' }};">
                    <label class="block text-xs font-medium text-gray-500 mb-1">Nurturing speed</label>
                    <select name="patron_type" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                        @foreach ($types as $value => $label)
                            <option value="{{ $value }}" @selected((string) old('patron_type', 0) === (string) $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Amount (per player)</label>
                <input type="number" name="amount" step="0.01" min="0.01" required value="{{ old('amount') }}"
                       class="w-full border border-gray-300 rounded px-3 py-2 text-sm @error('amount') border-red-400 @enderror">
                @error('amount') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                <p class="text-[11px] text-gray-400 mt-1">In your merchant currency. OMG enforces its own per-player maximum.</p>
            </div>
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-4 py-2 rounded-lg">Apply</button>
        </form>

        <form method="POST" action="{{ route('backoffice.patron.cancel') }}" class="mt-5 pt-5 border-t space-y-2"
              onsubmit="return confirm('Cancel nurturing/killing for these players?')">
            @csrf
            <div class="text-sm font-semibold text-gray-700">Cancel for players</div>
            <textarea name="usernames" rows="2" required placeholder="usernames to cancel"
                      class="w-full border border-gray-300 rounded px-3 py-2 text-sm"></textarea>
            <button type="submit" class="bg-red-50 hover:bg-red-100 text-red-700 text-sm font-semibold px-4 py-2 rounded-lg">Cancel control</button>
        </form>
    </div>

    {{-- New-player auto nurturing --}}
    <div class="bg-white rounded-lg shadow p-5">
        <div class="flex items-center justify-between mb-3">
            <div class="text-sm font-semibold text-gray-700">New-Player Auto Nurturing</div>
            <span class="text-[11px] px-2 py-0.5 rounded-full {{ $auto->enabled ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">{{ $auto->enabled ? 'On' : 'Off' }}</span>
        </div>
        <p class="text-xs text-gray-500 mb-3">While on, every newly registered player is nurtured until they reach the amount.</p>

        <form method="POST" action="{{ route('backoffice.patron.auto') }}" class="space-y-3">
            @csrf
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Switch</label>
                <select name="enabled" id="auto-enabled" class="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                        onchange="var on = this.value === '1'; document.getElementById('auto-on').style.display = on ? 'block' : 'none'; document.getElementById('auto-off').style.display = on ? 'none' : 'block'">
                    <option value="1" @selected(old('enabled', $auto->enabled ? '1' : '0') == '1')>On</option>
                    <option value="0" @selected(old('enabled', $auto->enabled ? '1' : '0') == '0')>Off</option>
                </select>
            </div>
            <div id="auto-on" class="space-y-3" style="display: {{ old('enabled', $auto->enabled ? '1' : '0') == '1' ? 'block' : 'none' }};">
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Amount (per player)</label>
                    <input type="number" name="amount" step="0.01" min="0.01" value="{{ old('amount', $auto->amount) }}"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm @error('amount') border-red-400 @enderror">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Nurturing speed</label>
                    <select name="patron_type" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                        @foreach ($types as $value => $label)
                            <option value="{{ $value }}" @selected((string) old('patron_type', $auto->patron_type) === (string) $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <label id="auto-off" class="flex items-center gap-2 text-xs text-gray-600" style="display: {{ old('enabled', $auto->enabled ? '1' : '0') == '1' ? 'none' : 'block' }};">
                <input type="checkbox" name="cancel_all" value="1"> Also end nurturing for players already being auto-nurtured
            </label>
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-4 py-2 rounded-lg">Save</button>
        </form>
    </div>
</div>

<div class="bg-white rounded-lg shadow overflow-x-auto mb-6">
    <div class="px-4 py-3 border-b text-sm font-semibold text-gray-700">Active controls</div>
    <table class="w-full text-sm">
        <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
            <tr>
                <th class="text-left px-4 py-3">Player</th>
                <th class="text-left px-4 py-3">Action</th>
                <th class="text-right px-4 py-3">Amount</th>
                <th class="text-left px-4 py-3">Set by</th>
                <th class="text-left px-4 py-3">Set</th>
            </tr>
        </thead>
        <tbody>
        @forelse ($active as $c)
            <tr>
                <td class="px-4 py-3 border-b font-medium">{{ $c->player?->username }}</td>
                <td class="px-4 py-3 border-b">{{ ucfirst($c->kind) }}@if ($c->kind === 'nurture') <span class="text-gray-400">({{ $types[$c->patron_type] ?? '—' }})</span>@endif</td>
                <td class="px-4 py-3 border-b text-right">{{ number_format((float) $c->amount, 2) }}</td>
                <td class="px-4 py-3 border-b text-gray-500">{{ $c->setBy?->name ?? $c->setBy?->username ?? '—' }}</td>
                <td class="px-4 py-3 border-b text-gray-500">{{ $c->created_at->format('Y-m-d H:i') }}</td>
            </tr>
        @empty
            <tr><td colspan="5" class="px-4 py-6 text-center text-gray-400">No active controls.</td></tr>
        @endforelse
        </tbody>
    </table>
    <div class="p-4">{{ $active->links() }}</div>
    <p class="px-4 pb-4 text-[11px] text-gray-400">This list is our record of what was sent to OMG. OMG ends a control by itself once the player reaches the amount, which is not reported back here — cancel to clear an entry.</p>
</div>

@if ($history->isNotEmpty())
<div class="bg-white rounded-lg shadow overflow-x-auto">
    <div class="px-4 py-3 border-b text-sm font-semibold text-gray-700">Recently cancelled / replaced</div>
    <table class="w-full text-sm">
        <tbody>
        @foreach ($history as $c)
            <tr>
                <td class="px-4 py-2 border-b">{{ $c->player?->username }}</td>
                <td class="px-4 py-2 border-b text-gray-500">{{ ucfirst($c->kind) }} · {{ number_format((float) $c->amount, 2) }}</td>
                <td class="px-4 py-2 border-b text-gray-400">{{ $c->cancelled_at?->format('Y-m-d H:i') }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif
@endsection
