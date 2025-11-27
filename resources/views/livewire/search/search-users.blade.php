<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="mx-auto max-w-7xl space-y-8">
        <!-- Page Header -->
        <div class="text-center">
            <h1 class="text-4xl font-bold text-white">Find People</h1>
            <p class="mt-2 text-lg text-gray-400">Discover users who share your interests</p>
        </div>

        <!-- Search and Filters Card -->
        <div class="glass-card p-6">
            <div class="top-accent-center"></div>
            
            <!-- Search Bar -->
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <input type="text"
                       wire:model.live.debounce.300ms="query"
                       placeholder="Search by name or username..."
                       class="block w-full pl-12 pr-12 py-4 bg-slate-800/50 border border-white/10 rounded-xl text-white placeholder-gray-500 focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/50 focus:outline-none transition-all">
                @if($query)
                    <button wire:click="$set('query', '')"
                            class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-white transition-colors">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                @endif
            </div>

            <!-- Active Filters & Distance Selector -->
            <div class="mt-6 flex flex-wrap items-center gap-4">
                <!-- Distance Filter -->
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    </svg>
                    @if(auth()->user()->location_coordinates)
                        <select wire:model.live="distance"
                                class="px-4 py-2 bg-slate-800/50 border border-white/10 rounded-xl text-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/50 focus:outline-none">
                            <option value="">Anywhere</option>
                            <option value="5">Within 5km</option>
                            <option value="10">Within 10km</option>
                            <option value="25">Within 25km</option>
                            <option value="50">Within 50km</option>
                            <option value="100">Within 100km</option>
                        </select>
                    @else
                        <a href="{{ route('profile.edit') }}" 
                           class="px-4 py-2 bg-slate-800/50 border border-white/10 rounded-xl text-gray-400 hover:text-white hover:border-indigo-500/50 transition-all">
                            Set location to filter by distance
                        </a>
                    @endif
                </div>

                <!-- Selected Interests -->
                @if(count($selectedInterests) > 0)
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="text-sm text-gray-400">Interests:</span>
                        @foreach($selectedInterests as $interest)
                            <button wire:click="toggleInterest('{{ $interest }}')"
                                    class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-sm font-medium bg-pink-500/20 text-pink-300 border border-pink-500/30 hover:bg-pink-500/30 transition-all">
                                {{ $interest }}
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        @endforeach
                    </div>
                @endif

                <!-- Clear Filters -->
                @if($query || count($selectedInterests) > 0 || $distance)
                    <button wire:click="clearFilters"
                            class="ml-auto px-4 py-2 text-sm text-gray-400 hover:text-white transition-colors">
                        Clear all filters
                    </button>
                @endif
            </div>

            <!-- Popular Interests -->
            @if(count($popularInterests) > 0)
                <div class="mt-6 pt-6 border-t border-white/10">
                    <h3 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-3">Popular Interests</h3>
                    <div class="flex flex-wrap gap-2">
                        @foreach($popularInterests as $interest)
                            <button wire:click="toggleInterest('{{ $interest }}')"
                                    class="px-3 py-1.5 rounded-full text-sm font-medium transition-all {{ in_array($interest, $selectedInterests) ? 'bg-pink-500/20 text-pink-300 border border-pink-500/30' : 'bg-purple-500/10 text-purple-300 border border-purple-500/20 hover:bg-purple-500/20' }}">
                                {{ $interest }}
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <!-- Loading State -->
        <div wire:loading class="text-center py-8">
            <div class="inline-flex items-center gap-3 px-6 py-3 bg-slate-800/50 border border-white/10 rounded-xl">
                <svg class="animate-spin h-5 w-5 text-cyan-400" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-white font-medium">Searching...</span>
            </div>
        </div>

        <!-- Results Grid -->
        <div wire:loading.remove>
            @if($results->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($results as $user)
                        <div class="glass-card group hover:border-cyan-500/30 transition-all cursor-pointer" 
                             onclick="window.location.href='{{ route('profile.view', $user->username) }}'">
                            <div class="p-6 space-y-4">
                                <!-- Avatar and Name -->
                                <div class="flex items-start gap-4">
                                    @if($user->profile_image_url)
                                        <img src="{{ Storage::url($user->profile_image_url) }}"
                                             alt="{{ $user->display_name ?? $user->username }}"
                                             class="h-16 w-16 rounded-full object-cover bg-slate-800 ring-2 ring-white/10 group-hover:ring-cyan-500/50 transition-all">
                                    @else
                                        <div class="h-16 w-16 rounded-full bg-gradient-to-br from-pink-500 to-purple-600 flex items-center justify-center ring-2 ring-white/10 group-hover:ring-cyan-500/50 transition-all">
                                            <span class="text-2xl font-bold text-white">
                                                {{ strtoupper(substr($user->display_name ?? $user->username, 0, 1)) }}
                                            </span>
                                        </div>
                                    @endif

                                    <div class="flex-1 min-w-0">
                                        <h3 class="text-lg font-semibold text-white truncate group-hover:text-cyan-400 transition-colors">
                                            {{ $user->display_name ?? $user->username }}
                                        </h3>
                                        <p class="text-sm text-gray-400">{{ '@'.$user->username }}</p>
                                    </div>
                                </div>

                                <!-- Location -->
                                @if($user->location_name)
                                    <div class="flex items-center gap-2 text-sm text-gray-400">
                                        <svg class="w-4 h-4 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                        </svg>
                                        <span class="truncate">{{ $user->location_name }}</span>
                                    </div>
                                @endif

                                <!-- Interests -->
                                @if($user->interests && count($user->interests) > 0)
                                    <div class="flex flex-wrap gap-2">
                                        @foreach(array_slice($user->interests, 0, 3) as $interest)
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ in_array($interest, $selectedInterests) ? 'bg-pink-500/20 text-pink-300 border border-pink-500/30' : 'bg-purple-500/10 text-purple-300 border border-purple-500/20' }}">
                                                {{ $interest }}
                                            </span>
                                        @endforeach
                                        @if(count($user->interests) > 3)
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-slate-700 text-gray-400">
                                                +{{ count($user->interests) - 3 }}
                                            </span>
                                        @endif
                                    </div>
                                @endif

                                <!-- Stats & Follow Button -->
                                <div class="flex items-center justify-between pt-4 border-t border-white/10">
                                    <div class="flex items-center gap-2 text-sm text-gray-400">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                        </svg>
                                        <span>{{ $user->follower_count ?? 0 }}</span>
                                    </div>

                                    @if(in_array($user->id, $followingIds))
                                        <button wire:click.stop="unfollow('{{ $user->id }}')"
                                                wire:loading.attr="disabled"
                                                class="px-4 py-2 border border-purple-500/50 rounded-xl text-sm font-semibold text-white bg-purple-500/20 hover:bg-purple-500/30 transition-all">
                                            <span wire:loading.remove wire:target="unfollow('{{ $user->id }}')">Following</span>
                                            <span wire:loading wire:target="unfollow('{{ $user->id }}')">...</span>
                                        </button>
                                    @else
                                        <button wire:click.stop="follow('{{ $user->id }}')"
                                                wire:loading.attr="disabled"
                                                class="px-4 py-2 bg-gradient-to-r from-pink-500 to-purple-500 rounded-xl text-sm font-semibold text-white hover:scale-105 transition-all">
                                            <span wire:loading.remove wire:target="follow('{{ $user->id }}')">Follow</span>
                                            <span wire:loading wire:target="follow('{{ $user->id }}')">...</span>
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Pagination -->
                <div class="mt-8">
                    {{ $results->links() }}
                </div>
            @else
                <!-- Empty State -->
                <div class="text-center py-16">
                    <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-slate-800/50 border border-white/10 mb-6">
                        <svg class="w-10 h-10 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-white mb-2">
                        @if($query || count($selectedInterests) > 0)
                            No users found
                        @else
                            Start searching for people
                        @endif
                    </h3>
                    <p class="text-gray-400 mb-6">
                        @if($query || count($selectedInterests) > 0)
                            Try adjusting your filters or search terms
                        @else
                            Enter a name, username, or select interests to find people
                        @endif
                    </p>
                    @if($query || count($selectedInterests) > 0)
                        <button wire:click="clearFilters"
                                class="px-6 py-3 bg-gradient-to-r from-pink-500 to-purple-500 rounded-xl text-sm font-semibold text-white hover:scale-105 transition-all">
                            Clear Filters
                        </button>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>
