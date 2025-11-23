<div class="container mx-auto lg:px-4 lg:py-12 flex items-center justify-center">
    <div class="card w-full max-w-2xl glass-card lg:rounded-xl shadow-2xl relative overflow-hidden">
        <div class="top-accent-center"></div>

        <div class="card-body p-6 sm:p-8 lg:p-10">
            <h2 class="card-title text-3xl font-bold mb-2 text-white">Edit Profile</h2>
            <p class="text-gray-400 mb-8">Update your personal information and preferences.</p>

            <!-- Success Message -->
            @if (session()->has('message'))
                <div role="alert" class="alert alert-success mb-6 bg-green-500/10 border-green-500/20 text-green-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <span>{{ session('message') }}</span>
                </div>
            @endif

            <form wire:submit="save" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Profile Photo (Custom UI preserved as requested) -->
                <div class="form-control md:col-span-2">
                    <label class="label pb-3"><span class="label-text text-gray-300 font-medium">Profile Photo</span></label>
                    <div class="flex flex-col sm:flex-row items-center gap-6 bg-slate-800/30 p-6 rounded-2xl border border-white/5">
                        <div class="avatar">
                            <div class="w-16 rounded-full ring ring-primary ring-offset-base-100 ring-offset-2">
                                @if ($profile_image)
                                    <img src="{{ $profile_image->temporaryUrl() }}" alt="Preview" />
                                @elseif ($current_profile_image_url)
                                    <img src="{{ Storage::url($current_profile_image_url) }}" alt="Current" />
                                @else
                                    <div class="w-full h-full bg-gradient-to-br from-pink-500 to-purple-600 flex items-center justify-center">
                                        <span class="text-xl font-bold text-white">{{ substr($display_name ?? 'U', 0, 1) }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="flex flex-col gap-3 w-full sm:w-auto">
                            <div class="flex gap-3">
                                <label for="profile-image-input" class="btn btn-sm btn-primary bg-gradient-to-r from-pink-500 to-purple-500 border-none text-white">
                                    Change Photo
                                </label>
                                <input type="file" wire:model="profile_image" accept="image/*" class="hidden" id="profile-image-input">
                                
                                @if ($current_profile_image_url)
                                    <button type="button" wire:click="removeProfileImage" class="btn btn-sm btn-outline btn-error">
                                        Remove
                                    </button>
                                @endif
                            </div>
                            <div class="text-xs text-gray-500">
                                JPG, PNG or WebP. Max 2MB.
                                @error('profile_image') <span class="text-red-400 block mt-1">{{ $message }}</span> @enderror
                            </div>
                            <div wire:loading wire:target="profile_image" class="text-xs text-cyan-400 animate-pulse">
                                Uploading...
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Username -->
                <div class="form-control md:col-span-2">
                    <label class="label pb-3"><span class="label-text text-gray-300 font-medium">Username</span></label>
                    <div class="relative">
                        <input type="text" wire:model.live.debounce.300ms="username" placeholder="e.g. cosmic_explorer"
                               class="input input-bordered w-full bg-white text-gray-900 border-white/10 focus:border-cyan-500 focus:outline-none placeholder-gray-400 pr-10" />
                        @if($usernameAvailable === true)
                            <svg class="absolute right-3 top-1/2 -translate-y-1/2 w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        @elseif($usernameAvailable === false)
                            <svg class="absolute right-3 top-1/2 -translate-y-1/2 w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        @endif
                    </div>
                    @if($usernameAvailable === true)
                        <span class="text-green-400 text-xs mt-1">✓ Username is available</span>
                    @elseif($usernameAvailable === false)
                        <span class="text-red-400 text-xs mt-1">✗ Username is already taken</span>
                    @else
                        <span class="text-gray-500 text-xs mt-1">Your profile URL: funlynk.com/u/{{ $username }}</span>
                    @endif
                    @error('username') <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <!-- Display Name -->
                <div class="form-control md:col-span-2">
                    <label class="label pb-3"><span class="label-text text-gray-300 font-medium">Display Name</span></label>
                    <input type="text" wire:model="display_name" placeholder="e.g. CosmicExplorer"
                           class="input input-bordered w-full bg-white text-gray-900 border-white/10 focus:border-cyan-500 focus:outline-none placeholder-gray-400" />
                    @error('display_name') <span class="text-red-400 text-xs mt-1">{{ $message }}</span> @enderror
                </div>

                <!-- Bio -->
                <div class="form-control md:col-span-2">
                    <label class="block text-gray-300 font-medium mb-3">Bio</label>
                    <textarea wire:model="bio" class="textarea textarea-bordered h-28 bg-white text-gray-900 border-white/10 focus:border-cyan-500 focus:outline-none placeholder-gray-400"
                              placeholder="Tell us about yourself..."></textarea>
                    <div class="flex justify-between items-center mt-1">
                        <span class="text-xs text-gray-500">{{ strlen($bio ?? '') }}/500</span>
                        @error('bio') <span class="text-xs text-red-400">{{ $message }}</span> @enderror
                    </div>
                </div>

                <!-- Interests -->
                <div class="form-control md:col-span-2">
                    <label class="label pb-3"><span class="label-text text-gray-300 font-medium">Interests</span></label>
                    <div class="flex gap-2 mb-3">
                        <input type="text"
                               id="interest-input"
                               wire:model="newInterest"
                               wire:keydown.enter.prevent="addInterest"
                               x-on:keydown.enter="$nextTick(() => $el.value = '')"
                               placeholder="Add interest (Enter)"
                               class="input input-bordered w-full bg-white text-gray-900 border-white/10 focus:border-cyan-500 focus:outline-none placeholder-gray-400" />
                        <button type="button"
                                wire:click="addInterest"
                                x-on:click="$nextTick(() => document.getElementById('interest-input').value = '')"
                                class="btn btn-primary bg-gradient-to-r from-pink-500 to-purple-500 border-none text-white">Add</button>
                    </div>
                    
                    @if(count($interests) > 0)
                        <div class="flex flex-wrap gap-2">
                            @foreach($interests as $index => $interest)
                                <div class="badge badge-lg gap-2 bg-purple-500/20 text-purple-300 border-purple-500/30 p-3">
                                    {{ $interest }}
                                    <button type="button" wire:click="removeInterest({{ $index }})" class="hover:text-white">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="inline-block w-4 h-4 stroke-current"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @endif
                    <div class="label">
                        <span class="label-text-alt text-gray-500">{{ count($interests) }}/10 interests</span>
                        @error('interests') <span class="label-text-alt text-red-400">{{ $message }}</span> @enderror
                    </div>
                </div>

                <!-- Location -->
                <div class="md:col-span-2">
                    <div class="form-control">
                        <label class="label pb-3"><span class="label-text text-gray-300 font-medium">Location</span></label>

                        <div class="relative" wire:ignore>
                            <input
                                type="text"
                                id="location-autocomplete-input"
                                value="{{ $location_name }}"
                                placeholder="Search for your city..."
                                class="input input-bordered w-full bg-white text-gray-900 border-white/10 focus:border-cyan-500 focus:outline-none placeholder-gray-400"
                                autocomplete="off"
                            />

                            <button
                                type="button"
                                onclick="getCurrentLocation()"
                                class="absolute right-2 top-1/2 -translate-y-1/2 p-2 text-gray-500 hover:text-cyan-500 transition"
                                title="Use my current location">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                            </button>
                        </div>

                        <p class="text-xs text-gray-500 mt-1">Start typing to search for a location</p>
                        @error('location_name') <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>

                <!-- Buttons -->
                <div class="md:col-span-2 flex justify-end gap-4 mt-6">
                    <a href="{{ route('profile.show') }}" class="btn btn-ghost text-gray-400 hover:text-white hover:bg-white/10">Cancel</a>
                    <button type="submit" class="btn btn-primary bg-gradient-to-r from-pink-500 to-purple-500 border-none text-white shadow-lg shadow-purple-500/20 hover:scale-105 transition-transform">
                        <span wire:loading.remove wire:target="save">Save Changes</span>
                        <span wire:loading wire:target="save">Saving...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('livewire:init', () => {
    // Load Google Places API
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
        const input = document.getElementById('location-autocomplete-input');
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
            component.call('setLocationData', name, lat, lng);
        });
    }

    // Handle current location request
    window.getCurrentLocation = function() {
        if (!navigator.geolocation) {
            alert('Geolocation is not supported by your browser');
            return;
        }

        navigator.geolocation.getCurrentPosition(
            (position) => {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                const input = document.getElementById('location-autocomplete-input');
                const component = Livewire.find(input.closest('[wire\\:id]').getAttribute('wire:id'));

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
});
</script>