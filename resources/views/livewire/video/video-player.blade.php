<div 
    x-data="videoPlayer(@js([
        'videoId' => $video->id,
        'streamUrls' => $streamUrls,
        'autoplay' => $autoplay,
        'muted' => $muted,
        'loop' => $loop,
        'trackViews' => $trackViews,
        'duration' => $video->duration_seconds ?? 0,
    ]))"
    class="relative w-full overflow-hidden rounded-xl bg-black"
    style="aspect-ratio: {{ $aspectRatio }}"
>
    @if($hasError)
        {{-- Error State --}}
        <div class="absolute inset-0 flex items-center justify-center bg-slate-900/90">
            <div class="text-center p-6">
                <div class="mb-4 inline-flex items-center justify-center w-16 h-16 rounded-full bg-red-500/20">
                    <svg class="w-8 h-8 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <p class="text-gray-400">{{ $errorMessage }}</p>
            </div>
        </div>
    @elseif(!$isReady)
        {{-- Loading State --}}
        <div class="absolute inset-0 flex items-center justify-center bg-slate-900/90">
            <div class="text-center">
                <svg class="w-12 h-12 text-cyan-400 animate-spin mx-auto" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class="mt-4 text-gray-400">Loading video...</p>
            </div>
        </div>
    @else
        {{-- Video Player --}}
        <video 
            x-ref="video"
            class="w-full h-full object-contain"
            @if($poster) poster="{{ $poster }}" @endif
            @if($controls) controls @endif
            @if($muted) muted @endif
            @if($loop) loop @endif
            playsinline
            x-on:play="onPlay()"
            x-on:pause="onPause()"
            x-on:ended="onEnded()"
            x-on:timeupdate="onTimeUpdate($event)"
            x-on:error="onError($event)"
        >
            {{-- Fallback for browsers without HLS.js --}}
            @if(isset($streamUrls['master']))
                <source src="{{ $streamUrls['master'] }}" type="application/x-mpegURL">
            @endif
            Your browser does not support video playback.
        </video>

        {{-- Duration Badge --}}
        @if($video->duration_seconds)
            <div 
                class="absolute bottom-2 right-2 px-2 py-1 bg-black/70 rounded text-white text-xs"
                x-show="!isPlaying"
            >
                {{ $this->formattedDuration }}
            </div>
        @endif

        {{-- Quality Selector (Custom) --}}
        @if(!$controls && count($streamUrls['qualities'] ?? []) > 1)
            <div class="absolute top-2 right-2" x-show="isPlaying">
                <select 
                    x-model="currentQuality"
                    x-on:change="changeQuality($event.target.value)"
                    class="bg-black/70 text-white text-xs rounded px-2 py-1 border-none focus:ring-0"
                >
                    <option value="auto">Auto</option>
                    @foreach($streamUrls['qualities'] ?? [] as $quality => $url)
                        <option value="{{ $quality }}">{{ ucfirst($quality) }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        {{-- Click to Play Overlay --}}
        <div 
            x-show="!isPlaying && !hasStarted"
            x-on:click="play()"
            class="absolute inset-0 flex items-center justify-center cursor-pointer bg-gradient-to-t from-black/50 to-transparent"
        >
            <div class="w-20 h-20 rounded-full bg-gradient-to-r from-pink-500 to-purple-500 flex items-center justify-center hover:scale-110 transition-transform shadow-lg shadow-purple-500/50">
                <svg class="w-10 h-10 text-white ml-1" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M8 5v14l11-7z" />
                </svg>
            </div>
        </div>
    @endif
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/hls.js@1.4.14/dist/hls.min.js"></script>
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('videoPlayer', (config) => ({
        videoId: config.videoId,
        streamUrls: config.streamUrls,
        autoplay: config.autoplay,
        muted: config.muted,
        loop: config.loop,
        trackViews: config.trackViews,
        duration: config.duration,

        hls: null,
        isPlaying: false,
        hasStarted: false,
        currentQuality: 'auto',
        watchDuration: 0,
        lastUpdateTime: 0,
        viewRecorded: false,
        urlRefreshTimer: null,

        init() {
            this.$nextTick(() => {
                this.initPlayer();
            });
        },

        initPlayer() {
            const video = this.$refs.video;
            if (!video || !this.streamUrls?.master) return;

            // Check for native HLS support (Safari)
            if (video.canPlayType('application/vnd.apple.mpegurl')) {
                video.src = this.streamUrls.master;
                if (this.autoplay) {
                    video.play().catch(() => {});
                }
            } 
            // Use HLS.js for other browsers
            else if (Hls.isSupported()) {
                this.hls = new Hls({
                    enableWorker: true,
                    lowLatencyMode: false,
                    backBufferLength: 90,
                    maxBufferLength: 30,
                    maxMaxBufferLength: 60,
                });

                this.hls.loadSource(this.streamUrls.master);
                this.hls.attachMedia(video);

                this.hls.on(Hls.Events.MANIFEST_PARSED, () => {
                    if (this.autoplay) {
                        video.play().catch(() => {});
                    }
                });

                this.hls.on(Hls.Events.ERROR, (event, data) => {
                    if (data.fatal) {
                        switch (data.type) {
                            case Hls.ErrorTypes.NETWORK_ERROR:
                                // Try to recover
                                this.hls.startLoad();
                                break;
                            case Hls.ErrorTypes.MEDIA_ERROR:
                                this.hls.recoverMediaError();
                                break;
                            default:
                                this.destroy();
                                break;
                        }
                    }
                });
            }

            // Schedule URL refresh before expiry
            this.scheduleUrlRefresh();
        },

        scheduleUrlRefresh() {
            if (!this.streamUrls?.expires_at) return;

            const expiresAt = new Date(this.streamUrls.expires_at);
            const refreshTime = expiresAt.getTime() - Date.now() - 60000; // Refresh 1 min before expiry

            if (refreshTime > 0) {
                this.urlRefreshTimer = setTimeout(async () => {
                    const result = await @this.refreshUrls();
                    if (result.success) {
                        this.streamUrls = result.urls;
                        this.scheduleUrlRefresh();
                    }
                }, refreshTime);
            }
        },

        play() {
            const video = this.$refs.video;
            if (video) {
                video.play().catch(() => {});
            }
        },

        pause() {
            const video = this.$refs.video;
            if (video) {
                video.pause();
            }
        },

        onPlay() {
            this.isPlaying = true;
            this.hasStarted = true;
        },

        onPause() {
            this.isPlaying = false;
        },

        onEnded() {
            this.isPlaying = false;
            
            // Record final view
            if (this.trackViews && !this.viewRecorded) {
                this.recordView();
            }
        },

        onTimeUpdate(event) {
            const currentTime = event.target.currentTime;
            
            // Track watch duration
            if (currentTime > this.lastUpdateTime) {
                this.watchDuration += currentTime - this.lastUpdateTime;
            }
            this.lastUpdateTime = currentTime;

            // Record view after 3 seconds of watching
            if (this.trackViews && !this.viewRecorded && this.watchDuration >= 3) {
                this.recordView();
            }
        },

        onError(event) {
            console.error('Video playback error:', event);
        },

        changeQuality(quality) {
            if (!this.hls) return;

            if (quality === 'auto') {
                this.hls.currentLevel = -1;
            } else {
                const levels = this.hls.levels;
                const index = levels.findIndex(l => 
                    l.height === parseInt(quality.replace('p', ''))
                );
                if (index !== -1) {
                    this.hls.currentLevel = index;
                }
            }

            this.currentQuality = quality;
        },

        recordView() {
            const quality = this.currentQuality === 'auto' 
                ? null 
                : this.currentQuality;
            
            @this.recordView(this.watchDuration, quality);
            this.viewRecorded = true;
        },

        destroy() {
            if (this.hls) {
                this.hls.destroy();
                this.hls = null;
            }
            if (this.urlRefreshTimer) {
                clearTimeout(this.urlRefreshTimer);
            }
        }
    }));
});
</script>
@endpush
