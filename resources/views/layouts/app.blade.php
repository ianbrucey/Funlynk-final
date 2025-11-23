<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name', 'FunLynk') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />

    <!-- Styles / Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(to bottom right, #0a0a1a, #0f1729, #0a0a1a);
            min-height: 100vh;
            color: white;
            position: relative;
            overflow-x: hidden;
        }

        /* Aurora borealis effect */
        @keyframes aurora {
            0%, 100% {
                opacity: 0.3;
                transform: translateY(0) scale(1);
            }
            50% {
                opacity: 0.6;
                transform: translateY(-20px) scale(1.1);
            }
        }

        .aurora {
            position: absolute;
            width: 100%;
            height: 100%;
            pointer-events: none;
            opacity: 0.4;
        }

        .aurora-layer-1 {
            background: radial-gradient(ellipse at 30% 20%, rgba(16, 185, 129, 0.18) 0%, transparent 50%);
            animation: aurora 8s ease-in-out infinite;
        }

        .aurora-layer-2 {
            background: radial-gradient(ellipse at 70% 40%, rgba(59, 130, 246, 0.2) 0%, transparent 50%);
            animation: aurora 10s ease-in-out infinite 2s;
        }

        .aurora-layer-3 {
            background: radial-gradient(ellipse at 50% 60%, rgba(139, 92, 246, 0.15) 0%, transparent 50%);
            animation: aurora 12s ease-in-out infinite 4s;
        }

        /* Animated stars */
        @keyframes twinkle {
            0%, 100% { opacity: 0.2; }
            50% { opacity: 0.8; }
        }

        .star {
            position: absolute;
            width: 2px;
            height: 2px;
            background: white;
            border-radius: 50%;
            animation: twinkle 3s infinite;
        }

        /* Tech grid background */
        .tech-grid {
            position: absolute;
            inset: 0;
            opacity: 0.05;
            pointer-events: none;
            background-image:
                linear-gradient(rgba(99, 102, 241, 0.08) 1px, transparent 1px),
                linear-gradient(90deg, rgba(99, 102, 241, 0.08) 1px, transparent 1px);
            background-size: 50px 50px;
        }

        .glass-card {
            background: rgba(15, 23, 42, 0.5);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(59, 130, 246, 0.3);
            border-radius: 1.5rem;
        }

        .gradient-border {
            position: relative;
            padding: 0.125rem;
            background: linear-gradient(to right, #ec4899, #8b5cf6, #06b6d4);
            border-radius: 1rem;
            animation: pulse 2s infinite;
        }

        .top-accent {
            position: absolute;
            top: 0;
            left: 0;
            width: 8rem;
            height: 0.25rem;
            background: linear-gradient(to right, #ec4899, #8b5cf6, transparent);
            border-radius: 9999px;
        }

        .top-accent-center {
            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 8rem;
            height: 0.25rem;
            background: linear-gradient(to right, transparent, #ec4899, transparent);
        }
    </style>
</head>
<body class="font-sans antialiased">
    <!-- Aurora Layers -->
    <div class="aurora aurora-layer-1"></div>
    <div class="aurora aurora-layer-2"></div>
    <div class="aurora aurora-layer-3"></div>

    <!-- Stars -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        @for($i = 0; $i < 150; $i++)
        <div class="star" style="left: {{ rand(0, 100) }}%; top: {{ rand(0, 100) }}%; animation-delay: {{ rand(0, 3000) }}ms; opacity: {{ rand(20, 80) / 100 }};"></div>
        @endfor
    </div>

    <!-- Tech Grid -->
    <div class="tech-grid"></div>

    <!-- Content -->
    <div class="relative z-10">
        <!-- Navigation -->
        <x-navbar />

        <!-- Page Content -->
        <main>
            {{ $slot }}
        </main>
    </div>

    @livewireScripts
</body>
</html>

