<div class="space-y-2">
    @forelse($conversations as $conversation)
        <div wire:click="selectConversation('{{ $conversation['id'] }}')"
             class="relative p-4 glass-card rounded-xl cursor-pointer transition-all duration-300 hover:scale-[1.02] {{ $selectedConversationId === $conversation['id'] ? 'border-cyan-500 shadow-lg shadow-cyan-500/20' : 'border-white/10 hover:border-cyan-500/50' }}">

            <div class="flex items-center gap-4">
                {{-- Avatar --}}
                <div class="relative flex-shrink-0">
                    <img src="{{ $conversation['other_user']['avatar'] }}"
                         alt="{{ $conversation['other_user']['name'] }}"
                         class="w-14 h-14 rounded-full border-2 border-white/20 object-cover">

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
            <div class="relative inline-block mb-6">
                <div class="absolute inset-0 bg-gradient-to-r from-cyan-500/20 to-purple-500/20 blur-2xl rounded-full"></div>
                <svg class="relative w-20 h-20 mx-auto text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </svg>
            </div>

            <h3 class="text-xl font-semibold text-white mb-2">No conversations yet</h3>
            <p class="text-gray-400 mb-6">Start a conversation from someone's profile</p>

            <a href="{{ route('feed.nearby') }}"
               class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-cyan-500 to-blue-500 rounded-xl text-sm font-semibold text-white hover:scale-105 transition-all shadow-lg hover:shadow-cyan-500/50">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                Discover People
            </a>
        </div>
    @endforelse
</div>
