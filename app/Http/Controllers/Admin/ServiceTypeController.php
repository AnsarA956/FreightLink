<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServiceType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ServiceTypeController extends Controller
{
    public function index()
    {
        $serviceTypes = ServiceType::orderBy('name')->get();

        return view('admin.service-types.index', compact('serviceTypes'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'       => ['required', 'string', 'max:100', 'unique:service_types,name'],
            'multiplier' => ['required', 'numeric', 'min:0.1', 'max:10'], // COLUMN
        ]);

        ServiceType::create($data);

        return back()->with('success', 'Service type added.');
    }

    public function update(Request $request, ServiceType $service_type)
    {
        $data = $request->validate([
            'name'       => ['required', 'string', 'max:100', Rule::unique('service_types', 'name')->ignore($service_type->id)],
            'multiplier' => ['required', 'numeric', 'min:0.1', 'max:10'], // COLUMN
        ]);

        $service_type->update($data);

        return back()->with('success', 'Service type updated.');
    }

    public function destroy(ServiceType $service_type)
    {
        if ($service_type->shipments()->exists()) {
            return back()->withErrors([
                'delete' => 'This service type is used by existing shipments and cannot be deleted.',
            ]);
        }

        $service_type->delete();

        return back()->with('success', 'Service type deleted.');
    }
}