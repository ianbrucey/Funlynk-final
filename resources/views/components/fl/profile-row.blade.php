@props([
    'href',
    'title',
    'meta' => '',
    'badge' => null,
    'tone' => 'muted',
    'tags' => [],
    'image' => null,
    'icon' => '💬',
])

@php
    $tones = [
        'green' => 'text-green-300 border-green-500/40 bg-green-500/10',
        'cyan' => 'text-cyan-300 border-cyan-500/40 bg-cyan-500/10',
        'purple' => 'text-purple-300 border-purple-500/40 bg-purple-500/10',
        'amber' => 'text-amber-300 border-amber-400/30 bg-amber-400/10',
        'muted' => 'text-gray-400 border-gray-400/30 bg-gray-400/10',
    ];
    $badgeClass = 'text-[0.65rem] font-bold uppercase tracking-wide px-2 py-0.5 rounded-md border ';
@endphp

<div {{ $attributes->class(['flex items-stretch gap-1 min-h-[76px] rounded-2xl bg-white/5 border border-white/10 hover:border-cyan-400/40 hover:-translate-y-px transition']) }}>
    <a href="{{ $href }}" wire:navigate class="flex flex-1 min-w-0 items-center gap-3 p-2.5">
        @if($image)
            <img src="{{ \App\Services\ImageProcessor::url($image) }}" alt="" loading="lazy" class="h-[60px] w-[60px] flex-none rounded-[10px] object-cover">
        @else
            <div class="h-[60px] w-[60px] flex-none rounded-[10px] bg-gradient-to-br from-pink-500/70 to-purple-600/70 grid place-items-center text-2xl" aria-hidden="true">{{ $icon }}</div>
        @endif
        <div class="min-w-0 flex-1">
            <h4 class="truncate text-sm font-semibold text-white">{{ $title }}</h4>
            @if($meta !== '')
                <p class="mt-0.5 truncate text-xs text-gray-400">{{ $meta }}</p>
            @endif
            <div class="mt-1 flex flex-wrap items-center gap-1.5">
                @if($badge)
                    <span class="{{ $badgeClass.($tones[$tone] ?? $tones['muted']) }}">{{ $badge }}</span>
                @endif
                @foreach($tags as $tag)
                    <span class="{{ $badgeClass.($tones[$tag['tone'] ?? 'muted'] ?? $tones['muted']) }}">{{ $tag['label'] }}</span>
                @endforeach
            </div>
        </div>
    </a>
    {{ $slot }}
</div>
