<div class="min-h-screen py-4 sm:py-8 px-3 sm:px-4">
    <div class="max-w-6xl mx-auto space-y-4 sm:space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4">
            <div class="text-center sm:text-left">
                <h1 class="text-2xl sm:text-3xl font-bold text-white">My Events</h1>
                <p class="text-gray-400 text-sm sm:text-base mt-1">Manage your hosted events and attendees</p>
            </div>
            <a href="{{ route('activities.create') }}"
               class="w-full sm:w-auto px-6 py-3 bg-gradient-to-r from-pink-500 to-purple-500 rounded-xl font-semibold hover:scale-105 transition-all text-white text-center inline-flex items-center justify-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Create Event
            </a>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 sm:gap-4">
            <div class="glass-card p-3 sm:p-5 text-center">
                <div class="text-2xl sm:text-3xl font-bold text-cyan-400">{{ $stats['upcoming'] }}</div>
                <div class="text-gray-400 text-xs sm:text-sm mt-1">Upcoming</div>
            </div>
            <div class="glass-card p-3 sm:p-5 text-center">
                <div class="text-2xl sm:text-3xl font-bold text-pink-400">{{ $stats['total_attendees'] }}</div>
                <div class="text-gray-400 text-xs sm:text-sm mt-1">Attendees</div>
            </div>
            <div class="glass-card p-3 sm:p-5 text-center">
                <div class="text-2xl sm:text-3xl font-bold text-purple-400">{{ $stats['past'] }}</div>
                <div class="text-gray-400 text-xs sm:text-sm mt-1">Past</div>
            </div>
        </div>

        <!-- Filters & Search -->
        <div class="glass-card p-3 sm:p-4">
            <div class="flex flex-col gap-3 sm:gap-4">
                <!-- Search (moved to top on mobile for easier access) -->
                <div class="order-first sm:order-last sm:flex-1">
                    <input type="text"
                           wire:model.live.debounce.300ms="search"
                           placeholder="Search events..."
                           class="w-full px-4 py-2.5 sm:py-2 bg-slate-800/50 border border-white/10 rounded-xl text-white placeholder-gray-500 focus:border-cyan-500 focus:outline-none text-sm sm:text-base">
                </div>

                <!-- Filter Tabs -->
                <div class="flex gap-2 overflow-x-auto pb-1 sm:pb-0 scrollbar-hide">
                    @foreach(['upcoming' => 'Upcoming', 'past' => 'Past', 'all' => 'All'] as $key => $label)
                        <button wire:click="setFilter('{{ $key }}')"
                                class="px-3 sm:px-4 py-2 rounded-lg font-medium transition-all whitespace-nowrap text-sm sm:text-base flex-shrink-0 {{ $filter === $key
                                    ? 'bg-gradient-to-r from-pink-500 to-purple-500 text-white'
                                    : 'bg-slate-800/50 text-gray-400 hover:text-white hover:bg-slate-700/50' }}">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Events List -->
        <div class="space-y-3 sm:space-y-4">
            @forelse($events as $event)
                @php
                    $eventStats = $this->getEventStats($event);
                    $isPast = $event->start_time <= now();
                    $isDraft = $event->status === 'draft';
                @endphp
                <div class="glass-card p-4 sm:p-6 hover:border-cyan-500/30 transition-all">
                    <!-- Mobile Layout -->
                    <div class="sm:hidden">
                        <!-- Title & Status -->
                        <div class="flex items-start justify-between gap-2 mb-3">
                            <h3 class="text-lg font-semibold text-white line-clamp-2">{{ $event->title }}</h3>
                            @if($isDraft)
                                <span class="px-2 py-1 bg-yellow-500/20 text-yellow-400 text-xs rounded-full flex-shrink-0">Draft</span>
                            @elseif($isPast)
                                <span class="px-2 py-1 bg-gray-500/20 text-gray-400 text-xs rounded-full flex-shrink-0">Past</span>
                            @else
                                <span class="px-2 py-1 bg-green-500/20 text-green-400 text-xs rounded-full flex-shrink-0">Active</span>
                            @endif
                        </div>

                        <!-- Date & Location -->
                        <div class="space-y-1.5 text-sm text-gray-400 mb-4">
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <span>{{ $event->start_time->format('M j, Y • g:i A') }}</span>
                            </div>
                            @if($event->location_name)
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    </svg>
                                    <span class="truncate">{{ $event->location_name }}</span>
                                </div>
                            @endif
                        </div>

                        <!-- Stats Row -->
                        <div class="flex items-center gap-4 mb-4 pb-4 border-b border-white/5">
                            <div class="text-center">
                                <div class="text-xl font-bold text-cyan-400">{{ $eventStats['total_rsvps'] }}</div>
                                <div class="text-xs text-gray-500">RSVPs</div>
                            </div>
                            @if(!$isDraft)
                                <div class="text-center">
                                    <div class="text-xl font-bold text-green-400">{{ $eventStats['checked_in_count'] }}</div>
                                    <div class="text-xs text-gray-500">Checked In</div>
                                </div>
                                @if($eventStats['total_rsvps'] > 0)
                                    <div class="flex-1">
                                        <div class="h-2 bg-slate-700 rounded-full overflow-hidden">
                                            <div class="h-full bg-gradient-to-r from-cyan-500 to-green-500 rounded-full"
                                                 style="width: {{ $eventStats['check_in_percentage'] }}%"></div>
                                        </div>
                                        <div class="text-xs text-gray-500 text-center mt-1">{{ $eventStats['check_in_percentage'] }}% checked in</div>
                                    </div>
                                @endif
                            @endif
                        </div>

                        <!-- Actions -->
                        <div class="flex flex-col gap-2">
                            @if(!$isDraft && $eventStats['total_rsvps'] > 0)
                                <a href="{{ route('activities.attendees', $event) }}"
                                   class="w-full px-4 py-2.5 bg-gradient-to-r from-cyan-500 to-blue-500 rounded-lg text-white text-sm font-semibold text-center">
                                    Manage Attendees
                                </a>
                            @endif
                            <div class="flex gap-2">
                                <a href="{{ route('activities.show', $event) }}"
                                   class="flex-1 px-4 py-2 bg-slate-800/50 border border-white/10 rounded-lg text-white text-sm text-center hover:border-cyan-500/50 transition">
                                    View
                                </a>
                                @if(!$isPast)
                                    <a href="{{ route('activities.edit', $event) }}"
                                       class="flex-1 px-4 py-2 bg-slate-800/50 border border-white/10 rounded-lg text-white text-sm text-center hover:border-cyan-500/50 transition">
                                        Edit
                                    </a>
                                @endif
                            </div>
                        </div>

                        <!-- Tags -->
                        @if($event->tags->count() > 0)
                            <div class="flex flex-wrap gap-1.5 mt-3 pt-3 border-t border-white/5">
                                @foreach($event->tags->take(3) as $tag)
                                    <span class="px-2 py-0.5 bg-purple-500/20 text-purple-300 text-xs rounded-full">
                                        {{ $tag->name }}
                                    </span>
                                @endforeach
                                @if($event->tags->count() > 3)
                                    <span class="text-xs text-gray-500">+{{ $event->tags->count() - 3 }}</span>
                                @endif
                            </div>
                        @endif
                    </div>

                    <!-- Desktop Layout -->
                    <div class="hidden sm:block">
                        <div class="flex flex-col lg:flex-row lg:items-center gap-4">
                            <!-- Event Info -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-3 mb-2">
                                    <h3 class="text-xl font-semibold text-white truncate">{{ $event->title }}</h3>
                                    @if($isDraft)
                                        <span class="px-2 py-1 bg-yellow-500/20 text-yellow-400 text-xs rounded-full">Draft</span>
                                    @elseif($isPast)
                                        <span class="px-2 py-1 bg-gray-500/20 text-gray-400 text-xs rounded-full">Past</span>
                                    @else
                                        <span class="px-2 py-1 bg-green-500/20 text-green-400 text-xs rounded-full">Active</span>
                                    @endif
                                </div>

                                <div class="flex flex-wrap items-center gap-4 text-sm text-gray-400">
                                    <span class="flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                        {{ $event->start_time->format('M j, Y • g:i A') }}
                                    </span>
                                    @if($event->location_name)
                                        <span class="flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            </svg>
                                            {{ Str::limit($event->location_name, 30) }}
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <!-- Attendee Stats -->
                            <div class="flex items-center gap-6">
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-cyan-400">{{ $eventStats['total_rsvps'] }}</div>
                                    <div class="text-xs text-gray-500">RSVPs</div>
                                </div>
                                @if(!$isDraft)
                                    <div class="text-center">
                                        <div class="text-2xl font-bold text-green-400">{{ $eventStats['checked_in_count'] }}</div>
                                        <div class="text-xs text-gray-500">Checked In</div>
                                    </div>
                                    @if($eventStats['total_rsvps'] > 0)
                                        <div class="w-16">
                                            <div class="h-2 bg-slate-700 rounded-full overflow-hidden">
                                                <div class="h-full bg-gradient-to-r from-cyan-500 to-green-500 rounded-full transition-all"
                                                     style="width: {{ $eventStats['check_in_percentage'] }}%"></div>
                                            </div>
                                            <div class="text-xs text-gray-500 text-center mt-1">{{ $eventStats['check_in_percentage'] }}%</div>
                                        </div>
                                    @endif
                                @endif
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center gap-2 flex-shrink-0">
                                <a href="{{ route('activities.show', $event) }}"
                                   class="px-4 py-2 bg-slate-800/50 border border-white/10 rounded-lg text-white text-sm hover:border-cyan-500/50 transition">
                                    View
                                </a>
                                @if(!$isPast)
                                    <a href="{{ route('activities.edit', $event) }}"
                                       class="px-4 py-2 bg-slate-800/50 border border-white/10 rounded-lg text-white text-sm hover:border-cyan-500/50 transition">
                                        Edit
                                    </a>
                                @endif
                                @if(!$isDraft && $eventStats['total_rsvps'] > 0)
                                    <a href="{{ route('activities.attendees', $event) }}"
                                       class="px-4 py-2 bg-gradient-to-r from-cyan-500 to-blue-500 rounded-lg text-white text-sm font-semibold hover:scale-105 transition-all">
                                        Manage Attendees
                                    </a>
                                @endif
                            </div>
                        </div>

                        <!-- Tags -->
                        @if($event->tags->count() > 0)
                            <div class="flex flex-wrap gap-2 mt-4 pt-4 border-t border-white/5">
                                @foreach($event->tags->take(5) as $tag)
                                    <span class="px-2 py-1 bg-purple-500/20 text-purple-300 text-xs rounded-full">
                                        {{ $tag->name }}
                                    </span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="glass-card p-8 sm:p-12 text-center">
                    <div class="text-5xl sm:text-6xl mb-4">📅</div>
                    <h3 class="text-lg sm:text-xl font-semibold text-white mb-2">No events found</h3>
                    <p class="text-gray-400 text-sm sm:text-base mb-6">
                        @if($search)
                            No events match "{{ $search }}"
                        @elseif($filter === 'upcoming')
                            You don't have any upcoming events
                        @elseif($filter === 'past')
                            You don't have any past events
                        @else
                            Start hosting events to see them here
                        @endif
                    </p>
                    @if($stats['upcoming'] + $stats['past'] === 0)
                        <a href="{{ route('activities.create') }}"
                           class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-pink-500 to-purple-500 rounded-xl font-semibold hover:scale-105 transition-all text-white text-sm sm:text-base">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Create Your First Event
                        </a>
                    @endif
                </div>
            @endforelse
        </div>
    </div>
</div>
