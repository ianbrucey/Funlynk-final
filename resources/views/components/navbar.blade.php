<nav class="fixed top-0 left-0 right-0 z-50 bg-slate-900/95 border-b border-white/20">
    <div class="container mx-auto px-6">
        <div class="flex items-center justify-between h-20">
            <!-- Logo -->
            <a href="{{ route('feed.nearby') }}" class="flex items-center gap-3 group">
                <div class="w-14 h-14 flex items-center justify-center p-0.5 bg-gradient-to-r from-pink-500 to-purple-500 rounded-xl">
                    <div class="bg-slate-900 rounded-xl w-full h-full flex items-center justify-center p-2">
                        <img src="{{ asset('images/fl-logo-icon-only.png') }}" alt="FunLynk" class="w-full h-full object-contain">
                    </div>
                </div>
                <div class="hidden md:block">
                    <div class="text-xl font-bold">
                        <span class="text-yellow-400">Fun</span><span class="text-cyan-400">Lynk</span>
                    </div>
                    <div class="text-xs text-white font-bold font-mono tracking-wider">SOCIAL ACTIVITY NETWORK</div>
                </div>
            </a>

            <!-- Navigation Icons -->
            <div class="flex items-center gap-2 md:gap-4">
                <!-- Home/Nearby Feed -->
                <a href="{{ route('feed.nearby') }}"
                   class="p-3 hover:bg-white/10 rounded-xl transition-all group relative {{ request()->routeIs('feed.nearby') ? 'bg-white/10' : '' }}"
                   title="Home">
                    <svg class="w-6 h-6 {{ request()->routeIs('feed.nearby') ? 'text-cyan-400' : 'text-gray-300' }} group-hover:text-cyan-400 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    @if(request()->routeIs('feed.nearby'))
                        <div class="absolute -bottom-1 left-0 right-0 h-0.5 bg-gradient-to-r from-pink-500 to-cyan-500"></div>
                    @endif
                </a>

                <!-- For You Feed -->
                <a href="{{ route('feed.for-you') }}"
                   class="p-3 hover:bg-white/10 rounded-xl transition-all group relative {{ request()->routeIs('feed.for-you') ? 'bg-white/10' : '' }}"
                   title="For You">
                    <svg class="w-6 h-6 {{ request()->routeIs('feed.for-you') ? 'text-cyan-400' : 'text-gray-300' }} group-hover:text-cyan-400 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    @if(request()->routeIs('feed.for-you'))
                        <div class="absolute -bottom-1 left-0 right-0 h-0.5 bg-gradient-to-r from-pink-500 to-cyan-500"></div>
                    @endif
                </a>

                <!-- Find People -->
                <a href="{{ route('search.users') }}"
                   class="p-3 hover:bg-white/10 rounded-xl transition-all group relative {{ request()->routeIs('search.users') ? 'bg-white/10' : '' }}"
                   title="Find People">
                    <svg class="w-6 h-6 {{ request()->routeIs('search.users') ? 'text-cyan-400' : 'text-gray-300' }} group-hover:text-cyan-400 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    @if(request()->routeIs('search.users'))
                        <div class="absolute -bottom-1 left-0 right-0 h-0.5 bg-gradient-to-r from-pink-500 to-cyan-500"></div>
                    @endif
                </a>

                <!-- Create Menu -->
                <div class="relative" x-data="{ open: false }">
                    <button
                        @click="open = !open"
                        class="p-3 bg-gradient-to-r from-pink-500 to-purple-500 rounded-xl hover:scale-110 transition-all shadow-lg group"
                        title="Create">
                        <svg class="w-6 h-6 text-white group-hover:rotate-90 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                    </button>

                    <!-- Dropdown Menu -->
                    <div x-show="open"
                         @click.away="open = false"
                         x-transition
                         class="absolute right-0 mt-2 w-56 glass-card border border-white/10 rounded-xl overflow-hidden">
                        <a href="{{ route('posts.create') }}" class="block px-4 py-3 hover:bg-white/10 transition">
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
                        <a href="{{ route('activities.create') }}" class="block px-4 py-3 hover:bg-white/10 transition">
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
                    </div>
                </div>

                <!-- Notifications -->
                <livewire:notifications.notification-bell />

                <!-- Profile -->
                <a href="{{ route('profile.show') }}"
                   class="p-3 hover:bg-white/10 rounded-xl transition-all group relative {{ request()->routeIs('profile.*') ? 'bg-white/10' : '' }}"
                   title="Profile">
                    @if(Auth::user()->profile_image_url)
                        <img src="{{ Storage::url(Auth::user()->profile_image_url) }}" alt="Profile" class="w-8 h-8 rounded-full object-cover border-2 border-purple-500">
                    @else
                        <svg class="w-6 h-6 {{ request()->routeIs('profile.*') ? 'text-purple-400' : 'text-gray-300' }} group-hover:text-purple-400 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    @endif
                    @if(request()->routeIs('profile.*'))
                        <div class="absolute -bottom-1 left-0 right-0 h-0.5 bg-gradient-to-r from-pink-500 to-cyan-500"></div>
                    @endif
                </a>

                <!-- Logout (Mobile Menu Toggle) -->
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open"
                            class="p-3 hover:bg-white/10 rounded-xl transition-all"
                            title="Menu">
                        <svg class="w-6 h-6 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>

                    <!-- Dropdown Menu -->
                    <div x-show="open"
                         @click.away="open = false"
                         x-transition
                         class="absolute right-0 mt-2 w-48 glass-card border border-white/10 rounded-xl overflow-hidden">
                        <a href="{{ route('profile.edit') }}" class="block px-4 py-3 hover:bg-white/10 transition text-gray-300 hover:text-white">
                            <div class="flex items-center gap-3">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <span>Settings</span>
                            </div>
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
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
        </div>
    </div>
</nav>

<!-- Spacer to prevent content from going under fixed navbar -->
<div class="h-20"></div>

