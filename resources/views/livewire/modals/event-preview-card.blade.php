<div class="space-y-6">
    <div class="text-center mb-6">
        <h3 class="text-xl font-bold text-white mb-2">Event Preview</h3>
        <p class="text-gray-400 text-sm">This is how your event will appear to users</p>
    </div>

    {{-- Event Card Preview --}}
    <div class="glass-card p-6 max-w-2xl mx-auto">
        <div class="top-accent-center"></div>

        {{-- Event Image --}}
        @if($imagePreview)
            <div class="w-full h-48 rounded-xl mb-4 overflow-hidden">
                <img src="{{ $imagePreview }}" alt="Event preview" class="w-full h-full object-cover">
            </div>
        @else
            <div class="w-full h-48 bg-gradient-to-br from-pink-500/20 to-purple-500/20 rounded-xl mb-4 flex items-center justify-center">
                <div class="text-center">
                    <div class="text-4xl mb-2">🎉</div>
                    <p class="text-gray-400 text-sm">Event Image</p>
                </div>
            </div>
        @endif

        {{-- Event Details --}}
        <h3 class="text-2xl font-bold text-white mb-3">{{ $title ?: 'Event Title' }}</h3>

        <div class="space-y-3 mb-4">
            {{-- Date/Time --}}
            <div class="flex items-start gap-3">
                <div class="text-2xl">📅</div>
                <div>
                    <p class="text-white font-semibold">{{ $startDate->format('l, F j, Y') }}</p>
                    <p class="text-gray-400 text-sm">
                        {{ $startDate->format('g:i A') }} - {{ $endDate->format('g:i A') }}
                    </p>
                </div>
            </div>

            {{-- Location --}}
            <div class="flex items-start gap-3">
                <div class="text-2xl">📍</div>
                <div>
                    <p class="text-white">{{ $location ?: 'Event Location' }}</p>
                </div>
            </div>

            {{-- Price --}}
            <div class="flex items-start gap-3">
                <div class="text-2xl">💵</div>
                <div>
                    <p class="text-white font-semibold">
                        @if($price > 0)
                            ${{ number_format($price, 2) }}
                        @else
                            Free
                        @endif
                    </p>
                </div>
            </div>

            {{-- Capacity --}}
            <div class="flex items-start gap-3">
                <div class="text-2xl">👥</div>
                <div>
                    <p class="text-white">{{ $maxAttendees }} spots available</p>
                    @if($interestedCount > 0)
                        <p class="text-gray-400 text-sm">{{ $interestedCount }} people already interested</p>
                    @endif
                </div>
            </div>
        </div>

        {{-- Tags --}}
        @if(count($tags) > 0)
            <div class="flex flex-wrap gap-2 mb-4">
                @foreach($tags as $tag)
                    <span class="px-3 py-1 rounded-full text-xs bg-gradient-to-r from-pink-500/20 to-purple-500/20 border border-pink-500/30 text-pink-300">
                        {{ is_array($tag) ? $tag['name'] : $tag }}
                    </span>
                @endforeach
            </div>
        @endif

        {{-- Description --}}
        <div class="border-t border-white/10 pt-4">
            <h4 class="text-white font-semibold mb-2">About this event</h4>
            <p class="text-gray-400 text-sm whitespace-pre-wrap">{{ $description ?: 'Event description will appear here.' }}</p>
        </div>

        {{-- Mock RSVP Button --}}
        <div class="mt-6">
            <button class="w-full px-6 py-3 bg-gradient-to-r from-pink-500 to-purple-500 rounded-xl font-semibold opacity-50 cursor-not-allowed">
                RSVP to Event (Preview)
            </button>
        </div>
    </div>

    {{-- Notification Preview --}}
    <div class="glass-card p-4 max-w-2xl mx-auto bg-cyan-500/5 border border-cyan-500/20">
        <h4 class="text-white font-semibold mb-2">📬 Notification Preview</h4>
        <p class="text-gray-400 text-sm mb-3">
            This is what interested users will see:
        </p>

        <div class="glass-card p-4 bg-slate-900/50">
            <div class="flex gap-3">
                <div class="text-2xl">🎉</div>
                <div class="flex-1">
                    <h5 class="text-white font-semibold mb-1">{{ $title ?: 'Event Title' }} is now an event!</h5>
                    <p class="text-gray-400 text-sm mb-2">
                        You showed interest in this post. The host has created an event!
                    </p>
                    <div class="text-xs text-gray-500">
                        📅 {{ $startDate->format('M j, g:i A') }} • 📍 {{ Str::limit($location ?: 'Location', 20) }} •
                        @if($price > 0)
                            💵 ${{ number_format($price, 2) }}
                        @else
                            Free
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
