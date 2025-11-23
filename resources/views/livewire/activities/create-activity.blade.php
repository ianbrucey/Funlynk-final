<div class="min-h-screen py-12">
    <div class="container mx-auto px-6">
        {{-- Header --}}
        <div class="mb-8 text-center">
            <h1 class="text-4xl font-bold mb-2">
                <span class="gradient-text">Create Activity</span>
            </h1>
            <p class="text-gray-400">Share your passion and connect with others</p>
        </div>

        {{-- Flash Messages --}}
        @if (session()->has('success'))
            <div class="mb-6 p-4 bg-green-500/20 border border-green-500/50 rounded-xl text-green-300">
                {{ session('success') }}
            </div>
        @endif

        @if (session()->has('error'))
            <div class="mb-6 p-4 bg-red-500/20 border border-red-500/50 rounded-xl text-red-300">
                {{ session('error') }}
            </div>
        @endif

        <form wire:submit="createActivity" class="max-w-4xl mx-auto space-y-6">
            {{-- Basic Information --}}
            <div class="relative p-8 glass-card">
                <div class="top-accent-center"></div>
                
                <h2 class="text-2xl font-bold mb-6 flex items-center gap-2">
                    <svg class="w-6 h-6 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Basic Information
                </h2>

                <div class="space-y-4">
                    {{-- Title --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-300 mb-2">Activity Title *</label>
                        <input 
                            type="text" 
                            wire:model="title"
                            placeholder="e.g., Pickup Basketball Game"
                            class="w-full px-4 py-3 bg-slate-800/50 border border-white/10 rounded-2xl focus:border-cyan-500/50 focus:outline-none transition text-white"
                        />
                        @error('title') <span class="text-red-400 text-sm mt-1">{{ $message }}</span> @enderror
                    </div>

                    {{-- Activity Type --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-300 mb-2">Activity Type *</label>
                        <select 
                            wire:model="activity_type"
                            class="w-full px-4 py-3 bg-slate-800/50 border border-white/10 rounded-2xl focus:border-cyan-500/50 focus:outline-none transition text-white"
                        >
                            <option value="sports">🏀 Sports</option>
                            <option value="music">🎵 Music</option>
                            <option value="food">🍕 Food & Drink</option>
                            <option value="social">👥 Social</option>
                            <option value="outdoor">🏕️ Outdoor</option>
                            <option value="arts">🎨 Arts & Culture</option>
                            <option value="wellness">🧘 Wellness</option>
                            <option value="tech">💻 Technology</option>
                            <option value="education">📚 Education</option>
                            <option value="other">✨ Other</option>
                        </select>
                        @error('activity_type') <span class="text-red-400 text-sm mt-1">{{ $message }}</span> @enderror
                    </div>

                    {{-- Description --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-300 mb-2">Description *</label>
                        <textarea 
                            wire:model="description"
                            rows="4"
                            placeholder="Tell people what to expect..."
                            class="w-full px-4 py-3 bg-slate-800/50 border border-white/10 rounded-2xl focus:border-cyan-500/50 focus:outline-none transition text-white"
                        ></textarea>
                        @error('description') <span class="text-red-400 text-sm mt-1">{{ $message }}</span> @enderror
                    </div>

                    {{-- Images --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-300 mb-2">Activity Images</label>
                        <input 
                            type="file" 
                            wire:model="images"
                            multiple
                            accept="image/*"
                            class="w-full px-4 py-3 bg-slate-800/50 border border-white/10 rounded-2xl focus:border-cyan-500/50 focus:outline-none transition text-white file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-gradient-to-r file:from-pink-500 file:to-purple-500 file:text-white hover:file:scale-105 file:transition-all"
                        />
                        <p class="text-xs text-gray-400 mt-1">Max 5 images, 2MB each</p>
                        @error('images.*') <span class="text-red-400 text-sm mt-1">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>

            {{-- Location --}}
            <div class="relative p-8 glass-card">
                <div class="top-accent-center"></div>
                
                <h2 class="text-2xl font-bold mb-6 flex items-center gap-2">
                    <svg class="w-6 h-6 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    Location
                </h2>

                <div class="space-y-4">
                    {{-- Location Name --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-300 mb-2">Location Name *</label>
                        <input 
                            type="text" 
                            wire:model="location_name"
                            placeholder="e.g., Central Park Basketball Courts"
                            class="w-full px-4 py-3 bg-slate-800/50 border border-white/10 rounded-2xl focus:border-cyan-500/50 focus:outline-none transition text-white"
                        />
                        @error('location_name') <span class="text-red-400 text-sm mt-1">{{ $message }}</span> @enderror
                    </div>

                    {{-- Coordinates --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-300 mb-2">Latitude *</label>
                            <input 
                                type="number" 
                                step="any"
                                wire:model="latitude"
                                placeholder="40.7829"
                                class="w-full px-4 py-3 bg-slate-800/50 border border-white/10 rounded-2xl focus:border-cyan-500/50 focus:outline-none transition text-white"
                            />
                            @error('latitude') <span class="text-red-400 text-sm mt-1">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-300 mb-2">Longitude *</label>
                            <input 
                                type="number" 
                                step="any"
                                wire:model="longitude"
                                placeholder="-73.9654"
                                class="w-full px-4 py-3 bg-slate-800/50 border border-white/10 rounded-2xl focus:border-cyan-500/50 focus:outline-none transition text-white"
                            />
                            @error('longitude') <span class="text-red-400 text-sm mt-1">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <button 
                        type="button"
                        wire:click="useCurrentLocation"
                        class="px-4 py-2 bg-slate-800/50 border border-white/10 rounded-xl hover:border-cyan-500/50 transition text-sm"
                    >
                        📍 Use My Current Location
                    </button>
                </div>
            </div>

            {{-- Date & Time --}}
            <div class="relative p-8 glass-card">
                <div class="top-accent-center"></div>
                
                <h2 class="text-2xl font-bold mb-6 flex items-center gap-2">
                    <svg class="w-6 h-6 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    Date & Time
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-300 mb-2">Start Time *</label>
                        <input 
                            type="datetime-local" 
                            wire:model="start_time"
                            class="w-full px-4 py-3 bg-slate-800/50 border border-white/10 rounded-2xl focus:border-cyan-500/50 focus:outline-none transition text-white"
                        />
                        @error('start_time') <span class="text-red-400 text-sm mt-1">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-300 mb-2">End Time (Optional)</label>
                        <input 
                            type="datetime-local" 
                            wire:model="end_time"
                            class="w-full px-4 py-3 bg-slate-800/50 border border-white/10 rounded-2xl focus:border-cyan-500/50 focus:outline-none transition text-white"
                        />
                        @error('end_time') <span class="text-red-400 text-sm mt-1">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>

            {{-- Capacity & Pricing --}}
            <div class="relative p-8 glass-card">
                <div class="top-accent-center"></div>
                
                <h2 class="text-2xl font-bold mb-6 flex items-center gap-2">
                    <svg class="w-6 h-6 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    Capacity & Pricing
                </h2>

                <div class="space-y-4">
                    {{-- Max Attendees --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-300 mb-2">Maximum Attendees</label>
                        <input 
                            type="number" 
                            wire:model="max_attendees"
                            placeholder="Leave empty for unlimited"
                            min="1"
                            class="w-full px-4 py-3 bg-slate-800/50 border border-white/10 rounded-2xl focus:border-cyan-500/50 focus:outline-none transition text-white"
                        />
                        <p class="text-xs text-gray-400 mt-1">Leave empty for unlimited capacity</p>
                        @error('max_attendees') <span class="text-red-400 text-sm mt-1">{{ $message }}</span> @enderror
                    </div>

                    {{-- Paid Toggle --}}
                    <div class="flex items-center gap-3">
                        <input 
                            type="checkbox" 
                            wire:model.live="is_paid"
                            id="is_paid"
                            class="w-5 h-5 rounded bg-slate-800/50 border-white/10 text-cyan-500 focus:ring-cyan-500/50"
                        />
                        <label for="is_paid" class="text-sm font-semibold text-gray-300">This is a paid activity</label>
                    </div>

                    {{-- Price --}}
                    @if ($is_paid)
                        <div>
                            <label class="block text-sm font-semibold text-gray-300 mb-2">Price (in cents) *</label>
                            <div class="relative">
                                <span class="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400">$</span>
                                <input 
                                    type="number" 
                                    wire:model="price_cents"
                                    placeholder="1500 (for $15.00)"
                                    min="1"
                                    class="w-full pl-8 pr-4 py-3 bg-slate-800/50 border border-white/10 rounded-2xl focus:border-cyan-500/50 focus:outline-none transition text-white"
                                />
                            </div>
                            <p class="text-xs text-gray-400 mt-1">Enter amount in cents (e.g., 1500 for $15.00)</p>
                            @error('price_cents') <span class="text-red-400 text-sm mt-1">{{ $message }}</span> @enderror
                        </div>
                    @endif
                </div>
            </div>

            {{-- Settings --}}
            <div class="relative p-8 glass-card">
                <div class="top-accent-center"></div>
                
                <h2 class="text-2xl font-bold mb-6 flex items-center gap-2">
                    <svg class="w-6 h-6 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    Settings
                </h2>

                <div class="space-y-4">
                    <div class="flex items-center gap-3">
                        <input 
                            type="checkbox" 
                            wire:model="is_public"
                            id="is_public"
                            class="w-5 h-5 rounded bg-slate-800/50 border-white/10 text-cyan-500 focus:ring-cyan-500/50"
                        />
                        <label for="is_public" class="text-sm font-semibold text-gray-300">
                            Public Activity
                            <span class="block text-xs text-gray-400 font-normal">Appears in discovery feeds</span>
                        </label>
                    </div>

                    <div class="flex items-center gap-3">
                        <input 
                            type="checkbox" 
                            wire:model="requires_approval"
                            id="requires_approval"
                            class="w-5 h-5 rounded bg-slate-800/50 border-white/10 text-cyan-500 focus:ring-cyan-500/50"
                        />
                        <label for="requires_approval" class="text-sm font-semibold text-gray-300">
                            Require Approval
                            <span class="block text-xs text-gray-400 font-normal">You must approve each RSVP</span>
                        </label>
                    </div>
                </div>
            </div>

            {{-- Tags --}}
            <div class="relative p-8 glass-card">
                <div class="top-accent-center"></div>
                
                <h2 class="text-2xl font-bold mb-6 flex items-center gap-2">
                    <svg class="w-6 h-6 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                    </svg>
                    Tags
                </h2>

                @livewire('tags.tag-autocomplete', ['selectedTags' => $selectedTags])
            </div>

            {{-- Submit Button --}}
            <div class="flex gap-4 justify-center">
                <a 
                    href="{{ route('activities.index') }}"
                    class="px-8 py-4 bg-slate-800/50 border border-white/10 rounded-xl hover:border-cyan-500/50 transition font-semibold"
                >
                    Cancel
                </a>
                <button 
                    type="submit"
                    class="px-8 py-4 bg-gradient-to-r from-pink-500 to-purple-500 rounded-xl font-semibold hover:scale-105 transition-all shadow-lg"
                >
                    Create Activity
                </button>
            </div>
        </form>
    </div>
</div>

<style>
    .gradient-text {
        background: linear-gradient(to right, #fbbf24, #06b6d4);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .glass-card {
        background: rgba(15, 23, 42, 0.5);
        backdrop-filter: blur(20px);
        border: 1px solid rgba(59, 130, 246, 0.3);
        border-radius: 1.5rem;
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
    // Get current location
    document.addEventListener('livewire:initialized', () => {
        Livewire.on('getCurrentLocation', () => {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        @this.setLocation(position.coords.latitude, position.coords.longitude);
                    },
                    (error) => {
                        alert('Unable to get your location. Please enter coordinates manually.');
                    }
                );
            } else {
                alert('Geolocation is not supported by your browser.');
            }
        });
    });
</script>
