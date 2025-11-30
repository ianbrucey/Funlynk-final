@props(['notification'])

<a href="{{ route('posts.show', $notification->data['post_id'] ?? '') }}"
   class="block bg-gradient-to-r from-pink-500/10 to-purple-500/10 rounded-lg p-3 border border-pink-500/20 hover:border-pink-500/50 hover:bg-pink-500/15 transition cursor-pointer">
    <div class="flex items-start justify-between gap-3">
        <div class="flex-1">
            <h4 class="text-pink-400 font-semibold text-sm">
                {{ $notification->title }}
            </h4>
            <p class="text-gray-300 text-xs mt-1">
                {{ $notification->message }}
            </p>

            @if(isset($notification->data['reaction_count']))
                <div class="flex items-center gap-2 mt-2">
                    <span class="text-xs bg-pink-500/20 text-pink-300 px-2 py-0.5 rounded-full">
                        {{ $notification->data['reaction_count'] }} reactions
                    </span>
                    @if(($notification->data['threshold'] ?? '') === 'strong')
                        <span class="text-xs bg-purple-500/20 text-purple-300 px-2 py-0.5 rounded-full">
                            Ready to convert!
                        </span>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex-shrink-0 text-pink-400">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </div>
    </div>
</a>