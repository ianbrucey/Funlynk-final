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
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
                <div>
                    <h1 class="text-3xl font-bold">Discover Groups</h1>
                    <p class="text-gray-400 mt-1">Find communities that match your interests</p>
                </div>
                @auth
                    <a href="{{ route('groups.create') }}" class="px-6 py-3 bg-gradient-to-r from-pink-500 to-purple-500 rounded-xl font-semibold hover:scale-105 transition-all">
                        + Create Group
                    </a>
                @endauth
            </div>

            <!-- Search and Filters -->
            <div class="flex flex-col gap-4 mb-8">
                <!-- Search -->
                <div class="relative">
                    <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search groups by name or description..."
                        class="w-full pl-12 pr-4 py-3 bg-slate-800/50 border border-white/10 rounded-xl focus:border-cyan-500/50 focus:outline-none transition text-white">
                    <svg class="absolute left-4 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>

                <!-- Filters Row -->
                    <!-- Privacy Filter -->
                    <div class="w-full md:w-auto">
                        <select wire:model.live="privacyFilter" class="px-4 py-2 bg-slate-800/50 border border-white/10 rounded-xl focus:border-cyan-500/50 focus:outline-none transition text-white">
                            <option value="all">All Groups</option>
                            <option value="public">Public Only</option>
                            <option value="private">Private Only</option>
                        </select>
                    </div>

                    <!-- Tags -->
                    @if(count($tags) > 0)
                        <div class="flex flex-wrap gap-2 items-center">
                            @foreach($tags as $tag)
                                <button wire:click="toggleTag('{{ $tag['id'] }}')"
                                    class="px-3 py-1 rounded-full text-sm transition {{ in_array($tag['id'], $selectedTags) ? 'bg-gradient-to-r from-pink-500 to-purple-500 text-white' : 'bg-slate-800/50 border border-white/10 hover:bg-white/10' }}">
                                    #{{ $tag['name'] }}
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <!-- My Groups Section -->
            @auth
                @if(count($userGroups) > 0)
                    <div class="mb-10">
                        <h2 class="text-xl font-bold mb-4 flex items-center gap-2">
                            <span class="text-cyan-400">👥</span> My Groups
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach($userGroups as $group)
                                <a href="{{ route('groups.show', $group['slug']) }}" wire:navigate
                                    class="relative p-4 rounded-xl bg-gradient-to-br from-purple-500/10 to-pink-500/10 border border-purple-500/30 hover:border-purple-500/50 transition-all block hover:scale-[1.02]">
                                    <div class="flex items-start gap-3">
                                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center text-white font-bold text-lg flex-shrink-0">
                                            {{ strtoupper(substr($group['name'], 0, 1)) }}
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <h3 class="font-bold text-white truncate">{{ $group['name'] }}</h3>
                                            <p class="text-gray-400 text-sm line-clamp-1">{{ $group['description'] ?? 'No description' }}</p>
                                            <div class="flex items-center gap-2 mt-2 text-xs text-gray-500">
                                                <span>{{ $group['members_count'] ?? 0 }} members</span>
                                                <span class="px-2 py-0.5 rounded-full {{ $group['privacy'] === 'public' ? 'bg-green-500/20 text-green-400' : 'bg-orange-500/20 text-orange-400' }}">
                                                    {{ ucfirst($group['privacy']) }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                    <hr class="border-white/10 mb-8">
                @endif
            @endauth

            <!-- Discover Groups Section -->
            <div>
                <h2 class="text-xl font-bold mb-4 flex items-center gap-2">
                    <span class="text-pink-400">🔍</span> Discover Groups
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @forelse($groups as $group)
                        @php
                            $isMember = in_array($group->id, array_column($userGroups, 'id'));
                        @endphp
                        <div class="relative p-4 rounded-xl bg-slate-800/30 border border-white/10 hover:border-cyan-500/30 transition-all">
                            <a href="{{ route('groups.show', $group->slug) }}" wire:navigate class="block">
                                <div class="flex items-start gap-3">
                                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-cyan-500 to-blue-500 flex items-center justify-center text-white font-bold text-lg flex-shrink-0">
                                        {{ strtoupper(substr($group->name, 0, 1)) }}
                                    </div>
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
                                    @if($isMember)
                                        <span class="text-xs text-cyan-400">✓ Joined</span>
                                    @else
                                        <button wire:click="joinGroup('{{ $group->id }}')" wire:loading.attr="disabled"
                                            class="px-3 py-1 text-sm bg-gradient-to-r from-pink-500 to-purple-500 rounded-lg font-semibold hover:scale-105 transition-all">
                                            <span wire:loading.remove wire:target="joinGroup('{{ $group->id }}')">
                                                {{ $group->privacy === 'private' ? 'Request' : 'Join' }}
                                            </span>
                                            <span wire:loading wire:target="joinGroup('{{ $group->id }}')">...</span>
                                        </button>
                                    @endif
                                @endauth
                            </div>
                        </div>
                    @empty
                        <div class="col-span-full text-center py-12">
                            <div class="text-gray-500 text-6xl mb-4">🔍</div>
                            <p class="text-gray-400">No groups found matching your criteria.</p>
                            @auth
                                <a href="{{ route('groups.create') }}" class="inline-block mt-4 px-6 py-3 bg-gradient-to-r from-pink-500 to-purple-500 rounded-xl font-semibold hover:scale-105 transition-all">
                                    Create the first one!
                                </a>
                            @endauth
                        </div>
                    @endforelse
                </div>

                <!-- Pagination -->
                @if($groups->hasPages())
                    <div class="mt-8">
                        {{ $groups->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>