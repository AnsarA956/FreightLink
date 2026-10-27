<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\CargoType;
use App\Models\ServiceType;
use App\Models\User;
use App\Services\DiscountService;
use App\Services\DistanceService;
use App\Services\PriceCalculator;
use App\Services\ShipmentCreator;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class WalkInBookingController extends Controller
{
    // Step 1: the form (customer details + shipment details)
    public function create()
    {
        return view('staff.walkin.create', [
            'services'   => ServiceType::orderBy('id')->get(),
            'cargoTypes' => CargoType::orderBy('id')->get(),
        ]);
    }

    // Step 2: validate, check the code, get the distance, calculate the price, keep it in the session
    public function estimate(
        Request $request,
        DistanceService $distance,
        PriceCalculator $calculator,
        DiscountService $discounts
    ) {
        $data = $request->validate([
            'customer_name'    => ['required', 'string', 'max:255'],
            // digits, spaces, + - and brackets, 10 to 20 characters (for example 0300 1234567)
            'customer_phone'   => ['required', 'string', 'max:20', 'regex:/^[0-9+\s\-()]{10,20}$/'],
            'customer_email'   => ['nullable', 'email', 'max:255'],
            'pickup_address'   => ['required', 'string', 'min:5', 'max:255'],
            'delivery_address' => ['required', 'string', 'min:5', 'max:255', 'different:pickup_address'],
            'weight_kg'        => ['required', 'numeric', 'min:0.1', 'max:50000'],
            'length_cm'        => ['required', 'numeric', 'min:1', 'max:1000'],
            'width_cm'         => ['required', 'numeric', 'min:1', 'max:1000'],
            'height_cm'        => ['required', 'numeric', 'min:1', 'max:1000'],
            'cargo_type_id'    => ['required', 'exists:cargo_types,id'],
            'service_type_id'  => ['required', 'exists:service_types,id'],
            'discount_code'    => ['nullable', 'string', 'max:30'],
        ]);

        // A typed email must not belong to a staff, driver or admin account, or to a deactivated one
        if (! empty($data['customer_email'])) {
            $existing = User::where('email', $data['customer_email'])->first();

            if ($existing && ($existing->role !== 'customer' || ! $existing->is_active)) {
                return back()->withInput()->withErrors([
                    'customer_email' => 'This email belongs to an account that cannot receive walk-in bookings. Use another email or leave it empty.',
                ]);
            }
        }

        // Check the code first, so a wrong code does not use up a map request
        try {
            $discountCode = $discounts->resolve($data['discount_code'] ?? null);
        } catch (RuntimeException $e) {
            return back()->withInput()->withErrors(['discount_code' => $e->getMessage()]);
        }

        try {
            $route = $distance->route($data['pickup_address'], $data['delivery_address']);
            $km    = $route['km'];
        } catch (RuntimeException $e) {
            return back()->withInput()->withErrors(['distance' => $e->getMessage()]);
        }

        $breakdown = $calculator->calculate(
            (float) $data['weight_kg'],
            (float) $data['length_cm'],
            (float) $data['width_cm'],
            (float) $data['height_cm'],
            $km,
            ServiceType::findOrFail($data['service_type_id']),
            CargoType::findOrFail($data['cargo_type_id']),
            $discountCode
        );
        // Keep the coordinates with the price, so the map can be shown and saved
        $breakdown = array_merge($breakdown, [
            'pickup_lat'   => $route['pickup_lat'],
            'pickup_lng'   => $route['pickup_lng'],
            'delivery_lat' => $route['delivery_lat'],
            'delivery_lng' => $route['delivery_lng'],
        ]);
        session(['walkin_estimate' => [
            'customer' => [
                'name'  => $data['customer_name'],
                'phone' => $data['customer_phone'],
                'email' => $data['customer_email'] ?? null,
            ],
            'input' => Arr::only($data, [
                'pickup_address', 'delivery_address', 'weight_kg', 'length_cm',
                'width_cm', 'height_cm', 'cargo_type_id', 'service_type_id', 'discount_code',
            ]),
            'breakdown' => $breakdown,
        ]]);

        return redirect()->route('staff.walkin.review');
    }

    // Step 3: show the price breakdown
    public function review()
    {
        $estimate = session('walkin_estimate');

        if (! $estimate) {
            return redirect()->route('staff.walkin.create');
        }

        return view('staff.walkin.review', [
            'customer' => $estimate['customer'],
            'data'     => $estimate['input'],
            'b'        => $estimate['breakdown'],
        ]);
    }

    // Step 4: confirm. Find or create the customer, then save the shipment (REQ-10)
    public function store(ShipmentCreator $creator)
    {
        $estimate = session('walkin_estimate');

        if (! $estimate) {
            return redirect()->route('staff.walkin.create')
                ->withErrors(['session' => 'The price estimate expired. Please calculate the price again.']);
        }

        $c          = $estimate['customer'];
        $newAccount = false;

        try {
            $shipment = DB::transaction(function () use ($estimate, $c, $creator, &$newAccount) {
                $customer = ! empty($c['email']) ? User::where('email', $c['email'])->first() : null;

                if ($customer && ($customer->role !== 'customer' || ! $customer->is_active)) {
                    throw new RuntimeException('This email belongs to an account that cannot receive walk-in bookings.');
                }

                // An existing customer without a phone number gets the one entered at the desk,
                // so the SMS notifications can reach them
                if ($customer && empty($customer->phone)) {
                    $customer->update(['phone' => $c['phone']]);
                }

                if (! $customer) {
                    $newAccount = true;

                    // A walk-in customer without an email gets a placeholder address and an
                    // unknown random password, so nobody can log in to this account.
                    $customer = User::create([
                        'name'      => $c['name'],
                        'email'     => $c['email'] ?: 'walkin-' . Str::lower(Str::random(10)) . '@freightlink.local',
                        'phone'     => $c['phone'],
                        'password'  => Str::random(40),
                        'role'      => 'customer',
                        'is_active' => true,
                    ]);
                }

                return $creator->create(
                    $customer,
                    $estimate['input'],
                    $estimate['breakdown'],
                    'Walk-in booking created by staff member ' . auth()->user()->name
                );
            });
        } catch (RuntimeException $e) {
            return redirect()->route('staff.walkin.create')->withErrors(['booking' => $e->getMessage()]);
        }

        session()->forget('walkin_estimate');

        return redirect()->route('admin.shipments.show', $shipment)
            ->with('success', 'Walk-in booking confirmed. Tracking ID: ' . $shipment->tracking_id
                . ($newAccount ? ' (a new customer record was created).' : '.'));
    }
}