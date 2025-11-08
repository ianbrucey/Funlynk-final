<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title ?? config('app.name') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @filamentStyles
        @livewireStyles

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

            .top-accent-center {
                position: absolute;
                top: 0;
                left: 50%;
                transform: translateX(-50%);
                width: 8rem;
                height: 0.25rem;
                background: linear-gradient(to right, transparent, #ec4899, transparent);
            }

            /* Filament form styling */
            .fi-input-wrp {
                background: rgba(30, 41, 59, 0.5) !important;
                border-color: rgba(255, 255, 255, 0.1) !important;
                border-radius: 1rem !important;
                transition: all 0.3s ease !important;
                min-height: 3rem !important;
            }

            .fi-input-wrp:focus-within {
                border-color: rgba(6, 182, 212, 0.5) !important;
                box-shadow: 0 0 0 3px rgba(6, 182, 212, 0.1) !important;
            }

            .fi-input {
                color: white !important;
                padding: 0.75rem 1rem !important;
                font-size: 1rem !important;
                line-height: 1.5rem !important;
            }

            .fi-fo-field-wrp-label {
                color: #d1d5db !important;
                font-weight: 500 !important;
                margin-bottom: 0.5rem !important;
            }

            .fi-select {
                background: rgba(30, 41, 59, 0.5) !important;
                border-color: rgba(255, 255, 255, 0.1) !important;
                border-radius: 1rem !important;
                color: white !important;
                padding: 0.75rem 1rem !important;
                transition: all 0.3s ease !important;
                min-height: 3rem !important;
                font-size: 1rem !important;
                line-height: 1.5rem !important;
            }

            .fi-select:focus {
                border-color: rgba(6, 182, 212, 0.5) !important;
                box-shadow: 0 0 0 3px rgba(6, 182, 212, 0.1) !important;
                outline: none !important;
            }

            .fi-textarea {
                background: rgba(30, 41, 59, 0.5) !important;
                border-color: rgba(255, 255, 255, 0.1) !important;
                border-radius: 1rem !important;
                color: white !important;
                padding: 0.75rem 1rem !important;
                transition: all 0.3s ease !important;
                font-size: 1rem !important;
                line-height: 1.5rem !important;
            }

            .fi-textarea:focus {
                border-color: rgba(6, 182, 212, 0.5) !important;
                box-shadow: 0 0 0 3px rgba(6, 182, 212, 0.1) !important;
                outline: none !important;
            }

            .fi-checkbox-wrp {
                background: rgba(30, 41, 59, 0.5) !important;
                border-color: rgba(255, 255, 255, 0.1) !important;
                border-radius: 0.5rem !important;
            }

            .fi-checkbox-wrp:checked {
                background: linear-gradient(to right, #ec4899, #8b5cf6) !important;
                border-color: transparent !important;
            }

            .fi-toggle-wrp {
                background: rgba(30, 41, 59, 0.5) !important;
                border-radius: 9999px !important;
            }

            .fi-toggle-wrp:checked {
                background: linear-gradient(to right, #ec4899, #8b5cf6) !important;
            }

            /* Placeholder styling */
            .fi-input::placeholder,
            .fi-textarea::placeholder {
                color: rgba(156, 163, 175, 0.5) !important;
            }

            /* Remove default focus rings */
            .fi-input:focus,
            .fi-select:focus,
            .fi-textarea:focus {
                outline: none !important;
            }

            /* Field wrapper spacing */
            .fi-fo-field-wrp {
                margin-bottom: 1.25rem !important;
            }

            /* Help text styling */
            .fi-fo-field-wrp-hint {
                color: #9ca3af !important;
                font-size: 0.875rem !important;
                margin-top: 0.25rem !important;
            }

            /* Error message styling */
            .fi-fo-field-wrp-error-message {
                color: #f87171 !important;
                font-size: 0.875rem !important;
                margin-top: 0.25rem !important;
            }

            /* Password reveal button styling */
            .fi-input-wrp-suffix button {
                color: #9ca3af !important;
                transition: color 0.3s ease !important;
            }

            .fi-input-wrp-suffix button:hover {
                color: #06b6d4 !important;
            }

            /* Ensure all input wrappers have consistent height */
            .fi-input-wrp,
            .fi-select-wrp,
            .fi-textarea-wrp {
                display: flex !important;
                align-items: center !important;
            }

            /* Grid layout for form fields */
            .fi-fo-component-ctn {
                width: 100% !important;
            }
        </style>
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

        <div class="relative z-10">
            {{ $slot }}
        </div>

        @livewireScripts
        @filamentScripts
    </body>
</html>
