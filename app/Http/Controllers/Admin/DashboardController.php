<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    // "Pending deliveries": booked but not yet delivered, and not cancelled
    private const PENDING = ['Booked', 'Assigned', 'Picked Up', 'In Transit', 'Delivery Failed'];

    private const STATUS_COLORS = [
        'Pending Payment' => '#a855f7',
        'Booked'          => '#3b82f6',
        'Assigned'        => '#6366f1',
        'Picked Up'       => '#f59e0b',
        'In Transit'      => '#f97316',
        'Delivered'       => '#16a34a',
        'Delivery Failed' => '#dc2626',
        'Cancelled'       => '#94a3b8',
        'Closed'          => '#0f766e',
    ];

    public function index(Request $request)
    {
        // The chart period: 7, 30 or 90 days (30 if anything else is given)
        $requested = (int) $request->query('days');
        $days      = in_array($requested, [7, 30, 90], true) ? $requested : 30;

        // REQ-41: the statistics cards (cancelled shipments are not counted in totals or revenue)
        $stats = [
            'total'     => Shipment::where('status', '!=', 'Cancelled')->count(),
            'pending'   => Shipment::whereIn('status', self::PENDING)->count(),
            'waiting'   => Shipment::where('status', 'Booked')->whereNull('driver_id')->count(),
            'delivered' => Shipment::whereIn('status', ['Delivered', 'Closed'])->count(),
            'cancelled' => Shipment::where('status', 'Cancelled')->count(),
            'revenue'   => (float) Shipment::where('status', '!=', 'Cancelled')->sum('total_price'),
        ];

        // REQ-42: shipments and revenue for each day of the period
        $from = today()->subDays($days - 1);

        $rows = Shipment::where('status', '!=', 'Cancelled')
            ->where('created_at', '>=', $from)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as shipments, SUM(total_price) as revenue')
            ->groupByRaw('DATE(created_at)')
            ->get()
            ->keyBy('day');

        $labels = [];
        $shipmentSeries = [];
        $revenueSeries = [];

        // Days without any shipment must appear as zero, so every day gets a value
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = today()->subDays($i);
            $row  = $rows->get($date->format('Y-m-d'));

            $labels[]         = $date->format('d M');
            $shipmentSeries[] = (int) ($row->shipments ?? 0);
            $revenueSeries[]  = round((float) ($row->revenue ?? 0), 2);
        }

        // Shipments by status
        $counts = Shipment::selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $statusLabels = [];
        $statusData   = [];
        $statusColors = [];

        foreach (Shipment::STATUSES as $status) {
            $count = (int) $counts->get($status, 0);

            if ($count > 0) {
                $statusLabels[] = $status;
                $statusData[]   = $count;
                $statusColors[] = self::STATUS_COLORS[$status] ?? '#64748b';
            }
        }

        $chart = [
            'labels'       => $labels,
            'shipments'    => $shipmentSeries,
            'revenue'      => $revenueSeries,
            'statusLabels' => $statusLabels,
            'statusData'   => $statusData,
            'statusColors' => $statusColors,
        ];

        $period = [
            'shipments' => array_sum($shipmentSeries),
            'revenue'   => array_sum($revenueSeries),
        ];

        $recent = Shipment::with(['user', 'driver.user'])->latest()->limit(8)->get();

        return view('admin.dashboard.index', compact('stats', 'chart', 'period', 'days', 'recent'));
    }
}