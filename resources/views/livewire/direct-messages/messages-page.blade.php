<div class="min-h-screen">
    <div class="container mx-auto sm:px-6 py-4 sm:py-8 max-w-7xl">
        {{-- MOBILE VIEW: Full-screen chat when conversation selected --}}
        <div class="lg:hidden">
            @if($conversation)
                {{-- Mobile Chat View --}}
                <div class="relative glass-card rounded-2xl overflow-hidden" style="height: calc(100vh - 8rem); height: calc(100dvh - 8rem);">
                    <div class="top-accent-center"></div>

                    {{-- Mobile Chat Header with Back Button --}}
                    <div class="flex items-center gap-3 py-3 border-b border-white/10 bg-slate-900/50">
                        <button wire:click="clearConversation"
                                class="p-2 -ml-2 hover:bg-white/10 rounded-lg transition">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                        </button>
                        @php
                            $otherUser = $conversation->participants->where('id', '!=', auth()->id())->first();
                        @endphp
                        <a href="{{ route('profile.view', $otherUser->username) }}" class="flex items-center gap-3 flex-1 min-w-0">
                            <img src="{{ $otherUser->profile_image_url ? Storage::url($otherUser->profile_image_url) : 'https://ui-avatars.com/api/?name='.urlencode($otherUser->name).'&background=ec4899&color=fff' }}"
                                 alt="{{ $otherUser->name }}"
                                 class="w-10 h-10 rounded-full border-2 border-white/20 object-cover flex-shrink-0">
                            <div class="min-w-0">
                                <h2 class="text-white font-semibold truncate">{{ $otherUser->name }}</h2>
                                <p class="text-xs text-gray-400 truncate">{{ "@".$otherUser->username }}</p>
                            </div>
                        </a>
                    </div>

                    {{-- Mobile Chat Component --}}
                    <div style="height: calc(100% - 64px);">
                        <livewire:chat.chat-component :conversationId="$conversation->id" :key="'chat-mobile-'.$conversation->id" :showHeader="false" />
                    </div>
                </div>
            @else
                {{-- Mobile List View --}}
                <div class="relative glass-card rounded-2xl overflow-hidden" style="height: calc(100vh - 8rem); height: calc(100dvh - 8rem);">
                    <div class="top-accent-center"></div>

                    {{-- Header with Tabs --}}
                    <div class="border-b border-white/10 px-4 py-4">
                        <h1 class="text-xl font-bold text-white mb-4">Messages</h1>

                        {{-- Tabs --}}
                        <div class="flex gap-2">
                            <button wire:click="switchTab('inbox')"
                                    class="flex-1 px-4 py-2.5 rounded-lg font-semibold transition-all text-sm {{ $activeTab === 'inbox' ? 'bg-gradient-to-r from-cyan-500 to-blue-500 text-white shadow-lg shadow-cyan-500/30' : 'text-gray-400 hover:text-white hover:bg-white/5' }}">
                                Inbox
                            </button>
                            <button wire:click="switchTab('requests')"
                                    class="relative flex-1 px-4 py-2.5 rounded-lg font-semibold transition-all text-sm {{ $activeTab === 'requests' ? 'bg-gradient-to-r from-purple-500 to-pink-500 text-white shadow-lg shadow-purple-500/30' : 'text-gray-400 hover:text-white hover:bg-white/5' }}">
                                Requests
                                @if($unreadRequestCount > 0)
                                    <span class="absolute -top-1 -right-1 flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-gradient-to-r from-pink-500 to-purple-500 rounded-full border-2 border-slate-900">
                                        {{ $unreadRequestCount > 9 ? '9+' : $unreadRequestCount }}
                                    </span>
                                @endif
                            </button>
                        </div>
                    </div>

                    {{-- Mobile Conversation List --}}
                    <div class="overflow-y-auto p-4" style="height: calc(100% - 120px);">
                        @if($activeTab === 'inbox')
                            <livewire:direct-messages.inbox-list />
                        @else
                            <livewire:direct-messages.requests-list />
                        @endif
                    </div>
                </div>
            @endif
        </div>

        {{-- DESKTOP VIEW: Side-by-side layout --}}
        <div class="hidden lg:block">
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
                    <div class="w-96 border-r border-white/10 overflow-y-auto p-4">
                        @if($activeTab === 'inbox')
                            <livewire:direct-messages.inbox-list />
                        @else
                            <livewire:direct-messages.requests-list />
                        @endif
                    </div>

                    {{-- Main Panel: Chat or Empty State --}}
                    <div class="flex-1 p-4 flex items-center justify-center">
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
            </div>
        </div>
    </div>
</div>
