<?php

namespace App\Http\Controllers;

use App\Mail\ShipmentUpdateMail;
use App\Models\Shipment;
use Illuminate\Support\Facades\Mail;

class ShipmentMailController extends Controller
{
    public function send(Shipment $shipment)
    {
        $email = $shipment->user->email ?? null;

        if (! $email) {
            return back()->with('error', 'This shipment has no customer email.');
        }

        try {
            Mail::to($email)->send(new ShipmentUpdateMail($shipment));
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'Email failed: '.$e->getMessage());
        }

        return back()->with('success', 'Email sent to '.$email);
    }
}
