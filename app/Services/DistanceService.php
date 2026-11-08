<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class DistanceService
{
    private const ORS_URL = 'https://api.openrouteservice.org';

    private const NOMINATIM_URL = 'https://nominatim.openstreetmap.org/search';

    private const OSRM_URL = 'https://router.project-osrm.org/route/v1/driving';

    // Error code that means: "OpenRouteService cannot be used now, try the free fallback"
    private const USE_FALLBACK = 9001;

    /** Common Pakistani city typos & informal names -> correct name */
    private array $fixes = [
        'faislabad' => 'faisalabad',
        'faisalbad' => 'faisalabad',
        'lahor' => 'lahore',
        'islamabd' => 'islamabad',
        'rawalpindi' => 'rawalpindi',
        'pindi' => 'rawalpindi',
        'multan' => 'multan',
        'peshwar' => 'peshawar',
        'quetta' => 'quetta',
        'karchi' => 'karachi',
        'hydrabad' => 'hyderabad',
        'gujrawala' => 'gujranwala',
        'sialkot' => 'sialkot',
        'sargoda' => 'sargodha',
        'bhawalpur' => 'bahawalpur',
        'kasoor' => 'kasur',
        'shekhupura' => 'sheikhupura',
        'rahimyar' => 'rahim yar khan',
        'ry khan' => 'rahim yar khan',
        'dg khan' => 'dera ghazi khan',
        'muzafarabad' => 'muzaffarabad',
    ];

    /** Built-in coordinates for major Pakistani cities [lat, lon] */
    private array $cities = [
        'karachi' => [24.8607, 67.0011],
        'lahore' => [31.5204, 74.3587],
        'islamabad' => [33.6844, 73.0479],
        'rawalpindi' => [33.5651, 73.0169],
        'faisalabad' => [31.4504, 73.1350],
        'multan' => [30.1575, 71.5249],
        'peshawar' => [34.0151, 71.5249],
        'quetta' => [30.1798, 66.9750],
        'hyderabad' => [25.3960, 68.3578],
        'gujranwala' => [32.1877, 74.1945],
        'sialkot' => [32.4945, 74.5229],
        'bahawalpur' => [29.3956, 71.6836],
        'sargodha' => [32.0836, 72.6711],
        'sukkur' => [27.7052, 68.8574],
        'larkana' => [27.5590, 68.2123],
        'sheikhupura' => [31.7167, 73.9850],
        'jhang' => [31.2681, 72.3181],
        'rahim yar khan' => [28.4202, 70.2952],
        'gujrat' => [32.5742, 74.0754],
        'mardan' => [34.1986, 72.0404],
        'abbottabad' => [34.1688, 73.2215],
        'kasur' => [31.1187, 74.4500],
        'okara' => [30.8081, 73.4458],
        'sahiwal' => [30.6682, 73.1114],
        'narowal' => [32.1012, 74.8730],
        'mirpur' => [33.1478, 73.7519],
        'muzaffarabad' => [34.3700, 73.4711],
        'gwadar' => [25.1216, 62.3254],
        'dera ghazi khan' => [30.0489, 70.6455],
        'mianwali' => [32.5839, 71.5370],
        'chiniot' => [31.7292, 72.9822],
        'burewala' => [30.1667, 72.6833],
        'kamoke' => [31.9744, 74.2244],
        'hafizabad' => [32.0719, 73.6883],
        'daska' => [32.3242, 74.3503],
    ];

    // Returns the road distance in km between two addresses
    public function distanceKm(string $from, string $to): float
    {
        return $this->route($from, $to)['km'];
    }

    // Returns the road distance and coordinates of both addresses
    public function route(string $from, string $to): array
    {
        $key = trim((string) config('services.openrouteservice.key'));

        // 1. OpenRouteService (if a valid key is provided)
        if ($key !== '') {
            try {
                return $this->viaOrs($from, $to, $key);
            } catch (RuntimeException $e) {
                if ($e->getCode() !== self::USE_FALLBACK) {
                    throw $e;
                }

                Log::warning('OpenRouteService not usable ('.$e->getMessage().'). Using OpenStreetMap fallback.');
            }
        }

        // 2. Fallback: Local database lookup + Cached Nominatim + OSRM / Haversine road estimation
        return $this->viaOsm($from, $to);
    }

    // ------------------------------------------------------------------
    // OpenRouteService
    // ------------------------------------------------------------------

    private function viaOrs(string $from, string $to, string $key): array
    {
        $start = $this->orsGeocode($from, $key); // [lon, lat]
        $end = $this->orsGeocode($to, $key);

        try {
            $response = $this->orsClient($key)->post(self::ORS_URL.'/v2/directions/driving-car', [
                'coordinates' => [$start, $end],
                'radiuses' => [-1, -1],
            ]);
        } catch (ConnectionException $e) {
            throw new RuntimeException('OpenRouteService is not reachable.', self::USE_FALLBACK);
        }

        if ($this->shouldFallBack($response)) {
            throw new RuntimeException('OpenRouteService answered HTTP '.$response->status().'.', self::USE_FALLBACK);
        }

        if ($response->failed()) {
            throw new RuntimeException('The map service could not find a road route between these two addresses.');
        }

        $meters = data_get($response->json(), 'routes.0.summary.distance');

        if ($meters === null) {
            throw new RuntimeException('The distance could not be calculated. Please check both addresses.');
        }

        return $this->result($meters, $start, $end);
    }

    private function orsGeocode(string $address, string $key): array
    {
        // Check built-in cities first to avoid API limits
        $clean = $this->clean($address);
        foreach ($this->cities as $name => $coords) {
            if (str_contains($clean, $name)) {
                return [(float) $coords[1], (float) $coords[0]]; // [lon, lat]
            }
        }

        try {
            $response = $this->orsClient($key)->get(self::ORS_URL.'/geocode/search', [
                'text' => $address,
                'size' => 1,
                'boundary.country' => 'PK',
            ]);
        } catch (ConnectionException $e) {
            throw new RuntimeException('OpenRouteService is not reachable.', self::USE_FALLBACK);
        }

        if ($this->shouldFallBack($response)) {
            throw new RuntimeException('OpenRouteService answered HTTP '.$response->status().'.', self::USE_FALLBACK);
        }

        if ($response->failed()) {
            throw new RuntimeException('The address search failed. Please try again.');
        }

        $coordinates = data_get($response->json(), 'features.0.geometry.coordinates');

        if (! $coordinates) {
            throw new RuntimeException("Address not found: \"{$address}\". Add the city name and try again.");
        }

        return $coordinates;
    }

    private function shouldFallBack(Response $response): bool
    {
        $status = $response->status();

        return $status === 401 || $status === 403 || $status === 429 || $status >= 500;
    }

    private function orsClient(string $key): PendingRequest
    {
        return Http::withHeaders(['Authorization' => $key])
            ->withOptions(['verify' => filter_var(config('services.openrouteservice.verify', true), FILTER_VALIDATE_BOOLEAN)])
            ->timeout(20)
            ->acceptJson();
    }

    // ------------------------------------------------------------------
    // Free fallback: Built-in Cities + Nominatim + OSRM + Haversine fallback
    // ------------------------------------------------------------------

    private function viaOsm(string $from, string $to): array
    {
        $start = $this->osmGeocode($from); // [lon, lat]
        $end = $this->osmGeocode($to);   // [lon, lat]

        // 1) Try OSRM driving route
        try {
            $response = $this->osmClient()->get(
                self::OSRM_URL."/{$start[0]},{$start[1]};{$end[0]},{$end[1]}",
                ['overview' => 'false']
            );

            if ($response->ok() && $response->json('code') === 'Ok') {
                $meters = data_get($response->json(), 'routes.0.distance');
                if ($meters !== null && (float) $meters > 0) {
                    return $this->result($meters, $start, $end);
                }
            }
        } catch (Throwable $e) {
            Log::warning('OSRM route failed: '.$e->getMessage());
        }

        // 2) Fallback: Straight line Haversine distance * 1.3 road factor
        $straightKm = $this->haversine($start, $end);
        $estRoadKm = max(1.0, round($straightKm * 1.3, 2));

        return [
            'km' => $estRoadKm,
            'pickup_lat' => round((float) $start[1], 7),
            'pickup_lng' => round((float) $start[0], 7),
            'delivery_lat' => round((float) $end[1], 7),
            'delivery_lng' => round((float) $end[0], 7),
        ];
    }

    private function osmGeocode(string $address): array
    {
        $clean = $this->clean($address);

        if ($clean === '') {
            throw new RuntimeException('Please enter an address.');
        }

        // 1) Instant lookup from built-in major cities (no network call, works offline)
        foreach ($this->cities as $name => $coords) {
            if (str_contains($clean, $name)) {
                return [(float) $coords[1], (float) $coords[0]]; // [lon, lat]
            }
        }

        // 2) Ask Nominatim with 30-day cache
        $cached = Cache::remember('geo:'.md5($clean), now()->addDays(30), function () use ($clean) {
            try {
                $response = $this->osmClient()->get(self::NOMINATIM_URL, [
                    'q' => $clean,
                    'format' => 'jsonv2',
                    'limit' => 1,
                    'countrycodes' => 'pk',
                ]);

                if ($response->ok()) {
                    $lon = data_get($response->json(), '0.lon');
                    $lat = data_get($response->json(), '0.lat');

                    if ($lon !== null && $lat !== null) {
                        return [(float) $lon, (float) $lat];
                    }
                }
            } catch (Throwable $e) {
                Log::warning('Nominatim geocoding failed: '.$e->getMessage());
            }

            return null;
        });

        if ($cached !== null) {
            return $cached;
        }

        throw new RuntimeException("Address not found: \"{$address}\". Check the spelling or enter a city name.");
    }

    /** Clean address, fix common Pakistani city misspellings, squash spaces */
    public function clean(string $address): string
    {
        $text = mb_strtolower(trim($address));
        $text = (string) preg_replace('/\bpakistan\b/i', '', $text);
        $text = (string) preg_replace('/[,\.\-\/\\\]+/', ' ', $text);
        $text = (string) preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        foreach ($this->fixes as $wrong => $right) {
            $text = (string) preg_replace('/\b'.preg_quote($wrong, '/').'\b/i', $right, $text);
        }

        return $text;
    }

    /** Great circle distance between two points in km */
    private function haversine(array $startLonLat, array $endLonLat): float
    {
        $earth = 6371; // Earth's radius in km
        $lat1 = deg2rad($startLonLat[1]);
        $lon1 = deg2rad($startLonLat[0]);
        $lat2 = deg2rad($endLonLat[1]);
        $lon2 = deg2rad($endLonLat[0]);

        $dLat = $lat2 - $lat1;
        $dLon = $lon2 - $lon1;

        $h = sin($dLat / 2) ** 2 + cos($lat1) * cos($lat2) * sin($dLon / 2) ** 2;

        return 2 * $earth * asin(sqrt($h));
    }

    private function osmClient(): PendingRequest
    {
        return Http::withHeaders(['User-Agent' => 'FreightLink-FYP-GCUF/1.0 (student project)'])
            ->withOptions(['verify' => filter_var(config('services.openrouteservice.verify', true), FILTER_VALIDATE_BOOLEAN)])
            ->timeout(15)
            ->acceptJson();
    }

    private function result(float|int|string $meters, array $start, array $end): array
    {
        return [
            'km' => round(((float) $meters) / 1000, 2),
            'pickup_lat' => round((float) $start[1], 7),
            'pickup_lng' => round((float) $start[0], 7),
            'delivery_lat' => round((float) $end[1], 7),
            'delivery_lng' => round((float) $end[0], 7),
        ];
    }
}
