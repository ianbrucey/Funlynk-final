<div class="min-h-screen py-12">
    <div class="container mx-auto lg:px-6">

        {{-- Header --}}
        <div class="px-6 lg:px-0 mb-8">
            <h1 class="text-4xl font-bold mb-2 text-white">For You</h1>
            <p class="text-gray-400">Personalized recommendations based on your interests and activity</p>
        </div>

        {{-- Feed Content --}}
        <div class="space-y-6">
            @forelse($items as $item)
                <div class="px-6 lg:px-0">
                    {{-- Recommendation Reason --}}
                    <div class="mb-2 flex items-center gap-2 text-sm text-cyan-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        <span>{{ $item['reason'] }}</span>
                    </div>

                    @if($item['type'] === 'post')
                        {{-- Post Card --}}
                        <x-post-card :post="$item['data']" />
                    @else
                        {{-- Event Card --}}
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
                    @endif
                </div>
            @empty
                {{-- Empty State --}}
                <div class="px-6 lg:px-0">
                    <div class="relative p-12 glass-card lg:rounded-xl text-center">
                        <div class="w-24 h-24 mx-auto mb-6 rounded-full bg-gradient-to-br from-pink-500 via-purple-500 to-cyan-500 flex items-center justify-center">
                            <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold mb-2 text-white">Help us personalize your feed</h3>
                        <p class="text-gray-400 mb-6">Complete your profile to get better recommendations based on your interests and location</p>
                        <div class="flex items-center justify-center gap-4">
                            <a href="{{ route('profile.edit') }}"
                               class="inline-block px-6 py-3 bg-gradient-to-r from-pink-500 to-purple-500 rounded-xl font-semibold hover:scale-105 transition-all">
                                Complete Profile
                            </a>
                            <a href="{{ route('feed.nearby') }}"
                               class="inline-block px-6 py-3 bg-slate-800/50 border border-white/10 rounded-xl hover:border-cyan-500/50 transition">
                                Browse Nearby
                            </a>
                        </div>
                    </div>
                </div>
            @endforelse
        </div>

    </div>
</div>
