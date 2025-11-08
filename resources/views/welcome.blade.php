<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />

        <!-- Styles / Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

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
        </style>
    </head>
    <body class="font-sans">
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

        <div class="relative z-10">
            <!-- Navbar -->
            <header class="relative p-6 glass-card mx-6 mt-6">
                <div class="top-accent"></div>
                <div class="flex items-center justify-between max-w-7xl mx-auto">
                    <div class="flex items-center gap-4">
                        <div class="relative">
                            <div class="gradient-border">
                                <div class="w-16 h-16 bg-slate-900 rounded-2xl flex items-center justify-center">
                                    <img src="{{ asset('images/fl-logo-icon-only.png') }}" alt="FL" class="h-12 w-auto">
                                </div>
                            </div>
                            <div class="absolute -top-1 -right-1 w-4 h-4 bg-green-500 rounded-full border-2 border-slate-900 animate-pulse"></div>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold">
                                <span class="text-yellow-400">Fun</span><span class="text-cyan-400">Lynk</span>
                            </h1>
                            <p class="text-xs text-gray-400 font-mono">SOCIAL ACTIVITY NETWORK</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        @auth
                            <a href="{{ url('/dashboard') }}" class="px-6 py-3 bg-gradient-to-r from-pink-500 to-purple-500 rounded-xl font-semibold hover:scale-105 transition-all">
                                Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="px-6 py-3 bg-slate-800/50 border border-white/10 rounded-xl hover:border-cyan-500/50 transition text-gray-300 hover:text-white">
                                Log in
                            </a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="px-6 py-3 bg-gradient-to-r from-pink-500 to-purple-500 rounded-xl font-semibold hover:scale-105 transition-all">
                                    Get Started
                                </a>
                            @endif
                        @endauth
                    </div>
                </div>
            </header>

            <!-- Hero Section -->
            <div class="container mx-auto px-6 py-20">
                <div class="flex flex-col lg:flex-row-reverse items-center gap-12 max-w-7xl mx-auto">
                    <div class="lg:w-1/2">
                        <img src="{{ asset('images/fl-logo-main.png') }}" alt="Funlynk" class="w-full max-w-md mx-auto">
                    </div>
                    <div class="lg:w-1/2">
                        <h2 class="text-5xl font-bold mb-6">Discover Activities Around You</h2>
                        <p class="text-lg text-gray-300 mb-8">
                            Connect with your community through spontaneous activities. From pickup basketball to music jam sessions,
                            find and join local events that match your interests.
                        </p>
                        <div class="flex gap-4">
                            @auth
                                <a href="{{ url('/dashboard') }}" class="px-8 py-4 bg-gradient-to-r from-pink-500 to-purple-500 rounded-xl font-semibold text-lg hover:scale-105 transition-all">
                                    Go to Dashboard
                                </a>
                            @else
                                <a href="{{ route('register') }}" class="px-8 py-4 bg-gradient-to-r from-pink-500 to-purple-500 rounded-xl font-semibold text-lg hover:scale-105 transition-all">
                                    Get Started
                                </a>
                                <a href="{{ route('login') }}" class="px-8 py-4 bg-slate-800/50 border border-white/10 rounded-xl text-lg hover:border-cyan-500/50 transition">
                                    Sign In
                                </a>
                            @endauth
                        </div>
                    </div>
                </div>
            </div>

            <!-- Features Section -->
            <div class="container mx-auto px-6 py-20">
                <h2 class="text-4xl font-bold text-center mb-12">Why Funlynk?</h2>
                <div class="grid md:grid-cols-3 gap-8 max-w-7xl mx-auto">
                    <div class="relative p-8 glass-card text-center">
                        <div class="text-5xl mb-4">🗺️</div>
                        <h3 class="text-xl font-bold mb-3">Map-Based Discovery</h3>
                        <p class="text-gray-300">Find activities happening near you in real-time with our interactive map.</p>
                    </div>
                    <div class="relative p-8 glass-card text-center">
                        <div class="text-5xl mb-4">👥</div>
                        <h3 class="text-xl font-bold mb-3">Build Your Network</h3>
                        <p class="text-gray-300">Follow hosts and friends to stay updated on activities that matter to you.</p>
                    </div>
                    <div class="relative p-8 glass-card text-center">
                        <div class="text-5xl mb-4">🎟️</div>
                        <h3 class="text-xl font-bold mb-3">Free & Paid Events</h3>
                        <p class="text-gray-300">Host free activities or sell tickets with integrated payment processing.</p>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <footer class="container mx-auto px-6 py-10 text-center">
                <div class="flex flex-col items-center gap-4">
                    <img src="{{ asset('images/fl-logo-main.png') }}" alt="Funlynk" class="h-12 w-auto">
                    <p class="font-bold text-lg">
                        <span class="text-yellow-400">Fun</span><span class="text-cyan-400">Lynk</span>
                    </p>
                    <p class="text-gray-400">Connecting communities through activities</p>
                    <p class="text-gray-600 text-sm">Copyright © {{ date('Y') }} - All rights reserved</p>
                </div>
            </footer>
        </div>
    </body>
</html>

