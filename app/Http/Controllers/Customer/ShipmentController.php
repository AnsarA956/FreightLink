<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Shipment;

class ShipmentController extends Controller
{
    // REQ-11: the customer's booking history
    public function index()
    {
        $shipments = Shipment::with('serviceType')
            ->where('user_id', auth()->id())
            ->latest()
            ->get();

        return view('customer.shipments.index', compact('shipments'));
    }

    public function show(Shipment $shipment)
    {
        // A customer may only open their own shipments
        abort_unless($shipment->user_id === auth()->id(), 403);

                $shipment->load(['serviceType', 'cargoType', 'statusLogs', 'review', 'paidPayment']);

        return view('customer.shipments.show', compact('shipment'));
    }
}