<div class="min-h-screen py-8 px-4" wire:poll.10s="refreshList">
    <div class="max-w-5xl mx-auto space-y-6">
        <!-- Header -->
        <div class="text-center">
            <h1 class="text-2xl font-bold text-white mb-2">Manage Attendees</h1>
            <p class="text-gray-400">{{ $activity->title }}</p>
        </div>

        <!-- Success/Error Messages -->
        @if ($successMessage)
            <div class="glass-card p-4 border-l-4 border-green-500 bg-green-500/10">
                <p class="text-green-400 font-semibold">{{ $successMessage }}</p>
            </div>
        @endif

        @if ($errorMessage)
            <div class="glass-card p-4 border-l-4 border-red-500 bg-red-500/10">
                <p class="text-red-400 font-semibold">{{ $errorMessage }}</p>
            </div>
        @endif

        <!-- Stats Bar -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="glass-card p-4 text-center">
                <div class="text-2xl font-bold text-purple-400">{{ $stats['total_rsvps'] }}</div>
                <div class="text-xs text-gray-400">Total RSVPs</div>
            </div>
            <div class="glass-card p-4 text-center">
                <div class="text-2xl font-bold text-green-400">{{ $stats['checked_in_count'] }}</div>
                <div class="text-xs text-gray-400">Checked In</div>
            </div>
            <div class="glass-card p-4 text-center">
                <div class="text-2xl font-bold text-yellow-400">{{ $stats['pending_count'] }}</div>
                <div class="text-xs text-gray-400">Pending</div>
            </div>
            <div class="glass-card p-4 text-center">
                <div class="text-2xl font-bold text-cyan-400">{{ $stats['check_in_percentage'] }}%</div>
                <div class="text-xs text-gray-400">Complete</div>
            </div>
        </div>

        <!-- Manual Code Entry & QR Scanner Link -->
        <div class="grid md:grid-cols-2 gap-4">
            <div class="glass-card p-6">
                <h3 class="text-lg font-semibold text-white mb-4">Check-in by Code</h3>
                <div class="flex gap-2">
                    <input type="text" wire:model="manualCode" placeholder="Enter 6-digit code" maxlength="6"
                           class="flex-1 px-4 py-3 bg-slate-800/50 border border-white/10 rounded-xl text-white placeholder-gray-500 focus:border-cyan-500 focus:outline-none uppercase tracking-widest font-mono" />
                    <button wire:click="checkInByCode" class="px-6 py-3 bg-gradient-to-r from-pink-500 to-purple-500 rounded-xl font-semibold hover:scale-105 transition-all text-white">
                        Check In
                    </button>
                </div>
            </div>
            <div class="glass-card p-6 flex items-center justify-center">
                <a href="{{ route('activities.scan', $activity) }}"
                   class="px-6 py-3 bg-gradient-to-r from-cyan-500 to-blue-500 rounded-xl font-semibold hover:scale-105 transition-all text-white text-center">
                    📷 Open QR Scanner
                </a>
            </div>
        </div>

        <!-- Search Input -->
        <div class="glass-card p-4">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search attendees by name or email..."
                   class="w-full px-4 py-3 bg-slate-800/50 border border-white/10 rounded-xl text-white placeholder-gray-500 focus:border-cyan-500 focus:outline-none" />
        </div>

        <!-- Attendee List -->
        <div class="glass-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-slate-800/70 border-b border-white/10">
                            <th class="text-left text-white text-sm font-semibold px-4 py-3">Attendee</th>
                            <th class="text-left text-white text-sm font-semibold px-4 py-3">Status</th>
                            <th class="text-left text-white text-sm font-semibold px-4 py-3 hidden md:table-cell">Check-in Time</th>
                            <th class="text-right text-white text-sm font-semibold px-4 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @forelse ($this->filteredRsvps as $rsvp)
                            <tr class="hover:bg-slate-800/30 transition-colors">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-pink-500 to-purple-500 flex items-center justify-center text-white font-semibold">
                                            {{ strtoupper(substr($rsvp->user->name ?? 'U', 0, 1)) }}
                                        </div>
                                        <div>
                                            <div class="font-semibold text-white">{{ $rsvp->user->name ?? 'Unknown' }}</div>
                                            <div class="text-sm text-gray-400">{{ $rsvp->user->email ?? '' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    @if ($rsvp->checked_in_at)
                                        <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium bg-green-500/20 text-green-400">
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                            Checked In
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium bg-yellow-500/20 text-yellow-400">
                                            Pending
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-400 text-sm hidden md:table-cell">
                                    {{ $rsvp->checked_in_at?->format('M j, g:i A') ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    @if (!$rsvp->checked_in_at)
                                        <button wire:click="manualCheckIn('{{ $rsvp->id }}')"
                                                class="px-4 py-2 bg-gradient-to-r from-pink-500 to-purple-500 rounded-lg font-semibold text-sm hover:scale-105 transition-all text-white">
                                            Check In
                                        </button>
                                    @else
                                        <span class="text-gray-500 text-sm">✓</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-12 text-gray-400">
                                    @if($search)
                                        No attendees match "{{ $search }}"
                                    @else
                                        No attendees yet
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Back to Event -->
        <div class="text-center">
            <a href="{{ route('activities.show', $activity) }}"
               class="px-6 py-3 bg-slate-800/50 border border-white/10 rounded-xl hover:border-cyan-500/50 transition inline-block text-white">
                ← Back to Event
            </a>
        </div>
    </div>
</div>
