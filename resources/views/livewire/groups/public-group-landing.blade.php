<div class="min-h-screen relative bg-slate-900">
    {{-- Full Bleed Hero Background --}}
    <div class="fixed inset-0 z-0">
        {{-- Base Layer: Heavily Blurred --}}
        <div class="absolute inset-0 bg-cover bg-center blur-xl scale-110 brightness-50" 
             style="background-image: url('{{ $group->cover_image_url ?? 'https://images.unsplash.com/photo-1517466787929-bc90951d0974?auto=format&fit=crop&q=80' }}');">
        </div>

        {{-- Top Layer: Sharp Image (masked) --}}
        <div class="absolute inset-0 bg-cover bg-center scale-110 brightness-75" 
             style="background-image: url('{{ $group->cover_image_url ?? 'https://images.unsplash.com/photo-1517466787929-bc90951d0974?auto=format&fit=crop&q=80' }}'); 
                    mask-image: linear-gradient(to bottom, black 20%, transparent 90%); 
                    -webkit-mask-image: linear-gradient(to bottom, black 20%, transparent 90%);">
        </div>

        {{-- Gradient Overlay --}}
        <div class="absolute inset-0 bg-gradient-to-t from-slate-900 via-slate-900/90 to-slate-900/50"></div>
    </div>

    <div class="relative z-10 pb-24 lg:pb-12">
        <div class="container mx-auto lg:px-6 lg:py-12">

            {{-- Authenticated View (Member) --}}
            @if(auth()->check() && auth()->user()->isMemberOf($group))
                <div class="p-8 bg-[#151515] rounded-xl border border-white/10 text-center mx-4 mt-20">
                    <h2 class="text-2xl font-bold text-white mb-2">Welcome Back!</h2>
                    <p class="text-gray-400">You are a member of {{ $group->name }}.</p>
                    <a href="{{ route('groups.show', $group) }}" class="inline-block mt-4 px-6 py-2 bg-gradient-to-r from-cyan-500 to-blue-500 rounded-lg text-white font-semibold">
                        Go to Group Chat
                    </a>
                </div>
            @else
                {{-- Guest / Landing View --}}
                <div class="flex flex-col min-h-screen lg:min-h-0">
                    
                    {{-- Header / Hero Content --}}
                    <div class="px-6 pt-12 pb-8 mt-20 lg:mt-0 text-center lg:text-left">
                        
                        {{-- Group Avatar/Emoji --}}
                        <div class="w-24 h-24 lg:w-32 lg:h-32 rounded-3xl bg-slate-800 border-4 border-white/10 shadow-2xl overflow-hidden mb-6 mx-auto lg:mx-0 flex items-center justify-center backdrop-blur-md bg-white/5">
                            @if($group->avatar_url)
                                <img src="{{ $group->avatar_url }}" class="w-full h-full object-cover">
                            @else
                                <span class="text-5xl lg:text-6xl">{{ $group->emoji ?? substr($group->name, 0, 1) }}</span>
                            @endif
                        </div>

                        <div class="lg:flex lg:items-end lg:justify-between">
                            <div>
                                <h1 class="text-4xl lg:text-6xl font-black text-white mb-2 tracking-tight drop-shadow-lg">{{ $group->name }}</h1>
                                <p class="text-lg lg:text-xl text-gray-200 flex items-center justify-center lg:justify-start gap-2 font-medium drop-shadow-md">
                                    <svg class="w-5 h-5 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                    {{ $group->location_name }}
                                </p>
                            </div>
                            
                            {{-- Member Count & Tags --}}
                            <div class="mt-6 lg:mt-0 flex flex-col items-center lg:items-end gap-3">
                                {{-- Facepile (Static for now, could be dynamic) --}}
                                <div class="flex -space-x-3">
                                    {{-- Just showing placeholders or the +count badge --}}
                                    <div class="w-10 h-10 rounded-full bg-slate-800 border-2 border-slate-900 flex items-center justify-center text-xs font-bold text-white">
                                        +{{ $group->members_count }}
                                    </div>
                                </div>
                                <div class="flex gap-2 flex-wrap justify-center lg:justify-end">
                                    @foreach($tags as $tag)
                                        <span class="px-3 py-1 text-xs font-bold bg-white/10 backdrop-blur-md text-white rounded-full border border-white/20">
                                            {{ $tag->name }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Main Cards Grid --}}
                    <div class="px-4 lg:px-0 grid grid-cols-1 lg:grid-cols-3 lg:gap-8 mt-4 lg:mt-12">
                        
                        {{-- Left Column --}}
                        <div class="lg:col-span-2 space-y-6">

                             {{-- About / Vibe Section --}}
                            <div class="relative p-8 glass-panel rounded-2xl">
                                <div class="top-accent"></div>
                                <h2 class="text-2xl font-bold text-white mb-4">The Vibe</h2>
                                <div class="prose prose-invert prose-lg text-gray-200 leading-relaxed font-light">
                                    {!! Str::markdown($group->description ?? 'No description yet.') !!}
                                </div>
                            </div>

                             {{-- Next Session / Schedule Card --}}
                            <div class="relative p-6 glass-panel rounded-2xl border-l-4 border-cyan-500 bg-[#151515]">
                                <div class="flex items-start justify-between mb-4">
                                    <div class="flex-1">
                                        <h3 class="text-xs font-bold text-cyan-400 uppercase tracking-widest mb-2">
                                            Next {{ $group->meetup_label ?? 'Session' }}
                                        </h3>
                                        @if($nextActivity)
                                            <p class="text-2xl text-white font-bold">
                                                {{ $nextActivity->start_time->format('l, M j @ g:i A') }}
                                            </p>
                                            <p class="text-gray-400 text-sm mt-1">
                                                {{ $nextActivity->location_name ?? $group->location_name }}
                                            </p>
                                        @else
                                            <p class="text-2xl text-white font-bold">
                                                {{ $group->schedule_text ?? 'check back soon' }}
                                            </p>
                                            <p class="text-gray-400 text-sm mt-1">
                                                No specific date scheduled yet.
                                            </p>
                                        @endif
                                    </div>
                                    <div class="text-center bg-slate-800/80 p-3 rounded-lg border border-white/10">
                                        <div class="text-2xl">{{ $group->emoji ?? '📅' }}</div>
                                        <div class="text-xs font-bold text-gray-400 uppercase mt-1">
                                            {{ $nextActivity ? 'Confirmed' : 'Schedule' }}
                                        </div>
                                    </div>
                                </div>

                                @if($nextActivity)
                                    {{-- Attendee Avatars + Count --}}
                                    @php
                                        $attendees = $nextActivity->rsvps->where('status', 'attending')->take(5);
                                        $attendeeCount = $nextActivity->rsvps->where('status', 'attending')->count();
                                    @endphp
                                    <div class="flex items-center justify-between mb-4 pt-4 border-t border-white/10">
                                        <div class="flex items-center gap-2">
                                            @if($attendees->count() > 0)
                                                <div class="flex -space-x-2">
                                                    @foreach($attendees as $rsvp)
                                                        @if($rsvp->user?->profile_image_url)
                                                            <img src="{{ Storage::url($rsvp->user->profile_image_url) }}"
                                                                 alt="{{ $rsvp->user->display_name }}"
                                                                 class="w-8 h-8 rounded-full border-2 border-slate-800 object-cover">
                                                        @else
                                                            <div class="w-8 h-8 rounded-full border-2 border-slate-800 bg-gradient-to-br from-cyan-500 to-purple-500 flex items-center justify-center text-xs font-bold text-white">
                                                                {{ strtoupper(substr($rsvp->user?->display_name ?? '?', 0, 1)) }}
                                                            </div>
                                                        @endif
                                                    @endforeach
                                                </div>
                                            @endif
                                            <span class="text-sm text-gray-400">
                                                @if($attendeeCount > 0)
                                                    {{ $attendeeCount }} {{ Str::plural('person', $attendeeCount) }} going
                                                @else
                                                    Be the first to join!
                                                @endif
                                            </span>
                                        </div>
                                        @if($nextActivity->max_attendees)
                                            <span class="text-xs px-2 py-1 rounded-full {{ $nextActivity->current_attendees >= $nextActivity->max_attendees ? 'bg-yellow-500/20 text-yellow-400' : 'bg-green-500/20 text-green-400' }}">
                                                {{ max(0, $nextActivity->max_attendees - $nextActivity->current_attendees) }} spots left
                                            </span>
                                        @endif
                                    </div>

                                    {{-- RSVP Button --}}
                                    @auth
                                        @if(auth()->id() !== $nextActivity->host_id)
                                            <livewire:activities.rsvp-button :activity="$nextActivity" :key="'landing-rsvp-'.$nextActivity->id" />
                                        @else
                                            <div class="text-center py-3 text-gray-400 text-sm border border-white/10 rounded-xl">
                                                You're hosting this event
                                            </div>
                                        @endif
                                    @else
                                        <button wire:click="$dispatch('openGroupAuthModal', { groupId: '{{ $group->id }}' })"
                                                class="w-full py-3 bg-gradient-to-r from-cyan-500 to-purple-500 rounded-xl font-semibold hover:scale-[1.02] transition-all">
                                            Join to RSVP
                                        </button>
                                    @endauth
                                @endif
                            </div>

                            {{-- Chat Teaser (Static / Mock for now as requested) --}}
                            <div class="relative p-6 glass-panel rounded-2xl opacity-90">
                                <h2 class="text-lg font-bold text-white mb-6 flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                                    Live Community
                                </h2>
                                
                                <div class="space-y-4 mask-bottom">
                                    <div class="flex gap-4">
                                        <div class="w-10 h-10 rounded-full bg-slate-700 flex items-center justify-center text-white font-bold shadow-lg">?</div>
                                        <div class="flex-1">
                                            <div class="flex items-baseline gap-2">
                                                <span class="text-sm font-bold text-cyan-400">Member</span>
                                                <span class="text-xs text-gray-500">Recently</span>
                                            </div>
                                            <p class="text-gray-200 mt-1 blur-sm select-none">This is a teaser of the chat content to show activity.</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mt-6 pt-4 border-t border-white/10 text-center">
                                    <p class="text-sm text-gray-400">Join {{ $group->members_count }} others in the chat</p>
                                </div>
                            </div>

                        </div>

                        {{-- Right Column (Admins) --}}
                        <div class="hidden lg:block lg:col-span-1 space-y-6">
                            <div class="p-6 glass-panel rounded-2xl">
                                <h3 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-4">Admins</h3>
                                @foreach($admins as $admin)
                                    <div class="flex items-center gap-3 mb-4">
                                        <img src="{{ $admin->profile_image_url }}" class="w-12 h-12 rounded-full object-cover">
                                        <div>
                                            <p class="text-white font-bold">{{ $admin->name }}</p>
                                            <span class="text-xs text-gray-400">Admin</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                    </div>
                </div>

                {{-- Persistent Bottom Action Bar --}}
                <div class="fixed bottom-0 left-0 right-0 p-4 lg:p-6 bg-slate-900/40 backdrop-blur-xl border-t border-white/10 z-50">
                    <div class="container mx-auto max-w-4xl flex items-center justify-between gap-4">
                        <div class="hidden lg:block">
                            <p class="text-white font-bold text-lg">Ready to join?</p>
                            <p class="text-sm text-gray-400">Join the group to RSVP and see the full schedule.</p>
                        </div>
                        <button wire:click="joinGroup" class="w-full lg:w-auto px-8 py-4 bg-gradient-to-r from-cyan-500 to-blue-600 rounded-xl text-white font-bold text-lg shadow-lg shadow-cyan-500/30 hover:scale-105 transition-transform flex items-center justify-center gap-2">
                             <span>👋</span> Join Group
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <style>
        .glass-panel {
            background: #151515;
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        .top-accent {
            position: absolute;
            top: 0;
            left: 0;
            width: 6rem;
            height: 0.15rem;
            background: linear-gradient(to right, #22d3ee, #3b82f6, transparent);
            border-radius: 9999px;
        }
        .mask-bottom {
            mask-image: linear-gradient(to bottom, black 50%, transparent 100%);
            -webkit-mask-image: linear-gradient(to bottom, black 50%, transparent 100%);
        }
    </style>

    {{-- Group Auth Modal --}}
    <livewire:auth.group-auth-modal :group="$group" />
</div>
