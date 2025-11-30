<div class="min-h-screen">
    <div class="container mx-auto px-6 py-8 max-w-7xl">
        <div class="relative glass-card rounded-2xl overflow-hidden" style="height: calc(100vh - 12rem);">
            <div class="top-accent-center"></div>

            {{-- Header with Tabs --}}
            <div class="border-b border-white/10 px-6 py-4">
                <div class="flex items-center justify-between mb-4">
                    <h1 class="text-2xl font-bold text-white">Messages</h1>
                </div>

                {{-- Tabs --}}
                <div class="flex gap-4">
                    <button wire:click="switchTab('inbox')"
                            class="px-4 py-2 rounded-lg font-semibold transition-all {{ $activeTab === 'inbox' ? 'bg-gradient-to-r from-cyan-500 to-blue-500 text-white shadow-lg shadow-cyan-500/30' : 'text-gray-400 hover:text-white hover:bg-white/5' }}">
                        Inbox
                    </button>
                    <button wire:click="switchTab('requests')"
                            class="relative px-4 py-2 rounded-lg font-semibold transition-all {{ $activeTab === 'requests' ? 'bg-gradient-to-r from-purple-500 to-pink-500 text-white shadow-lg shadow-purple-500/30' : 'text-gray-400 hover:text-white hover:bg-white/5' }}">
                        Requests
                        @if($unreadRequestCount > 0)
                            <span class="absolute -top-1 -right-1 flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-gradient-to-r from-pink-500 to-purple-500 rounded-full border-2 border-slate-900">
                                {{ $unreadRequestCount > 9 ? '9+' : $unreadRequestCount }}
                            </span>
                        @endif
                    </button>
                </div>
            </div>

            {{-- Main Content Area --}}
            <div class="flex" style="height: calc(100% - 120px);">
            {{-- Sidebar: Conversation/Request List --}}
            <div class="w-full lg:w-96 border-r border-white/10 overflow-y-auto p-4">
                @if($activeTab === 'inbox')
                    <livewire:direct-messages.inbox-list />
                @else
                    <livewire:direct-messages.requests-list />
                @endif
            </div>

            {{-- Main Panel: Chat or Empty State --}}
            <div class="hidden lg:flex flex-1 p-4 items-center justify-center">
                @if($conversation)
                    {{-- Integrated ChatComponent --}}
                    <div class="w-full h-full">
                        <livewire:chat.chat-component :conversationId="$conversation->id" :key="'chat-'.$conversation->id" />
                    </div>
                @else
                    {{-- Empty State --}}
                    <div class="text-center">
                        <div class="relative inline-block mb-6">
                            <div class="absolute inset-0 bg-gradient-to-r from-cyan-500/20 to-purple-500/20 blur-2xl rounded-full"></div>
                            <svg class="relative w-24 h-24 mx-auto text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                      d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                            </svg>
                        </div>

                        <h3 class="text-2xl font-semibold text-white mb-2">
                            @if($activeTab === 'inbox')
                                Select a conversation
                            @else
                                Select a message request
                            @endif
                        </h3>
                        <p class="text-gray-400">
                            @if($activeTab === 'inbox')
                                Choose a conversation from the sidebar to start chatting
                            @else
                                Review and respond to message requests
                            @endif
                        </p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Mobile: Show chat when conversation is selected --}}
        <div class="lg:hidden mt-4">
            @if($conversation)
                <div class="glass-card rounded-xl overflow-hidden" style="height: 600px;">
                    <livewire:chat.chat-component :conversationId="$conversation->id" :key="'chat-mobile-'.$conversation->id" />
                </div>
            @endif
        </div>
    </div>
</div>
