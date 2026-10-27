@extends('layouts.app')

@section('title', 'My shipments - FreightLink')

@section('content')
@php
    $badge = [
        'Assigned'        => 'bg-indigo-100 text-indigo-800',
        'Picked Up'       => 'bg-amber-100 text-amber-800',
        'In Transit'      => 'bg-orange-100 text-orange-800',
        'Delivered'       => 'bg-green-100 text-green-800',
        'Delivery Failed' => 'bg-red-100 text-red-800',
        'Cancelled'       => 'bg-slate-200 text-slate-600',
        'Closed'          => 'bg-emerald-100 text-emerald-800',
    ];

    $hour     = now()->hour;
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
@endphp

<div class="max-w-5xl mx-auto space-y-6">

    {{-- Welcome --}}
    <div class="bg-gradient-to-r from-slate-900 to-slate-700 text-white rounded-2xl p-6 shadow">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold">{{ $greeting }}, {{ $driver->user->name }}</h1>
                <p class="text-slate-300 text-sm mt-1">Your status: <strong class="text-white">{{ $driver->status }}</strong></p>
            </div>

            @if ($active->count() > 0)
                <a href="{{ route('driver.route') }}"
                   class="bg-orange-500 hover:bg-orange-600 font-semibold px-5 py-2.5 rounded-lg transition text-sm">
                    🧭 Suggest best route
                </a>
            @endif
        </div>

        <div class="grid grid-cols-2 gap-3 mt-5 max-w-md">
            <div class="bg-white/10 rounded-xl p-3">
                <div class="text-xs text-slate-300">Active shipments</div>
                <div class="text-2xl font-bold">{{ $active->count() }}</div>
            </div>
            <div class="bg-white/10 rounded-xl p-3">
                <div class="text-xs text-slate-300">Completed or failed</div>
                <div class="text-2xl font-bold">{{ number_format($past->total()) }}</div>
            </div>
        </div>

        @if ($active->count() > 1)
            <p class="text-xs text-slate-300 mt-3">You have {{ $active->count() }} active shipments. The suggested route gives the best order of stops.</p>
        @endif
    </div>

    {{-- Active shipments --}}
    <div>
        <h2 class="text-lg font-bold text-slate-900 mb-3">Active shipments</h2>

        @forelse ($active as $shipment)
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 mb-4">
                <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                    <a href="{{ route('driver.shipments.show', $shipment) }}" class="text-lg font-bold font-mono text-orange-600 hover:underline">{{ $shipment->tracking_id }}</a>
                    <span class="{{ $badge[$shipment->status] ?? 'bg-slate-100 text-slate-700' }} px-3 py-1 rounded-full text-xs font-semibold">{{ $shipment->status }}</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                    <div>
                        <div class="text-xs uppercase tracking-wide text-slate-500">Pickup</div>
                        <div class="font-medium">{{ $shipment->pickup_address }}</div>
                    </div>
                    <div>
                        <div class="text-xs uppercase tracking-wide text-slate-500">Delivery</div>
                        <div class="font-medium">{{ $shipment->delivery_address }}</div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3 mt-4 pt-4 border-t border-slate-100">
                    <div class="text-sm text-slate-600">
                        {{ $shipment->user->name }} · {{ number_format($shipment->weight_kg, 2) }} kg · {{ $shipment->serviceType->name }}
                    </div>

                    <div class="flex gap-2">
                        @if ($shipment->user->phone)
                            <a href="tel:{{ preg_replace('/[^0-9+]/', '', $shipment->user->phone) }}"
                               class="border border-slate-300 hover:bg-slate-100 px-4 py-2 rounded-lg text-sm transition">📞 Call</a>
                        @endif
                        <a href="{{ route('driver.shipments.show', $shipment) }}"
                           class="bg-slate-900 hover:bg-slate-700 text-white px-5 py-2 rounded-lg text-sm font-semibold transition">Open</a>
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-10 text-center">
                <div class="text-4xl mb-2">🚚</div>
                <p class="text-slate-600">You have no active shipments.</p>
            </div>
        @endforelse
    </div>

    {{-- Past shipments --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 overflow-x-auto">
        <h2 class="text-lg font-bold text-slate-900 mb-4">Completed and failed</h2>

        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                    <th class="py-2 pr-3">Tracking ID</th>
                    <th class="py-2 pr-3 hidden sm:table-cell">Customer</th>
                    <th class="py-2 pr-3">Status</th>
                    <th class="py-2 pr-3 hidden md:table-cell">Last update</th>
                    <th class="py-2"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($past as $shipment)
                    <tr class="border-t border-slate-100 hover:bg-slate-50">
                        <td class="py-3 pr-3 font-mono font-semibold">{{ $shipment->tracking_id }}</td>
                        <td class="py-3 pr-3 hidden sm:table-cell">{{ $shipment->user->name }}</td>
                        <td class="py-3 pr-3">
                            <span class="{{ $badge[$shipment->status] ?? 'bg-slate-100 text-slate-700' }} px-2.5 py-0.5 rounded-full text-xs font-medium whitespace-nowrap">{{ $shipment->status }}</span>
                        </td>
                        <td class="py-3 pr-3 whitespace-nowrap text-slate-500 hidden md:table-cell">{{ $shipment->updated_at->format('d M Y, H:i') }}</td>
                        <td class="py-3 text-right">
                            <a href="{{ route('driver.shipments.show', $shipment) }}" class="text-orange-600 font-semibold hover:underline">View</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-8 text-center text-slate-500">Nothing here yet.</td></tr>
                @endforelse
            </tbody>
        </table>

        <div class="mt-4">{{ $past->links() }}</div>
    </div>
</div>
@endsection