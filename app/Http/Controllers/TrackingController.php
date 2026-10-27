<?php

namespace App\Http\Controllers;

use App\Models\Shipment;
use Illuminate\Http\Request;

class TrackingController extends Controller
{
    // The search page. If an ID was typed, go to that shipment's page.
    public function index(Request $request)
    {
        if ($request->filled('tracking_id')) {
            $request->validate([
                'tracking_id' => ['string', 'max:20', 'regex:/^[A-Za-z0-9\-]+$/'],
            ], [
                'tracking_id.regex' => 'A tracking ID contains only letters, numbers and a dash, for example FL-123456.',
            ]);

            return redirect()->route('track.show', $this->normalize($request->input('tracking_id')));
        }

        return view('tracking.index');
    }

    // REQ-29 and REQ-30: show the status and timeline of one shipment
    public function show(string $trackingId)
    {
        $id = $this->normalize($trackingId);

        $shipment = Shipment::with(['serviceType', 'statusLogs'])
            ->where('tracking_id', $id)
            ->first();

        if (! $shipment) {
            return response()->view('tracking.index', ['notFound' => $id], 404);
        }

        // The normal path of a shipment, shown as a progress bar
        $steps   = ['Booked', 'Assigned', 'Picked Up', 'In Transit', 'Delivered'];
        $reached = $shipment->statusLogs->pluck('status')->unique()->all();

        return view('tracking.show', [
            'shipment' => $shipment,
            'steps'    => $steps,
            'reached'  => $reached,
            'timeline' => $shipment->statusLogs->reverse()->values(), // newest first
        ]);
    }

    // "fl-123456", " FL-123456 " and "123456" all become FL-123456
    private function normalize(string $value): string
    {
        $id = strtoupper(trim($value));

        return ctype_digit($id) ? 'FL-' . $id : $id;
    }
}