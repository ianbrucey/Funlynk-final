@if($isFollowing)
    <button type="button" x-on:click="confirmUnfollow = true" wire:loading.attr="disabled"
            class="flex min-h-[44px] flex-1 items-center justify-center rounded-xl border border-white/15 bg-slate-800/50 px-6 text-sm font-semibold text-white hover:border-cyan-400/50 transition md:flex-none">
        Following
    </button>
@else
    <button type="button" wire:click="follow" wire:loading.attr="disabled"
            class="flex min-h-[44px] flex-1 items-center justify-center rounded-xl bg-gradient-to-r from-pink-500 to-purple-500 px-6 text-sm font-semibold text-white hover:scale-105 transition md:flex-none">
        <span wire:loading.remove wire:target="follow">Follow</span>
        <span wire:loading wire:target="follow">…</span>
    </button>
@endif
<button type="button" wire:click="startConversation" wire:loading.attr="disabled"
        class="flex min-h-[44px] flex-1 items-center justify-center rounded-xl border border-white/15 bg-slate-800/50 px-6 text-sm font-semibold text-white hover:border-cyan-400/50 transition md:flex-none">
    <span wire:loading.remove wire:target="startConversation">Message</span>
    <span wire:loading wire:target="startConversation">…</span>
</button>
