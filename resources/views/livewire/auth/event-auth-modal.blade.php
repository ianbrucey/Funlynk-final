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
