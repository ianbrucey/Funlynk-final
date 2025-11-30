<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="user-id" content="{{ auth()->id() }}">
        <title>{{ $title ?? config('app.name') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @filamentStyles
        @livewireStyles

        <link rel="stylesheet" href="{{ asset('css/galaxy-theme.css') }}">

        {{ $head ?? '' }}
    </head>
    <body class="font-sans antialiased">
        <!-- Aurora Borealis Layers -->
        <div class="aurora aurora-layer-1"></div>
        <div class="aurora aurora-layer-2"></div>
        <div class="aurora aurora-layer-3"></div>

        <!-- Animated Background Stars -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            @for($i = 0; $i < 150; $i++)
            <div class="star" style="left: {{ rand(0, 100) }}%; top: {{ rand(0, 100) }}%; animation-delay: {{ rand(0, 3000) }}ms; opacity: {{ rand(20, 80) / 100 }};"></div>
            @endfor
        </div>

        <!-- Tech Grid Background -->
        <div class="tech-grid"></div>

        <!-- Content -->
        <div class="relative z-10">
            {{ $slot }}
        </div>

        <!-- Global Modals -->
        <livewire:posts.invite-friends-modal />
        <livewire:modals.convert-post-modal />

        @livewireScripts
        @filamentScripts
        {{ $scripts ?? '' }}
    </body>
</html>

