<?php

namespace Database\Seeders;

use App\Models\Review;
use App\Models\Shipment;
use App\Models\ShipmentLocation;
use App\Models\StatusLog;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoCleanupSeeder extends Seeder
{
    // Deletes only the demo customers and their shipments
    public function run(): void
    {
        $userIds = User::where('email', 'like', 'demo-customer-%@freightlink.test')->pluck('id');

        if ($userIds->isEmpty()) {
            return;
        }

        $shipmentIds = Shipment::whereIn('user_id', $userIds)->pluck('id');

        Review::whereIn('shipment_id', $shipmentIds)->delete();
        ShipmentLocation::whereIn('shipment_id', $shipmentIds)->delete();
        StatusLog::whereIn('shipment_id', $shipmentIds)->delete();
        Shipment::whereIn('id', $shipmentIds)->delete();
        User::whereIn('id', $userIds)->delete();
    }
}