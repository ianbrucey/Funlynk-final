<div>
    {{-- <x-galaxy-layout> --}}
        <x-slot name="title">
            Discover Groups
        </x-slot>

        <div class="container mx-auto px-6 py-8">
            <div class="relative p-8 glass-card max-w-6xl mx-auto">
                <div class="top-accent-center"></div>

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

                <!-- Header and Search -->
                <div class="flex flex-col md:flex-row justify-between items-center mb-8">
                    <h1 class="text-3xl font-bold mb-4 md:mb-0">Groups</h1>
                    <div class="w-full md:w-1/2 relative">
                        <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search groups..." class="w-full px-4 py-3 bg-slate-800/50 border border-white/10 rounded-2xl focus:border-cyan-500/50 focus:outline-none transition text-white">
                        <svg class="absolute left-4 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>
                </div>

                <!-- Filters -->
                <div class="flex flex-col md:flex-row gap-4 mb-8">
                    <!-- Privacy Filter -->
                    <div class="w-full md:w-1/3">
                        <label for="privacyFilter" class="block text-sm font-medium text-gray-300 mb-1">Privacy</label>
                        <select wire:model="privacyFilter" id="privacyFilter" class="w-full px-4 py-3 bg-slate-800/50 border border-white/10 rounded-2xl focus:border-cyan-500/50 focus:outline-none transition text-white">
                            <option value="public">Public</option>
                            <option value="private">Private</option>
                            <option value="all">All</option>
                        </select>
                    </div>

                    <!-- Tag Filter -->
                    <div class="w-full md:w-2/3">
                        <label class="block text-sm font-medium text-gray-300 mb-1">Tags</label>
                        <div class="flex flex-wrap gap-2">
                            @foreach($tags as $tag)
                                <button wire:click="toggleTag('{{ $tag['id'] }}')" class="px-3 py-1 rounded-full text-sm transition
                                    {{ in_array($tag['id'], $selectedTags) ? 'bg-gradient-to-r from-pink-500 to-purple-500 text-white' : 'bg-slate-800/50 border border-white/10 hover:bg-white/10' }}">
                                    #{{ $tag['name'] }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- My Groups -->
                @if(count($userGroups) > 0)
                    <div class="mb-12">
                        <h2 class="text-2xl font-bold mb-4">My Groups</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            @foreach($userGroups as $group)
                                <a href="{{ route('groups.show', $group['slug']) }}" wire:navigate class="relative p-4 rounded-2xl bg-slate-800/50 border border-white/10 hover:border-purple-500/50 transition-all group cursor-pointer block hover:scale-[1.02] transition-transform">
                                    <h3 class="text-xl font-bold mb-2">{{ $group['name'] }}</h3>
                                    <p class="text-gray-400 text-sm mb-4 line-clamp-2">{{ $group['description'] }}</p>
                                    <div class="flex items-center justify-between text-sm text-gray-300">
                                        <div class="flex items-center gap-2">
                                            <span>{{ $group['members_count'] ?? 0 }} Members</span>
                                            <span class="px-2 py-0.5 rounded-full text-xs {{ $group['privacy'] === 'public' ? 'bg-green-500/20 text-green-300' : 'bg-orange-500/20 text-orange-300' }}">
                                                {{ ucfirst($group['privacy']) }}
                                            </span>
                                        </div>
                                        <button wire:click.stop="leaveGroup('{{ $group['id'] }}')" wire:loading.attr="disabled" class="px-4 py-2 bg-slate-800/50 border border-white/10 rounded-xl hover:border-cyan-500/50 transition">
                                            <span wire:loading.remove wire:target="leaveGroup('{{ $group['id'] }}')">Leave</span>
                                            <span wire:loading wire:target="leaveGroup('{{ $group['id'] }}')">Leaving...</span>
                                        </button>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Discover Groups -->
                <div>
                    <h2 class="text-2xl font-bold mb-4">Discover Groups</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @forelse($groups as $group)
                            <a href="{{ route('groups.show', $group->slug) }}" wire:navigate class="relative p-4 rounded-2xl bg-slate-800/50 border border-white/10 hover:border-purple-500/50 transition-all group cursor-pointer block hover:scale-[1.02] transition-transform">
                                <h3 class="text-xl font-bold mb-2">{{ $group->name }}</h3>
                                <p class="text-gray-400 text-sm mb-4 line-clamp-2">{{ $group->description }}</p>
                                <div class="flex items-center justify-between text-sm text-gray-300">
                                    <div class="flex items-center gap-2">
                                        <span>{{ $group->members_count }} Members</span>
                                        <span class="px-2 py-0.5 rounded-full text-xs {{ $group->privacy === 'public' ? 'bg-green-500/20 text-green-300' : 'bg-orange-500/20 text-orange-300' }}">
                                            {{ ucfirst($group->privacy) }}
                                        </span>
                                    </div>
                                    @if(in_array($group->id, array_column($userGroups, 'id')))
                                        <button wire:click.stop="leaveGroup('{{ $group->id }}')" wire:loading.attr="disabled" class="px-4 py-2 bg-slate-800/50 border border-white/10 rounded-xl hover:border-cyan-500/50 transition">
                                            <span wire:loading.remove wire:target="leaveGroup('{{ $group->id }}')">Leave</span>
                                            <span wire:loading wire:target="leaveGroup('{{ $group->id }}')">Leaving...</span>
                                        </button>
                                    @else
                                        <button wire:click.stop="joinGroup('{{ $group->id }}')" wire:loading.attr="disabled" class="px-4 py-2 bg-gradient-to-r from-pink-500 to-purple-500 rounded-xl font-semibold hover:scale-105 transition-all">
                                            <span wire:loading.remove wire:target="joinGroup('{{ $group->id }}')">Join</span>
                                            <span wire:loading wire:target="joinGroup('{{ $group->id }}')">Joining...</span>
                                        </button>
                                    @endif
                                </div>
                            </a>
                        @empty
                            <p class="text-gray-400 col-span-full">No groups found matching your criteria.</p>
                        @endforelse
                    </div>

                    <div class="mt-8">
                        {{ $groups->links() }}
                    </div>
                </div>

            </div>
        </div>
    {{-- </x-galaxy-layout> --}}
</div>