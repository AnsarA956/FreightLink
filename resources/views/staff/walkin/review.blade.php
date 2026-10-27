@extends('layouts.app')

@section('title', 'Confirm Walk-in Booking - FreightLink')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">

    <div>
        <h1 class="text-2xl font-bold text-slate-900">Price breakdown</h1>
        <p class="text-sm text-slate-500 mt-1">Check the details and the price with the customer, then confirm the booking.</p>

        <div class="flex items-center gap-2 mt-4 text-xs font-medium">
            <span class="bg-green-500 text-white rounded-full px-3 py-1">✓ 1. Details</span>
            <span class="text-slate-400">→</span>
            <span class="bg-orange-500 text-white rounded-full px-3 py-1">2. Price</span>
            <span class="text-slate-400">→</span>
            <span class="bg-slate-200 text-slate-600 rounded-full px-3 py-1">3. Confirm</span>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Price table --}}
        <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <h2 class="font-bold text-slate-900 mb-3">Weight</h2>

            <table class="w-full text-sm mb-6">
                <tbody>
                    <tr class="border-t border-slate-100">
                        <td class="py-2 text-slate-500">Actual weight</td>
                        <td class="py-2 text-right">{{ number_format($b['weight_kg'], 2) }} kg</td>
                    </tr>
                    <tr class="border-t border-slate-100">
                        <td class="py-2 text-slate-500">Volumetric weight</td>
                        <td class="py-2 text-right">{{ number_format($b['volumetric_kg'], 2) }} kg</td>
                    </tr>
                    <tr class="border-t border-slate-100">
                        <td class="py-2 text-slate-500">Chargeable weight (the larger one)</td>
                        <td class="py-2 text-right font-semibold">{{ number_format($b['chargeable_kg'], 2) }} kg</td>
                    </tr>
                </tbody>
            </table>

            <h2 class="font-bold text-slate-900 mb-3">Price</h2>

            <table class="w-full text-sm">
                <tbody>
                    <tr class="border-t border-slate-100">
                        <td class="py-2">Base charge</td>
                        <td class="py-2 text-right">Rs {{ number_format($b['base_charge'], 2) }}</td>
                    </tr>
                    <tr class="border-t border-slate-100">
                        <td class="py-2">Weight charge ({{ number_format($b['chargeable_kg'], 2) }} kg × Rs {{ number_format($b['rate_per_kg'], 2) }})</td>
                        <td class="py-2 text-right">Rs {{ number_format($b['weight_charge'], 2) }}</td>
                    </tr>
                    <tr class="border-t border-slate-100">
                        <td class="py-2">Distance charge ({{ number_format($b['distance_km'], 2) }} km × Rs {{ number_format($b['rate_per_km'], 2) }})</td>
                        <td class="py-2 text-right">Rs {{ number_format($b['distance_charge'], 2) }}</td>
                    </tr>
                    <tr class="border-t border-slate-200 font-semibold bg-slate-50">
                        <td class="py-2 px-1">Subtotal</td>
                        <td class="py-2 px-1 text-right">Rs {{ number_format($b['subtotal'], 2) }}</td>
                    </tr>
                    <tr class="border-t border-slate-100">
                        <td class="py-2">{{ $b['service_name'] }} service (× {{ number_format($b['service_multiplier'], 2) }})</td>
                        <td class="py-2 text-right">Rs {{ number_format($b['service_amount'], 2) }}</td>
                    </tr>
                    <tr class="border-t border-slate-100">
                        <td class="py-2">{{ $b['cargo_name'] }} cargo surcharge</td>
                        <td class="py-2 text-right">Rs {{ number_format($b['cargo_surcharge'], 2) }}</td>
                    </tr>
                    @if ($b['discount_amount'] > 0)
                        <tr class="border-t border-slate-100 text-green-700">
                            <td class="py-2">Discount ({{ $b['discount_code'] }}, {{ number_format($b['discount_percent'], 2) }}%)</td>
                            <td class="py-2 text-right">- Rs {{ number_format($b['discount_amount'], 2) }}</td>
                        </tr>
                    @endif
                    <tr class="border-t border-slate-100">
                        <td class="py-2">Tax ({{ number_format($b['tax_percent'], 2) }}%)</td>
                        <td class="py-2 text-right">Rs {{ number_format($b['tax_amount'], 2) }}</td>
                    </tr>
                    <tr class="border-t-2 border-slate-900 text-lg font-bold">
                        <td class="py-3">Total</td>
                        <td class="py-3 text-right text-orange-600">Rs {{ number_format($b['total_price'], 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Summary and buttons --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 h-fit">
            <h2 class="font-bold text-slate-900 mb-3">Walk-in shipment</h2>

            <div class="text-sm space-y-3 mb-6">
                <div>
                    <div class="text-xs uppercase tracking-wide text-slate-500">Customer</div>
                    <div class="font-medium">{{ $customer['name'] }}</div>
                    <div class="text-slate-600">{{ $customer['phone'] }}@if ($customer['email']) · {{ $customer['email'] }}@endif</div>
                </div>
                <div>
                    <div class="text-xs uppercase tracking-wide text-slate-500">From</div>
                    <div class="font-medium">{{ $data['pickup_address'] }}</div>
                </div>
                <div>
                    <div class="text-xs uppercase tracking-wide text-slate-500">To</div>
                    <div class="font-medium">{{ $data['delivery_address'] }}</div>
                </div>
                <div>
                    <div class="text-xs uppercase tracking-wide text-slate-500">Road distance</div>
                    <div class="font-medium">{{ number_format($b['distance_km'], 2) }} km</div>
                </div>
            </div>

            <div class="bg-orange-50 border border-orange-200 rounded-xl p-4 mb-5 text-center">
                <div class="text-xs text-orange-800">Total to collect</div>
                <div class="text-2xl font-bold text-orange-600">Rs {{ number_format($b['total_price'], 2) }}</div>
            </div>

            <form method="POST" action="{{ route('staff.walkin.store') }}" id="confirm-form" class="mb-3">
                @csrf
                <button type="submit" id="confirm-button"
                        class="w-full bg-green-600 hover:bg-green-700 disabled:bg-slate-400 text-white font-semibold py-3 rounded-lg transition">
                    Confirm booking
                </button>
            </form>

            <a href="{{ route('staff.walkin.create') }}"
               class="block text-center w-full px-4 py-2.5 rounded-lg border border-slate-300 hover:bg-slate-100 text-sm transition">
                ← Edit details
            </a>
        </div>
    </div>

    {{-- Route map (full width, below the price) --}}
    @include('partials.route-map', [
        'mapId'    => 'walkin-review-map',
        'pickup'   => [$b['pickup_lat'] ?? null, $b['pickup_lng'] ?? null],
        'delivery' => [$b['delivery_lat'] ?? null, $b['delivery_lng'] ?? null],
    ])
</div>

<script>
    // One click can only create one booking
    document.getElementById('confirm-form').addEventListener('submit', function () {
        var button = document.getElementById('confirm-button');
        button.disabled = true;
        button.textContent = 'Booking...';
    });

    window.addEventListener('pageshow', function () {
        var button = document.getElementById('confirm-button');
        button.disabled = false;
        button.textContent = 'Confirm booking';
    });
</script>
@endsection