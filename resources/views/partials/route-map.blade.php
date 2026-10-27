@php
    $mapId   = $mapId ?? 'route-map';
    $liveUrl = $liveUrl ?? null;

    $pLat = $pickup[0] ?? null;
    $pLng = $pickup[1] ?? null;
    $dLat = $delivery[0] ?? null;
    $dLng = $delivery[1] ?? null;

    $hasMap = $pLat !== null && $pLng !== null && $dLat !== null && $dLng !== null;
@endphp

<div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
    <h2 class="font-bold text-slate-900 mb-3">🗺️ Route map</h2>

    @if ($hasMap)
        <div id="{{ $mapId }}" style="height: 320px;" class="rounded-xl border border-slate-200 relative z-0"></div>

        @if ($liveUrl)
            <p id="{{ $mapId }}-live" class="text-sm text-slate-700 mt-3 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2">Checking the driver's location...</p>
        @endif

        <p class="text-xs text-slate-500 mt-3">
            Green is the pickup point and red is the delivery point.
            @if ($liveUrl) Blue is the driver. @endif
            The dashed line is a straight line between pickup and delivery, not the road route.
        </p>

        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        <script>
            (function () {
                var pickup   = @json([(float) $pLat, (float) $pLng]);
                var delivery = @json([(float) $dLat, (float) $dLng]);

                var map = L.map(@json($mapId), { scrollWheelZoom: false });

                L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 18,
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                }).addTo(map);

                L.circleMarker(pickup, {
                    radius: 9, color: '#166534', weight: 2, fillColor: '#22c55e', fillOpacity: 1
                }).addTo(map).bindTooltip('Pickup', { permanent: true, direction: 'top' });

                L.circleMarker(delivery, {
                    radius: 9, color: '#991b1b', weight: 2, fillColor: '#ef4444', fillOpacity: 1
                }).addTo(map).bindTooltip('Delivery', { permanent: true, direction: 'top' });

                L.polyline([pickup, delivery], { color: '#2563eb', weight: 3, dashArray: '6 6' }).addTo(map);

                map.fitBounds(L.latLngBounds([pickup, delivery]), { padding: [50, 50] });

                @if ($liveUrl)
                    var liveUrl      = @json($liveUrl);
                    var liveEl       = document.getElementById(@json($mapId . '-live'));
                    var driverMarker = null;
                    var fitted       = false;
                    var timer        = null;
                    var finished     = ['Delivered', 'Closed', 'Cancelled', 'Delivery Failed'];

                    var say = function (text) {
                        if (liveEl) { liveEl.textContent = text; }
                    };

                    var ago = function (seconds) {
                        if (seconds < 60) { return seconds + ' seconds ago'; }
                        var minutes = Math.round(seconds / 60);
                        return minutes + (minutes === 1 ? ' minute ago' : ' minutes ago');
                    };

                    var refresh = function () {
                        if (document.hidden) { return; }

                        fetch(liveUrl, {
                            headers: { 'Accept': 'application/json' },
                            credentials: 'same-origin',
                            cache: 'no-store'
                        })
                        .then(function (response) { return response.ok ? response.json() : null; })
                        .then(function (data) {
                            if (!data) { return; }

                            if (!data.active) {
                                if (driverMarker) { map.removeLayer(driverMarker); driverMarker = null; }

                                if (finished.indexOf(data.status) !== -1) {
                                    say('This shipment is no longer on the road.');
                                    clearInterval(timer);
                                } else if (data.status === 'Picked Up' || data.status === 'In Transit') {
                                    say('The driver is not sharing a location right now.');
                                } else {
                                    say('The live location starts after the driver picks up the shipment.');
                                }
                                return;
                            }

                            var position = [data.lat, data.lng];

                            if (!driverMarker) {
                                driverMarker = L.circleMarker(position, {
                                    radius: 10, color: '#1e3a8a', weight: 3, fillColor: '#3b82f6', fillOpacity: 1
                                }).addTo(map).bindTooltip('Driver', { permanent: true, direction: 'top' });
                            } else {
                                driverMarker.setLatLng(position);
                            }

                            if (!fitted) {
                                map.fitBounds(L.latLngBounds([pickup, delivery, position]), { padding: [50, 50] });
                                fitted = true;
                            }

                            if (data.seconds_ago > 300) {
                                say('Last known driver location, updated ' + ago(data.seconds_ago) + '. The driver may be offline.');
                            } else {
                                say('Driver location updated ' + ago(data.seconds_ago) + '.');
                            }
                        })
                        .catch(function () {});
                    };

                    refresh();
                    timer = setInterval(refresh, 10000);
                @endif
            })();
        </script>
    @else
        <p class="text-sm text-slate-500">The map is not available for this shipment. It was booked before maps were added.</p>
    @endif
</div>