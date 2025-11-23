<div class="container mx-auto px-6 py-8">
    <!-- Welcome Section -->
    <div class="relative p-8 glass-card max-w-5xl mx-auto mb-8">
        <div class="top-accent-center"></div>

        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-4xl font-bold mb-2">
                    Welcome back, <span class="text-cyan-400">{{ Auth::user()->display_name ?? Auth::user()->username }}</span>!
                </h1>
                <p class="text-gray-400">Discover spontaneous activities happening near you</p>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
            <div class="p-4 rounded-xl bg-slate-800/50 border border-white/10">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-pink-500 to-purple-500 flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="text-2xl font-bold">0</div>
                        <div class="text-sm text-gray-400">Connections</div>
                    </div>
                </div>
            </div>

            <div class="p-4 rounded-xl bg-slate-800/50 border border-white/10">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-cyan-500 to-blue-500 flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="text-2xl font-bold">0</div>
                        <div class="text-sm text-gray-400">Events Joined</div>
                    </div>
                </div>
            </div>

            <div class="p-4 rounded-xl bg-slate-800/50 border border-white/10">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-yellow-500 to-orange-500 flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="text-2xl font-bold">0</div>
                        <div class="text-sm text-gray-400">Posts Created</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Activity Feed Placeholder -->
    <div class="relative p-8 glass-card max-w-5xl mx-auto">
        <div class="top-accent-center"></div>

        <h2 class="text-2xl font-bold mb-6">Activity Feed</h2>

        <div class="text-center py-12">
            <div class="w-24 h-24 mx-auto mb-6 rounded-full bg-gradient-to-br from-pink-500 via-purple-500 to-cyan-500 flex items-center justify-center">
                <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
            </div>
            <h3 class="text-xl font-bold mb-2">No activities yet</h3>
            <p class="text-gray-400 mb-6">Start by creating your first post or exploring nearby events</p>
            <a
                href="{{ route('activities.create') }}"
                class="inline-block px-6 py-3 bg-gradient-to-r from-pink-500 to-purple-500 rounded-xl font-semibold hover:scale-105 transition-all">
                Create Your First Post
            </a>
        </div>
    </div>
</div>
