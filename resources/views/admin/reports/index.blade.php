@extends('layouts.app')

@section('title', 'Reports - FreightLink')

@section('content')
@php
    $s     = $r['summary'];
    $query = [
        'type' => $r['type'],
        'from' => $r['from']->format('Y-m-d'),
        'to'   => $r['to']->format('Y-m-d'),
    ];
@endphp

<div class="max-w-6xl space-y-6">

    {{-- Choose the report --}}
    <div class="bg-white rounded-xl shadow p-6">
        <h1 class="text-2xl font-bold mb-1">Reports</h1>
        <p class="text-sm text-slate-500 mb-4">
            Pick a report type and a date range (by booking date). Cancelled shipments are not counted in totals or revenue.
        </p>

        @if ($errors->any())
            <div class="mb-4 p-3 rounded bg-red-100 text-red-800 text-sm">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        {{-- Quick buttons --}}
        <div class="flex flex-wrap gap-2 mb-4 text-sm">
            @foreach ($presets as $label => $preset)
                <a href="{{ route('admin.reports.index', $preset) }}" class="px-3 py-1.5 rounded border hover:bg-slate-100">{{ $label }}</a>
            @endforeach
        </div>

        <form method="GET" action="{{ route('admin.reports.index') }}" class="flex flex-wrap gap-3 items-end">
            <div>
                <label class="block text-sm font-medium mb-1">Report type</label>
                <select name="type" class="border rounded px-3 py-2">
                    @foreach ($types as $value => $label)
                        <option value="{{ $value }}" @selected($r['type'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">From</label>
                <input type="date" name="from" value="{{ $r['from']->format('Y-m-d') }}" class="border rounded px-3 py-2" required>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">To</label>
                <input type="date" name="to" value="{{ $r['to']->format('Y-m-d') }}" class="border rounded px-3 py-2" required>
            </div>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">Show report</button>
        </form>
    </div>

    {{-- The report --}}
    <div class="bg-white rounded-xl shadow p-6">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <div>
                <h2 class="text-xl font-bold">{{ $r['type_label'] }} report</h2>
                <p class="text-sm text-slate-500">{{ $r['from']->format('d M Y') }} to {{ $r['to']->format('d M Y') }}</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('admin.reports.pdf', $query) }}" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded text-sm">Download PDF</a>
                <a href="{{ route('admin.reports.excel', $query) }}" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded text-sm">Download Excel</a>
            </div>
        </div>

        @if ($s['shipments'] === 0 && $s['cancelled'] === 0)
            <div class="mb-4 p-3 rounded bg-amber-100 text-amber-800 text-sm">There are no shipments in this date range.</div>
        @endif

        {{-- Summary cards --}}
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 mb-6">
            @foreach ([
                ['Shipments', number_format($s['shipments'])],
                ['Delivered', number_format($s['delivered'])],
                ['Pending', number_format($s['pending'])],
                ['Cancelled', number_format($s['cancelled'])],
                ['Revenue', 'Rs ' . number_format($s['revenue'], 2)],
                ['Average value', 'Rs ' . number_format($s['average'], 2)],
            ] as [$label, $value])
                <div class="border rounded-lg p-3">
                    <div class="text-xs text-slate-500">{{ $label }}</div>
                    <div class="text-lg font-bold">{{ $value }}</div>
                </div>
            @endforeach
        </div>

        <div class="text-sm text-slate-600 mb-6">
            Revenue includes Rs {{ number_format($s['tax'], 2) }} of tax. Discounts given: Rs {{ number_format($s['discount'], 2) }}.
            Total distance: {{ number_format($s['distance'], 2) }} km. Total weight: {{ number_format($s['weight'], 2) }} kg.
        </div>

        {{-- By period --}}
        <h3 class="font-bold mb-2">By {{ ['daily' => 'day', 'weekly' => 'week', 'monthly' => 'month'][$r['type']] }}</h3>
        <div class="overflow-x-auto mb-6">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-slate-500">
                        <th class="py-2">Period</th>
                        <th class="py-2 text-right">Shipments</th>
                        <th class="py-2 text-right">Delivered</th>
                        <th class="py-2 text-right">Cancelled</th>
                        <th class="py-2 text-right">Revenue (Rs)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($r['periods'] as $p)
                        <tr class="border-t">
                            <td class="py-2">{{ $p['label'] }}</td>
                            <td class="py-2 text-right">{{ number_format($p['shipments']) }}</td>
                            <td class="py-2 text-right">{{ number_format($p['delivered']) }}</td>
                            <td class="py-2 text-right">{{ number_format($p['cancelled']) }}</td>
                            <td class="py-2 text-right">{{ number_format($p['revenue'], 2) }}</td>
                        </tr>
                    @endforeach
                    <tr class="border-t-2 font-bold">
                        <td class="py-2">Total</td>
                        <td class="py-2 text-right">{{ number_format(collect($r['periods'])->sum('shipments')) }}</td>
                        <td class="py-2 text-right">{{ number_format(collect($r['periods'])->sum('delivered')) }}</td>
                        <td class="py-2 text-right">{{ number_format(collect($r['periods'])->sum('cancelled')) }}</td>
                        <td class="py-2 text-right">{{ number_format(collect($r['periods'])->sum('revenue'), 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- By status --}}
            <div>
                <h3 class="font-bold mb-2">By status</h3>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-slate-500">
                            <th class="py-2">Status</th>
                            <th class="py-2 text-right">Shipments</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($r['statuses'] as $row)
                            <tr class="border-t">
                                <td class="py-2">{{ $row['status'] }}</td>
                                <td class="py-2 text-right">{{ number_format($row['count']) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="2" class="py-3 text-slate-500">No shipments.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- By service --}}
            <div>
                <h3 class="font-bold mb-2">By service type</h3>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-slate-500">
                            <th class="py-2">Service</th>
                            <th class="py-2 text-right">Shipments</th>
                            <th class="py-2 text-right">Revenue (Rs)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($r['services'] as $row)
                            <tr class="border-t">
                                <td class="py-2">{{ $row['name'] }}</td>
                                <td class="py-2 text-right">{{ number_format($row['shipments']) }}</td>
                                <td class="py-2 text-right">{{ number_format($row['revenue'], 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="py-3 text-slate-500">No shipments.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <p class="text-xs text-slate-500 mt-6">The full list of shipments is included in the PDF and Excel downloads.</p>
    </div>
</div>
@endsection