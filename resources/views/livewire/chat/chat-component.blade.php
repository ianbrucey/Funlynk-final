<div class="flex flex-col h-full bg-slate-900/50 backdrop-blur-lg border border-white/10 rounded-2xl overflow-hidden">
    {{-- Header --}}
    <div class="px-6 py-4 border-b border-white/10 bg-gradient-to-r from-slate-800/50 to-slate-900/50">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-white">Chat</h3>
                <p class="text-sm text-gray-400">3 participants</p>
            </div>
            <button class="px-3 py-1.5 text-sm text-gray-400 hover:text-white transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                </svg>
            </button>
        </div>
    </div>

    {{-- Messages Container --}}
    <div 
        class="flex-1 overflow-y-auto p-6 space-y-4" 
        style="max-height: calc(100% - 180px);"
        x-data="{ scrollToBottom() { this.$el.scrollTop = this.$el.scrollHeight; } }"
        x-init="scrollToBottom()"
        @message-received.window="scrollToBottom()"
    >
        @foreach($messages as $message)
            <div class="flex {{ $message['is_mine'] ? 'justify-end' : 'justify-start' }}">
                <div class="flex gap-3 max-w-[70%] {{ $message['is_mine'] ? 'flex-row-reverse' : 'flex-row' }}">
                    {{-- Avatar --}}
                    <div class="flex-shrink-0">
                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-cyan-500 to-purple-500 flex items-center justify-center text-white font-semibold">
                            {{ substr($message['user']['display_name'], 0, 1) }}
                        </div>
                    </div>

                    {{-- Message Bubble --}}
                    <div class="flex flex-col {{ $message['is_mine'] ? 'items-end' : 'items-start' }}">
                        <div class="text-xs text-gray-400 mb-1 px-1">
                            {{ $message['user']['display_name'] }}
                        </div>

                        {{-- Reply Context --}}
                        @if($message['reply_to'])
                            <div class="mb-2 px-3 py-2 rounded-lg bg-slate-800/30 border-l-2 border-cyan-500/50 text-xs text-gray-400 max-w-full">
                                <div class="font-semibold text-cyan-400">{{ $message['reply_to']['user']['display_name'] }}</div>
                                <div class="truncate">{{ $message['reply_to']['body'] }}</div>
                            </div>
                        @endif

                        {{-- Message Body --}}
                        <div class="px-4 py-3 rounded-2xl {{ $message['is_mine'] 
                            ? 'bg-gradient-to-r from-pink-500 to-purple-500 text-white' 
                            : 'bg-slate-800/50 backdrop-blur-lg border border-white/10 text-gray-100' }}">
                            <p class="text-sm">{{ $message['body'] }}</p>
                        </div>

                        {{-- Timestamp & Actions --}}
                        <div class="flex items-center gap-2 mt-1 px-1">
                            <span class="text-xs text-gray-500">
                                {{ $message['created_at']->diffForHumans() }}
                            </span>
                            @if(!$message['is_mine'])
                                <button 
                                    wire:click="replyTo('{{ $message['id'] }}')"
                                    class="text-xs text-gray-500 hover:text-cyan-400 transition">
                                    Reply
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Reply Preview --}}
    @if($replyingTo)
        <div class="px-6 py-3 bg-slate-800/30 border-t border-white/10 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <svg class="w-4 h-4 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                </svg>
                <div>
                    <div class="text-xs text-cyan-400 font-semibold">Replying to {{ $replyingTo['user']['display_name'] }}</div>
                    <div class="text-xs text-gray-400 truncate max-w-md">{{ $replyingTo['body'] }}</div>
                </div>
            </div>
            <button wire:click="cancelReply" class="text-gray-400 hover:text-white transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    @endif

    {{-- Input Area --}}
    <div class="px-6 py-4 border-t border-white/10 bg-slate-900/50">
        <form wire:submit.prevent="sendMessage" class="flex items-end gap-3">
            {{-- Input --}}
            <div class="flex-1">
                <input 
                    type="text"
                    wire:model.live="newMessage"
                    placeholder="Type a message..."
                    class="w-full px-4 py-3 bg-slate-800/50 border border-white/10 rounded-xl text-white placeholder-gray-500 focus:outline-none focus:border-cyan-500/50 focus:ring-2 focus:ring-cyan-500/20 transition"
                >
            </div>

            {{-- Send Button --}}
            <button 
                type="submit"
                wire:loading.attr="disabled"
                class="flex-shrink-0 p-3 bg-gradient-to-r from-pink-500 to-purple-500 rounded-xl text-white hover:scale-105 transition-transform disabled:opacity-50 disabled:hover:scale-100"
            >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                </svg>
            </button>
        </form>
    </div>
</div>
