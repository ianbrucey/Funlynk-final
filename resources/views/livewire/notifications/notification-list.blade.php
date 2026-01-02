<div class="min-h-screen py-4 md:py-8 pb-24 md:pb-8">
    <div class="container mx-auto px-4 md:px-6">
        {{-- Header --}}
        <div class="mb-6 md:mb-8">
            <div class="flex items-center justify-between gap-3 mb-3 md:mb-4">
                <h1 class="text-3xl md:text-4xl font-bold">
                    <span class="gradient-text">Notifications</span>
                </h1>
                @if($unreadCount > 0)
                    <button
                        wire:click="markAllAsRead"
                        class="px-3 py-2 md:px-4 md:py-2 bg-gradient-to-r from-pink-500 to-purple-500 rounded-xl font-semibold hover:scale-105 transition-all text-xs md:text-sm whitespace-nowrap flex-shrink-0">
                        <span class="hidden sm:inline">Mark all as read</span>
                        <span class="sm:hidden">Mark all</span>
                        <span class="ml-1">({{ $unreadCount }})</span>
                    </button>
                @endif
            </div>
            <p class="text-gray-400 text-sm md:text-base">Stay updated with your activity</p>
        </div>

        {{-- Search Bar --}}
        <div class="mb-4 md:mb-6">
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-4 w-4 md:h-5 md:w-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search notifications..."
                    class="block w-full pl-9 md:pl-10 pr-3 py-2.5 md:py-2 border border-gray-700 rounded-xl leading-5 bg-slate-800/50 text-gray-300 placeholder-gray-500 focus:outline-none focus:bg-slate-800 focus:border-pink-500 focus:ring-1 focus:ring-pink-500 text-sm transition-colors"
                >
            </div>
        </div>

        {{-- Notifications List --}}
        <div class="relative glass-card rounded-xl overflow-hidden">
            <div class="top-accent-center"></div>

            @forelse($notifications as $notification)
                @if($notification->type === 'post_conversion_prompt')
                    {{-- Conversion Prompt Card --}}
                    <div class="p-3 md:p-4 border-b border-white/5 {{ $notification->read_at ? 'bg-slate-900/20' : 'bg-slate-900/50' }}">
                        <x-notifications.conversion-prompt-card :notification="$notification" />
                    </div>
                @else
                    {{-- Standard Notification --}}
                    <a href="{{ $notification->data['url'] ?? '#' }}"
                       wire:click.prevent="handleNotificationClick('{{ $notification->id }}', '{{ $notification->data['url'] ?? '' }}')"
                       class="block p-4 md:p-6 hover:bg-white/5 cursor-pointer border-b border-white/5 transition group {{ $notification->read_at ? 'bg-slate-900/20' : 'bg-slate-900/50' }}">
                        <div class="flex items-start gap-3 md:gap-4">
                            {{-- Actor Avatar or Unread Indicator --}}
                            @if(isset($notification->data['actor_avatar']) && $notification->data['actor_avatar'])
                                <img src="{{ Storage::url($notification->data['actor_avatar']) }}"
                                     alt="{{ $notification->data['actor_name'] ?? 'User' }}"
                                     class="w-8 h-8 md:w-10 md:h-10 rounded-full object-cover flex-shrink-0 border-2 {{ $notification->read_at ? 'border-gray-700' : 'border-pink-500' }}">
                            @else
                                {{-- Unread Indicator --}}
                                @if(!$notification->read_at)
                                    <div class="w-2.5 h-2.5 md:w-3 md:h-3 bg-pink-500 rounded-full mt-1.5 md:mt-2 group-hover:scale-125 transition flex-shrink-0"></div>
                                @else
                                    <div class="w-2.5 h-2.5 md:w-3 md:h-3 bg-gray-700 rounded-full mt-1.5 md:mt-2 flex-shrink-0"></div>
                                @endif
                            @endif

                            {{-- Content --}}
                            <div class="flex-1 min-w-0">
                                <p class="text-white font-semibold text-sm md:text-base mb-1.5 md:mb-2 group-hover:text-pink-400 transition">
                                    {{ $notification->title ?? Str::headline($notification->type) }}
                                </p>
                                <p class="text-gray-300 text-xs md:text-sm mb-1.5 md:mb-2 leading-relaxed">
                                    @if($notification->type === 'post_reaction')
                                        <strong class="text-pink-400">{{ $notification->data['reactor_name'] ?? 'Someone' }}</strong> is down for
                                        <strong>"{{ $notification->data['post_title'] ?? 'a post' }}"</strong>
                                        @if($notification->data['post_location'] ?? false)
                                            <span class="hidden sm:inline">· 📍 {{ $notification->data['post_location'] }}</span>
                                        @endif
                                        @if(($notification->data['reaction_count'] ?? 0) > 1)
                                            <br>
                                            <span class="text-cyan-400">{{ $notification->data['reaction_count'] }} total reactions</span>
                                        @endif
                                    @elseif($notification->type === 'post_invitation')
                                        <strong class="text-pink-400">{{ $notification->data['inviter_name'] ?? 'Someone' }}</strong> invited you to
                                        <strong>"{{ $notification->data['post_title'] ?? 'a post' }}"</strong>
                                    @elseif($notification->type === 'post_conversion')
                                        Your post <strong>"{{ $notification->data['post_title'] ?? 'a post' }}"</strong> can be converted to an event!
                                    @elseif(isset($notification->data['actor_name']))
                                        {{-- Group notifications with actor --}}
                                        <strong class="text-pink-400">{{ $notification->data['actor_name'] }}</strong> {{ $notification->message }}
                                    @else
                                        {{ $notification->message ?? 'New notification' }}
                                    @endif
                                </p>
                                <div class="flex items-center gap-1.5 md:gap-2 text-[10px] md:text-xs text-gray-500">
                                    <svg class="w-3 h-3 md:w-4 md:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    {{ $notification->created_at->diffForHumans() }}
                                    @if($notification->read_at)
                                        <span class="hidden md:inline">· <span class="text-gray-600">Read {{ $notification->read_at->diffForHumans() }}</span></span>
                                    @endif
                                </div>
                            </div>

                            {{-- Arrow Icon --}}
                            <div class="flex-shrink-0">
                                <svg class="w-4 h-4 md:w-5 md:h-5 text-gray-500 group-hover:text-pink-400 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </div>
                        </div>
                    </a>
                @endif
            @empty
                {{-- Empty State --}}
                <div class="p-8 md:p-16 text-center">
                    <div class="w-16 h-16 md:w-24 md:h-24 mx-auto mb-4 md:mb-6 rounded-full bg-gradient-to-br from-pink-500 via-purple-500 to-cyan-500 flex items-center justify-center opacity-50">
                        <svg class="w-8 h-8 md:w-12 md:h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                    </div>
                    <h3 class="text-lg md:text-xl font-bold mb-2 text-white">All caught up!</h3>
                    <p class="text-sm md:text-base text-gray-400">You don't have any notifications yet</p>
                </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        @if($notifications->hasPages())
            <div class="mt-8">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>

    <style>
        .gradient-text {
            background: linear-gradient(to right, #ec4899, #a855f7);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .top-accent-center {
            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 8rem;
            height: 0.25rem;
            background: linear-gradient(to right, transparent, #ec4899, transparent);
        }
    </style>
</div>
