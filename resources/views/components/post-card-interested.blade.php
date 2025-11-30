@props(['post', 'canRemove' => false])

<div class="relative glass-card p-6 hover:scale-105 transition-all group">
    {{-- Converted Badge --}}
    @if($post->status === 'converted')
        <div class="absolute top-0 left-0 right-0 bg-gradient-to-r from-pink-500/20 to-purple-500/20 backdrop-blur-sm p-3 rounded-t-xl border-b border-white/10">
            <div class="flex items-center justify-between">
                <span class="text-sm font-semibold text-white">✨ Converted to Event</span>
                @if($post->convertedActivity)
                    <a href="{{ route('activities.show', $post->convertedActivity->id) }}"
                       class="text-xs text-cyan-400 hover:text-cyan-300">
                        View Event →
                    </a>
                @endif
            </div>
        </div>
        <div class="h-12"></div> {{-- Spacer for badge --}}
    @endif

    {{-- Post Content --}}
    <h3 class="text-lg font-semibold text-white mb-2">{{ $post->title }}</h3>
    <p class="text-gray-400 text-sm mb-4 line-clamp-2">{{ $post->description }}</p>

    {{-- Meta Info --}}
    <div class="flex items-center gap-4 text-xs text-gray-500 mb-4">
        <span>📍 {{ $post->location_name }}</span>
        <span>👍 {{ $post->reaction_count }}</span>
    </div>

    {{-- Interested Since --}}
    @php
        $userReaction = $post->reactions()->where('user_id', auth()->id())->first();
    @endphp
    @if($userReaction)
        <div class="text-xs text-gray-500 mb-4">
            Interested since {{ $userReaction->created_at->diffForHumans() }}
        </div>
    @endif

    {{-- Actions --}}
    <div class="flex gap-2">
        @if($post->status === 'converted' && $post->convertedActivity)
            <a href="{{ route('activities.show', $post->convertedActivity->id) }}"
               class="flex-1 px-4 py-2 bg-gradient-to-r from-pink-500 to-purple-500 rounded-lg text-center text-sm font-semibold hover:scale-105 transition-all">
                View Event
            </a>
        @else
            <a href="{{ route('posts.show', $post->id) }}"
               class="flex-1 px-4 py-2 bg-slate-800/50 border border-white/10 rounded-lg text-center text-sm hover:border-cyan-500/50 transition">
                View Post
            </a>
        @endif

        @if($canRemove && $post->status === 'active')
            <button
                wire:click="removeInterest('{{ $post->id }}')"
                class="px-4 py-2 bg-red-500/20 border border-red-500/30 rounded-lg text-sm hover:bg-red-500/30 transition"
                title="Remove Interest">
                ✕
            </button>
        @endif
    </div>

    {{-- Expiry Warning --}}
    @if($post->status === 'active' && $post->expires_at && $post->expires_at->diffInHours() < 6)
        <div class="mt-3 text-xs text-amber-400">
            ⏰ Expires {{ $post->expires_at->diffForHumans() }}
        </div>
    @endif
</div>