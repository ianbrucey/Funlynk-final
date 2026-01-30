<div wire:poll.60s="refreshSession">
    @if($nextSession)
        <div class="relative overflow-hidden bg-gradient-to-r from-pink-500/20 via-purple-500/20 to-cyan-500/20 border border-pink-500/30 rounded-2xl p-6 mb-6">
            {{-- Animated background glow --}}
            <div class="absolute inset-0 bg-gradient-to-r from-pink-500/10 to-purple-500/10 animate-pulse"></div>

            <div class="relative z-10">
                {{-- Header --}}
                <div class="flex items-center gap-2 mb-3">
                    <span class="text-pink-400 text-sm font-semibold uppercase tracking-wider">Next Session</span>
                    <div class="flex-1 h-px bg-gradient-to-r from-pink-500/50 to-transparent"></div>
                </div>

                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                    {{-- Event Info --}}
                    <div class="flex-1">
                        <h3 class="text-2xl font-bold text-white mb-2">{{ $nextSession->title }}</h3>

                        <div class="flex flex-wrap items-center gap-4 text-sm text-gray-300">
                            {{-- Date & Time --}}
                            <span class="flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                {{ $nextSession->start_time->format('D, M j') }}
                            </span>

                            <span class="flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                {{ $nextSession->start_time->format('g:i A') }}
                            </span>

                            @if($nextSession->location_name)
                                <span class="flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                    {{ Str::limit($nextSession->location_name, 30) }}
                                </span>
                            @endif

                            {{-- RSVP Count --}}
                            <span class="flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                                {{ $rsvpCount }} {{ Str::plural('member', $rsvpCount) }} going
                            </span>
                        </div>
                    </div>

                    {{-- Countdown & Actions --}}
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                        {{-- Live Countdown --}}
                        <div
                            x-data="countdownTimer('{{ $nextSession->start_time->toIso8601String() }}')"
                            class="text-center px-4 py-3 bg-slate-900/50 rounded-xl border border-white/10"
                        >
                            <div class="text-xs text-gray-400 uppercase tracking-wider mb-1">Starts in</div>
                            <div class="text-2xl font-mono font-bold text-cyan-400" x-text="display"></div>
                        </div>

                        {{-- RSVP Button --}}
                        @auth
                            @if(auth()->id() !== $nextSession->host_id)
                                <livewire:activities.rsvp-button :activity="$nextSession" :key="'countdown-rsvp-'.$nextSession->id" />
                            @else
                                <div class="text-center py-3 px-4 text-green-300 text-sm border border-green-500/50 rounded-xl bg-green-500/20">
                                    You're hosting
                                </div>
                            @endif
                        @else
                            <button
                                wire:click="$dispatch('openGroupAuthModal', { groupId: '{{ $group->id }}' })"
                                class="px-6 py-3 bg-gradient-to-r from-cyan-500 to-purple-500 rounded-xl font-semibold hover:scale-[1.02] transition-all"
                            >
                                Sign in to RSVP
                            </button>
                        @endauth

                        {{-- View Details Link --}}
                        <a
                            href="{{ route('events.show', $nextSession) }}"
                            class="px-4 py-3 rounded-xl cursor-pointer transition-all hover:bg-white/5 flex items-center justify-center gap-1.5 text-gray-400 hover:text-cyan-400 border border-white/10"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                            <span class="text-sm">Details</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

@push('scripts')
<script>
    function countdownTimer(targetDate) {
        return {
            display: '',
            targetTime: new Date(targetDate).getTime(),
            interval: null,

            init() {
                this.updateCountdown();
                this.interval = setInterval(() => this.updateCountdown(), 1000);
            },

            destroy() {
                if (this.interval) {
                    clearInterval(this.interval);
                }
            },

            updateCountdown() {
                const now = new Date().getTime();
                const distance = this.targetTime - now;

                if (distance < 0) {
                    this.display = 'Starting now!';
                    this.destroy();
                    return;
                }

                const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((distance % (1000 * 60)) / 1000);

                if (days > 0) {
                    this.display = `${days}d ${hours}h ${minutes}m`;
                } else if (hours > 0) {
                    this.display = `${hours}h ${minutes}m ${seconds}s`;
                } else if (minutes > 0) {
                    this.display = `${minutes}m ${seconds}s`;
                } else {
                    this.display = `${seconds}s`;
                }
            }
        }
    }
</script>
@endpush
