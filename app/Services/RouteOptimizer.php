<?php

namespace App\Services;

use Illuminate\Support\Collection;

class RouteOptimizer
{
    // $start = [lat, lng] or null
    public function optimize(Collection $shipments, ?array $start = null): array
    {
        $stops   = [];
        $skipped = [];

        foreach ($shipments as $s) {
            // A shipment without saved map points (booked before Step 23) cannot be placed
            if ($s->delivery_lat === null || $s->delivery_lng === null) {
                $skipped[] = $s->tracking_id;
                continue;
            }

            $pickupIndex = null;

            // Not picked up yet: the driver must visit the pickup point first
            if ($s->status === 'Assigned' && $s->pickup_lat !== null && $s->pickup_lng !== null) {
                $pickupIndex = count($stops);
                $stops[] = [
                    'shipment_id' => $s->id,
                    'tracking_id' => $s->tracking_id,
                    'type'        => 'Pickup',
                    'address'     => $s->pickup_address,
                    'lat'         => (float) $s->pickup_lat,
                    'lng'         => (float) $s->pickup_lng,
                    'requires'    => null,
                ];
            }

            $stops[] = [
                'shipment_id' => $s->id,
                'tracking_id' => $s->tracking_id,
                'type'        => 'Delivery',
                'address'     => $s->delivery_address,
                'lat'         => (float) $s->delivery_lat,
                'lng'         => (float) $s->delivery_lng,
                'requires'    => $pickupIndex,   // index of the pickup stop that must come first
            ];
        }

        $count = count($stops);

        if ($count === 0) {
            return [
                'stops' => [], 'total_km' => 0, 'original_km' => 0,
                'saved_km' => 0, 'start' => $start, 'skipped' => $skipped,
            ];
        }

        // The order the stops would be visited in without optimization
        $original   = range(0, $count - 1);
        $originalKm = $this->length($stops, $original, $start);

        // Without a start point, try every possible first stop and keep the shortest route
        $firsts = $start !== null
            ? [null]
            : array_keys(array_filter($stops, fn ($stop) => $stop['requires'] === null));

        $bestOrder = $original;
        $bestKm    = $originalKm;

        foreach ($firsts as $first) {
            $order = $this->nearest($stops, $start, $first);
            $order = $this->improve($stops, $order, $start);
            $km    = $this->length($stops, $order, $start);

            if ($km + 0.000001 < $bestKm) {
                $bestKm    = $km;
                $bestOrder = $order;
            }
        }

        // Build the result with the distance of every leg
        $result = [];
        $prev   = $start;

        foreach ($bestOrder as $n => $i) {
            $stop = $stops[$i];
            $leg  = $prev !== null ? $this->km($prev, [$stop['lat'], $stop['lng']]) : 0.0;

            $result[] = [
                'step'        => $n + 1,
                'shipment_id' => $stop['shipment_id'],
                'tracking_id' => $stop['tracking_id'],
                'type'        => $stop['type'],
                'address'     => $stop['address'],
                'lat'         => $stop['lat'],
                'lng'         => $stop['lng'],
                'leg_km'      => round($leg, 1),
            ];

            $prev = [$stop['lat'], $stop['lng']];
        }

        return [
            'stops'       => $result,
            'total_km'    => round($bestKm, 1),
            'original_km' => round($originalKm, 1),
            'saved_km'    => max(0, round($originalKm - $bestKm, 1)),
            'start'       => $start,
            'skipped'     => $skipped,
        ];
    }

    // Nearest-stop method: always go to the closest stop that is allowed next
    private function nearest(array $stops, ?array $start, ?int $first): array
    {
        $order   = [];
        $visited = [];
        $current = $start;

        if ($first !== null) {
            $order[]        = $first;
            $visited[$first] = true;
            $current        = [$stops[$first]['lat'], $stops[$first]['lng']];
        }

        while (count($order) < count($stops)) {
            $bestIndex = null;
            $bestDist  = INF;

            foreach ($stops as $i => $stop) {
                if (isset($visited[$i])) {
                    continue;
                }

                // A delivery is allowed only after its pickup
                if ($stop['requires'] !== null && ! isset($visited[$stop['requires']])) {
                    continue;
                }

                $dist = $current !== null ? $this->km($current, [$stop['lat'], $stop['lng']]) : 0.0;

                if ($dist < $bestDist) {
                    $bestDist  = $dist;
                    $bestIndex = $i;
                }
            }

            $order[]              = $bestIndex;
            $visited[$bestIndex]  = true;
            $current              = [$stops[$bestIndex]['lat'], $stops[$bestIndex]['lng']];
        }

        return $order;
    }

    // 2-opt: reverse a part of the route when that makes it shorter and the order is still allowed
    private function improve(array $stops, array $order, ?array $start): array
    {
        $best  = $this->length($stops, $order, $start);
        $n     = count($order);
        $round = 0;

        do {
            $improved = false;
            $round++;

            for ($i = 0; $i < $n - 1; $i++) {
                for ($j = $i + 1; $j < $n; $j++) {
                    $candidate = array_merge(
                        array_slice($order, 0, $i),
                        array_reverse(array_slice($order, $i, $j - $i + 1)),
                        array_slice($order, $j + 1)
                    );

                    if (! $this->valid($stops, $candidate)) {
                        continue;
                    }

                    $len = $this->length($stops, $candidate, $start);

                    if ($len + 0.000001 < $best) {
                        $best     = $len;
                        $order    = $candidate;
                        $improved = true;
                    }
                }
            }
        } while ($improved && $round < 50);

        return $order;
    }

    // Every delivery must come after its own pickup
    private function valid(array $stops, array $order): bool
    {
        $position = array_flip($order);

        foreach ($stops as $i => $stop) {
            if ($stop['requires'] !== null && $position[$i] < $position[$stop['requires']]) {
                return false;
            }
        }

        return true;
    }

    // Total distance of a route in km (from the start point, if there is one)
    private function length(array $stops, array $order, ?array $start): float
    {
        $total = 0.0;
        $prev  = $start;

        foreach ($order as $i) {
            $point = [$stops[$i]['lat'], $stops[$i]['lng']];

            if ($prev !== null) {
                $total += $this->km($prev, $point);
            }

            $prev = $point;
        }

        return $total;
    }

    // Straight-line distance between two points (haversine formula)
    private function km(array $a, array $b): float
    {
        $earth = 6371.0;
        $dLat  = deg2rad($b[0] - $a[0]);
        $dLng  = deg2rad($b[1] - $a[1]);

        $h = sin($dLat / 2) ** 2
           + cos(deg2rad($a[0])) * cos(deg2rad($b[0])) * sin($dLng / 2) ** 2;

        return $earth * 2 * asin(min(1, sqrt($h)));
    }
}