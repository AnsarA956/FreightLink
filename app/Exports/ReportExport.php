<?php

namespace App\Exports;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ReportExport
{
    public function __construct(private array $report)
    {
    }

    // Builds the Excel file in a temporary file and returns its path
    public function save(): string
    {
        $r = $this->report;
        $s = $r['summary'];

        $book = new Spreadsheet();

        // Sheet 1: summary (counts go in "Number", money goes in "Amount")
        $summaryRows = [
            ['Report', $r['type_label'] . ' report', null],
            ['From', $r['from']->format('Y-m-d'), null],
            ['To', $r['to']->format('Y-m-d'), null],
            ['Shipments (cancelled not counted)', $s['shipments'], null],
            ['Delivered', $s['delivered'], null],
            ['Pending deliveries', $s['pending'], null],
            ['Cancelled', $s['cancelled'], null],
            ['Revenue (cancelled not counted)', null, $s['revenue']],
            ['of which tax', null, $s['tax']],
            ['Discounts given', null, $s['discount']],
            ['Average shipment value', null, $s['average']],
            ['Total distance', number_format($s['distance'], 2) . ' km', null],
            ['Total weight', number_format($s['weight'], 2) . ' kg', null],
        ];

        $this->fill(
            $book->getActiveSheet(),
            'Summary',
            ['Item', 'Number', 'Amount (Rs)'],
            $summaryRows,
            ['B' => '#,##0', 'C' => '#,##0.00']
        );

        // Sheet 2: by period, with a totals row
        $periodRows = [];

        foreach ($r['periods'] as $p) {
            $periodRows[] = [$p['label'], $p['shipments'], $p['delivered'], $p['cancelled'], $p['revenue']];
        }

        $periodRows[] = [
            'Total',
            array_sum(array_column($r['periods'], 'shipments')),
            array_sum(array_column($r['periods'], 'delivered')),
            array_sum(array_column($r['periods'], 'cancelled')),
            round(array_sum(array_column($r['periods'], 'revenue')), 2),
        ];

        $this->fill(
            $book->createSheet(),
            'By period',
            ['Period', 'Shipments', 'Delivered', 'Cancelled', 'Revenue (Rs)'],
            $periodRows,
            ['B' => '#,##0', 'C' => '#,##0', 'D' => '#,##0', 'E' => '#,##0.00'],
            1
        );

        // Sheet 3: every shipment in the report
        $shipmentRows = [];

        foreach ($r['shipments'] as $sh) {
            $shipmentRows[] = [
                $sh->tracking_id,
                $sh->created_at->format('Y-m-d H:i'),
                $sh->user->name,
                $sh->pickup_address,
                $sh->delivery_address,
                $sh->serviceType->name,
                $sh->status,
                $sh->driver?->user?->name,
                (float) $sh->weight_kg,
                (float) $sh->distance_km,
                (float) $sh->discount_amount,
                (float) $sh->tax_amount,
                (float) $sh->total_price,
            ];
        }

        $this->fill(
            $book->createSheet(),
            'Shipments',
            [
                'Tracking ID', 'Booked on', 'Customer', 'From', 'To', 'Service', 'Status', 'Driver',
                'Weight (kg)', 'Distance (km)', 'Discount (Rs)', 'Tax (Rs)', 'Total (Rs)',
            ],
            $shipmentRows,
            ['I' => '#,##0.00', 'J' => '#,##0.00', 'K' => '#,##0.00', 'L' => '#,##0.00', 'M' => '#,##0.00']
        );

        $book->setActiveSheetIndex(0);

        $path = tempnam(sys_get_temp_dir(), 'frl');
        (new Xlsx($book))->save($path);
        $book->disconnectWorksheets();

        return $path;
    }

    // Writes the headings and rows into one worksheet
    private function fill(Worksheet $sheet, string $title, array $headings, array $rows, array $formats, int $boldLastRows = 0): void
    {
        $sheet->setTitle($title);

        $this->writeRow($sheet, 1, $headings);

        foreach ($rows as $i => $row) {
            $this->writeRow($sheet, $i + 2, $row);
        }

        $lastColumn = Coordinate::stringFromColumnIndex(count($headings));
        $lastRow    = count($rows) + 1;

        // The heading row is bold
        $sheet->getStyle('A1:' . $lastColumn . '1')->getFont()->setBold(true);

        // Number formats (thousand separators, two decimals)
        if ($lastRow >= 2) {
            foreach ($formats as $column => $format) {
                $sheet->getStyle($column . '2:' . $column . $lastRow)->getNumberFormat()->setFormatCode($format);
            }
        }

        // The last rows (totals) are bold
        for ($i = 0; $i < $boldLastRows; $i++) {
            $row = $lastRow - $i;
            $sheet->getStyle('A' . $row . ':' . $lastColumn . $row)->getFont()->setBold(true);
        }

        for ($column = 1; $column <= count($headings); $column++) {
            $sheet->getColumnDimensionByColumn($column)->setAutoSize(true);
        }

        $sheet->freezePane('A2');
    }

    // Text is always stored as text, so a value like "=1+1" in an address can never run as a formula
    private function writeRow(Worksheet $sheet, int $rowNumber, array $values): void
    {
        $column = 1;

        foreach ($values as $value) {
            if (is_string($value)) {
                $sheet->setCellValueExplicit([$column, $rowNumber], $value, DataType::TYPE_STRING);
            } elseif ($value !== null) {
                $sheet->setCellValue([$column, $rowNumber], $value);
            }

            $column++;
        }
    }
}