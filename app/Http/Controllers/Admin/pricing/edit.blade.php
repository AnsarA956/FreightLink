@extends('layouts.app')

@section('title', 'Pricing Settings - FreightLink')

@section('content')
<div class="max-w-xl bg-white rounded-xl shadow p-6">
    <h1 class="text-2xl font-bold mb-1">Pricing Settings</h1>
    <p class="text-sm text-slate-500 mb-4">
        Subtotal = Base Charge + (Chargeable Weight × Rate per kg) + (Distance × Rate per km).
        The service multiplier and cargo surcharge are applied after this, then discount and tax.
    </p>

    @if ($errors->any())
        <div class="mb-4 p-3 rounded bg-red-100 text-red-800 text-sm">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('admin.pricing.update') }}" class="space-y-4">
        @csrf
        @method('PUT')

        <div>
            <label class="block text-sm font-medium mb-1">Base charge (Rs)</label>
            <input type="number" step="0.01" min="0" name="base_charge"
                   value="{{ old('base_charge', $setting->base_charge) }}"
                   class="w-full border rounded px-3 py-2" required>
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Rate per kg (Rs)</label>
            <input type="number" step="0.01" min="0" name="rate_per_kg"
                   value="{{ old('rate_per_kg', $setting->rate_per_kg) }}"
                   class="w-full border rounded px-3 py-2" required>
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Rate per km (Rs)</label>
            <input type="number" step="0.01" min="0" name="rate_per_km"
                   value="{{ old('rate_per_km', $setting->rate_per_km) }}"
                   class="w-full border rounded px-3 py-2" required>
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Tax (%)</label>
            <input type="number" step="0.01" min="0" max="100" name="tax_percent"
                   value="{{ old('tax_percent', $setting->tax_percent) }}"
                   class="w-full border rounded px-3 py-2" required>
        </div>

        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">Save settings</button>
    </form>
</div>
@endsection