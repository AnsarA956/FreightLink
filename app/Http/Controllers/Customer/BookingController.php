<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\CargoType;
use App\Models\ServiceType;
use App\Services\DiscountService;
use App\Services\DistanceService;
use App\Services\PriceCalculator;
use App\Services\ShipmentCreator;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use RuntimeException;

class BookingController extends Controller
{
    // Step 1: the booking form
    public function create()
    {
        return view('customer.booking.create', [
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

        // THIS WAS MISSING: keep the details and the price for the next page
        session(['booking_estimate' => [
            'input' => Arr::only($data, [
                'pickup_address', 'delivery_address', 'weight_kg', 'length_cm',
                'width_cm', 'height_cm', 'cargo_type_id', 'service_type_id', 'discount_code',
            ]),
            'breakdown' => $breakdown,
        ]]);

        return redirect()->route('customer.book.review');
    }

    // Step 3: show the price breakdown (REQ-19)
    public function review()
    {
        $estimate = session('booking_estimate');

        if (! $estimate) {
            return redirect()->route('customer.book.create');
        }

        return view('customer.booking.review', [
            'data' => $estimate['input'],
            'b'    => $estimate['breakdown'],
        ]);
    }

    // Step 4: the customer confirms, so save the shipment (REQ-9)
    public function store(ShipmentCreator $creator)
    {
        $estimate = session('booking_estimate');

        if (! $estimate) {
            return redirect()->route('customer.book.create')
                ->withErrors(['session' => 'Your price estimate expired. Please calculate the price again.']);
        }

        try {
            $shipment = $creator->create(
                auth()->user(),
                $estimate['input'],
                $estimate['breakdown'],
                'Booking confirmed by customer'
            );
        } catch (RuntimeException $e) {
            return redirect()->route('customer.book.create')->withErrors(['discount_code' => $e->getMessage()]);
        }

        session()->forget('booking_estimate');

        return redirect()->route('customer.shipments.show', $shipment)
            ->with('success', 'Booking confirmed. Your tracking ID is ' . $shipment->tracking_id . '.');
    }
}