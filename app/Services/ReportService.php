<?php

namespace App\Services;

use App\Models\ServiceType;
use App\Models\Shipment;
use Carbon\CarbonPeriod;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Carbon;

class ReportService
{
    public const TYPES = [
        'daily'   => 'Daily',
        'weekly'  => 'Weekly',
        'monthly' => 'Monthly',
    ];

    // The same meaning as "Pending deliveries" on the dashboard
    private const PENDING = ['Booked', 'Assigned', 'Picked Up', 'In Transit', 'Delivery Failed'];

    // $listLimit = how many shipments to include in the detailed list (0 = no list)
    public function build(string $type, Carbon $from, Carbon $to, int $listLimit = 0): array
    {
        // A fresh query for the selected booking dates, every time it is called
        $base = fn () => Shipment::whereDate('created_at', '>=', $from->toDateString())
            ->whereDate('created_at', '<=', $to->toDateString());

        // Summary: cancelled shipments are not counted in totals or revenue
        $sum = $base()->where('status', '<>', 'Cancelled')
            ->selectRaw('COUNT(*) as shipments,
                COALESCE(SUM(total_price), 0) as revenue,
                COALESCE(SUM(tax_amount), 0) as tax,
                COALESCE(SUM(discount_amount), 0) as discount,
                COALESCE(SUM(distance_km), 0) as distance,
                COALESCE(SUM(weight_kg), 0) as weight')
            ->first();

        $counts = $base()->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $shipments = (int) $sum->shipments;
        $revenue   = round((float) $sum->revenue, 2);

        $pending = 0;
        foreach (self::PENDING as $status) {
            $pending += (int) $counts->get($status, 0);
        }

        $summary = [
            'shipments' => $shipments,
            'delivered' => (int) $counts->get('Delivered', 0) + (int) $counts->get('Closed', 0),
            'pending'   => $pending,
            'cancelled' => (int) $counts->get('Cancelled', 0),
            'revenue'   => $revenue,
            'tax'       => round((float) $sum->tax, 2),
            'discount'  => round((float) $sum->discount, 2),
            'average'   => $shipments > 0 ? round($revenue / $shipments, 2) : 0.0,
            'distance'  => round((float) $sum->distance, 2),
            'weight'    => round((float) $sum->weight, 2),
        ];

        // One row per booking day, then rolled up into days, weeks or months below
        $daily = $base()->selectRaw("DATE(created_at) as day,
                SUM(CASE WHEN status <> 'Cancelled' THEN 1 ELSE 0 END) as shipments,
                SUM(CASE WHEN status IN ('Delivered', 'Closed') THEN 1 ELSE 0 END) as delivered,
                SUM(CASE WHEN status = 'Cancelled' THEN 1 ELSE 0 END) as cancelled,
                SUM(CASE WHEN status <> 'Cancelled' THEN total_price ELSE 0 END) as revenue")
            ->groupByRaw('DATE(created_at)')
            ->get()
            ->keyBy('day');

        $groups = [];

        foreach (CarbonPeriod::create($from, $to) as $date) {
            $key = match ($type) {
                'weekly'  => $date->copy()->startOfWeek()->format('Y-m-d'),
                'monthly' => $date->format('Y-m'),
                default   => $date->format('Y-m-d'),
            };

            if (! isset($groups[$key])) {
                $groups[$key] = [
                    'start' => $date->copy(), 'end' => $date->copy(),
                    'shipments' => 0, 'delivered' => 0, 'cancelled' => 0, 'revenue' => 0.0,
                ];
            }

            $groups[$key]['end'] = $date->copy();

            $row = $daily->get($date->format('Y-m-d'));

            if ($row) {
                $groups[$key]['shipments'] += (int) $row->shipments;
                $groups[$key]['delivered'] += (int) $row->delivered;
                $groups[$key]['cancelled'] += (int) $row->cancelled;
                $groups[$key]['revenue']   += (float) $row->revenue;
            }
        }

        $periods = [];

        foreach ($groups as $group) {
            $label = match ($type) {
                'weekly'  => $group['start']->format('d M') . ' - ' . $group['end']->format('d M Y'),
                'monthly' => $group['start']->format('F Y'),
                default   => $group['start']->format('D, d M Y'),
            };

            $periods[] = [
                'label'     => $label,
                'shipments' => $group['shipments'],
                'delivered' => $group['delivered'],
                'cancelled' => $group['cancelled'],
                'revenue'   => round($group['revenue'], 2),
            ];
        }

        // By status
        $statuses = [];

        foreach (Shipment::STATUSES as $status) {
            $count = (int) $counts->get($status, 0);

            if ($count > 0) {
                $statuses[] = ['status' => $status, 'count' => $count];
            }
        }

        // By service type (cancelled not counted)
        $names = ServiceType::pluck('name', 'id');

        $services = $base()->where('status', '<>', 'Cancelled')
            ->selectRaw('service_type_id, COUNT(*) as shipments, COALESCE(SUM(total_price), 0) as revenue')
            ->groupBy('service_type_id')
            ->get()
            ->map(fn ($row) => [
                'name'      => $names[$row->service_type_id] ?? 'Unknown',
                'shipments' => (int) $row->shipments,
                'revenue'   => round((float) $row->revenue, 2),
            ])
            ->sortByDesc('revenue')
            ->values()
            ->all();

        // The detailed list of shipments (only for the PDF and Excel files)
        $listTotal = 0;
        $list      = collect();

        if ($listLimit > 0) {
            $listTotal = $base()->count();
            $list      = $base()->with(['user', 'serviceType', 'driver.user'])
                ->orderBy('created_at')
                ->orderBy('id')
                ->limit($listLimit)
                ->get();
        }

        return [
            'type'            => $type,
            'type_label'      => self::TYPES[$type],
            'from'            => $from,
            'to'              => $to,
            'summary'         => $summary,
            'periods'         => $periods,
            'statuses'        => $statuses,
            'services'        => $services,
            'shipments'       => $list,
            'shipments_total' => $listTotal,
        ];
    }

    // Returns the PDF file content as a string
    public function pdf(array $report, string $generatedBy): string
    {
        $html = view('reports.pdf', ['r' => $report, 'generatedBy' => $generatedBy])->render();

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);
        $options->set('isPhpEnabled', false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }
}