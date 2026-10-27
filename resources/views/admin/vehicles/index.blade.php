@extends('layouts.app')

@section('title', 'Vehicles - FreightLink')

@section('content')
@php
    $field = 'border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500';
@endphp

<div class="space-y-6">

    {{-- Heading --}}
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Vehicles</h1>
        <p class="text-sm text-slate-500 mt-1">
            Capacity is the maximum weight (kg) the vehicle can carry. A shipment heavier than this cannot be assigned to it.
        </p>
    </div>

    @if ($errors->any())
        <div class="p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 text-sm">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    {{-- Add a vehicle --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
        <h2 class="font-bold text-slate-900 mb-4">➕ Add a vehicle</h2>

        <form method="POST" action="{{ route('admin.vehicles.store') }}" class="flex flex-wrap gap-4 items-end">
            @csrf
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Plate no.</label>
                <input type="text" name="plate_no" value="{{ old('plate_no') }}" class="{{ $field }} w-40" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Type</label>
                <select name="type" class="{{ $field }}" required>
                    @foreach (\App\Models\Vehicle::TYPES as $type)
                        <option value="{{ $type }}" @selected(old('type') === $type)>{{ $type }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Capacity (kg)</label>
                <input type="number" min="1" name="capacity_kg" value="{{ old('capacity_kg') }}" class="{{ $field }} w-36" required>
            </div>
            <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white font-semibold px-5 py-2 rounded-lg transition text-sm">Add vehicle</button>
        </form>
    </div>

    {{-- The list (edit a row, then press Save) --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
        <h2 class="font-bold text-slate-900 mb-1">All vehicles ({{ $vehicles->count() }})</h2>
        <p class="text-xs text-slate-500 mb-4">To change a vehicle, edit the row and press Save.</p>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                        <th class="py-2 pr-3">Plate no.</th>
                        <th class="py-2 pr-3">Type</th>
                        <th class="py-2 pr-3">Capacity (kg)</th>
                        <th class="py-2 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($vehicles as $vehicle)
                        <tr class="border-t border-slate-100 align-middle">
                            <td class="py-3 pr-3">
                                <input form="update-{{ $vehicle->id }}" type="text" name="plate_no" value="{{ $vehicle->plate_no }}"
                                       class="{{ $field }} w-40" required>
                            </td>
                            <td class="py-3 pr-3">
                                <select form="update-{{ $vehicle->id }}" name="type" class="{{ $field }}" required>
                                    @foreach (\App\Models\Vehicle::TYPES as $type)
                                        <option value="{{ $type }}" @selected($vehicle->type === $type)>{{ $type }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="py-3 pr-3">
                                <input form="update-{{ $vehicle->id }}" type="number" min="1" name="capacity_kg"
                                       value="{{ $vehicle->capacity_kg }}" class="{{ $field }} w-32" required>
                            </td>
                            <td class="py-3 text-right whitespace-nowrap">
                                <form id="update-{{ $vehicle->id }}" method="POST" action="{{ route('admin.vehicles.update', $vehicle) }}" class="inline">
                                    @csrf
                                    @method('PUT')
                                    <button type="submit" class="bg-slate-900 hover:bg-slate-700 text-white px-3 py-1.5 rounded-lg text-xs">Save</button>
                                </form>

                                <form method="POST" action="{{ route('admin.vehicles.destroy', $vehicle) }}" class="inline"
                                      onsubmit="return confirm('Remove this vehicle?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="border border-red-300 text-red-700 hover:bg-red-50 px-3 py-1.5 rounded-lg text-xs">Remove</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-8 text-center text-slate-500">No vehicles yet. Add the first one above.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection