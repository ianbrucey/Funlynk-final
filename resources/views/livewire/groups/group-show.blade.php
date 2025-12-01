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
                                <button wire:click="editGroup" wire:loading.attr="disabled" class="px-6 py-3 bg-gradient-to-r from-cyan-500 to-blue-500 rounded-xl font-semibold hover:scale-105 transition-all">
                                    <span wire:loading.remove wire:target="editGroup">Edit Group</span>
                                    <span wire:loading wire:target="editGroup">Editing...</span>
                                </button>
                                <button wire:click="deleteGroup" wire:loading.attr="disabled" class="px-6 py-3 bg-red-600/50 border border-white/10 rounded-xl hover:border-red-500/50 transition">
                                    <span wire:loading.remove wire:target="deleteGroup">Delete Group</span>
                                    <span wire:loading wire:target="deleteGroup">Deleting...</span>
                                </button>
                            @else
                                @if ($isMember)
                                    <button wire:click="leaveGroup" wire:loading.attr="disabled" class="px-6 py-3 bg-slate-800/50 border border-white/10 rounded-xl hover:border-cyan-500/50 transition">
                                        <span wire:loading.remove wire:target="leaveGroup">Leave Group</span>
                                        <span wire:loading wire:target="leaveGroup">Leaving...</span>
                                    </button>
                                @else
                                    <button wire:click="joinGroup" wire:loading.attr="disabled" class="px-6 py-3 bg-gradient-to-r from-pink-500 to-purple-500 rounded-xl font-semibold hover:scale-105 transition-all">
                                        <span wire:loading.remove wire:target="joinGroup">Join Group</span>
                                        <span wire:loading wire:target="joinGroup">Joining...</span>
                                    </button>
                                @endif
                            @endif
                        @endauth
                    </div>
                </div>
            </div>

            <!-- Tab Navigation -->
            <div class="relative p-2 glass-card max-w-5xl mx-auto mb-8">
                <div class="flex justify-center gap-2">
                    <button wire:click="switchTab('timeline')" class="px-6 py-2 rounded-lg {{ $activeTab === 'timeline' ? 'bg-white/10' : '' }} hover:bg-white/10 transition">Timeline</button>
                    <button wire:click="switchTab('members')" class="px-6 py-2 rounded-lg {{ $activeTab === 'members' ? 'bg-white/10' : '' }} hover:bg-white/10 transition">Members</button>
                    <button wire:click="switchTab('chat')" class="px-6 py-2 rounded-lg {{ $activeTab === 'chat' ? 'bg-white/10' : '' }} hover:bg-white/10 transition">Chat</button>
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
                        <h3 class="text-2xl font-bold mb-4">Group Chat</h3>
                        <p class="text-gray-400">Chat functionality will be implemented here.</p>
                    </div>
                @endif
            </div>

        </div>
    {{-- </x-galaxy-layout> --}}
</div>