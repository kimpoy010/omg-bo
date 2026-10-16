@extends('layouts.app')
@section('title', 'Edit ' . $game['name'])

@section('content')
<p class="text-sm text-gray-500 mb-4">
    {{ $game['provider'] }} · Game ID <span class="font-mono">{{ $game['id'] }}</span> — leave a field blank to leave it unchanged.
    Values shown are what was last requested here, not read live from OMG.
</p>

<form method="POST" action="{{ route('backoffice.game-config.update', $game['id']) }}" class="bg-white rounded-lg shadow p-5 max-w-2xl space-y-4">
    @csrf
    @foreach ($fields as $key => $def)
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">
                {{ $def['label'] }}
                @if (isset($pending[$key]))
                    @php $isProcessed = $pending[$key]->created_at->lte(now()->subMinutes(3)); @endphp
                    <span class="ml-2 {{ $isProcessed ? 'text-green-600' : 'text-amber-600' }} font-semibold">{{ $isProcessed ? 'processed' : 'processing' }} → {{ \App\Support\GameConfigFields::display($key, $pending[$key]->new_value) }}</span>
                @endif
            </label>
            @if ($def['type'] === 'select')
                <select name="{{ $key }}" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                    <option value="">— unchanged —</option>
                    @foreach ($def['options'] as $value => $label)
                        <option value="{{ $value }}" @selected((string) old($key, $settings[$key] ?? '') === (string) $value)>{{ $label }}</option>
                    @endforeach
                </select>
            @else
                <input type="number" name="{{ $key }}" step="{{ $def['step'] }}" min="0" value="{{ old($key, $settings[$key] ?? '') }}"
                       class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
            @endif
            @isset($def['help'])
                <p class="text-[11px] text-gray-400 mt-1">{{ $def['help'] }}</p>
            @endisset
        </div>
    @endforeach

    <div class="flex items-center gap-3 pt-2">
        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-4 py-2 rounded-lg">Submit Changes</button>
        <a href="{{ route('backoffice.game-config.index') }}" class="text-sm text-gray-500 hover:underline">Cancel</a>
    </div>
</form>
@endsection
