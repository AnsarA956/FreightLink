@extends('layouts.app')

@section('title', 'My dashboard - FreightLink')

@section('content')
@php
    $hour     = now()->hour;
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');

    $badge = [
        'Pending Payment' => 'bg-purple-100 text-purple-800',
        'Booked'          => 'bg-blue-100 text-blue-800',
        'Assigned'        => 'bg-indigo-100 text-indigo-800',
        'Picked Up'       => 'bg-amber-100 text-amber-800',
        'In Transit'      => 'bg-orange-100 text-orange-800',
        'Delivered'       => 'bg-green-100 text-green-800',
        'Delivery Failed' => 'bg-red-100 text-red-800',
        'Cancelled'       => 'bg-slate-200 text-slate-600',
        'Closed'          => 'bg-emerald-100 text-emerald-800',
    ];

    // label, value, icon, icon background
    $cards = [
        ['My shipments',     number_format($stats['total']),               '📦', 'bg-blue-100'],
        ['On the way',       number_format($stats['active']),              '🚚', 'bg-orange-100'],
        ['Delivered',        number_format($stats['delivered']),           '✅', 'bg-green-100'],
        ['Not paid online',  number_format($stats['unpaid']),              '💳', 'bg-amber-100'],
        ['Total value',      'Rs ' . number_format($stats['value'], 2),    '💰', 'bg-slate-200'],
    ];
@endphp

<div class="space-y-6">

    {{-- Welcome and tracking --}}
    <div class="bg-gradient-to-r from-slate-900 to-slate-700 text-white rounded-2xl p-6 shadow">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold">{{ $greeting }}, {{ auth()->user()->name }}</h1>
                <p class="text-slate-300 text-sm mt-1">Book a shipment, pay, track it and download your invoice.</p>
            </div>
            <a href="{{ route('customer.book.create') }}" class="bg-orange-500 hover:bg-orange-600 font-semibold px-5 py-2.5 rounded-lg transition text-sm">
                ➕ Book a shipment
            </a>
        </div>

        <form id="dash-track" class="mt-5 flex flex-col sm:flex-row gap-3">
            <input type="text" id="dash-track-id" placeholder="Track any shipment: enter a tracking ID, for example FL-123456"
                   class="flex-1 rounded-lg px-4 py-2.5 text-slate-900 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400" required>
            <button type="submit" class="bg-white/10 hover:bg-white/20 px-5 py-2.5 rounded-lg text-sm transition">Track</button>
        </form>
    </div>

    {{-- Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-5 gap-4">
        @foreach ($cards as [$label, $value, $icon, $iconBg])
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4">
                <div class="{{ $iconBg }} w-10 h-10 rounded-xl flex items-center justify-center text-xl mb-3">{{ $icon }}</div>
                <div class="text-xs text-slate-500">{{ $label }}</div>
                <div class="text-lg font-bold text-slate-900 truncate">{{ $value }}</div>
            </div>
        @endforeach
    </div>

    {{-- Waiting for a rating --}}
    @if ($toRate->isNotEmpty())
        <div class="bg-amber-50 border border-amber-200 rounded-2xl p-5">
            <h2 class="font-bold text-amber-900 mb-1">⭐ How was your delivery?</h2>
            <p class="text-sm text-amber-800 mb-3">These shipments are delivered and not rated yet. Your rating helps us improve.</p>
            <div class="flex flex-wrap gap-2">
                @foreach ($toRate as $s)
                    <a href="{{ route('customer.shipments.show', $s) }}"
                       class="bg-white border border-amber-300 hover:bg-amber-100 text-amber-900 text-sm px-3 py-1.5 rounded-lg transition">
                        Rate {{ $s->tracking_id }}
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Recent shipments --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 overflow-x-auto">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-bold text-slate-900">Recent shipments</h2>
            <a href="{{ route('customer.shipments.index') }}" class="text-sm text-orange-600 font-semibold hover:underline">View all →</a>
        </div>

        @if ($recent->isEmpty())
            <div class="text-center py-10">
                <div class="text-4xl mb-2">📦</div>
                <p class="text-slate-600 mb-4">You have no shipments yet.</p>
                <a href="{{ route('customer.book.create') }}" class="bg-orange-500 hover:bg-orange-600 text-white font-semibold px-5 py-2.5 rounded-lg transition text-sm">Book your first shipment</a>
            </div>
        @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                        <th class="py-2 pr-3">Tracking ID</th>
                        <th class="py-2 pr-3">From</th>
                        <th class="py-2 pr-3">To</th>
                        <th class="py-2 pr-3">Status</th>
                        <th class="py-2 pr-3 text-right">Total</th>
                        <th class="py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($recent as $s)
                        <tr class="border-t border-slate-100 hover:bg-slate-50 align-top">
                            <td class="py-3 pr-3">
                                <a href="{{ route('customer.shipments.show', $s) }}" class="text-orange-600 font-semibold hover:underline">{{ $s->tracking_id }}</a>
                            </td>
                            <td class="py-3 pr-3">{{ $s->pickup_address }}</td>
                            <td class="py-3 pr-3">{{ $s->delivery_address }}</td>
                            <td class="py-3 pr-3">
                                <span class="{{ $badge[$s->status] ?? 'bg-slate-100 text-slate-700' }} px-2.5 py-0.5 rounded-full text-xs font-medium whitespace-nowrap">{{ $s->status }}</span>
                            </td>
                            <td class="py-3 pr-3 text-right whitespace-nowrap font-medium">Rs {{ number_format($s->total_price, 2) }}</td>
                            <td class="py-3 text-right whitespace-nowrap">
                                @if ($s->paidPayment)
                                    <span class="text-xs text-green-700 font-medium">Paid</span>
                                @elseif ($s->status !== 'Cancelled')
                                    <a href="{{ route('customer.shipments.pay', $s) }}" class="text-xs bg-green-600 hover:bg-green-700 text-white px-2.5 py-1 rounded-lg">Pay</a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>

<script>
    // Sends the customer to the tracking page of the typed ID
    document.getElementById('dash-track').addEventListener('submit', function (event) {
        event.preventDefault();

        var id = document.getElementById('dash-track-id').value.trim();

        if (id !== '') {
            window.location.href = @json(url('/track')) + '/' + encodeURIComponent(id);
        }
    });
</script>
@endsection