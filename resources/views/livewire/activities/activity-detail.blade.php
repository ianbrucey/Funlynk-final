<div class="min-h-screen py-12">
    <div class="container mx-auto lg:px-6 lg:py-12">

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

        <div class="grid grid-cols-1 lg:grid-cols-3 lg:gap-8">

            {{-- Main Content (Left Column) --}}
            <div class="lg:col-span-2 space-y-6 lg:space-y-6">

                {{-- Title, Details & Description Card --}}
                <div class="relative p-6 lg:p-8 glass-card lg:rounded-xl overflow-hidden">
                    <div class="top-accent"></div>

                    {{-- Status Badge --}}
                    <div class="absolute top-6 right-6 z-10">
                        @if($activity->status === 'draft')
                            <span class="px-3 py-1 bg-gray-500/30 border border-gray-500/50 rounded-full text-xs font-bold text-gray-300 uppercase tracking-wider">Draft</span>
                        @elseif($activity->status === 'published')
                            <span class="px-3 py-1 bg-blue-500/30 border border-blue-500/50 rounded-full text-xs font-bold text-blue-300 uppercase tracking-wider">Published</span>
                        @elseif($activity->status === 'active')
                            <span class="px-3 py-1 bg-green-500/30 border border-green-500/50 rounded-full text-xs font-bold text-green-300 uppercase tracking-wider">Active</span>
                        @elseif($activity->status === 'completed')
                            <span class="px-3 py-1 bg-purple-500/30 border border-purple-500/50 rounded-full text-xs font-bold text-purple-300 uppercase tracking-wider">Completed</span>
                        @elseif($activity->status === 'cancelled')
                            <span class="px-3 py-1 bg-red-500/30 border border-red-500/50 rounded-full text-xs font-bold text-red-300 uppercase tracking-wider">Cancelled</span>
                        @endif
                    </div>

                    {{-- Title --}}
                    <h1 class="text-4xl font-bold mb-4 text-white pr-24">{{ $activity->title }}</h1>

                    {{-- Short Details --}}
                    <div class="flex flex-wrap gap-4 text-sm text-gray-300 mb-4">
                        <div class="flex items-center gap-2">
                            <span class="text-2xl">{{ match($activity->activity_type) {
                                'sports' => '🏀',
                                'music' => '🎵',
                                'food' => '🍕',
                                'social' => '👥',
                                'outdoor' => '🏕️',
                                'arts' => '🎨',
                                'wellness' => '🧘',
                                'tech' => '💻',
                                'education' => '📚',
                                'other' => '✨',
                                default => '📅'
                            } }}</span>
                            <span class="capitalize">{{ $activity->activity_type }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            <span>{{ $activity->location_name }}</span>
                        </div>
                    </div>

                    {{-- Tags --}}
                    @if(count($activity->tags) > 0)
                        <div class="flex flex-wrap gap-2 mb-6">
                            @foreach($activity->tags as $tag)
                                <span class="px-3 py-1 bg-slate-800/50 border border-white/10 rounded-lg text-sm text-purple-300">
                                    #{{ $tag['name'] ?? $tag->name }}
                                </span>
                            @endforeach
                        </div>
                    @endif

                    {{-- Image Carousel --}}
                    @if($activity->images && count($activity->images) > 0)
                        <div class="relative mb-6" x-data="{ currentSlide: 0, totalSlides: {{ count($activity->images) }} }">
                            {{-- Carousel Container --}}
                            <div class="relative overflow-hidden lg:rounded-xl border-y lg:border border-white/10 bg-slate-900/50">
                                <div class="flex transition-transform duration-500 ease-out"
                                     :style="`transform: translateX(-${currentSlide * 100}%)`">
                                    @foreach($activity->images as $image)
                                        <div class="w-full flex-shrink-0 flex items-center justify-center" >
                                            <img src="{{ Storage::url($image) }}"
                                                 class="max-w-full max-h-full object-contain"
                                                 alt="Activity image">
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            {{-- Navigation Arrows (only show if more than 1 image) --}}
                            @if(count($activity->images) > 1)
                                {{-- Previous Button --}}
                                <button @click="currentSlide = currentSlide === 0 ? totalSlides - 1 : currentSlide - 1"
                                        class="absolute left-2 top-1/2 -translate-y-1/2 p-2 bg-slate-900/80 hover:bg-slate-800 border border-white/20 rounded-full transition-all hover:scale-110 z-10">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                    </svg>
                                </button>

                                {{-- Next Button --}}
                                <button @click="currentSlide = currentSlide === totalSlides - 1 ? 0 : currentSlide + 1"
                                        class="absolute right-2 top-1/2 -translate-y-1/2 p-2 bg-slate-900/80 hover:bg-slate-800 border border-white/20 rounded-full transition-all hover:scale-110 z-10">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </button>

                                {{-- Dots Indicator --}}
                                <div class="absolute bottom-4 left-1/2 -translate-x-1/2 flex gap-2 z-10">
                                    @foreach($activity->images as $index => $image)
                                        <button @click="currentSlide = {{ $index }}"
                                                class="w-2 h-2 rounded-full transition-all"
                                                :class="currentSlide === {{ $index }} ? 'bg-cyan-400 w-6' : 'bg-white/50 hover:bg-white/80'">
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endif

                    {{-- Description --}}
                    <div class="prose prose-invert max-w-none text-gray-300 leading-relaxed">
                        {{ $activity->description }}
                    </div>
                </div>

                {{-- About & Details Card --}}
                <div class="relative p-6 lg:p-8 glass-card lg:rounded-xl">
                    <div class="top-accent"></div>

                    <h2 class="text-2xl font-bold mb-6 text-white">About this Activity</h2>

                    {{-- Key Details Grid --}}
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8 pb-8 border-b border-white/10">

                        {{-- Date & Time --}}
                        <div>
                            <h3 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-3">When</h3>
                            <div class="flex items-start gap-3">
                                <div class="p-2 bg-slate-800 rounded-lg border border-white/10">
                                    <span class="text-2xl font-bold text-cyan-400">{{ $activity->start_time->format('d') }}</span>
                                </div>
                                <div>
                                    <div class="text-lg font-bold text-white">{{ $activity->start_time->format('F Y') }}</div>
                                    <div class="text-gray-300">{{ $activity->start_time->format('l, g:i A') }}</div>
                                    @if($activity->end_time)
                                        <div class="text-sm text-gray-500 mt-1">
                                            to {{ $activity->end_time->format('g:i A') }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Price --}}
                        <div>
                            <h3 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-3">Price</h3>
                            <div class="text-3xl font-bold text-white">
                                @if($activity->is_paid)
                                    ${{ number_format($activity->price_cents / 100, 2) }}
                                @else
                                    <span class="text-green-400">Free</span>
                                @endif
                            </div>
                        </div>

                        {{-- Capacity --}}
                        <div>
                            <h3 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-3">Availability</h3>
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-gray-300">{{ $activity->current_attendees }} attending</span>
                                @if($activity->max_attendees)
                                    <span class="text-gray-400">{{ $spotsRemaining }} spots left</span>
                                @endif
                            </div>
                            @if($activity->max_attendees)
                                <div class="w-full bg-slate-800 rounded-full h-2">
                                    <div class="bg-gradient-to-r from-cyan-500 to-blue-500 h-2 rounded-full"
                                         style="width: {{ ($activity->current_attendees / $activity->max_attendees) * 100 }}%"></div>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Description --}}
                    <div class="prose prose-invert max-w-none text-gray-300 mb-8">
                        {{ $activity->description }}
                    </div>

                    {{-- RSVP Button --}}
                    @if(!$isHost)
                        <livewire:activities.rsvp-button :activity="$activity" />
                    @endif

                    {{-- Host Actions --}}
                    @if($isHost)
                        <div class="flex gap-2">
                            <a href="{{ route('activities.edit', $activity->id) }}" class="flex-1 py-3 text-center bg-slate-800/50 border border-white/10 rounded-lg hover:border-cyan-500/50 transition font-semibold">
                                Edit
                            </a>
                            <button
                                wire:click="deleteActivity"
                                wire:confirm="Are you sure you want to delete this activity?"
                                class="flex-1 py-3 bg-red-500/10 border border-red-500/30 rounded-lg hover:bg-red-500/20 transition font-semibold text-red-400"
                            >
                                Delete
                            </button>
                        </div>
                    @endif
                </div>

                {{-- Comments Section --}}
                <div class="relative p-6 lg:p-8 glass-card lg:rounded-xl">
                    <livewire:comments.comment-section
                        :commentable-type="'App\\Models\\Activity'"
                        :commentable-id="$activity->id"
                    />
                </div>

            </div>

            {{-- Sidebar (Right Column) --}}
            <div class="space-y-6 lg:space-y-8">

                {{-- Host Info --}}
                <div class="relative p-6 glass-card lg:rounded-xl">
                    <h3 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-4">Hosted By</h3>
                    <div class="flex items-center gap-4">
                        @if($activity->host->profile_image_url)
                            <img src="{{ Storage::url($activity->host->profile_image_url) }}" class="w-12 h-12 rounded-full object-cover border-2 border-white/10">
                        @else
                            <div class="w-12 h-12 rounded-full bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center text-xl font-bold text-white">
                                {{ substr($activity->host->name, 0, 1) }}
                            </div>
                        @endif
                        <div>
                            <div class="font-bold text-white">{{ $activity->host->name }}</div>
                            <div class="text-xs text-gray-400">Member since {{ $activity->host->created_at->format('M Y') }}</div>
                        </div>
                    </div>
                </div>

                {{-- Map --}}
                <div class="relative p-1 glass-card lg:rounded-xl overflow-hidden h-64">
                    <div id="activity-map" class="w-full h-full lg:rounded-xl"></div>
                </div>

            </div>
        </div>
    </div>
    <style>
       

        .top-accent {
            position: absolute;
            top: 0;
            left: 0;
            width: 8rem;
            height: 0.25rem;
            background: linear-gradient(to right, #ec4899, #8b5cf6, transparent);
            border-radius: 9999px;
        }
    </style>
    @if($activity->location_coordinates instanceof \MatanYadaev\EloquentSpatial\Objects\Point)
    <script>
        const activityMapData = {
            lat: {{ $activity->location_coordinates->latitude }},
            lng: {{ $activity->location_coordinates->longitude }},
            title: @json($activity->title),
            locationName: @json($activity->location_name)
        };

        function initActivityMap() {
            const mapElement = document.getElementById('activity-map');
            if (!mapElement) {
                console.log('Map element not found');
                return;
            }

            console.log('Initializing map with position:', activityMapData);

            const position = {
                lat: activityMapData.lat,
                lng: activityMapData.lng
            };

            const map = new google.maps.Map(mapElement, {
                center: position,
                zoom: 15,
                styles: [
                    { elementType: "geometry", stylers: [{ color: "#1e293b" }] },
                    { elementType: "labels.text.stroke", stylers: [{ color: "#0f172a" }] },
                    { elementType: "labels.text.fill", stylers: [{ color: "#94a3b8" }] },
                    {
                        featureType: "administrative.locality",
                        elementType: "labels.text.fill",
                        stylers: [{ color: "#cbd5e1" }],
                    },
                    {
                        featureType: "poi",
                        elementType: "labels.text.fill",
                        stylers: [{ color: "#64748b" }],
                    },
                    {
                        featureType: "poi.park",
                        elementType: "geometry",
                        stylers: [{ color: "#1e3a2e" }],
                    },
                    {
                        featureType: "poi.park",
                        elementType: "labels.text.fill",
                        stylers: [{ color: "#6b9080" }],
                    },
                    {
                        featureType: "road",
                        elementType: "geometry",
                        stylers: [{ color: "#334155" }],
                    },
                    {
                        featureType: "road",
                        elementType: "geometry.stroke",
                        stylers: [{ color: "#1e293b" }],
                    },
                    {
                        featureType: "road.highway",
                        elementType: "geometry",
                        stylers: [{ color: "#475569" }],
                    },
                    {
                        featureType: "water",
                        elementType: "geometry",
                        stylers: [{ color: "#0c1e2e" }],
                    },
                    {
                        featureType: "water",
                        elementType: "labels.text.fill",
                        stylers: [{ color: "#475569" }],
                    },
                ],
                disableDefaultUI: true,
                zoomControl: true,
            });

            // Custom marker with gradient
            const marker = new google.maps.Marker({
                position: position,
                map: map,
                title: activityMapData.locationName,
                animation: google.maps.Animation.DROP,
            });

            // Info window
            const infoWindow = new google.maps.InfoWindow({
                content: `
                    <div style="padding: 8px; color: #1e293b;">
                        <h3 style="margin: 0 0 4px 0; font-weight: bold;">${activityMapData.title}</h3>
                        <p style="margin: 0; font-size: 14px;">${activityMapData.locationName}</p>
                    </div>
                `
            });

            marker.addListener('click', () => {
                infoWindow.open(map, marker);
            });

            console.log('Map initialized successfully');
        }

        // Load Google Maps API
        if (!window.google || !window.google.maps) {
            console.log('Loading Google Maps API...');
            const script = document.createElement('script');
            script.src = `https://maps.googleapis.com/maps/api/js?key={{ config('services.google.places_api_key') }}&callback=initActivityMap`;
            script.async = true;
            script.defer = true;
            document.head.appendChild(script);
        } else {
            console.log('Google Maps API already loaded');
            initActivityMap();
        }
    </script>
    @else
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const mapElement = document.getElementById('activity-map');
            if (mapElement) {
                mapElement.innerHTML = '<div style="display: flex; align-items: center; justify-content: center; height: 100%; color: #94a3b8;">Location not available</div>';
            }
        });
    </script>
    @endif
</div>
