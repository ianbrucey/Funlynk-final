@props(['post', 'threshold' => 'soft'])

@php
$badgeClasses = $threshold === 'strong'
    ? 'bg-gradient-to-r from-pink-500 to-purple-500 animate-pulse'
    : 'bg-gradient-to-r from-amber-500 to-orange-500';
@endphp

@if($post->isEligibleForConversion() && !$post->hasReachedDismissLimit())
    <div class="absolute top-2 right-2 z-10">
        <button
            wire:click.stop="openConversionModal('{{ $post->id }}')"
            class="{{ $badgeClasses }} px-3 py-1 rounded-full text-xs font-bold text-white shadow-lg hover:scale-110 transition-all"
            title="{{ $threshold === 'strong' ? 'Convert to Event Now!' : 'Ready to Convert' }}">
            @if($threshold === 'strong')
                🔥 Convert Now!
            @else
                ⭐ Ready
            @endif
        </button>
    </div>
@endif