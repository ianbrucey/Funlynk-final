<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FunLynk - Claude Style</title>
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <style>
        body {
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

       

        .gradient-border {
            position: relative;
            padding: 0.125rem;
            background: linear-gradient(to right, #ec4899, #8b5cf6, #06b6d4);
            border-radius: 1rem;
            animation: pulse 2s infinite;
        }

        .gradient-text {
            background: linear-gradient(to right, #fbbf24, #06b6d4);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .activity-card {
            transition: all 0.3s ease;
        }

        .activity-card:hover {
            transform: scale(1.1);
            box-shadow: 0 0 30px rgba(139, 92, 246, 0.5);
        }

        .crew-avatar {
            transition: all 0.3s ease;
        }

        .crew-avatar:hover {
            transform: scale(1.1);
            box-shadow: 0 0 30px rgba(59, 130, 246, 0.5);
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

    <div class="relative z-10 p-6 max-w-7xl mx-auto">
        <!-- Header -->
        <header class="relative mb-8 p-6 glass-card">
            <div class="top-accent"></div>
            <div class="flex items-center justify-between">
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

                <!-- Navigation -->
                <nav class="flex gap-8">
                    @foreach(['Feed', 'Find Activities', 'Community'] as $tab)
                    <button class="text-gray-300 hover:text-white transition relative group">
                        {{ $tab }}
                        <div class="absolute -bottom-2 left-0 right-0 h-0.5 bg-gradient-to-r from-pink-500 to-purple-500 scale-x-0 group-hover:scale-x-100 transition-transform"></div>
                    </button>
                    @endforeach
                </nav>

                <!-- Profile -->
                <div class="flex items-center gap-4">
                    <button class="relative p-2 hover:bg-white/10 rounded-xl transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                        <span class="absolute top-1 right-1 w-2 h-2 bg-pink-500 rounded-full animate-pulse"></span>
                    </button>
                    <div class="w-12 h-12 rounded-full bg-gradient-to-br from-purple-500 to-pink-500 p-0.5">
                        <div class="w-full h-full bg-slate-800 rounded-full flex items-center justify-center text-lg font-bold">
                            U
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Trending Activities Panel -->
            <div class="relative p-8 glass-card overflow-hidden">
                <div class="top-accent-center"></div>
                
                <h2 class="text-2xl font-bold mb-8 flex items-center gap-2">
                    Trending Activities
                    <svg class="w-6 h-6 text-yellow-400 animate-pulse" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M13 2L3 14h8l-1 8 10-12h-8l1-8z"/>
                    </svg>
                </h2>

                <!-- Activity Icons -->
                <div class="flex gap-4 mb-8 flex-wrap">
                    @php
                    $activities = [
                        ['icon' => 'M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3', 'color' => 'border-pink-500 bg-pink-500/10'],
                        ['icon' => 'M13 10V3L4 14h7v7l9-11h-7z', 'color' => 'border-yellow-500 bg-yellow-500/10'],
                        ['icon' => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253', 'color' => 'border-cyan-500 bg-cyan-500/10'],
                        ['icon' => 'M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z', 'color' => 'border-purple-500 bg-purple-500/10'],
                        ['icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z', 'color' => 'border-green-500 bg-green-500/10'],
                    ];
                    @endphp

                    @foreach($activities as $activity)
                    <button class="activity-card relative w-20 h-20 rounded-2xl border-2 {{ $activity['color'] }} backdrop-blur-sm flex items-center justify-center">
                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $activity['icon'] }}"/>
                        </svg>
                    </button>
                    @endforeach
                </div>

                <!-- Trending Events -->
                <div class="space-y-3 mt-12">
                    @php
                    $events = [
                        ['title' => 'Weekend Gaming Marathon', 'members' => 24, 'time' => 'Sat 8PM', 'category' => 'Gaming', 'hot' => true],
                        ['title' => 'Jazz Night Live', 'members' => 18, 'time' => 'Fri 9PM', 'category' => 'Music', 'hot' => true],
                        ['title' => 'Book Club: Sci-Fi Edition', 'members' => 12, 'time' => 'Sun 3PM', 'category' => 'Reading', 'hot' => false],
                    ];
                    @endphp

                    @foreach($events as $event)
                    <div class="relative p-4 rounded-2xl bg-slate-800/50 border border-white/10 hover:border-purple-500/50 transition-all group cursor-pointer">
                        @if($event['hot'])
                        <div class="absolute -top-2 -right-2 px-3 py-1 bg-gradient-to-r from-orange-500 to-pink-500 rounded-full text-xs font-bold flex items-center gap-1 animate-pulse">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                            </svg>
                            HOT
                        </div>
                        @endif
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="font-semibold mb-1 group-hover:text-cyan-400 transition">{{ $event['title'] }}</h4>
                                <div class="flex items-center gap-3 text-xs text-gray-400">
                                    <span class="px-2 py-1 bg-purple-500/20 text-purple-300 rounded-lg">{{ $event['category'] }}</span>
                                    <span>{{ $event['members'] }} joined</span>
                                    <span>{{ $event['time'] }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Find Your Crew Panel -->
            <div class="relative p-8 glass-card">
                <div class="top-accent-center"></div>
                
                <h2 class="text-2xl font-bold mb-6">Find Your Crew</h2>

                <!-- Search -->
                <div class="relative mb-8">
                    <svg class="absolute left-4 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" placeholder="Search by Interest..." class="w-full pl-12 pr-4 py-3 bg-slate-800/50 border border-white/10 rounded-2xl focus:border-cyan-500/50 focus:outline-none transition text-white"/>
                </div>

                <!-- Crew Grid -->
                <div class="grid grid-cols-3 gap-4">
                    @php
                    $crew = [
                        ['name' => 'Alex', 'color' => 'border-cyan-500', 'online' => true],
                        ['name' => 'Sarah', 'color' => 'border-green-500', 'online' => true],
                        ['name' => 'Mike', 'color' => 'border-cyan-500', 'online' => true],
                        ['name' => 'Emma', 'color' => 'border-pink-500', 'online' => false],
                        ['name' => 'John', 'color' => 'border-green-500', 'online' => true],
                        ['name' => 'Lisa', 'color' => 'border-yellow-500', 'online' => true],
                        ['name' => 'David', 'color' => 'border-purple-500', 'online' => true],
                        ['name' => 'Nina', 'color' => 'border-yellow-500', 'online' => true],
                        ['name' => 'Tom', 'color' => 'border-pink-500', 'online' => false],
                    ];
                    @endphp

                    @foreach($crew as $member)
                    <div class="relative group cursor-pointer">
                        <div class="crew-avatar w-20 h-20 rounded-full border-3 {{ $member['color'] }} p-0.5 mx-auto">
                            <div class="w-full h-full bg-slate-800 rounded-full flex items-center justify-center text-lg font-bold">
                                {{ substr($member['name'], 0, 1) }}
                            </div>
                        </div>
                        @if($member['online'])
                        <div class="absolute top-0 right-6 w-4 h-4 bg-green-500 rounded-full border-2 border-slate-900 animate-pulse"></div>
                        @endif
                        <p class="text-center text-xs mt-2 opacity-0 group-hover:opacity-100 transition">{{ $member['name'] }}</p>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Floating Action Button -->
        <button class="fixed bottom-8 right-8 w-16 h-16 bg-gradient-to-br from-pink-500 via-purple-500 to-cyan-500 rounded-full shadow-2xl hover:scale-110 transition-all duration-300 flex items-center justify-center group">
            <svg class="w-8 h-8 text-white group-hover:rotate-90 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
        </button>
    </div>

    <!-- Corner Tech Details -->
    <div class="absolute bottom-4 right-4 text-xs font-mono text-gray-600 pointer-events-none">
        <div class="flex items-center gap-2">
            <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
            <span>SYSTEM ONLINE</span>
        </div>
    </div>

</body>
</html>

