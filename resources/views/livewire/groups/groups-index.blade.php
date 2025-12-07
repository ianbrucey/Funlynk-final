<div>
    <div class="container mx-auto px-6 py-8">
        <!-- Flash Messages -->
        @if (session()->has('success'))
            <div class="alert alert-success mb-4 p-4 rounded-xl bg-green-500/20 text-green-300 max-w-6xl mx-auto">
                {{ session('success') }}
            </div>
        @endif

        @if (session()->has('error'))
            <div class="alert alert-error mb-4 p-4 rounded-xl bg-red-500/20 text-red-300 max-w-6xl mx-auto">
                {{ session('error') }}
            </div>
        @endif

        <div class="relative p-8 glass-card max-w-6xl mx-auto">
            <div class="top-accent-center"></div>

            <!-- Header -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
                <div>
                    <h1 class="text-3xl font-bold">Groups</h1>
                    <p class="text-gray-400 mt-1">Find communities that match your interests</p>
                </div>
                @auth
                    <a href="{{ route('groups.create') }}" class="px-6 py-3 bg-gradient-to-r from-pink-500 to-purple-500 rounded-xl font-semibold hover:scale-105 transition-all">
                        + Create Group
                    </a>
                @endauth
            </div>

            <!-- Tabs -->
            @auth
            <div class="flex gap-2 mb-6 border-b border-white/10 pb-4">
                <button wire:click="setTab('my-groups')"
                    class="px-4 py-2 rounded-lg font-medium transition-all flex items-center gap-2 {{ $tab === 'my-groups' ? 'bg-gradient-to-r from-purple-500 to-pink-500 text-white' : 'bg-slate-800/50 text-gray-400 hover:text-white hover:bg-slate-700/50' }}">
                    <span>👥</span> My Groups
                    @if($this->myGroupsCount > 0)
                        <span class="px-2 py-0.5 text-xs rounded-full {{ $tab === 'my-groups' ? 'bg-white/20' : 'bg-purple-500/30 text-purple-300' }}">
                            {{ $this->myGroupsCount }}
                        </span>
                    @endif
                </button>
                <button wire:click="setTab('discover')"
                    class="px-4 py-2 rounded-lg font-medium transition-all flex items-center gap-2 {{ $tab === 'discover' ? 'bg-gradient-to-r from-cyan-500 to-blue-500 text-white' : 'bg-slate-800/50 text-gray-400 hover:text-white hover:bg-slate-700/50' }}">
                    <span>🔍</span> Discover
                </button>
            </div>
            @endauth

            <!-- Search and Filters -->
            <div class="flex flex-col gap-4 mb-6">
                <!-- Search -->
                <div class="relative">
                    <input wire:model.live.debounce.300ms="search" type="text"
                        placeholder="{{ $tab === 'my-groups' ? 'Search your groups...' : 'Search groups to discover...' }}"
                        class="w-full pl-12 pr-4 py-3 bg-slate-800/50 border border-white/10 rounded-xl focus:border-cyan-500/50 focus:outline-none transition text-white">
                    <svg class="absolute left-4 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>

                <!-- Filters Row -->
                <div class="flex flex-wrap gap-4 items-center">
                    <!-- Privacy Filter -->
                    <select wire:model.live="privacyFilter" class="px-4 py-2 bg-slate-800/50 border border-white/10 rounded-xl focus:border-cyan-500/50 focus:outline-none transition text-white">
                        <option value="all">All Groups</option>
                        <option value="public">Public Only</option>
                        <option value="private">Private Only</option>
                    </select>

                    <!-- Tags -->
                    @if($this->popularTags->count() > 0)
                        <div class="flex flex-wrap gap-2 items-center">
                            @foreach($this->popularTags as $tag)
                                <button wire:click="toggleTag('{{ $tag->id }}')"
                                    class="px-3 py-1 rounded-full text-sm transition {{ in_array($tag->id, $selectedTags) ? 'bg-gradient-to-r from-pink-500 to-purple-500 text-white' : 'bg-slate-800/50 border border-white/10 hover:bg-white/10' }}">
                                    #{{ $tag->name }}
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <!-- My Groups Tab -->
            @if($tab === 'my-groups')
                @auth
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @forelse($this->myGroups as $group)
                            <a href="{{ route('groups.show', $group->slug) }}" wire:navigate
                                class="relative p-4 rounded-xl bg-gradient-to-br from-purple-500/10 to-pink-500/10 border border-purple-500/30 hover:border-purple-500/50 transition-all block hover:scale-[1.02]">
                                <div class="flex items-start gap-3">
                                    <!-- Avatar -->
                                    @if($group->avatar_url)
                                        <img src="{{ Storage::url($group->avatar_url) }}" alt="{{ $group->name }}"
                                            class="w-12 h-12 rounded-xl object-cover flex-shrink-0">
                                    @else
                                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center text-white font-bold text-lg flex-shrink-0">
                                            {{ strtoupper(substr($group->name, 0, 1)) }}
                                        </div>
                                    @endif
                                    <div class="flex-1 min-w-0">
                                        <h3 class="font-bold text-white truncate">{{ $group->name }}</h3>
                                        <p class="text-gray-400 text-sm line-clamp-1">{{ $group->description ?? 'No description' }}</p>
                                        <div class="flex items-center gap-2 mt-2 text-xs text-gray-500">
                                            <span>{{ $group->members_count }} members</span>
                                            <span class="px-2 py-0.5 rounded-full {{ $group->privacy === 'public' ? 'bg-green-500/20 text-green-400' : 'bg-orange-500/20 text-orange-400' }}">
                                                {{ ucfirst($group->privacy) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        @empty
                            <div class="col-span-full text-center py-12">
                                <div class="text-gray-500 text-6xl mb-4">👥</div>
                                <p class="text-gray-400 mb-4">You haven't joined any groups yet.</p>
                                <button wire:click="setTab('discover')" class="px-6 py-3 bg-gradient-to-r from-cyan-500 to-blue-500 rounded-xl font-semibold hover:scale-105 transition-all">
                                    Discover Groups
                                </button>
                            </div>
                        @endforelse
                    </div>

                    <!-- Pagination -->
                    @if($this->myGroups->hasPages())
                        <div class="mt-8">
                            {{ $this->myGroups->links() }}
                        </div>
                    @endif
                @endauth
            @endif

            <!-- Discover Groups Tab -->
            @if($tab === 'discover' || !Auth::check())
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @forelse($this->discoverGroups as $group)
                        <div class="relative p-4 rounded-xl bg-slate-800/30 border border-white/10 hover:border-cyan-500/30 transition-all">
                            <a href="{{ route('groups.show', $group->slug) }}" wire:navigate class="block">
                                <div class="flex items-start gap-3">
                                    <!-- Avatar -->
                                    @if($group->avatar_url)
                                        <img src="{{ Storage::url($group->avatar_url) }}" alt="{{ $group->name }}"
                                            class="w-12 h-12 rounded-xl object-cover flex-shrink-0">
                                    @else
                                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-cyan-500 to-blue-500 flex items-center justify-center text-white font-bold text-lg flex-shrink-0">
                                            {{ strtoupper(substr($group->name, 0, 1)) }}
                                        </div>
                                    @endif
                                    <div class="flex-1 min-w-0">
                                        <h3 class="font-bold text-white truncate">{{ $group->name }}</h3>
                                        <p class="text-gray-400 text-sm line-clamp-2">{{ $group->description ?? 'No description' }}</p>
                                    </div>
                                </div>
                            </a>
                            <div class="flex items-center justify-between mt-4 pt-3 border-t border-white/5">
                                <div class="flex items-center gap-2 text-xs text-gray-500">
                                    <span>{{ $group->members_count }} members</span>
                                    <span class="px-2 py-0.5 rounded-full {{ $group->privacy === 'public' ? 'bg-green-500/20 text-green-400' : 'bg-orange-500/20 text-orange-400' }}">
                                        {{ ucfirst($group->privacy) }}
                                    </span>
                                </div>
                                @auth
                                    <button wire:click="joinGroup('{{ $group->id }}')" wire:loading.attr="disabled"
                                        class="px-3 py-1 text-sm bg-gradient-to-r from-pink-500 to-purple-500 rounded-lg font-semibold hover:scale-105 transition-all">
                                        <span wire:loading.remove wire:target="joinGroup('{{ $group->id }}')">
                                            {{ $group->privacy === 'private' ? 'Request' : 'Join' }}
                                        </span>
                                        <span wire:loading wire:target="joinGroup('{{ $group->id }}')">...</span>
                                    </button>
                                @endauth
                            </div>
                        </div>
                    @empty
                        <div class="col-span-full text-center py-12">
                            <div class="text-gray-500 text-6xl mb-4">🎉</div>
                            @if($search || $privacyFilter !== 'all' || !empty($selectedTags))
                                <p class="text-gray-400">No groups found matching your criteria.</p>
                            @else
                                <p class="text-gray-400">You've joined all available groups!</p>
                            @endif
                            @auth
                                <a href="{{ route('groups.create') }}" class="inline-block mt-4 px-6 py-3 bg-gradient-to-r from-pink-500 to-purple-500 rounded-xl font-semibold hover:scale-105 transition-all">
                                    Create a new group
                                </a>
                            @endauth
                        </div>
                    @endforelse
                </div>

                <!-- Pagination -->
                @if($this->discoverGroups->hasPages())
                    <div class="mt-8">
                        {{ $this->discoverGroups->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>