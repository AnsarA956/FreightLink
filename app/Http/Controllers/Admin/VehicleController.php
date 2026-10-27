<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VehicleController extends Controller
{
    public function index()
    {
        $vehicles = Vehicle::orderBy('id')->get();

        return view('admin.vehicles.index', compact('vehicles'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'plate_no'    => ['required', 'string', 'max:20', 'unique:vehicles,plate_no'],
            'type'        => ['required', Rule::in(Vehicle::TYPES)],
            'capacity_kg' => ['required', 'integer', 'min:1'],
        ]);

        Vehicle::create($data);

        return back()->with('success', 'Vehicle added.');
    }

    public function update(Request $request, Vehicle $vehicle)
    {
        $data = $request->validate([
            'plate_no'    => ['required', 'string', 'max:20', Rule::unique('vehicles', 'plate_no')->ignore($vehicle->id)],
            'type'        => ['required', Rule::in(Vehicle::TYPES)],
            'capacity_kg' => ['required', 'integer', 'min:1'],
        ]);

        $vehicle->update($data);

        return back()->with('success', 'Vehicle updated.');
    }

    public function destroy(Vehicle $vehicle)
    {
        $vehicle->delete();

        return back()->with('success', 'Vehicle removed.');
    }
}