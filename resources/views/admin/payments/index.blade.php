@extends('layouts.app')

@section('title', 'Payments - FreightLink')

@section('content')
<div class="space-y-6">

    <div>
        <h1 class="text-2xl font-bold text-slate-900">Payments</h1>
        <p class="text-sm text-slate-500 mt-1">Online payments made in test mode. Failed attempts are listed too.</p>
    </div>

    <div class="p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 text-sm">
        🧪 <strong>Test mode.</strong> No real money is involved, and no full card number is ever stored. Only the card brand and the last 4 digits are kept.
    </div>

    {{-- Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 flex items-center gap-4">
            <div class="bg-orange-100 w-12 h-12 rounded-xl flex items-center justify-center text-2xl shrink-0">💰</div>
            <div>
                <div class="text-sm text-slate-500">Collected online</div>
                <div class="text-xl font-bold text-slate-900">Rs {{ number_format($collected, 2) }}</div>
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 flex items-center gap-4">
            <div class="bg-green-100 w-12 h-12 rounded-xl flex items-center justify-center text-2xl shrink-0">✅</div>
            <div>
                <div class="text-sm text-slate-500">Successful payments</div>
                <div class="text-xl font-bold text-slate-900">{{ number_format($paid) }}</div>
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 flex items-center gap-4">
            <div class="bg-red-100 w-12 h-12 rounded-xl flex items-center justify-center text-2xl shrink-0">⚠️</div>
            <div>
                <div class="text-sm text-slate-500">Failed attempts</div>
                <div class="text-xl font-bold text-slate-900">{{ number_format($failed) }}</div>
            </div>
        </div>
    </div>

    {{-- The list --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                        <th class="py-2 pr-3">Date</th>
                        <th class="py-2 pr-3 hidden lg:table-cell">Reference</th>
                        <th class="py-2 pr-3">Shipment</th>
                        <th class="py-2 pr-3 hidden md:table-cell">Customer</th>
                        <th class="py-2 pr-3 hidden md:table-cell">Card</th>
                        <th class="py-2 pr-3 text-right">Amount (Rs)</th>
                        <th class="py-2">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($payments as $payment)
                        <tr class="border-t border-slate-100 hover:bg-slate-50 align-top">
                            <td class="py-3 pr-3 whitespace-nowrap">{{ $payment->created_at->format('d M Y, H:i') }}</td>
                            <td class="py-3 pr-3 font-mono text-xs hidden lg:table-cell">{{ $payment->reference }}</td>
                            <td class="py-3 pr-3 whitespace-nowrap">
                                <a href="{{ route('admin.shipments.show', $payment->shipment) }}" class="text-orange-600 font-semibold hover:underline">{{ $payment->shipment->tracking_id }}</a>
                            </td>
                            <td class="py-3 pr-3 hidden md:table-cell">{{ $payment->user->name }}</td>
                            <td class="py-3 pr-3 whitespace-nowrap hidden md:table-cell">
                                @if ($payment->card_last4)
                                    {{ $payment->card_brand }} •••• {{ $payment->card_last4 }}
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>
                            <td class="py-3 pr-3 text-right whitespace-nowrap font-medium">{{ number_format($payment->amount, 2) }}</td>
                            <td class="py-3">
                                @if ($payment->status === 'paid')
                                    <span class="bg-green-100 text-green-800 px-2.5 py-0.5 rounded-full text-xs font-medium">Paid</span>
                                @else
                                    <span class="bg-red-100 text-red-800 px-2.5 py-0.5 rounded-full text-xs font-medium">Failed</span>
                                    @if ($payment->failure_reason)
                                        <div class="text-xs text-slate-500 mt-1">{{ $payment->failure_reason }}</div>
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-8 text-center text-slate-500">No payments yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $payments->links() }}</div>
    </div>
</div>
@endsection