<div class="min-h-screen py-8 px-4">
    <div class="max-w-lg mx-auto space-y-6">
        <!-- Header -->
        <div class="text-center">
            <h1 class="text-2xl font-bold text-white mb-2">Scan Attendees</h1>
            <p class="text-gray-400">{{ $activity->title }}</p>
        </div>

        <!-- Stats Bar -->
        <div class="glass-card p-4">
            <div class="grid grid-cols-3 gap-4 text-center">
                <div>
                    <div class="text-2xl font-bold text-cyan-400">{{ $stats['checked_in_count'] ?? 0 }}</div>
                    <div class="text-xs text-gray-400">Checked In</div>
                </div>
                <div>
                    <div class="text-2xl font-bold text-yellow-400">{{ $stats['pending_count'] ?? 0 }}</div>
                    <div class="text-xs text-gray-400">Pending</div>
                </div>
                <div>
                    <div class="text-2xl font-bold text-white">{{ $stats['check_in_percentage'] ?? 0 }}%</div>
                    <div class="text-xs text-gray-400">Complete</div>
                </div>
            </div>
        </div>

        <!-- Success Message -->
        @if($successMessage)
            <div class="glass-card p-4 border-l-4 border-green-500 bg-green-500/10">
                <div class="flex items-center gap-3">
                    <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>
                        <p class="text-green-400 font-semibold">{{ $successMessage }}</p>
                        @if($lastCheckedIn)
                            <p class="text-gray-400 text-sm">{{ $lastCheckedIn->user->email }}</p>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        <!-- Error Message -->
        @if($errorMessage)
            <div class="glass-card p-4 border-l-4 border-red-500 bg-red-500/10">
                <div class="flex items-center gap-3">
                    <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-red-400 font-semibold">{{ $errorMessage }}</p>
                </div>
            </div>
        @endif

        <!-- QR Scanner -->
        <div class="glass-card p-4">
            <div id="qr-reader" class="rounded-lg overflow-hidden"></div>
            <p class="text-center text-gray-400 text-sm mt-4">Point camera at attendee's QR code</p>
        </div>

        <!-- Navigation -->
        <div class="flex gap-4">
            <a href="{{ route('activities.attendees', $activity) }}" 
               class="flex-1 px-4 py-3 bg-slate-800/50 border border-white/10 rounded-xl text-center hover:border-cyan-500/50 transition">
                View List
            </a>
            <a href="{{ route('activities.show', $activity) }}" 
               class="flex-1 px-4 py-3 bg-slate-800/50 border border-white/10 rounded-xl text-center hover:border-cyan-500/50 transition">
                Back to Event
            </a>
        </div>
    </div>

    <!-- html5-qrcode CDN -->
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const html5QrCode = new Html5Qrcode("qr-reader");
            let isProcessing = false;

            const config = {
                fps: 10,
                qrbox: { width: 250, height: 250 },
                aspectRatio: 1.0
            };

            html5QrCode.start(
                { facingMode: "environment" },
                config,
                (decodedText) => {
                    if (isProcessing) return;
                    isProcessing = true;
                    
                    // Send to Livewire
                    @this.call('processQrCode', decodedText).then(() => {
                        setTimeout(() => { isProcessing = false; }, 2000);
                    });
                },
                (errorMessage) => {
                    // Ignore scan errors
                }
            ).catch((err) => {
                console.error('Camera error:', err);
                document.getElementById('qr-reader').innerHTML = 
                    '<div class="p-8 text-center text-red-400">Camera access denied or unavailable.<br>Please allow camera access and reload.</div>';
            });

            // Play sound on success
            Livewire.on('play-success-sound', () => {
                const audio = new Audio('data:audio/wav;base64,UklGRl9vT19XQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YU');
                audio.play().catch(() => {});
            });
        });
    </script>
</div>

