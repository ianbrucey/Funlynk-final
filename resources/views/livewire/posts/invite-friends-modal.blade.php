<div>
    {{-- Invite Friends Modal --}}
    @if($show)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm"
             x-data="{ show: @entangle('show') }"
             x-show="show"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click.self="$wire.closeModal()">

            <div class="relative w-full max-w-lg glass-card border border-white/10 rounded-2xl overflow-hidden"
                 x-transition:enter="transition ease-out duration-300 transform"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-200 transform"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95">

                {{-- Header --}}
                <div class="p-6 border-b border-white/10">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xl font-bold text-white">Invite Friends</h3>
                        <button wire:click="closeModal" class="text-gray-400 hover:text-white transition">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    <p class="text-sm text-gray-400 mt-2">Select friends to invite to this post</p>
                </div>

                {{-- Search --}}
                <div class="p-6 border-b border-white/10">
                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input
                            type="text"
                            wire:model.live.debounce.300ms="search"
                            placeholder="Search friends..."
                            class="w-full pl-10 pr-4 py-3 bg-slate-800/50 border border-white/10 rounded-xl focus:border-purple-500/50 focus:outline-none transition text-white placeholder-gray-500"
                        />
                    </div>
                </div>

                {{-- Friends List --}}
                <div class="p-6 max-h-96 overflow-y-auto">
                    @if($friends->isEmpty())
                        <div class="text-center py-8">
                            <svg class="w-16 h-16 mx-auto text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                            <p class="text-gray-400">
                                @if($search)
                                    No friends found matching "{{ $search }}"
                                @else
                                    You're not following anyone yet
                                @endif
                            </p>
                        </div>
                    @else
                        <div class="space-y-2">
                            @foreach($friends as $friend)
                                <label
                                    class="flex items-center gap-3 p-3 rounded-xl hover:bg-white/5 cursor-pointer transition group">
                                    <input
                                        type="checkbox"
                                        wire:model.live="selectedFriends"
                                        value="{{ $friend->id }}"
                                        class="w-5 h-5 rounded border-white/20 bg-slate-800/50 text-purple-500 focus:ring-purple-500/50 focus:ring-offset-0 cursor-pointer transition-all"
                                    />
                                    <div class="flex items-center gap-3 flex-1">
                                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center text-white font-semibold">
                                            {{ strtoupper(substr($friend->display_name ?: $friend->username, 0, 1)) }}
                                        </div>
                                        <div class="flex-1">
                                            <p class="text-white font-medium group-hover:text-purple-400 transition">{{ $friend->display_name ?: $friend->username }}</p>
                                            <p class="text-xs text-gray-400">{{ '@' . $friend->username }}</p>
                                        </div>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Footer --}}
                <div class="p-6 border-t border-white/10 bg-slate-900/50">
                    <div class="flex items-center justify-between gap-4">
                        <p class="text-sm text-gray-400">
                            {{ count($selectedFriends) }} friend(s) selected
                        </p>
                        <div class="flex gap-3">
                            <button
                                wire:click="closeModal"
                                class="px-6 py-2.5 bg-slate-800/50 border border-white/10 rounded-xl hover:border-white/20 transition text-white font-medium">
                                Cancel
                            </button>
                            <button
                                wire:click="inviteFriends"
                                {{ empty($selectedFriends) ? 'disabled' : '' }}
                                class="px-6 py-2.5 rounded-xl font-semibold transition-all text-white
                                    {{ empty($selectedFriends)
                                        ? 'bg-slate-700 opacity-50 cursor-not-allowed'
                                        : 'bg-gradient-to-r from-purple-500 to-indigo-500 hover:scale-105' }}">
                                <span wire:loading.remove wire:target="inviteFriends">
                                    Invite {{ count($selectedFriends) > 0 ? '('.count($selectedFriends).')' : '' }}
                                </span>
                                <span wire:loading wire:target="inviteFriends">
                                    Sending...
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Success/Error Messages --}}
    @if(session('success'))
        <div class="fixed top-4 right-4 z-50 glass-card border border-green-500/30 rounded-xl p-4 animate-slide-in">
            <div class="flex items-center gap-3">
                <div class="text-2xl">✅</div>
                <p class="text-white font-medium">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="fixed top-4 right-4 z-50 glass-card border border-red-500/30 rounded-xl p-4 animate-slide-in">
            <div class="flex items-center gap-3">
                <div class="text-2xl">❌</div>
                <p class="text-white font-medium">{{ session('error') }}</p>
            </div>
        </div>
    @endif
</div>
