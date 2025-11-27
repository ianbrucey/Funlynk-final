<div class="relative" x-data="{ open: false }">
    {{-- Bell Icon --}}
    <button @click="open = !open" class="relative p-2 text-gray-400 hover:text-white transition">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
        </svg>

        @if($unreadCount > 0)
            <span class="absolute top-0 right-0 w-5 h-5 bg-pink-500 rounded-full text-xs text-white flex items-center justify-center font-semibold">
                {{ $unreadCount > 9 ? '9+' : $unreadCount }}
            </span>
        @endif
    </button>

    {{-- Dropdown --}}
    <div x-show="open"
         @click.away="open = false"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="absolute right-0 mt-2 w-80 glass-card border border-white/10 rounded-xl overflow-hidden z-50 shadow-2xl"
         style="display: none;">

        {{-- Header --}}
        <div class="p-4 border-b border-white/10 flex items-center justify-between bg-slate-900/50">
            <h3 class="text-white font-semibold flex items-center gap-2">
                <svg class="w-5 h-5 text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
                Notifications
            </h3>
            @if($unreadCount > 0)
                <button wire:click="markAllAsRead" class="text-xs text-pink-400 hover:text-pink-300 transition font-medium">
                    Mark all read
                </button>
            @endif
        </div>

        {{-- Notifications List --}}
        <div class="max-h-96 overflow-y-auto">
            @forelse($recentNotifications as $notification)
                <div wire:click="markAsRead('{{ $notification->id }}')"
                     class="p-4 hover:bg-white/5 cursor-pointer border-b border-white/5 transition group">
                    <div class="flex items-start gap-3">
                        <div class="w-2 h-2 bg-pink-500 rounded-full mt-2 group-hover:scale-125 transition"></div>
                        <div class="flex-1">
                            <p class="text-white text-sm font-medium group-hover:text-pink-400 transition">
                                {{ $notification->type }}
                            </p>
                            <p class="text-gray-400 text-xs mt-1">
                                @if($notification->type === 'post_reaction')
                                    {{ $notification->data['reactor_name'] ?? 'Someone' }} is down for "{{ $notification->data['post_title'] ?? 'a post' }}"
                                @elseif($notification->type === 'post_invitation')
                                    {{ $notification->data['inviter_name'] ?? 'Someone' }} invited you to "{{ $notification->data['post_title'] ?? 'a post' }}"
                                @elseif($notification->type === 'post_conversion')
                                    Your post "{{ $notification->data['post_title'] ?? 'a post' }}" can be converted to an event!
                                @else
                                    {{ $notification->message ?? 'New notification' }}
                                @endif
                            </p>
                            <p class="text-gray-500 text-xs mt-1 flex items-center gap-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                {{ $notification->created_at->diffForHumans() }}
                            </p>
                        </div>
                    </div>
                </div>
            @empty
                <div class="p-8 text-center text-gray-400">
                    <svg class="w-16 h-16 mx-auto mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                    <p class="text-sm font-medium mb-1">All caught up!</p>
                    <p class="text-xs">No new notifications</p>
                </div>
            @endforelse
        </div>

        {{-- Footer --}}
        <div class="p-3 border-t border-white/10 text-center bg-slate-900/50">
            <a href="{{ route('notifications.index') }}" class="text-sm text-pink-400 hover:text-pink-300 transition font-medium inline-flex items-center gap-1">
                View all notifications
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>
    </div>
</div>
