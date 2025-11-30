@props(['post'])

@if($post->status === 'converted' && $post->convertedActivity)
    <div class="absolute inset-0 bg-gradient-to-br from-purple-900/80 to-pink-900/80 backdrop-blur-sm rounded-xl flex items-center justify-center z-20">
        <div class="text-center p-6">
            <div class="text-4xl mb-3">✨</div>
            <h4 class="text-xl font-bold text-white mb-2">Converted to Event</h4>
            <p class="text-gray-300 text-sm mb-4">
                {{ $post->reaction_count }} people were interested
            </p>
            <a href="{{ route('activities.show', $post->convertedActivity->id) }}"
               class="inline-block px-6 py-3 bg-gradient-to-r from-cyan-500 to-blue-500 rounded-xl font-semibold hover:scale-105 transition-all">
                View Event →
            </a>
        </div>
    </div>
@endif