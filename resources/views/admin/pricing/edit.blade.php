@extends('layouts.app')

@section('title', 'Pricing - FreightLink')

@section('content')
@php
    $field = 'w-full border border-slate-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500';
@endphp

<div class="max-w-5xl mx-auto space-y-6">

    <div>
        <h1 class="text-2xl font-bold text-slate-900">Pricing settings</h1>
        <p class="text-sm text-slate-500 mt-1">These values are used to calculate the price of every new booking. Old shipments keep their old price.</p>
    </div>

    @if ($errors->any())
        <div class="p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 text-sm">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- The rates --}}
        <form method="POST" action="{{ route('admin.pricing.update') }}"
              class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-4">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Base charge (Rs)</label>
                    <input type="number" step="0.01" min="0" name="base_charge" id="base_charge"
                           value="{{ old('base_charge', $setting->base_charge) }}" class="{{ $field }}" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Tax (%)</label>
                    <input type="number" step="0.01" min="0" max="100" name="tax_percent" id="tax_percent"
                           value="{{ old('tax_percent', $setting->tax_percent) }}" class="{{ $field }}" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Rate per kg (Rs)</label>
                    <input type="number" step="0.01" min="0" name="rate_per_kg" id="rate_per_kg"
                           value="{{ old('rate_per_kg', $setting->rate_per_kg) }}" class="{{ $field }}" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Rate per km (Rs)</label>
                    <input type="number" step="0.01" min="0" name="rate_per_km" id="rate_per_km"
                           value="{{ old('rate_per_km', $setting->rate_per_km) }}" class="{{ $field }}" required>
                </div>
            </div>

            <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white font-semibold px-6 py-2.5 rounded-lg transition text-sm">Save pricing</button>
        </form>

        {{-- Live example --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 h-fit">
            <h2 class="font-bold text-slate-900 mb-1">Try an example</h2>
            <p class="text-xs text-slate-500 mb-4">Shows the effect of the numbers on the left. Nothing is saved.</p>

            <div class="grid grid-cols-2 gap-3 mb-4">
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Weight (kg)</label>
                    <input type="number" id="ex_kg" value="10" min="0" step="0.1" class="{{ $field }}">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Distance (km)</label>
                    <input type="number" id="ex_km" value="120" min="0" step="1" class="{{ $field }}">
                </div>
            </div>

            <table class="w-full text-sm">
                <tr class="border-t border-slate-100"><td class="py-2 text-slate-500">Subtotal</td><td class="py-2 text-right" id="ex_sub">-</td></tr>
                <tr class="border-t border-slate-100"><td class="py-2 text-slate-500">Tax</td><td class="py-2 text-right" id="ex_tax">-</td></tr>
                <tr class="border-t-2 border-slate-900 font-bold"><td class="py-2">Total</td><td class="py-2 text-right text-orange-600" id="ex_total">-</td></tr>
            </table>

            <p class="text-xs text-slate-500 mt-3">
                Formula: base + (kg × rate per kg) + (km × rate per km). Without the service multiplier, cargo surcharge and discount.
            </p>
        </div>
    </div>
</div>

<script>
    (function () {
        var ids = ['base_charge', 'rate_per_kg', 'rate_per_km', 'tax_percent', 'ex_kg', 'ex_km'];
        var el  = {};
        ids.forEach(function (id) { el[id] = document.getElementById(id); });

        function money(n) {
            return 'Rs ' + n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function update() {
            var base = parseFloat(el.base_charge.value) || 0;
            var perKg = parseFloat(el.rate_per_kg.value) || 0;
            var perKm = parseFloat(el.rate_per_km.value) || 0;
            var tax = parseFloat(el.tax_percent.value) || 0;
            var kg = parseFloat(el.ex_kg.value) || 0;
            var km = parseFloat(el.ex_km.value) || 0;

            var sub = base + kg * perKg + km * perKm;
            var taxAmount = sub * tax / 100;

            document.getElementById('ex_sub').textContent = money(sub);
            document.getElementById('ex_tax').textContent = money(taxAmount);
            document.getElementById('ex_total').textContent = money(sub + taxAmount);
        }

        ids.forEach(function (id) { el[id].addEventListener('input', update); });
        update();
    })();
</script>
@endsection