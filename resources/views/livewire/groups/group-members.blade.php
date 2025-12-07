<div class="min-h-screen">
    <div class="container mx-auto px-6 py-8">
        <div class="relative p-8 glass-card max-w-7xl mx-auto">
            <div class="top-accent-center"></div>

            <h2 class="text-3xl font-bold mb-6 text-white">Group Members ({{ $group->name }})</h2>

            <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
                <div class="relative w-full md:w-1/3">
                    <input type="text"
                           wire:model.live.debounce.300ms="search"
                           placeholder="Search members..."
                           class="w-full pl-12 pr-4 py-3 bg-slate-800/50 border border-white/10 rounded-2xl focus:border-cyan-500/50 focus:outline-none transition text-white"/>
                    <svg class="absolute left-4 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                @can('manageMembers', $group)
                    <button wire:click="inviteMembers" wire:loading.attr="disabled"
                            class="px-6 py-3 bg-gradient-to-r from-pink-500 to-purple-500 rounded-xl font-semibold hover:scale-105 transition-all">
                        <span wire:loading.remove wire:target="inviteMembers">Invite Members</span>
                        <span wire:loading wire:target="inviteMembers">Inviting...</span>
                    </button>
                @endcan
            </div>

            @if (session()->has('success'))
                <div class="bg-green-500/20 text-green-300 p-4 rounded-xl mb-4">
                    {{ session('success') }}
                </div>
            @endif

            @if (session()->has('error'))
                <div class="bg-red-500/20 text-red-300 p-4 rounded-xl mb-4">
                    {{ session('error') }}
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @forelse ($members as $member)
                    <div class="relative p-4 rounded-2xl bg-slate-800/50 border border-white/10 hover:border-purple-500/50 transition-all group flex items-center gap-4">
                        <div class="flex-shrink-0">
                            <img src="{{ Storage::url($member->user->profile_image_url) ?? 'https://ui-avatars.com/api/?name=' . urlencode($member->user->name) . '&background=a855f7&color=fff' }}"
                                 alt="{{ $member->user->name }}"
                                 class="w-12 h-12 rounded-xl object-cover">
                        </div>
                        <div class="flex-grow">
                            <p class="text-lg font-semibold text-white">{{ $member->user->name }}</p>
                            <p class="text-sm text-gray-400">Joined: {{ $member->joined_at->format('M d, Y') }}</p>
                        </div>
                        <div class="flex-shrink-0">
                            <span class="px-3 py-1 rounded-full text-xs font-semibold
                                {{ $member->role === 'admin' ? 'bg-yellow-500/20 text-yellow-300' : 'bg-blue-500/20 text-blue-300' }}">
                                {{ ucfirst($member->role) }}
                            </span>
                        </div>
                        @can('manageMembers', $group)
                            <div class="absolute top-2 right-2 flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                @if ($member->role === 'member')
                                    <button wire:click="changeRole('{{ $member->id }}', 'admin')" wire:loading.attr="disabled"
                                            class="p-2 hover:bg-white/10 rounded-full transition text-white"
                                            title="Make Admin">
                                        <span wire:loading.remove wire:target="changeRole('{{ $member->id }}', 'admin')">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                        </span>
                                        <span wire:loading wire:target="changeRole('{{ $member->id }}', 'admin')">...</span>
                                    </button>
                                @else
                                    <button wire:click="changeRole('{{ $member->id }}', 'member')" wire:loading.attr="disabled"
                                            class="p-2 hover:bg-white/10 rounded-full transition text-white"
                                            title="Demote to Member">
                                        <span wire:loading.remove wire:target="changeRole('{{ $member->id }}', 'member')">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l-6 6m0 0l6 6m-6-6h10a2 2 0 012 2v5a2 2 0 01-2 2H7a2 2 0 01-2-2V9a2 2 0 012-2h6z"></path>
                                            </svg>
                                        </span>
                                        <span wire:loading wire:target="changeRole('{{ $member->id }}', 'member')">...</span>
                                    </button>
                                @endif
                                <button wire:click="removeMember('{{ $member->id }}')" wire:loading.attr="disabled"
                                        class="p-2 hover:bg-red-500/20 rounded-full transition text-red-400"
                                        title="Remove Member">
                                    <span wire:loading.remove wire:target="removeMember('{{ $member->id }}')">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </span>
                                    <span wire:loading wire:target="removeMember('{{ $member->id }}')">...</span>
                                </button>
                            </div>
                        @endcan
                    </div>
                @empty
                    <p class="text-gray-400 text-center col-span-full">No members found.</p>
                @endforelse
            </div>

            <div class="mt-8">
                {{ $members->links() }}
            </div>
        </div>
    </div>
</div>
