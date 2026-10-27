<?php

namespace App\Http\Controllers;

use App\Models\Shipment;
use App\Models\ShipmentLocation;
use Illuminate\Http\JsonResponse;

class ShipmentLocationController extends Controller
{
    private const SHARING = ['Picked Up', 'In Transit'];

    // The latest driver position, for the map on the customer and admin pages
    public function show(Shipment $shipment): JsonResponse
    {
        $user = auth()->user();

        // Only the customer who booked it, the admin and the staff
        abort_unless(
            in_array($user->role, ['admin', 'staff'], true) || $shipment->user_id === $user->id,
            403
        );

        $none = ['active' => false, 'status' => $shipment->status];

        if (! in_array($shipment->status, self::SHARING, true)) {
            return $this->json($none);
        }

        $location = ShipmentLocation::where('shipment_id', $shipment->id)->first();

        if (! $location) {
            return $this->json($none);
        }

        return $this->json([
            'active'      => true,
            'status'      => $shipment->status,
            'lat'         => $location->latitude,
            'lng'         => $location->longitude,
            'accuracy'    => $location->accuracy_m,
            'seconds_ago' => (int) round($location->updated_at->diffInSeconds(now(), true)),
        ]);
    }

    // A position must never be kept in the browser cache
    private function json(array $data): JsonResponse
    {
        return response()->json($data)->header('Cache-Control', 'no-store');
    }
}