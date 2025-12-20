<div class="min-h-screen flex items-center justify-center px-4 py-12">
    <div class="w-full max-w-2xl">
        <div class="text-center mb-8">
            <h2 class="text-3xl font-bold">
                <span class="text-yellow-400">Create</span> <span class="text-cyan-400">New Group</span>
            </h2>
            <p class="text-gray-400 mt-2">Start a new community around your interests</p>
        </div>

        <div class="relative p-8 glass-card">
            <div class="top-accent-center"></div>

            @if (session()->has('message'))
                <div class="mb-6 p-4 rounded-xl bg-green-500/20 border border-green-500/50 flex items-center gap-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2m4-4l-4 4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="text-green-200">{{ session('message') }}</span>
                </div>
            @endif

            @if (session()->has('error'))
                <div class="mb-6 p-4 rounded-xl bg-red-500/20 border border-red-500/50 flex items-center gap-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="text-red-200">{{ session('error') }}</span>
                </div>
            @endif

            <form wire:submit.prevent="createGroup">
                <div class="mb-4">
                    <label for="name" class="block text-gray-300 text-sm font-semibold mb-2">Group Name</label>
                    <input type="text" id="name" wire:model.live="name"
                           class="w-full px-4 py-3 bg-slate-800/50 border border-white/10 rounded-2xl focus:border-cyan-500/50 focus:outline-none transition text-white"
                           placeholder="e.g., Local Hiking Enthusiasts">
                    @error('name') <span class="text-red-400 text-xs mt-1">{{ $message }}</span> @enderror
                </div>

                <div class="mb-4">
                    <label for="description" class="block text-gray-300 text-sm font-semibold mb-2">Description</label>
                    <textarea id="description" wire:model.live="description"
                              class="w-full px-4 py-3 bg-slate-800/50 border border-white/10 rounded-2xl focus:border-cyan-500/50 focus:outline-none transition text-white"
                              rows="4" placeholder="Tell us what your group is about..."></textarea>
                    @error('description') <span class="text-red-400 text-xs mt-1">{{ $message }}</span> @enderror
                </div>

                <div class="mb-4">
                    <label for="privacy" class="block text-gray-300 text-sm font-semibold mb-2">Privacy</label>
                    <select id="privacy" wire:model="privacy"
                            class="w-full px-4 py-3 bg-slate-800/50 border border-white/10 rounded-2xl focus:border-cyan-500/50 focus:outline-none transition text-white">
                        <option value="public">Public (Anyone can see and join)</option>
                        <option value="private">Private (Only members can see content, join by invitation or approval)</option>
                    </select>
                    @error('privacy') <span class="text-red-400 text-xs mt-1">{{ $message }}</span> @enderror
                </div>

                <!-- Dynamic Tags Input (type-and-enter) -->
                <div class="mb-4">
                    <label class="block text-gray-300 text-sm font-semibold mb-2">Tags (Optional)</label>
                    <div class="flex gap-2 mb-2">
                        <input type="text"
                               id="tag-input"
                               wire:model="newTag"
                               wire:keydown.enter.prevent="addTag"
                               placeholder="Type a tag and press Enter"
                               class="flex-1 px-4 py-3 bg-slate-800/50 border border-white/10 rounded-2xl focus:border-cyan-500/50 focus:outline-none transition text-white" />
                        <button type="button"
                                wire:click="addTag"
                                class="px-4 py-3 bg-gradient-to-r from-pink-500 to-purple-500 rounded-xl font-semibold hover:scale-105 transition-all">
                            Add
                        </button>
                    </div>
                    @if(count($tags) > 0)
                        <div class="flex flex-wrap gap-2">
                            @foreach($tags as $index => $tag)
                                <span class="px-3 py-1 bg-purple-500/20 text-purple-300 rounded-full text-sm flex items-center gap-2">
                                    {{ $tag }}
                                    <button type="button" wire:click="removeTag({{ $index }})" class="text-purple-300 hover:text-white transition">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </span>
                            @endforeach
                        </div>
                    @endif
                    <p class="text-xs text-gray-500 mt-1">{{ count($tags) }}/10 tags</p>
                    @error('tags') <span class="text-red-400 text-xs mt-1">{{ $message }}</span> @enderror
                </div>

                <!-- Location with Geocoding (Required) -->
                <div class="mb-4">
                    <label class="block text-gray-300 text-sm font-semibold mb-2">Location <span class="text-red-400">*</span></label>
                    <div class="relative" wire:ignore>
                        <input type="text"
                               id="group-location-input"
                               value="{{ $location_name }}"
                               placeholder="Search for a city..."
                               class="w-full px-4 py-3 bg-slate-800/50 border border-white/10 rounded-2xl focus:border-cyan-500/50 focus:outline-none transition text-white"
                               autocomplete="off" />
                        <button type="button"
                                onclick="getGroupCurrentLocation()"
                                class="absolute right-3 top-1/2 -translate-y-1/2 p-2 text-gray-500 hover:text-cyan-500 transition"
                                title="Use my current location">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                        </button>
                    </div>
                    @if($location_name)
                        <p class="text-xs text-cyan-400 mt-1 flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Location set: {{ $location_name }}
                        </p>
                    @else
                        <p class="text-xs text-gray-500 mt-1">Start typing to search for a location</p>
                    @endif
                    @error('location_name') <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                    @error('latitude') <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <!-- Avatar Image -->
                <div class="mb-4">
                    <label class="block text-gray-300 text-sm font-semibold mb-2">Group Avatar (Optional)</label>
                    <input type="file" wire:model="avatarImage" accept="image/*"
                        class="w-full text-gray-300 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:bg-purple-500/20 file:text-purple-300 hover:file:bg-purple-500/30 file:transition">
                    @if($avatarImage)
                        <div class="mt-3 flex items-center gap-3">
                            <img src="{{ $avatarImage->temporaryUrl() }}" alt="Avatar preview" class="w-16 h-16 rounded-xl object-cover border border-purple-500/50">
                            <p class="text-green-400 text-sm">Preview: {{ $avatarImage->getClientOriginalName() }}</p>
                        </div>
                    @endif
                    @error('avatarImage') <span class="text-red-400 text-xs mt-1">{{ $message }}</span> @enderror
                </div>

                <!-- Cover Image -->
                <div class="mb-6">
                    <label class="block text-gray-300 text-sm font-semibold mb-2">Cover Image (Optional)</label>
                    <input type="file" wire:model="coverImage" accept="image/*"
                        class="w-full text-gray-300 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:bg-purple-500/20 file:text-purple-300 hover:file:bg-purple-500/30 file:transition">
                    @if($coverImage)
                        <div class="mt-3">
                            <img src="{{ $coverImage->temporaryUrl() }}" alt="Cover preview" class="w-full h-32 rounded-xl object-cover border border-purple-500/50">
                            <p class="text-green-400 text-sm mt-2">Preview: {{ $coverImage->getClientOriginalName() }}</p>
                        </div>
                    @endif
                    @error('coverImage') <span class="text-red-400 text-xs mt-1">{{ $message }}</span> @enderror
                </div>

                <div>
                    <button type="submit" class="w-full px-6 py-3 bg-gradient-to-r from-pink-500 to-purple-500 rounded-xl font-semibold hover:scale-105 transition-all">
                        Create Group
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
        script.src = `https://maps.googleapis.com/maps/api/js?key={{ config('services.google.places_api_key') }}&libraries=places&loading=async`;
        script.async = true;
        script.defer = true;
        document.head.appendChild(script);

        script.onload = () => {
            initializeGroupAutocomplete();
        };
    } else {
        initializeGroupAutocomplete();
    }

    function initializeGroupAutocomplete() {
        const input = document.getElementById('group-location-input');
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

    // Handle current location request for groups
    window.getGroupCurrentLocation = function() {
        if (!navigator.geolocation) {
            alert('Geolocation is not supported by your browser');
            return;
        }

        navigator.geolocation.getCurrentPosition(
            (position) => {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                const input = document.getElementById('group-location-input');
                const component = Livewire.find(input.closest('[wire\\:id]').getAttribute('wire:id'));

                // Reverse geocode to get location name
                const geocoder = new google.maps.Geocoder();
                geocoder.geocode(
                    { location: { lat, lng } },
                    (results, status) => {
                        if (status === 'OK' && results[0]) {
                            input.value = results[0].formatted_address;
                            component.call('setLocationData', results[0].formatted_address, lat, lng);
                        } else {
                            input.value = 'Current Location';
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
