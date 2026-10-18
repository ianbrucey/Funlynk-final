<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class GeocodingService
{
    private const ENDPOINT = 'https://maps.googleapis.com/maps/api/geocode/json';

    /**
     * Turn typed text (city, ZIP, address) into coordinates via the Google Geocoding API.
     *
     * @return array{name: string, lat: float, lng: float}|null null when nothing was found or the lookup failed
     */
    public function geocode(string $query): ?array
    {
        $query = trim($query);
        $key = config('services.google.places_api_key');

        if (mb_strlen($query) < 3 || ! $key) {
            return null;
        }

        $cacheKey = 'geocode:'.md5(mb_strtolower($query));

        if (is_array($cached = Cache::get($cacheKey))) {
            return $cached;
        }

        try {
            $response = Http::timeout(5)->get(self::ENDPOINT, [
                'address' => $query,
                'key' => $key,
            ]);
        } catch (\Throwable $e) {
            report($e);

            return null;
        }

        if (! $response->successful() || $response->json('status') !== 'OK') {
            return null;
        }

        $result = $response->json('results.0');
        $lat = data_get($result, 'geometry.location.lat');
        $lng = data_get($result, 'geometry.location.lng');

        if ($lat === null || $lng === null) {
            return null;
        }

        $place = [
            'name' => (string) ($result['formatted_address'] ?? $query),
            'lat' => (float) $lat,
            'lng' => (float) $lng,
        ];

        Cache::put($cacheKey, $place, now()->addDays(30));

        return $place;
    }
}
