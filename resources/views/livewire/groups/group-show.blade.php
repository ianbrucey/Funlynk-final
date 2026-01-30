<div>
    {{-- Invite Friends Modal (at root level for proper positioning) --}}
    @livewire('posts.invite-friends-modal')

    <x-slot name="title">{{ $group->name }}</x-slot>

    <div class="container mx-auto sm:px-6 py-4 sm:py-8 max-w-5xl">
        <!-- Flash Messages -->
        @if (session()->has('success'))
            <div class="mb-4 p-3 sm:p-4 rounded-xl bg-green-500/20 text-green-300 text-sm sm:text-base">
                {{ session('success') }}
            </div>
        @endif

        @if (session()->has('error'))
            <div class="mb-4 p-3 sm:p-4 rounded-xl bg-red-500/20 text-red-300 text-sm sm:text-base">
                {{ session('error') }}
            </div>
        @endif

        <!-- Cover Image Hero Section (if cover exists) -->
        @if($group->cover_image_url)
            <div class="relative h-32 sm:h-48 md:h-56 rounded-t-2xl overflow-hidden mb-0">
                <img
                    src="{{ $group->cover_image_url }}"
                    alt="{{ $group->name }} cover"
                    class="w-full h-full object-cover"
                    onerror="this.parentElement.classList.add('hidden')"
                >
                <div class="absolute inset-0 bg-gradient-to-t from-slate-900/90 via-slate-900/40 to-transparent"></div>
            </div>
        @endif

        <!-- Group Header Card -->
        <div class="relative glass-card {{ $group->cover_image_url ? 'rounded-t-none' : '' }} mb-4 sm:mb-6">
            <div class="top-accent-center"></div>

            <div class="p-4 sm:p-6 lg:p-8">
                <!-- Mobile Layout: Stacked -->
                <div class="flex flex-col sm:flex-row items-center sm:items-start gap-4 sm:gap-6">

                    <!-- Avatar with Fallback -->
                    <div class="flex-shrink-0 {{ $group->cover_image_url ? '-mt-16 sm:-mt-20' : '' }}">
                        @if($group->avatar_url)
                            <img
                                src="{{ $group->avatar_url }}"
                                alt="{{ $group->name }}"
                                class="w-24 h-24 sm:w-28 sm:h-28 lg:w-32 lg:h-32 rounded-2xl object-cover ring-4 ring-slate-900/80 shadow-xl bg-slate-700"
                                onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                            >
                            <div class="hidden w-24 h-24 sm:w-28 sm:h-28 lg:w-32 lg:h-32 rounded-2xl bg-gradient-to-br from-purple-500 to-pink-500 items-center justify-center text-white font-bold text-3xl sm:text-4xl ring-4 ring-slate-900/80 shadow-xl">
                                {{ strtoupper(substr($group->name, 0, 2)) }}
                            </div>
                        @else
                            <div class="w-24 h-24 sm:w-28 sm:h-28 lg:w-32 lg:h-32 rounded-2xl bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center text-white font-bold text-3xl sm:text-4xl ring-4 ring-slate-900/80 shadow-xl">
                                {{ strtoupper(substr($group->name, 0, 2)) }}
                            </div>
                        @endif
                    </div>

                    <!-- Group Info -->
                    <div class="flex-1 min-w-0 text-center sm:text-left {{ $group->cover_image_url ? 'sm:mt-4' : '' }}">
                        <h1 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-white truncate">{{ $group->name }}</h1>

                        @if($group->description)
                            <p class="text-gray-400 mt-2 text-sm sm:text-base line-clamp-2 sm:line-clamp-3">{{ $group->description }}</p>
                        @endif

                        <!-- Meta Info Row -->
                        <div class="mt-3 sm:mt-4 flex flex-wrap justify-center sm:justify-start items-center gap-2 sm:gap-3">
                            <span class="inline-flex items-center gap-1.5 text-sm text-gray-300">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                                {{ $membersCount }} {{ Str::plural('member', $membersCount) }}
                            </span>

                            <span class="px-2.5 py-1 rounded-full text-xs font-medium {{ $group->privacy === 'public' ? 'bg-green-500/20 text-green-400' : 'bg-orange-500/20 text-orange-400' }}">
                                {{ ucfirst($group->privacy) }}
                            </span>
                        </div>

                        <!-- Tags -->
                        @if($tags->isNotEmpty())
                            <div class="mt-3 flex flex-wrap justify-center sm:justify-start gap-2">
                                @foreach($tags as $tag)
                                    <span class="px-2.5 py-1 bg-purple-500/20 text-purple-300 rounded-lg text-xs font-medium">
                                        #{{ $tag->name }}
                                    </span>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <!-- Action Buttons - Stack on mobile, row on desktop -->
                    <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto mt-2 sm:mt-0">
                        {{-- Share Group Button (Available to Everyone) --}}
                        <div x-data="{
                            copied: false,
                            url: '{{ route('groups.public', $group->slug) }}',
                            copy() {
                                navigator.clipboard.writeText(this.url).then(() => {
                                    this.copied = true;
                                    window.dispatchEvent(new CustomEvent('show-toast', {
                                        detail: { message: 'Group link copied to clipboard!', type: 'success' }
                                    }));
                                    setTimeout(() => this.copied = false, 2000);
                                });
                            }
                        }">
                            <button @click="copy()"
                                    class="inline-flex items-center justify-center gap-2 px-5 py-3 sm:py-2.5 bg-slate-800/50 border border-white/10 rounded-xl font-semibold text-sm sm:text-base hover:border-purple-500/50 active:scale-95 transition-all min-h-[44px]"
                                    :class="copied ? 'border-green-500/50 bg-green-500/10' : ''">
                                <svg x-show="!copied" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"></path>
                                </svg>
                                <svg x-show="copied" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span x-text="copied ? 'Copied!' : 'Share'"></span>
                            </button>
                        </div>

                        @auth
                            @if ($isAdmin)
                                <a href="{{ route('groups.settings', $group) }}"
                                   class="inline-flex items-center justify-center gap-2 px-5 py-3 sm:py-2.5 bg-gradient-to-r from-cyan-500 to-blue-500 rounded-xl font-semibold text-sm sm:text-base hover:scale-105 active:scale-95 transition-all shadow-lg min-h-[44px]">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    Settings
                                </a>
                            @elseif ($isMember)
                                <button wire:click="leaveGroup" wire:loading.attr="disabled"
                                        class="inline-flex items-center justify-center gap-2 px-5 py-3 sm:py-2.5 bg-slate-800/50 border border-white/10 rounded-xl font-semibold text-sm sm:text-base hover:border-red-500/50 hover:bg-red-500/10 active:scale-95 transition-all min-h-[44px]">
                                    <span wire:loading.remove wire:target="leaveGroup">Leave Group</span>
                                    <span wire:loading wire:target="leaveGroup" class="inline-flex items-center gap-2">
                                        <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Leaving...
                                    </span>
                                </button>
                            @elseif ($hasPendingRequest)
                                <button wire:click="cancelJoinRequest" wire:loading.attr="disabled"
                                        class="inline-flex items-center justify-center gap-2 px-5 py-3 sm:py-2.5 bg-yellow-600/30 border border-yellow-500/30 rounded-xl font-semibold text-sm sm:text-base text-yellow-300 hover:bg-yellow-600/40 active:scale-95 transition-all min-h-[44px]">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <span wire:loading.remove wire:target="cancelJoinRequest">Pending</span>
                                    <span wire:loading wire:target="cancelJoinRequest">Cancelling...</span>
                                </button>
                            @else
                                <button wire:click="joinGroup" wire:loading.attr="disabled"
                                        class="inline-flex items-center justify-center gap-2 px-5 py-3 sm:py-2.5 bg-gradient-to-r from-pink-500 to-purple-500 rounded-xl font-semibold text-sm sm:text-base hover:scale-105 active:scale-95 transition-all shadow-lg min-h-[44px]">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                                    </svg>
                                    <span wire:loading.remove wire:target="joinGroup">
                                        {{ $group->privacy === 'private' ? 'Request to Join' : 'Join Group' }}
                                    </span>
                                    <span wire:loading wire:target="joinGroup" class="inline-flex items-center gap-2">
                                        <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Joining...
                                    </span>
                                </button>
                            @endif
                        @else
                            <a href="{{ route('login') }}"
                               class="inline-flex items-center justify-center gap-2 px-5 py-3 sm:py-2.5 bg-gradient-to-r from-pink-500 to-purple-500 rounded-xl font-semibold text-sm sm:text-base hover:scale-105 active:scale-95 transition-all shadow-lg min-h-[44px]">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                                </svg>
                                Login to Join
                            </a>
                        @endauth
                    </div>
                </div>
            </div>
        </div>

        @if ($isMember || $group->privacy === 'public')
            <!-- Tab Navigation - Horizontally Scrollable on Mobile -->
            <div class="relative glass-card mb-4 sm:mb-6 overflow-hidden">
                <div class="flex overflow-x-auto scrollbar-hide -mx-px px-2 py-2 sm:py-3 gap-1 sm:gap-2 sm:justify-center">
                    <button wire:click="switchTab('timeline')"
                            class="flex-shrink-0 px-4 sm:px-6 py-2.5 sm:py-2 rounded-xl font-medium text-sm sm:text-base transition-all min-h-[44px] flex items-center gap-2 active:scale-95
                            {{ $activeTab === 'timeline' ? 'bg-gradient-to-r from-purple-500/30 to-pink-500/30 text-white border border-purple-500/30' : 'text-gray-400 hover:text-white hover:bg-white/5' }}">
                        <svg class="w-4 h-4 sm:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
                        </svg>
                        Timeline
                    </button>

                    <button wire:click="switchTab('members')"
                            class="flex-shrink-0 px-4 sm:px-6 py-2.5 sm:py-2 rounded-xl font-medium text-sm sm:text-base transition-all min-h-[44px] flex items-center gap-2 active:scale-95
                            {{ $activeTab === 'members' ? 'bg-gradient-to-r from-purple-500/30 to-pink-500/30 text-white border border-purple-500/30' : 'text-gray-400 hover:text-white hover:bg-white/5' }}">
                        <svg class="w-4 h-4 sm:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                        Members
                    </button>

                    <button wire:click="switchTab('chat')"
                            class="flex-shrink-0 px-4 sm:px-6 py-2.5 sm:py-2 rounded-xl font-medium text-sm sm:text-base transition-all min-h-[44px] flex items-center gap-2 active:scale-95
                            {{ $activeTab === 'chat' ? 'bg-gradient-to-r from-purple-500/30 to-pink-500/30 text-white border border-purple-500/30' : 'text-gray-400 hover:text-white hover:bg-white/5' }}">
                        <svg class="w-4 h-4 sm:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                        </svg>
                        Chat
                    </button>

                    @if($isAdmin)
                        <button wire:click="switchTab('requests')"
                                class="flex-shrink-0 px-4 sm:px-6 py-2.5 sm:py-2 rounded-xl font-medium text-sm sm:text-base transition-all min-h-[44px] flex items-center gap-2 relative active:scale-95
                                {{ $activeTab === 'requests' ? 'bg-gradient-to-r from-purple-500/30 to-pink-500/30 text-white border border-purple-500/30' : 'text-gray-400 hover:text-white hover:bg-white/5' }}">
                            <svg class="w-4 h-4 sm:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                            </svg>
                            Requests
                            @if($pendingRequestsCount > 0)
                                <span class="absolute -top-1 -right-1 sm:static sm:ml-1 bg-pink-500 text-white text-xs rounded-full min-w-[20px] h-5 px-1.5 flex items-center justify-center font-bold">
                                    {{ $pendingRequestsCount }}
                                </span>
                            @endif
                        </button>
                    @endif
                </div>
            </div>

            <!-- Tab Content -->
            <div>
                @if ($activeTab === 'timeline')
                    @livewire('groups.group-timeline', ['group' => $group], key('timeline-'.$group->id))
                @elseif ($activeTab === 'members')
                    @livewire('groups.group-members', ['group' => $group], key('members-'.$group->id))
                @elseif ($activeTab === 'chat')
                    <div class="relative glass-card p-4 sm:p-6 lg:p-8">
                        <div class="top-accent-center"></div>
                        @if ($isMember && $conversationId)
                            @livewire('chat.chat-component', ['conversationId' => $conversationId], key('chat-'.$group->id))
                        @elseif ($isMember)
                            <div class="text-center py-8 sm:py-12">
                                <div class="animate-pulse">
                                    <svg class="w-12 h-12 sm:w-16 sm:h-16 mx-auto text-purple-500/50 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                    </svg>
                                </div>
                                <h3 class="text-lg sm:text-xl font-semibold text-gray-300 mb-2">Setting up chat...</h3>
                                <p class="text-gray-500 text-sm sm:text-base">This will only take a moment</p>
                            </div>
                        @else
                            <div class="text-center py-8 sm:py-12">
                                <svg class="w-12 h-12 sm:w-16 sm:h-16 mx-auto text-purple-500/50 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                </svg>
                                <h3 class="text-lg sm:text-xl font-semibold text-gray-300 mb-2">Members Only</h3>
                                <p class="text-gray-500 text-sm sm:text-base mb-6">Join this group to participate in the chat.</p>
                                @auth
                                    <button wire:click="joinGroup"
                                            class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-gradient-to-r from-pink-500 to-purple-500 rounded-xl font-semibold text-sm sm:text-base hover:scale-105 active:scale-95 transition-all shadow-lg min-h-[44px]">
                                        {{ $group->privacy === 'private' ? 'Request to Join' : 'Join Group' }}
                                    </button>
                                @else
                                    <a href="{{ route('login') }}"
                                       class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-gradient-to-r from-pink-500 to-purple-500 rounded-xl font-semibold text-sm sm:text-base hover:scale-105 active:scale-95 transition-all shadow-lg min-h-[44px]">
                                        Login to Join
                                    </a>
                                @endauth
                            </div>
                        @endif
                    </div>
                @elseif ($activeTab === 'requests' && $isAdmin)
                    <div class="relative glass-card p-4 sm:p-6 lg:p-8">
                        <div class="top-accent-center"></div>
                        @livewire('groups.join-requests-list', ['group' => $group], key('requests-'.$group->id))
                    </div>
                @endif
            </div>
        @else
            <!-- Private Group - Non-Member View -->
            <div class="relative glass-card p-6 sm:p-8 lg:p-12">
                <div class="top-accent-center"></div>
                <div class="text-center py-6 sm:py-8">
                    <div class="w-20 h-20 sm:w-24 sm:h-24 mx-auto mb-6 rounded-full bg-purple-500/10 flex items-center justify-center">
                        <svg class="w-10 h-10 sm:w-12 sm:h-12 text-purple-500/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl sm:text-2xl font-bold text-gray-300 mb-2">Private Group</h3>
                    <p class="text-gray-500 text-sm sm:text-base mb-6 max-w-md mx-auto">
                        This is a private group. Request to join to see posts, events, and chat with members.
                    </p>
                    @auth
                        @if ($hasPendingRequest)
                            <button wire:click="cancelJoinRequest" wire:loading.attr="disabled"
                                    class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-yellow-600/30 border border-yellow-500/30 rounded-xl font-semibold text-sm sm:text-base text-yellow-300 hover:bg-yellow-600/40 active:scale-95 transition-all min-h-[44px]">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span wire:loading.remove wire:target="cancelJoinRequest">Request Pending - Click to Cancel</span>
                                <span wire:loading wire:target="cancelJoinRequest">Cancelling...</span>
                            </button>
                        @else
                            <button wire:click="joinGroup" wire:loading.attr="disabled"
                                    class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-gradient-to-r from-pink-500 to-purple-500 rounded-xl font-semibold text-sm sm:text-base hover:scale-105 active:scale-95 transition-all shadow-lg min-h-[44px]">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                                </svg>
                                <span wire:loading.remove wire:target="joinGroup">Request to Join</span>
                                <span wire:loading wire:target="joinGroup" class="inline-flex items-center gap-2">
                                    <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Sending...
                                </span>
                            </button>
                        @endif
                    @else
                        <a href="{{ route('login') }}"
                           class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-gradient-to-r from-pink-500 to-purple-500 rounded-xl font-semibold text-sm sm:text-base hover:scale-105 active:scale-95 transition-all shadow-lg min-h-[44px]">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                            </svg>
                            Login to Request Access
                        </a>
                    @endauth
                </div>
            </div>
        @endif
    </div>

    <style>
        /* Hide scrollbar but keep functionality */
        .scrollbar-hide {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
        .scrollbar-hide::-webkit-scrollbar {
            display: none;
        }
    </style>
    <style>
        .glass-card {
            background: #151515 !important;
        }
    </style>
</div>