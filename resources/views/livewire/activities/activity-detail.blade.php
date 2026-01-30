<div class="min-h-screen">
    <div class="container mx-auto lg:px-6 lg:py-12">

        {{-- Flash Messages --}}
        @if (session()->has('success'))
            <div class="mb-6 mx-4 lg:mx-0 p-4 bg-green-500/20 border border-green-500/50 rounded-xl text-green-300">
                {{ session('success') }}
            </div>
        @endif

        @if (session()->has('error'))
            <div class="mb-6 mx-4 lg:mx-0 p-4 bg-red-500/20 border border-red-500/50 rounded-xl text-red-300">
                {{ session('error') }}
            </div>
        @endif

        {{-- Event Changes Alert Banner (for attendees with pending response) --}}
        @if ($pendingChangeResponse && $pendingChangeResponse->isPending() && $activeRefundWindow?->isActive())
            <div class="mb-6 mx-4 lg:mx-0 p-4 bg-amber-500/20 border border-amber-500/50 rounded-xl">
                <div class="flex flex-col lg:flex-row lg:items-center gap-4">
                    <div class="flex items-start gap-3 flex-1">
                        <div class="p-2 bg-amber-500/30 rounded-full flex-shrink-0">
                            <svg class="w-6 h-6 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-amber-300">Event Details Changed</h3>
                            <p class="text-amber-200/80 text-sm mt-1">
                                The host has made significant changes to this event. You have
                                <strong>{{ $activeRefundWindow->timeRemaining() }}</strong>
                                to request a full refund if you're not satisfied with the changes.
                            </p>
                            <div class="mt-2 space-y-1">
                                @foreach ($activeRefundWindow->changes_summary as $change)
                                    <p class="text-amber-200/70 text-xs">
                                        <strong>{{ ucfirst(str_replace('_', ' ', $change['field'])) }}:</strong>
                                        {{ $change['old_display'] }} → {{ $change['new_display'] }}
                                    </p>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div class="flex gap-2 lg:flex-shrink-0">
                        <button
                            wire:click="acceptChanges"
                            class="px-4 py-2 bg-green-500/20 border border-green-500/50 rounded-lg text-green-300 hover:bg-green-500/30 transition text-sm font-semibold"
                        >
                            Accept Changes
                        </button>
                        <button
                            wire:click="openRefundModal"
                            class="px-4 py-2 bg-red-500/20 border border-red-500/50 rounded-lg text-red-300 hover:bg-red-500/30 transition text-sm font-semibold"
                        >
                            Request Refund
                        </button>
                    </div>
                </div>
            </div>
        @endif

        {{-- Already Responded Banner --}}
        @if ($pendingChangeResponse && !$pendingChangeResponse->isPending())
            <div class="mb-6 mx-4 lg:mx-0 p-4 {{ $pendingChangeResponse->isAccepted() ? 'bg-green-500/20 border-green-500/50' : 'bg-blue-500/20 border-blue-500/50' }} border rounded-xl">
                <div class="flex items-center gap-3">
                    @if ($pendingChangeResponse->isAccepted())
                        <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <p class="text-green-300">You have accepted the event changes. Your RSVP is confirmed.</p>
                    @else
                        <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path>
                        </svg>
                        <p class="text-blue-300">Your refund has been processed. You will receive your money back within 5-10 business days.</p>
                    @endif
                </div>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 lg:gap-8">

            {{-- Main Content (Left Column) --}}
            <div class="lg:col-span-2 space-y-6 lg:space-y-6">

                {{-- Back Button --}}
                @auth
                <div class="px-4 lg:px-0">
                    @if($isGroupEvent && $group)
                        <a href="{{ route('groups.show', $group->slug) }}" class="inline-flex items-center gap-2 text-gray-400 hover:text-cyan-400 transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            <span class="flex items-center gap-2">
                                Back to
                                <span class="font-semibold text-cyan-400">{{ $group->name }}</span>
                            </span>
                        </a>
                    @else
                        <a href="{{ route('feed.nearby') }}" class="inline-flex items-center gap-2 text-gray-400 hover:text-cyan-400 transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            Back to Feed
                        </a>
                    @endif
                </div>
                @endauth

                {{-- Title, Details & Description Card --}}
                <div class="relative p-6 lg:p-8 glass-card lg:rounded-xl overflow-hidden">
                    <div class="top-accent"></div>

                    {{-- Header Row: Host Info + Actions (Flex) --}}
                    <div class="relative z-10 flex flex-col md:flex-row md:justify-between md:items-start gap-4 mb-6">
                        
                        {{-- Host Info --}}
                        <div class="flex items-center gap-4 min-w-0">
                            <a href="{{ route('profile.view', $activity->host->username) }}" class="flex-shrink-0 group">
                                @if($activity->host->profile_image_url)
                                    <img src="{{ Storage::url($activity->host->profile_image_url) }}" class="w-12 h-12 rounded-full object-cover border-2 border-white/10 shadow-lg group-hover:border-cyan-400/50 transition-colors">
                                @else
                                    <div class="w-12 h-12 rounded-full bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center text-xl font-bold text-white shadow-lg group-hover:ring-2 group-hover:ring-cyan-400/50 transition-all">
                                        {{ substr($activity->host->name, 0, 1) }}
                                    </div>
                                @endif
                            </a>
                            
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2 mb-0.5">
                                    <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider truncate">
                                        Hosted by <a href="{{ route('profile.view', $activity->host->username) }}" class="hover:text-cyan-400 transition-colors">{{ '@' . $activity->host->username }}</a>
                                    </div>
                                </div>
                                <a href="{{ route('profile.view', $activity->host->username) }}" class="font-bold text-white text-lg leading-tight hover:text-cyan-400 transition-colors block truncate">
                                    {{ $activity->host->name }}
                                </a>
                            </div>
                        </div>

                        {{-- Right Side: Actions (Status, Follow, Copy) --}}
                        <div class="flex items-center gap-3 self-start md:ml-auto flex-wrap">
                            {{-- Status Badge --}}
                            @if($activity->status === 'draft')
                                <span class="px-3 py-1 bg-gray-500/30 border border-gray-500/50 rounded-full text-xs font-bold text-gray-300 uppercase tracking-wider shadow-sm">Draft</span>
                            @elseif($activity->status === 'active')
                                <span class="px-3 py-1 bg-green-500/30 border border-green-500/50 rounded-full text-xs font-bold text-green-300 uppercase tracking-wider shadow-sm">Active</span>
                            @elseif($activity->status === 'completed')
                                <span class="px-3 py-1 bg-purple-500/30 border border-purple-500/50 rounded-full text-xs font-bold text-purple-300 uppercase tracking-wider shadow-sm">Completed</span>
                            @elseif($activity->status === 'cancelled')
                                <span class="px-3 py-1 bg-red-500/30 border border-red-500/50 rounded-full text-xs font-bold text-red-300 uppercase tracking-wider shadow-sm">Cancelled</span>
                            @endif

                            {{-- Follow Button --}}
                            @auth
                                @if(!$isHost)
                                    <div class="scale-90 origin-center">
                                        <livewire:follow-button :user-id="$activity->host->id" :is-following="$isFollowingHost" />
                                    </div>
                                @endif
                            @endauth

                             {{-- Copy Link Button --}}
                             <div x-data="{
                                copied: false,
                                url: '{{ route('events.show', $activity) }}',
                                copy() {
                                    navigator.clipboard.writeText(this.url).then(() => {
                                        this.copied = true;
                                        window.dispatchEvent(new CustomEvent('show-toast', {
                                            detail: { message: 'Public link copied to clipboard!', type: 'success' }
                                        }));
                                        setTimeout(() => this.copied = false, 2000);
                                    });
                                }
                             }">
                                <button @click="copy()"
                                        class="flex items-center gap-2 px-3 py-1.5 bg-purple-500/10 border border-purple-500/30 rounded-lg text-xs font-semibold hover:bg-purple-500/20 hover:border-purple-500/50 transition-all text-purple-300 shadow-sm"
                                        :class="{ 'bg-green-500/10 border-green-500/30 text-green-400': copied }">
                                    <svg x-show="!copied" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                                    </svg>
                                    <svg x-show="copied" x-cloak class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <span x-text="copied ? 'Copied' : 'Copy Link'"></span>
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Title --}}
                    <h1 class="text-3xl lg:text-5xl font-bold mb-4 text-white lead-tight">{{ $activity->title }}</h1>

                    {{-- Short Details --}}
                    <div class="flex flex-wrap gap-4 text-sm text-gray-300 mb-4">
                        <div class="flex items-center gap-2">
                            <span class="text-2xl">{{ match($activity->activity_type) {
                                'sports' => '🏀',
                                'music' => '🎵',
                                'food' => '🍕',
                                'social' => '👥',
                                'outdoor' => '🏕️',
                                'arts' => '🎨',
                                'wellness' => '🧘',
                                'tech' => '💻',
                                'education' => '📚',
                                'group_event' => '👥',
                                'other' => '✨',
                                default => '📅'
                            } }}</span>
                            <span class="capitalize">{{ str_replace('_', ' ', $activity->activity_type) }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            <span>{{ $activity->location_name }}</span>
                        </div>
                    </div>

                    {{-- Tags --}}
                    @if(count($activity->tags) > 0)
                        <div class="flex flex-wrap gap-2 mb-6">
                            @foreach($activity->tags as $tag)
                                <span class="px-3 py-1 bg-slate-800/50 border border-white/10 rounded-lg text-sm text-purple-300">
                                    #{{ $tag['name'] ?? $tag->name }}
                                </span>
                            @endforeach
                        </div>
                    @endif

                    {{-- Image Carousel --}}
                    @if($activity->images && count($activity->images) > 0)
                        <div class="relative mb-6" x-data="{ currentSlide: 0, totalSlides: {{ count($activity->images) }} }">
                            {{-- Carousel Container --}}
                            <div class="relative overflow-hidden lg:rounded-xl border-y lg:border border-white/10 bg-slate-900/50">
                                <div class="flex transition-transform duration-500 ease-out"
                                     :style="`transform: translateX(-${currentSlide * 100}%)`">
                                    @foreach($activity->images as $image)
                                        <div class="w-full flex-shrink-0 flex items-center justify-center" >
                                            <img src="{{ Storage::url($image) }}"
                                                 class="max-w-full max-h-full object-contain"
                                                 alt="Activity image">
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            {{-- Navigation Arrows (only show if more than 1 image) --}}
                            @if(count($activity->images) > 1)
                                {{-- Previous Button --}}
                                <button @click="currentSlide = currentSlide === 0 ? totalSlides - 1 : currentSlide - 1"
                                        class="absolute left-2 top-1/2 -translate-y-1/2 p-2 bg-slate-900/80 hover:bg-slate-800 border border-white/20 rounded-full transition-all hover:scale-110 z-10">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                    </svg>
                                </button>

                                {{-- Next Button --}}
                                <button @click="currentSlide = currentSlide === totalSlides - 1 ? 0 : currentSlide + 1"
                                        class="absolute right-2 top-1/2 -translate-y-1/2 p-2 bg-slate-900/80 hover:bg-slate-800 border border-white/20 rounded-full transition-all hover:scale-110 z-10">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </button>

                                {{-- Dots Indicator --}}
                                <div class="absolute bottom-4 left-1/2 -translate-x-1/2 flex gap-2 z-10">
                                    @foreach($activity->images as $index => $image)
                                        <button @click="currentSlide = {{ $index }}"
                                                class="w-2 h-2 rounded-full transition-all"
                                                :class="currentSlide === {{ $index }} ? 'bg-cyan-400 w-6' : 'bg-white/50 hover:bg-white/80'">
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endif

                    {{-- Description --}}
                    <div class="prose prose-invert max-w-none text-gray-300 leading-relaxed">
                        {{ $activity->description }}
                    </div>
                </div>

                {{-- About & Details Card --}}
                <div class="relative p-6 lg:p-8 glass-card lg:rounded-xl">
                    <div class="top-accent"></div>

                    <h2 class="text-2xl font-bold mb-6 text-white">About this Activity</h2>

                    {{-- Key Details Grid --}}
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8 pb-8 border-b border-white/10">

                        {{-- Date & Time --}}
                        <div>
                            <h3 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-3">When</h3>
                            <div class="flex items-start gap-3">
                                <div class="p-2 bg-slate-800 rounded-lg border border-white/10">
                                    <span class="text-2xl font-bold text-cyan-400">{{ $activity->start_time->format('d') }}</span>
                                </div>
                                <div>
                                    <div class="text-lg font-bold text-white">{{ $activity->start_time->format('F Y') }}</div>
                                    <div class="text-gray-300">{{ $activity->start_time->format('l, g:i A') }}</div>
                                    @if($activity->end_time)
                                        <div class="text-sm text-gray-500 mt-1">
                                            to {{ $activity->end_time->format('g:i A') }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Price --}}
                        <div>
                            <h3 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-3">Price</h3>
                            <div class="text-3xl font-bold text-white">
                                @if($activity->is_paid)
                                    ${{ number_format($activity->price_cents / 100, 2) }}
                                @else
                                    <span class="text-green-400">Free</span>
                                @endif
                            </div>
                        </div>

                        {{-- Capacity --}}
                        <div>
                            <h3 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-3">Availability</h3>
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-gray-300">{{ $activity->current_attendees }} attending</span>
                                @if($activity->max_attendees)
                                    <span class="text-gray-400">{{ $spotsRemaining }} spots left</span>
                                @endif
                            </div>
                            @if($activity->max_attendees)
                                <div class="w-full bg-slate-800 rounded-full h-2">
                                    <div class="bg-gradient-to-r from-cyan-500 to-blue-500 h-2 rounded-full"
                                         style="width: {{ ($activity->current_attendees / $activity->max_attendees) * 100 }}%"></div>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Description --}}
                    <div class="prose prose-invert max-w-none text-gray-300 mb-8">
                        {{ $activity->description }}
                    </div>

                    {{-- RSVP / Get Tickets Button --}}
                    @auth
                        @if(!$isHost)
                            <livewire:activities.rsvp-button :activity="$activity" />
                        @endif
                    @else
                        {{-- Guest CTA - Only Get Tickets Button --}}
                        <div class="sticky top-4 z-20 mb-4">
                            <button wire:click="$dispatch('openEventAuthModal', { activityId: '{{ $activity->id }}' })" class="w-full px-8 py-4 bg-gradient-to-r from-pink-500 to-purple-500 rounded-xl font-bold text-lg hover:scale-105 transition-all shadow-lg">
                                🎟️ Get Tickets
                            </button>
                        </div>
                    @endauth

                    {{-- Action Buttons --}}
                    <div class="mt-6 space-y-3">
                        @auth
                        {{-- Invite Friends Button (only for authenticated users) --}}
                        <button
                            wire:click="$dispatch('openActivityInviteModal', { activityId: '{{ $activity->id }}' })"
                            class="w-full px-6 py-3.5 bg-gradient-to-r from-purple-500 to-indigo-500 rounded-xl font-semibold hover:scale-[1.02] transition-all shadow-lg hover:shadow-purple-500/50">
                            <span class="flex items-center justify-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                </svg>
                                @if($isGroupEvent)
                                    Invite Group Members
                                @else
                                    Invite Friends
                                @endif
                                @if($activity->invitations_count ?? 0 > 0)
                                    <span class="bg-white/20 px-2 py-0.5 rounded-full text-xs">
                                        {{ $activity->invitations_count }}
                                    </span>
                                @endif
                            </span>
                        </button>

                        {{-- Host Actions --}}
                        @if($isHost)
                            {{-- Manage Attendees Button --}}
                            <a href="{{ route('events.attendees', $activity) }}"
                               class="flex items-center justify-center gap-2 w-full px-6 py-3.5 bg-gradient-to-r from-cyan-500 to-blue-500 rounded-xl font-semibold hover:scale-[1.02] transition-all shadow-lg hover:shadow-cyan-500/50">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                                </svg>
                                Manage Attendees & Check-In
                            </a>

                            {{-- Edit & Delete Buttons --}}
                            <div class="grid grid-cols-2 gap-3">
                                <a href="{{ route('events.edit', $activity->id) }}"
                                   class="flex items-center justify-center gap-2 py-3 bg-slate-800/50 border border-white/10 rounded-xl hover:border-cyan-500/50 hover:bg-slate-800/70 transition font-semibold">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                    Edit
                                </a>
                                <button
                                    wire:click="deleteActivity"
                                    wire:confirm="Are you sure you want to delete this activity?"
                                    class="flex items-center justify-center gap-2 py-3 bg-red-500/10 border border-red-500/30 rounded-xl hover:bg-red-500/20 hover:border-red-500/50 transition font-semibold text-red-400"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                    Delete
                                </button>
                            </div>
                        @endif

                        {{-- Attendee Ticket Button --}}
                        @if($userRsvp && $userRsvp->status === 'attending')
                            <a href="{{ route('events.my-ticket', $activity) }}"
                               class="flex items-center justify-center gap-2 w-full px-6 py-3.5 bg-gradient-to-r from-green-500 to-emerald-500 rounded-xl font-semibold hover:scale-[1.02] transition-all shadow-lg hover:shadow-green-500/50">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path>
                                </svg>
                                View My Ticket
                            </a>
                        @endif
                        @else
                        {{-- Share This Event section hidden per user request --}}
                        {{--
                        <div class="p-6 bg-slate-800/50 border border-white/10 rounded-xl">
                            <h3 class="text-lg font-bold text-white mb-4 text-center">📢 Share This Event</h3>
                            <div class="flex flex-wrap justify-center gap-3">
                                <button wire:click="share('instagram')" class="px-4 py-2 bg-gradient-to-br from-purple-600 to-pink-600 rounded-lg font-semibold hover:scale-105 transition-all text-sm">
                                    📷 Instagram
                                </button>
                                <button wire:click="share('facebook')" class="px-4 py-2 bg-blue-600 rounded-lg font-semibold hover:scale-105 transition-all text-sm">
                                    👍 Facebook
                                </button>
                                <button wire:click="share('twitter')" class="px-4 py-2 bg-sky-500 rounded-lg font-semibold hover:scale-105 transition-all text-sm">
                                    🐦 Twitter
                                </button>
                                <button wire:click="share('copy_link')" class="px-4 py-2 bg-slate-700 rounded-lg font-semibold hover:scale-105 transition-all text-sm">
                                    🔗 Copy Link
                                </button>
                            </div>
                        </div>
                        --}}
                        @endauth
                    </div>
                </div>

                {{-- Chat Section (only for authenticated users) --}}
                @auth
                <div id="discussion" class="relative p-6 lg:p-8 glass-card lg:rounded-xl scroll-mt-6">
                    <div class="top-accent"></div>
                    <h2 class="text-2xl font-bold mb-6 text-white">Discussion</h2>
                    <div class="h-[600px]">
                        <livewire:chat.chat-component :conversationable="$activity" />
                    </div>
                </div>
                @endauth

            </div>

            {{-- Sidebar (Right Column) --}}
            <div class="space-y-6 lg:space-y-8">

                {{-- Map --}}
                <div class="relative p-1 glass-card lg:rounded-xl overflow-hidden h-64">
                    <div id="activity-map" class="w-full h-full lg:rounded-xl"></div>
                </div>

            </div>
        </div>

        {{-- Invite Friends Modal --}}
        <livewire:activities.invite-friends-modal />

        {{-- Event Auth Modal for Guests --}}
        @guest
        <livewire:auth.event-auth-modal :activity="$activity" />
        @endguest
    </div>
    <style>
       

        .top-accent {
            position: absolute;
            top: 0;
            left: 0;
            width: 8rem;
            height: 0.25rem;
            background: linear-gradient(to right, #ec4899, #8b5cf6, transparent);
            border-radius: 9999px;
        }
    </style>
    @if($activity->location_coordinates instanceof \MatanYadaev\EloquentSpatial\Objects\Point)
    <script>
        const activityMapData = {
            lat: {{ $activity->location_coordinates->latitude }},
            lng: {{ $activity->location_coordinates->longitude }},
            title: @json($activity->title),
            locationName: @json($activity->location_name)
        };

        function initActivityMap() {
            const mapElement = document.getElementById('activity-map');
            if (!mapElement) {
                console.log('Map element not found');
                return;
            }

            console.log('Initializing map with position:', activityMapData);

            const position = {
                lat: activityMapData.lat,
                lng: activityMapData.lng
            };

            const map = new google.maps.Map(mapElement, {
                center: position,
                zoom: 15,
                styles: [
                    { elementType: "geometry", stylers: [{ color: "#1e293b" }] },
                    { elementType: "labels.text.stroke", stylers: [{ color: "#0f172a" }] },
                    { elementType: "labels.text.fill", stylers: [{ color: "#94a3b8" }] },
                    {
                        featureType: "administrative.locality",
                        elementType: "labels.text.fill",
                        stylers: [{ color: "#cbd5e1" }],
                    },
                    {
                        featureType: "poi",
                        elementType: "labels.text.fill",
                        stylers: [{ color: "#64748b" }],
                    },
                    {
                        featureType: "poi.park",
                        elementType: "geometry",
                        stylers: [{ color: "#1e3a2e" }],
                    },
                    {
                        featureType: "poi.park",
                        elementType: "labels.text.fill",
                        stylers: [{ color: "#6b9080" }],
                    },
                    {
                        featureType: "road",
                        elementType: "geometry",
                        stylers: [{ color: "#334155" }],
                    },
                    {
                        featureType: "road",
                        elementType: "geometry.stroke",
                        stylers: [{ color: "#1e293b" }],
                    },
                    {
                        featureType: "road.highway",
                        elementType: "geometry",
                        stylers: [{ color: "#475569" }],
                    },
                    {
                        featureType: "water",
                        elementType: "geometry",
                        stylers: [{ color: "#0c1e2e" }],
                    },
                    {
                        featureType: "water",
                        elementType: "labels.text.fill",
                        stylers: [{ color: "#475569" }],
                    },
                ],
                disableDefaultUI: true,
                zoomControl: true,
            });

            // Custom marker with gradient
            const marker = new google.maps.Marker({
                position: position,
                map: map,
                title: activityMapData.locationName,
                animation: google.maps.Animation.DROP,
            });

            // Info window
            const infoWindow = new google.maps.InfoWindow({
                content: `
                    <div style="padding: 8px; color: #1e293b;">
                        <h3 style="margin: 0 0 4px 0; font-weight: bold;">${activityMapData.title}</h3>
                        <p style="margin: 0; font-size: 14px;">${activityMapData.locationName}</p>
                    </div>
                `
            });

            marker.addListener('click', () => {
                infoWindow.open(map, marker);
            });

            console.log('Map initialized successfully');
        }

        // Load Google Maps API
        if (!window.google || !window.google.maps) {
            console.log('Loading Google Maps API...');
            const script = document.createElement('script');
            script.src = `https://maps.googleapis.com/maps/api/js?key={{ config('services.google.places_api_key') }}&callback=initActivityMap`;
            script.async = true;
            script.defer = true;
            document.head.appendChild(script);
        } else {
            console.log('Google Maps API already loaded');
            initActivityMap();
        }
    </script>
    @else
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const mapElement = document.getElementById('activity-map');
            if (mapElement) {
                mapElement.innerHTML = '<div style="display: flex; align-items: center; justify-content: center; height: 100%; color: #94a3b8;">Location not available</div>';
            }
        });
    </script>
    @endif

    {{-- Refund Confirmation Modal --}}
    @if ($showRefundModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
            <div class="relative w-full max-w-md p-6 glass-card">
                <div class="top-accent-center"></div>

                {{-- Header --}}
                <div class="flex items-center gap-3 mb-4">
                    <div class="p-3 bg-red-500/20 rounded-full">
                        <svg class="w-6 h-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-white">Confirm Refund Request</h3>
                        <p class="text-gray-400 text-sm">This action cannot be undone</p>
                    </div>
                </div>

                {{-- Content --}}
                <div class="mb-6 p-4 bg-slate-800/50 rounded-xl border border-white/10">
                    <p class="text-gray-300 text-sm">
                        You are about to request a full refund of
                        <strong class="text-white">${{ number_format(($userRsvp?->payment_amount ?? 0) / 100, 2) }}</strong>
                        for this event.
                    </p>
                    <p class="text-gray-400 text-sm mt-2">
                        Your RSVP will be cancelled and you will no longer be able to attend this event.
                    </p>
                </div>

                {{-- Warning --}}
                <div class="mb-6 p-4 bg-amber-500/10 border border-amber-500/30 rounded-xl">
                    <p class="text-amber-200 text-sm">
                        <strong>Note:</strong> Refunds typically take 5-10 business days to appear in your account.
                    </p>
                </div>

                {{-- Actions --}}
                <div class="flex gap-3 justify-end">
                    <button
                        type="button"
                        wire:click="closeRefundModal"
                        class="px-6 py-3 bg-slate-800/50 border border-white/10 rounded-xl hover:border-cyan-500/50 transition font-semibold"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        wire:click="requestRefund"
                        class="px-6 py-3 bg-gradient-to-r from-red-500 to-pink-500 rounded-xl font-semibold hover:scale-105 transition-all shadow-lg"
                    >
                        Confirm Refund
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Copy to Clipboard Script --}}
    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('copy-to-clipboard', (event) => {
                const url = event.url;

                // Use modern Clipboard API
                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(url).then(() => {
                        console.log('Link copied to clipboard!');
                        window.dispatchEvent(new CustomEvent('show-toast', {
                            detail: { message: 'Public link copied to clipboard!', type: 'success' }
                        }));
                    }).catch(err => {
                        console.error('Failed to copy:', err);
                        fallbackCopy(url);
                    });
                } else {
                    fallbackCopy(url);
                }
            });
        });

        // Fallback for older browsers
        function fallbackCopy(text) {
            const textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.left = '-999999px';
            document.body.appendChild(textArea);
            textArea.select();
            try {
                document.execCommand('copy');
                console.log('Link copied to clipboard (fallback)!');
                window.dispatchEvent(new CustomEvent('show-toast', {
                    detail: { message: 'Public link copied to clipboard!', type: 'success' }
                }));
            } catch (err) {
                console.error('Fallback copy failed:', err);
            }
            document.body.removeChild(textArea);
        }
    </script>

    {{-- Interest Modal for Guests --}}
    @guest
    @if($showInterestModal)
        <div class="fixed inset-0 bg-black/80 backdrop-blur-sm flex items-center justify-center z-50" wire:click.self="showInterestModal = false">
            <div class="relative p-8 glass-card max-w-md mx-4 rounded-xl border border-white/10">
                <div class="top-accent"></div>

                <h2 class="text-2xl font-bold text-white mb-4">Stay Updated</h2>
                <p class="text-gray-300 mb-6">Enter your email to receive updates and reminders about this event.</p>

                <form wire:submit="expressInterest">
                    <div class="mb-4">
                        <input
                            type="email"
                            wire:model="email"
                            placeholder="your@email.com"
                            class="w-full px-4 py-3 bg-slate-900/50 border border-white/10 rounded-xl text-white placeholder-gray-500 focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/50 transition"
                        >
                        @error('email') <span class="text-red-400 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex gap-3">
                        <button type="submit" class="flex-1 px-6 py-3 bg-gradient-to-r from-pink-500 to-purple-500 rounded-xl font-semibold hover:scale-105 transition-all">
                            Submit
                        </button>
                        <button type="button" wire:click="showInterestModal = false" class="px-6 py-3 bg-slate-800/50 border border-white/10 rounded-xl hover:border-cyan-500/50 transition">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
    @endguest

    {{-- Share URL Handler Script --}}
    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('share-url-generated', (event) => {
                const { platform, url } = event[0];

                if (platform === 'copy_link') {
                    navigator.clipboard.writeText(url);
                    window.dispatchEvent(new CustomEvent('show-toast', {
                        detail: { message: 'Link copied to clipboard!', type: 'success' }
                    }));
                } else {
                    window.open(url, '_blank');
                }
            });
        });
    </script>
</div>
