@extends('layouts.app')

@section('title', 'Admin Dashboard - FreightLink')

@section('content')
@php
    $hour     = now()->hour;
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');

    // Color of the status badge in the recent shipments table
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
        ['Total shipments',    number_format($stats['total']),                '📦', 'bg-blue-100',   route('admin.shipments.index')],
        ['Pending deliveries', number_format($stats['pending']),              '⏳', 'bg-amber-100',  route('admin.shipments.index')],
        ['Awaiting driver',    number_format($stats['waiting']),              '🧑', 'bg-indigo-100', route('admin.shipments.index', ['status' => 'Booked'])],
        ['Delivered',          number_format($stats['delivered']),            '✅', 'bg-green-100',  route('admin.shipments.index', ['status' => 'Delivered'])],
        ['Cancelled',          number_format($stats['cancelled']),            '🚫', 'bg-slate-200',  route('admin.shipments.index', ['status' => 'Cancelled'])],
        ['Revenue',            'Rs ' . number_format($stats['revenue'], 2),   '💰', 'bg-orange-100', route('admin.reports.index')],
    ];
@endphp

<div class="space-y-6">

    {{-- Welcome --}}
    <div class="bg-gradient-to-r from-slate-900 to-slate-700 text-white rounded-2xl p-6 shadow flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold">{{ $greeting }}, {{ auth()->user()->name }}</h1>
            <p class="text-slate-300 text-sm mt-1">{{ now()->format('l, d F Y') }} · Cancelled shipments are not counted in totals or revenue.</p>
        </div>
        <div class="flex flex-wrap gap-2 text-sm">
            <a href="{{ route('admin.shipments.index') }}" class="bg-orange-500 hover:bg-orange-600 font-semibold px-4 py-2 rounded-lg transition">All shipments</a>
            <a href="{{ route('admin.reports.index') }}" class="bg-white/10 hover:bg-white/20 px-4 py-2 rounded-lg transition">Reports</a>
            <a href="{{ route('admin.drivers.index') }}" class="bg-white/10 hover:bg-white/20 px-4 py-2 rounded-lg transition">Drivers</a>
        </div>
    </div>

    {{-- Summary cards --}}
    <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-4">
        @foreach ($cards as [$label, $value, $icon, $iconBg, $link])
            <a href="{{ $link }}" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 hover:shadow-md transition block">
                <div class="{{ $iconBg }} w-10 h-10 rounded-xl flex items-center justify-center text-xl mb-3">{{ $icon }}</div>
                <div class="text-xs text-slate-500">{{ $label }}</div>
                <div class="text-lg font-bold text-slate-900 truncate">{{ $value }}</div>
            </a>
        @endforeach
    </div>

    {{-- Period selector --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-lg font-bold text-slate-900">Last {{ $days }} days</h2>
            <p class="text-sm text-slate-500">
                {{ number_format($period['shipments']) }} shipments · Rs {{ number_format($period['revenue'], 2) }} revenue
            </p>
        </div>

        <div class="inline-flex rounded-lg border border-slate-300 overflow-hidden text-sm bg-white">
            @foreach ([7, 30, 90] as $d)
                <a href="{{ route('admin.dashboard', ['days' => $d]) }}"
                   class="px-4 py-2 {{ $days === $d ? 'bg-orange-500 text-white font-semibold' : 'text-slate-700 hover:bg-slate-100' }}">
                    {{ $d }} days
                </a>
            @endforeach
        </div>
    </div>

    {{-- Charts --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <h3 class="font-bold text-slate-900 mb-3">Shipments per day</h3>
            <div class="relative h-64"><canvas id="shipmentsChart"></canvas></div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <h3 class="font-bold text-slate-900 mb-3">Revenue per day (Rs)</h3>
            <div class="relative h-64"><canvas id="revenueChart"></canvas></div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Status chart --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <h3 class="font-bold text-slate-900 mb-3">Shipments by status</h3>

            @if (count($chart['statusData']) > 0)
                <div class="relative h-64"><canvas id="statusChart"></canvas></div>
            @else
                <p class="text-sm text-slate-500 py-10 text-center">No shipments yet.</p>
            @endif
        </div>

        {{-- Recent shipments --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 lg:col-span-2 overflow-x-auto">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold text-slate-900">Recent shipments</h3>
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
                    @forelse ($recent as $shipment)
                        <tr class="border-t border-slate-100 hover:bg-slate-50">
                            <td class="py-3 pr-3">
                                <a href="{{ route('admin.shipments.show', $shipment) }}" class="text-orange-600 font-semibold hover:underline">{{ $shipment->tracking_id }}</a>
                            </td>
                            <td class="py-3 pr-3">{{ $shipment->user->name }}</td>
                            <td class="py-3 pr-3">
                                <span class="{{ $badge[$shipment->status] ?? 'bg-slate-100 text-slate-700' }} px-2.5 py-0.5 rounded-full text-xs font-medium whitespace-nowrap">{{ $shipment->status }}</span>
                            </td>
                            <td class="py-3 pr-3">{{ $shipment->driver?->user?->name ?? '-' }}</td>
                            <td class="py-3 text-right whitespace-nowrap font-medium">Rs {{ number_format($shipment->total_price, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-6 text-center text-slate-500">No shipments yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    (function () {
        if (typeof Chart === 'undefined') {
            // The chart library comes from the internet
            document.querySelectorAll('canvas').forEach(function (canvas) {
                var note = document.createElement('p');
                note.className = 'text-sm text-slate-500';
                note.textContent = 'The charts need an internet connection.';
                canvas.parentNode.replaceChild(note, canvas);
            });
            return;
        }

        var c = @json($chart);

        Chart.defaults.font.family = 'Inter, system-ui, sans-serif';

        // Shipments per day
        new Chart(document.getElementById('shipmentsChart'), {
            type: 'bar',
            data: {
                labels: c.labels,
                datasets: [{ label: 'Shipments', data: c.shipments, backgroundColor: '#f97316', borderRadius: 4 }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { ticks: { maxTicksLimit: 10 }, grid: { display: false } },
                    y: { beginAtZero: true, ticks: { precision: 0 } }
                }
            }
        });

        // Revenue per day
        new Chart(document.getElementById('revenueChart'), {
            type: 'line',
            data: {
                labels: c.labels,
                datasets: [{
                    label: 'Revenue (Rs)',
                    data: c.revenue,
                    borderColor: '#0f172a',
                    backgroundColor: 'rgba(249, 115, 22, 0.15)',
                    fill: true,
                    tension: 0.3,
                    pointRadius: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { ticks: { maxTicksLimit: 10 }, grid: { display: false } },
                    y: { beginAtZero: true }
                }
            }
        });

        // Shipments by status
        var statusCanvas = document.getElementById('statusChart');

        if (statusCanvas) {
            new Chart(statusCanvas, {
                type: 'doughnut',
                data: {
                    labels: c.statusLabels,
                    datasets: [{ data: c.statusData, backgroundColor: c.statusColors, borderWidth: 2, borderColor: '#ffffff' }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '60%',
                    plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } }
                }
            });
        }
    })();
</script>
@endsection