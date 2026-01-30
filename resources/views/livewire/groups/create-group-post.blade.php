<div>
    @if($showModal)
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-end justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <!-- Background overlay -->
            <div class="fixed inset-0 transition-opacity bg-gray-900 bg-opacity-75" wire:click="closeModal"></div>

            <!-- Modal content -->
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="relative inline-block px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform rounded-lg shadow-xl glass-card sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                <div class="top-accent-center"></div>

                {{-- Header with user avatar --}}
                <div class="flex items-center gap-3 mb-4">
                    <img src="{{ auth()->user()->avatar_url ?? 'https://ui-avatars.com/api/?name=' . urlencode(auth()->user()->display_name ?? auth()->user()->username) }}"
                         alt="Your avatar"
                         class="w-10 h-10 rounded-full border-2 border-purple-500/50">
                    <div>
                        <p class="font-semibold text-white">{{ auth()->user()->display_name ?? auth()->user()->username }}</p>
                        <p class="text-xs text-gray-400">Posting to {{ $group->name }}</p>
                    </div>
                </div>

                <form wire:submit.prevent="createPost">
                    {{-- Main content textarea --}}
                    <div class="mb-4">
                        <textarea
                            id="content"
                            wire:model="content"
                            rows="4"
                            placeholder="What's happening?"
                            class="w-full rounded-xl bg-slate-800/50 border border-white/10 text-white placeholder-gray-500 focus:border-cyan-500 focus:ring-cyan-500 resize-none p-4 text-lg"
                            autofocus
                        ></textarea>
                        <div class="flex justify-between mt-1">
                            @error('content') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            <span class="text-xs text-gray-500 ml-auto">{{ strlen($content) }}/500</span>
                        </div>
                    </div>

                    {{-- Location toggle with Google Places Autocomplete --}}
                    @if($showLocation)
                    <div class="mb-4">
                        <div class="flex items-center gap-2 mb-2">
                            <svg class="w-4 h-4 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <span class="text-sm text-gray-300">Location</span>
                            <button type="button" wire:click="toggleLocation" class="ml-auto text-gray-500 hover:text-white">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                        <div
                            x-data="{
                                init() {
                                    setTimeout(() => this.loadGoogleMaps(), 100);
                                },
                                loadGoogleMaps() {
                                    if (window.google && window.google.maps && window.google.maps.places) {
                                        this.initAutocomplete();
                                        return;
                                    }
                                    if (!document.getElementById('google-maps-script')) {
                                        const script = document.createElement('script');
                                        script.id = 'google-maps-script';
                                        script.src = 'https://maps.googleapis.com/maps/api/js?key={{ config('services.google.places_api_key') }}&libraries=places';
                                        script.async = true;
                                        script.defer = true;
                                        script.onload = () => {
                                            this.initAutocomplete();
                                        };
                                        document.head.appendChild(script);
                                    } else {
                                        const checkInterval = setInterval(() => {
                                            if (window.google && window.google.maps && window.google.maps.places) {
                                                clearInterval(checkInterval);
                                                this.initAutocomplete();
                                            }
                                        }, 100);
                                    }
                                },
                                initAutocomplete() {
                                    const input = this.$refs.locationInput;
                                    if (!input) return;

                                    const autocomplete = new google.maps.places.Autocomplete(input, {
                                        types: ['establishment', 'geocode'],
                                        fields: ['formatted_address', 'geometry', 'name']
                                    });

                                    autocomplete.addListener('place_changed', () => {
                                        const place = autocomplete.getPlace();
                                        if (!place.geometry) return;

                                        const name = place.name || place.formatted_address;
                                        @this.set('locationName', name);
                                    });
                                }
                            }"
                            wire:ignore
                        >
                            <input
                                x-ref="locationInput"
                                type="text"
                                wire:model="locationName"
                                placeholder="Search for a location..."
                                autocomplete="off"
                                class="w-full rounded-lg bg-slate-800/50 border border-white/10 text-white placeholder-gray-500 focus:border-cyan-500 focus:ring-cyan-500 px-3 py-2 text-sm"
                            >
                        </div>
                        @error('locationName') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                    @endif

                    {{-- Bottom toolbar --}}
                    <div class="flex items-center justify-between border-t border-white/10 pt-4">
                        <div class="flex items-center gap-3">
                            {{-- Add location button --}}
                            @if(!$showLocation)
                            <button type="button" wire:click="toggleLocation" class="flex items-center gap-1 text-gray-400 hover:text-cyan-400 transition text-sm">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <span>Location</span>
                            </button>
                            @endif

                            {{-- Expiration dropdown --}}
                            <div class="flex items-center gap-1 text-gray-400 text-sm">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <select wire:model="expiresIn" class="bg-transparent border-none text-gray-400 text-sm focus:ring-0 cursor-pointer hover:text-white">
                                    <option value="24">24 hours</option>
                                    <option value="48">48 hours</option>
                                    <option value="168">1 week</option>
                                </select>
                            </div>
                        </div>

                        <div class="flex gap-2">
                            <button type="button" wire:click="closeModal" class="px-4 py-2 text-gray-400 hover:text-white transition text-sm">
                                Cancel
                            </button>
                            <button type="submit" wire:loading.attr="disabled" class="px-5 py-2 bg-gradient-to-r from-pink-500 to-purple-500 rounded-full font-semibold hover:scale-105 transition-all text-white text-sm disabled:opacity-50 disabled:cursor-wait disabled:hover:scale-100">
                                <span wire:loading.remove wire:target="createPost">Post</span>
                                <span wire:loading wire:target="createPost" class="flex items-center gap-2">
                                    <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    Posting...
                                </span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>
