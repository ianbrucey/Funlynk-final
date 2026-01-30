<div class="min-h-screen relative bg-slate-900">
    {{-- Full Bleed Hero Background --}}
    <div class="fixed inset-0 z-0">
        {{-- Base Layer: Heavily Blurred (for the bottom/background feel) --}}
        <div class="absolute inset-0 bg-cover bg-center blur-xl scale-110 brightness-50" 
             style="background-image: url('https://shoeboxchronicles.com/wp-content/uploads/2021/08/Jam_Session-6.jpg');">
        </div>

        {{-- Top Layer: Sharp Image (masked to fade out at the bottom) --}}
        <div class="absolute inset-0 bg-cover bg-center scale-110 brightness-75" 
             style="background-image: url('https://shoeboxchronicles.com/wp-content/uploads/2021/08/Jam_Session-6.jpg'); 
                    mask-image: linear-gradient(to bottom, black 20%, transparent 90%); 
                    -webkit-mask-image: linear-gradient(to bottom, black 20%, transparent 90%);">
        </div>

        {{-- Gradient Overlay for Contrast --}}
        <div class="absolute inset-0 bg-gradient-to-t from-slate-900 via-slate-900/90 to-slate-900/50"></div>
    </div>

    <div class="relative z-10 pb-24 lg:pb-12">
        <div class="container mx-auto lg:px-6 lg:py-12">

            {{-- Authenticated View (Placeholder) --}}
            @auth
                <div class="p-8 bg-slate-800/50 rounded-xl border border-white/10 text-center mx-4 mt-20">
                    <h2 class="text-2xl font-bold text-white mb-2">Welcome Back!</h2>
                    <p class="text-gray-400">You are a member of {{ $groupName }}.</p>
                    <button class="mt-4 px-6 py-2 bg-gradient-to-r from-amber-500 to-orange-500 rounded-lg text-white font-semibold">
                        Go to Group Chat
                    </button>
                </div>
            @endauth

            {{-- Guest / Landing View --}}
            @guest
                <div class="flex flex-col min-h-screen lg:min-h-0">
                    
                    {{-- Header / Hero Content --}}
                    <div class="px-6 pt-12 pb-8 mt-20 lg:mt-0 text-center lg:text-left">
                        
                        {{-- Group Avatar/Logo --}}
                        <div class="w-24 h-24 lg:w-32 lg:h-32 rounded-3xl bg-slate-800 border-4 border-white/10 shadow-2xl overflow-hidden mb-6 mx-auto lg:mx-0 flex items-center justify-center backdrop-blur-md bg-white/5">
                            <span class="text-5xl lg:text-6xl">🎷</span>
                        </div>

                        <div class="lg:flex lg:items-end lg:justify-between">
                            <div>
                                <h1 class="text-4xl lg:text-6xl font-black text-white mb-2 tracking-tight drop-shadow-lg">{{ $groupName }}</h1>
                                <p class="text-lg lg:text-xl text-gray-200 flex items-center justify-center lg:justify-start gap-2 font-medium drop-shadow-md">
                                    <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                    {{ $location }}
                                </p>
                            </div>
                            
                            {{-- Member Count & Tags --}}
                            <div class="mt-6 lg:mt-0 flex flex-col items-center lg:items-end gap-3">
                                <div class="flex -space-x-3">
                                    <img class="w-10 h-10 rounded-full border-2 border-slate-900" src="https://ui-avatars.com/api/?name=Miles&background=random" alt="">
                                    <img class="w-10 h-10 rounded-full border-2 border-slate-900" src="https://ui-avatars.com/api/?name=Coltrane&background=random" alt="">
                                    <img class="w-10 h-10 rounded-full border-2 border-slate-900" src="https://ui-avatars.com/api/?name=Ella&background=random" alt="">
                                    <div class="w-10 h-10 rounded-full bg-slate-800 border-2 border-slate-900 flex items-center justify-center text-xs font-bold text-white">
                                        +{{ $memberCount }}
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                     <span class="px-3 py-1 text-xs font-bold bg-white/10 backdrop-blur-md text-white rounded-full border border-white/20">Jazz</span>
                                     <span class="px-3 py-1 text-xs font-bold bg-white/10 backdrop-blur-md text-white rounded-full border border-white/20">Improv</span>
                                     <span class="px-3 py-1 text-xs font-bold bg-white/10 backdrop-blur-md text-white rounded-full border border-white/20">Live</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Main Cards Grid --}}
                    <div class="px-4 lg:px-0 grid grid-cols-1 lg:grid-cols-3 lg:gap-8 mt-4 lg:mt-12">
                        
                        {{-- Left Column --}}
                        <div class="lg:col-span-2 space-y-6">

                             {{-- About / Vibe Section (Glass) --}}
                            <div class="relative p-8 glass-panel rounded-2xl">
                                <div class="top-accent"></div>
                                <h2 class="text-2xl font-bold text-white mb-4">The Vibe</h2>
                                <div class="prose prose-invert prose-lg text-gray-200 leading-relaxed font-light">
                                    <p>
                                        The dim lights, the clinking of glasses, and the raw energy of improvisation. 
                                        We host open mic nights every Thursday. Bring your horn, your sticks, or just your vibe.
                                    </p>
                                    <p>
                                        <strong>The Rule:</strong> Listen more than you play.
                                    </p>
                                </div>
                            </div>

                             {{-- Upcoming Session (Glass + Highlight) --}}
                            <div class="relative p-6 glass-panel rounded-2xl border-l-4 border-amber-500 bg-slate-800/60">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h3 class="text-xs font-bold text-amber-400 uppercase tracking-widest mb-2">Next Jam</h3>
                                        <p class="text-2xl text-white font-bold">{{ $nextSession }}</p>
                                        <p class="text-gray-400 text-sm mt-1">Special Guest: The Trio</p>
                                    </div>
                                    <div class="text-center bg-slate-800/80 p-3 rounded-lg border border-white/10">
                                        <div class="text-2xl">🎷</div>
                                        <div class="text-xs font-bold text-gray-400 uppercase mt-1">Live<br>Music</div>
                                    </div>
                                </div>
                            </div>

                            {{-- Chat Teaser (Glass) --}}
                            <div class="relative p-6 glass-panel rounded-2xl opacity-90">
                                <h2 class="text-lg font-bold text-white mb-6 flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                                    Backstage Chatter
                                </h2>
                                
                                <div class="space-y-4 mask-bottom">
                                    <div class="flex gap-4">
                                        <img src="https://ui-avatars.com/api/?name=Miles&background=random" class="w-10 h-10 rounded-full shadow-lg">
                                        <div class="flex-1">
                                            <div class="flex items-baseline gap-2">
                                                <span class="text-sm font-bold text-amber-400">Miles</span>
                                                <span class="text-xs text-gray-500">2h ago</span>
                                            </div>
                                            <p class="text-gray-200 mt-1">Set list for Thursday? Or we just winging it?</p>
                                        </div>
                                    </div>

                                    <div class="flex gap-4">
                                        <img src="https://ui-avatars.com/api/?name=Ella&background=random" class="w-10 h-10 rounded-full shadow-lg">
                                        <div class="flex-1">
                                            <div class="flex items-baseline gap-2">
                                                <span class="text-sm font-bold text-purple-400">Ella</span>
                                                <span class="text-xs text-gray-500">1h ago</span>
                                            </div>
                                            <p class="text-gray-200 mt-1">Winging it. Always.</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mt-6 pt-4 border-t border-white/10 text-center">
                                    <p class="text-sm text-gray-400">Join 140 others in the chat</p>
                                </div>
                            </div>

                        </div>

                        {{-- Right Column (Admins & Map) --}}
                        <div class="hidden lg:block lg:col-span-1 space-y-6">
                            <div class="p-6 glass-panel rounded-2xl">
                                <h3 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-4">Curators</h3>
                                <div class="flex items-center gap-3 mb-4">
                                    <div class="w-12 h-12 rounded-full bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center font-bold text-lg shadow-lg">M</div>
                                    <div>
                                        <p class="text-white font-bold">Miles</p>
                                        <span class="text-xs text-gray-400">Trumpet</span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 rounded-full bg-gradient-to-br from-purple-500 to-pink-600 flex items-center justify-center font-bold text-lg shadow-lg">E</div>
                                    <div>
                                        <p class="text-white font-bold">Ella</p>
                                        <span class="text-xs text-gray-400">Vocals</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                {{-- Persistent Bottom Action Bar (Glass + Blur) --}}
                <div class="fixed bottom-0 left-0 right-0 p-4 lg:p-6 bg-slate-900/40 backdrop-blur-xl border-t border-white/10 z-50">
                    <div class="container mx-auto max-w-4xl flex items-center justify-between gap-4">
                        <div class="hidden lg:block">
                            <p class="text-white font-bold text-lg">Vibe with us?</p>
                            <p class="text-sm text-gray-400">Join the group to get on the guest list.</p>
                        </div>
                        <button class="w-full lg:w-auto px-8 py-4 bg-gradient-to-r from-amber-500 to-orange-600 rounded-xl text-white font-bold text-lg shadow-lg shadow-amber-500/30 hover:scale-105 transition-transform flex items-center justify-center gap-2">
                             <span>🎺</span> Join Session
                        </button>
                    </div>
                </div>

            @endguest
        </div>
    </div>

    <style>
        .glass-panel {
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        .top-accent {
            position: absolute;
            top: 0;
            left: 0;
            width: 6rem;
            height: 0.15rem;
            background: linear-gradient(to right, #f59e0b, #ea580c, transparent);
            border-radius: 9999px;
        }
        .mask-bottom {
            mask-image: linear-gradient(to bottom, black 50%, transparent 100%);
            -webkit-mask-image: linear-gradient(to bottom, black 50%, transparent 100%);
        }
    </style>
</div>
