<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\Shipment;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    // REQ-45: the customer rates a delivered shipment
    public function store(Request $request, Shipment $shipment)
    {
        // Only the customer who booked it
        abort_unless($shipment->user_id === auth()->id(), 403);

        // Only after delivery, and only if a driver did the delivery
        abort_unless(
            in_array($shipment->status, ['Delivered', 'Closed']) && $shipment->driver_id,
            403
        );

        $data = $request->validate([
            'rating'  => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        // One review per shipment (the database also enforces this)
        $review = Review::firstOrCreate(
            ['shipment_id' => $shipment->id],
            [
                'user_id'   => auth()->id(),
                'driver_id' => $shipment->driver_id,
                'rating'    => $data['rating'],
                'comment'   => filled($data['comment'] ?? null) ? trim($data['comment']) : null,
            ]
        );

        if (! $review->wasRecentlyCreated) {
            return redirect()
                ->route('customer.shipments.show', $shipment)
                ->withErrors(['rating' => 'You have already reviewed this shipment.']);
        }

        return redirect()
            ->route('customer.shipments.show', $shipment)
            ->with('success', 'Thank you! Your review has been saved.');
    }
}