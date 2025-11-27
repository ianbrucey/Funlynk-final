<div class="relative">
    <!-- Comment Card -->
    <div class="relative p-4 glass-card" style="margin-left: {{ $comment->depth * 2 }}rem;">
        @if($comment->depth > 0)
            <div class="absolute -left-4 top-0 bottom-0 w-0.5 bg-gradient-to-b from-cyan-500/50 to-purple-500/50"></div>
        @endif

        <!-- Comment Header -->
        <div class="flex items-start gap-3 mb-3">
            <!-- Avatar -->
            <div class="flex-shrink-0">
                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-pink-500 to-purple-500 flex items-center justify-center text-white font-bold">
                    {{ strtoupper(substr($comment->user->username ?? 'U', 0, 1)) }}
                </div>
            </div>

            <!-- User Info & Content -->
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 mb-2">
                    <span class="font-semibold text-white">{{ $comment->user->username ?? 'Unknown' }}</span>
                    <span class="text-xs text-gray-500">•</span>
                    <span class="text-xs text-gray-500" title="{{ $comment->created_at->format('M d, Y H:i') }}">
                        {{ $comment->created_at->diffForHumans() }}
                    </span>
                    @if($comment->is_edited)
                        <span class="text-xs text-gray-500">(edited)</span>
                    @endif
                    @if($comment->depth > 0)
                        <span class="text-xs px-2 py-0.5 rounded-full bg-cyan-500/20 text-cyan-400 border border-cyan-500/30">
                            Depth {{ $comment->depth }}
                        </span>
                    @endif
                </div>

                <!-- Comment Content -->
                <div class="text-gray-300 leading-relaxed break-words">
                    {{ $comment->content }}
                </div>
            </div>
        </div>

        <!-- Comment Actions -->
        <div class="flex items-center gap-4 mt-3 ml-13">
            <!-- Reply Button -->
            @auth
                @if($canReply)
                    <button
                        wire:click="toggleReply"
                        class="text-sm text-gray-400 hover:text-cyan-400 transition flex items-center gap-1"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                        </svg>
                        Reply
                    </button>
                @else
                    <span class="text-sm text-gray-600 flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                        </svg>
                        Max depth
                    </span>
                @endif
            @endauth

            <!-- Reactions Count -->
            @if($comment->reactions_count > 0)
                <span class="text-sm text-gray-500 flex items-center gap-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5" />
                    </svg>
                    {{ $comment->reactions_count }}
                </span>
            @endif

            <!-- Replies Count -->
            @if($comment->replies && $comment->replies->count() > 0)
                <span class="text-sm text-gray-500 flex items-center gap-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                    </svg>
                    {{ $comment->replies->count() }} {{ $comment->replies->count() === 1 ? 'reply' : 'replies' }}
                </span>
            @endif

            <!-- Delete Button (Own Comments) -->
            @auth
                @if($comment->user_id === auth()->id())
                    <button
                        wire:click="delete"
                        wire:confirm="Are you sure you want to delete this comment?"
                        class="text-sm text-gray-400 hover:text-red-400 transition flex items-center gap-1"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        Delete
                    </button>
                @endif
            @endauth
        </div>
    </div>

    <!-- Reply Form (Toggle) -->
    @if($showReplyForm)
        <div class="mt-4" style="margin-left: {{ ($comment->depth + 1) * 2 }}rem;">
            <div class="relative p-4 glass-card">
                <div class="top-accent-left"></div>
                <livewire:comments.comment-form
                    :commentable="$comment->commentable"
                    :parent="$comment"
                    :key="'reply-form-' . $comment->id"
                />
            </div>
        </div>
    @endif

    <!-- Nested Replies -->
    @if($comment->replies && $comment->replies->count() > 0)
        <div class="mt-4 space-y-4">
            @foreach($comment->replies as $reply)
                <livewire:comments.comment-item :comment="$reply" :key="'comment-' . $reply->id" />
            @endforeach
        </div>
    @endif
</div>
