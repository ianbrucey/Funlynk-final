@props(['post'])

<div class="relative rounded-[18px] p-4 h-full flex flex-col group"
     style="background: linear-gradient(180deg, rgba(255,255,255,0.03), rgba(255,255,255,0.01)), #111933; border: 1px solid rgba(255,255,255,0.08); box-shadow: 0 12px 30px rgba(0,0,0,0.35);">

    {{-- Conversion Badge (for active posts owned by current user) --}}
    @if($post->status === 'active' && auth()->check() && auth()->id() === $post->user_id)
        <x-conversion-badge
            :post="$post"
            :threshold="$post->reaction_count >= 10 ? 'strong' : 'soft'" />
    @endif

    {{-- Converted Overlay (for converted posts) --}}
    <x-converted-post-overlay :post="$post" />

    {{-- Card Header --}}
    @php
        $author = $post->author;
        $isGroup = $post->posted_as_group && $post->group;
        $authorName = $isGroup
            ? $author->name
            : ($author?->display_name ?? $author?->username ?? 'Unknown User');
        $authorImage = $isGroup
            ? $author->avatar_url
            : $author?->profile_image_url;
        $authorInitial = $isGroup
            ? ($author->emoji ?? substr($author->name, 0, 1))
            : strtoupper(substr($authorName, 0, 1));
            
        $authorUrl = '#';
        if ($isGroup && $author) {
            $authorUrl = route('groups.show', $author);
        } elseif ($author) {
            $authorUrl = route('profile.view', $author->username);
        }
    @endphp
    <div class="flex items-center gap-3">
        {{-- Avatar --}}
        <a href="{{ $authorUrl }}" class="block shrink-0 transition hover:opacity-80" onclick="event.stopPropagation()">
            @if($authorImage)
                <img
                    src="{{ $isGroup ? $authorImage : Storage::url($authorImage) }}"
                    alt="{{ $authorName }}"
                    class="w-11 h-11 rounded-full object-cover"
                >
            @else
                <div class="w-11 h-11 rounded-full grid place-items-center font-bold text-white {{ $isGroup ? 'text-2xl' : '' }}"
                     style="background: linear-gradient(135deg, {{ $isGroup ? '#06b6d4, #8b5cf6' : '#ff3d9a, #8b5cf6' }});">
                    {{ $authorInitial }}
                </div>
            @endif
        </a>

        {{-- Author & Time --}}
        <div class="flex-1 min-w-0">
            <div class="flex items-center gap-1.5">
                <a href="{{ $authorUrl }}" class="font-semibold text-white leading-tight truncate hover:text-cyan-400 transition" onclick="event.stopPropagation()">
                    {{ $authorName }}
                </a>
                @if($isGroup)
                    <span class="px-1.5 py-0.5 text-[0.65rem] font-semibold rounded bg-cyan-500/20 text-cyan-400 border border-cyan-500/30">
                        GROUP
                    </span>
                @endif
            </div>
            <p class="text-xs" style="color: #8a93b2;">
                {{ $post->created_at->diffForHumans(null, true) }} ago
            </p>
        </div>

        {{-- Expiration Timer --}}
        <span class="text-xs" style="color: #8a93b2;">
            {{ $post->expires_at->diffForHumans(null, true) }}
        </span>
    </div>

    {{-- Card Body --}}
    <div class="mt-3 flex-1 cursor-pointer" onclick="window.location.href='{{ route('posts.chat', $post->id) }}'">
        <h3 class="text-[1.1rem] font-bold text-white mb-1.5 line-clamp-2">{{ $post->title }}</h3>

        {{-- Meta Row (Location, Time, Mood) --}}
        <div class="flex flex-wrap items-center gap-2 text-[0.85rem]" style="color: #8a93b2;">
            @if($post->location_name)
                <span class="py-1 px-2.5 rounded-full"
                      style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.08);">
                    📍 {{ Str::limit($post->location_name, 30) }}
                </span>
            @endif
            @if($post->approximate_time)
                <span class="py-1 px-2.5 rounded-full"
                      style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.08);">
                    🕐 {{ $post->approximate_time->format('M j, g:i A') }}
                </span>
            @endif
            @if($post->mood)
                <span class="py-1 px-2.5 rounded-full text-pink-300"
                      style="background: linear-gradient(90deg, rgba(255,61,154,0.2), rgba(139,92,246,0.2)); border: 1px solid rgba(255,61,154,0.3);">
                    ✨ {{ ucfirst($post->mood) }}
                </span>
            @endif
        </div>

        {{-- Tags --}}
        @if($post->tags && count($post->tags) > 0)
            <div class="flex flex-wrap gap-1.5 mt-2">
                @foreach(array_slice($post->tags, 0, 3) as $tag)
                    <span class="px-2 py-0.5 rounded text-xs text-gray-300"
                          style="background: rgba(30,41,59,0.5); border: 1px solid rgba(255,255,255,0.1);">
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
    </div>

    {{-- Card Actions --}}
    <div class="grid gap-2.5 mt-3.5" style="grid-template-columns: 1fr auto;" onclick="event.stopPropagation()">
        <livewire:posts.reaction-button
            :post="$post"
            size="sm"
            :full-width="true"
            :key="'reaction-'.$post->id" />

        <button
            wire:click.stop="$dispatch('openInviteModal', { postId: '{{ $post->id }}' })"
            class="px-3.5 py-3 rounded-xl cursor-pointer transition-all hover:bg-white/5"
            style="background: transparent; border: 1px solid rgba(255,255,255,0.08); color: #eef1ff;">
            Invite
        </button>
    </div>

    {{-- Footer --}}
    <div class="flex justify-between items-center mt-3 text-[0.8rem]" style="color: #8a93b2;">
        <a href="{{ route('posts.chat', $post->id) }}" class="flex items-center gap-1.5 hover:text-cyan-400 transition">
            💬 Discussion
        </a>
        <span>{{ $post->reaction_count ?? 0 }} going</span>
    </div>
</div>

