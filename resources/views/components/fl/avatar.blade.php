@props(['name' => '', 'src' => null, 'disk' => null])

@php
    $gradients = [
        'from-pink-500 to-purple-600',
        'from-cyan-500 to-blue-600',
        'from-emerald-500 to-teal-600',
        'from-amber-500 to-orange-600',
        'from-fuchsia-500 to-indigo-600',
        'from-rose-500 to-red-600',
    ];
    $label = trim((string) $name) !== '' ? (string) $name : '?';
    $gradient = $gradients[abs(crc32(mb_strtolower($label))) % count($gradients)];
    $words = preg_split('/\s+/u', $label, -1, PREG_SPLIT_NO_EMPTY);
    $initials = mb_strtoupper(mb_substr($words[0], 0, 1).(count($words) > 1 ? mb_substr(end($words), 0, 1) : ''));
    $url = $src ? (\Illuminate\Support\Str::startsWith($src, ['http://', 'https://', '/']) ? $src : \App\Services\ImageProcessor::url($src)) : null;
@endphp

@if($url)
    <img src="{{ $url }}" alt="{{ $label }}" loading="lazy" {{ $attributes->class(['rounded-full object-cover']) }}>
@else
    <div role="img" aria-label="{{ $label }}" {{ $attributes->class(['rounded-full bg-gradient-to-br text-white font-semibold flex items-center justify-center select-none', $gradient]) }}>
        <span style="font-size: 40%">{{ $initials }}</span>
    </div>
@endif
