<?php

namespace App\Livewire\Concerns;

use App\Services\GeocodingService;
use Illuminate\Validation\ValidationException;

trait ResolvesTypedLocation
{
    /**
     * Fallback for when Places autocomplete gave no coordinates: geocode the typed text on the server.
     * Works with both location_name and locationName components.
     *
     * @throws ValidationException when the typed text cannot be located
     */
    protected function resolveTypedLocation(bool $required = true): void
    {
        $nameProperty = property_exists($this, 'locationName') ? 'locationName' : 'location_name';

        if (filled($this->latitude) && filled($this->longitude)) {
            return;
        }

        if (blank($this->{$nameProperty})) {
            return;
        }

        $place = app(GeocodingService::class)->geocode((string) $this->{$nameProperty});

        if ($place === null) {
            if (! $required) {
                return;
            }

            throw ValidationException::withMessages([
                $nameProperty => "We couldn't find that place. Try a city name or ZIP code.",
            ]);
        }

        $this->setLocationData($place['name'], $place['lat'], $place['lng']);
    }
}
