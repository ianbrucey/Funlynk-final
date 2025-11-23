<nav class="fixed top-0 left-0 right-0 z-50 bg-slate-900/95 border-b border-white/20">
    <div class="container mx-auto px-6">
        <div class="flex items-center justify-between h-20">
            <!-- Logo -->
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3 group">
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
                <!-- Timeline/Feed -->
                <a href="{{ route('dashboard') }}"
                   class="p-3 hover:bg-white/10 rounded-xl transition-all group relative {{ request()->routeIs('dashboard') ? 'bg-white/10' : '' }}"
                   title="Timeline">
                    <svg class="w-6 h-6 {{ request()->routeIs('dashboard') ? 'text-cyan-400' : 'text-gray-300' }} group-hover:text-cyan-400 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    @if(request()->routeIs('dashboard'))
                        <div class="absolute -bottom-1 left-0 right-0 h-0.5 bg-gradient-to-r from-pink-500 to-cyan-500"></div>
                    @endif
                </a>

                <!-- Create Post -->
                <a
                    href="{{ route('activities.create') }}"
                    class="p-3 bg-gradient-to-r from-pink-500 to-purple-500 rounded-xl hover:scale-110 transition-all shadow-lg group"
                    title="Create Post">
                    <svg class="w-6 h-6 text-white group-hover:rotate-90 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                </a>

                <!-- Notifications -->
                <button
                    onclick="alert('Notifications feature coming soon!')"
                    class="p-3 hover:bg-white/10 rounded-xl transition-all group relative"
                    title="Notifications">
                    <svg class="w-6 h-6 text-gray-300 group-hover:text-yellow-400 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                    <!-- Notification badge (example) -->
                    <span class="absolute top-2 right-2 w-2 h-2 bg-pink-500 rounded-full animate-pulse"></span>
                </button>

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

