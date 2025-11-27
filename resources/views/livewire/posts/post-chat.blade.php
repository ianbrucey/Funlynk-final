<div class="min-h-screen flex flex-col">
    {{-- Compact Post Header --}}
    <div class="bg-slate-900/50 border-b border-white/10">
        <div class="px-4 py-4">
            {{-- Back Button --}}
            <a href="{{ route('feed.nearby') }}" class="inline-flex items-center gap-2 text-gray-400 hover:text-cyan-400 transition mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Feed
            </a>

            {{-- Post Context --}}
            <div class="flex items-start gap-3">
                {{-- Avatar --}}
                @if($post->user?->profile_image_url)
                    <img
                        src="{{ Storage::url($post->user->profile_image_url) }}"
                        alt="{{ $post->user->display_name ?? $post->user->username }}"
                        class="w-10 h-10 rounded-full object-cover ring-2 ring-pink-500/50 bg-slate-800 flex-shrink-0"
                    >
                @else
                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-pink-500 to-purple-500 flex items-center justify-center ring-2 ring-pink-500/50 flex-shrink-0">
                        <span class="text-white font-bold text-sm">
                            {{ strtoupper(substr($post->user?->display_name ?? $post->user?->username ?? '?', 0, 1)) }}
                        </span>
                    </div>
                @endif

                <div class="flex-1 min-w-0">
                    {{-- Title --}}
                    <h1 class="text-xl font-bold text-white mb-1">{{ $post->title }}</h1>
                    
                    {{-- Meta Info --}}
                    <div class="flex flex-wrap items-center gap-3 text-sm text-gray-400">
                        <span>{{ "@".$post->user?->username }}</span>
                        <span>·</span>
                        <span class="flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            {{ $post->location_name }}
                        </span>
                        <span>·</span>
                        <span class="text-pink-400">⏱️ Expires {{ $post->expires_at->diffForHumans() }}</span>
                    </div>

                    {{-- Description (if exists) --}}
                    @if($post->description)
                        <p class="text-gray-300 text-sm mt-2 line-clamp-2">{{ $post->description }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Full-Height Chat --}}
    <div class="flex-1 px-4 py-6">
        <div class="h-full">
            <livewire:chat.chat-component :conversationable="$post" />
        </div>
    </div>
</div>
