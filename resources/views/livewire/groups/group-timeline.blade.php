<div>
    {{-- Modals (rendered at component root level to prevent positioning issues) --}}
    @livewire('groups.create-group-post', ['group' => $group], key('create-post-'.$group->id))
    @livewire('groups.create-group-event', ['group' => $group], key('create-event-'.$group->id))

    <div class="relative p-8 glass-card">
        <div class="top-accent-center"></div>

        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold">Group Timeline</h2>
            @auth
                @php
                    $canCreatePost = $group->canCreatePost(auth()->user());
                    $canCreateEvent = $group->canCreateEvent(auth()->user());
                @endphp
                @if($canCreatePost || $canCreateEvent)
                <div class="flex gap-2">
                    @if($canCreatePost)
                    <button wire:click="createPost" wire:loading.attr="disabled" wire:loading.class="opacity-50 cursor-wait" class="px-4 py-2 bg-gradient-to-r from-pink-500 to-purple-500 rounded-xl font-semibold hover:scale-105 transition-all text-sm disabled:hover:scale-100">
                        <span wire:loading.remove wire:target="createPost">New Post</span>
                        <span wire:loading wire:target="createPost" class="flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            Loading...
                        </span>
                    </button>
                    @endif
                    @if($canCreateEvent)
                    <button wire:click="createEvent" wire:loading.attr="disabled" wire:loading.class="opacity-50 cursor-wait" class="px-4 py-2 bg-slate-800/50 border border-white/10 rounded-xl hover:border-cyan-500/50 transition text-sm disabled:hover:scale-100">
                        <span wire:loading.remove wire:target="createEvent">New Event</span>
                        <span wire:loading wire:target="createEvent" class="flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            Loading...
                        </span>
                    </button>
                    @endif
                </div>
                @endif
            @endauth
        </div>

        {{-- Next Session Countdown --}}
        <livewire:groups.next-session-countdown :group="$group" :key="'countdown-'.$group->id" />

    <div class="space-y-8" wire:poll.10s="loadMore" wire:init="loadMore" wire:scroll.window="loadMore">
        @forelse($items as $item)
            <div class="relative p-5 rounded-2xl {{ $item->type === 'post' && $item->is_pinned ? 'bg-gradient-to-br from-amber-500/10 to-orange-500/10 border-amber-500/30' : 'bg-slate-800/50 border-white/10' }} border hover:border-{{ $item->type === 'event' ? 'cyan' : ($item->is_pinned ?? false ? 'amber' : 'purple') }}-500/50 transition-all">
                {{-- Pinned Indicator --}}
                @if($item->type === 'post' && $item->is_pinned)
                    <div class="absolute -top-2 -left-2 flex items-center gap-1 px-2 py-1 bg-amber-500 text-amber-900 text-xs font-bold rounded-full shadow-lg">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M10 2a1 1 0 011 1v1.323l3.954 1.582 1.599-.8a1 1 0 01.894 1.79l-1.233.616 1.738 5.42a1 1 0 01-.285 1.05A3.989 3.989 0 0115 15a3.989 3.989 0 01-2.667-1.019 1 1 0 01-.285-1.05l1.715-5.349L11 6.477V16h2a1 1 0 110 2H7a1 1 0 110-2h2V6.477L6.237 7.582l1.715 5.349a1 1 0 01-.285 1.05A3.989 3.989 0 015 15a3.989 3.989 0 01-2.667-1.019 1 1 0 01-.285-1.05l1.738-5.42-1.233-.617a1 1 0 01.894-1.788l1.599.799L9 4.323V3a1 1 0 011-1z"/></svg>
                        PINNED
                    </div>
                @endif

                @if($item->type === 'post')
                    {{-- Post Card Header --}}
                    @php
                        $author = $item->author;
                        $isGroupPost = $item->posted_as_group && $item->group;
                        $authorName = $isGroupPost
                            ? $author->name
                            : ($author?->display_name ?? $author?->username ?? 'Unknown');
                        $isAdmin = $group->isAdmin(auth()->user());
                    @endphp
                    <div class="flex items-start justify-between mb-3 {{ $item->is_pinned ? 'mt-2' : '' }}">
                        <div class="flex items-center gap-3">
                            {{-- Author Avatar --}}
                            @if($isGroupPost && $author->avatar_url)
                                <img src="{{ $author->avatar_url }}" alt="{{ $authorName }}" class="w-10 h-10 rounded-full object-cover">
                            @elseif(!$isGroupPost && $author?->profile_image_url)
                                <img src="{{ Storage::url($author->profile_image_url) }}" alt="{{ $authorName }}" class="w-10 h-10 rounded-full object-cover">
                            @else
                                <div class="w-10 h-10 rounded-full bg-gradient-to-br {{ $isGroupPost ? 'from-cyan-500 to-purple-500' : 'from-pink-500 to-purple-500' }} flex items-center justify-center text-white font-bold">
                                    {{ $isGroupPost ? ($author->emoji ?? substr($authorName, 0, 1)) : strtoupper(substr($authorName, 0, 1)) }}
                                </div>
                            @endif
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="font-semibold text-white">{{ $authorName }}</span>
                                    @if($isGroupPost)
                                        <span class="px-1.5 py-0.5 text-[0.65rem] font-semibold rounded bg-cyan-500/20 text-cyan-400 border border-cyan-500/30">GROUP</span>
                                    @endif
                                </div>
                                <span class="text-xs text-gray-400">{{ $item->created_at->diffForHumans() }}</span>
                            </div>
                        </div>
                        {{-- Action buttons --}}
                        @auth
                            <div class="flex items-center gap-1">
                                {{-- Pin/Unpin button (admin only) --}}
                                @if($isAdmin)
                                    @if($item->is_pinned)
                                        <button wire:click="unpinPost('{{ $item->id }}')" class="text-amber-400 hover:text-amber-300 transition p-1" title="Unpin post">
                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M10 2a1 1 0 011 1v1.323l3.954 1.582 1.599-.8a1 1 0 01.894 1.79l-1.233.616 1.738 5.42a1 1 0 01-.285 1.05A3.989 3.989 0 0115 15a3.989 3.989 0 01-2.667-1.019 1 1 0 01-.285-1.05l1.715-5.349L11 6.477V16h2a1 1 0 110 2H7a1 1 0 110-2h2V6.477L6.237 7.582l1.715 5.349a1 1 0 01-.285 1.05A3.989 3.989 0 015 15a3.989 3.989 0 01-2.667-1.019 1 1 0 01-.285-1.05l1.738-5.42-1.233-.617a1 1 0 01.894-1.788l1.599.799L9 4.323V3a1 1 0 011-1z"/></svg>
                                        </button>
                                    @else
                                        <button wire:click="pinPost('{{ $item->id }}')" class="text-gray-400 hover:text-amber-400 transition p-1" title="Pin post">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/></svg>
                                        </button>
                                    @endif
                                @endif
                                {{-- Delete button (creator/admin only) --}}
                                @if(auth()->id() === $item->user_id || $isAdmin)
                                    <button wire:click="deleteItem('{{ $item->id }}', 'post')" class="text-gray-400 hover:text-red-500 transition p-1" title="Delete post">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                @endif
                            </div>
                        @endauth
                    </div>

                    {{-- Post Content --}}
                    @if($item->description)
                        <p class="text-gray-300 text-sm mb-4 line-clamp-3">{{ $item->description }}</p>
                    @endif

                    {{-- Post Meta --}}
                    @if($item->location_name)
                        <div class="flex items-center gap-1.5 text-sm text-gray-400 mb-3">
                            <svg class="w-4 h-4 text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            {{ $item->location_name }}
                        </div>
                    @endif

                    {{-- Post Actions --}}
                    <div class="grid gap-2.5 pt-3 border-t border-white/10" style="grid-template-columns: 1fr auto auto;" onclick="event.stopPropagation()">
                        {{-- Reaction Button (Interactive) --}}
                        <livewire:posts.reaction-button
                            :post="$item"
                            size="sm"
                            :full-width="true"
                            :key="'reaction-'.$item->id" />

                        {{-- Invite Button --}}
                        @auth
                        <button
                            wire:click.stop="$dispatch('openInviteModal', { postId: '{{ $item->id }}' })"
                            class="px-3.5 py-3 rounded-xl cursor-pointer transition-all hover:bg-white/5"
                            style="background: transparent; border: 1px solid rgba(255,255,255,0.08); color: #eef1ff;">
                            Invite
                        </button>
                        @endauth

                        {{-- Discussion Link --}}
                        <a href="{{ route('posts.chat', $item->id) }}" class="px-3.5 py-3 rounded-xl cursor-pointer transition-all hover:bg-white/5 flex items-center justify-center gap-1.5 text-gray-400 hover:text-cyan-400"
                           style="background: transparent; border: 1px solid rgba(255,255,255,0.08);">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                            <span class="text-sm">Chat</span>
                        </a>
                    </div>
                    {{-- Expiration Notice --}}
                    @if($item->expires_at)
                        <div class="mt-2 text-xs text-gray-500 text-right">
                            Expires {{ $item->expires_at->diffForHumans() }}
                        </div>
                    @endif
                @elseif($item->type === 'event')
                    {{-- Event Card Header --}}
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="px-2 py-0.5 text-xs font-semibold rounded bg-cyan-500/20 text-cyan-400 border border-cyan-500/30">
                                    EVENT
                                </span>
                                @if($item->start_time->isToday())
                                    <span class="px-2 py-0.5 text-xs font-semibold rounded bg-green-500/20 text-green-400 border border-green-500/30">
                                        TODAY
                                    </span>
                                @endif
                            </div>
                            <h3 class="text-xl font-semibold text-white mb-1">{{ $item->title }}</h3>
                        </div>
                        {{-- Delete button (creator/admin only) --}}
                        @if(auth()->id() === $item->host_id || $group->creator->id === auth()->id())
                            <button wire:click="deleteItem('{{ $item->id }}', 'event')" class="text-gray-400 hover:text-red-500 transition p-1">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            </button>
                        @endif
                    </div>

                    {{-- Event Meta --}}
                    <div class="flex flex-wrap items-center gap-3 text-sm text-gray-400 mb-3">
                        <span class="flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            {{ $item->start_time->format('M j, g:i A') }}
                        </span>
                        @if($item->location_name)
                            <span class="flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                {{ Str::limit($item->location_name, 30) }}
                            </span>
                        @endif
                        <span class="flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                            Hosted by {{ $item->host?->display_name ?? $item->host?->username ?? 'Unknown' }}
                        </span>
                    </div>

                    @if($item->description)
                        <p class="text-gray-300 text-sm mb-4 line-clamp-2">{{ $item->description }}</p>
                    @endif

                    {{-- Attendee Avatars + Count --}}
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            @php
                                $attendees = $item->rsvps->where('status', 'attending')->take(5);
                                $attendeeCount = $item->rsvps->where('status', 'attending')->count();
                            @endphp
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
                        @if($item->max_attendees)
                            <span class="text-xs px-2 py-1 rounded-full {{ $item->current_attendees >= $item->max_attendees ? 'bg-yellow-500/20 text-yellow-400' : 'bg-green-500/20 text-green-400' }}">
                                {{ max(0, $item->max_attendees - $item->current_attendees) }} spots left
                            </span>
                        @endif
                    </div>

                    {{-- Event Actions --}}
                    <div class="grid gap-2.5" style="grid-template-columns: 1fr auto;">
                        {{-- RSVP Button --}}
                        @auth
                            @if(auth()->id() !== $item->host_id)
                                <livewire:activities.rsvp-button :activity="$item" :key="'rsvp-'.$item->id" />
                            @else
                                <div class="text-center py-3 text-gray-400 text-sm border border-white/10 rounded-xl">
                                    You're hosting this event
                                </div>
                            @endif
                        @else
                            <button wire:click="$dispatch('openGroupAuthModal', { groupId: '{{ $group->id }}' })"
                                    class="w-full py-3 bg-gradient-to-r from-cyan-500 to-purple-500 rounded-xl font-semibold hover:scale-[1.02] transition-all">
                                Sign in to RSVP
                            </button>
                        @endauth

                        {{-- View Details Button --}}
                        <a href="{{ route('events.show', $item) }}"
                           class="px-4 py-3 rounded-xl cursor-pointer transition-all hover:bg-white/5 flex items-center justify-center gap-1.5 text-gray-400 hover:text-cyan-400"
                           style="background: transparent; border: 1px solid rgba(255,255,255,0.08);">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                            <span class="text-sm">Details</span>
                        </a>
                    </div>
                @endif
            </div>
        @empty
            {{-- Enhanced Empty State --}}
            <div class="relative p-8 rounded-2xl bg-gradient-to-br from-slate-800/80 to-slate-900/80 border border-white/10 text-center">
                {{-- Decorative gradient orb --}}
                <div class="absolute top-0 left-1/2 -translate-x-1/2 -translate-y-1/2 w-32 h-32 bg-gradient-to-br from-cyan-500/20 to-purple-500/20 rounded-full blur-3xl"></div>

                {{-- Icon --}}
                <div class="relative mx-auto w-20 h-20 mb-6 rounded-2xl bg-gradient-to-br from-cyan-500/20 to-purple-500/20 border border-white/10 flex items-center justify-center">
                    <svg class="w-10 h-10 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"></path>
                    </svg>
                </div>

                {{-- Text --}}
                <h3 class="text-xl font-bold text-white mb-2">No activity yet</h3>
                <p class="text-gray-400 mb-6 max-w-sm mx-auto">
                    This group is waiting for its first post or event. Start the conversation!
                </p>

                {{-- CTA Buttons --}}
                @auth
                    @if($group->memberships()->where('user_id', auth()->id())->exists())
                        <div class="flex flex-col sm:flex-row gap-3 justify-center">
                            <button wire:click="createPost" class="px-6 py-3 bg-gradient-to-r from-pink-500 to-purple-500 rounded-xl font-semibold hover:scale-105 transition-all">
                                <span class="flex items-center justify-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                    </svg>
                                    Create First Post
                                </span>
                            </button>
                            <button wire:click="createEvent" class="px-6 py-3 bg-slate-800/50 border border-cyan-500/50 rounded-xl font-semibold hover:border-cyan-400 transition">
                                <span class="flex items-center justify-center gap-2">
                                    <svg class="w-5 h-5 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    Schedule Event
                                </span>
                            </button>
                        </div>
                    @else
                        <p class="text-sm text-gray-500">Join this group to start posting</p>
                    @endif
                @else
                    <p class="text-sm text-gray-500">Sign in to participate in this group</p>
                @endauth
            </div>
        @endforelse

        @if($hasMore)
            <div class="text-center mt-8">
                <button wire:click="loadMore" class="px-6 py-3 bg-slate-800/50 border border-white/10 rounded-xl hover:border-cyan-500/50 transition">
                    Load More
                </button>
            </div>
        @endif
    </div>
    </div>
</div>