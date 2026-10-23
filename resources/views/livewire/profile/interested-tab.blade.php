<div x-data="{ confirmId: null }">
    <p class="mb-2 px-1 text-xs text-gray-400">Posts you tapped "I'm down" on.</p>

    <div class="mb-3 flex gap-1.5 overflow-x-auto px-0.5 pb-1">
        @foreach(['active' => 'Active', 'converted' => 'Converted to events', 'expired' => 'Expired'] as $key => $label)
            <button type="button" wire:click="setFilter('{{ $key }}')" wire:key="filter-{{ $key }}"
                    class="min-h-[36px] whitespace-nowrap rounded-full px-3 text-xs transition {{ $filter === $key ? 'bg-gradient-to-r from-pink-500 to-purple-500 text-white' : 'border border-white/10 bg-slate-800/50 text-gray-300 hover:border-cyan-400/40' }}">
                {{ $label }}
            </button>
        @endforeach
    </div>

    @if($posts->count() > 0)
        <div class="grid gap-2 md:grid-cols-2">
            @foreach($posts as $post)
                @php
                    [$badge, $tone] = match (true) {
                        $post->status === 'converted' => ['Converted', 'cyan'],
                        $post->status === 'active' && $post->expires_at?->isFuture() => ['Live', 'green'],
                        default => ['Expired', 'muted'],
                    };
                    $meta = collect([$post->created_at->diffForHumans(), ($post->reaction_count ?? 0).' reactions'])->implode(' · ');
                @endphp
                <x-fl.profile-row :href="route('posts.show', $post->id)" :title="$post->title" :meta="$meta" :badge="$badge" :tone="$tone" wire:key="saved-{{ $post->id }}">
                    @if(auth()->id() === $user->id)
                        <button type="button" x-on:click="confirmId = '{{ $post->id }}'" aria-label="Remove from saved"
                                class="min-h-[44px] min-w-[44px] self-center rounded-xl text-gray-400 hover:text-white">✕</button>
                    @endif
                </x-fl.profile-row>
            @endforeach
        </div>
        <div class="mt-4">{{ $posts->links() }}</div>
    @else
        <div class="px-4 py-12 text-center">
            <div class="text-4xl" aria-hidden="true">💫</div>
            <h3 class="mt-3 text-sm font-medium text-gray-300">
                @if($filter === 'active') Nothing saved yet @elseif($filter === 'converted') No converted posts @else No expired posts @endif
            </h3>
            <p class="mt-1 text-xs text-gray-500">
                @if($filter === 'active')
                    Tap "I'm down" on a post to save it here.
                @elseif($filter === 'converted')
                    Posts you saved that become events appear here.
                @else
                    Expired posts you saved appear here.
                @endif
            </p>
            @if($filter === 'active' && auth()->id() === $user->id)
                <a href="{{ route('feed.nearby') }}" wire:navigate class="mt-4 inline-flex min-h-[44px] items-center rounded-xl bg-gradient-to-r from-pink-500 to-purple-500 px-6 text-sm font-semibold text-white">Discover nearby</a>
            @endif
        </div>
    @endif

    <div x-show="confirmId" x-cloak x-on:keydown.escape.window="confirmId = null" class="fixed inset-0 z-50 flex items-end justify-center bg-black/60 p-3 md:items-center">
        <div class="glass-card w-full max-w-sm p-5" x-on:click.outside="confirmId = null">
            <p class="font-semibold text-white">Remove from Saved?</p>
            <p class="mt-1 text-sm text-gray-300">You can tap "I'm down" again later if the post is still live.</p>
            <button type="button" x-on:click="$wire.removeInterest(confirmId); confirmId = null" class="mt-4 min-h-[44px] w-full rounded-xl border border-red-400/50 text-sm font-semibold text-red-300">Remove</button>
            <button type="button" x-on:click="confirmId = null" class="mt-2 min-h-[44px] w-full rounded-xl border border-white/15 bg-slate-800/50 text-sm font-semibold text-white">Cancel</button>
        </div>
    </div>
</div>
