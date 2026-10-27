<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\Shipment;
use App\Models\ShipmentLocation;
use App\Models\StatusLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShipmentController extends Controller
{
    // The only moves a driver may make
    private const NEXT = [
        'Assigned'   => ['Picked Up'],
        'Picked Up'  => ['In Transit'],
        'In Transit' => ['Delivered', 'Delivery Failed'],
    ];

    private const ACTIVE = ['Assigned', 'Picked Up', 'In Transit'];

    // The location may be shared only in these statuses
    private const SHARING = ['Picked Up', 'In Transit'];

    private function currentDriver(): Driver
    {
        $driver = Driver::with('user')->where('user_id', auth()->id())->first();
        abort_unless($driver, 403, 'No driver profile is linked to this account.');

        return $driver;
    }

    // REQ-25: the driver's own shipments
    public function index()
    {
        $driver = $this->currentDriver();

        $active = Shipment::with(['user', 'serviceType'])
            ->where('driver_id', $driver->id)
            ->whereIn('status', self::ACTIVE)
            ->latest()
            ->get();

        $past = Shipment::with(['user'])
            ->where('driver_id', $driver->id)
            ->whereNotIn('status', self::ACTIVE)
            ->latest()
            ->paginate(10);

        return view('driver.shipments.index', compact('driver', 'active', 'past'));
    }

    public function show(Shipment $shipment)
    {
        $driver = $this->currentDriver();
        abort_unless($shipment->driver_id === $driver->id, 403);

        $shipment->load(['user', 'serviceType', 'cargoType', 'vehicle', 'statusLogs']);

        return view('driver.shipments.show', [
            'shipment' => $shipment,
            'options'  => self::NEXT[$shipment->status] ?? [],
        ]);
    }

    // REQ-27, REQ-28: update the status and log it with date and time
    public function updateStatus(Request $request, Shipment $shipment)
    {
        $driver = $this->currentDriver();
        abort_unless($shipment->driver_id === $driver->id, 403);

        $data = $request->validate([
            'status' => ['required', 'string'],
            'note'   => ['nullable', 'string', 'max:255'],
        ]);

        $error = null;

        DB::transaction(function () use ($shipment, $driver, $data, &$error) {
            // Re-read the row locked, so two clicks cannot both succeed
            $locked = Shipment::whereKey($shipment->id)->lockForUpdate()->first();

            $allowed = self::NEXT[$locked->status] ?? [];

            if ($locked->driver_id !== $driver->id || ! in_array($data['status'], $allowed, true)) {
                $error = 'You cannot change this shipment from "' . $locked->status . '" to "' . $data['status'] . '".';
                return;
            }

            if ($data['status'] === 'Delivery Failed' && blank($data['note'] ?? null)) {
                $error = 'Please write the reason for the failed delivery.';
                return;
            }

            $locked->update(['status' => $data['status']]);

            StatusLog::create([
                'shipment_id' => $locked->id,
                'status'      => $data['status'],
                'note'        => $data['note'] ?: 'Updated by driver ' . $driver->user->name,
                'changed_at'  => now(),
            ]);

            // The trip is over, so the saved position is deleted
            if (! in_array($data['status'], self::SHARING, true)) {
                ShipmentLocation::where('shipment_id', $locked->id)->delete();
            }
        });

        if ($error) {
            return back()->withInput()->withErrors(['status' => $error]);
        }

        return redirect()->route('driver.shipments.show', $shipment)
            ->with('success', 'Status updated to ' . $data['status'] . '.');
    }

    // REQ-31: the driver's phone sends its current position
    public function updateLocation(Request $request, Shipment $shipment)
    {
        $driver = $this->currentDriver();
        abort_unless($shipment->driver_id === $driver->id, 403);

        if (! in_array($shipment->status, self::SHARING, true)) {
            return response()->json([
                'message' => 'Location sharing is only available while the shipment is Picked Up or In Transit.',
                'stop'    => true,
            ], 409);
        }

        $data = $request->validate([
            'latitude'  => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy'  => ['nullable', 'numeric', 'min:0', 'max:100000'],
        ]);

        // 0,0 is what a broken GPS reports, so it is not a real position
        if (abs((float) $data['latitude']) < 0.0001 && abs((float) $data['longitude']) < 0.0001) {
            return response()->json(['message' => 'That position is not valid.'], 422);
        }

        $location = ShipmentLocation::firstOrNew(['shipment_id' => $shipment->id]);

        $location->fill([
            'driver_id'  => $driver->id,
            'latitude'   => round((float) $data['latitude'], 7),
            'longitude'  => round((float) $data['longitude'], 7),
            'accuracy_m' => isset($data['accuracy']) ? (int) round((float) $data['accuracy']) : null,
        ]);

        // Even if the driver has not moved, the "last updated" time must move forward
        $location->updated_at = now();
        $location->save();

        return response()->json(['ok' => true]);
    }
}