@props(['hostId', 'name' => '', 'following' => false, 'variant' => 'pill'])

@php
    $label = $name !== '' ? $name : null;
    $text = $following ? '✓ Following'.($label ? " {$label}" : '') : '+ Follow'.($label ? " {$label}" : '');
    $classes = match (true) {
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
        {{ $attributes->class(['inline-flex min-h-[44px] items-center gap-1.5 px-3 text-xs font-semibold text-white whitespace-nowrap transition disabled:cursor-wait disabled:opacity-60', $classes]) }}>
    <span class="truncate">{{ $text }}</span>
</button>
