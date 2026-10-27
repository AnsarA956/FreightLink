@extends('layouts.app')

@section('title', $shipment->tracking_id . ' - FreightLink')

@section('content')
@php
    $failed   = in_array($shipment->status, ['Cancelled', 'Delivery Failed'], true);
    $finished = in_array($shipment->status, ['Delivered', 'Closed'], true);
    $banner   = $failed ? 'from-red-700 to-red-600' : ($finished ? 'from-green-700 to-green-600' : 'from-slate-900 to-slate-700');
    $trackUrl = route('track.show', $shipment->tracking_id);
@endphp

<div class="max-w-5xl mx-auto space-y-6">

    {{-- Banner --}}
    <div class="bg-gradient-to-r {{ $banner }} text-white rounded-2xl p-6 shadow">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <div class="text-xs uppercase tracking-wider text-white/70">Tracking ID</div>
                <h1 class="text-3xl font-bold font-mono">{{ $shipment->tracking_id }}</h1>
                <div class="text-sm text-white/80 mt-1">Booked on {{ $shipment->created_at->format('d M Y, H:i') }}</div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <span class="bg-white/20 px-4 py-1.5 rounded-full text-sm font-semibold">{{ $shipment->status }}</span>

                <button type="button" id="copy-link" data-url="{{ $trackUrl }}"
                        class="bg-white/10 hover:bg-white/20 px-4 py-1.5 rounded-full text-sm transition">
                    🔗 Copy tracking link
                </button>

                @if ($shipment->status === 'Booked' && ! $shipment->driver_id)
                    <form method="POST" action="{{ route('shipments.cancel', $shipment) }}"
                          onsubmit="return confirm('Cancel this booking? This cannot be undone.')">
                        @csrf
                        <button type="submit" class="bg-red-600 hover:bg-red-700 px-4 py-1.5 rounded-full text-sm font-semibold transition">Cancel booking</button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Left column --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Details --}}
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <h2 class="font-bold text-slate-900 mb-4">📦 Shipment details</h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                    <div class="sm:col-span-2">
                        <div class="text-xs uppercase tracking-wide text-slate-500">From</div>
                        <div class="font-medium">{{ $shipment->pickup_address }}</div>
                    </div>
                    <div class="sm:col-span-2">
                        <div class="text-xs uppercase tracking-wide text-slate-500">To</div>
                        <div class="font-medium">{{ $shipment->delivery_address }}</div>
                    </div>
                    <div>
                        <div class="text-xs uppercase tracking-wide text-slate-500">Distance</div>
                        <div class="font-medium">{{ number_format($shipment->distance_km, 2) }} km</div>
                    </div>
                    <div>
                        <div class="text-xs uppercase tracking-wide text-slate-500">Weight</div>
                        <div class="font-medium">{{ number_format($shipment->weight_kg, 2) }} kg <span class="text-slate-500 font-normal">(chargeable {{ number_format($shipment->chargeable_kg, 2) }} kg)</span></div>
                    </div>
                    <div>
                        <div class="text-xs uppercase tracking-wide text-slate-500">Service</div>
                        <div class="font-medium">{{ $shipment->serviceType->name }}</div>
                    </div>
                    <div>
                        <div class="text-xs uppercase tracking-wide text-slate-500">Cargo</div>
                        <div class="font-medium">{{ $shipment->cargoType->name }}</div>
                    </div>
                </div>

                <div class="mt-4 pt-4 border-t border-slate-100 text-sm">
                    <span class="text-slate-500">Tracking link:</span>
                    <a href="{{ $trackUrl }}" class="text-orange-600 hover:underline break-all">{{ $trackUrl }}</a>
                </div>
            </div>

            {{-- Route map with the live driver position --}}
            @include('partials.route-map', [
                'mapId'    => 'shipment-map',
                'pickup'   => [$shipment->pickup_lat, $shipment->pickup_lng],
                'delivery' => [$shipment->delivery_lat, $shipment->delivery_lng],
                'liveUrl'  => route('shipments.location', $shipment),
            ])

            {{-- Status history --}}
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <h2 class="font-bold text-slate-900 mb-4">🕒 Status history</h2>

                <ol class="border-l-2 border-slate-200 ml-2 space-y-5">
                    @foreach ($shipment->statusLogs as $log)
                        <li class="pl-5 relative">
                            <span class="absolute -left-[7px] top-1.5 w-3 h-3 rounded-full {{ $loop->last ? 'bg-orange-500 ring-4 ring-orange-100' : 'bg-slate-400' }}"></span>
                            <div class="font-semibold text-sm text-slate-900">{{ $log->status }}</div>
                            <div class="text-xs text-slate-500">{{ $log->changed_at->format('d M Y, H:i') }}@if ($log->note) · {{ $log->note }}@endif</div>
                        </li>
                    @endforeach
                </ol>
            </div>
        </div>

        {{-- Right column --}}
        <div class="space-y-6">

            {{-- Price --}}
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <h2 class="font-bold text-slate-900 mb-3">💲 Price</h2>

                <table class="w-full text-sm">
                    <tbody>
                        <tr class="border-t border-slate-100">
                            <td class="py-2 text-slate-600">Subtotal</td>
                            <td class="py-2 text-right">Rs {{ number_format($shipment->subtotal, 2) }}</td>
                        </tr>
                        <tr class="border-t border-slate-100">
                            <td class="py-2 text-slate-600">Service multiplier</td>
                            <td class="py-2 text-right">× {{ number_format($shipment->service_multiplier, 2) }}</td>
                        </tr>
                        <tr class="border-t border-slate-100">
                            <td class="py-2 text-slate-600">Cargo surcharge</td>
                            <td class="py-2 text-right">Rs {{ number_format($shipment->cargo_surcharge, 2) }}</td>
                        </tr>
                        @if ($shipment->discount_amount > 0)
                            <tr class="border-t border-slate-100 text-green-700">
                                <td class="py-2">Discount{{ $shipment->discount_code ? ' (' . $shipment->discount_code . ', ' . number_format($shipment->discount_percent, 2) . '%)' : '' }}</td>
                                <td class="py-2 text-right">- Rs {{ number_format($shipment->discount_amount, 2) }}</td>
                            </tr>
                        @endif
                        <tr class="border-t border-slate-100">
                            <td class="py-2 text-slate-600">Tax ({{ number_format($shipment->tax_percent, 2) }}%)</td>
                            <td class="py-2 text-right">Rs {{ number_format($shipment->tax_amount, 2) }}</td>
                        </tr>
                        <tr class="border-t-2 border-slate-900 font-bold text-base">
                            <td class="py-3">Total</td>
                            <td class="py-3 text-right text-orange-600">Rs {{ number_format($shipment->total_price, 2) }}</td>
                        </tr>
                    </tbody>
                </table>

                <div class="mt-3">
                    @if ($finished)
                        <a href="{{ route('invoice.download', $shipment) }}"
                           class="block text-center bg-slate-900 hover:bg-slate-700 text-white px-4 py-2.5 rounded-lg text-sm font-semibold transition">
                            ⬇ Download invoice (PDF)
                        </a>
                    @else
                        <p class="text-xs text-slate-500">The invoice is available after delivery.</p>
                    @endif
                </div>
            </div>

            {{-- Payment (test mode) --}}
            @if ($errors->has('payment'))
                <div class="p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 text-sm">{{ $errors->first('payment') }}</div>
            @endif

            @if ($shipment->paidPayment)
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                    <div class="flex items-center justify-between mb-3">
                        <h2 class="font-bold text-slate-900">💳 Payment</h2>
                        <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-xs font-semibold">Paid</span>
                    </div>
                    <div class="text-sm space-y-1.5">
                        <div><span class="text-slate-500">Amount:</span> Rs {{ number_format($shipment->paidPayment->amount, 2) }}</div>
                        <div><span class="text-slate-500">Card:</span> {{ $shipment->paidPayment->card_brand }} ending {{ $shipment->paidPayment->card_last4 }} (test mode)</div>
                        <div><span class="text-slate-500">Reference:</span> <span class="font-mono text-xs">{{ $shipment->paidPayment->reference }}</span></div>
                        <div><span class="text-slate-500">Paid on:</span> {{ $shipment->paidPayment->paid_at->format('d M Y, H:i') }}</div>
                    </div>
                </div>
            @elseif ($shipment->status !== 'Cancelled')
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                    <h2 class="font-bold text-slate-900 mb-1">💳 Payment</h2>
                    <p class="text-sm text-slate-500 mb-4">Not paid yet. You can pay online now or pay on delivery.</p>
                    <a href="{{ route('customer.shipments.pay', $shipment) }}"
                       class="block text-center bg-green-600 hover:bg-green-700 text-white px-4 py-2.5 rounded-lg text-sm font-semibold transition">
                        Pay online (test mode)
                    </a>
                </div>
            @endif

            {{-- Rating and review (REQ-45) --}}
            @if ($finished && $shipment->driver_id)
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                    <h2 class="font-bold text-slate-900 mb-3">⭐ Your rating</h2>

                    @if ($shipment->review)
                        <div class="text-2xl text-amber-500" aria-label="{{ $shipment->review->rating }} out of 5 stars">
                            {{ str_repeat('★', $shipment->review->rating) }}<span class="text-slate-300">{{ str_repeat('★', 5 - $shipment->review->rating) }}</span>
                        </div>
                        <div class="text-sm text-slate-500 mb-2">
                            {{ $shipment->review->rating }} out of 5 · reviewed on {{ $shipment->review->created_at->format('d M Y') }}
                        </div>
                        @if ($shipment->review->comment)
                            <p class="text-sm text-slate-700">{{ $shipment->review->comment }}</p>
                        @endif
                    @else
                        <p class="text-sm text-slate-500 mb-3">How was your delivery? Your rating helps us improve our drivers and service.</p>

                        @if ($errors->hasAny(['rating', 'comment']))
                            <div class="mb-3 p-3 rounded-lg bg-red-50 border border-red-200 text-red-800 text-sm">
                                @foreach (array_merge($errors->get('rating'), $errors->get('comment')) as $error)
                                    <div>{{ $error }}</div>
                                @endforeach
                            </div>
                        @endif

                        <form method="POST" action="{{ route('customer.shipments.review', $shipment) }}" class="space-y-3">
                            @csrf
                            <div class="space-y-1.5 text-sm">
                                @foreach ([5 => 'Excellent', 4 => 'Good', 3 => 'Average', 2 => 'Poor', 1 => 'Very poor'] as $value => $label)
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="radio" name="rating" value="{{ $value }}" @checked((int) old('rating') === $value) required>
                                        <span class="text-amber-500">{{ str_repeat('★', $value) }}</span>
                                        <span class="text-slate-600">{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Comment (optional)</label>
                                <textarea name="comment" rows="3" maxlength="1000"
                                          class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500"
                                          placeholder="Tell us about your delivery">{{ old('comment') }}</textarea>
                            </div>
                            <button type="submit" class="w-full bg-orange-500 hover:bg-orange-600 text-white font-semibold px-4 py-2.5 rounded-lg transition text-sm">Submit rating</button>
                        </form>
                    @endif
                </div>
            @endif
        </div>
    </div>

    <a href="{{ route('customer.shipments.index') }}" class="inline-block text-slate-600 hover:text-slate-900 text-sm">← Back to my shipments</a>
</div>

<script>
    // Copies the public tracking link
    (function () {
        var button = document.getElementById('copy-link');
        if (!button) { return; }

        button.addEventListener('click', function () {
            var url = button.getAttribute('data-url');

            function done() {
                button.textContent = '✓ Link copied';
                setTimeout(function () { button.textContent = '🔗 Copy tracking link'; }, 2000);
            }

            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(url).then(done).catch(function () { window.prompt('Copy this link:', url); });
            } else {
                window.prompt('Copy this link:', url);
            }
        });
    })();
</script>
@endsection