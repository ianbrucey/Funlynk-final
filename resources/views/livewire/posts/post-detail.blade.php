<div class="min-h-screen py-12">
    <div class="container mx-auto lg:px-6 lg:py-12">
        <div class="max-w-4xl mx-auto space-y-6">
            
            {{-- Back Button --}}
            <div class="px-4 lg:px-0">
                <a href="{{ route('feed.nearby') }}" class="inline-flex items-center gap-2 text-gray-400 hover:text-cyan-400 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back to Feed
                </a>
            </div>

            {{-- Post Card --}}
            <div class="relative p-6 lg:p-8 glass-card lg:rounded-xl border-l-4 border-pink-500">
                <div class="top-accent-center"></div>
                
                {{-- Expiration Timer --}}
                <div class="absolute top-6 right-6">
                    <span class="text-xs text-gray-400 bg-slate-800/50 px-3 py-1.5 rounded-full">
                        ⏱️ {{ $post->expires_at->diffForHumans() }}
                    </span>
                </div>

                {{-- User Info --}}
                <div class="flex items-center gap-3 mb-6">
                    @if($post->user?->profile_image_url)
                        <img
                            src="{{ Storage::url($post->user->profile_image_url) }}"
                            alt="{{ $post->user->display_name ?? $post->user->username }}"
                            class="w-12 h-12 rounded-full object-cover ring-2 ring-pink-500/50 bg-slate-800"
                        >
                    @else
                        <div class="w-12 h-12 rounded-full bg-gradient-to-br from-pink-500 to-purple-500 flex items-center justify-center ring-2 ring-pink-500/50">
                            <span class="text-white font-bold">
                                {{ strtoupper(substr($post->user?->display_name ?? $post->user?->username ?? '?', 0, 1)) }}
                            </span>
                        </div>
                    @endif

                    <div class="flex-1 min-w-0">
                        <a href="{{ route('profile.view', $post->user?->username ?? 'unknown') }}" class="font-semibold text-white hover:text-cyan-400 transition">
                            {{ $post->user?->display_name ?? $post->user?->username ?? 'Unknown User' }}
                        </a>
                        <p class="text-sm text-gray-400">
                            {{ "@".$post->user?->username }} · {{ $post->created_at->diffForHumans() }}
                        </p>
                    </div>
                </div>

                {{-- Content --}}
                <div class="mb-6">
                    <h1 class="text-3xl font-bold mb-4 text-white">{{ $post->title }}</h1>
                    @if($post->description)
                        <p class="text-gray-300 text-lg leading-relaxed">{{ $post->description }}</p>
                    @endif
                </div>
                
                {{-- Location & Time --}}
                <div class="flex flex-wrap items-center gap-4 text-gray-300 mb-6 pb-6 border-b border-white/10">
                    <span class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        {{ $post->location_name }}
                    </span>
                    @if($post->approximate_time)
                        <span class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            {{ $post->approximate_time->format('M j, g:i A') }}
                        </span>
                    @endif
                </div>
                
                {{-- Tags --}}
                @if($post->tags && count($post->tags) > 0)
                    <div class="flex flex-wrap gap-2 mb-6">
                        @foreach($post->tags as $tag)
                            <span class="px-3 py-1.5 bg-slate-800/50 border border-white/10 rounded-lg text-sm text-gray-300">
                                {{ $tag }}
                            </span>
                        @endforeach
                    </div>
                @endif
                
                {{-- Reactions --}}
                <div class="flex items-center gap-3">
                    @php
                        $userHasReacted = $post->reactions->where('user_id', auth()->id())->where('reaction_type', 'im_down')->isNotEmpty();
                        $reactionCount = $post->reactions->where('reaction_type', 'im_down')->count();
                    @endphp
                    <button
                        wire:click="reactToPost('{{ $post->id }}', 'im_down')"
                        class="flex-1 px-6 py-3 rounded-xl text-sm font-semibold hover:scale-105 transition-all
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
                        class="flex-1 px-6 py-3 bg-gradient-to-r from-purple-500 to-indigo-500 rounded-xl text-sm font-semibold hover:scale-105 transition-all">
                        <span class="flex items-center justify-center gap-2">
                            📨 Invite Friends
                        </span>
                    </button>
                </div>
            </div>

            {{-- Comments Section --}}
            <div class="relative p-6 lg:p-8 glass-card lg:rounded-xl">
                <livewire:comments.comment-section
                    :commentable-type="'App\\Models\\Post'"
                    :commentable-id="$post->id"
                />
            </div>

        </div>
    </div>

    {{-- Invite Friends Modal --}}
    <livewire:posts.invite-friends-modal />
</div>
