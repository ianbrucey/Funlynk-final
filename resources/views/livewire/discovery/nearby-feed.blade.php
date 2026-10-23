<div class="min-h-screen">
    <x-fl.follow-modals />
    <div class="container mx-auto ">

        

        {{-- Filters --}}
        <div class=" lg:px-0 mb-6">

            


            <div class="relative p-4 glass-card lg:rounded-xl">

                {{-- Nearby | Following switch (spec 010) --}}
                <div class="grid grid-cols-2 gap-1 p-1 mb-3 rounded-2xl bg-black/30 border border-white/10" role="tablist">
                    @foreach(['nearby' => 'Nearby', 'following' => 'Following'] as $value => $label)
                        <button type="button" role="tab" aria-selected="{{ $feed === $value ? 'true' : 'false' }}"
                                wire:click="setFeed('{{ $value }}')" wire:key="feed-{{ $value }}"
                                class="min-h-[44px] rounded-xl text-sm font-semibold transition {{ $feed === $value ? 'bg-gradient-to-r from-pink-500 to-purple-500 text-white' : 'text-gray-400 hover:text-gray-200' }}">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>

                <div x-data="{ open: false }">
                    {{-- Search row: one line, filters behind the gear --}}
                    <div class="flex gap-2">
                        <div class="relative flex-1">
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
                                class="w-full pl-12 pr-12 py-3 bg-slate-800/50 border border-white/10 rounded-2xl text-white placeholder-gray-400 focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/50 transition"
                            >
                            @if($searchQuery)
                                <button wire:click="clearSearch" type="button" aria-label="Clear search" class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-white transition">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            @endif
                        </div>
                        <button type="button" @click="open = !open" aria-label="Filters"
                            :class="open || {{ $this->filtersActive ? 'true' : 'false' }} ? 'border-cyan-400 text-cyan-400' : 'border-white/15 text-white'"
                            class="w-12 shrink-0 rounded-xl border bg-white/5 transition grid place-items-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h18M6 12h12M10 20h4"/>
                            </svg>
                        </button>
                    </div>

                    {{-- Content type chips --}}
                    <div class="flex gap-2 mt-3">
                        @foreach(['all' => 'All', 'posts' => 'Posts', 'events' => 'Events'] as $value => $label)
                            <button type="button" wire:click="$set('contentType', '{{ $value }}')" wire:key="chip-{{ $value }}"
                                class="px-4 py-1.5 rounded-full text-sm border transition {{ $contentType === $value ? 'bg-gradient-to-r from-pink-500 to-purple-500 border-transparent text-white' : 'bg-white/5 border-white/10 text-gray-300' }}">
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>

                    @if($feed === 'following')
                        <p class="mt-3 text-xs text-gray-400">People you follow · within {{ \App\Services\FeedService::FOLLOWING_RADIUS_KM }} km of you</p>
                    @endif

                    @if($searchQuery)
                        <p class="mt-3 text-sm text-gray-400">
                            Showing results for "<span class="text-cyan-400">{{ $searchQuery }}</span>"
                        </p>
                    @endif

                    {{-- Collapsible filter panel --}}
                    <div x-show="open" x-cloak x-collapse class="mt-3 pt-3 border-t border-white/10">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-400 mb-1">When</label>
                                <select wire:model.live="timeFilter" class="w-full px-3 py-2 bg-slate-800/50 border border-white/10 rounded-lg text-white text-sm focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/50 transition">
                                    <option value="all">Anytime</option>
                                    <option value="today">Today</option>
                                    <option value="week">This Week</option>
                                    <option value="month">This Month</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-400 mb-1">
                                    Event radius: <span class="text-cyan-400">{{ $eventRadiusMiles }} mi</span>
                                </label>
                                <input type="range" wire:model.live.debounce.150ms="eventRadiusMiles" min="6" max="31" step="1"
                                    class="w-full h-2 bg-slate-700 rounded-lg appearance-none cursor-pointer accent-cyan-500">
                            </div>
                        </div>
                        <p class="mt-2 text-xs text-cyan-300">Posts: 6 mi · Events: up to {{ $eventRadiusMiles }} mi</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Feed Content - 3 Grid Layout --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4" wire:loading.class="opacity-50">
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
                        @elseif($feed === 'following' && ! $followsAnyone)
                            <h3 class="text-xl font-bold mb-2 text-white">You are not following anyone yet</h3>
                            <p class="text-gray-400 mb-6 max-w-sm mx-auto">Follow hosts to see their posts and events here, and get notified when they publish an event.</p>
                            <a href="{{ route('search.users') }}" wire:navigate
                               class="inline-block px-6 py-3 bg-gradient-to-r from-pink-500 to-purple-500 rounded-xl font-semibold hover:scale-105 transition-all">
                                Find people
                            </a>
                        @elseif($feed === 'following')
                            <h3 class="text-xl font-bold mb-2 text-white">Nothing new from people you follow</h3>
                            <p class="text-gray-400 mb-6">When they post or publish an event, it will show up here.</p>
                            <button type="button" wire:click="setFeed('nearby')"
                               class="inline-block px-6 py-3 bg-gradient-to-r from-pink-500 to-purple-500 rounded-xl font-semibold hover:scale-105 transition-all">
                                See what is nearby
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
