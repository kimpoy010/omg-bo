@extends('layouts.app')
@section('title', 'Merchant Credits')

@section('content')
<div class="mb-6 bg-white rounded-lg shadow p-5">
    <div class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Current Balance</div>
    <div class="text-3xl font-bold {{ $wallet->balance <= 0 ? 'text-red-600' : 'text-gray-800' }} mt-1">
        ₱{{ number_format((float) $wallet->balance, 2) }}
    </div>
</div>

<p class="mb-6 text-sm text-gray-500">
    Credits are loaded by your provider after a purchase. To top up, contact them with the amount you need.
</p>

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
                    <td class="px-4 py-3 border-b">
                        <span class="inline-block text-xs font-semibold px-2 py-0.5 rounded
                            {{ match($log->type) {
                                'topup' => 'bg-green-50 text-green-700',
                                'deduction' => 'bg-red-50 text-red-700',
                                default => 'bg-indigo-50 text-indigo-700',
                            } }}">
                            {{ ucfirst($log->type) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 border-b text-right font-mono {{ $log->amount < 0 ? 'text-red-600' : 'text-green-700' }}">
                        {{ $log->amount >= 0 ? '+' : '' }}{{ number_format((float) $log->amount, 2) }}
                    </td>
                    <td class="px-4 py-3 border-b text-right font-mono text-gray-700">{{ number_format((float) $log->balance_after, 2) }}</td>
                    <td class="px-4 py-3 border-b text-gray-500">{{ $log->webmaster ? 'Webmaster' : ($log->admin?->name ?? $log->admin?->username ?? 'System') }}</td>
                    <td class="px-4 py-3 border-b text-gray-500">{{ $log->description ?? '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-4 py-10 text-center text-gray-400">No credit activity yet.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    <div class="p-4">{{ $logs->links() }}</div>
</div>
@endsection
