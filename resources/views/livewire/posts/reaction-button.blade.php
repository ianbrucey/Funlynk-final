@php
    // Size classes
    $sizeClasses = match($size) {
        'sm' => 'px-3 py-2 text-xs',
        'lg' => 'px-6 py-3 text-base',
        default => 'px-4 py-2 text-sm',
    };

    // Width classes
    $widthClass = $fullWidth ? 'w-full' : 'flex-1';

    // Active state classes
    $activeClasses = $hasReacted
        ? 'bg-gradient-to-r from-pink-600 to-purple-600 ring-2 ring-pink-400'
        : 'bg-gradient-to-r from-pink-500 to-purple-500';

    // Owner state classes
    $ownerClasses = $isOwner
        ? 'bg-gradient-to-r from-amber-500 to-orange-500 cursor-default'
        : $activeClasses;
@endphp

<button
    wire:click.stop="react"
    wire:loading.attr="disabled"
    wire:loading.class="opacity-75 cursor-wait"
    @if($isOwner) disabled @endif
    class="{{ $widthClass }} {{ $sizeClasses }} rounded-lg font-semibold transition-all {{ $isOwner ? $ownerClasses : "{$ownerClasses} hover:scale-105" }}">
    <span class="flex items-center justify-center gap-2">
        {{-- Loading spinner --}}
        <span wire:loading wire:target="react" class="inline-block">
            <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </span>

        {{-- Button content --}}
        <span wire:loading.remove wire:target="react">
            @if($isOwner)
                👑 You're Hosting
            @else
                {{ $hasReacted ? $checkedIcon : $icon }} {{ $label }}
            @endif
        </span>

        @if($reactionCount > 0)
            <span class="bg-white/20 px-2 py-0.5 rounded-full text-xs">
                {{ $reactionCount }}
            </span>
        @endif
    </span>
</button>
