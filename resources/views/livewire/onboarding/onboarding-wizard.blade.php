<div class="container mx-auto lg:px-4 lg:py-12 flex items-center justify-center min-h-screen">
    <div class="card w-full max-w-3xl glass-card lg:rounded-xl shadow-2xl relative overflow-hidden">
        <div class="top-accent-center"></div>

        <div class="card-body p-6 sm:p-8 lg:p-10">
            <!-- Progress Indicator -->
            <div class="mb-8">
                <div class="flex items-center justify-center gap-2">
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-full {{ $currentStep >= 1 ? 'bg-gradient-to-r from-pink-500 to-purple-500' : 'bg-slate-700' }} flex items-center justify-center text-white font-semibold">
                            1
                        </div>
                        <span class="ml-2 text-sm {{ $currentStep >= 1 ? 'text-white' : 'text-gray-500' }}">Location</span>
                    </div>
                    <div class="w-16 h-1 {{ $currentStep >= 2 ? 'bg-gradient-to-r from-pink-500 to-purple-500' : 'bg-slate-700' }}"></div>
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-full {{ $currentStep >= 2 ? 'bg-gradient-to-r from-pink-500 to-purple-500' : 'bg-slate-700' }} flex items-center justify-center text-white font-semibold">
                            2
                        </div>
                        <span class="ml-2 text-sm {{ $currentStep >= 2 ? 'text-white' : 'text-gray-500' }}">Interests</span>
                    </div>
                </div>
            </div>

            @if($currentStep === 1)
                <!-- Step 1: Location -->
                <div class="space-y-6">
                    <div class="text-center mb-8">
                        <div class="inline-block p-4 bg-gradient-to-r from-cyan-500/20 to-blue-500/20 rounded-2xl mb-4">
                            <svg class="w-12 h-12 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <h2 class="text-3xl font-bold text-white mb-3">Where are you located?</h2>
                        <p class="text-gray-400 text-lg max-w-xl mx-auto">
                            FunLynk connects you with activities near you. We need your location to show relevant posts and events in your area.
                        </p>
                    </div>

                    <!-- Location Input -->
                    <div class="relative" wire:ignore>
                        <input
                            type="text"
                            id="onboarding-location-input"
                            value="{{ $location_name }}"
                            placeholder="Search for your city..."
                            class="input input-bordered w-full bg-white text-gray-900 border-white/10 focus:border-cyan-500 focus:outline-none placeholder-gray-400 text-lg py-6"
                            autocomplete="off"
                        />

                        <button
                            type="button"
                            onclick="getCurrentLocationOnboarding()"
                            class="absolute right-3 top-1/2 -translate-y-1/2 p-2 text-gray-500 hover:text-cyan-500 transition"
                            title="Use my current location">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                        </button>
                    </div>

                    @if($location_name)
                        <p class="text-center text-cyan-400 flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Location set: {{ $location_name }}
                        </p>
                    @endif

                    @error('location_name') <p class="text-red-400 text-sm text-center">{{ $message }}</p> @enderror
                    @error('latitude') <p class="text-red-400 text-sm text-center">{{ $message }}</p> @enderror
                    @error('longitude') <p class="text-red-400 text-sm text-center">{{ $message }}</p> @enderror

                    <!-- Continue Button -->
                    <div class="flex justify-end mt-8">
                        <button 
                            wire:click="nextStep"
                            class="btn btn-lg bg-gradient-to-r from-pink-500 to-purple-500 border-none text-white px-8 hover:scale-105 transition-transform"
                            {{ !$location_name || !$latitude || !$longitude ? 'disabled' : '' }}>
                            Continue
                            <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                            </svg>
                        </button>
                    </div>
                </div>

            @elseif($currentStep === 2)
                <!-- Step 2: Interests -->
                <div class="space-y-6">
                    <!-- TODO: FIGURE OUT HOW TO MAKE THIS WORK WITHOUT THIS STUPID H1 TAG -->
                    <h1></h1> 
                    <div class="text-center mb-8">
                        <div class="inline-block p-4 bg-gradient-to-r from-purple-500/20 to-pink-500/20 rounded-2xl mb-4">
                            <svg class="w-12 h-12 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                            </svg>
                        </div>
                        <h2 class="text-3xl font-bold text-white mb-3">What are you interested in?</h2>
                        <p class="text-gray-400 text-lg max-w-xl mx-auto">
                            Help us personalize your feed by selecting activities you enjoy. You can always change these later.
                        </p>
                    </div>

                    <!-- Interest Input -->
                    <div class="flex gap-2">
                        <input type="text"
                               id="interest-input-onboarding"
                               wire:model="newInterest"
                               wire:keydown.enter.prevent="addInterest"
                               x-on:keydown.enter="$nextTick(() => $el.value = '')"
                               placeholder="Add interest (Enter)"
                               class="input input-bordered w-full bg-white text-gray-900 border-white/10 focus:border-cyan-500 focus:outline-none placeholder-gray-400" />
                        <button type="button"
                                wire:click="addInterest"
                                x-on:click="$nextTick(() => document.getElementById('interest-input-onboarding').value = '')"
                                class="btn bg-gradient-to-r from-pink-500 to-purple-500 border-none text-white">Add</button>
                    </div>

                    @if(count($interests) > 0)
                        <div class="flex flex-wrap gap-2 p-4 bg-slate-800/30 rounded-xl border border-white/5">
                            @foreach($interests as $index => $interest)
                                <div class="badge badge-lg gap-2 bg-purple-500/20 text-purple-300 border-purple-500/30 p-3">
                                    {{ $interest }}
                                    <button type="button" wire:click="removeInterest({{ $index }})" class="hover:text-white">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="inline-block w-4 h-4 stroke-current">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-center text-gray-500 py-4">No interests added yet. Add some to personalize your experience!</p>
                    @endif

                    <p class="text-center text-sm text-gray-500">{{ count($interests) }}/10 interests</p>
                    @error('interests') <p class="text-red-400 text-sm text-center">{{ $message }}</p> @enderror

                    <!-- Buttons -->
                    <div class="flex justify-between mt-8">
                        <button 
                            wire:click="previousStep"
                            class="btn btn-ghost text-gray-400 hover:text-white hover:bg-white/10">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"/>
                            </svg>
                            Back
                        </button>
                        <button 
                            wire:click="complete"
                            class="btn btn-lg bg-gradient-to-r from-pink-500 to-purple-500 border-none text-white px-8 hover:scale-105 transition-transform">
                            <span wire:loading.remove wire:target="complete">Complete Setup</span>
                            <span wire:loading wire:target="complete">Completing...</span>
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<script>
// Prevent multiple initializations
if (!window.onboardingLocationInitialized) {
    window.onboardingLocationInitialized = true;

    // Wait for both Livewire and DOM to be ready
    function initOnboardingLocation() {
        // Load Google Places API only once
        if (!window.google || !window.google.maps || !window.google.maps.places) {
            // Check if script is already being loaded
            if (document.querySelector('script[src*="maps.googleapis.com"]')) {
                // Wait for it to load
                const checkGoogle = setInterval(() => {
                    if (window.google && window.google.maps && window.google.maps.places) {
                        clearInterval(checkGoogle);
                        initializeOnboardingAutocomplete();
                    }
                }, 100);
                return;
            }

            const script = document.createElement('script');
            script.src = `https://maps.googleapis.com/maps/api/js?key={{ config('services.google.places_api_key') }}&libraries=places`;
            script.async = true;
            script.defer = true;
            document.head.appendChild(script);

            script.onload = () => {
                initializeOnboardingAutocomplete();
            };
        } else {
            initializeOnboardingAutocomplete();
        }

        function initializeOnboardingAutocomplete() {
        const input = document.getElementById('onboarding-location-input');
        if (!input) return;

        const autocomplete = new google.maps.places.Autocomplete(input, {
            types: ['(cities)'],
            fields: ['formatted_address', 'geometry', 'name']
        });

        autocomplete.addListener('place_changed', () => {
            const place = autocomplete.getPlace();

            if (!place.geometry) return;

            const lat = place.geometry.location.lat();
            const lng = place.geometry.location.lng();
            const name = place.formatted_address || place.name;

            // Get Livewire component instance
            const component = Livewire.find(input.closest('[wire\\:id]').getAttribute('wire:id'));
            if (component) {
                // Update input visually
                input.value = name;
                // Update Livewire
                component.call('setLocationData', name, lat, lng);
            }
        });
    }

    // Handle current location request
    window.getCurrentLocationOnboarding = function() {
        if (!navigator.geolocation) {
            alert('Geolocation is not supported by your browser');
            return;
        }

        navigator.geolocation.getCurrentPosition(
            (position) => {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                const input = document.getElementById('onboarding-location-input');
                const component = Livewire.find(input.closest('[wire\\:id]').getAttribute('wire:id'));

                if (!component) {
                    alert('Unable to connect to the form. Please refresh the page.');
                    return;
                }

                // Reverse geocode to get location name
                const geocoder = new google.maps.Geocoder();
                geocoder.geocode(
                    { location: { lat, lng } },
                    (results, status) => {
                        if (status === 'OK' && results[0]) {
                            // Update input visually
                            input.value = results[0].formatted_address;
                            // Update Livewire
                            component.call('setLocationData', results[0].formatted_address, lat, lng);
                        } else {
                            // Update input visually
                            input.value = 'Current Location';
                            // Update Livewire
                            component.call('setLocationData', 'Current Location', lat, lng);
                        }
                    }
                );
            },
            (error) => {
                alert('Unable to get your location. Please check your browser permissions.');
            }
        );
    };
    }

    // Try multiple initialization methods to ensure it runs
    if (typeof Livewire !== 'undefined') {
        // Livewire already loaded
        initOnboardingLocation();
    } else {
        // Wait for Livewire to load
        document.addEventListener('livewire:init', initOnboardingLocation);
        // Fallback to DOMContentLoaded
        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(initOnboardingLocation, 100);
        });
    }
}
</script>
