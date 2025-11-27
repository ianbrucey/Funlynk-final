<div class="min-h-screen pb-24">
    <!-- Search Header -->
    <div class="sticky top-0 z-10 backdrop-blur-xl bg-slate-900/80 border-b border-white/10">
        <div class="container mx-auto px-6 py-4">
            <!-- Search Input -->
            <div class="relative">
                <input
                    type="text"
                    wire:model.live.debounce.500ms="query"
                    placeholder="Search posts and events..."
                    class="w-full px-6 py-4 pl-14 bg-slate-800/50 border border-white/10 rounded-2xl text-white placeholder-gray-400 focus:outline-none focus:border-cyan-500/50 focus:ring-2 focus:ring-cyan-500/20 transition-all"
                    autofocus
                >
                <svg class="absolute left-5 top-1/2 -translate-y-1/2 w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap gap-3 mt-4">
                <!-- Content Type Filter -->
                <div class="flex gap-2">
                    <button
                        wire:click="$set('contentType', 'all')"
                        class="px-4 py-2 rounded-xl text-sm font-medium transition-all {{ $contentType === 'all' ? 'bg-gradient-to-r from-pink-500 to-purple-500 text-white' : 'bg-slate-800/50 text-gray-300 hover:bg-slate-700/50' }}">
                        All
                    </button>
                    <button
                        wire:click="$set('contentType', 'posts')"
                        class="px-4 py-2 rounded-xl text-sm font-medium transition-all {{ $contentType === 'posts' ? 'bg-gradient-to-r from-pink-500 to-purple-500 text-white' : 'bg-slate-800/50 text-gray-300 hover:bg-slate-700/50' }}">
                        Posts
                    </button>
                    <button
                        wire:click="$set('contentType', 'events')"
                        class="px-4 py-2 rounded-xl text-sm font-medium transition-all {{ $contentType === 'events' ? 'bg-gradient-to-r from-cyan-500 to-blue-500 text-white' : 'bg-slate-800/50 text-gray-300 hover:bg-slate-700/50' }}">
                        Events
                    </button>
                </div>

                <!-- Geo Filter Toggle -->
                <div class="flex items-center gap-3 px-4 py-2 bg-slate-800/50 rounded-xl">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input
                            type="checkbox"
                            wire:model.live="useGeoFilter"
                            class="w-4 h-4 rounded border-gray-600 bg-slate-700 text-cyan-500 focus:ring-cyan-500/20"
                        >
                        <span class="text-sm text-gray-300">Near me</span>
                    </label>

                    @if($useGeoFilter)
                        <select
                            wire:model.live="radius"
                            class="px-3 py-1 bg-slate-700/50 border border-white/10 rounded-lg text-sm text-white focus:outline-none focus:border-cyan-500/50">
                            <option value="5">5 km</option>
                            <option value="10">10 km</option>
                            <option value="25">25 km</option>
                            <option value="50">50 km</option>
                        </select>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Results -->
    <div class="container mx-auto px-6 py-8">
        @if(empty(trim($query)))
            <!-- Empty State - No Query -->
            <div class="relative p-12 glass-card lg:rounded-xl text-center max-w-2xl mx-auto">
                <div class="top-accent-center"></div>
                <svg class="w-20 h-20 mx-auto mb-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <h2 class="text-2xl font-bold text-white mb-3">Search FunLynk</h2>
                <p class="text-gray-400 mb-6">Find spontaneous posts and events near you</p>

                <div class="flex flex-wrap gap-2 justify-center">
                    <span class="px-4 py-2 bg-slate-800/50 rounded-full text-sm text-gray-300">Basketball</span>
                    <span class="px-4 py-2 bg-slate-800/50 rounded-full text-sm text-gray-300">Coffee</span>
                    <span class="px-4 py-2 bg-slate-800/50 rounded-full text-sm text-gray-300">Hiking</span>
                    <span class="px-4 py-2 bg-slate-800/50 rounded-full text-sm text-gray-300">Music</span>
                </div>
            </div>
        @elseif($items->isEmpty())
            <!-- Empty State - No Results -->
            <div class="relative p-12 glass-card lg:rounded-xl text-center max-w-2xl mx-auto">
                <div class="top-accent-center"></div>
                <svg class="w-20 h-20 mx-auto mb-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <h2 class="text-2xl font-bold text-white mb-3">No results found</h2>
                <p class="text-gray-400 mb-6">Try adjusting your search or filters</p>

                <button
                    wire:click="$set('query', '')"
                    class="px-6 py-3 bg-gradient-to-r from-pink-500 to-purple-500 rounded-xl font-semibold hover:scale-105 transition-all">
                    Clear Search
                </button>
            </div>
        @else
            <!-- Results Count -->
            <div class="mb-6">
                <p class="text-gray-400">
                    Found <span class="text-white font-semibold">{{ $items->count() }}</span>
                    {{ $items->count() === 1 ? 'result' : 'results' }} for
                    <span class="text-cyan-400">"{{ $query }}"</span>
                </p>
            </div>

            <!-- Results Grid -->
            <div class="space-y-4">
                @foreach($items as $item)
                    @if($item['type'] === 'post')
                        <!-- Post Card -->
                        <x-post-card :post="$item['data']" />
                    @else
                        <!-- Event Card -->
                        <div class="relative p-6 glass-card lg:rounded-xl border-l-4 border-cyan-500 hover:border-blue-500 transition-all">
                            <div class="top-accent-left"></div>

                            <!-- Converted Badge -->
                            @if($item['data']->originated_from_post_id)
                                <div class="absolute top-4 right-4">
                                    <span class="px-3 py-1 bg-gradient-to-r from-purple-500 to-pink-500 rounded-full text-xs font-semibold">
                                        ✨ Converted from Post
                                    </span>
                                </div>
                            @endif

                            <!-- Event Content -->
                            <div class="mb-4">
                                <h3 class="text-xl font-bold text-white mb-2">{{ $item['data']->title }}</h3>
                                <p class="text-gray-300 line-clamp-2">{{ $item['data']->description }}</p>
                            </div>

            <!-- Event Meta -->
                            <div class="flex flex-wrap gap-4 text-sm text-gray-400 mb-4">
                                <span class="flex items-center gap-1">
                                    📍 {{ $item['data']->location_name }}
                                </span>
                                <span class="flex items-center gap-1">
                                    📅 {{ $item['data']->start_time->format('M j, g:i A') }}
                                </span>
                                @if($item['data']->is_paid)
                                    <span class="flex items-center gap-1">
                                        💰 ${{ number_format($item['data']->price_cents / 100, 2) }}
                                    </span>
                                @endif
                            </div>

                            <!-- Event Tags -->
                            @if($item['data']->tags && $item['data']->tags->isNotEmpty())
                                <div class="flex flex-wrap gap-2 mb-4">
                                    @foreach($item['data']->tags->take(3) as $tag)
                                        <span class="px-3 py-1 bg-cyan-500/10 border border-cyan-500/30 rounded-full text-xs text-cyan-400">
                                            {{ $tag->name }}
                                        </span>
                                    @endforeach
                                </div>
                            @endif

                            <!-- View Button -->
                            <a href="{{ route('activities.show', $item['data']->id) }}"
                               class="inline-block px-6 py-3 bg-gradient-to-r from-cyan-500 to-blue-500 rounded-xl font-semibold hover:scale-105 transition-all">
                                View Event
                            </a>
                        </div>
                    @endif
                @endforeach
            </div>
        @endif
    </div>
</div>
