<?php

namespace Tests\Feature;

use App\Models\CargoType;
use App\Models\Driver;
use App\Models\ServiceType;
use App\Models\Shipment;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\AutomationService;
use App\Services\DistanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutomationTest extends TestCase
{
    use RefreshDatabase;

    public function test_distance_service_handles_typos_and_resolves_pakistan_cities(): void
    {
        $service = app(DistanceService::class);

        // Typo test: 'Faislabad' -> 'faisalabad', 'Lahor' -> 'lahore'
        $cleaned = $service->clean('Faislabad, Pakistan');
        $this->assertEquals('faisalabad', $cleaned);

        $route = $service->route('Faislabad', 'Lahor');

        $this->assertIsArray($route);
        $this->assertGreaterThan(50, $route['km']);
        $this->assertEquals(31.4504, $route['pickup_lat']);
        $this->assertEquals(73.1350, $route['pickup_lng']);
        $this->assertEquals(31.5204, $route['delivery_lat']);
        $this->assertEquals(74.3587, $route['delivery_lng']);
    }

    public function test_auto_assign_picks_free_driver_and_vehicle(): void
    {
        $customer = User::create([
            'name' => 'Customer Test',
            'email' => 'cust@example.com',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);

        $driverUser = User::create([
            'name' => 'Driver Test',
            'email' => 'driver@example.com',
            'password' => bcrypt('password'),
            'role' => 'driver',
            'is_active' => true,
        ]);

        $driver = Driver::create([
            'user_id' => $driverUser->id,
            'license_no' => 'PK-DL-98765',
            'status' => 'Available',
        ]);

        $vehicle = Vehicle::create([
            'plate_no' => 'LEA-1234',
            'type' => 'Truck',
            'capacity_kg' => 5000,
        ]);

        $serviceType = ServiceType::create([
            'name' => 'Standard',
            'base_rate' => 500,
            'rate_per_kg' => 20,
            'rate_per_km' => 15,
            'speed_multiplier' => 1.0,
        ]);

        $cargoType = CargoType::create([
            'name' => 'General Goods',
            'surcharge' => 0,
        ]);

        $shipment = Shipment::create([
            'tracking_id' => 'FL-999999',
            'user_id' => $customer->id,
            'service_type_id' => $serviceType->id,
            'cargo_type_id' => $cargoType->id,
            'pickup_address' => 'Faisalabad',
            'delivery_address' => 'Lahore',
            'weight_kg' => 100,
            'length_cm' => 50,
            'width_cm' => 50,
            'height_cm' => 50,
            'volumetric_kg' => 25,
            'chargeable_kg' => 100,
            'distance_km' => 140,
            'subtotal' => 3000,
            'service_multiplier' => 1.0,
            'cargo_surcharge' => 0,
            'discount_amount' => 0,
            'tax_percent' => 16,
            'tax_amount' => 480,
            'total_price' => 3480,
            'status' => 'Booked',
        ]);

        $automation = app(AutomationService::class);
        $result = $automation->run('auto-assign');

        $this->assertEquals(1, $result['assigned']);

        $shipment->refresh();
        $this->assertEquals('Assigned', $shipment->status);
        $this->assertEquals($driver->id, $shipment->driver_id);
        $this->assertEquals($vehicle->id, $shipment->vehicle_id);
    }

    public function test_payment_reminder_and_auto_cancel_tasks_run(): void
    {
        $automation = app(AutomationService::class);

        $reminderResult = $automation->run('payment-reminders');
        $this->assertArrayHasKey('reminders_sent', $reminderResult);

        $cancelResult = $automation->run('auto-cancel');
        $this->assertArrayHasKey('cancelled', $cancelResult);
    }
}
