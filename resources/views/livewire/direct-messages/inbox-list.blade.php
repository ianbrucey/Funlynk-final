<div class="space-y-3">
    {{-- Search Box --}}
    <div class="relative">
        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
        </div>
        <input type="text"
               wire:model.live.debounce.300ms="search"
               placeholder="Search conversations..."
               class="w-full pl-10 pr-4 py-2.5 bg-slate-800/50 border border-white/10 rounded-xl text-white placeholder-gray-400 focus:outline-none focus:border-cyan-500/50 focus:ring-1 focus:ring-cyan-500/30 transition">
        @if($search)
            <button wire:click="$set('search', '')"
                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-white transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        @endif
    </div>

    {{-- Loading indicator --}}
    <div wire:loading.delay wire:target="search" class="flex justify-center py-2">
        <svg class="animate-spin h-5 w-5 text-cyan-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
    </div>

    {{-- Conversation List --}}
    <div class="space-y-2" wire:loading.class="opacity-50" wire:target="search">
        @forelse($conversations as $conversation)
            <div wire:click="selectConversation('{{ $conversation['id'] }}')"
                 class="relative p-4 glass-card rounded-xl cursor-pointer transition-all duration-300 hover:scale-[1.02] active:scale-[0.98] {{ $selectedConversationId === $conversation['id'] ? 'border-cyan-500 shadow-lg shadow-cyan-500/20' : 'border-white/10 hover:border-cyan-500/50' }}">

                <div class="flex items-center gap-4">
                    {{-- Avatar --}}
                    <div class="relative flex-shrink-0">
                        <img src="{{ $conversation['other_user']['avatar'] }}"
                             alt="{{ $conversation['other_user']['name'] }}"
                             class="w-14 h-14 rounded-full border-2 border-white/20 object-cover bg-slate-700"
                             onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode($conversation['other_user']['name']) }}&background=ec4899&color=fff'">

                        @if($conversation['unread_count'] > 0)
                            <div class="absolute -top-1 -right-1 min-w-[20px] h-5 px-1.5 bg-gradient-to-r from-pink-500 to-purple-500 rounded-full flex items-center justify-center text-xs font-bold shadow-lg">
                                {{ $conversation['unread_count'] }}
                            </div>
                        @endif
                    </div>

                    {{-- Content --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between mb-1">
                            <h4 class="text-white font-semibold truncate">
                                {{ $conversation['other_user']['name'] }}
                            </h4>
                            <span class="text-xs text-gray-400 flex-shrink-0 ml-2">
                                {{ $conversation['last_message_at']->diffForHumans() }}
                            </span>
                        </div>

                        <p class="text-sm truncate {{ $conversation['unread_count'] > 0 ? 'text-white font-medium' : 'text-gray-400' }}">
                            {{ $conversation['latest_message'] ?? 'No messages yet' }}
                        </p>
                    </div>

                    {{-- Chevron indicator --}}
                    <div class="flex-shrink-0">
                        <svg class="w-5 h-5 text-gray-500 transition-transform {{ $selectedConversationId === $conversation['id'] ? 'rotate-90 text-cyan-400' : '' }}"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>
                </div>
            </div>
        @empty
            {{-- Empty State --}}
            <div class="text-center py-16">
                @if($search)
                    {{-- No search results --}}
                    <div class="relative inline-block mb-6">
                        <svg class="w-16 h-16 mx-auto text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-white mb-2">No results found</h3>
                    <p class="text-gray-400 mb-4">No conversations match "{{ $search }}"</p>
                    <button wire:click="$set('search', '')"
                            class="text-cyan-400 hover:text-cyan-300 font-medium transition">
                        Clear search
                    </button>
                @else
                    {{-- No conversations at all --}}
                    <div class="relative inline-block mb-6">
                        <div class="absolute inset-0 bg-gradient-to-r from-cyan-500/20 to-purple-500/20 blur-2xl rounded-full"></div>
                        <svg class="relative w-20 h-20 mx-auto text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                        </svg>
                    </div>

                    <h3 class="text-xl font-semibold text-white mb-2">No conversations yet</h3>
                    <p class="text-gray-400 mb-6">Start a conversation from someone's profile</p>

                    <a href="{{ route('search.users') }}"
                       class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-cyan-500 to-blue-500 rounded-xl text-sm font-semibold text-white hover:scale-105 transition-all shadow-lg hover:shadow-cyan-500/50">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        Discover People
                    </a>
                @endif
            </div>
        @endforelse
    </div>

    {{-- Load More Button --}}
    @if($hasMorePages)
        <div class="pt-2">
            <button wire:click="nextPage"
                    wire:loading.attr="disabled"
                    class="w-full py-3 bg-slate-800/50 border border-white/10 rounded-xl text-gray-400 hover:text-white hover:border-cyan-500/50 transition font-medium disabled:opacity-50">
                <span wire:loading.remove wire:target="nextPage">Load more</span>
                <span wire:loading wire:target="nextPage" class="flex items-center justify-center gap-2">
                    <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Loading...
                </span>
            </button>
        </div>
    @endif
</div>
