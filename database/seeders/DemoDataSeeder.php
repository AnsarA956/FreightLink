<?php

namespace Database\Seeders;

use App\Models\CargoType;
use App\Models\Driver;
use App\Models\Review;
use App\Models\ServiceType;
use App\Models\Shipment;
use App\Models\StatusLog;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\PriceCalculator;
use App\Services\ShipmentCreator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DemoDataSeeder extends Seeder
{
    private const TOTAL = 40;

    // name, latitude, longitude
    private const CITIES = [
        ['Faisalabad',  31.4504, 73.1350],
        ['Lahore',      31.5204, 74.3587],
        ['Narowal',     32.1019, 74.8731],
        ['Sialkot',     32.4945, 74.5229],
        ['Jhang',       31.2681, 72.3181],
        ['Sargodha',    32.0740, 72.6861],
        ['Multan',      30.1575, 71.5249],
        ['Islamabad',   33.6844, 73.0479],
        ['Gujranwala',  32.1877, 74.1945],
        ['Okara',       30.8138, 73.4534],
    ];

    private const STREETS = [
        'Main Bazaar', 'Model Town', 'Satellite Town', 'Industrial Area', 'Civil Lines', 'Railway Road',
    ];

    private const CUSTOMERS = [
        'Ali Raza', 'Sana Malik', 'Usman Tariq', 'Hina Aslam', 'Bilal Ahmad', 'Ayesha Khan',
    ];

    private const COMMENTS = [
        'Delivered on time, thank you.',
        'The driver was polite and careful with the parcel.',
        'Good service and fair price.',
        'A little late, but the cargo was safe.',
        'Very fast delivery.',
        null,
        null,
    ];

    public function run(PriceCalculator $calculator, ShipmentCreator $creator): void
    {
        $drivers  = Driver::all();
        $vehicles = Vehicle::all();
        $services = ServiceType::all();
        $cargos   = CargoType::all();

        // Stop with a clear message if something the demo needs does not exist yet
        $missing = [];
        if ($drivers->count() < 2)  { $missing[] = 'at least 2 drivers (admin, Drivers page)'; }
        if ($vehicles->count() < 2) { $missing[] = 'at least 2 vehicles (admin, Vehicles page)'; }
        if ($services->isEmpty())   { $missing[] = 'service types'; }
        if ($cargos->isEmpty())     { $missing[] = 'cargo types'; }

        if ($missing) {
            $this->command->error('Please add first: ' . implode(', ', $missing));
            return;
        }

        // Start fresh: remove the demo data of an earlier run
        $this->call(DemoCleanupSeeder::class);

        // 6 demo customers (password for all: Demo@12345)
        $customers = collect();

        foreach (self::CUSTOMERS as $i => $name) {
            $customers->push(User::create([
                'name'      => $name,
                'email'     => 'demo-customer-' . ($i + 1) . '@freightlink.test',
                'phone'     => '0300100' . str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT),
                'password'  => 'Demo@12345',
                'role'      => 'customer',
                'is_active' => true,
            ]));
        }

        $created = 0;

        for ($n = 0; $n < self::TOTAL; $n++) {
            // Two different cities
            $pair = collect(self::CITIES)->shuffle()->take(2)->values();
            $from = $pair[0];
            $to   = $pair[1];

            $km     = round($this->roadKm($from, $to), 2);
            $weight = random_int(10, 400) / 10;
            $length = random_int(10, 60);
            $width  = random_int(10, 60);
            $height = random_int(10, 60);

            $service = $services->random();
            $cargo   = $cargos->random();

            $breakdown = $calculator->calculate(
                (float) $weight, (float) $length, (float) $width, (float) $height,
                $km, $service, $cargo, null
            );

            $breakdown = array_merge($breakdown, [
                'pickup_lat'   => $from[1],
                'pickup_lng'   => $from[2],
                'delivery_lat' => $to[1],
                'delivery_lng' => $to[2],
            ]);

            $input = [
                'service_type_id'  => $service->id,
                'cargo_type_id'    => $cargo->id,
                'pickup_address'   => collect(self::STREETS)->random() . ', ' . $from[0],
                'delivery_address' => collect(self::STREETS)->random() . ', ' . $to[0],
                'weight_kg'        => $weight,
                'length_cm'        => $length,
                'width_cm'         => $width,
                'height_cm'        => $height,
            ];

            $customer = $customers->random();
            $shipment = $creator->create($customer, $input, $breakdown, 'Demo booking');

            // About a quarter of the shipments are recent, so some are still on the road
            $daysAgo = random_int(1, 100) <= 25 ? random_int(0, 2) : random_int(3, 60);

            $booked = now()->subDays($daysAgo)->setTime(random_int(8, 18), random_int(0, 59));
            if ($booked->isFuture()) {
                $booked = now()->subMinutes(30);
            }

            if ($daysAgo <= 2) {
                $target = collect(['Booked', 'Assigned', 'Picked Up', 'In Transit'])->random();
            } else {
                $target = random_int(1, 100) <= 88 ? 'Delivered' : 'Cancelled';
            }

            $this->progress($shipment, $booked, $target, $drivers, $vehicles);

            // Most delivered shipments get a rating
            if ($target === 'Delivered' && random_int(1, 100) <= 70) {
                $review = new Review([
                    'shipment_id' => $shipment->id,
                    'user_id'     => $customer->id,
                    'driver_id'   => $shipment->driver_id,
                    'rating'      => collect([5, 5, 5, 4, 4, 4, 3, 3, 2, 5])->random(),
                    'comment'     => collect(self::COMMENTS)->random(),
                ]);
                $review->created_at = $shipment->updated_at->copy()->addHours(random_int(1, 20));
                $review->updated_at = $review->created_at;
                $review->save();
            }

            $created++;
        }

        $this->command->info("Done. {$created} demo shipments created for 6 demo customers.");
        $this->command->info('Demo customer login: demo-customer-1@freightlink.test / Demo@12345');
    }

    // Moves a shipment through its statuses and sets believable dates
    private function progress(Shipment $s, Carbon $booked, string $target, $drivers, $vehicles): void
    {
        $time = $booked->copy();

        $s->created_at = $booked;
        StatusLog::where('shipment_id', $s->id)->update(['changed_at' => $booked]);

        if ($target === 'Booked') {
            $s->updated_at = $time;
            $s->save();
            return;
        }

        if ($target === 'Cancelled') {
            $time = $this->later($time, 1, 6);

            StatusLog::create([
                'shipment_id' => $s->id,
                'status'      => 'Cancelled',
                'note'        => 'Demo: cancelled by the customer',
                'changed_at'  => $time,
            ]);

            $s->status     = 'Cancelled';
            $s->updated_at = $time;
            $s->save();
            return;
        }

        // A driver and a vehicle that can carry the weight
        $driver = $drivers->random();
        $fit    = $vehicles->filter(fn ($v) => $v->capacity_kg >= $s->weight_kg);
        $vehicle = ($fit->isNotEmpty() ? $fit : $vehicles)->random();

        $s->driver_id  = $driver->id;
        $s->vehicle_id = $vehicle->id;

        foreach (['Assigned', 'Picked Up', 'In Transit', 'Delivered'] as $status) {
            $time = $this->later($time, 1, 8);

            StatusLog::create([
                'shipment_id' => $s->id,
                'status'      => $status,
                'note'        => 'Demo: ' . $status,
                'changed_at'  => $time,
            ]);

            $s->status = $status;

            if ($status === $target) {
                break;
            }
        }

        $s->updated_at = $time;
        $s->save();
    }

    // A later time, but never in the future
    private function later(Carbon $time, int $minHours, int $maxHours): Carbon
    {
        $next = $time->copy()->addHours(random_int($minHours, $maxHours));

        return $next->isFuture() ? now() : $next;
    }

    // Straight-line distance between two cities, multiplied by 1.3 to look like a road distance
    private function roadKm(array $a, array $b): float
    {
        $dLat = deg2rad($b[1] - $a[1]);
        $dLng = deg2rad($b[2] - $a[2]);

        $h = sin($dLat / 2) ** 2
           + cos(deg2rad($a[1])) * cos(deg2rad($b[1])) * sin($dLng / 2) ** 2;

        return 6371 * 2 * asin(min(1, sqrt($h))) * 1.3;
    }
}