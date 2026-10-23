<div x-data="{ intro: null, unfollow: null }"
     x-on:follow-intro.window="intro = $event.detail.name"
     x-on:confirm-unfollow.window="unfollow = $event.detail"
     x-on:keydown.escape.window="intro = null; unfollow = null">
    <div x-show="intro" x-cloak class="fixed inset-0 z-50 flex items-end justify-center bg-black/60 p-3 md:items-center" role="dialog" aria-modal="true">
        <div class="glass-card w-full max-w-sm p-5" x-on:click.outside="intro = null">
            <p class="font-semibold text-white">You're following <span x-text="intro"></span> 🔔</p>
            <p class="mt-2 text-sm leading-relaxed text-gray-300">You'll get a notification when <span x-text="intro"></span> publishes an event. You can unfollow any time.</p>
            <button type="button" x-on:click="intro = null" class="mt-4 min-h-[44px] w-full rounded-xl bg-gradient-to-r from-pink-500 to-purple-500 text-sm font-semibold text-white">Got it</button>
            <a href="{{ route('settings.notifications') }}" wire:navigate class="mt-3 inline-block text-xs text-cyan-300 hover:underline">Notification settings</a>
        </div>
    </div>
    <div x-show="unfollow" x-cloak class="fixed inset-0 z-50 flex items-end justify-center bg-black/60 p-3 md:items-center" role="dialog" aria-modal="true">
        <div class="glass-card w-full max-w-sm p-5" x-on:click.outside="unfollow = null">
            <p class="font-semibold text-white">Unfollow <span x-text="unfollow?.name"></span>?</p>
            <p class="mt-1 text-sm text-gray-300">You'll stop getting notifications about their new events.</p>
            <button type="button" x-on:click="$wire.unfollowHost(unfollow.id); unfollow = null" class="mt-4 min-h-[44px] w-full rounded-xl border border-red-400/50 text-sm font-semibold text-red-300">Unfollow</button>
            <button type="button" x-on:click="unfollow = null" class="mt-2 min-h-[44px] w-full rounded-xl border border-white/15 bg-slate-800/50 text-sm font-semibold text-white">Cancel</button>
        </div>
    </div>
</div>
