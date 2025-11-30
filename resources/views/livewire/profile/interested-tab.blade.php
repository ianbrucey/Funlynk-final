<div class="space-y-6">
    {{-- Filter Tabs --}}
    <div class="flex gap-2 overflow-x-auto pb-2">
        <button
            wire:click="setFilter('active')"
            class="px-4 py-2 rounded-lg transition-all whitespace-nowrap {{ $filter === 'active' ? 'bg-gradient-to-r from-pink-500 to-purple-500 text-white' : 'bg-slate-800/50 text-gray-300 hover:bg-slate-700/50' }}">
            Active
        </button>
        <button
            wire:click="setFilter('converted')"
            class="px-4 py-2 rounded-lg transition-all whitespace-nowrap {{ $filter === 'converted' ? 'bg-gradient-to-r from-pink-500 to-purple-500 text-white' : 'bg-slate-800/50 text-gray-300 hover:bg-slate-700/50' }}">
            Converted to Events
        </button>
        <button
            wire:click="setFilter('expired')"
            class="px-4 py-2 rounded-lg transition-all whitespace-nowrap {{ $filter === 'expired' ? 'bg-gradient-to-r from-pink-500 to-purple-500 text-white' : 'bg-slate-800/50 text-gray-300 hover:bg-slate-700/50' }}">
            Expired
        </button>
    </div>

    {{-- Posts Grid --}}
    @if($posts->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($posts as $post)
                <x-post-card-interested
                    :post="$post"
                    :can-remove="auth()->id() === $user->id"
                    wire:key="post-{{ $post->id }}" />
            @endforeach
        </div>

        <div class="mt-6">
            {{ $posts->links() }}
        </div>
    @else
        {{-- Empty State --}}
        <div class="glass-card p-12 text-center">
            <div class="text-6xl mb-4">💫</div>
            <h3 class="text-xl font-semibold text-white mb-2">
                @if($filter === 'active')
                    No Active Interests Yet
                @elseif($filter === 'converted')
                    No Converted Posts
                @else
                    No Expired Posts
                @endif
            </h3>
            <p class="text-gray-400 mb-6">
                @if($filter === 'active')
                    Start exploring posts and tap "I'm down" to show your interest!
                @elseif($filter === 'converted')
                    Posts you're interested in that become events will appear here.
                @else
                    Expired posts you were interested in will appear here.
                @endif
            </p>
            @if($filter === 'active')
                <a href="{{ route('discovery.nearby') }}"
                   class="inline-block px-6 py-3 bg-gradient-to-r from-pink-500 to-purple-500 rounded-xl font-semibold hover:scale-105 transition-all">
                    Discover Posts
                </a>
            @endif
        </div>
    @endif
</div>
