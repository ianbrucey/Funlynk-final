<div class="space-y-6">
    <!-- Comment Count Header -->
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-2xl font-bold text-white flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-cyan-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
            </svg>
            <span class="bg-gradient-to-r from-cyan-400 to-purple-400 bg-clip-text text-transparent">
                Comments
            </span>
            <span class="text-gray-400 text-lg">({{ $totalComments }})</span>
        </h3>
    </div>

    <!-- New Comment Form -->
    @auth
        <div class="relative p-6 glass-card">
            <div class="top-accent-center"></div>
            <livewire:comments.comment-form :commentable="$commentable" :key="'comment-form-' . $commentable->id" />
        </div>
    @else
        <div class="relative p-6 glass-card text-center">
            <div class="top-accent-center"></div>
            <p class="text-gray-400 mb-4">Sign in to join the conversation</p>
            <a href="{{ route('login') }}" class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-pink-500 to-purple-500 rounded-xl font-semibold hover:scale-105 transition-all">
                Sign In
            </a>
        </div>
    @endauth

    <!-- Comments List -->
    @if($comments->count() > 0)
        <div class="space-y-4">
            @foreach($comments as $comment)
                <livewire:comments.comment-item :comment="$comment" :key="'comment-' . $comment->id" />
            @endforeach
        </div>

        <!-- Pagination -->
        @if($comments->hasPages())
            <div class="mt-6">
                {{ $comments->links() }}
            </div>
        @endif
    @else
        <div class="relative p-12 glass-card text-center">
            <div class="top-accent-center"></div>
            <div class="flex flex-col items-center gap-4">
                <div class="w-16 h-16 rounded-full bg-slate-800/50 flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                    </svg>
                </div>
                <div>
                    <h4 class="text-lg font-semibold text-gray-300 mb-1">No comments yet</h4>
                    <p class="text-gray-500">Be the first to share your thoughts!</p>
                </div>
            </div>
        </div>
    @endif
</div>
