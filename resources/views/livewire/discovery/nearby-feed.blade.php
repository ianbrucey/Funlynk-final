<div class="min-h-screen py-12">
    <div class="container mx-auto lg:px-6">

        {{-- Header --}}
        <div class="px-6 lg:px-0 mb-8">
            <h1 class="text-4xl font-bold mb-2 text-white">Nearby Feed</h1>
            <p class="text-gray-400">Discover spontaneous activities and events happening around you</p>
        </div>

        {{-- Filters --}}
        <div class="px-6 lg:px-0 mb-6">
            <div class="relative p-6 glass-card lg:rounded-xl">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

                    {{-- Content Type Filter --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-400 mb-2">Show</label>
                        <select wire:model.live="contentType" class="w-full px-4 py-2 bg-slate-800/50 border border-white/10 rounded-lg text-white focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/50 transition">
                            <option value="all">All Content</option>
                            <option value="posts">Posts Only</option>
                            <option value="events">Events Only</option>
                        </select>
                    </div>

                    {{-- Distance Filter --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-400 mb-2">
                            Distance: {{ $radius }} km
                        </label>
                        <input type="range" wire:model.live="radius" min="1" max="50" class="w-full h-2 bg-slate-800/50 rounded-lg appearance-none cursor-pointer accent-cyan-500">
                    </div>

                    {{-- Time Filter --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-400 mb-2">When</label>
                        <select wire:model.live="timeFilter" class="w-full px-4 py-2 bg-slate-800/50 border border-white/10 rounded-lg text-white focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/50 transition">
                            <option value="all">Anytime</option>
                            <option value="today">Today</option>
                            <option value="week">This Week</option>
                            <option value="month">This Month</option>
                        </select>
                    </div>

                </div>
            </div>
        </div>

        {{-- Feed Content --}}
        <div class="space-y-6">
            @forelse($items as $item)
                @if($item['type'] === 'post')
                    {{-- Post Card --}}
                    <div class="px-6 lg:px-0">
                        <x-post-card :post="$item['data']" />
                    </div>
                @else
                    {{-- Event Card --}}
                    <div class="px-6 lg:px-0">
                        <div class="relative p-6 glass-card lg:rounded-xl border-l-4 border-cyan-500 hover:border-blue-500 transition-all">

                            {{-- Converted Badge --}}
                            @if($item['data']->originated_from_post_id)
                                <div class="absolute top-4 right-4">
                                    <span class="px-3 py-1 bg-purple-500/30 border border-purple-500/50 rounded-full text-xs font-bold text-purple-300 uppercase tracking-wider">
                                        ⭐ Converted from Post
                                    </span>
                                </div>
                            @endif

                            {{-- Content --}}
                            <div class="pr-32">
                                <h3 class="text-xl font-bold mb-2 text-white">{{ $item['data']->title }}</h3>
                                @if($item['data']->description)
                                    <p class="text-gray-400 text-sm mb-4 line-clamp-2">{{ $item['data']->description }}</p>
                                @endif
                            </div>

                            {{-- Event Details --}}
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                                <div>
                                    <div class="text-xs text-gray-400 mb-1">When</div>
                                    <div class="text-sm font-semibold text-white">{{ $item['data']->start_time->format('M j, g:i A') }}</div>
                                </div>
                                <div>
                                    <div class="text-xs text-gray-400 mb-1">Location</div>
                                    <div class="text-sm font-semibold text-white">{{ $item['data']->location_name }}</div>
                                </div>
                                <div>
                                    <div class="text-xs text-gray-400 mb-1">Price</div>
                                    <div class="text-sm font-semibold text-white">
                                        @if($item['data']->is_paid)
                                            ${{ number_format($item['data']->price, 2) }}
                                        @else
                                            Free
                                        @endif
                                    </div>
                                </div>
                                <div>
                                    <div class="text-xs text-gray-400 mb-1">Availability</div>
                                    <div class="text-sm font-semibold text-white">
                                        {{ $item['data']->max_attendees - $item['data']->rsvps()->count() }} spots left
                                    </div>
                                </div>
                            </div>

                            {{-- Action Button --}}
                            <div class="flex items-center gap-3">
                                <a href="{{ route('activities.show', $item['data']) }}"
                                   class="flex-1 px-6 py-3 bg-gradient-to-r from-cyan-500 to-blue-500 rounded-xl font-semibold hover:scale-105 transition-all text-center">
                                    View Event Details
                                </a>
                            </div>
                        </div>
                    </div>
                @endif
            @empty
                {{-- Empty State --}}
                <div class="px-6 lg:px-0">
                    <div class="relative p-12 glass-card lg:rounded-xl text-center">
                        <div class="w-24 h-24 mx-auto mb-6 rounded-full bg-gradient-to-br from-pink-500 via-purple-500 to-cyan-500 flex items-center justify-center">
                            <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold mb-2 text-white">No activities found</h3>
                        <p class="text-gray-400 mb-6">Try adjusting your filters or check back later for new posts and events</p>
                        <a href="{{ route('activities.create') }}"
                           class="inline-block px-6 py-3 bg-gradient-to-r from-pink-500 to-purple-500 rounded-xl font-semibold hover:scale-105 transition-all">
                            Create a Post
                        </a>
                    </div>
                </div>
            @endforelse
        </div>

    </div>

    {{-- Invite Friends Modal --}}
    <livewire:posts.invite-friends-modal />
</div>
