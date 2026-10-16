@extends('layouts.app')
@section('title', 'Merchant Credits')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Merchant Credits</h1>
    <p class="text-sm text-gray-500 mt-0.5">
        Load credit after the merchant buys it. Only the webmaster can change this balance — the merchant sees it read-only in the casino back office.
        Players are blocked from casino games when it reaches ₱0, and a bet larger than the remaining credit is declined even if the player has balance.
    </p>
</div>

@if (session('success'))
    <div class="mb-4 bg-green-50 border border-green-200 text-green-800 text-sm px-4 py-3 rounded-lg">{{ session('success') }}</div>
@endif
@if ($errors->any())
    <div class="mb-4 bg-red-50 border border-red-200 text-red-800 text-sm px-4 py-3 rounded-lg">{{ $errors->first() }}</div>
@endif

<div class="bg-white rounded-lg shadow p-5 mb-6">
    <div class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Current Balance</div>
    <div class="text-3xl font-bold {{ $wallet->balance <= 0 ? 'text-red-600' : 'text-gray-800' }} mt-1">₱{{ number_format((float) $wallet->balance, 2) }}</div>
    <p class="text-xs text-gray-400 mt-2">Updated live with every bet and win. Last update: {{ $wallet->last_reconciled_at ? $wallet->last_reconciled_at->diffForHumans() : 'never yet' }}.</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
    <form method="POST" action="{{ route('webmaster.merchant-credits.topup') }}" class="bg-white rounded-lg shadow p-5">
        @csrf
        <div class="text-sm font-semibold text-gray-700 mb-3">Add Credits</div>
        <label class="block text-xs font-medium text-gray-500 mb-1">Amount (₱)</label>
        <input type="number" name="amount" min="0.01" step="0.01" required class="w-full border border-gray-300 rounded px-3 py-2 text-sm mb-3">
        <label class="block text-xs font-medium text-gray-500 mb-1">Note (optional)</label>
        <input type="text" name="description" maxlength="255" placeholder="e.g. Client purchased 10,000 credits" class="w-full border border-gray-300 rounded px-3 py-2 text-sm mb-4">
        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white text-sm font-semibold px-4 py-2 rounded-lg">Add Credits</button>
    </form>

    <form method="POST" action="{{ route('webmaster.merchant-credits.deduct') }}" class="bg-white rounded-lg shadow p-5"
          onsubmit="return confirm('Deduct these credits from the merchant?')">
        @csrf
        <div class="text-sm font-semibold text-gray-700 mb-3">Deduct Credits</div>
        <label class="block text-xs font-medium text-gray-500 mb-1">Amount (₱)</label>
        <input type="number" name="amount" min="0.01" step="0.01" required class="w-full border border-gray-300 rounded px-3 py-2 text-sm mb-3">
        <label class="block text-xs font-medium text-gray-500 mb-1">Note (optional)</label>
        <input type="text" name="description" maxlength="255" placeholder="e.g. Correction" class="w-full border border-gray-300 rounded px-3 py-2 text-sm mb-4">
        <button type="submit" class="bg-red-600 hover:bg-red-700 text-white text-sm font-semibold px-4 py-2 rounded-lg">Deduct Credits</button>
    </form>
</div>

<form method="POST" action="{{ route('webmaster.merchant-credits.reset') }}" class="bg-white rounded-lg shadow border border-red-200 p-5 mb-6"
      onsubmit="return confirm('Reset the merchant back office? This cannot be undone.')">
    @csrf
    <div class="text-sm font-semibold text-red-700 mb-1">Reset Merchant (Fresh Start)</div>
    <p class="text-xs text-gray-500 mb-3">
        Sets the merchant balance to ₱0, deletes the merchant credit and operation logs, and restarts every back office report
        (dashboard, rankings, trend, retention, players, game/order records) from zero. Player accounts and their game history are not touched.
        Casino games stay blocked for players until you add credits again.
        @if ($wallet->reset_at)
            <br>Last reset: {{ $wallet->reset_at->format('Y-m-d H:i') }}.
        @endif
    </p>
    @error('confirm')<p class="text-xs text-red-600 mb-2">{{ $message }}</p>@enderror
    <div class="flex items-center gap-2">
        <input type="text" name="confirm" placeholder="Type RESET to confirm" autocomplete="off" required class="border border-gray-300 rounded px-3 py-2 text-sm w-56">
        <button type="submit" class="bg-red-600 hover:bg-red-700 text-white text-sm font-semibold px-4 py-2 rounded-lg">Reset Merchant</button>
    </div>
</form>

<div class="bg-white rounded-lg shadow overflow-x-auto">
    <table class="w-full text-sm border-collapse">
        <thead>
            <tr>
                <th class="bg-gray-50 text-left px-4 py-3 border-b font-semibold text-gray-600">Date</th>
                <th class="bg-gray-50 text-left px-4 py-3 border-b font-semibold text-gray-600">Type</th>
                <th class="bg-gray-50 text-right px-4 py-3 border-b font-semibold text-gray-600">Amount</th>
                <th class="bg-gray-50 text-right px-4 py-3 border-b font-semibold text-gray-600">Balance After</th>
                <th class="bg-gray-50 text-left px-4 py-3 border-b font-semibold text-gray-600">By</th>
                <th class="bg-gray-50 text-left px-4 py-3 border-b font-semibold text-gray-600">Note</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($logs as $log)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 border-b text-gray-500 whitespace-nowrap">{{ $log->created_at->format('Y-m-d H:i') }}</td>
                    <td class="px-4 py-3 border-b">{{ ucfirst($log->type) }}</td>
                    <td class="px-4 py-3 border-b text-right font-mono {{ $log->amount < 0 ? 'text-red-600' : 'text-green-700' }}">{{ $log->amount >= 0 ? '+' : '' }}{{ number_format((float) $log->amount, 2) }}</td>
                    <td class="px-4 py-3 border-b text-right font-mono">{{ number_format((float) $log->balance_after, 2) }}</td>
                    <td class="px-4 py-3 border-b text-gray-500">{{ $log->webmaster?->username ?? ($log->admin?->username ?? 'System') }}</td>
                    <td class="px-4 py-3 border-b text-gray-500">{{ $log->description ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-10 text-center text-gray-400">No credit activity yet.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="p-4">{{ $logs->links() }}</div>
</div>
@endsection
