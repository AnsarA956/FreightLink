<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $r['type_label'] }} report</title>
    <style>
        @page { margin: 28px 32px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1e293b; }
        table { width: 100%; border-collapse: collapse; }
        h1 { font-size: 20px; margin: 0; color: #0f172a; }
        h2 { font-size: 12px; margin: 16px 0 6px 0; color: #0f172a; }
        .brand { font-size: 16px; font-weight: bold; color: #0399b8; }
        .muted { color: #64748b; }
        th { text-align: left; background: #f1f5f9; padding: 5px 6px; font-size: 8px; text-transform: uppercase; color: #475569; }
        td { padding: 5px 6px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
        .right { text-align: right; }
        .total td { font-weight: bold; border-top: 2px solid #0f172a; }
        .card { border: 1px solid #e2e8f0; padding: 8px 10px; }
        .card .value { font-size: 14px; font-weight: bold; }
        .small { font-size: 8px; }
    </style>
</head>
<body>
@php $s = $r['summary']; @endphp

    <table>
        <tr>
            <td style="border: none; padding: 0;">
                <div class="brand">FreightLink</div>
                <div class="muted">Freight and cargo management</div>
            </td>
            <td class="right" style="border: none; padding: 0;">
                <h1>{{ strtoupper($r['type_label']) }} REPORT</h1>
                <div>{{ $r['from']->format('d M Y') }} to {{ $r['to']->format('d M Y') }}</div>
            </td>
        </tr>
    </table>

    {{-- Summary --}}
    <h2>Summary</h2>
    <table>
        <tr>
            <td class="card" style="width: 16%;"><div class="muted small">Shipments</div><div class="value">{{ number_format($s['shipments']) }}</div></td>
            <td class="card" style="width: 16%;"><div class="muted small">Delivered</div><div class="value">{{ number_format($s['delivered']) }}</div></td>
            <td class="card" style="width: 16%;"><div class="muted small">Pending</div><div class="value">{{ number_format($s['pending']) }}</div></td>
            <td class="card" style="width: 16%;"><div class="muted small">Cancelled</div><div class="value">{{ number_format($s['cancelled']) }}</div></td>
            <td class="card" style="width: 20%;"><div class="muted small">Revenue (Rs)</div><div class="value">{{ number_format($s['revenue'], 2) }}</div></td>
            <td class="card"><div class="muted small">Average (Rs)</div><div class="value">{{ number_format($s['average'], 2) }}</div></td>
        </tr>
    </table>
    <p class="muted small">
        Cancelled shipments are not counted in totals or revenue. Revenue includes Rs {{ number_format($s['tax'], 2) }} of tax.
        Discounts given: Rs {{ number_format($s['discount'], 2) }}.
        Total distance: {{ number_format($s['distance'], 2) }} km. Total weight: {{ number_format($s['weight'], 2) }} kg.
    </p>

    {{-- By period --}}
    <h2>By {{ ['daily' => 'day', 'weekly' => 'week', 'monthly' => 'month'][$r['type']] }}</h2>
    <table>
        <thead>
            <tr>
                <th>Period</th>
                <th class="right">Shipments</th>
                <th class="right">Delivered</th>
                <th class="right">Cancelled</th>
                <th class="right">Revenue (Rs)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($r['periods'] as $p)
                <tr>
                    <td>{{ $p['label'] }}</td>
                    <td class="right">{{ number_format($p['shipments']) }}</td>
                    <td class="right">{{ number_format($p['delivered']) }}</td>
                    <td class="right">{{ number_format($p['cancelled']) }}</td>
                    <td class="right">{{ number_format($p['revenue'], 2) }}</td>
                </tr>
            @endforeach
            <tr class="total">
                <td>Total</td>
                <td class="right">{{ number_format(collect($r['periods'])->sum('shipments')) }}</td>
                <td class="right">{{ number_format(collect($r['periods'])->sum('delivered')) }}</td>
                <td class="right">{{ number_format(collect($r['periods'])->sum('cancelled')) }}</td>
                <td class="right">{{ number_format(collect($r['periods'])->sum('revenue'), 2) }}</td>
            </tr>
        </tbody>
    </table>

    {{-- By status and service --}}
    <table style="margin-top: 14px;">
        <tr>
            <td style="width: 46%; border: none; padding: 0; vertical-align: top;">
                <h2 style="margin-top: 0;">By status</h2>
                <table>
                    <thead><tr><th>Status</th><th class="right">Shipments</th></tr></thead>
                    <tbody>
                        @forelse ($r['statuses'] as $row)
                            <tr><td>{{ $row['status'] }}</td><td class="right">{{ number_format($row['count']) }}</td></tr>
                        @empty
                            <tr><td colspan="2" class="muted">No shipments.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </td>
            <td style="width: 8%; border: none; padding: 0;"></td>
            <td style="border: none; padding: 0; vertical-align: top;">
                <h2 style="margin-top: 0;">By service type</h2>
                <table>
                    <thead><tr><th>Service</th><th class="right">Shipments</th><th class="right">Revenue (Rs)</th></tr></thead>
                    <tbody>
                        @forelse ($r['services'] as $row)
                            <tr>
                                <td>{{ $row['name'] }}</td>
                                <td class="right">{{ number_format($row['shipments']) }}</td>
                                <td class="right">{{ number_format($row['revenue'], 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="muted">No shipments.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </td>
        </tr>
    </table>

    {{-- Shipment list --}}
    <h2>Shipments</h2>
    <table>
        <thead>
            <tr>
                <th>Tracking ID</th>
                <th>Booked on</th>
                <th>Customer</th>
                <th>Service</th>
                <th>Status</th>
                <th class="right">Total (Rs)</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($r['shipments'] as $sh)
                <tr>
                    <td>{{ $sh->tracking_id }}</td>
                    <td>{{ $sh->created_at->format('d M Y H:i') }}</td>
                    <td>{{ $sh->user->name }}</td>
                    <td>{{ $sh->serviceType->name }}</td>
                    <td>{{ $sh->status }}</td>
                    <td class="right">{{ number_format($sh->total_price, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="muted">No shipments in this date range.</td></tr>
            @endforelse
        </tbody>
    </table>

    @if ($r['shipments_total'] > $r['shipments']->count())
        <p class="muted small">
            Showing the first {{ number_format($r['shipments']->count()) }} of {{ number_format($r['shipments_total']) }} shipments.
            Download the Excel file to see all of them.
        </p>
    @endif

    <p class="muted small" style="margin-top: 14px;">
        Generated by {{ $generatedBy }} on {{ now()->format('d M Y, H:i') }}. All amounts are in Pakistani Rupees (Rs).
    </p>

</body>
</html>