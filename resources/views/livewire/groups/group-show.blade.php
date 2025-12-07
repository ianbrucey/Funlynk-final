<div>
    {{-- <x-galaxy-layout> --}}
        <x-slot name="title">
            {{ $group->name }}
        </x-slot>

        <div class="container mx-auto px-6 py-8">
            <!-- Flash Messages -->
            @if (session()->has('success'))
                <div class="alert alert-success mb-4 p-4 rounded-xl bg-green-500/20 text-green-300">
                    {{ session('success') }}
                </div>
            @endif

            @if (session()->has('error'))
                <div class="alert alert-error mb-4 p-4 rounded-xl bg-red-500/20 text-red-300">
                    {{ session('error') }}
                </div>
            @endif

            <!-- Group Header -->
            <div class="relative p-8 glass-card max-w-5xl mx-auto mb-8">
                <div class="top-accent-center"></div>
                <div class="flex flex-col md:flex-row items-center gap-8">
                    <img src="{{ $group->avatar_url ?? 'https://via.placeholder.com/150' }}" alt="{{ $group->name }}" class="w-32 h-32 rounded-xl object-cover">
                    <div class="text-center md:text-left flex-grow">
                        <h1 class="text-4xl font-bold">{{ $group->name }}</h1>
                        <p class="text-gray-400 mt-2">{{ $group->description ?? 'No description available.' }}</p>
                        <div class="mt-4 flex flex-wrap justify-center md:justify-start items-center gap-4">
                            <span class="text-sm text-gray-300">{{ $membersCount }} members</span>
                            <span class="text-sm text-gray-300 capitalize">· {{ $group->privacy }}</span>
                            @if($tags->isNotEmpty())
                                <div class="flex flex-wrap gap-2 mt-2 md:mt-0">
                                    @foreach($tags as $tag)
                                        <span class="px-2 py-1 bg-purple-500/20 text-purple-300 rounded-lg text-xs">{{ $tag->name }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="flex flex-col md:flex-row gap-2 mt-4 md:mt-0">
                        @auth
                            @if ($isAdmin)
                                <a href="{{ route('groups.settings', $group) }}" class="px-6 py-3 bg-gradient-to-r from-cyan-500 to-blue-500 rounded-xl font-semibold hover:scale-105 transition-all text-center">
                                    Settings
                                </a>
                            @elseif ($isMember)
                                <button wire:click="leaveGroup" wire:loading.attr="disabled" class="px-6 py-3 bg-slate-800/50 border border-white/10 rounded-xl hover:border-cyan-500/50 transition">
                                    <span wire:loading.remove wire:target="leaveGroup">Leave Group</span>
                                    <span wire:loading wire:target="leaveGroup">Leaving...</span>
                                </button>
                            @elseif ($hasPendingRequest)
                                <button wire:click="cancelJoinRequest" wire:loading.attr="disabled" class="px-6 py-3 bg-yellow-600/50 border border-yellow-500/30 rounded-xl font-semibold hover:bg-yellow-600 transition">
                                    <span wire:loading.remove wire:target="cancelJoinRequest">Request Pending</span>
                                    <span wire:loading wire:target="cancelJoinRequest">Cancelling...</span>
                                </button>
                            @else
                                <button wire:click="joinGroup" wire:loading.attr="disabled" class="px-6 py-3 bg-gradient-to-r from-pink-500 to-purple-500 rounded-xl font-semibold hover:scale-105 transition-all">
                                    @if($group->privacy === 'private')
                                        <span wire:loading.remove wire:target="joinGroup">Request to Join</span>
                                    @else
                                        <span wire:loading.remove wire:target="joinGroup">Join Group</span>
                                    @endif
                                    <span wire:loading wire:target="joinGroup">...</span>
                                </button>
                            @endif
                        @endauth
                    </div>
                </div>
            </div>

            @if ($isMember || $group->privacy === 'public')
                <!-- Tab Navigation -->
                <div class="relative p-2 glass-card max-w-5xl mx-auto mb-8">
                    <div class="flex justify-center gap-2">
                        <button wire:click="switchTab('timeline')" class="px-6 py-2 rounded-lg {{ $activeTab === 'timeline' ? 'bg-white/10' : '' }} hover:bg-white/10 transition">Timeline</button>
                        <button wire:click="switchTab('members')" class="px-6 py-2 rounded-lg {{ $activeTab === 'members' ? 'bg-white/10' : '' }} hover:bg-white/10 transition">Members</button>
                        <button wire:click="switchTab('chat')" class="px-6 py-2 rounded-lg {{ $activeTab === 'chat' ? 'bg-white/10' : '' }} hover:bg-white/10 transition">Chat</button>
                        @if($isAdmin)
                            <button wire:click="switchTab('requests')" class="px-6 py-2 rounded-lg {{ $activeTab === 'requests' ? 'bg-white/10' : '' }} hover:bg-white/10 transition relative">
                                Requests
                                @if($pendingRequestsCount > 0)
                                    <span class="absolute -top-1 -right-1 bg-pink-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">{{ $pendingRequestsCount }}</span>
                                @endif
                            </button>
                        @endif
                    </div>
                </div>

                <!-- Tab Content -->
                <div class="max-w-5xl mx-auto">
                    @if ($activeTab === 'timeline')
                        <div class="mt-8">
                            @livewire('groups.group-timeline', ['group' => $group], key('timeline-'.$group->id))
                        </div>
                    @elseif ($activeTab === 'members')
                        <div class="mt-8">
                            @livewire('groups.group-members', ['group' => $group], key('members-'.$group->id))
                        </div>
                    @elseif ($activeTab === 'chat')
                        <div class="relative p-8 glass-card">
                            <div class="top-accent-center"></div>
                            @if ($isMember && $conversationId)
                                @livewire('chat.chat-component', ['conversationId' => $conversationId], key('chat-'.$group->id))
                            @elseif ($isMember)
                                <div class="text-center py-8">
                                    <h3 class="text-xl font-semibold text-gray-300 mb-2">Chat Loading...</h3>
                                    <p class="text-gray-400">Setting up group chat...</p>
                                </div>
                            @else
                                <div class="text-center py-8">
                                    <h3 class="text-xl font-semibold text-gray-300 mb-2">Members Only</h3>
                                    <p class="text-gray-400">Join this group to participate in the chat.</p>
                                    @auth
                                        <button wire:click="joinGroup" class="mt-4 px-6 py-3 bg-gradient-to-r from-pink-500 to-purple-500 rounded-xl font-semibold hover:scale-105 transition-all">
                                            @if($group->privacy === 'private')
                                                Request to Join
                                            @else
                                                Join Group
                                            @endif
                                        </button>
                                    @else
                                        <a href="{{ route('login') }}" class="mt-4 inline-block px-6 py-3 bg-gradient-to-r from-pink-500 to-purple-500 rounded-xl font-semibold hover:scale-105 transition-all">
                                            Login to Join
                                        </a>
                                    @endauth
                                </div>
                            @endif
                        </div>
                    @elseif ($activeTab === 'requests' && $isAdmin)
                        <div class="relative p-8 glass-card">
                            <div class="top-accent-center"></div>
                            @livewire('groups.join-requests-list', ['group' => $group], key('requests-'.$group->id))
                        </div>
                    @endif
                </div>
            @else
                <!-- Private Group - Non-Member View -->
                <div class="relative p-8 glass-card max-w-5xl mx-auto">
                    <div class="top-accent-center"></div>
                    <div class="text-center py-12">
                        <svg class="w-24 h-24 mx-auto mb-6 text-purple-500/50" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                        <h3 class="text-2xl font-bold text-gray-300 mb-2">Private Group</h3>
                        <p class="text-gray-400 mb-6">This is a private group. Request to join to see the content.</p>
                        @auth
                            @if ($hasPendingRequest)
                                <button wire:click="cancelJoinRequest" wire:loading.attr="disabled" class="px-6 py-3 bg-yellow-600/50 border border-yellow-500/30 rounded-xl font-semibold hover:bg-yellow-600 transition">
                                    <span wire:loading.remove wire:target="cancelJoinRequest">Request Pending</span>
                                    <span wire:loading wire:target="cancelJoinRequest">Cancelling...</span>
                                </button>
                            @else
                                <button wire:click="joinGroup" wire:loading.attr="disabled" class="px-6 py-3 bg-gradient-to-r from-pink-500 to-purple-500 rounded-xl font-semibold hover:scale-105 transition-all">
                                    <span wire:loading.remove wire:target="joinGroup">Request to Join</span>
                                    <span wire:loading wire:target="joinGroup">Sending Request...</span>
                                </button>
                            @endif
                        @else
                            <a href="{{ route('login') }}" class="inline-block px-6 py-3 bg-gradient-to-r from-pink-500 to-purple-500 rounded-xl font-semibold hover:scale-105 transition-all">
                                Login to Request Access
                            </a>
                        @endauth
                    </div>
                </div>
            @endif

        </div>
    {{-- </x-galaxy-layout> --}}
</div>