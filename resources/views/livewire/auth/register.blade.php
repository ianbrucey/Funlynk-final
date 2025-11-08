<div class="min-h-screen flex items-center justify-center px-4 py-12">
    <div class="w-full max-w-2xl">
        <!-- Logo -->
        <div class="text-center mb-8">
            <div class="flex justify-center mb-4">
                <div class="relative">
                    <div class="gradient-border">
                        <div class="w-20 h-20 bg-slate-900 rounded-2xl flex items-center justify-center">
                            <img src="{{ asset('images/fl-logo-icon-only.png') }}" alt="FL" class="h-14 w-auto">
                        </div>
                    </div>
                    <div class="absolute -top-1 -right-1 w-5 h-5 bg-green-500 rounded-full border-2 border-slate-900 animate-pulse"></div>
                </div>
            </div>
            <h2 class="text-3xl font-bold">
                Create your <span class="text-yellow-400">Fun</span><span class="text-cyan-400">Lynk</span> account
            </h2>
            <p class="text-gray-400 mt-2">Share a few details to get started. You can update these later</p>
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

            <form wire:submit.prevent="register">
                {{ $this->form }}

                <div class="mt-6">
                    <button type="submit" class="w-full px-6 py-3 bg-gradient-to-r from-pink-500 to-purple-500 rounded-xl font-semibold hover:scale-105 transition-all">
                        Create account
                    </button>
                </div>
            </form>

            <div class="relative my-6">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-white/10"></div>
                </div>
                <div class="relative flex justify-center text-sm">
                    <span class="px-4 bg-slate-900/50 text-gray-400">Or continue with</span>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <a href="{{ route('social.redirect', 'google') }}" class="flex items-center justify-center gap-2 px-4 py-3 bg-slate-800/50 border border-white/10 rounded-xl hover:border-cyan-500/50 transition">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                        <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                        <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                        <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                    </svg>
                    <span class="text-sm">Google</span>
                </a>

                <a href="{{ route('social.redirect', 'facebook') }}" class="flex items-center justify-center gap-2 px-4 py-3 bg-slate-800/50 border border-white/10 rounded-xl hover:border-cyan-500/50 transition">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10 0C4.477 0 0 4.477 0 10c0 4.991 3.657 9.128 8.438 9.879V12.89h-2.54V10h2.54V7.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V10h2.773l-.443 2.89h-2.33v6.989C16.343 19.128 20 14.991 20 10c0-5.523-4.477-10-10-10z"/>
                    </svg>
                    <span class="text-sm">Facebook</span>
                </a>
            </div>

            <div class="text-center mt-6">
                <span class="text-gray-400">Already have an account?</span>
                <a href="{{ route('login') }}" class="text-cyan-400 hover:text-cyan-300 font-semibold ml-1 transition">
                    Sign in
                </a>
            </div>
        </div>
    </div>
</div>
