<div x-data="{ open: false }"
     x-show="open"
     x-cloak
     x-on:open-create-group-event-modal.window="open = true; $wire.call('openModal')"
     x-on:close-create-group-event-modal.window="open = false"
     x-on:keydown.escape.window="open = false"
     class="relative z-50">

    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen p-4 text-center">
            <!-- Background overlay -->
            <div class="fixed inset-0 transition-opacity bg-gray-900 bg-opacity-75" @click="open = false"></div>

            <!-- Modal content -->
            <div class="relative inline-block w-full max-w-lg px-6 pt-5 pb-4 overflow-hidden text-left align-middle transition-all transform rounded-2xl shadow-xl glass-card sm:p-6">
                <div class="top-accent-center"></div>

                {{-- Header with event icon --}}
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-cyan-500 to-purple-500 flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-white">Create Event</h3>
                        <p class="text-sm text-gray-400">Schedule an activity for {{ $group->name }}</p>
                    </div>
                </div>

                <form wire:submit.prevent="createEvent" class="space-y-4">
                    {{-- Event Title --}}
                    <div>
                        <input
                            type="text"
                            wire:model="title"
                            placeholder="Event name (e.g., Sunday Open Mat)"
                            class="w-full rounded-xl bg-slate-800/50 border border-white/10 text-white placeholder-gray-500 focus:border-cyan-500 focus:ring-cyan-500 px-4 py-3 text-lg"
                        >
                        @error('title') <span class="text-red-500 text-sm mt-1">{{ $message }}</span> @enderror
                    </div>

                    {{-- Description (optional) --}}
                    <div>
                        <textarea
                            wire:model="description"
                            rows="2"
                            placeholder="Add details (optional)"
                            class="w-full rounded-xl bg-slate-800/50 border border-white/10 text-white placeholder-gray-500 focus:border-cyan-500 focus:ring-cyan-500 px-4 py-3 resize-none"
                        ></textarea>
                        @error('description') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    {{-- Location with Google Places Autocomplete --}}
                    <div
                        class="flex items-center gap-2 p-3 rounded-xl bg-slate-800/30 border border-white/5"
                        x-data="{
                            init() {
                                this.loadGoogleMaps();
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
                                    // Wait for existing script
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
                                const autocomplete = new google.maps.places.Autocomplete(input, {
                                    types: ['establishment', 'geocode'],
                                    fields: ['formatted_address', 'geometry', 'name']
                                });
                                autocomplete.addListener('place_changed', () => {
                                    const place = autocomplete.getPlace();
                                    if (!place.geometry) return;
                                    
                                    const name = place.name || place.formatted_address;
                                    const lat = place.geometry.location.lat();
                                    const lng = place.geometry.location.lng();
                                    
                                    @this.setLocationData(name, lat, lng);
                                });
                            }
                        }"
                        wire:ignore
                    >
                        <svg class="w-5 h-5 text-pink-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <input
                            x-ref="locationInput"
                            type="text"
                            wire:model="locationName"
                            placeholder="Search for a location..."
                            autocomplete="off"
                            class="flex-1 bg-transparent border-none text-white placeholder-gray-500 focus:ring-0 px-0"
                        >
                    </div>
                    @error('locationName') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror

                    {{-- Date & Time Section --}}
                    <div class="p-4 rounded-xl bg-slate-800/30 border border-white/5 space-y-3">
                        <div class="flex items-center gap-2 text-gray-300 text-sm font-medium mb-2">
                            <svg class="w-5 h-5 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            When?
                        </div>

                        {{-- Date picker --}}
                        <div>
                            <input
                                type="date"
                                wire:model="startDate"
                                min="{{ now()->format('Y-m-d') }}"
                                class="w-full rounded-lg bg-slate-700/50 border border-white/10 text-white focus:border-cyan-500 focus:ring-cyan-500 px-3 py-2"
                            >
                            @error('startDate') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        {{-- Time picker (simplified) --}}
                        <div class="flex items-center gap-2">
                            <select wire:model="startTimeHour" class="rounded-lg bg-slate-700/50 border border-white/10 text-white focus:border-cyan-500 focus:ring-cyan-500 px-3 py-2">
                                @for($i = 1; $i <= 12; $i++)
                                    <option value="{{ $i }}">{{ $i }}</option>
                                @endfor
                            </select>
                            <span class="text-gray-400">:</span>
                            <select wire:model="startTimeMinute" class="rounded-lg bg-slate-700/50 border border-white/10 text-white focus:border-cyan-500 focus:ring-cyan-500 px-3 py-2">
                                <option value="00">00</option>
                                <option value="15">15</option>
                                <option value="30">30</option>
                                <option value="45">45</option>
                            </select>
                            <select wire:model="startTimePeriod" class="rounded-lg bg-slate-700/50 border border-white/10 text-white focus:border-cyan-500 focus:ring-cyan-500 px-3 py-2">
                                <option value="AM">AM</option>
                                <option value="PM">PM</option>
                            </select>
                        </div>

                        {{-- Duration --}}
                        <div class="flex items-center gap-2">
                            <span class="text-gray-400 text-sm">Duration:</span>
                            <select wire:model="duration" class="flex-1 rounded-lg bg-slate-700/50 border border-white/10 text-white focus:border-cyan-500 focus:ring-cyan-500 px-3 py-2">
                                <option value="30">30 minutes</option>
                                <option value="60">1 hour</option>
                                <option value="90">1.5 hours</option>
                                <option value="120">2 hours</option>
                                <option value="180">3 hours</option>
                                <option value="240">4 hours</option>
                            </select>
                        </div>
                    </div>

                    {{-- Max Attendees (optional) --}}
                    <div class="flex items-center gap-3 p-3 rounded-xl bg-slate-800/30 border border-white/5">
                        <svg class="w-5 h-5 text-purple-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        <input
                            type="number"
                            wire:model="maxAttendees"
                            min="1"
                            placeholder="Max attendees (leave empty for unlimited)"
                            class="flex-1 bg-transparent border-none text-white placeholder-gray-500 focus:ring-0 px-0"
                        >
                    </div>
                    @error('maxAttendees') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror

                    {{-- Action buttons --}}
                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" @click="open = false" class="px-5 py-2 text-gray-400 hover:text-white transition text-sm">
                            Cancel
                        </button>
                        <button type="submit" wire:loading.attr="disabled" class="px-6 py-2.5 bg-gradient-to-r from-cyan-500 to-purple-500 rounded-full font-semibold hover:scale-105 transition-all text-white text-sm disabled:opacity-50 disabled:cursor-wait disabled:hover:scale-100">
                            <span wire:loading.remove wire:target="createEvent">Create Event</span>
                            <span wire:loading wire:target="createEvent" class="flex items-center gap-2">
                                <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                Creating...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
