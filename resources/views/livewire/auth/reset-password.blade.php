<div class="min-h-screen flex items-center justify-center px-4 py-12">
    <div class="w-full max-w-md">
        <!-- Logo -->
        <div class="text-center mb-8">
            <div class="flex justify-center mb-4">
                <div class="relative">
                    <div class="gradient-border">
                        <div class="w-20 h-20 bg-slate-900 rounded-2xl flex items-center justify-center">
                            <img src="{{ asset('images/fl-logo-icon-only.png') }}" alt="FL" class="h-14 w-auto">
                        </div>
                    </div>
                    <div class="absolute -top-1 -right-1 w-5 h-5 bg-purple-500 rounded-full border-2 border-slate-900 animate-pulse"></div>
                </div>
            </div>
            <h2 class="text-3xl font-bold">
                <span class="text-yellow-400">Reset</span> <span class="text-cyan-400">Password</span>
            </h2>
            <p class="text-gray-400 mt-2">Enter your new password below</p>
        </div>

        <style>
            .gradient-border {
                position: relative;
                padding: 0.125rem;
                background: linear-gradient(to right, #ec4899, #8b5cf6, #06b6d4);
                border-radius: 1rem;
                animation: pulse 2s infinite;
            }
        </style>

        <!-- Glass Card -->
        <div class="relative p-8 glass-card">
            <div class="top-accent-center"></div>

            @if ($resetSuccessful)
                <!-- Success State -->
                <div class="text-center py-6">
                    <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-green-500/20 flex items-center justify-center">
                        <svg class="w-8 h-8 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-2">Password Reset!</h3>
                    <p class="text-gray-400 mb-6">
                        Your password has been successfully reset. You can now sign in with your new password.
                    </p>
                    <a href="{{ route('login') }}" class="inline-block w-full px-6 py-3 bg-gradient-to-r from-pink-500 to-purple-500 rounded-xl font-semibold hover:scale-105 transition-all text-center">
                        Sign In
                    </a>
                </div>
            @else
                <!-- Form State -->
                <form wire:submit.prevent="resetPassword">
                    <div class="p-6 bg-slate-800/40 border border-white/10 rounded-2xl mb-6">
                        <h2 class="text-xl font-bold mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5 text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                            New Password
                        </h2>
                        
                        <div class="space-y-4">
                            <div>
                                <label for="email" class="block text-sm font-semibold text-gray-300 mb-2">Email address</label>
                                <input 
                                    type="email" 
                                    id="email"
                                    wire:model="email"
                                    class="w-full px-4 py-3 bg-slate-800/50 border border-white/10 rounded-2xl focus:border-pink-500/50 focus:outline-none focus:ring-2 focus:ring-pink-500/20 transition text-white placeholder-gray-500"
                                    placeholder="Confirm your email"
                                    required
                                >
                                @error('email')
                                    <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="password" class="block text-sm font-semibold text-gray-300 mb-2">New Password</label>
                                <input 
                                    type="password" 
                                    id="password"
                                    wire:model="password"
                                    class="w-full px-4 py-3 bg-slate-800/50 border border-white/10 rounded-2xl focus:border-pink-500/50 focus:outline-none focus:ring-2 focus:ring-pink-500/20 transition text-white placeholder-gray-500"
                                    placeholder="Enter new password (min. 8 characters)"
                                    required
                                >
                                @error('password')
                                    <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="password_confirmation" class="block text-sm font-semibold text-gray-300 mb-2">Confirm Password</label>
                                <input 
                                    type="password" 
                                    id="password_confirmation"
                                    wire:model="password_confirmation"
                                    class="w-full px-4 py-3 bg-slate-800/50 border border-white/10 rounded-2xl focus:border-pink-500/50 focus:outline-none focus:ring-2 focus:ring-pink-500/20 transition text-white placeholder-gray-500"
                                    placeholder="Confirm new password"
                                    required
                                >
                            </div>
                        </div>
                    </div>

                    <div class="mt-6">
                        <button 
                            type="submit" 
                            class="w-full px-6 py-3 bg-gradient-to-r from-pink-500 to-purple-500 rounded-xl font-semibold hover:scale-105 transition-all flex items-center justify-center gap-2"
                            wire:loading.attr="disabled"
                            wire:loading.class="opacity-75 cursor-not-allowed"
                        >
                            <span wire:loading.remove>Reset Password</span>
                            <span wire:loading class="flex items-center gap-2">
                                <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Resetting...
                            </span>
                        </button>
                    </div>
                </form>

                <div class="text-center mt-6">
                    <a href="{{ route('login') }}" class="inline-flex items-center gap-2 text-cyan-400 hover:text-cyan-300 font-semibold transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        Back to login
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>

