<div class="min-h-screen py-12">
    <div class="container mx-auto lg:px-6 lg:py-12">

        {{-- Back Button --}}
        <div class="px-4 lg:px-0 mb-6">
            <a href="{{ route('feed.nearby') }}" class="inline-flex items-center gap-2 text-gray-400 hover:text-cyan-400 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Feed
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 lg:gap-8">

            {{-- Main Content (Left Column) --}}
            <div class="lg:col-span-2 space-y-6">

                {{-- Post Card --}}
                <div class="relative p-6 lg:p-8 glass-card lg:rounded-xl border-l-4 border-pink-500">
                    <div class="top-accent"></div>

                    {{-- Expiration Timer --}}
                    <div class="absolute top-6 right-6">
                        <span class="text-xs text-gray-400 bg-slate-800/50 px-3 py-1.5 rounded-full">
                            ⏱️ {{ $post->expires_at->diffForHumans() }}
                        </span>
                    </div>

                    {{-- User Info --}}
                    <div class="flex items-center gap-3 mb-4">
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
                            <a href="{{ route('profile.view', $post->user?->username ?? 'unknown') }}" class="font-semibold text-white hover:text-cyan-400 transition truncate block">
                                {{ $post->user?->display_name ?? $post->user?->username ?? 'Unknown User' }}
                            </a>
                            <p class="text-xs text-gray-400">
                                {{ "@".$post->user?->username }} · {{ $post->created_at->diffForHumans() }}
                            </p>
                        </div>
                    </div>

                    {{-- Content --}}
                    <h1 class="text-3xl font-bold mb-4 text-white pr-24">{{ $post->title }}</h1>
                    @if($post->description)
                        <p class="text-gray-300 text-lg mb-6 leading-relaxed">{{ $post->description }}</p>
                    @endif

                    {{-- Location & Time --}}
                    <div class="flex flex-wrap items-center gap-4 text-sm text-gray-400 mb-6 pb-6 border-b border-white/10">
                        <span class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            {{ $post->location_name }}
                        </span>
                        @if($post->approximate_time)
                            <span class="flex items-center gap-2">
                                <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                                <span class="px-3 py-1 bg-slate-800/50 border border-white/10 rounded-lg text-sm text-gray-300">
                                    {{ $tag }}
                                </span>
                            @endforeach
                        </div>
                    @endif

                    {{-- Reactions & Actions --}}
                    <div class="flex items-center gap-3">
                        @php
                            $userHasReacted = $post->reactions->where('user_id', auth()->id())->where('reaction_type', 'im_down')->isNotEmpty();
                            $reactionCount = $post->reactions->where('reaction_type', 'im_down')->count();
                            $isOwner = auth()->id() === $post->user_id;
                        @endphp
                        <button
                            @if(!$isOwner) wire:click="reactToPost('{{ $post->id }}', 'im_down')" @endif
                            @if($isOwner) disabled @endif
                            class="flex-1 px-6 py-3 rounded-xl text-sm font-semibold transition-all
                                {{ $isOwner
                                    ? 'bg-slate-700/50 border border-white/10 text-gray-400 cursor-not-allowed'
                                    : ($userHasReacted
                                        ? 'bg-gradient-to-r from-pink-600 to-purple-600 ring-2 ring-pink-400 hover:scale-105'
                                        : 'bg-gradient-to-r from-pink-500 to-purple-500 hover:scale-105') }}">
                            <span class="flex items-center justify-center gap-2">
                                @if($isOwner)
                                    👑 You're Hosting
                                @else
                                    {{ $userHasReacted ? '✓' : '👍' }} I'm down
                                @endif
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
                                @if($post->invitations_count ?? 0 > 0)
                                    <span class="bg-white/20 px-2 py-0.5 rounded-full text-xs">
                                        {{ $post->invitations_count }}
                                    </span>
                                @endif
                            </span>
                        </button>
                    </div>
                </div>

                {{-- Chat Section --}}
                <div class="relative p-6 lg:p-8 glass-card lg:rounded-xl">
                    <div class="top-accent"></div>
                    <h2 class="text-2xl font-bold mb-6 text-white">Discussion</h2>
                    <div class="h-[600px]">
                        <livewire:chat.chat-component :conversationable="$post" />
                    </div>
                </div>

            </div>

            {{-- Sidebar (Right Column) --}}
            <div class="space-y-6 lg:space-y-8">

                {{-- Author Info --}}
                <div class="relative p-6 glass-card lg:rounded-xl">
                    <h3 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-4">Posted By</h3>
                    <div class="flex items-center gap-4">
                        @if($post->user->profile_image_url)
                            <img src="{{ Storage::url($post->user->profile_image_url) }}" class="w-12 h-12 rounded-full object-cover border-2 border-white/10">
                        @else
                            <div class="w-12 h-12 rounded-full bg-gradient-to-br from-pink-500 to-purple-500 flex items-center justify-center text-xl font-bold text-white">
                                {{ substr($post->user->display_name ?? $post->user->username, 0, 1) }}
                            </div>
                        @endif
                        <div>
                            <div class="font-bold text-white">{{ $post->user->display_name ?? $post->user->username }}</div>
                            <div class="text-xs text-gray-400">{{ "@".$post->user->username }}</div>
                        </div>
                    </div>
                </div>

                {{-- Post Stats --}}
                <div class="relative p-6 glass-card lg:rounded-xl">
                    <h3 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-4">Activity</h3>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-400">Views</span>
                            <span class="text-white font-semibold">{{ $post->view_count }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-400">Reactions</span>
                            <span class="text-white font-semibold">{{ $post->reaction_count }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-400">Expires</span>
                            <span class="text-white font-semibold">{{ $post->expires_at->diffForHumans() }}</span>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <style>
        .top-accent {
            position: absolute;
            top: 0;
            left: 0;
            width: 8rem;
            height: 0.25rem;
            background: linear-gradient(to right, #ec4899, #8b5cf6, transparent);
            border-radius: 9999px;
        }
    </style>

    {{-- Invite Friends Modal --}}
    <livewire:posts.invite-friends-modal />
</div>
