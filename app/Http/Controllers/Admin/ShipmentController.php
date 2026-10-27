<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\Shipment;
use App\Models\StatusLog;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ShipmentController extends Controller
{
    // Statuses in which a driver and vehicle may still be assigned or changed
    private const ASSIGNABLE = ['Booked', 'Assigned', 'Delivery Failed'];

    // REQ-43: list with search and filters
    public function index(Request $request)
    {
        $filters = $request->validate([
            'q'      => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(Shipment::STATUSES)],
            'from'   => ['nullable', 'date'],
            'to'     => ['nullable', 'date'],
        ]);

        $shipments = Shipment::with(['user', 'serviceType', 'driver.user', 'vehicle'])
            ->when($filters['q'] ?? null, function ($query, $q) {
                $query->where(function ($w) use ($q) {
                    $w->where('tracking_id', 'like', "%{$q}%")
                      ->orWhereHas('user', function ($u) use ($q) {
                          $u->where('name', 'like', "%{$q}%")
                            ->orWhere('email', 'like', "%{$q}%");
                      });
                });
            })
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['from'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['to'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '<=', $date))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.shipments.index', [
            'shipments' => $shipments,
            'statuses'  => Shipment::STATUSES,
        ]);
    }

    public function show(Shipment $shipment)
    {
        $shipment->load(['user', 'serviceType', 'cargoType', 'driver.user', 'vehicle', 'statusLogs']);

        // Only active drivers who are not off duty can be chosen
        $drivers = Driver::with('user')
            ->where('status', '!=', 'Off Duty')
            ->whereHas('user', fn ($q) => $q->where('is_active', true))
            ->get();

        $vehicles = Vehicle::orderBy('capacity_kg')->get();

        return view('admin.shipments.show', [
            'shipment'   => $shipment,
            'drivers'    => $drivers,
            'vehicles'   => $vehicles,
            'assignable' => in_array($shipment->status, self::ASSIGNABLE),
        ]);
    }

    // REQ-23, REQ-24, REQ-65: assign a driver and vehicle, with the capacity check
    public function assign(Request $request, Shipment $shipment)
    {
        if (! in_array($shipment->status, self::ASSIGNABLE)) {
            return back()->withErrors([
                'assign' => "A shipment with the status \"{$shipment->status}\" cannot be assigned or reassigned.",
            ]);
        }

        $data = $request->validate([
            'driver_id'  => ['required', 'exists:drivers,id'],
            'vehicle_id' => ['required', 'exists:vehicles,id'],
        ]);

        $driver  = Driver::with('user')->findOrFail($data['driver_id']);
        $vehicle = Vehicle::findOrFail($data['vehicle_id']);

        if (! $driver->user->is_active || $driver->status === 'Off Duty') {
            return back()->withInput()->withErrors([
                'driver_id' => 'This driver is deactivated or off duty and cannot be assigned.',
            ]);
        }

        // The safety rule: the vehicle must be able to carry the shipment
        if ($vehicle->capacity_kg < (float) $shipment->weight_kg) {
            return back()->withInput()->withErrors([
                'vehicle_id' => "Vehicle {$vehicle->plate_no} can carry only " . number_format($vehicle->capacity_kg)
                    . ' kg, but this shipment weighs ' . number_format($shipment->weight_kg, 2)
                    . ' kg. Please choose a larger vehicle.',
            ]);
        }

        $wasBooked = $shipment->status === 'Booked';

        DB::transaction(function () use ($shipment, $driver, $vehicle, $wasBooked) {
            $shipment->update([
                'driver_id'  => $driver->id,
                'vehicle_id' => $vehicle->id,
                'status'     => 'Assigned',
            ]);

            StatusLog::create([
                'shipment_id' => $shipment->id,
                'status'      => 'Assigned',
                'note'        => ($wasBooked ? 'Assigned to ' : 'Reassigned to ')
                                 . $driver->user->name . ' with vehicle ' . $vehicle->plate_no,
                'changed_at'  => now(),
            ]);
        });

        return back()->with('success', 'Shipment assigned to ' . $driver->user->name . ' with vehicle ' . $vehicle->plate_no . '.');
    }
}