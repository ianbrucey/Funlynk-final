<div class="min-h-screen">
    <div class="container mx-auto ">

        

        {{-- Filters --}}
        <div class=" lg:px-0 mb-6">

            


            <div class="relative p-4 glass-card lg:rounded-xl">

                {{-- Search Bar (Full Width) --}}
                <div class="mb-4">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                        <input
                            type="text"
                            wire:model="searchQuery"
                            wire:keydown.enter="search"
                            placeholder="👀 search for your thing :)"
                            class="w-full pl-12 pr-12 py-4 bg-slate-800/50 border border-white/10 rounded-2xl text-white placeholder-gray-400 focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/50 transition text-lg"
                        >
                        @if($searchQuery)
                            <button
                                wire:click="clearSearch"
                                type="button"
                                class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-white transition"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        @endif
                    </div>
                </div>

                {{-- Search Button (Full Width) --}}
                <button
                    wire:click="search"
                    wire:loading.attr="disabled"
                    wire:loading.class="opacity-50 cursor-wait"
                    type="button"
                    class="w-full mb-4 py-3 bg-gradient-to-r from-pink-500 to-purple-500 rounded-xl font-semibold hover:scale-[1.02] transition-all disabled:hover:scale-100 flex items-center justify-center gap-2"
                >
                    <svg class="w-5 h-5" wire:loading.remove wire:target="search" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <span wire:loading.remove wire:target="search">Search</span>
                    <svg wire:loading wire:target="search" class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span wire:loading wire:target="search">Searching...</span>
                </button>

                @if($searchQuery)
                    <p class="mb-4 text-sm text-gray-400">
                        Showing results for "<span class="text-cyan-400">{{ $searchQuery }}</span>"
                    </p>
                @endif

                {{-- Filters Row --}}
                <div class="grid grid-cols-3 gap-3">
                    {{-- Content Type Filter --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-400 mb-1">Show</label>
                        <select wire:model.live="contentType" class="w-full px-3 py-2 bg-slate-800/50 border border-white/10 rounded-lg text-white text-sm focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/50 transition">
                            <option value="all">All</option>
                            <option value="posts">Posts</option>
                            <option value="events">Events</option>
                        </select>
                    </div>

                    {{-- Event Radius Filter (posts are a fixed 10km, not adjustable) --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-400 mb-1">
                            Event radius: <span class="text-cyan-400">{{ $eventRadiusKm }}km</span>
                        </label>
                        <input
                            type="range"
                            wire:model.live.debounce.150ms="eventRadiusKm"
                            min="10"
                            max="50"
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
                    <x-post-card-compact :post="$item" />
                @else
                    <x-event-card-compact :event="$item" />
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
                            Showing {{ count($items) }} items
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

    {{-- Invite Friends Modals --}}
    <livewire:posts.invite-friends-modal />
    <livewire:activities.invite-friends-modal />
</div>
