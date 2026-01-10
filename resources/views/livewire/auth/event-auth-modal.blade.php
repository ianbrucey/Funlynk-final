<div>
    {{-- Modal Backdrop --}}
    @if($show)
    <div 
        class="fixed inset-0 bg-black/80 backdrop-blur-sm flex items-center justify-center z-50 p-4"
        wire:click.self="closeModal"
        x-data
        x-init="$el.focus()"
        @keydown.escape.window="$wire.closeModal()"
    >
        {{-- Modal Content --}}
        <div class="relative w-full max-w-md bg-slate-900/95 border border-white/10 rounded-2xl shadow-2xl overflow-hidden">
            
            {{-- Accent Line --}}
            <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-pink-500 via-purple-500 to-cyan-500"></div>
            
            {{-- Close Button --}}
            <button 
                wire:click="closeModal"
                class="absolute top-4 right-4 p-2 text-gray-400 hover:text-white transition z-10"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>

            {{-- Header --}}
            <div class="p-6 pb-4">
                <div class="flex items-center gap-3 mb-2">
                    <span class="text-3xl">🎟️</span>
                    <h2 class="text-2xl font-bold text-white">Get Your Ticket</h2>
                </div>
                @if($activity)
                    <p class="text-gray-400 text-sm">{{ $activity->title }}</p>
                @endif
            </div>

            {{-- Tab Switcher --}}
            <div class="px-6">
                <div class="flex bg-slate-800/50 rounded-xl p-1 border border-white/5">
                    <button 
                        wire:click="switchMode('quick-join')"
                        class="flex-1 py-2.5 px-4 rounded-lg text-sm font-semibold transition-all {{ $mode === 'quick-join' ? 'bg-gradient-to-r from-pink-500 to-purple-500 text-white shadow-lg' : 'text-gray-400 hover:text-white' }}"
                    >
                        Quick Join
                    </button>
                    <button 
                        wire:click="switchMode('sign-in')"
                        class="flex-1 py-2.5 px-4 rounded-lg text-sm font-semibold transition-all {{ $mode === 'sign-in' ? 'bg-gradient-to-r from-pink-500 to-purple-500 text-white shadow-lg' : 'text-gray-400 hover:text-white' }}"
                    >
                        Sign In
                    </button>
                </div>
            </div>

            {{-- Form Content --}}
            <div class="p-6">
                {{-- Error Message --}}
                @if($errorMessage)
                    <div class="mb-4 p-3 bg-red-500/20 border border-red-500/50 rounded-xl text-red-300 text-sm">
                        {{ $errorMessage }}
                    </div>
                @endif

                @if($mode === 'quick-join')
                    {{-- Quick Join Form --}}
                    <form wire:submit="quickJoin" class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-300 mb-2">Email address</label>
                            <input 
                                type="email" 
                                wire:model="email"
                                placeholder="your@email.com"
                                class="w-full px-4 py-3 bg-slate-800/50 border border-white/10 rounded-xl focus:border-cyan-500/50 focus:outline-none focus:ring-2 focus:ring-cyan-500/20 transition text-white placeholder-gray-500"
                                required
                            >
                            @error('email') <span class="text-red-400 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-300 mb-2">Create a password</label>
                            <input 
                                type="password" 
                                wire:model="password"
                                placeholder="••••••••"
                                class="w-full px-4 py-3 bg-slate-800/50 border border-white/10 rounded-xl focus:border-cyan-500/50 focus:outline-none focus:ring-2 focus:ring-cyan-500/20 transition text-white placeholder-gray-500"
                                required
                            >
                            @error('password') <span class="text-red-400 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>

                        {{-- Optional Profile Photo --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-300 mb-2">
                                Profile photo <span class="text-gray-500 font-normal">(optional)</span>
                            </label>
                            <div class="flex items-center gap-4">
                                @if($profilePhoto)
                                    <img src="{{ $profilePhoto->temporaryUrl() }}" class="w-14 h-14 rounded-full object-cover border-2 border-purple-500">
                                @else
                                    <div class="w-14 h-14 rounded-full bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center text-white text-xl font-bold">
                                        {{ $email ? strtoupper(substr($email, 0, 1)) : '?' }}
                                    </div>
                                @endif
                                <label class="flex-1 cursor-pointer">
                                    <div class="px-4 py-2 bg-slate-800/50 border border-white/10 rounded-xl text-center text-sm text-gray-300 hover:border-cyan-500/50 transition">
                                        {{ $profilePhoto ? 'Change photo' : 'Add photo' }}
                                    </div>
                                    <input type="file" wire:model="profilePhoto" accept="image/*" class="hidden">
                                </label>
                            </div>
                            @error('profilePhoto') <span class="text-red-400 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>

                        {{-- Submit Button --}}
                        <button 
                            type="submit"
                            class="w-full py-4 bg-gradient-to-r from-pink-500 to-purple-500 rounded-xl font-bold text-lg hover:scale-[1.02] transition-all shadow-lg disabled:opacity-50 disabled:cursor-not-allowed"
                            wire:loading.attr="disabled"
                        >
                            <span wire:loading.remove wire:target="quickJoin">
                                🎟️ Join & Get Ticket
                            </span>
                            <span wire:loading wire:target="quickJoin" class="flex items-center justify-center gap-2">
                                <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                                </svg>
                                Creating account...
                            </span>
                        </button>

                        <p class="text-xs text-gray-500 text-center">
                            By joining, you agree to our <a href="#" class="text-cyan-400 hover:underline">Terms of Service</a>
                        </p>

                        {{-- OR Divider --}}
                        <div class="relative my-4">
                            <div class="absolute inset-0 flex items-center">
                                <div class="w-full border-t border-white/10"></div>
                            </div>
                            <div class="relative flex justify-center text-xs">
                                <span class="px-2 bg-slate-900/95 text-gray-400">Or</span>
                            </div>
                        </div>

                        {{-- Google OAuth Button --}}
                        <a href="{{ route('social.redirect.event', ['provider' => 'google', 'activity' => $activity->id]) }}" 
                           class="flex items-center justify-center gap-3 w-full px-4 py-3.5 bg-white text-gray-700 rounded-xl font-semibold hover:bg-gray-100 transition-all shadow-md">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                                <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                                <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                                <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                            </svg>
                            <span>Continue with Google</span>
                        </a>
                    </form>

                @else
                    {{-- Sign In Form --}}
                    <form wire:submit="signIn" class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-300 mb-2">Email address</label>
                            <input 
                                type="email" 
                                wire:model="email"
                                placeholder="your@email.com"
                                class="w-full px-4 py-3 bg-slate-800/50 border border-white/10 rounded-xl focus:border-cyan-500/50 focus:outline-none focus:ring-2 focus:ring-cyan-500/20 transition text-white placeholder-gray-500"
                                required
                            >
                            @error('email') <span class="text-red-400 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-300 mb-2">Password</label>
                            <input 
                                type="password" 
                                wire:model="password"
                                placeholder="••••••••"
                                class="w-full px-4 py-3 bg-slate-800/50 border border-white/10 rounded-xl focus:border-cyan-500/50 focus:outline-none focus:ring-2 focus:ring-cyan-500/20 transition text-white placeholder-gray-500"
                                required
                            >
                            @error('password') <span class="text-red-400 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div class="text-right">
                            <a href="{{ route('password.request') }}" class="text-sm text-cyan-400 hover:underline">
                                Forgot password?
                            </a>
                        </div>

                        {{-- Submit Button --}}
                        <button 
                            type="submit"
                            class="w-full py-4 bg-gradient-to-r from-pink-500 to-purple-500 rounded-xl font-bold text-lg hover:scale-[1.02] transition-all shadow-lg disabled:opacity-50 disabled:cursor-not-allowed"
                            wire:loading.attr="disabled"
                        >
                            <span wire:loading.remove wire:target="signIn">
                                🎟️ Sign In & Get Ticket
                            </span>
                            <span wire:loading wire:target="signIn" class="flex items-center justify-center gap-2">
                                <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                                </svg>
                                Signing in...
                            </span>
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>
    @endif
</div>
