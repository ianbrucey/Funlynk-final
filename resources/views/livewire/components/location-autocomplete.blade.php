<div>
    <label class="label pb-3"><span class="label-text text-gray-300 font-medium">{{ $label }}</span></label>

    <div class="relative">
        <input
            type="text"
            id="{{ $inputId }}"
            wire:model="locationName"
            placeholder="{{ $placeholder }}"
            class="input input-bordered w-full bg-white text-gray-900 border-white/10 focus:border-cyan-500 focus:outline-none placeholder-gray-400"
            autocomplete="off"
        />

        @if($showCurrentLocationButton)
            <button
                type="button"
                wire:click="$dispatch('getCurrentLocation')"
                class="absolute right-2 top-1/2 -translate-y-1/2 p-2 text-gray-500 hover:text-cyan-500 transition"
                title="Use my current location">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
            </button>
        @endif
    </div>

    <p class="text-xs text-gray-500 mt-1">Start typing to search for a location</p>
</div>

@script
<script>
    // Load Google Places API if not already loaded
    if (!window.google || !window.google.maps || !window.google.maps.places) {
        const script = document.createElement('script');
        script.src = `https://maps.googleapis.com/maps/api/js?key={{ config('services.google.places_api_key') }}&libraries=places`;
        script.async = true;
        script.defer = true;
        document.head.appendChild(script);

        script.onload = () => {
            initializeAutocomplete();
        };
    } else {
        initializeAutocomplete();
    }

    function initializeAutocomplete() {
        const input = document.getElementById('{{ $inputId }}');
        if (!input) return;

        const autocomplete = new google.maps.places.Autocomplete(input, {
            types: ['(cities)'],
            fields: ['formatted_address', 'geometry', 'name']
        });

        autocomplete.addListener('place_changed', () => {
            const place = autocomplete.getPlace();

            if (!place.geometry) {
                return;
            }

            const lat = place.geometry.location.lat();
            const lng = place.geometry.location.lng();
            const name = place.formatted_address || place.name;

            $wire.setLocation(name, lat, lng);
        });
    }

    // Handle current location request
    $wire.on('getCurrentLocation', () => {
        if (!navigator.geolocation) {
            alert('Geolocation is not supported by your browser');
            return;
        }

        navigator.geolocation.getCurrentPosition(
            (position) => {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;

                // Reverse geocode to get location name
                const geocoder = new google.maps.Geocoder();
                geocoder.geocode(
                    { location: { lat, lng } },
                    (results, status) => {
                        if (status === 'OK' && results[0]) {
                            $wire.setLocation(results[0].formatted_address, lat, lng);
                        } else {
                            $wire.setLocation('Current Location', lat, lng);
                        }
                    }
                );
            },
            (error) => {
                alert('Unable to get your location. Please check your browser permissions.');
            }
        );
    });
</script>
@endscript
