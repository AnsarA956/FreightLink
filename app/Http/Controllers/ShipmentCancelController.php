<?php

namespace App\Http\Controllers;

use App\Models\Shipment;
use App\Models\StatusLog;
use Illuminate\Support\Facades\DB;

class ShipmentCancelController extends Controller
{
    // REQ-12: a booking can be cancelled before a driver is assigned
    public function __invoke(Shipment $shipment)
    {
        $user    = auth()->user();
        $isStaff = in_array($user->role, ['admin', 'staff']);
        $isOwner = $user->role === 'customer' && $user->id === $shipment->user_id;

        // Only the customer who booked it, staff and the admin may cancel
        abort_unless($isStaff || $isOwner, 403);

        $cancelled = DB::transaction(function () use ($shipment, $user, $isOwner) {
            // Read the shipment again and lock it, so a driver assignment at the same moment cannot slip through
            $fresh = Shipment::whereKey($shipment->id)->lockForUpdate()->first();

            if ($fresh->status !== 'Booked' || $fresh->driver_id !== null) {
                return false;
            }

            $fresh->update(['status' => 'Cancelled']);

            StatusLog::create([
                'shipment_id' => $fresh->id,
                'status'      => 'Cancelled',
                'note'        => 'Cancelled by ' . ($isOwner ? 'the customer' : ucfirst($user->role) . ' ' . $user->name),
                'changed_at'  => now(),
            ]);

            return true;
        });

        if (! $cancelled) {
            return back()->with('error', 'This booking can no longer be cancelled, because a driver has already been assigned or it has already moved on.');
        }

        return back()->with('success', 'The booking ' . $shipment->tracking_id . ' was cancelled.');
    }
}