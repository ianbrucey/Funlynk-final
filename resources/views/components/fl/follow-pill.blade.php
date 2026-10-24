@props(['hostId', 'name' => '', 'following' => false, 'variant' => 'pill'])

@php
    $label = $name !== '' ? $name : null;
    $text = $following ? '✓ Following'.($label ? " {$label}" : '') : '+ Follow'.($label ? " {$label}" : '');
    $classes = match (true) {
        $variant === 'icon' => 'h-11 w-11 justify-center rounded-full '.($following ? 'border border-purple-500/50 bg-purple-500/20' : 'bg-gradient-to-r from-pink-500 to-purple-500'),
        $variant === 'row' => 'w-full justify-center rounded-xl '.($following ? 'border border-purple-500/50 bg-purple-500/20' : 'border border-white/10 bg-slate-800/50 hover:border-cyan-400/50'),
        default => 'max-w-[150px] rounded-full '.($following ? 'border border-purple-500/50 bg-purple-500/20' : 'bg-gradient-to-r from-pink-500 to-purple-500'),
    };
@endphp

<button type="button"
        aria-pressed="{{ $following ? 'true' : 'false' }}"
        aria-label="{{ ($following ? 'Following ' : 'Follow ').($label ?? 'this host') }}"
        @if($following)
            x-on:click.stop="window.dispatchEvent(new CustomEvent('confirm-unfollow', { detail: { id: '{{ $hostId }}', name: @js($label ?? 'this host') } }))"
        @else
            wire:click.stop="followHost('{{ $hostId }}')"
        @endif
        wire:loading.attr="disabled" wire:target="followHost,unfollowHost"
        @if($variant === 'icon') title="{{ $following ? 'Following' : 'Follow' }}" @endif
        {{ $attributes->class(['inline-flex min-h-[44px] items-center gap-1.5 text-xs font-semibold text-white whitespace-nowrap transition disabled:cursor-wait disabled:opacity-60', $variant === 'icon' ? 'shrink-0' : 'px-3', $classes]) }}>
    @if($variant === 'icon')
        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.5-1.632z"/>
            @if($following)
                <path stroke-linecap="round" stroke-linejoin="round" d="M17.5 4.5l1.5 1.5 3-3" transform="translate(0 2)"/>
            @else
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 3.5v5M16.5 6h5"/>
            @endif
        </svg>
    @else
        <span class="truncate">{{ $text }}</span>
    @endif
</button>
