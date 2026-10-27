<?php

namespace Database\Seeders;

use App\Models\CargoType;
use App\Models\PricingSetting;
use App\Models\ServiceType;
use Illuminate\Database\Seeder;

class PricingSeeder extends Seeder
{
    public function run(): void
    {
        PricingSetting::current();

        $services = [
            'Standard'  => 1.00,
            'Express'   => 1.50,
            'Overnight' => 2.00,
        ];

        foreach ($services as $name => $multiplier) {
            ServiceType::updateOrCreate(['name' => $name], ['multiplier' => $multiplier]);
        }

        $cargo = [
            'General'    => 0,
            'Fragile'    => 300,
            'Perishable' => 500,
            'Electronics' => 400,
        ];

        foreach ($cargo as $name => $surcharge) {
            CargoType::updateOrCreate(['name' => $name], ['surcharge' => $surcharge]);
        }
    }
}