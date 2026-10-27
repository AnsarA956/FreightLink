@extends('layouts.app')

@section('title', $shipment->tracking_id . ' - FreightLink')

@section('content')
@php
    $canShare  = in_array($shipment->status, ['Picked Up', 'In Transit'], true);
    $hasCoords = $shipment->pickup_lat !== null && $shipment->pickup_lng !== null
              && $shipment->delivery_lat !== null && $shipment->delivery_lng !== null;

    $failed   = in_array($shipment->status, ['Cancelled', 'Delivery Failed'], true);
    $finished = in_array($shipment->status, ['Delivered', 'Closed'], true);
    $banner   = $failed ? 'from-red-700 to-red-600' : ($finished ? 'from-green-700 to-green-600' : 'from-slate-900 to-slate-700');

    $phone = preg_replace('/[^0-9+]/', '', (string) $shipment->user->phone);

    // Opens the phone's map app with directions (no key needed)
    $pickupNav   = $shipment->pickup_lat !== null
        ? 'https://www.google.com/maps/dir/?api=1&destination=' . $shipment->pickup_lat . ',' . $shipment->pickup_lng : null;
    $deliveryNav = $shipment->delivery_lat !== null
        ? 'https://www.google.com/maps/dir/?api=1&destination=' . $shipment->delivery_lat . ',' . $shipment->delivery_lng : null;
@endphp

<div class="max-w-3xl mx-auto space-y-6">

    @if ($errors->any())
        <div class="p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 text-sm">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    {{-- Banner --}}
    <div class="bg-gradient-to-r {{ $banner }} text-white rounded-2xl p-6 shadow">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <div class="text-xs uppercase tracking-wider text-white/70">Tracking ID</div>
                <h1 class="text-3xl font-bold font-mono">{{ $shipment->tracking_id }}</h1>
            </div>
            <span class="bg-white/20 px-4 py-1.5 rounded-full text-sm font-semibold">{{ $shipment->status }}</span>
        </div>
    </div>

    {{-- Customer and stops --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
            <div>
                <div class="text-xs uppercase tracking-wide text-slate-500">Customer</div>
                <div class="font-semibold text-slate-900">{{ $shipment->user->name }}</div>
                <div class="text-sm text-slate-600">{{ $shipment->user->phone }}</div>
            </div>
            @if ($phone !== '')
                <a href="tel:{{ $phone }}" class="bg-slate-900 hover:bg-slate-700 text-white px-5 py-2.5 rounded-lg text-sm font-semibold transition">📞 Call customer</a>
            @endif
        </div>

        <div class="space-y-4 text-sm">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <div class="text-xs uppercase tracking-wide text-green-700 font-semibold">● Pickup</div>
                    <div class="font-medium">{{ $shipment->pickup_address }}</div>
                </div>
                @if ($pickupNav)
                    <a href="{{ $pickupNav }}" target="_blank" rel="noopener"
                       class="shrink-0 border border-slate-300 hover:bg-slate-100 px-3 py-2 rounded-lg text-xs transition">🧭 Navigate</a>
                @endif
            </div>

            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <div class="text-xs uppercase tracking-wide text-red-700 font-semibold">● Delivery</div>
                    <div class="font-medium">{{ $shipment->delivery_address }}</div>
                </div>
                @if ($deliveryNav)
                    <a href="{{ $deliveryNav }}" target="_blank" rel="noopener"
                       class="shrink-0 border border-slate-300 hover:bg-slate-100 px-3 py-2 rounded-lg text-xs transition">🧭 Navigate</a>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm mt-5 pt-5 border-t border-slate-100">
            <div>
                <div class="text-xs uppercase tracking-wide text-slate-500">Distance</div>
                <div class="font-semibold">{{ number_format($shipment->distance_km, 2) }} km</div>
            </div>
            <div>
                <div class="text-xs uppercase tracking-wide text-slate-500">Weight</div>
                <div class="font-semibold">{{ number_format($shipment->weight_kg, 2) }} kg</div>
            </div>
            <div>
                <div class="text-xs uppercase tracking-wide text-slate-500">Size</div>
                <div class="font-semibold">{{ $shipment->length_cm + 0 }} × {{ $shipment->width_cm + 0 }} × {{ $shipment->height_cm + 0 }} cm</div>
            </div>
            <div>
                <div class="text-xs uppercase tracking-wide text-slate-500">Vehicle</div>
                <div class="font-semibold">{{ $shipment->vehicle?->plate_no ?? '-' }}</div>
            </div>
            <div class="col-span-2 md:col-span-4">
                <div class="text-xs uppercase tracking-wide text-slate-500">Service / Cargo</div>
                <div class="font-semibold">{{ $shipment->serviceType->name }} / {{ $shipment->cargoType->name }}</div>
            </div>
        </div>
    </div>

    {{-- Update the status --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
        <h2 class="font-bold text-slate-900 mb-3">Update status</h2>

        @if (count($options) > 0)
            <form method="POST" action="{{ route('driver.shipments.status', $shipment) }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">
                        Note @if (in_array('Delivery Failed', $options, true)) (required if the delivery failed) @else (optional) @endif
                    </label>
                    <textarea name="note" rows="2" maxlength="255"
                              class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">{{ old('note') }}</textarea>
                </div>
                <div class="flex flex-col sm:flex-row gap-3">
                    @foreach ($options as $option)
                        <button type="submit" name="status" value="{{ $option }}"
                                class="{{ $option === 'Delivery Failed' ? 'bg-red-600 hover:bg-red-700' : 'bg-green-600 hover:bg-green-700' }} text-white font-semibold px-6 py-3.5 rounded-lg transition">
                            Mark as {{ $option }}
                        </button>
                    @endforeach
                </div>
            </form>
        @else
            <p class="text-sm text-slate-500 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2">
                There is no further status change for this shipment ("{{ $shipment->status }}").
            </p>
        @endif
    </div>

    {{-- Share the location --}}
    @if ($canShare)
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <h2 class="font-bold text-slate-900 mb-1">📡 Share my location</h2>
            <p class="text-sm text-slate-500 mb-3">
                While you share, the customer and the office can see where you are on the map. Keep this page open and the screen on.
                Sharing stops when you mark the shipment Delivered or Delivery Failed.
            </p>

            <p id="share-status" class="text-sm text-slate-600 mb-3 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2">Not sharing.</p>

            <div class="flex flex-wrap gap-3">
                <button type="button" id="share-toggle" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-3 rounded-lg transition">
                    Start sharing my location
                </button>

                @if (config('app.debug') && $hasCoords)
                    <button type="button" id="share-demo" class="bg-slate-600 hover:bg-slate-700 text-white px-5 py-3 rounded-lg transition">
                        Demo: simulate the trip
                    </button>
                @endif
            </div>

            @if (config('app.debug') && $hasCoords)
                <p class="text-xs text-slate-500 mt-2">
                    The demo button is visible only in test mode. It sends 11 positions, one every 4 seconds, from the pickup point to the delivery point.
                </p>
            @endif
        </div>

        <script>
            (function () {
                var url    = @json(route('driver.shipments.location', $shipment));
                var token  = @json(csrf_token());
                var route  = @json($hasCoords ? [
                    'from' => [(float) $shipment->pickup_lat, (float) $shipment->pickup_lng],
                    'to'   => [(float) $shipment->delivery_lat, (float) $shipment->delivery_lng],
                ] : null);

                var button   = document.getElementById('share-toggle');
                var demoBtn  = document.getElementById('share-demo');
                var statusEl = document.getElementById('share-status');

                var watchId  = null;
                var lastSent = 0;
                var wakeLock = null;
                var demoTimer = null;

                function say(text, bad) {
                    statusEl.textContent = text;
                    statusEl.className = 'text-sm mb-3 rounded-lg px-3 py-2 border ' +
                        (bad ? 'text-red-700 bg-red-50 border-red-200' : 'text-slate-600 bg-slate-50 border-slate-200');
                }

                function send(lat, lng, accuracy) {
                    return fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': token
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({ latitude: lat, longitude: lng, accuracy: accuracy })
                    }).then(function (response) {
                        return response.json()
                            .catch(function () { return {}; })
                            .then(function (data) { return { ok: response.ok, data: data }; });
                    });
                }

                function handle(result) {
                    if (result.ok) {
                        say('Sharing. Last sent at ' + new Date().toLocaleTimeString() + '.', false);
                        return;
                    }

                    say(result.data.message || 'The position could not be sent.', true);

                    if (result.data.stop) {
                        stopSharing();
                        stopDemo();
                    }
                }

                function onPosition(position) {
                    var now = Date.now();
                    if (now - lastSent < 10000) { return; }
                    lastSent = now;

                    send(position.coords.latitude, position.coords.longitude, position.coords.accuracy)
                        .then(handle)
                        .catch(function () { say('Could not reach the server. Trying again...', true); });
                }

                function onError(error) {
                    if (error.code === 1) {
                        say('Permission denied. Allow location access for this site in the browser settings, then try again.', true);
                        stopSharing();
                    } else if (error.code === 2) {
                        say('Your position is not available right now. Still trying...', true);
                    } else {
                        say('Finding your position is taking too long. Still trying...', true);
                    }
                }

                function startSharing() {
                    if (!('geolocation' in navigator)) {
                        say('This browser cannot share a location.', true);
                        return;
                    }

                    if (!window.isSecureContext) {
                        say('Location sharing needs HTTPS, or http://127.0.0.1 on this computer. Use the demo button to test.', true);
                        return;
                    }

                    say('Waiting for your position...', false);
                    watchId = navigator.geolocation.watchPosition(onPosition, onError, {
                        enableHighAccuracy: true,
                        maximumAge: 5000,
                        timeout: 20000
                    });
                    button.textContent = 'Stop sharing';

                    if ('wakeLock' in navigator) {
                        navigator.wakeLock.request('screen').then(function (lock) { wakeLock = lock; }).catch(function () {});
                    }
                }

                function stopSharing() {
                    if (watchId !== null) {
                        navigator.geolocation.clearWatch(watchId);
                        watchId = null;
                    }
                    if (wakeLock) {
                        wakeLock.release().catch(function () {});
                        wakeLock = null;
                    }
                    button.textContent = 'Start sharing my location';
                }

                function stopDemo() {
                    if (demoTimer !== null) {
                        clearInterval(demoTimer);
                        demoTimer = null;
                    }
                    if (demoBtn) { demoBtn.disabled = false; }
                }

                button.addEventListener('click', function () {
                    if (watchId === null) {
                        startSharing();
                    } else {
                        stopSharing();
                        say('Not sharing.', false);
                    }
                });

                if (demoBtn && route) {
                    demoBtn.addEventListener('click', function () {
                        var step = 0;
                        demoBtn.disabled = true;

                        function sendStep() {
                            var t   = step / 10;
                            var lat = route.from[0] + (route.to[0] - route.from[0]) * t;
                            var lng = route.from[1] + (route.to[1] - route.from[1]) * t;

                            send(lat, lng, 10).then(function (result) {
                                handle(result);
                                if (result.ok) { say('Demo: sent position ' + (step + 1) + ' of 11.', false); }
                            }).catch(function () { say('Could not reach the server.', true); });

                            step++;
                            if (step > 10) {
                                stopDemo();
                            }
                        }

                        sendStep();
                        demoTimer = setInterval(sendStep, 4000);
                    });
                }
            })();
        </script>
    @endif

    {{-- Route map --}}
    @include('partials.route-map', [
        'mapId'    => 'driver-shipment-map',
        'pickup'   => [$shipment->pickup_lat, $shipment->pickup_lng],
        'delivery' => [$shipment->delivery_lat, $shipment->delivery_lng],
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

    <a href="{{ route('driver.dashboard') }}" class="inline-block text-slate-600 hover:text-slate-900 text-sm">← Back to my shipments</a>
</div>
@endsection