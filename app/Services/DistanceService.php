<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class DistanceService
{
    private const ORS_URL       = 'https://api.openrouteservice.org';
    private const NOMINATIM_URL = 'https://nominatim.openstreetmap.org/search';
    private const OSRM_URL      = 'https://router.project-osrm.org/route/v1/driving';

    // Error code that means: "OpenRouteService cannot be used now, try the free fallback"
    private const USE_FALLBACK = 9001;

    // Returns the road distance in km between two addresses
    public function distanceKm(string $from, string $to): float
    {
        return $this->route($from, $to)['km'];
    }

    // Returns the road distance and the coordinates of both addresses
    public function route(string $from, string $to): array
    {
        $key = trim((string) config('services.openrouteservice.key'));

        // 1. OpenRouteService (needs a valid key)
        if ($key !== '') {
            try {
                return $this->viaOrs($from, $to, $key);
            } catch (RuntimeException $e) {
                if ($e->getCode() !== self::USE_FALLBACK) {
                    throw $e;   // for example "Address not found": a real problem with the input
                }

                Log::warning('OpenRouteService not usable (' . $e->getMessage() . '). Using the OpenStreetMap fallback.');
            }
        } else {
            Log::info('No ORS_API_KEY set. Using the OpenStreetMap fallback.');
        }

        // 2. Free fallback: Nominatim (address search) + OSRM (road distance)
        return $this->viaOsm($from, $to);
    }

    // ------------------------------------------------------------------
    // OpenRouteService
    // ------------------------------------------------------------------

    private function viaOrs(string $from, string $to, string $key): array
    {
        $start = $this->orsGeocode($from, $key); // [longitude, latitude]
        $end   = $this->orsGeocode($to, $key);

        try {
            $response = $this->orsClient($key)->post(self::ORS_URL . '/v2/directions/driving-car', [
                'coordinates' => [$start, $end],
                'radiuses'    => [-1, -1], // accept the nearest road, however far
            ]);
        } catch (ConnectionException $e) {
            throw new RuntimeException('OpenRouteService is not reachable.', self::USE_FALLBACK);
        }

        if ($this->shouldFallBack($response)) {
            throw new RuntimeException('OpenRouteService answered HTTP ' . $response->status() . '.', self::USE_FALLBACK);
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
        try {
            $response = $this->orsClient($key)->get(self::ORS_URL . '/geocode/search', [
                'text'             => $address,
                'size'             => 1,
                'boundary.country' => 'PK', // search inside Pakistan only; delete this line to search worldwide
            ]);
        } catch (ConnectionException $e) {
            throw new RuntimeException('OpenRouteService is not reachable.', self::USE_FALLBACK);
        }

        if ($this->shouldFallBack($response)) {
            throw new RuntimeException('OpenRouteService answered HTTP ' . $response->status() . '.', self::USE_FALLBACK);
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

    // A wrong key (401, 403), the daily limit (429) or a server problem (5xx): use the fallback
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
    // Free fallback: OpenStreetMap Nominatim + OSRM (no key needed)
    // ------------------------------------------------------------------

    private function viaOsm(string $from, string $to): array
    {
        $start = $this->osmGeocode($from); // [longitude, latitude]

        usleep(1100000);                   // the Nominatim rule: at most 1 request per second

        $end = $this->osmGeocode($to);

        try {
            $response = $this->osmClient()->get(
                self::OSRM_URL . "/{$start[0]},{$start[1]};{$end[0]},{$end[1]}",
                ['overview' => 'false']
            );
        } catch (ConnectionException $e) {
            Log::warning('OSRM connection error: ' . $e->getMessage());

            throw new RuntimeException('Could not reach the map service. Check the internet connection and try again.');
        }

        if ($response->failed()) {
            Log::warning('OSRM failed: HTTP ' . $response->status() . ' ' . mb_substr($response->body(), 0, 200));

            throw new RuntimeException('The map service could not find a road route between these two addresses.');
        }

        $meters = data_get($response->json(), 'routes.0.distance');

        if ($meters === null) {
            throw new RuntimeException('The distance could not be calculated. Please check both addresses.');
        }

        return $this->result($meters, $start, $end);
    }

    private function osmGeocode(string $address): array
    {
        try {
            $response = $this->osmClient()->get(self::NOMINATIM_URL, [
                'q'            => $address,
                'format'       => 'jsonv2',
                'limit'        => 1,
                'countrycodes' => 'pk', // search inside Pakistan only; delete this line to search worldwide
            ]);
        } catch (ConnectionException $e) {
            Log::warning('Nominatim connection error: ' . $e->getMessage());

            throw new RuntimeException('Could not reach the map service. Check the internet connection and try again.');
        }

        if ($response->failed()) {
            Log::warning('Nominatim failed: HTTP ' . $response->status() . ' ' . mb_substr($response->body(), 0, 200));

            throw new RuntimeException('The address search failed (HTTP ' . $response->status() . '). Please try again in a minute.');
        }

        $lon = data_get($response->json(), '0.lon');
        $lat = data_get($response->json(), '0.lat');

        if ($lon === null || $lat === null) {
            throw new RuntimeException("Address not found: \"{$address}\". Add the city name and try again.");
        }

        return [(float) $lon, (float) $lat];
    }

    // Nominatim requires a clear User-Agent
    private function osmClient(): PendingRequest
    {
        return Http::withHeaders(['User-Agent' => 'FreightLink-FYP-GCUF/1.0 (student project)'])
            ->withOptions(['verify' => filter_var(config('services.openrouteservice.verify', true), FILTER_VALIDATE_BOOLEAN)])
            ->timeout(20)
            ->acceptJson();
    }

    // ------------------------------------------------------------------

    private function result(float|int|string $meters, array $start, array $end): array
    {
        return [
            'km'           => round(((float) $meters) / 1000, 2),
            'pickup_lat'   => round((float) $start[1], 7),
            'pickup_lng'   => round((float) $start[0], 7),
            'delivery_lat' => round((float) $end[1], 7),
            'delivery_lng' => round((float) $end[0], 7),
        ];
    }
}