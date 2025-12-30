<div class="container mx-auto lg:px-4 lg:py-12 flex items-center justify-center min-h-screen">
    <div class="card w-full max-w-3xl glass-card lg:rounded-xl shadow-2xl relative overflow-hidden">
        <div class="top-accent-center"></div>

        {{-- Logout Button --}}
        <div class="absolute top-4 right-4 z-10">
            <button
                wire:click="logout"
                wire:confirm="Are you sure you want to log out? Your progress will be saved."
                class="px-4 py-2 bg-slate-800/50 border border-white/10 rounded-xl hover:border-red-500/50 hover:bg-red-500/10 transition text-gray-300 hover:text-red-400 flex items-center gap-2"
                title="Log out">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
                <span class="hidden sm:inline">Log out</span>
            </button>
        </div>

        <div class="card-body p-6 sm:p-8 lg:p-10">
            <!-- Progress Indicator: Location → Photo → Interests -->
            <div class="mb-8">
                <div class="flex items-center justify-center gap-2">
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-full {{ $currentStep >= 1 ? 'bg-gradient-to-r from-pink-500 to-purple-500' : 'bg-slate-700' }} flex items-center justify-center text-white font-semibold">
                            1
                        </div>
                        <span class="ml-2 text-sm {{ $currentStep >= 1 ? 'text-white' : 'text-gray-500' }}">Location</span>
                    </div>
                    <div class="w-12 h-1 {{ $currentStep >= 2 ? 'bg-gradient-to-r from-pink-500 to-purple-500' : 'bg-slate-700' }}"></div>
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-full {{ $currentStep >= 2 ? 'bg-gradient-to-r from-pink-500 to-purple-500' : 'bg-slate-700' }} flex items-center justify-center text-white font-semibold">
                            2
                        </div>
                        <span class="ml-2 text-sm {{ $currentStep >= 2 ? 'text-white' : 'text-gray-500' }}">Photo</span>
                    </div>
                    <div class="w-12 h-1 {{ $currentStep >= 3 ? 'bg-gradient-to-r from-pink-500 to-purple-500' : 'bg-slate-700' }}"></div>
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-full {{ $currentStep >= 3 ? 'bg-gradient-to-r from-pink-500 to-purple-500' : 'bg-slate-700' }} flex items-center justify-center text-white font-semibold">
                            3
                        </div>
                        <span class="ml-2 text-sm {{ $currentStep >= 3 ? 'text-white' : 'text-gray-500' }}">Interests</span>
                    </div>
                </div>
            </div>

            @if($currentStep === 1)
                <!-- Step 1: Location (FIRST - Google Maps loads immediately on page load) -->
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

                    <!-- Location Input (NO wire:ignore - this is step 1, always visible on page load) -->
                    <div class="relative">
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
                            wire:click="nextStepFromLocation"
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
                <!-- Step 2: Profile Picture -->
                <div class="space-y-6">
                    <div class="text-center mb-8">
                        <div class="inline-block p-4 bg-gradient-to-r from-pink-500/20 to-purple-500/20 rounded-2xl mb-4">
                            <svg class="w-12 h-12 text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </div>
                        <h2 class="text-3xl font-bold text-white mb-3">Add your profile picture</h2>
                        <p class="text-gray-400 text-lg max-w-xl mx-auto">
                            Help others recognize you by uploading a profile picture. This is required to continue.
                        </p>
                        <p class="text-gray-500 text-sm mt-2">
                            Maximum file size: 6MB
                        </p>
                    </div>

                    <!-- Profile Picture Upload -->
                    <div class="flex flex-col items-center gap-6">
                        @if($profileImage)
                            {{-- New image being uploaded --}}
                            <div class="relative">
                                <img src="{{ $profileImage->temporaryUrl() }}"
                                     alt="Profile preview"
                                     class="w-40 h-40 rounded-full object-cover ring-4 ring-cyan-500/50">
                                <button type="button"
                                        wire:click="$set('profileImage', null)"
                                        class="absolute -top-2 -right-2 p-2 bg-red-500 rounded-full text-white hover:bg-red-600 transition">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                        @elseif($uploadedImagePath)
                            {{-- Previously uploaded image --}}
                            <div class="relative">
                                <img src="{{ Storage::disk('s3')->url($uploadedImagePath) }}"
                                     alt="Profile preview"
                                     class="w-40 h-40 rounded-full object-cover ring-4 ring-green-500/50">
                                <div class="absolute -bottom-2 left-1/2 -translate-x-1/2 px-3 py-1 bg-green-500 rounded-full text-white text-xs font-semibold">
                                    ✓ Saved
                                </div>
                            </div>
                        @else
                            <div class="w-40 h-40 rounded-full bg-slate-800/50 border-2 border-dashed border-white/20 flex items-center justify-center">
                                <svg class="w-16 h-16 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                            </div>
                        @endif

                        <div class="w-full max-w-md">
                            <label for="profile-image-upload"
                                   class="block w-full px-6 py-4 {{ $uploadedImagePath ? 'bg-slate-700 hover:bg-slate-600' : 'bg-gradient-to-r from-pink-500 to-purple-500' }} rounded-xl font-semibold text-white text-center cursor-pointer hover:scale-105 transition-all">
                                <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                @if($profileImage)
                                    Change Photo
                                @elseif($uploadedImagePath)
                                    Change Photo
                                @else
                                    Upload Photo
                                @endif
                            </label>
                            <input type="file"
                                   id="profile-image-upload"
                                   wire:model="profileImage"
                                   accept="image/*"
                                   class="hidden">
                        </div>

                        <div wire:loading wire:target="profileImage" class="text-cyan-400 flex items-center gap-2">
                            <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Uploading...
                        </div>

                        @error('profileImage')
                            <p class="text-red-400 text-sm">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Navigation Buttons -->
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
                            wire:click="nextStepFromPhoto"
                            wire:loading.attr="disabled"
                            class="btn btn-lg bg-gradient-to-r from-pink-500 to-purple-500 border-none text-white px-8 hover:scale-105 transition-transform"
                            {{ (!$profileImage && !$uploadedImagePath) ? 'disabled' : '' }}>
                            <span wire:loading.remove wire:target="nextStepFromPhoto">Continue</span>
                            <span wire:loading wire:target="nextStepFromPhoto">Saving...</span>
                            <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                            </svg>
                        </button>
                    </div>
                </div>

            @elseif($currentStep === 3)
                <!-- Step 3: Interests -->
                <div class="space-y-6">
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
                               placeholder="Add interest (Enter)"
                               class="input input-bordered w-full bg-white text-gray-900 border-white/10 focus:border-cyan-500 focus:outline-none placeholder-gray-400" />
                        <button type="button"
                                wire:click="addInterest"
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
// Google Places Autocomplete for Onboarding
// Since location is now Step 1, autocomplete initializes on page load
(function() {
    let autocompleteInstance = null;

    // Initialize Google Places Autocomplete
    function initAutocomplete() {
        const input = document.getElementById('onboarding-location-input');
        if (!input) return;

        // Check if Google Maps is loaded
        if (!window.google || !window.google.maps || !window.google.maps.places) {
            setTimeout(initAutocomplete, 300);
            return;
        }

        // Prevent duplicate initialization
        if (autocompleteInstance) return;

        autocompleteInstance = new google.maps.places.Autocomplete(input, {
            types: ['(cities)'],
            fields: ['formatted_address', 'geometry', 'name']
        });

        autocompleteInstance.addListener('place_changed', () => {
            const place = autocompleteInstance.getPlace();
            if (!place.geometry) return;

            const lat = place.geometry.location.lat();
            const lng = place.geometry.location.lng();
            const name = place.formatted_address || place.name;

            // Find Livewire component and update
            const wireId = input.closest('[wire\\:id]')?.getAttribute('wire:id');
            const component = wireId ? Livewire.find(wireId) : null;

            if (component) {
                input.value = name;
                component.call('setLocationData', name, lat, lng);
            }
        });
    }

    // Get current location using browser geolocation
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
                if (!input) return;

                const wireId = input.closest('[wire\\:id]')?.getAttribute('wire:id');
                const component = wireId ? Livewire.find(wireId) : null;
                if (!component) return;

                // Reverse geocode to get location name
                const geocoder = new google.maps.Geocoder();
                geocoder.geocode({ location: { lat, lng } }, (results, status) => {
                    const address = (status === 'OK' && results[0])
                        ? results[0].formatted_address
                        : 'Current Location';
                    input.value = address;
                    component.call('setLocationData', address, lat, lng);
                });
            },
            () => alert('Unable to get your location. Please check browser permissions.')
        );
    };

    // Load Google Maps API and initialize
    function loadGoogleMaps() {
        if (window.google?.maps?.places) {
            initAutocomplete();
            return;
        }

        if (document.querySelector('script[src*="maps.googleapis.com"]')) {
            // Script already loading, wait for it
            const checkLoaded = setInterval(() => {
                if (window.google?.maps?.places) {
                    clearInterval(checkLoaded);
                    initAutocomplete();
                }
            }, 200);
            return;
        }

        const script = document.createElement('script');
        script.src = `https://maps.googleapis.com/maps/api/js?key={{ config('services.google.places_api_key') }}&libraries=places`;
        script.async = true;
        script.defer = true;
        script.onload = initAutocomplete;
        document.head.appendChild(script);
    }

    // Start loading on DOMContentLoaded
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', loadGoogleMaps);
    } else {
        loadGoogleMaps();
    }
})();
</script>
