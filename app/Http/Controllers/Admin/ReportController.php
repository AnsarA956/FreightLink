<?php

namespace App\Http\Controllers\Admin;

use App\Exports\ReportExport;
use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;


class ReportController extends Controller
{
    // The report page. With no choices made yet, it shows a daily report of the last 30 days.
    public function index(Request $request, ReportService $reports)
    {
        if ($request->has('type')) {
            [$type, $from, $to] = $this->parse($request);
        } else {
            $type = 'daily';
            $to   = today();
            $from = today()->subDays(29);
        }

        $report = $reports->build($type, $from, $to);

        // Quick buttons: each one fills in a report type and a date range
        $presets = [
            'Today' => [
                'type' => 'daily',
                'from' => today()->format('Y-m-d'),
                'to'   => today()->format('Y-m-d'),
            ],
            'This week' => [
                'type' => 'daily',
                'from' => today()->startOfWeek()->format('Y-m-d'),
                'to'   => today()->format('Y-m-d'),
            ],
            'This month' => [
                'type' => 'weekly',
                'from' => today()->startOfMonth()->format('Y-m-d'),
                'to'   => today()->format('Y-m-d'),
            ],
            'Last 6 months' => [
                'type' => 'monthly',
                'from' => today()->subMonths(5)->startOfMonth()->format('Y-m-d'),
                'to'   => today()->format('Y-m-d'),
            ],
        ];

        return view('admin.reports.index', [
            'r'       => $report,
            'types'   => ReportService::TYPES,
            'presets' => $presets,
        ]);
    }

    // REQ-44: PDF export
    public function pdf(Request $request, ReportService $reports)
    {
        [$type, $from, $to] = $this->parse($request);

        $report = $reports->build($type, $from, $to, 300);
        $pdf    = $reports->pdf($report, auth()->user()->name);

        return response($pdf, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $this->filename($type, $from, $to, 'pdf') . '"',
        ]);
    }

        // REQ-44: Excel export
    public function excel(Request $request, ReportService $reports)
    {
        [$type, $from, $to] = $this->parse($request);

        $report = $reports->build($type, $from, $to, 10000);
        $path   = (new ReportExport($report))->save();

        return response()->download($path, $this->filename($type, $from, $to, 'xlsx'), [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    // Checks the report type and the dates
    private function parse(Request $request): array
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(ReportService::TYPES))],
            'from' => ['required', 'date'],
            'to'   => ['required', 'date', 'after_or_equal:from'],
        ]);

        $from = Carbon::parse($data['from'])->startOfDay();
        $to   = Carbon::parse($data['to'])->startOfDay();

        if ($from->diffInDays($to) > 365) {
            throw ValidationException::withMessages([
                'to' => 'A report can cover at most 366 days. Please choose a shorter period.',
            ]);
        }

        return [$data['type'], $from, $to];
    }

    private function filename(string $type, Carbon $from, Carbon $to, string $extension): string
    {
        return 'freightlink-' . $type . '-report-' . $from->format('Y-m-d') . '-to-' . $to->format('Y-m-d') . '.' . $extension;
    }
}