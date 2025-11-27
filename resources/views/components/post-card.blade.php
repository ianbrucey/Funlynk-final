@props(['post'])

<div class="relative p-4 glass-card lg:rounded-xl border-l-4 border-pink-500 hover:border-purple-500 transition-all">
    {{-- Expiration Timer --}}
    <div class="absolute top-4 right-4">
        <span class="text-xs text-gray-400 bg-slate-800/50 px-2 py-1 rounded-full">
            ⏱️ {{ $post->expires_at->diffForHumans() }}
        </span>
    </div>
    
    {{-- Content --}}
    <div class="pr-24">
        <h3 class="text-lg font-bold mb-2 text-white">{{ $post->title }}</h3>
        @if($post->description)
            <p class="text-gray-400 text-sm mb-4 line-clamp-2">{{ $post->description }}</p>
        @endif
    </div>
    
    {{-- Location & Time --}}
    <div class="flex flex-wrap items-center gap-3 text-sm text-gray-400 mb-4">
        <span class="flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            {{ $post->location_name }}
        </span>
        @if($post->approximate_time)
            <span class="flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                {{ $post->approximate_time->format('M j, g:i A') }}
            </span>
        @endif
    </div>
    
    {{-- Tags --}}
    @if($post->tags && count($post->tags) > 0)
        <div class="flex flex-wrap gap-2 mb-4">
            @foreach($post->tags as $tag)
                <span class="px-2 py-1 bg-slate-800/50 border border-white/10 rounded-lg text-xs text-gray-300">
                    {{ $tag }}
                </span>
            @endforeach
        </div>
    @endif
    
    {{-- Reactions --}}
    <div class="flex items-center gap-2">
        @php
            $userHasReacted = $post->reactions->where('user_id', auth()->id())->where('reaction_type', 'im_down')->isNotEmpty();
            $reactionCount = $post->reactions->where('reaction_type', 'im_down')->count();
        @endphp
        <button
            wire:click="reactToPost('{{ $post->id }}', 'im_down')"
            class="flex-1 px-4 py-2 rounded-lg text-sm font-semibold hover:scale-105 transition-all
                {{ $userHasReacted
                    ? 'bg-gradient-to-r from-pink-600 to-purple-600 ring-2 ring-pink-400'
                    : 'bg-gradient-to-r from-pink-500 to-purple-500' }}">
            <span class="flex items-center justify-center gap-2">
                {{ $userHasReacted ? '✓' : '👍' }} I'm down
                @if($reactionCount > 0)
                    <span class="bg-white/20 px-2 py-0.5 rounded-full text-xs">
                        {{ $reactionCount }}
                    </span>
                @endif
            </span>
        </button>
        <button
            wire:click="$dispatch('openInviteModal', { postId: '{{ $post->id }}' })"
            class="flex-1 px-4 py-2 bg-gradient-to-r from-purple-500 to-indigo-500 rounded-lg text-sm font-semibold hover:scale-105 transition-all">
            <span class="flex items-center justify-center gap-2">
                📨 Invite Friends
                @if($post->invitations_count ?? 0 > 0)
                    <span class="bg-white/20 px-2 py-0.5 rounded-full text-xs">
                        {{ $post->invitations_count }}
                    </span>
                @endif
            </span>
        </button>
    </div>
    
    {{-- View Details Link --}}
    <div class="mt-3 pt-3 border-t border-white/10">
        <a href="#" class="text-sm text-cyan-400 hover:text-cyan-300 transition-colors">
            View full details →
        </a>
    </div>
</div>

