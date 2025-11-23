<div class="min-h-screen py-12">
    <div class="container mx-auto px-6">
        
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

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            {{-- Main Content (Left Column) --}}
            <div class="lg:col-span-2 space-y-8">
                
                {{-- Hero / Title Card --}}
                <div class="relative p-8 glass-card overflow-hidden">
                    <div class="top-accent"></div>
                    
                    {{-- Status Badge --}}
                    <div class="absolute top-6 right-6">
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

                    <h1 class="text-4xl font-bold mb-4 text-white">{{ $activity->title }}</h1>
                    
                    <div class="flex flex-wrap gap-4 text-sm text-gray-300 mb-6">
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
                        <div class="flex flex-wrap gap-2">
                            @foreach($activity->tags as $tag)
                                <span class="px-3 py-1 bg-slate-800/50 border border-white/10 rounded-lg text-sm text-purple-300">
                                    #{{ $tag['name'] ?? $tag->name }}
                                </span>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Images Gallery --}}
                @if($activity->images && count($activity->images) > 0)
                    <div class="glass-card p-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach($activity->images as $image)
                                <img src="{{ Storage::url($image) }}" class="w-full h-64 object-cover rounded-xl border border-white/10 hover:scale-[1.02] transition-transform duration-300">
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Description --}}
                <div class="relative p-8 glass-card">
                    <h2 class="text-2xl font-bold mb-4 text-white">About this Activity</h2>
                    <div class="prose prose-invert max-w-none text-gray-300">
                        {{ $activity->description }}
                    </div>
                </div>

            </div>

            {{-- Sidebar (Right Column) --}}
            <div class="space-y-8">
                
                {{-- Action Card --}}
                <div class="relative p-6 glass-card">
                    <div class="top-accent"></div>
                    
                    {{-- Date & Time --}}
                    <div class="mb-6">
                        <h3 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-2">When</h3>
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
                    <div class="mb-6">
                        <h3 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-2">Price</h3>
                        <div class="text-2xl font-bold text-white">
                            @if($activity->is_paid)
                                ${{ number_format($activity->price_cents / 100, 2) }}
                            @else
                                <span class="text-green-400">Free</span>
                            @endif
                        </div>
                    </div>

                    {{-- Capacity --}}
                    <div class="mb-8">
                        <h3 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-2">Availability</h3>
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

                    {{-- RSVP Button (Placeholder) --}}
                    <button class="w-full py-4 bg-gradient-to-r from-pink-500 to-purple-500 rounded-xl font-bold text-white hover:scale-105 transition-all shadow-lg mb-4">
                        Join Activity
                    </button>

                    {{-- Host Actions --}}
                    @if($isHost)
                        <div class="pt-4 border-t border-white/10 flex gap-2">
                            <a href="{{ route('activities.edit', $activity->id) }}" class="flex-1 py-2 text-center bg-slate-800/50 border border-white/10 rounded-lg hover:border-cyan-500/50 transition text-sm font-semibold">
                                Edit
                            </a>
                            <button 
                                wire:click="deleteActivity"
                                wire:confirm="Are you sure you want to delete this activity?"
                                class="flex-1 py-2 bg-red-500/10 border border-red-500/30 rounded-lg hover:bg-red-500/20 transition text-sm font-semibold text-red-400"
                            >
                                Delete
                            </button>
                        </div>
                    @endif
                </div>

                {{-- Host Info --}}
                <div class="relative p-6 glass-card">
                    <h3 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-4">Hosted By</h3>
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-full bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center text-xl font-bold text-white">
                            {{ substr($activity->host->name, 0, 1) }}
                        </div>
                        <div>
                            <div class="font-bold text-white">{{ $activity->host->name }}</div>
                            <div class="text-xs text-gray-400">Member since {{ $activity->host->created_at->format('M Y') }}</div>
                        </div>
                    </div>
                </div>

                {{-- Map Placeholder --}}
                <div class="relative p-1 glass-card overflow-hidden h-48">
                    <div class="absolute inset-0 bg-slate-800 flex items-center justify-center">
                        <div class="text-center">
                            <div class="text-4xl mb-2">🗺️</div>
                            <div class="text-sm text-gray-400">Map View</div>
                            <div class="text-xs text-gray-500">
                                @if($activity->location_coordinates instanceof \MatanYadaev\EloquentSpatial\Objects\Point)
                                    {{ $activity->location_coordinates->latitude }}, {{ $activity->location_coordinates->longitude }}
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
    <style>
        .glass-card {
            background: rgba(15, 23, 42, 0.5);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(59, 130, 246, 0.3);
            border-radius: 1.5rem;
        }

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
</div>
