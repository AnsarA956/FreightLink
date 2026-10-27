<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CargoType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CargoTypeController extends Controller
{
    // COLUMN: change this to the surcharge column name from Step 1
    private const SURCHARGE = 'surcharge';

    public function index()
    {
        $cargoTypes = CargoType::orderBy('name')->get();

        return view('admin.cargo-types.index', [
            'cargoTypes' => $cargoTypes,
            'surcharge'  => self::SURCHARGE,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'            => ['required', 'string', 'max:100', 'unique:cargo_types,name'],
            self::SURCHARGE   => ['required', 'numeric', 'min:0'],
        ]);

        CargoType::create($data);

        return back()->with('success', 'Cargo type added.');
    }

    public function update(Request $request, CargoType $cargo_type)
    {
        $data = $request->validate([
            'name'            => ['required', 'string', 'max:100', Rule::unique('cargo_types', 'name')->ignore($cargo_type->id)],
            self::SURCHARGE   => ['required', 'numeric', 'min:0'],
        ]);

        $cargo_type->update($data);

        return back()->with('success', 'Cargo type updated.');
    }

    public function destroy(CargoType $cargo_type)
    {
        if ($cargo_type->shipments()->exists()) {
            return back()->withErrors([
                'delete' => 'This cargo type is used by existing shipments and cannot be deleted.',
            ]);
        }

        $cargo_type->delete();

        return back()->with('success', 'Cargo type deleted.');
    }
}