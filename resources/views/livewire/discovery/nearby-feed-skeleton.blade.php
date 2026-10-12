{{-- #[Lazy] placeholder for NearbyFeed — paints before the feed query runs.
     Root element must stay a <div> to match nearby-feed.blade.php's root. --}}
<div class="min-h-screen" id="nearby-feed-skeleton">
    <div class="container mx-auto">
        <div class="lg:px-0 mb-6">
            <div class="relative p-4 glass-card lg:rounded-xl">
                <div class="h-12 rounded-2xl bg-white/5 animate-pulse mb-4"></div>
                <div class="h-10 rounded-xl bg-white/5 animate-pulse mb-4"></div>
                <div class="grid grid-cols-3 gap-3">
                    <div class="h-14 rounded-lg bg-white/5 animate-pulse"></div>
                    <div class="h-14 rounded-lg bg-white/5 animate-pulse"></div>
                    <div class="h-14 rounded-lg bg-white/5 animate-pulse"></div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @for ($i = 0; $i < 6; $i++)
                <div class="relative rounded-[18px] p-4 h-full flex flex-col"
                     style="background: linear-gradient(180deg, rgba(255,255,255,0.03), rgba(255,255,255,0.01)), #111933; border: 1px solid rgba(255,255,255,0.08);">
                    <div class="flex items-center gap-3">
                        <div class="w-11 h-11 rounded-full bg-white/10 animate-pulse shrink-0"></div>
                        <div class="flex-1 min-w-0 space-y-2">
                            <div class="h-3 w-2/3 rounded bg-white/10 animate-pulse"></div>
                            <div class="h-3 w-1/3 rounded bg-white/10 animate-pulse"></div>
                        </div>
                    </div>
                    <div class="mt-4 space-y-2">
                        <div class="h-3 w-5/6 rounded bg-white/10 animate-pulse"></div>
                        <div class="h-3 w-3/5 rounded bg-white/10 animate-pulse"></div>
                    </div>
                    <div class="mt-auto pt-4">
                        <div class="h-9 w-full rounded-xl bg-white/10 animate-pulse"></div>
                    </div>
                </div>
            @endfor
        </div>
    </div>
</div>

