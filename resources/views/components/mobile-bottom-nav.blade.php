{{-- Mobile Bottom Navigation - Only visible on mobile --}}
<nav class="fixed bottom-0 inset-x-0 z-[100] bg-slate-900/95 backdrop-blur-lg border-t border-white/10 md:hidden safe-area-bottom">
    <div class="flex items-center justify-between h-16 px-4 max-w-screen-sm mx-auto">
        {{-- Home --}}
        <a href="{{ route('feed.nearby') }}"
           class="relative flex flex-col items-center justify-center py-2 px-2 rounded-xl transition-all {{ request()->routeIs('feed.nearby') ? 'text-cyan-400' : 'text-gray-400 hover:text-white' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            <span class="text-[10px] mt-0.5">Home</span>
            @if(request()->routeIs('feed.nearby'))
                <div class="absolute bottom-0 w-6 h-0.5 bg-gradient-to-r from-pink-500 to-cyan-500 rounded-full"></div>
            @endif
        </a>

        {{-- Search/People --}}
        <a href="{{ route('search.users') }}"
           class="relative flex flex-col items-center justify-center py-2 px-2 rounded-xl transition-all {{ request()->routeIs('search.users') ? 'text-cyan-400' : 'text-gray-400 hover:text-white' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <span class="text-[10px] mt-0.5">Search</span>
            @if(request()->routeIs('search.users'))
                <div class="absolute bottom-0 w-6 h-0.5 bg-gradient-to-r from-pink-500 to-cyan-500 rounded-full"></div>
            @endif
        </a>

        {{-- Create (Center, Prominent) --}}
        <div class="relative" x-data="{ open: false }">
            <button
                @click="open = !open"
                class="flex items-center justify-center w-12 h-12 -mt-4 bg-gradient-to-r from-pink-500 to-purple-500 rounded-full shadow-lg shadow-pink-500/30 hover:scale-110 transition-all">
                <svg class="w-6 h-6 text-white transition-transform duration-300" :class="{ 'rotate-45': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                </svg>
            </button>

            {{-- Create Menu (Slides Up) --}}
            <div x-show="open"
                 @click.away="open = false"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-4"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 translate-y-0"
                 x-transition:leave-end="opacity-0 translate-y-4"
                 class="absolute bottom-20 left-1/2 -translate-x-1/2 w-56 bg-slate-900/95 backdrop-blur-lg border border-white/10 rounded-2xl overflow-hidden shadow-2xl">
                <a href="{{ route('posts.create') }}" class="block px-4 py-4 hover:bg-white/10 transition">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-gradient-to-r from-pink-500 to-purple-500 flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                        </div>
                        <div>
                            <div class="text-white font-semibold">Quick Post</div>
                            <div class="text-xs text-gray-400">Spontaneous, 24-72h</div>
                        </div>
                    </div>
                </a>
                <a href="{{ route('events.create') }}" class="block px-4 py-4 hover:bg-white/10 transition border-t border-white/5">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-gradient-to-r from-cyan-500 to-blue-500 flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div>
                            <div class="text-white font-semibold">Plan Event</div>
                            <div class="text-xs text-gray-400">Structured, persistent</div>
                        </div>
                    </div>
                </a>
                <a href="{{ route('groups.create') }}" class="block px-4 py-4 hover:bg-white/10 transition border-t border-white/5">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-gradient-to-r from-green-500 to-teal-500 flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                        </div>
                        <div>
                            <div class="text-white font-semibold">Create Group</div>
                            <div class="text-xs text-gray-400">Build your community</div>
                        </div>
                    </div>
                </a>
            </div>
        </div>

        {{-- Notifications --}}
        <a href="{{ route('notifications.index') }}"
           class="relative flex flex-col items-center justify-center py-2 px-2 rounded-xl transition-all {{ request()->routeIs('notifications.*') ? 'text-cyan-400' : 'text-gray-400 hover:text-white' }}">
            @php
                $unreadCount = auth()->user()->notifications()->where('is_read', false)->count();
            @endphp
            <div class="relative">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
                @if($unreadCount > 0)
                    <span class="absolute -top-1 -right-1 w-4 h-4 bg-red-500 rounded-full text-[10px] font-bold flex items-center justify-center text-white">
                        {{ min($unreadCount, 9) }}{{ $unreadCount > 9 ? '+' : '' }}
                    </span>
                @endif
            </div>
            <span class="text-[10px] mt-0.5">Alerts</span>
            @if(request()->routeIs('notifications.*'))
                <div class="absolute bottom-0 w-6 h-0.5 bg-gradient-to-r from-pink-500 to-cyan-500 rounded-full"></div>
            @endif
        </a>

        {{-- Profile/Menu --}}
        <div class="relative" x-data="{ open: false }">
            <button
                @click="open = !open"
                class="flex flex-col items-center justify-center py-2 px-2 rounded-xl transition-all {{ request()->routeIs('profile.*') ? 'text-cyan-400' : 'text-gray-400 hover:text-white' }}">
                @if(Auth::user()->profile_image_url)
                    <img src="{{ Storage::url(Auth::user()->profile_image_url) }}" alt="Profile" class="w-5 h-5 rounded-full object-cover border border-purple-500">
                @else
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                @endif
                <span class="text-[10px] mt-0.5">Menu</span>
            </button>

            {{-- Profile Menu (Slides Up) --}}
            <div x-show="open"
                 @click.away="open = false"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-4"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 translate-y-0"
                 x-transition:leave-end="opacity-0 translate-y-4"
                 class="absolute bottom-16 right-0 w-56 bg-slate-900/95 backdrop-blur-lg border border-white/10 rounded-2xl overflow-hidden shadow-2xl">
                
                {{-- Profile Link --}}
                <a href="{{ route('profile.show') }}" class="block px-4 py-3 hover:bg-white/10 transition {{ request()->routeIs('profile.show') ? 'bg-white/10 text-cyan-400' : 'text-gray-300' }}">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        <span>My Profile</span>
                    </div>
                </a>

                {{-- My Events --}}
                <a href="{{ route('events.dashboard') }}" class="block px-4 py-3 hover:bg-white/10 transition {{ request()->routeIs('events.*') ? 'bg-white/10 text-cyan-400' : 'text-gray-300' }}">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <span>My Events</span>
                    </div>
                </a>

                {{-- My Tickets --}}
                <a href="{{ route('tickets.index') }}" class="block px-4 py-3 hover:bg-white/10 transition {{ request()->routeIs('tickets.*') ? 'bg-white/10 text-cyan-400' : 'text-gray-300' }}">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
                        </svg>
                        <span>My Tickets</span>
                    </div>
                </a>

                {{-- Groups --}}
                <a href="{{ route('groups.index') }}" class="block px-4 py-3 hover:bg-white/10 transition {{ request()->routeIs('groups.*') ? 'bg-white/10 text-cyan-400' : 'text-gray-300' }}">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        <span>Groups</span>
                    </div>
                </a>

                {{-- Messages --}}
                @php
                    $unreadMessageCount = auth()->user()->conversations()
                        ->where('type', 'private')
                        ->get()
                        ->sum(function ($conv) {
                            $lastRead = $conv->pivot->last_read_at;
                            return $conv->messages()
                                ->where('user_id', '!=', auth()->id())
                                ->when($lastRead, fn($q) => $q->where('created_at', '>', $lastRead))
                                ->count();
                        });
                @endphp
                <a href="{{ route('messages.index') }}" class="block px-4 py-3 hover:bg-white/10 transition text-gray-300 border-t border-white/5 {{ request()->routeIs('messages.*') ? 'bg-white/10 text-cyan-400' : '' }}">
                    <div class="flex items-center gap-3">
                        <div class="relative">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                            </svg>
                            @if($unreadMessageCount > 0)
                                <span class="absolute -top-1.5 -right-1.5 w-4 h-4 bg-gradient-to-r from-pink-500 to-purple-500 rounded-full text-[10px] font-bold flex items-center justify-center text-white">
                                    {{ min($unreadMessageCount, 9) }}{{ $unreadMessageCount > 9 ? '+' : '' }}
                                </span>
                            @endif
                        </div>
                        <span>Messages</span>
                    </div>
                </a>

                {{-- Settings --}}
                <a href="{{ route('profile.edit') }}" class="block px-4 py-3 hover:bg-white/10 transition text-gray-300">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span>Settings</span>
                    </div>
                </a>

                {{-- Logout --}}
                <form method="POST" action="{{ route('logout') }}" class="border-t border-white/5">
                    @csrf
                    <button type="submit" class="w-full text-left px-4 py-3 hover:bg-white/10 transition text-gray-300 hover:text-red-400">
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                            <span>Logout</span>
                        </div>
                    </button>
                </form>
            </div>
        </div>
    </div>
</nav>

<style>
    /* Safe area for devices with home indicators (iPhone X+) */
    .safe-area-bottom {
        padding-bottom: env(safe-area-inset-bottom, 0);
    }
</style>
