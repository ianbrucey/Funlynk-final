@props(['post'])

<div class="relative p-4 glass-card rounded-xl border-l-4 border-pink-500 hover:border-purple-500 transition-all h-full flex flex-col group cursor-pointer"
     onclick="window.location.href='{{ route('posts.chat', $post->id) }}'">
    
    {{-- Expiration Timer --}}
    <div class="absolute top-3 right-3 z-10">
        <span class="text-xs text-gray-400 bg-slate-800/80 px-2 py-1 rounded-full backdrop-blur-sm">
            ⏱️ {{ $post->expires_at->diffForHumans(null, true) }}
        </span>
    </div>

    {{-- User Info --}}
    <div class="flex items-center gap-2 mb-3">
        {{-- Avatar --}}
        @if($post->user?->profile_image_url)
            <img
                src="{{ Storage::url($post->user->profile_image_url) }}"
                alt="{{ $post->user->display_name ?? $post->user->username }}"
                class="w-8 h-8 rounded-full object-cover ring-2 ring-pink-500/50 bg-slate-800"
            >
        @else
            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-pink-500 to-purple-500 flex items-center justify-center ring-2 ring-pink-500/50">
                <span class="text-white font-bold text-xs">
                    {{ strtoupper(substr($post->user?->display_name ?? $post->user?->username ?? '?', 0, 1)) }}
                </span>
            </div>
        @endif

        {{-- Username --}}
        <div class="flex-1 min-w-0">
            <p class="font-semibold text-white text-sm truncate group-hover:text-cyan-400 transition">
                {{ $post->user?->display_name ?? $post->user?->username ?? 'Unknown User' }}
            </p>
            <p class="text-xs text-gray-500">
                {{ $post->created_at->diffForHumans(null, true) }} ago
            </p>
        </div>
    </div>

    {{-- Content --}}
    <div class="flex-1 mb-3">
        <h3 class="text-base font-bold mb-1.5 text-white line-clamp-2 pr-16">{{ $post->title }}</h3>
        @if($post->description)
            <p class="text-gray-400 text-xs line-clamp-2">{{ $post->description }}</p>
        @endif
    </div>
    
    {{-- Location & Time --}}
    <div class="space-y-1.5 text-xs text-gray-400 mb-3">
        <div class="flex items-center gap-1.5">
            <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
            </svg>
            <span class="truncate">{{ $post->location_name }}</span>
        </div>
        @if($post->approximate_time)
            <div class="flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>{{ $post->approximate_time->format('M j, g:i A') }}</span>
            </div>
        @endif
    </div>
    
    {{-- Tags --}}
    @if($post->tags && count($post->tags) > 0)
        <div class="flex flex-wrap gap-1.5 mb-3">
            @foreach(array_slice($post->tags, 0, 3) as $tag)
                <span class="px-2 py-0.5 bg-slate-800/50 border border-white/10 rounded text-xs text-gray-300">
                    {{ $tag }}
                </span>
            @endforeach
            @if(count($post->tags) > 3)
                <span class="px-2 py-0.5 text-xs text-gray-500">
                    +{{ count($post->tags) - 3 }}
                </span>
            @endif
        </div>
    @endif
    
    {{-- Reactions & Actions --}}
    <div class="space-y-2 mt-auto">
        @php
            $userHasReacted = $post->reactions->where('user_id', auth()->id())->where('reaction_type', 'im_down')->isNotEmpty();
            $reactionCount = $post->reactions->where('reaction_type', 'im_down')->count();
        @endphp
        
        <button
            wire:click.stop="reactToPost('{{ $post->id }}', 'im_down')"
            class="w-full px-3 py-2 rounded-lg text-xs font-semibold hover:scale-105 transition-all
                {{ $userHasReacted
                    ? 'bg-gradient-to-r from-pink-600 to-purple-600 ring-2 ring-pink-400'
                    : 'bg-gradient-to-r from-pink-500 to-purple-500' }}">
            <span class="flex items-center justify-center gap-1.5">
                {{ $userHasReacted ? '✓' : '👍' }} I'm down
                @if($reactionCount > 0)
                    <span class="bg-white/20 px-1.5 py-0.5 rounded-full text-xs">
                        {{ $reactionCount }}
                    </span>
                @endif
            </span>
        </button>
        
        <button
            wire:click.stop="$dispatch('openInviteModal', { postId: '{{ $post->id }}' })"
            class="w-full px-3 py-2 bg-gradient-to-r from-purple-500 to-indigo-500 rounded-lg text-xs font-semibold hover:scale-105 transition-all">
            <span class="flex items-center justify-center gap-1.5">
                📨 Invite
                @if($post->invitations_count ?? 0 > 0)
                    <span class="bg-white/20 px-1.5 py-0.5 rounded-full text-xs">
                        {{ $post->invitations_count }}
                    </span>
                @endif
            </span>
        </button>
        
        <div class="pt-2 border-t border-white/10">
            <div class="flex items-center justify-center gap-1.5 text-xs text-gray-400 group-hover:text-cyan-400 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
                <span class="font-semibold">Discussion</span>
            </div>
        </div>
    </div>
</div>

