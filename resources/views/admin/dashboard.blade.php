@extends('layouts.app')

@section('title', 'Admin Panel - FreightLink')

@section('content')
<div class="max-w-6xl space-y-6">
    <h1 class="text-2xl font-bold">Admin Panel</h1>

    {{-- Summary cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        @php
            $cards = [
                ['Total shipments',   number_format($stats['total'])],
                ['Booked today',      number_format($stats['today'])],
                ['Awaiting driver',   number_format($stats['awaiting'])],
                ['Delivered',         number_format($stats['delivered'])],
                ['Revenue',           'Rs ' . number_format($stats['revenue'], 2)],
                ['Customers',         number_format($stats['customers'])],
                ['Drivers',           number_format($stats['drivers'])],
                ['Vehicles',          number_format($stats['vehicles'])],
            ];
        @endphp
        @foreach ($cards as [$label, $value])
            <div class="bg-white rounded-xl shadow p-4">
                <div class="text-sm text-slate-500">{{ $label }}</div>
                <div class="text-2xl font-bold mt-1">{{ $value }}</div>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        {{-- Status breakdown --}}
        <div class="bg-white rounded-xl shadow p-6">
            <h2 class="text-lg font-bold mb-3">Shipments by status</h2>
            <table class="w-full text-sm">
                @foreach ($statuses as $status)
                    <tr class="border-t first:border-t-0">
                        <td class="py-2">
                            <a href="{{ route('admin.shipments.index', ['status' => $status]) }}" class="text-blue-600 hover:underline">{{ $status }}</a>
                        </td>
                        <td class="py-2 text-right font-medium">{{ $byStatus[$status] ?? 0 }}</td>
                    </tr>
                @endforeach
            </table>
        </div>

        {{-- Recent shipments --}}
        <div class="bg-white rounded-xl shadow p-6 md:col-span-2">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-lg font-bold">Recent shipments</h2>
                <a href="{{ route('admin.shipments.index') }}" class="text-sm text-blue-600 underline">View all</a>
            </div>
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-slate-500">
                        <th class="py-2">Tracking ID</th>
                        <th class="py-2">Customer</th>
                        <th class="py-2">Status</th>
                        <th class="py-2">Driver</th>
                        <th class="py-2 text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recent as $shipment)
                        <tr class="border-t">
                            <td class="py-2">
                                <a href="{{ route('admin.shipments.show', $shipment) }}" class="text-blue-600 underline">{{ $shipment->tracking_id }}</a>
                            </td>
                            <td class="py-2">{{ $shipment->user->name }}</td>
                            <td class="py-2">{{ $shipment->status }}</td>
                            <td class="py-2">{{ $shipment->driver?->user->name ?? '-' }}</td>
                            <td class="py-2 text-right whitespace-nowrap">Rs {{ number_format($shipment->total_price, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-4 text-slate-500">No shipments yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection