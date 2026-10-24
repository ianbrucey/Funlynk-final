@if(isset($posts) && $posts->count() > 0)
    <div class="grid gap-2 md:grid-cols-2">
        @foreach($posts as $post)
            @php
                $isLive = $post->status === 'active' && $post->expires_at?->isFuture();
                [$badge, $tone] = $post->status === 'converted' ? ['Converted', 'cyan'] : ($isLive ? ['Live', 'green'] : ['Expired', 'muted']);
                $place = $post->location_name ? trim(explode(',', $post->location_name)[0]) : null;
                $meta = collect([$post->created_at->diffForHumans(), $place, ($post->reaction_count ?? 0).' reactions'])->filter()->implode(' · ');
            @endphp
            <x-fl.profile-row :href="route('posts.show', $post->id)" :title="$post->title" :meta="$meta" :badge="$badge" :tone="$tone" wire:key="post-{{ $post->id }}" />
        @endforeach
    </div>
    <div class="mt-4">{{ $posts->links() }}</div>
@else
    <div class="px-4 py-12 text-center">
        <div class="text-4xl" aria-hidden="true">💫</div>
        <h3 class="mt-3 text-sm font-medium text-gray-300">No posts yet</h3>
        <p class="mt-1 text-xs text-gray-500">{{ auth()->id() === $user->id ? "You haven't created any posts yet." : "This user hasn't created any posts yet." }}</p>
        @if(auth()->id() === $user->id)
            <a href="{{ route('posts.create') }}" wire:navigate class="mt-4 inline-flex min-h-[44px] items-center rounded-xl bg-gradient-to-r from-pink-500 to-purple-500 px-6 text-sm font-semibold text-white">Create a post</a>
        @endif
    </div>
@endif
