<div class="relative p-8 glass-card">
    <div class="top-accent-center"></div>

    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold">Group Timeline</h2>
        <div class="flex gap-2">
            <button wire:click="createPost" class="px-4 py-2 bg-gradient-to-r from-pink-500 to-purple-500 rounded-xl font-semibold hover:scale-105 transition-all text-sm">
                New Post
            </button>
            <button wire:click="createEvent" class="px-4 py-2 bg-slate-800/50 border border-white/10 rounded-xl hover:border-cyan-500/50 transition text-sm">
                New Event
            </button>
        </div>
    </div>

    <div class="space-y-8" wire:poll.10s="loadMore" wire:init="loadMore" wire:scroll.window="loadMore">
        @forelse($items as $item)
            <div class="relative p-4 rounded-2xl bg-slate-800/50 border border-white/10 hover:border-purple-500/50 transition-all group cursor-pointer">
                @if($item->type === 'post')
                    <h3 class="text-xl font-semibold text-white mb-2">{{ $item->title }}</h3>
                    <p class="text-gray-300 text-sm mb-3">{{ $item->description }}</p>
                    <div class="flex items-center text-gray-400 text-xs">
                        <span class="mr-2">Posted by {{ $item->user->name }}</span>
                        <span>{{ $item->created_at->diffForHumans() }}</span>
                    </div>
                    <div class="flex items-center gap-4 mt-3">
                        <button wire:click="likeItem('{{ $item->id }}', 'post')" class="flex items-center gap-1 text-gray-400 hover:text-pink-500 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 22.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path></svg>
                            {{ $item->reactions->count() }} Reactions
                        </button>
                        <span class="flex items-center gap-1 text-gray-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                            0 Comments
                        </span>
                        {{-- Delete button (creator/admin only) --}}
                        @if(auth()->id() === $item->user_id || $group->creator->id === auth()->id())
                            <button wire:click="deleteItem('{{ $item->id }}', 'post')" class="ml-auto text-gray-400 hover:text-red-500 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            </button>
                        @endif
                    </div>
                @elseif($item->type === 'event')
                    <h3 class="text-xl font-semibold text-cyan-400 mb-2">{{ $item->title }}</h3>
                    <p class="text-gray-300 text-sm mb-3">{{ $item->description }}</p>
                    <div class="flex items-center text-gray-400 text-xs">
                        <span class="mr-2">Hosted by {{ $item->host->name }}</span>
                        <span>{{ $item->start_time->format('M d, H:i') }}</span>
                    </div>
                    <div class="flex items-center gap-4 mt-3">
                        <span class="flex items-center gap-1 text-gray-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.653-.146-1.28-.423-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.653.146-1.28.423-1.857m0 0a5.002 5.002 0 019.154 0m-4.255-4.255a2 2 0 11-2.83 2.83 2 2 0 012.83-2.83zm0 0l-1.06-1.06A3 3 0 0012 8.343V4"></path></svg>
                            {{ $item->rsvps->count() }} RSVPs
                        </span>
                        <span class="flex items-center gap-1 text-gray-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                            0 Comments
                        </span>
                        {{-- Delete button (creator/admin only) --}}
                        @if(auth()->id() === $item->host_id || $group->creator->id === auth()->id())
                            <button wire:click="deleteItem('{{ $item->id }}', 'event')" class="ml-auto text-gray-400 hover:text-red-500 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            </button>
                        @endif
                    </div>
                @endif
            </div>
        @empty
            <div class="text-center text-gray-400 py-8">
                <p>No posts or events in this group yet.</p>
                <p class="text-sm">Be the first to share something!</p>
            </div>
        @endforelse

        @if($hasMore)
            <div class="text-center mt-8">
                <button wire:click="loadMore" class="px-6 py-3 bg-slate-800/50 border border-white/10 rounded-xl hover:border-cyan-500/50 transition">
                    Load More
                </button>
            </div>
        @endif
    </div>
</div>