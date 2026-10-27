@extends('layouts.app')

@section('title', 'Book a Shipment - FreightLink')

@section('content')
@php
    // If the customer came back from the price page, refill the form
    $draft = session('booking_estimate.input', []);

    $field = 'w-full border border-slate-300 rounded-lg px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500';
@endphp

<div class="max-w-3xl mx-auto">

    {{-- Heading --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-900">Book a shipment</h1>
        <p class="text-sm text-slate-500 mt-1">Fill in the details. You will see the full price before anything is booked.</p>

        <div class="flex items-center gap-2 mt-4 text-xs font-medium">
            <span class="bg-orange-500 text-white rounded-full px-3 py-1">1. Details</span>
            <span class="text-slate-400">→</span>
            <span class="bg-slate-200 text-slate-600 rounded-full px-3 py-1">2. Price</span>
            <span class="text-slate-400">→</span>
            <span class="bg-slate-200 text-slate-600 rounded-full px-3 py-1">3. Confirm</span>
        </div>
    </div>

    @if ($errors->any())
        <div class="mb-4 p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 text-sm">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form id="booking-form" method="POST" action="{{ route('customer.book.estimate') }}" class="space-y-6">
        @csrf

        {{-- Addresses --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <h2 class="font-bold text-slate-900 mb-4">📍 Where is it going?</h2>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Pickup address</label>
                    <input type="text" name="pickup_address" placeholder="e.g. Clock Tower, Faisalabad, Pakistan"
                           value="{{ old('pickup_address', $draft['pickup_address'] ?? '') }}"
                           class="{{ $field }}" required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Delivery address</label>
                    <input type="text" name="delivery_address" placeholder="e.g. Liberty Market, Lahore, Pakistan"
                           value="{{ old('delivery_address', $draft['delivery_address'] ?? '') }}"
                           class="{{ $field }}" required>
                    <p class="text-xs text-slate-500 mt-1">Include the city name so the map service finds the right place.</p>
                </div>
            </div>
        </div>

        {{-- Package --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <h2 class="font-bold text-slate-900 mb-4">📦 The package</h2>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Weight (kg)</label>
                    <input type="number" step="0.01" min="0.1" name="weight_kg" id="weight_kg"
                           value="{{ old('weight_kg', $draft['weight_kg'] ?? '') }}"
                           class="{{ $field }}" required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Package size (cm)</label>
                    <div class="grid grid-cols-3 gap-3">
                        <input type="number" step="0.01" min="1" name="length_cm" id="length_cm" placeholder="Length"
                               value="{{ old('length_cm', $draft['length_cm'] ?? '') }}" class="{{ $field }}" required>
                        <input type="number" step="0.01" min="1" name="width_cm" id="width_cm" placeholder="Width"
                               value="{{ old('width_cm', $draft['width_cm'] ?? '') }}" class="{{ $field }}" required>
                        <input type="number" step="0.01" min="1" name="height_cm" id="height_cm" placeholder="Height"
                               value="{{ old('height_cm', $draft['height_cm'] ?? '') }}" class="{{ $field }}" required>
                    </div>
                </div>

                {{-- Live weight check --}}
                <div id="weight-hint" class="hidden bg-slate-50 border border-slate-200 rounded-xl p-3 text-sm text-slate-700"></div>
            </div>
        </div>

        {{-- Service --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <h2 class="font-bold text-slate-900 mb-4">🚚 Service and cargo</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Cargo type</label>
                    <select name="cargo_type_id" class="{{ $field }}" required>
                        @foreach ($cargoTypes as $cargo)
                            <option value="{{ $cargo->id }}"
                                @selected((string) old('cargo_type_id', $draft['cargo_type_id'] ?? '') === (string) $cargo->id)>
                                {{ $cargo->name }}@if ($cargo->surcharge > 0) (+Rs {{ number_format($cargo->surcharge, 2) }})@endif
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Service type</label>
                    <select name="service_type_id" class="{{ $field }}" required>
                        @foreach ($services as $service)
                            <option value="{{ $service->id }}"
                                @selected((string) old('service_type_id', $draft['service_type_id'] ?? '') === (string) $service->id)>
                                {{ $service->name }} (x{{ number_format($service->multiplier, 2) }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="mt-4">
                <label class="block text-sm font-medium text-slate-700 mb-1">Discount code (optional)</label>
                <input type="text" name="discount_code" value="{{ old('discount_code', $draft['discount_code'] ?? '') }}"
                       placeholder="e.g. WELCOME10" class="{{ $field }} uppercase">
            </div>
        </div>

        <button type="submit" id="submit-button"
                class="w-full sm:w-auto bg-orange-500 hover:bg-orange-600 disabled:bg-slate-400 text-white font-semibold px-8 py-3 rounded-lg transition">
            Calculate price →
        </button>
    </form>
</div>

<script>
    (function () {
        var form   = document.getElementById('booking-form');
        var button = document.getElementById('submit-button');
        var hint   = document.getElementById('weight-hint');

        var weight = document.getElementById('weight_kg');
        var length = document.getElementById('length_cm');
        var width  = document.getElementById('width_cm');
        var height = document.getElementById('height_cm');

        // Shows the volumetric weight, as in the price calculation: (L x W x H) / 5000
        function update() {
            var w = parseFloat(weight.value);
            var l = parseFloat(length.value);
            var b = parseFloat(width.value);
            var h = parseFloat(height.value);

            if (!(w > 0) || !(l > 0) || !(b > 0) || !(h > 0)) {
                hint.classList.add('hidden');
                return;
            }

            var volumetric = (l * b * h) / 5000;
            var chargeable = Math.max(w, volumetric);
            var reason     = volumetric > w ? 'the package is large for its weight' : 'the actual weight is higher';

            hint.innerHTML = 'Volumetric weight: <strong>' + volumetric.toFixed(2) + ' kg</strong> · ' +
                             'Actual weight: <strong>' + w.toFixed(2) + ' kg</strong> · ' +
                             'You are charged for <strong>' + chargeable.toFixed(2) + ' kg</strong> (' + reason + ').';
            hint.classList.remove('hidden');
        }

        [weight, length, width, height].forEach(function (input) {
            input.addEventListener('input', update);
        });
        update();

        // The distance lookup takes a few seconds, so the button is switched off after one click
        form.addEventListener('submit', function () {
            button.disabled = true;
            button.textContent = 'Calculating price...';
        });

        // If the browser returns to this page with the Back button, the button must work again
        window.addEventListener('pageshow', function () {
            button.disabled = false;
            button.textContent = 'Calculate price →';
        });
    })();
</script>
@endsection