<div class="min-h-screen py-4 sm:py-8 px-3 sm:px-4" wire:poll.10s="refreshList">
    <div class="max-w-5xl mx-auto space-y-4 sm:space-y-6">
        <!-- Header -->
        <div class="text-center px-2">
            <h1 class="text-xl sm:text-2xl font-bold text-white mb-1 sm:mb-2">Manage Attendees</h1>
            <p class="text-gray-400 text-sm sm:text-base truncate">{{ $activity->title }}</p>
        </div>

        <!-- Success/Error Messages -->
        @if ($successMessage)
            <div class="glass-card p-3 sm:p-4 border-l-4 border-green-500 bg-green-500/10">
                <p class="text-green-400 font-semibold text-sm sm:text-base">{{ $successMessage }}</p>
            </div>
        @endif

        @if ($errorMessage)
            <div class="glass-card p-3 sm:p-4 border-l-4 border-red-500 bg-red-500/10">
                <p class="text-red-400 font-semibold text-sm sm:text-base">{{ $errorMessage }}</p>
            </div>
        @endif

        <!-- Stats Bar -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 sm:gap-4">
            <div class="glass-card p-3 sm:p-4 text-center">
                <div class="text-xl sm:text-2xl font-bold text-purple-400">{{ $stats['total_rsvps'] }}</div>
                <div class="text-xs text-gray-400">Total RSVPs</div>
            </div>
            <div class="glass-card p-3 sm:p-4 text-center">
                <div class="text-xl sm:text-2xl font-bold text-green-400">{{ $stats['checked_in_count'] }}</div>
                <div class="text-xs text-gray-400">Checked In</div>
            </div>
            <div class="glass-card p-3 sm:p-4 text-center">
                <div class="text-xl sm:text-2xl font-bold text-yellow-400">{{ $stats['pending_count'] }}</div>
                <div class="text-xs text-gray-400">Pending</div>
            </div>
            <div class="glass-card p-3 sm:p-4 text-center">
                <div class="text-xl sm:text-2xl font-bold text-cyan-400">{{ $stats['check_in_percentage'] }}%</div>
                <div class="text-xs text-gray-400">Complete</div>
            </div>
        </div>

        <!-- Quick Actions: QR Scanner (prominent on mobile) -->
        <div class="glass-card p-4 sm:hidden">
            <a href="{{ route('activities.scan', $activity) }}"
               class="w-full px-6 py-4 bg-gradient-to-r from-cyan-500 to-blue-500 rounded-xl font-semibold hover:scale-105 transition-all text-white text-center flex items-center justify-center gap-2 text-lg">
                📷 Scan QR Code
            </a>
        </div>

        <!-- Manual Code Entry & QR Scanner Link -->
        <div class="glass-card p-4 sm:p-6">
            <h3 class="text-base sm:text-lg font-semibold text-white mb-3 sm:mb-4">Check-in by Code</h3>
            <div class="flex flex-col sm:flex-row gap-2 sm:gap-3">
                <input type="text"
                       wire:model="manualCode"
                       wire:keydown.enter="checkInByCode"
                       placeholder="ENTER 6-DIGIT CODE"
                       maxlength="6"
                       inputmode="numeric"
                       class="w-full sm:flex-1 px-4 py-3 bg-slate-800/50 border border-white/10 rounded-xl text-white placeholder-gray-500 focus:border-cyan-500 focus:outline-none uppercase tracking-widest font-mono text-center sm:text-left text-lg" />
                <button wire:click="checkInByCode"
                        wire:loading.attr="disabled"
                        class="w-full sm:w-auto px-6 py-3 bg-gradient-to-r from-pink-500 to-purple-500 rounded-xl font-semibold hover:scale-105 transition-all text-white disabled:opacity-50">
                    <span wire:loading.remove wire:target="checkInByCode">Check In</span>
                    <span wire:loading wire:target="checkInByCode">Checking...</span>
                </button>
            </div>
        </div>

        <!-- QR Scanner Link (desktop only - mobile has prominent button above) -->
        <div class="hidden sm:block glass-card p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-white">QR Scanner</h3>
                    <p class="text-sm text-gray-400">Scan attendee tickets for quick check-in</p>
                </div>
                <a href="{{ route('activities.scan', $activity) }}"
                   class="px-6 py-3 bg-gradient-to-r from-cyan-500 to-blue-500 rounded-xl font-semibold hover:scale-105 transition-all text-white text-center">
                    📷 Open Scanner
                </a>
            </div>
        </div>

        <!-- Search Input -->
        <div class="glass-card p-3 sm:p-4">
            <input type="text"
                   wire:model.live.debounce.300ms="search"
                   placeholder="Search attendees..."
                   class="w-full px-4 py-3 bg-slate-800/50 border border-white/10 rounded-xl text-white placeholder-gray-500 focus:border-cyan-500 focus:outline-none text-sm sm:text-base" />
        </div>

        <!-- Attendee List - Mobile Card View -->
        <div class="sm:hidden space-y-3">
            @forelse ($this->filteredRsvps as $rsvp)
                <div class="glass-card p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-3 flex-1 min-w-0">
                            @if($rsvp->user->profile_image_url)
                                <img src="{{ Storage::url($rsvp->user->profile_image_url) }}"
                                     alt="{{ $rsvp->user->name }}"
                                     class="w-10 h-10 rounded-full object-cover flex-shrink-0">
                            @else
                                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-pink-500 to-purple-500 flex items-center justify-center text-white font-semibold flex-shrink-0">
                                    {{ strtoupper(substr($rsvp->user->display_name ?? $rsvp->user->username ?? 'U', 0, 1)) }}
                                </div>
                            @endif
                            <div class="min-w-0 flex-1">
                                <div class="font-semibold text-white truncate">
                                    {{ $rsvp->user->display_name ?? $rsvp->user->username ?? 'Unknown' }}
                                </div>
                                <div class="text-xs text-gray-400 truncate">{{ $rsvp->user->email ?? '' }}</div>
                            </div>
                        </div>
                        @if ($rsvp->checked_in_at)
                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium bg-green-500/20 text-green-400 flex-shrink-0">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                Checked In
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium bg-yellow-500/20 text-yellow-400 flex-shrink-0">
                                Pending
                            </span>
                        @endif
                    </div>

                    @if ($rsvp->checked_in_at)
                        <div class="mt-3 pt-3 border-t border-white/5 text-xs text-gray-400">
                            Checked in {{ $rsvp->checked_in_at->format('M j, g:i A') }}
                        </div>
                    @else
                        <div class="mt-3 pt-3 border-t border-white/5">
                            <button wire:click="manualCheckIn('{{ $rsvp->id }}')"
                                    wire:loading.attr="disabled"
                                    class="w-full px-4 py-2 bg-gradient-to-r from-pink-500 to-purple-500 rounded-lg font-semibold text-sm hover:scale-105 transition-all text-white">
                                <span wire:loading.remove wire:target="manualCheckIn('{{ $rsvp->id }}')">Check In</span>
                                <span wire:loading wire:target="manualCheckIn('{{ $rsvp->id }}')">...</span>
                            </button>
                        </div>
                    @endif
                </div>
            @empty
                <div class="glass-card p-8 text-center">
                    <div class="text-4xl mb-3">👥</div>
                    <p class="text-gray-400">
                        @if($search)
                            No attendees match "{{ $search }}"
                        @else
                            No attendees yet
                        @endif
                    </p>
                </div>
            @endforelse
        </div>

        <!-- Attendee List - Desktop Table View -->
        <div class="hidden sm:block glass-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-slate-800/70 border-b border-white/10">
                            <th class="text-left text-white text-sm font-semibold px-4 py-3">Attendee</th>
                            <th class="text-left text-white text-sm font-semibold px-4 py-3">Status</th>
                            <th class="text-left text-white text-sm font-semibold px-4 py-3 hidden lg:table-cell">Check-in Time</th>
                            <th class="text-right text-white text-sm font-semibold px-4 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @forelse ($this->filteredRsvps as $rsvp)
                            <tr class="hover:bg-slate-800/30 transition-colors">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        @if($rsvp->user->profile_image_url)
                                            <img src="{{ Storage::url($rsvp->user->profile_image_url) }}"
                                                 alt="{{ $rsvp->user->name }}"
                                                 class="w-10 h-10 rounded-full object-cover">
                                        @else
                                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-pink-500 to-purple-500 flex items-center justify-center text-white font-semibold">
                                                {{ strtoupper(substr($rsvp->user->display_name ?? $rsvp->user->username ?? 'U', 0, 1)) }}
                                            </div>
                                        @endif
                                        <div>
                                            <div class="font-semibold text-white">{{ $rsvp->user->display_name ?? $rsvp->user->username ?? 'Unknown' }}</div>
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
                                <td class="px-4 py-3 text-gray-400 text-sm hidden lg:table-cell">
                                    {{ $rsvp->checked_in_at?->format('M j, g:i A') ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    @if (!$rsvp->checked_in_at)
                                        <button wire:click="manualCheckIn('{{ $rsvp->id }}')"
                                                wire:loading.attr="disabled"
                                                class="px-4 py-2 bg-gradient-to-r from-pink-500 to-purple-500 rounded-lg font-semibold text-sm hover:scale-105 transition-all text-white">
                                            <span wire:loading.remove wire:target="manualCheckIn('{{ $rsvp->id }}')">Check In</span>
                                            <span wire:loading wire:target="manualCheckIn('{{ $rsvp->id }}')">...</span>
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
        <div class="text-center pb-4">
            <a href="{{ route('activities.show', $activity) }}"
               class="px-6 py-3 bg-slate-800/50 border border-white/10 rounded-xl hover:border-cyan-500/50 transition inline-block text-white text-sm sm:text-base">
                ← Back to Event
            </a>
        </div>
    </div>
</div>
