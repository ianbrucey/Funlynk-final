<div class="min-h-screen py-4 sm:py-8 px-3 sm:px-4 bg-slate-900">
    <div class="max-w-4xl mx-auto space-y-4 sm:space-y-6">
        <!-- Header -->
        <div class="text-center">
            <h1 class="text-2xl sm:text-3xl font-bold text-white">My Tickets</h1>
            <p class="text-gray-400 text-sm sm:text-base mt-1">View all your event tickets in one place</p>
        </div>

        <!-- Tab Navigation -->
        <div class="bg-slate-800/70 rounded-2xl border border-white/10 p-1 flex gap-1">
            <button wire:click="switchTab('upcoming')"
                    class="flex-1 px-4 py-2.5 rounded-xl font-medium transition-all text-sm {{ $activeTab === 'upcoming'
                        ? 'bg-gradient-to-r from-pink-500 to-purple-500 text-white'
                        : 'bg-transparent text-gray-400 hover:text-white' }}">
                Upcoming ({{ $this->upcomingTickets->count() }})
            </button>
            <button wire:click="switchTab('past')"
                    class="flex-1 px-4 py-2.5 rounded-xl font-medium transition-all text-sm {{ $activeTab === 'past'
                        ? 'bg-gradient-to-r from-pink-500 to-purple-500 text-white'
                        : 'bg-transparent text-gray-400 hover:text-white' }}">
                Past ({{ $this->pastTickets->count() }})
            </button>
        </div>

        <!-- Tickets List -->
        <div class="space-y-3">
            @php
                $paginationData = $this->getPaginatedTickets();
                $tickets = $paginationData['tickets'];
            @endphp

            @forelse($tickets as $ticket)
                <div class="bg-slate-800/70 border border-white/10 rounded-xl p-4 sm:p-6 hover:border-cyan-500/30 transition-all">
                    <div class="flex flex-col sm:flex-row gap-4 sm:gap-6">
                        <!-- QR Code -->
                        <div class="flex-shrink-0 mx-auto sm:mx-0">
                            <div class="bg-white p-3 rounded-xl inline-block">
                                {!! $ticket->qr_code_svg !!}
                            </div>
                        </div>

                        <!-- Event Details -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between gap-3 mb-3">
                                <h3 class="text-lg sm:text-xl font-semibold text-white">
                                    {{ $ticket->activity->title }}
                                </h3>
                                @if($ticket->rsvp->checked_in_at)
                                    <span class="px-2 py-1 bg-green-500/20 text-green-400 text-xs rounded-full flex-shrink-0">
                                        ✓ Checked In
                                    </span>
                                @elseif($activeTab === 'upcoming')
                                    <span class="px-2 py-1 bg-cyan-500/20 text-cyan-400 text-xs rounded-full flex-shrink-0">
                                        Active
                                    </span>
                                @endif
                            </div>

                            <div class="space-y-2 text-sm text-gray-400 mb-4">
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    <span>{{ $ticket->activity->start_time->format('l, M j, Y • g:i A') }}</span>
                                </div>
                                @if($ticket->activity->location_name)
                                    <div class="flex items-center gap-2">
                                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                        <span class="truncate">{{ $ticket->activity->location_name }}</span>
                                    </div>
                                @endif
                            </div>

                            <!-- Check-in Code -->
                            <div class="bg-slate-700/50 rounded-lg p-3 mb-4">
                                <p class="text-xs text-gray-500 mb-1">Check-in Code</p>
                                <div class="text-xl font-mono font-bold tracking-widest text-cyan-400">
                                    {{ $ticket->rsvp->check_in_code ?? 'N/A' }}
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex flex-col sm:flex-row gap-2">
                                <a href="{{ route('events.show', $ticket->activity->id) }}"
                                   class="flex-1 px-4 py-2 bg-slate-700/50 border border-white/10 rounded-lg text-white text-sm text-center hover:border-cyan-500/50 transition">
                                    View Event Details
                                </a>
                                @if($activeTab === 'upcoming')
                                    <a href="{{ route('events.my-ticket', $ticket->activity->id) }}"
                                       class="flex-1 px-4 py-2 bg-gradient-to-r from-cyan-500 to-blue-500 rounded-lg text-white text-sm font-semibold text-center hover:scale-105 transition-all">
                                        Full Screen Ticket
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="bg-slate-800/70 border border-white/10 rounded-xl p-8 sm:p-12 text-center">
                    <div class="text-5xl sm:text-6xl mb-4">🎫</div>
                    <h3 class="text-lg sm:text-xl font-semibold text-white mb-2">
                        @if($activeTab === 'upcoming')
                            No Upcoming Tickets
                        @else
                            No Past Tickets
                        @endif
                    </h3>
                    <p class="text-gray-400 text-sm sm:text-base mb-6">
                        @if($activeTab === 'upcoming')
                            You don't have any upcoming event tickets. Start exploring events!
                        @else
                            You haven't attended any events yet.
                        @endif
                    </p>
                    @if($activeTab === 'upcoming')
                        <a href="{{ route('feed.nearby') }}"
                           class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-pink-500 to-purple-500 rounded-xl font-semibold hover:scale-105 transition-all text-white">
                            Discover Events
                        </a>
                    @endif
                </div>
            @endforelse

            <!-- Pagination Controls -->
            @if($paginationData['totalPages'] > 1)
                <div class="flex items-center justify-center gap-2 mt-8 pt-6 border-t border-white/10">
                    <!-- Previous Button -->
                    @if($paginationData['currentPage'] > 1)
                        <a href="?page={{ $paginationData['currentPage'] - 1 }}"
                           class="px-4 py-2 bg-slate-700/50 border border-white/10 rounded-lg text-white text-sm hover:border-cyan-500/50 transition">
                            ← Previous
                        </a>
                    @else
                        <button disabled class="px-4 py-2 bg-slate-700/30 border border-white/5 rounded-lg text-gray-500 text-sm cursor-not-allowed">
                            ← Previous
                        </button>
                    @endif

                    <!-- Page Info -->
                    <span class="text-gray-400 text-sm px-4">
                        Page {{ $paginationData['currentPage'] }} of {{ $paginationData['totalPages'] }}
                    </span>

                    <!-- Next Button -->
                    @if($paginationData['currentPage'] < $paginationData['totalPages'])
                        <a href="?page={{ $paginationData['currentPage'] + 1 }}"
                           class="px-4 py-2 bg-slate-700/50 border border-white/10 rounded-lg text-white text-sm hover:border-cyan-500/50 transition">
                            Next →
                        </a>
                    @else
                        <button disabled class="px-4 py-2 bg-slate-700/30 border border-white/5 rounded-lg text-gray-500 text-sm cursor-not-allowed">
                            Next →
                        </button>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>

