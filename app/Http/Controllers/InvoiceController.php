<?php

namespace App\Http\Controllers;

use App\Models\Shipment;
use App\Services\InvoiceService;

class InvoiceController extends Controller
{
    // REQ-37: download the PDF invoice
    public function download(Shipment $shipment, InvoiceService $invoices)
    {
        $user    = auth()->user();
                $isAdmin = in_array($user->role, ['admin', 'staff']);
        $isOwner = $user->id === $shipment->user_id;

        // Only the admin and the customer who booked it may open the invoice
        abort_unless($isAdmin || $isOwner, 403);

        if ($shipment->status === 'Cancelled') {
            return back()->with('error', 'A cancelled shipment has no invoice.');
        }

        // Customers get the invoice after delivery
        if (! $isAdmin && ! in_array($shipment->status, ['Delivered', 'Closed'])) {
            return back()->with('error', 'Your invoice will be available after the shipment is delivered.');
        }

        $invoice = $invoices->invoiceFor($shipment);
        $pdf     = $invoices->pdf($shipment, $invoice);

        return response($pdf, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $invoice->invoice_no . '.pdf"',
        ]);
    }
}