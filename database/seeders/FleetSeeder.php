<?php

namespace Database\Seeders;

use App\Models\Driver;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;

class FleetSeeder extends Seeder
{
    public function run(): void
    {
        $vehicles = [
            ['FSD-1234', 'Pickup', 1000],
            ['LHR-5678', 'Truck', 5000],
            ['ISB-9012', 'Van', 500],
        ];

        foreach ($vehicles as [$plate, $type, $capacity]) {
            Vehicle::updateOrCreate(
                ['plate_no' => $plate],
                ['type' => $type, 'capacity_kg' => $capacity]
            );
        }

        // Give the seeded driver account its driver record
        $user = User::where('email', 'driver@freightlink.test')->first();

        if ($user) {
            Driver::updateOrCreate(
                ['user_id' => $user->id],
                ['license_no' => 'LHR-4471', 'status' => 'Available']
            );
        }
    }
}