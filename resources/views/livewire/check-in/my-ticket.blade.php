<div class="min-h-screen py-8 px-4">
    <div class="max-w-md mx-auto space-y-6">
        <!-- Event Info -->
        <div class="glass-card p-6 text-center">
            <h1 class="text-2xl font-bold text-white mb-2">{{ $activity->title }}</h1>
            <p class="text-cyan-400 text-lg mb-1">
                {{ $activity->start_time?->format('l, M j, Y') }}
            </p>
            <p class="text-gray-400">
                {{ $activity->start_time?->format('g:i A') }}
            </p>
            <p class="text-gray-300 mt-2">{{ $activity->location_name }}</p>
        </div>

        <!-- QR Code -->
        <div class="glass-card p-8 text-center">
            <div class="bg-white p-4 rounded-xl inline-block mb-4">
                {!! $qrCodeSvg !!}
            </div>

            <p class="text-gray-400 text-sm">Show this QR code to the host</p>
        </div>

        <!-- Manual Code -->
        <div class="glass-card p-6 text-center">
            <p class="text-gray-400 text-sm mb-2">Or use your check-in code</p>
            <div class="text-3xl font-mono font-bold tracking-widest text-cyan-400">
                {{ $rsvp->check_in_code ?? 'N/A' }}
            </div>
        </div>

        <!-- Navigation -->
        <a href="{{ route('events.show', $activity) }}"
           class="block w-full px-6 py-3 bg-slate-800/50 border border-white/10 rounded-xl text-center text-white hover:border-cyan-500/50 transition">
            ← Back to Event
        </a>
    </div>
</div>