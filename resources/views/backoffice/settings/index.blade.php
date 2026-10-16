@extends('layouts.app')
@section('title', 'Callback URLs')

@section('content')
<p class="text-sm text-gray-500 mb-4">Register the endpoints OMG calls on your system. Each must be a public <code>https://</code> URL. Leave a field blank to clear it.</p>

<form method="POST" action="{{ route('backoffice.settings.update') }}" class="bg-white rounded-lg shadow p-5 max-w-2xl space-y-4">
    @csrf
    @foreach ($fields as $field => $label)
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">{{ $label }}</label>
            <input type="url" name="{{ $field }}" value="{{ old($field, $urls->$field) }}" placeholder="https://example.com/api/..."
                   class="w-full border border-gray-300 rounded px-3 py-2 text-sm @error($field) border-red-400 @enderror">
        </div>
    @endforeach

    <div class="flex items-center gap-3">
        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded">Save</button>
        @if ($urls->updated_at && $urls->updated_by)
            <span class="text-xs text-gray-400">Last saved {{ $urls->updated_at->diffForHumans() }}</span>
        @endif
    </div>
</form>
@endsection
