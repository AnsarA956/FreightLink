<?php

namespace Database\Seeders;

use App\Models\CargoType;
use App\Models\Driver;
use App\Models\ServiceType;
use App\Models\Shipment;
use App\Models\StatusLog;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\PriceCalculator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;

class DemoShipmentSeeder extends Seeder
{
    public function run(): void
    {
        $customers  = User::where('role', 'customer')->where('is_active', true)->get();
        $services   = ServiceType::all();
        $cargoTypes = CargoType::all();
        $drivers    = Driver::all();
        $vehicles   = Vehicle::all();

        if ($customers->isEmpty() || $services->isEmpty() || $cargoTypes->isEmpty()) {
            $this->command?->warn('Customers, service types or cargo types are missing. Run UserSeeder and PricingSeeder first.');

            return;
        }

        // pickup, delivery, approximate road distance in km
        $routes = [
            ['Faisalabad', 'Lahore', 130],
            ['Lahore', 'Islamabad', 380],
            ['Faisalabad', 'Multan', 240],
            ['Karachi', 'Hyderabad', 165],
            ['Islamabad', 'Peshawar', 185],
            ['Lahore', 'Sialkot', 125],
        ];

        $paths = [
            'Booked'     => ['Booked'],
            'Assigned'   => ['Booked', 'Assigned'],
            'Picked Up'  => ['Booked', 'Assigned', 'Picked Up'],
            'In Transit' => ['Booked', 'Assigned', 'Picked Up', 'In Transit'],
            'Delivered'  => ['Booked', 'Assigned', 'Picked Up', 'In Transit', 'Delivered'],
            'Cancelled'  => ['Booked', 'Cancelled'],
        ];

        $calculator = new PriceCalculator();

        // Events are switched off, so no emails are triggered by demo data
        Model::withoutEvents(function () use ($customers, $services, $cargoTypes, $drivers, $vehicles, $routes, $paths, $calculator) {
            for ($i = 1; $i <= 60; $i++) {
                $trackingId = 'FL-D' . str_pad((string) $i, 6, '0', STR_PAD_LEFT);

                if (Shipment::where('tracking_id', $trackingId)->exists()) {
                    continue;
                }

                [$from, $to, $baseKm] = $routes[array_rand($routes)];
                $km      = round($baseKm + random_int(-8, 8) + random_int(0, 99) / 100, 2);
                $weight  = random_int(1, 200);
                $length  = random_int(15, 70);
                $width   = random_int(15, 70);
                $height  = random_int(15, 70);
                $service = $services->random();
                $cargo   = $cargoTypes->random();

                $price = $calculator->calculate($weight, $length, $width, $height, $km, $service, $cargo);

                // Older shipments are almost all finished, recent ones are still moving
                $daysAgo = random_int(0, 59);
                $roll    = random_int(1, 100);

                if ($daysAgo > 7) {
                    $final = $roll <= 85 ? 'Delivered' : 'Cancelled';
                } else {
                    $final = match (true) {
                        $roll <= 35 => 'Delivered',
                        $roll <= 50 => 'In Transit',
                        $roll <= 62 => 'Picked Up',
                        $roll <= 72 => 'Assigned',
                        $roll <= 90 => 'Booked',
                        default     => 'Cancelled',
                    };
                }

                $created = now()->subDays($daysAgo)->setTime(random_int(8, 18), random_int(0, 59));

                if ($created->isFuture()) {
                    $created = now()->subMinutes(random_int(5, 90));
                }

                // Work out the time of every status change
                $times = [];
                $time  = $created->copy();

                foreach ($paths[$final] as $index => $status) {
                    if ($index > 0) {
                        $time = $time->copy()->addMinutes(random_int(30, 600));

                        if ($time->isFuture()) {
                            $time = now();
                        }
                    }

                    $times[$status] = $time->copy();
                }

                $isAssigned = in_array($final, ['Assigned', 'Picked Up', 'In Transit', 'Delivered'], true);

                $shipment = new Shipment([
                    'tracking_id'        => $trackingId,
                    'user_id'            => $customers->random()->id,
                    'driver_id'          => $isAssigned && $drivers->isNotEmpty() ? $drivers->random()->id : null,
                    'vehicle_id'         => $isAssigned && $vehicles->isNotEmpty() ? $vehicles->random()->id : null,
                    'service_type_id'    => $service->id,
                    'cargo_type_id'      => $cargo->id,
                    'pickup_address'     => $from . ' (demo)',
                    'delivery_address'   => $to . ' (demo)',
                    'weight_kg'          => $weight,
                    'length_cm'          => $length,
                    'width_cm'           => $width,
                    'height_cm'          => $height,
                    'volumetric_kg'      => $price['volumetric_kg'],
                    'chargeable_kg'      => $price['chargeable_kg'],
                    'distance_km'        => $km,
                    'subtotal'           => $price['subtotal'],
                    'service_multiplier' => $price['service_multiplier'],
                    'cargo_surcharge'    => $price['cargo_surcharge'],
                    'discount_amount'    => 0,
                    'tax_percent'        => $price['tax_percent'],
                    'tax_amount'         => $price['tax_amount'],
                    'total_price'        => $price['total_price'],
                    'status'             => $final,
                ]);

                $shipment->created_at = $created;
                $shipment->updated_at = end($times);
                $shipment->save();

                foreach ($times as $status => $changedAt) {
                    StatusLog::create([
                        'shipment_id' => $shipment->id,
                        'status'      => $status,
                        'note'        => 'Demo data',
                        'changed_at'  => $changedAt,
                    ]);
                }
            }
        });

        $this->command?->info('Demo shipments created. They all have tracking IDs starting with FL-D.');
    }
}