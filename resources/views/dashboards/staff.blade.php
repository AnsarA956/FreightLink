@extends('layouts.app')

@section('title', 'Staff dashboard - FreightLink')

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

    // label, value, icon, icon background, link
    $cards = [
        ['Booked today',        number_format($stats['today']),           '🗓️', 'bg-blue-100',   route('admin.shipments.index')],
        ['My walk-ins today',   number_format($stats['walkins']),         '📝', 'bg-orange-100', route('staff.walkin.create')],
        ['Awaiting driver',     number_format($stats['waiting']),         '⏳', 'bg-amber-100',  route('admin.shipments.index', ['status' => 'Booked'])],
        ['On the road',         number_format($stats['on_road']),         '🚚', 'bg-indigo-100', route('admin.shipments.index', ['status' => 'In Transit'])],
        ['Delivered today',     number_format($stats['delivered_today']), '✅', 'bg-green-100',  route('admin.shipments.index', ['status' => 'Delivered'])],
    ];
@endphp

<div class="space-y-6">

    {{-- Welcome --}}
    <div class="bg-gradient-to-r from-slate-900 to-slate-700 text-white rounded-2xl p-6 shadow flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold">{{ $greeting }}, {{ auth()->user()->name }}</h1>
            <p class="text-slate-300 text-sm mt-1">{{ now()->format('l, d F Y') }} · Create walk-in bookings and follow the shipments.</p>
        </div>
        <div class="flex flex-wrap gap-2 text-sm">
            <a href="{{ route('staff.walkin.create') }}" class="bg-orange-500 hover:bg-orange-600 font-semibold px-4 py-2 rounded-lg transition">📝 New walk-in booking</a>
            <a href="{{ route('admin.shipments.index') }}" class="bg-white/10 hover:bg-white/20 px-4 py-2 rounded-lg transition">All shipments</a>
            <a href="{{ route('track.index') }}" class="bg-white/10 hover:bg-white/20 px-4 py-2 rounded-lg transition">Track a shipment</a>
        </div>
    </div>

    {{-- Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-5 gap-4">
        @foreach ($cards as [$label, $value, $icon, $iconBg, $link])
            <a href="{{ $link }}" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 hover:shadow-md transition block">
                <div class="{{ $iconBg }} w-10 h-10 rounded-xl flex items-center justify-center text-xl mb-3">{{ $icon }}</div>
                <div class="text-xs text-slate-500">{{ $label }}</div>
                <div class="text-lg font-bold text-slate-900">{{ $value }}</div>
            </a>
        @endforeach
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Needs attention --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <h2 class="text-lg font-bold text-slate-900 mb-1">Needs attention</h2>
            <p class="text-xs text-slate-500 mb-4">Failed deliveries, and bookings without a driver for more than a day.</p>

            @forelse ($attention as $s)
                <a href="{{ route('admin.shipments.show', $s) }}" class="block border border-slate-200 hover:border-orange-300 hover:bg-orange-50 rounded-xl p-3 mb-2 transition">
                    <div class="flex items-center justify-between gap-2">
                        <span class="font-semibold text-slate-900 text-sm">{{ $s->tracking_id }}</span>
                        <span class="{{ $badge[$s->status] ?? 'bg-slate-100 text-slate-700' }} px-2 py-0.5 rounded-full text-xs whitespace-nowrap">{{ $s->status }}</span>
                    </div>
                    <div class="text-xs text-slate-500 mt-1">{{ $s->user->name }} · {{ $s->created_at->diffForHumans() }}</div>
                </a>
            @empty
                <div class="text-center py-8">
                    <div class="text-3xl mb-1">🎉</div>
                    <p class="text-sm text-slate-500">Nothing needs attention.</p>
                </div>
            @endforelse
        </div>

        {{-- Recent shipments --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 lg:col-span-2 overflow-x-auto">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-bold text-slate-900">Recent shipments</h2>
                <a href="{{ route('admin.shipments.index') }}" class="text-sm text-orange-600 font-semibold hover:underline">View all →</a>
            </div>

            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                        <th class="py-2 pr-3">Tracking ID</th>
                        <th class="py-2 pr-3">Customer</th>
                        <th class="py-2 pr-3">Status</th>
                        <th class="py-2 pr-3">Driver</th>
                        <th class="py-2 text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recent as $s)
                        <tr class="border-t border-slate-100 hover:bg-slate-50">
                            <td class="py-3 pr-3">
                                <a href="{{ route('admin.shipments.show', $s) }}" class="text-orange-600 font-semibold hover:underline">{{ $s->tracking_id }}</a>
                            </td>
                            <td class="py-3 pr-3">{{ $s->user->name }}</td>
                            <td class="py-3 pr-3">
                                <span class="{{ $badge[$s->status] ?? 'bg-slate-100 text-slate-700' }} px-2.5 py-0.5 rounded-full text-xs font-medium whitespace-nowrap">{{ $s->status }}</span>
                            </td>
                            <td class="py-3 pr-3">{{ $s->driver?->user?->name ?? '-' }}</td>
                            <td class="py-3 text-right whitespace-nowrap font-medium">Rs {{ number_format($s->total_price, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-6 text-center text-slate-500">No shipments yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection