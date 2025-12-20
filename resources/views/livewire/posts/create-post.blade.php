<div class="min-h-screen lg:py-12">
    <div class="container mx-auto lg:px-6">
        {{-- Header --}}
        <div class="mb-6 lg:mb-8 text-center px-6 lg:px-0 pt-6 lg:pt-0">
            <h1 class="text-4xl font-bold mb-2">
                <span class="gradient-text">Quick Post</span>
            </h1>
            <p class="text-gray-400">Share what you're up to right now</p>
        </div>

        {{-- Flash Messages --}}
        @if (session()->has('success'))
            <div class="mb-6 mx-4 lg:mx-0 p-4 bg-green-500/20 border border-green-500/50 rounded-xl text-green-300">
                {{ session('success') }}
            </div>
        @endif

        @if (session()->has('error'))
            <div class="mb-6 mx-4 lg:mx-0 p-4 bg-red-500/20 border border-red-500/50 rounded-xl text-red-300">
                {{ session('error') }}
            </div>
        @endif

        <form wire:submit="createPost" class="max-w-3xl mx-auto space-y-6 lg:space-y-6">
            {{-- Required Fields --}}
            <div class="relative p-6 lg:p-8 glass-card">
                <div class="top-accent-center"></div>

                <h2 class="text-xl font-bold mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Required Information
                </h2>

                <div class="space-y-4">
                    {{-- Title --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-300 mb-2">What's happening (or ask around)? *</label>
                        <input
                            type="text"
                            wire:model="title"
                            placeholder="e.g., Coffee at Starbucks in 30 mins, Anyone want to play basketball?"
                            class="w-full px-4 py-3 bg-slate-800/50 border border-white/10 rounded-2xl focus:border-pink-500/50 focus:outline-none transition text-white"
                            autofocus
                        />
                        @error('title') <span class="text-red-400 text-sm mt-1">{{ $message }}</span> @enderror
                    </div>

                    {{-- Location --}}
                    <div class="form-control">
                        <label class="block text-sm font-semibold text-gray-300 mb-2">Location * <span class="text-xs text-gray-400 font-normal">(city or zip is fine)</span></label>

                        <div class="relative" wire:ignore>
                            <input
                                type="text"
                                id="location-autocomplete-input"
                                value="{{ $location_name }}"
                                placeholder="Search for a location..."
                                class="w-full px-4 py-3 bg-slate-800/50 border border-white/10 rounded-2xl focus:border-pink-500/50 focus:outline-none transition text-white"
                                autocomplete="off"
                            />

                            <button
                                type="button"
                                onclick="getCurrentLocation()"
                                class="absolute right-2 top-1/2 -translate-y-1/2 p-2 text-gray-500 hover:text-pink-500 transition"
                                title="Use my current location">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                            </button>
                        </div>

                        <p class="text-xs text-gray-500 mt-1">Start typing to search for a location</p>
                        @error('location_name') <span class="text-red-400 text-sm mt-1 block">{{ $message }}</span> @enderror
                        @error('latitude') <span class="text-red-400 text-sm mt-1 block">Please select a valid location from the list</span> @enderror
                    </div>
                </div>
            </div>

            {{-- Optional Details --}}
            <div class="relative p-6 lg:p-8 glass-card">
                <div class="top-accent-center"></div>

                <h2 class="text-xl font-bold mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Optional Details
                </h2>

                <div class="space-y-4">
                    {{-- Description --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-300 mb-2">Details</label>
                        <textarea
                            wire:model="description"
                            rows="3"
                            placeholder="Add more context..."
                            class="w-full px-4 py-3 bg-slate-800/50 border border-white/10 rounded-2xl focus:border-pink-500/50 focus:outline-none transition text-white"
                        ></textarea>
                        <p class="text-xs text-gray-400 mt-1">{{ strlen($description ?? '') }}/500 characters</p>
                        @error('description') <span class="text-red-400 text-sm mt-1">{{ $message }}</span> @enderror
                    </div>

                    {{-- Time Hint --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-300 mb-2">When?</label>
                        <input
                            type="text"
                            wire:model="time_hint"
                            placeholder="e.g., In 30 mins, Tonight at 8pm, Tomorrow afternoon"
                            class="w-full px-4 py-3 bg-slate-800/50 border border-white/10 rounded-2xl focus:border-pink-500/50 focus:outline-none transition text-white"
                        />
                        <p class="text-xs text-gray-400 mt-1">Keep it casual - no need for exact times</p>
                        @error('time_hint') <span class="text-red-400 text-sm mt-1">{{ $message }}</span> @enderror
                    </div>

                    {{-- Mood --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-300 mb-2">Vibe</label>
                        <select
                            wire:model="mood"
                            class="w-full px-4 py-3 bg-slate-800/50 border border-white/10 rounded-2xl focus:border-pink-500/50 focus:outline-none transition text-white"
                        >
                            <option value="">Select a vibe...</option>
                            <option value="creative">✨ Creative</option>
                            <option value="social">🎉 Social</option>
                            <option value="active">⚡ Active</option>
                            <option value="chill">😌 Chill</option>
                            <option value="adventurous">🚀 Adventurous</option>
                        </select>
                        @error('mood') <span class="text-red-400 text-sm mt-1">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>

            {{-- Tags & Settings --}}
            <div class="relative p-6 lg:p-8 glass-card">
                <div class="top-accent-center"></div>

                <h2 class="text-xl font-bold mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                    </svg>
                    Tags & Duration
                </h2>

                <div class="space-y-4">
                    {{-- Tags --}}
                    <div class="form-control">
                        <label class="block text-sm font-semibold text-gray-300 mb-2">Tags (Optional)</label>
                        <div class="flex gap-2 mb-3">
                            <input type="text"
                                   id="tag-input"
                                   wire:model.live="newTag"
                                   wire:keydown.enter.prevent="addTag"
                                   x-on:keydown.enter="$nextTick(() => $el.value = '')"
                                   placeholder="Add tag (Enter)"
                                   class="w-full px-4 py-3 bg-slate-800/50 border border-white/10 rounded-2xl focus:border-pink-500/50 focus:outline-none transition text-white" />
                            <button type="button"
                                    wire:click="addTag"
                                    x-on:click="$nextTick(() => document.getElementById('tag-input').value = '')"
                                    class="px-6 py-3 bg-gradient-to-r from-pink-500 to-purple-500 rounded-xl font-semibold hover:scale-105 transition-all text-white">Add</button>
                        </div>

                        @if(count($selectedTags) > 0)
                            <div class="flex flex-wrap gap-2">
                                @foreach($selectedTags as $index => $tag)
                                    <div class="badge badge-lg gap-2 bg-pink-500/20 text-pink-300 border-pink-500/30 p-3 rounded-lg flex items-center">
                                        {{ $tag['name'] }}
                                        <button type="button" wire:click="removeTag({{ $index }})" class="hover:text-white ml-2">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="w-4 h-4 stroke-current"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                        <div class="label mt-2">
                            <span class="text-xs text-gray-500">{{ count($selectedTags) }}/5 tags</span>
                            @error('selectedTags') <span class="text-xs text-red-400 ml-2">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    {{-- TTL --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-300 mb-2">Post Duration</label>
                        <select
                            wire:model="ttl_hours"
                            class="w-full px-4 py-3 bg-slate-800/50 border border-white/10 rounded-2xl focus:border-pink-500/50 focus:outline-none transition text-white"
                        >
                            <option value="24">24 hours</option>
                            <option value="48">48 hours (recommended)</option>
                            <option value="72">72 hours</option>
                        </select>
                        <p class="text-xs text-gray-400 mt-1">Posts automatically expire after this time</p>
                        @error('ttl_hours') <span class="text-red-400 text-sm mt-1">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>

            {{-- Submit Button --}}
            <div class="flex gap-4 justify-center pb-6">
                <a
                    href="{{ route('feed.nearby') }}"
                    class="px-8 py-4 bg-slate-800/50 border border-white/10 rounded-xl hover:border-pink-500/50 transition font-semibold"
                >
                    Cancel
                </a>
                <button
                    type="submit"
                    class="px-8 py-4 bg-gradient-to-r from-pink-500 to-purple-500 rounded-xl font-semibold hover:scale-105 transition-all shadow-lg"
                >
                    Post Now
                </button>
            </div>
        </form>
    </div>

    <style>
        .gradient-text {
            background: linear-gradient(to right, #ec4899, #a855f7);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .top-accent-center {
            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 8rem;
            height: 0.25rem;
            background: linear-gradient(to right, transparent, #ec4899, transparent);
        }
    </style>

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
                types: ['establishment', 'geocode'],
                fields: ['formatted_address', 'geometry', 'name']
            });

            autocomplete.addListener('place_changed', () => {
                const place = autocomplete.getPlace();

                if (!place.geometry) return;

                const lat = place.geometry.location.lat();
                const lng = place.geometry.location.lng();
                const name = place.name || place.formatted_address;

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
</div>
