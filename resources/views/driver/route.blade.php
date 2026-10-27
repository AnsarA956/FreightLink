@extends('layouts.app')

@section('title', 'Suggested route - FreightLink')

@section('content')
@php
    $stops = $result['stops'];
@endphp

<div class="max-w-4xl space-y-6">

    <div class="bg-white rounded-xl shadow p-6">
        <h1 class="text-2xl font-bold mb-1">Suggested route</h1>
        <p class="text-sm text-slate-500 mb-4">
            The best order to visit your pickup and delivery points. A delivery always comes after its own pickup.
        </p>

        @if (count($stops) === 0)
            <div class="p-3 rounded bg-amber-100 text-amber-800 text-sm">
                You have no active shipments with map points, so there is nothing to order.
            </div>
        @else
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
                <div class="border rounded-lg p-3">
                    <div class="text-xs text-slate-500">Stops</div>
                    <div class="text-lg font-bold">{{ count($stops) }}</div>
                </div>
                <div class="border rounded-lg p-3">
                    <div class="text-xs text-slate-500">Suggested route</div>
                    <div class="text-lg font-bold">{{ number_format($result['total_km'], 1) }} km</div>
                </div>
                <div class="border rounded-lg p-3">
                    <div class="text-xs text-slate-500">Without optimization</div>
                    <div class="text-lg font-bold">{{ number_format($result['original_km'], 1) }} km</div>
                </div>
                <div class="border rounded-lg p-3">
                    <div class="text-xs text-slate-500">Saved</div>
                    <div class="text-lg font-bold text-green-700">{{ number_format($result['saved_km'], 1) }} km</div>
                </div>
            </div>

            <p class="text-sm text-slate-600 mb-3">{{ $startLabel }}</p>

            <div class="flex flex-wrap gap-2">
                <button type="button" id="use-position" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm">
                    Use my current position as the start
                </button>
                <a href="{{ route('driver.route') }}" class="px-4 py-2 rounded border text-sm hover:bg-slate-100">Reset</a>
            </div>
            <p id="position-message" class="text-sm text-red-700 mt-2"></p>
        @endif

        @if (! empty($result['skipped']))
            <div class="mt-4 p-3 rounded bg-amber-100 text-amber-800 text-sm">
                These shipments have no map points (they were booked before maps were added), so they are not in the route:
                {{ implode(', ', $result['skipped']) }}
            </div>
        @endif
    </div>

    @if (count($stops) > 0)
        {{-- The ordered list --}}
        <div class="bg-white rounded-xl shadow p-6 overflow-x-auto">
            <h2 class="text-lg font-bold mb-3">Visit the stops in this order</h2>
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-slate-500">
                        <th class="py-2">#</th>
                        <th class="py-2">Stop</th>
                        <th class="py-2">Shipment</th>
                        <th class="py-2">Address</th>
                        <th class="py-2 text-right">From previous</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($stops as $stop)
                        <tr class="border-t align-top">
                            <td class="py-2 font-bold">{{ $stop['step'] }}</td>
                            <td class="py-2">
                                <span class="px-2 py-0.5 rounded-full text-xs {{ $stop['type'] === 'Pickup' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $stop['type'] }}
                                </span>
                            </td>
                            <td class="py-2">
                                <a href="{{ route('driver.shipments.show', $stop['shipment_id']) }}" class="text-blue-600 underline">{{ $stop['tracking_id'] }}</a>
                            </td>
                            <td class="py-2">{{ $stop['address'] }}</td>
                            <td class="py-2 text-right">{{ $stop['leg_km'] > 0 ? number_format($stop['leg_km'], 1) . ' km' : '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- The map --}}
        <div class="bg-white rounded-xl shadow p-6">
            <h2 class="text-lg font-bold mb-3">Route map</h2>
            <div id="route-order-map" style="height: 380px;" class="rounded border relative z-0"></div>
            <p class="text-xs text-slate-500 mt-2">
                Green numbers are pickups and red numbers are deliveries. The blue star is your start position.
                The lines are straight lines between stops, not the road route, so the real road distance will be longer.
            </p>
        </div>

        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        <script>
            (function () {
                var stops = @json($stops);
                var start = @json($result['start']);

                var map = L.map('route-order-map', { scrollWheelZoom: false });

                L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 18,
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                }).addTo(map);

                var points = [];

                if (start) {
                    points.push(start);
                    L.marker(start, {
                        icon: L.divIcon({
                            className: '',
                            html: '<div style="background:#2563eb;color:#fff;width:26px;height:26px;border-radius:50%;text-align:center;line-height:26px;font-size:14px;border:2px solid #1e3a8a;">★</div>',
                            iconSize: [26, 26],
                            iconAnchor: [13, 13]
                        })
                    }).addTo(map).bindTooltip('Start', { direction: 'top' });
                }

                stops.forEach(function (stop) {
                    var position = [stop.lat, stop.lng];
                    points.push(position);

                    var color  = stop.type === 'Pickup' ? '#16a34a' : '#dc2626';
                    var border = stop.type === 'Pickup' ? '#166534' : '#991b1b';

                    L.marker(position, {
                        icon: L.divIcon({
                            className: '',
                            html: '<div style="background:' + color + ';color:#fff;width:26px;height:26px;border-radius:50%;text-align:center;line-height:26px;font-size:13px;font-weight:bold;border:2px solid ' + border + ';">' + stop.step + '</div>',
                            iconSize: [26, 26],
                            iconAnchor: [13, 13]
                        })
                    }).addTo(map).bindTooltip(stop.type + ': ' + stop.tracking_id, { direction: 'top' });
                });

                L.polyline(points, { color: '#2563eb', weight: 3, dashArray: '6 6' }).addTo(map);
                map.fitBounds(L.latLngBounds(points), { padding: [40, 40] });

                // "Use my current position" reloads this page with the position in the address
                var button  = document.getElementById('use-position');
                var message = document.getElementById('position-message');

                button.addEventListener('click', function () {
                    message.textContent = '';

                    if (!('geolocation' in navigator) || !window.isSecureContext) {
                        message.textContent = 'Your position can be used only on HTTPS, or on http://127.0.0.1 on this computer.';
                        return;
                    }

                    button.disabled = true;
                    button.textContent = 'Finding your position...';

                    navigator.geolocation.getCurrentPosition(function (position) {
                        window.location = @json(route('driver.route')) +
                            '?lat=' + position.coords.latitude.toFixed(6) +
                            '&lng=' + position.coords.longitude.toFixed(6);
                    }, function () {
                        button.disabled = false;
                        button.textContent = 'Use my current position as the start';
                        message.textContent = 'Your position could not be found. Allow location access for this site and try again.';
                    }, { enableHighAccuracy: true, timeout: 15000 });
                });
            })();
        </script>
    @endif
</div>
@endsection