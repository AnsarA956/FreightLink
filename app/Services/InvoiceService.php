<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Shipment;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Dompdf\Dompdf;
use Dompdf\Options;
use Throwable;

class InvoiceService
{
    // Gets the invoice of this shipment, or creates it the first time (REQ-35)
    public function invoiceFor(Shipment $shipment): Invoice
    {
        return Invoice::firstOrCreate(
            ['shipment_id' => $shipment->id],
            [
                'invoice_no' => 'INV-' . now()->format('Y') . '-' . str_pad((string) $shipment->id, 5, '0', STR_PAD_LEFT),
                'issued_at'  => now(),
            ]
        );
    }

    // Returns the PDF file content as a string
    public function pdf(Shipment $shipment, Invoice $invoice): string
    {
        $shipment->loadMissing(['user', 'serviceType', 'cargoType', 'paidPayment']);

        // The price lines are rebuilt from the values saved with the shipment
        $subtotal      = (float) $shipment->subtotal;
        $serviceAmount = round($subtotal * (float) $shipment->service_multiplier, 2);
        $serviceExtra  = round($serviceAmount - $subtotal, 2);
        $taxable       = round(max(0, $serviceAmount + (float) $shipment->cargo_surcharge - (float) $shipment->discount_amount), 2);

        // REQ-36: the QR code opens the public tracking page
        $trackUrl = route('track.show', $shipment->tracking_id);

        $html = view('invoices.pdf', [
            'shipment'     => $shipment,
            'invoice'      => $invoice,
            'serviceExtra' => $serviceExtra,
            'taxable'      => $taxable,
            'trackUrl'     => $trackUrl,
            'qr'           => $this->qrDataUri($trackUrl),
        ])->render();

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

    // Makes the QR code as a small SVG picture, ready to put inside the PDF.
    // If it fails for any reason, the invoice is still produced (it shows the link as text).
    private function qrDataUri(string $text): ?string
    {
        try {
            $renderer = new ImageRenderer(new RendererStyle(220, 2), new SvgImageBackEnd());
            $svg      = (new Writer($renderer))->writeString($text);

            return 'data:image/svg+xml;base64,' . base64_encode($svg);
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }
}