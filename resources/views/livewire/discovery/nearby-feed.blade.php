<div class="min-h-screen py-8">
    <div class="container mx-auto ">

        

        {{-- Filters --}}
        <div class=" lg:px-0 mb-6">

            


            <div class="relative p-4 glass-card lg:rounded-xl">

                <div class=" lg:px-0 mb-6">
                {{-- Search Bar --}}
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                        <input
                            type="text"
                            wire:model.live.debounce.300ms="searchQuery"
                            placeholder="👀 search for something to get into :)"
                            class="w-full pl-12 pr-12 py-4 bg-slate-800/50 border border-white/10 rounded-2xl text-white placeholder-gray-400 focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/50 transition text-lg"
                        >
                        @if($searchQuery)
                            <button
                                wire:click="clearSearch"
                                class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-white transition"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        @endif
                    </div>
                    @if($searchQuery)
                        <p class="mt-2 text-sm text-gray-400">
                            Searching for "<span class="text-cyan-400">{{ $searchQuery }}</span>"
                        </p>
                    @endif
                </div>

                <div class="grid grid-cols-3 gap-4">

                    {{-- Content Type Filter --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-400 mb-1">Show</label>
                        <select wire:model.live="contentType" class="w-full px-3 py-2 bg-slate-800/50 border border-white/10 rounded-lg text-white text-sm focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/50 transition">
                            <option value="all">All</option>
                            <option value="posts">Posts</option>
                            <option value="events">Events</option>
                        </select>
                    </div>

                    {{-- Distance Filter --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-400 mb-1">
                            Distance: <span class="text-cyan-400">{{ $radius }} km</span>
                        </label>
                        <input
                            type="range"
                            wire:model.live.debounce.150ms="radius"
                            min="1"
                            max="100"
                            step="1"
                            class="w-full h-2 bg-slate-700 rounded-lg appearance-none cursor-pointer accent-cyan-500"
                        >
                    </div>

                    {{-- Time Filter --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-400 mb-1">When</label>
                        <select wire:model.live="timeFilter" class="w-full px-3 py-2 bg-slate-800/50 border border-white/10 rounded-lg text-white text-sm focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/50 transition">
                            <option value="all">Anytime</option>
                            <option value="today">Today</option>
                            <option value="week">This Week</option>
                            <option value="month">This Month</option>
                        </select>
                    </div>

                </div>
            </div>
        </div>

        {{-- Feed Content - 3 Grid Layout --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" wire:loading.class="opacity-50">
            @forelse($items as $item)
                @if($item['type'] === 'post')
                    {{-- Post Card (Compact) --}}
                    <x-post-card-compact :post="$item['data']" />
                @else
                    {{-- Event Card (Compact) --}}
                    <div class="relative p-4 glass-card rounded-xl border-l-4 border-cyan-500 hover:border-blue-500 transition-all h-full flex flex-col group cursor-pointer"
                         onclick="window.location.href='{{ route('activities.show', $item['data']) }}'">

                        {{-- Converted Badge --}}
                        @if($item['data']->originated_from_post_id)
                            <div class="absolute top-3 right-3 z-10">
                                <span class="px-2 py-0.5 bg-purple-500/30 border border-purple-500/50 rounded-full text-xs font-bold text-purple-300">
                                    ⭐
                                </span>
                            </div>
                        @endif

                        {{-- Host Info --}}
                        <div class="flex items-center gap-2 mb-3">
                            @if($item['data']->host?->profile_image_url)
                                <img
                                    src="{{ Storage::url($item['data']->host->profile_image_url) }}"
                                    alt="{{ $item['data']->host->display_name ?? $item['data']->host->username }}"
                                    class="w-8 h-8 rounded-full object-cover ring-2 ring-cyan-500/50 bg-slate-800"
                                >
                            @else
                                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-cyan-500 to-blue-500 flex items-center justify-center ring-2 ring-cyan-500/50">
                                    <span class="text-white font-bold text-xs">
                                        {{ strtoupper(substr($item['data']->host?->display_name ?? $item['data']->host?->username ?? '?', 0, 1)) }}
                                    </span>
                                </div>
                            @endif
                            <div class="flex-1 min-w-0">
                                <p class="font-semibold text-white text-sm truncate group-hover:text-cyan-400 transition">
                                    {{ $item['data']->host?->display_name ?? $item['data']->host?->username ?? 'Unknown Host' }}
                                </p>
                                <p class="text-xs text-gray-500">Hosting</p>
                            </div>
                        </div>

                        {{-- Event Image (First image only for compact view) --}}
                        @if($item['data']->images && count($item['data']->images) > 0)
                            <div class="relative mb-3 -mx-4 overflow-hidden bg-slate-900/50 h-40">
                                <img src="{{ Storage::url($item['data']->images[0]) }}"
                                     class="w-full h-full object-cover"
                                     alt="{{ $item['data']->title }}">
                                @if(count($item['data']->images) > 1)
                                    <div class="absolute bottom-2 right-2 px-2 py-1 bg-slate-900/80 backdrop-blur-sm rounded-full text-xs text-white">
                                        +{{ count($item['data']->images) - 1 }}
                                    </div>
                                @endif
                            </div>
                        @endif

                        {{-- Content --}}
                        <div class="flex-1 mb-3">
                            <h3 class="text-base font-bold mb-1.5 text-white line-clamp-2 pr-8">{{ $item['data']->title }}</h3>
                            @if($item['data']->description)
                                <p class="text-gray-400 text-xs line-clamp-2">{{ $item['data']->description }}</p>
                            @endif
                        </div>

                        {{-- Event Details --}}
                        <div class="space-y-2 mb-3 text-xs">
                            <div class="flex items-center gap-1.5 text-gray-400">
                                <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <span class="text-white font-semibold">{{ $item['data']->start_time->format('M j, g:i A') }}</span>
                            </div>
                            <div class="flex items-center gap-1.5 text-gray-400">
                                <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                </svg>
                                <span class="truncate">{{ $item['data']->location_name }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-1.5 text-gray-400">
                                    <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <span class="text-white font-semibold">
                                        @if($item['data']->is_paid)
                                            ${{ number_format($item['data']->price, 2) }}
                                        @else
                                            Free
                                        @endif
                                    </span>
                                </div>
                                <div class="text-gray-400">
                                    <span class="text-cyan-400 font-semibold">{{ $item['data']->max_attendees - $item['data']->rsvps()->count() }}</span> spots
                                </div>
                            </div>
                        </div>

                        {{-- Action Buttons --}}
                        <div class="space-y-2 mt-auto">
                            <button
                                onclick="event.stopPropagation(); window.location.href='{{ route('activities.show', $item['data']) }}'"
                                class="w-full px-3 py-2 bg-gradient-to-r from-cyan-500 to-blue-500 rounded-lg text-xs font-semibold hover:scale-105 transition-all">
                                View Details
                            </button>
                            <div class="pt-2 border-t border-white/10">
                                <div class="flex items-center justify-center gap-1.5 text-xs text-gray-400 group-hover:text-cyan-400 transition">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                    </svg>
                                    <span class="font-semibold">Discussion</span>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            @empty
                {{-- Empty State - Spans full grid --}}
                <div class="col-span-full">
                    <div class="relative p-12 glass-card rounded-xl text-center">
                        <div class="w-24 h-24 mx-auto mb-6 rounded-full bg-gradient-to-br from-pink-500 via-purple-500 to-cyan-500 flex items-center justify-center">
                            @if($searchQuery)
                                <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            @else
                                <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            @endif
                        </div>
                        @if($searchQuery)
                            <h3 class="text-xl font-bold mb-2 text-white">No results for "{{ $searchQuery }}"</h3>
                            <p class="text-gray-400 mb-6">Try a different search term or adjust your filters</p>
                            <button
                                wire:click="clearSearch"
                                class="inline-block px-6 py-3 bg-gradient-to-r from-pink-500 to-purple-500 rounded-xl font-semibold hover:scale-105 transition-all">
                                Clear Search
                            </button>
                        @else
                            <h3 class="text-xl font-bold mb-2 text-white">Nothing nearby yet</h3>
                            <p class="text-gray-400 mb-6">Be the first to post something! Try increasing your distance or check back later.</p>
                            <a href="{{ route('posts.create') }}"
                               class="inline-block px-6 py-3 bg-gradient-to-r from-pink-500 to-purple-500 rounded-xl font-semibold hover:scale-105 transition-all">
                                Create a Post
                            </a>
                        @endif
                    </div>
                </div>
            @endforelse
        </div>

        {{-- Load More Button / Infinite Scroll Trigger --}}
        @if($hasMore && count($items) > 0)
            <div class="mt-8" x-data="{
                observe() {
                    const observer = new IntersectionObserver((entries) => {
                        entries.forEach(entry => {
                            if (entry.isIntersecting) {
                                @this.call('loadMore');
                            }
                        });
                    }, { threshold: 0.5 });
                    observer.observe(this.$el);
                }
            }" x-init="observe()">
                <div class="relative p-6 glass-card rounded-xl text-center">
                    <div wire:loading.remove wire:target="loadMore">
                        <button
                            wire:click="loadMore"
                            class="px-8 py-3 bg-gradient-to-r from-cyan-500 to-blue-500 rounded-xl font-semibold hover:scale-105 transition-all">
                            Load More
                        </button>
                        <p class="text-sm text-gray-400 mt-2">
                            Showing {{ count($items) }} of {{ $totalItems }} items
                        </p>
                    </div>
                    <div wire:loading wire:target="loadMore" class="flex items-center justify-center gap-3">
                        <svg class="animate-spin h-6 w-6 text-cyan-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="text-gray-400">Loading more...</span>
                    </div>
                </div>
            </div>
        @elseif(count($items) >= 200)
            <div class="mt-8">
                <div class="relative p-6 glass-card rounded-xl text-center">
                    <p class="text-gray-400 mb-4">You've reached the maximum of 200 items.</p>
                    <p class="text-sm text-gray-500">Try refining your filters to see more specific results.</p>
                </div>
            </div>
        @endif

    </div>

    {{-- Invite Friends Modal --}}
    <livewire:posts.invite-friends-modal />
</div>
