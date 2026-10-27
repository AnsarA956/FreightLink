<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\Shipment;
use App\Models\ShipmentLocation;
use App\Services\RouteOptimizer;
use Illuminate\Http\Request;

class RouteController extends Controller
{
    private const ACTIVE = ['Assigned', 'Picked Up', 'In Transit'];

    // REQ-26: suggested order of stops for a driver with several shipments
    public function show(Request $request, RouteOptimizer $optimizer)
    {
        $driver = Driver::where('user_id', auth()->id())->first();
        abort_unless($driver, 403, 'No driver profile is linked to this account.');

        $data = $request->validate([
            'lat' => ['nullable', 'numeric', 'between:-90,90', 'required_with:lng'],
            'lng' => ['nullable', 'numeric', 'between:-180,180', 'required_with:lat'],
        ]);

        $shipments = Shipment::where('driver_id', $driver->id)
            ->whereIn('status', self::ACTIVE)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        // Where does the driver start? 1) the browser position, 2) the last shared position, 3) automatic
        $start      = null;
        $startLabel = 'The best first stop was chosen automatically.';

        if (isset($data['lat'], $data['lng'])) {
            $start      = [(float) $data['lat'], (float) $data['lng']];
            $startLabel = 'Starting from your current position.';
        } else {
            $saved = ShipmentLocation::where('driver_id', $driver->id)
                ->where('updated_at', '>=', now()->subMinutes(30))
                ->orderByDesc('updated_at')
                ->first();

            if ($saved) {
                $start      = [(float) $saved->latitude, (float) $saved->longitude];
                $startLabel = 'Starting from your last shared position.';
            }
        }

        $result = $optimizer->optimize($shipments, $start);

        return view('driver.route', [
            'result'      => $result,
            'startLabel'  => $startLabel,
            'shipmentCount' => $shipments->count(),
        ]);
    }
}