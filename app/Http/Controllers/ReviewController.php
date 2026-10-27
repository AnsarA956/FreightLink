<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\Shipment;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    // REQ-45: a customer rates a delivered shipment
    public function store(Request $request, Shipment $shipment)
    {
        $user = $request->user();

        // Only the customer who booked the shipment may rate it
        abort_unless($shipment->user_id === $user->id, 403);

        if (! in_array($shipment->status, ['Delivered', 'Closed'], true)) {
            return back()->withErrors(['rating' => 'You can rate a shipment only after it is delivered.']);
        }

        if ($shipment->review()->exists()) {
            return back()->withErrors(['rating' => 'You have already rated this shipment.']);
        }

        $data = $request->validate([
            'rating'  => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        Review::create([
            'shipment_id' => $shipment->id,
            'user_id'     => $user->id,
            'driver_id'   => $shipment->driver_id,
            'rating'      => $data['rating'],
            'comment'     => $data['comment'] ?? null,
        ]);

        return back()->with('success', 'Thank you for your rating.');
    }
}