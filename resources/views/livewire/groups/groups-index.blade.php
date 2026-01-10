<div>
    <div class="container mx-auto sm:px-6 py-4 sm:py-8">
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

        <div class="relative p-4 sm:p-6 lg:p-8 glass-card max-w-6xl mx-auto">
            <div class="top-accent-center"></div>

            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 sm:gap-4 mb-4 sm:mb-6">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-bold">Groups</h1>
                    <p class="text-gray-400 mt-1 text-sm sm:text-base">Find communities that match your interests</p>
                </div>
                @auth
                    <a href="{{ route('groups.create') }}" class="w-full sm:w-auto text-center px-6 py-3 bg-gradient-to-r from-pink-500 to-purple-500 rounded-xl font-semibold hover:scale-105 active:scale-95 transition-all shadow-lg">
                        + Create Group
                    </a>
                @endauth
            </div>

            <!-- Tabs -->
            @auth
            <div class="flex gap-2 mb-4 sm:mb-6 border-b border-white/10 pb-3 sm:pb-4 overflow-x-auto -mx-4 px-4 sm:mx-0 sm:px-0">
                <button wire:click="setTab('my-groups')"
                    class="flex-shrink-0 px-4 py-2.5 sm:py-2 rounded-lg font-medium transition-all flex items-center gap-2 text-sm sm:text-base active:scale-95 {{ $tab === 'my-groups' ? 'bg-gradient-to-r from-purple-500 to-pink-500 text-white shadow-lg' : 'bg-slate-800/50 text-gray-400 hover:text-white hover:bg-slate-700/50' }}">
                    <span>👥</span> <span class="whitespace-nowrap">My Groups</span>
                    @if($this->myGroupsCount > 0)
                        <span class="px-2 py-0.5 text-xs rounded-full {{ $tab === 'my-groups' ? 'bg-white/20' : 'bg-purple-500/30 text-purple-300' }}">
                            {{ $this->myGroupsCount }}
                        </span>
                    @endif
                </button>
                <button wire:click="setTab('discover')"
                    class="flex-shrink-0 px-4 py-2.5 sm:py-2 rounded-lg font-medium transition-all flex items-center gap-2 text-sm sm:text-base active:scale-95 {{ $tab === 'discover' ? 'bg-gradient-to-r from-cyan-500 to-blue-500 text-white shadow-lg' : 'bg-slate-800/50 text-gray-400 hover:text-white hover:bg-slate-700/50' }}">
                    <span>🔍</span> <span class="whitespace-nowrap">Discover</span>
                </button>
            </div>
            @endauth

            <!-- Search and Filters -->
            <div class="flex flex-col gap-3 sm:gap-4 mb-4 sm:mb-6">
                <!-- Search -->
                <div class="relative">
                    <input wire:model.live.debounce.300ms="search" type="text"
                        placeholder="{{ $tab === 'my-groups' ? 'Search your groups...' : 'Search groups to discover...' }}"
                        class="w-full pl-10 sm:pl-12 pr-4 py-2.5 sm:py-3 bg-slate-800/50 border border-white/10 rounded-xl focus:border-cyan-500/50 focus:ring-1 focus:ring-cyan-500/30 focus:outline-none transition text-white text-sm sm:text-base placeholder:text-gray-500">
                    <svg class="absolute left-3 sm:left-4 top-1/2 transform -translate-y-1/2 w-4 h-4 sm:w-5 sm:h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>

                <!-- Filters Row -->
                <div class="flex flex-col sm:flex-row sm:flex-wrap gap-3 sm:gap-4 items-stretch sm:items-center">
                    <!-- Privacy Filter -->
                    <select wire:model.live="privacyFilter" class="w-full sm:w-auto px-4 py-2.5 sm:py-2 bg-slate-800/50 border border-white/10 rounded-xl focus:border-cyan-500/50 focus:ring-1 focus:ring-cyan-500/30 focus:outline-none transition text-white text-sm sm:text-base">
                        <option value="all">All Groups</option>
                        <option value="public">Public Only</option>
                        <option value="private">Private Only</option>
                    </select>

                    <!-- Tags -->
                    @if($this->popularTags->count() > 0)
                        <div class="flex flex-wrap gap-2 items-center -mx-1 px-1">
                            @foreach($this->popularTags as $tag)
                                <button wire:click="toggleTag('{{ $tag->id }}')"
                                    class="px-3 py-1.5 sm:py-1 rounded-full text-xs sm:text-sm transition active:scale-95 {{ in_array($tag->id, $selectedTags) ? 'bg-gradient-to-r from-pink-500 to-purple-500 text-white shadow-lg' : 'bg-slate-800/50 border border-white/10 hover:bg-white/10 text-gray-300' }}">
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
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4">
                        @forelse($this->myGroups as $group)
                            <a href="{{ route('groups.show', $group->slug) }}" wire:navigate
                                class="relative p-4 rounded-xl bg-gradient-to-br from-purple-500/10 to-pink-500/10 border border-purple-500/30 hover:border-purple-500/50 active:scale-[0.98] transition-all block hover:scale-[1.02]">
                                <div class="flex items-start gap-3">
                                    <!-- Avatar -->
                                    @if($group->avatar_url)
                                        <img src="{{ $group->avatar_url }}" alt="{{ $group->name }}"
                                            class="w-12 h-12 sm:w-14 sm:h-14 rounded-xl object-cover flex-shrink-0 bg-slate-700"
                                            onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                        <div class="hidden w-12 h-12 sm:w-14 sm:h-14 rounded-xl bg-gradient-to-br from-purple-500 to-pink-500 items-center justify-center text-white font-bold text-lg flex-shrink-0">
                                            {{ strtoupper(substr($group->name, 0, 1)) }}
                                        </div>
                                    @else
                                        <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-xl bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center text-white font-bold text-lg flex-shrink-0">
                                            {{ strtoupper(substr($group->name, 0, 1)) }}
                                        </div>
                                    @endif
                                    <div class="flex-1 min-w-0">
                                        <h3 class="font-bold text-white truncate text-sm sm:text-base">{{ $group->name }}</h3>
                                        <p class="text-gray-400 text-xs sm:text-sm line-clamp-2 mt-0.5">{{ $group->description ?? 'No description' }}</p>
                                        <div class="flex items-center gap-2 mt-2 text-xs">
                                            <span class="text-gray-500">{{ $group->members_count }} members</span>
                                            <span class="px-2 py-0.5 rounded-full {{ $group->privacy === 'public' ? 'bg-green-500/20 text-green-400' : 'bg-orange-500/20 text-orange-400' }}">
                                                {{ ucfirst($group->privacy) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        @empty
                            <div class="col-span-full text-center py-12 sm:py-16">
                                <div class="text-gray-500 text-5xl sm:text-6xl mb-4">👥</div>
                                <p class="text-gray-400 mb-4 text-sm sm:text-base">You haven't joined any groups yet.</p>
                                <button wire:click="setTab('discover')" class="px-6 py-3 bg-gradient-to-r from-cyan-500 to-blue-500 rounded-xl font-semibold hover:scale-105 active:scale-95 transition-all shadow-lg text-sm sm:text-base">
                                    Discover Groups
                                </button>
                            </div>
                        @endforelse
                    </div>

                    <!-- Pagination -->
                    @if($this->myGroups->hasPages())
                        <div class="mt-6 sm:mt-8">
                            {{ $this->myGroups->links() }}
                        </div>
                    @endif
                @endauth
            @endif

            <!-- Discover Groups Tab -->
            @if($tab === 'discover' || !Auth::check())
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4">
                    @forelse($this->discoverGroups as $group)
                        <div class="relative p-4 rounded-xl bg-slate-800/30 border border-white/10 hover:border-cyan-500/30 active:scale-[0.98] transition-all">
                            <a href="{{ route('groups.show', $group->slug) }}" wire:navigate class="block">
                                <div class="flex items-start gap-3">
                                    <!-- Avatar -->
                                    @if($group->avatar_url)
                                        <img src="{{ $group->avatar_url }}" alt="{{ $group->name }}"
                                            class="w-12 h-12 sm:w-14 sm:h-14 rounded-xl object-cover flex-shrink-0 bg-slate-700"
                                            onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                        <div class="hidden w-12 h-12 sm:w-14 sm:h-14 rounded-xl bg-gradient-to-br from-cyan-500 to-blue-500 items-center justify-center text-white font-bold text-lg flex-shrink-0">
                                            {{ strtoupper(substr($group->name, 0, 1)) }}
                                        </div>
                                    @else
                                        <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-xl bg-gradient-to-br from-cyan-500 to-blue-500 flex items-center justify-center text-white font-bold text-lg flex-shrink-0">
                                            {{ strtoupper(substr($group->name, 0, 1)) }}
                                        </div>
                                    @endif
                                    <div class="flex-1 min-w-0">
                                        <h3 class="font-bold text-white truncate text-sm sm:text-base">{{ $group->name }}</h3>
                                        <p class="text-gray-400 text-xs sm:text-sm line-clamp-2 mt-0.5">{{ $group->description ?? 'No description' }}</p>
                                    </div>
                                </div>
                            </a>
                            <div class="flex items-center justify-between gap-3 mt-4 pt-3 border-t border-white/5">
                                <div class="flex items-center gap-2 text-xs flex-wrap">
                                    <span class="text-gray-500 whitespace-nowrap">{{ $group->members_count }} members</span>
                                    <span class="px-2 py-0.5 rounded-full whitespace-nowrap {{ $group->privacy === 'public' ? 'bg-green-500/20 text-green-400' : 'bg-orange-500/20 text-orange-400' }}">
                                        {{ ucfirst($group->privacy) }}
                                    </span>
                                </div>
                                @auth
                                    <button wire:click="joinGroup('{{ $group->id }}')" wire:loading.attr="disabled"
                                        class="flex-shrink-0 px-4 py-1.5 text-xs sm:text-sm bg-gradient-to-r from-pink-500 to-purple-500 rounded-lg font-semibold hover:scale-105 active:scale-95 transition-all shadow-lg disabled:opacity-50">
                                        <span wire:loading.remove wire:target="joinGroup('{{ $group->id }}')">
                                            {{ $group->privacy === 'private' ? 'Request' : 'Join' }}
                                        </span>
                                        <span wire:loading wire:target="joinGroup('{{ $group->id }}')">
                                            <svg class="animate-spin h-4 w-4 inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                        </span>
                                    </button>
                                @endauth
                            </div>
                        </div>
                    @empty
                        <div class="col-span-full text-center py-12 sm:py-16">
                            <div class="text-gray-500 text-5xl sm:text-6xl mb-4">🎉</div>
                            @if($search || $privacyFilter !== 'all' || !empty($selectedTags))
                                <p class="text-gray-400 text-sm sm:text-base">No groups found matching your criteria.</p>
                            @else
                                <p class="text-gray-400 text-sm sm:text-base">You've joined all available groups!</p>
                            @endif
                            @auth
                                <a href="{{ route('groups.create') }}" class="inline-block mt-4 px-6 py-3 bg-gradient-to-r from-pink-500 to-purple-500 rounded-xl font-semibold hover:scale-105 active:scale-95 transition-all shadow-lg text-sm sm:text-base">
                                    Create a new group
                                </a>
                            @endauth
                        </div>
                    @endforelse
                </div>

                <!-- Pagination -->
                @if($this->discoverGroups->hasPages())
                    <div class="mt-6 sm:mt-8">
                        {{ $this->discoverGroups->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>