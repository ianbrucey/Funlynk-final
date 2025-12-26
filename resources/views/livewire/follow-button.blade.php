<div>
    @if($isFollowing)
        <button wire:click="toggle"
                wire:loading.attr="disabled"
                class="px-4 py-2 border border-purple-500/50 rounded-xl text-sm font-semibold text-white bg-purple-500/20 hover:bg-purple-500/30 transition-all">
            <span wire:loading.remove wire:target="toggle">Following</span>
            <span wire:loading wire:target="toggle">...</span>
        </button>
    @else
        <button wire:click="toggle"
                wire:loading.attr="disabled"
                class="px-4 py-2 bg-gradient-to-r from-pink-500 to-purple-500 rounded-xl text-sm font-semibold text-white hover:scale-105 transition-all">
            <span wire:loading.remove wire:target="toggle">Follow</span>
            <span wire:loading wire:target="toggle">...</span>
        </button>
    @endif
</div>
