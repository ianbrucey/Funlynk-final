<a href="{{ route('messages.index') }}"
   class="relative p-3 hover:bg-white/10 rounded-xl transition-all group {{ request()->routeIs('messages.*') ? 'bg-white/10' : '' }}"
   title="Messages">
    {{-- Inbox Icon --}}
    <svg class="w-6 h-6 {{ request()->routeIs('messages.*') ? 'text-cyan-400' : 'text-gray-300' }} group-hover:text-cyan-400 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
    </svg>

    {{-- Unread Badge --}}
    @if($this->getTotalUnreadCount() > 0)
        <span class="absolute top-1 right-1 w-5 h-5 bg-gradient-to-r from-pink-500 to-purple-500 rounded-full text-xs text-white flex items-center justify-center font-semibold shadow-lg">
            {{ $this->getTotalUnreadCount() > 9 ? '9+' : $this->getTotalUnreadCount() }}
        </span>
    @endif

    {{-- Active Indicator --}}
    @if(request()->routeIs('messages.*'))
        <div class="absolute -bottom-1 left-0 right-0 h-0.5 bg-gradient-to-r from-pink-500 to-cyan-500"></div>
    @endif
</a>
