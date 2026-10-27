@extends('layouts.app')

@section('title', 'My Shipments - FreightLink')

@section('content')
@php
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
@endphp

<div class="space-y-6">

    {{-- Heading --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">My shipments</h1>
            <p class="text-sm text-slate-500 mt-1">All the shipments you have booked.</p>
        </div>
        <a href="{{ route('customer.book.create') }}"
           class="bg-orange-500 hover:bg-orange-600 text-white font-semibold px-5 py-2.5 rounded-lg transition text-sm">
            ➕ Book a shipment
        </a>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">

        @if ($shipments->count() === 0)
            <div class="text-center py-12">
                <div class="text-5xl mb-3">📦</div>
                <p class="text-slate-600 mb-4">You have no shipments yet.</p>
                <a href="{{ route('customer.book.create') }}"
                   class="bg-orange-500 hover:bg-orange-600 text-white font-semibold px-5 py-2.5 rounded-lg transition text-sm">
                    Book your first shipment
                </a>
            </div>
        @else
            {{-- Instant search --}}
            <div class="mb-4">
                <input type="text" id="list-search" placeholder="Search by tracking ID, address or status"
                       class="w-full sm:w-96 border border-slate-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                            <th class="py-2 pr-3">Tracking ID</th>
                            <th class="py-2 pr-3">From → To</th>
                            <th class="py-2 pr-3 hidden md:table-cell">Service</th>
                            <th class="py-2 pr-3">Status</th>
                            <th class="py-2 pr-3 text-right">Total</th>
                            <th class="py-2 hidden lg:table-cell">Booked on</th>
                        </tr>
                    </thead>
                    <tbody id="list-body">
                        @foreach ($shipments as $shipment)
                            <tr class="border-t border-slate-100 hover:bg-slate-50 align-top"
                                data-search="{{ strtolower($shipment->tracking_id . ' ' . $shipment->pickup_address . ' ' . $shipment->delivery_address . ' ' . $shipment->status) }}">
                                <td class="py-3 pr-3 whitespace-nowrap">
                                    <a href="{{ route('customer.shipments.show', $shipment) }}" class="text-orange-600 font-semibold hover:underline">{{ $shipment->tracking_id }}</a>
                                </td>
                                <td class="py-3 pr-3">
                                    {{ \Illuminate\Support\Str::limit($shipment->pickup_address, 28) }}
                                    <span class="text-slate-400">→</span>
                                    {{ \Illuminate\Support\Str::limit($shipment->delivery_address, 28) }}
                                </td>
                                <td class="py-3 pr-3 hidden md:table-cell">{{ $shipment->serviceType->name }}</td>
                                <td class="py-3 pr-3">
                                    <span class="{{ $badge[$shipment->status] ?? 'bg-slate-100 text-slate-700' }} px-2.5 py-0.5 rounded-full text-xs font-medium whitespace-nowrap">{{ $shipment->status }}</span>
                                </td>
                                <td class="py-3 pr-3 text-right whitespace-nowrap font-medium">Rs {{ number_format($shipment->total_price, 2) }}</td>
                                <td class="py-3 whitespace-nowrap text-slate-500 hidden lg:table-cell">{{ $shipment->created_at->format('d M Y, H:i') }}</td>
                            </tr>
                        @endforeach

                        <tr id="list-empty" class="hidden">
                            <td colspan="6" class="py-6 text-center text-slate-500">No shipment matches your search.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            @if (method_exists($shipments, 'links'))
                <div class="mt-4">{{ $shipments->links() }}</div>
            @endif
        @endif
    </div>
</div>

<script>
    (function () {
        var box = document.getElementById('list-search');
        if (!box) { return; }

        var rows  = document.querySelectorAll('#list-body tr[data-search]');
        var empty = document.getElementById('list-empty');

        box.addEventListener('input', function () {
            var text    = box.value.trim().toLowerCase();
            var visible = 0;

            rows.forEach(function (row) {
                var show = row.getAttribute('data-search').indexOf(text) !== -1;
                row.classList.toggle('hidden', !show);
                if (show) { visible++; }
            });

            empty.classList.toggle('hidden', visible !== 0);
        });
    })();
</script>
@endsection