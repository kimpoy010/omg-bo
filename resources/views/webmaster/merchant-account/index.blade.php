@extends('layouts.app')
@section('title', 'Merchant Account')

@section('content')
<p class="text-sm text-gray-500 mb-4">The merchant's login for the back office. There is one merchant account per deployment; only the webmaster can change it.</p>

<form method="POST" action="{{ route('webmaster.merchant-account.update') }}" class="bg-white rounded-lg shadow p-5 max-w-xl" autocomplete="off">
    @csrf
    <div class="text-sm font-semibold text-gray-700 mb-3">Merchant Login</div>

    <label class="block text-xs font-medium text-gray-600 mb-1">Username</label>
    <input type="text" name="username" value="{{ old('username', $merchant?->username) }}" required autocomplete="off"
           class="w-full border border-gray-300 rounded px-3 py-2 text-sm @error('username') border-red-400 @enderror">

    <label class="block text-xs font-medium text-gray-600 mb-1 mt-3">New password</label>
    <input type="password" name="password" autocomplete="new-password" minlength="8" placeholder="{{ $merchant ? 'Leave blank to keep the current password' : 'Required' }}"
           class="w-full border border-gray-300 rounded px-3 py-2 text-sm @error('password') border-red-400 @enderror">

    <label class="block text-xs font-medium text-gray-600 mb-1 mt-3">Confirm new password</label>
    <input type="password" name="password_confirmation" autocomplete="new-password" minlength="8"
           class="w-full border border-gray-300 rounded px-3 py-2 text-sm">

    <p class="text-[11px] text-gray-400 mt-2">Minimum 8 characters. A merchant who is already signed in stays signed in until their session ends.</p>
    <button type="submit" class="mt-4 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded">{{ $merchant ? 'Update Merchant Login' : 'Create Merchant Login' }}</button>
</form>
@endsection
