<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Demo - FunLynk</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-slate-950 min-h-screen">
    {{-- Galaxy Background --}}
    <div class="fixed inset-0 overflow-hidden pointer-events-none">
        <div class="absolute inset-0 bg-gradient-to-br from-purple-900/20 via-slate-950 to-cyan-900/20"></div>
        <div class="aurora-layer absolute inset-0 opacity-30"></div>
    </div>

    <div class="relative z-10 container mx-auto px-6 py-8">
        <div class="max-w-4xl mx-auto">
            <h1 class="text-3xl font-bold text-white mb-6">Chat Component Demo</h1>
            
            <div class="h-[600px]">
                <livewire:chat.chat-component />
            </div>
        </div>
    </div>

    @livewireScripts
</body>
</html>
