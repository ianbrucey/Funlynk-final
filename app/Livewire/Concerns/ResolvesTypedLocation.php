<?php

namespace App\Livewire\Concerns;

use App\Services\GeocodingService;
use Illuminate\Validation\ValidationException;

trait ResolvesTypedLocation
{
    /**
     * Fallback for when Places autocomplete gave no coordinates: geocode the typed text on the server.
     *
     * @throws ValidationException when the typed text cannot be located
     */
    protected function resolveTypedLocation(): void
    {
        if (filled($this->latitude) && filled($this->longitude)) {
            return;
        }

        if (blank($this->location_name)) {
            return;
        }

        $place = app(GeocodingService::class)->geocode((string) $this->location_name);

        if ($place === null) {
            throw ValidationException::withMessages([
                'location_name' => "We couldn't find that place. Try a city name or ZIP code.",
            ]);
        }

        $this->setLocationData($place['name'], $place['lat'], $place['lng']);
    }
}
