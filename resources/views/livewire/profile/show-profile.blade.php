<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="mx-auto max-w-5xl space-y-8">
        <!-- Profile Header -->
        <div class="relative glass-card overflow-hidden">
            <div class="top-accent-center"></div>
            
            <!-- Cover -->
            <div class="h-48 bg-gradient-to-r from-pink-500 via-purple-500 to-indigo-600"></div>

            <!-- Profile content -->
            <div class="px-8 pb-8">
                <div class="flex flex-col sm:flex-row sm:items-end gap-6 -mt-20">
                    <!-- Avatar -->
                    <div class="flex-shrink-0">
                        @if($user->profile_image_url)
                            <img src="{{ Storage::url($user->profile_image_url) }}"
                                 alt="{{ $user->display_name ?? $user->name }}"
                                 class="h-40 w-40 rounded-full ring-4 ring-slate-900 object-cover bg-slate-800 shadow-2xl">
                        @else
                            <div class="h-40 w-40 rounded-full ring-4 ring-slate-900 bg-gradient-to-br from-pink-500 to-purple-600 flex items-center justify-center shadow-2xl">
                                <span class="text-5xl font-bold text-white">
                                    {{ strtoupper(substr($user->display_name ?? $user->name, 0, 1)) }}
                                </span>
                            </div>
                        @endif
                    </div>

                    <!-- Name and actions -->
                    <div class="flex-1 min-w-0 pb-2">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                            <div>
                                <h1 class="text-3xl font-bold text-white truncate">
                                    {{ $user->display_name ?? $user->name }}
                                </h1>
                                @if($user->location_name)
                                    <p class="mt-2 text-base text-gray-400 flex items-center gap-2">
                                        <svg class="w-5 h-5 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                        {{ $user->location_name }}
                                    </p>
                                @endif
                            </div>

                            @if(auth()->id() === $user->id)
                                <a href="{{ route('profile.edit') }}"
                                   class="inline-flex items-center justify-center px-6 py-3 border border-white/10 rounded-xl text-sm font-semibold text-white bg-slate-800/50 hover:bg-slate-700 hover:border-cyan-500/50 transition-all shadow-lg hover:shadow-cyan-500/20">
                                    Edit Profile
                                </a>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Bio & Interests Grid -->
                <div class="mt-10 grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Bio -->
                    <div class="lg:col-span-2 space-y-6">
                        @if($user->bio)
                            <div>
                                <h3 class="text-lg font-semibold text-white mb-3">About</h3>
                                <p class="text-gray-300 leading-relaxed text-lg">
                                    {{ $user->bio }}
                                </p>
                            </div>
                        @endif
                    </div>

                    <!-- Interests -->
                    <div class="lg:col-span-1">
                        @if($user->interests && count($user->interests) > 0)
                            <div class="bg-slate-800/30 rounded-2xl p-6 border border-white/5">
                                <h3 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-4">Interests</h3>
                                <div class="flex flex-wrap gap-2">
                                    @foreach($user->interests as $interest)
                                        <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium bg-purple-500/10 text-purple-300 border border-purple-500/20 hover:bg-purple-500/20 transition-colors cursor-default">
                                            {{ $interest }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-slate-800/50 border border-white/10 rounded-2xl p-6 text-center backdrop-blur-sm hover:border-pink-500/30 transition-colors group">
                <dt class="text-sm font-medium text-gray-400 group-hover:text-pink-400 transition-colors">Activities Hosted</dt>
                <dd class="mt-2 text-4xl font-bold text-white">0</dd>
            </div>
            <div class="bg-slate-800/50 border border-white/10 rounded-2xl p-6 text-center backdrop-blur-sm hover:border-purple-500/30 transition-colors group">
                <dt class="text-sm font-medium text-gray-400 group-hover:text-purple-400 transition-colors">Events Attended</dt>
                <dd class="mt-2 text-4xl font-bold text-white">0</dd>
            </div>
            <div class="bg-slate-800/50 border border-white/10 rounded-2xl p-6 text-center backdrop-blur-sm hover:border-cyan-500/30 transition-colors group">
                <dt class="text-sm font-medium text-gray-400 group-hover:text-cyan-400 transition-colors">Connections</dt>
                <dd class="mt-2 text-4xl font-bold text-white">0</dd>
            </div>
        </div>
    </div>
</div>
