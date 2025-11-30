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

    <style>
        body {
            font-family: 'Inter', sans-serif;
            /* background: linear-gradient(to bottom right, #0a0a1a, #0f1729, #0a0a1a); */
            background: linear-gradient(to bottom right, #0a0a1a,rgb(44, 64, 112), #0a0a1a);
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

        /* .aurora-layer-1 {
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
        } */

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
            /* background: rgba(33, 72, 164, 0.5); */
            backdrop-filter: blur(20px);
            border: 1px solid rgba(59, 130, 246, 0.3);
        }

        /* Responsive border radius for glass cards */
        @media (min-width: 1024px) {
            /* .glass-card {
                border-radius: 1.5rem;
            } */
        }

        .gradient-border {
            position: relative;
            padding: 0.125rem;
            background: linear-gradient(to right, #ec4899, #8b5cf6, #06b6d4);
            border-radius: 1rem;
            /* animation: pulse 2s infinite; */
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

    <!-- Toast Notifications -->
    <x-toast-notification />

    @livewireScripts

    {{-- Global Toast Notifications --}}
    

    @if (session()->has('error'))
        <div id="toast-error" class="fixed top-24 right-4 z-50 glass-card border border-red-500/30 rounded-xl p-4 animate-slide-in max-w-sm shadow-2xl">
            <div class="flex items-center gap-3">
                <div class="text-2xl">❌</div>
                <p class="text-white font-medium">{{ session('error') }}</p>
                <button onclick="document.getElementById('toast-error').remove()" class="ml-auto text-gray-400 hover:text-white transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
        <script>
            setTimeout(() => {
                const toast = document.getElementById('toast-error');
                if (toast) {
                    toast.style.opacity = '0';
                    toast.style.transform = 'translateX(100%)';
                    toast.style.transition = 'all 0.3s ease-out';
                    setTimeout(() => toast.remove(), 300);
                }
            }, 3000);
        </script>
    @endif
</body>
</html>

