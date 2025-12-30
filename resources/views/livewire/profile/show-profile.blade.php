<div class="min-h-screen overflow-x-hidden">
    <div class="container mx-auto px-4 py-6 md:py-12">
        <div class="mx-auto max-w-5xl space-y-6 md:space-y-8">
            <!-- Profile Header -->
            <div class="relative glass-card overflow-hidden">
                <div class="top-accent-center"></div>

                <!-- Profile content -->
                <div class="px-4 md:px-8 py-6 md:py-8">
                    <div class="flex flex-col items-center md:flex-row md:items-start gap-4 md:gap-6">
                        <!-- Avatar -->
                        <div class="flex-shrink-0">
                            @if($user->profile_image_url)
                                <img src="{{ Storage::url($user->profile_image_url) }}"
                                     alt="{{ $user->display_name ?? $user->name }}"
                                     class="h-24 w-24 md:h-32 md:w-32 rounded-full ring-4 ring-white/10 object-cover bg-slate-800 shadow-xl">
                            @else
                                <div class="h-24 w-24 md:h-32 md:w-32 rounded-full ring-4 ring-white/10 bg-gradient-to-br from-pink-500 to-purple-600 flex items-center justify-center shadow-xl">
                                    <span class="text-3xl md:text-4xl font-bold text-white">
                                        {{ strtoupper(substr($user->display_name ?? $user->name, 0, 1)) }}
                                    </span>
                                </div>
                            @endif
                        </div>

                        <!-- Name and actions -->
                        <div class="flex-1 min-w-0 text-center md:text-left">
                            <div class="flex flex-col gap-4">
                                <div>
                                    <h1 class="text-2xl md:text-3xl font-bold text-white truncate">
                                        {{ $user->display_name ?? $user->name }}
                                    </h1>
                                    <p class="mt-1 text-sm md:text-base text-gray-400">{{ '@'.$user->username }}</p>
                                    @if($user->location_name)
                                        @php
                                            // Parse location_name to extract city, state
                                            $locationParts = explode(',', $user->location_name);
                                            $city = trim($locationParts[0] ?? '');
                                            $state = trim($locationParts[1] ?? '');
                                            $displayLocation = $city;
                                            if ($state) {
                                                $displayLocation .= ', ' . $state;
                                            }
                                        @endphp
                                        <p class="mt-2 text-sm md:text-base text-gray-400 flex items-center justify-center md:justify-start gap-2">
                                            <svg class="w-4 h-4 md:w-5 md:h-5 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            </svg>
                                            {{ $displayLocation }}
                                        </p>
                                    @endif
                                </div>

                                @auth
                                    @if(auth()->id() === $user->id)
                                        <a href="{{ route('profile.edit') }}"
                                           class="inline-flex items-center justify-center px-4 md:px-6 py-2.5 md:py-3 border border-white/10 rounded-xl text-sm font-semibold text-white bg-slate-800/50 hover:bg-slate-700 hover:border-cyan-500/50 transition-all shadow-lg hover:shadow-cyan-500/20">
                                            <svg class="w-4 h-4 md:w-5 md:h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                            Edit Profile
                                        </a>
                                    @else
                                        <div class="flex flex-wrap justify-center md:justify-start gap-2 md:gap-3">
                                            {{-- Follow/Unfollow Button --}}
                                            @if($isFollowing)
                                                <button wire:click="unfollow"
                                                        wire:loading.attr="disabled"
                                                        class="inline-flex items-center justify-center px-4 md:px-6 py-2.5 md:py-3 border border-purple-500/50 rounded-xl text-sm font-semibold text-white bg-purple-500/20 hover:bg-purple-500/30 hover:border-purple-500 transition-all shadow-lg hover:shadow-purple-500/20">
                                                    <svg wire:loading.remove wire:target="unfollow" class="w-4 h-4 md:w-5 md:h-5 mr-1.5 md:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                    </svg>
                                                    <svg wire:loading wire:target="unfollow" class="animate-spin w-4 h-4 md:w-5 md:h-5 mr-1.5 md:mr-2" fill="none" viewBox="0 0 24 24">
                                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                    </svg>
                                                    <span wire:loading.remove wire:target="unfollow">Following</span>
                                                    <span wire:loading wire:target="unfollow">...</span>
                                                </button>
                                            @else
                                                <button wire:click="follow"
                                                        wire:loading.attr="disabled"
                                                        class="inline-flex items-center justify-center px-4 md:px-6 py-2.5 md:py-3 bg-gradient-to-r from-pink-500 to-purple-500 rounded-xl text-sm font-semibold text-white hover:scale-105 transition-all shadow-lg hover:shadow-purple-500/50">
                                                    <svg wire:loading.remove wire:target="follow" class="w-4 h-4 md:w-5 md:h-5 mr-1.5 md:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                                    </svg>
                                                    <svg wire:loading wire:target="follow" class="animate-spin w-4 h-4 md:w-5 md:h-5 mr-1.5 md:mr-2" fill="none" viewBox="0 0 24 24">
                                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                    </svg>
                                                    <span wire:loading.remove wire:target="follow">Follow</span>
                                                    <span wire:loading wire:target="follow">...</span>
                                                </button>
                                            @endif

                                            {{-- Message Button --}}
                                            <button wire:click="startConversation"
                                                    wire:loading.attr="disabled"
                                                    class="inline-flex items-center justify-center px-4 md:px-6 py-2.5 md:py-3 bg-gradient-to-r from-cyan-500 to-blue-500 rounded-xl text-sm font-semibold text-white hover:scale-105 transition-all shadow-lg hover:shadow-cyan-500/50">
                                                <svg wire:loading.remove wire:target="startConversation" class="w-4 h-4 md:w-5 md:h-5 mr-1.5 md:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                                </svg>
                                                <svg wire:loading wire:target="startConversation" class="animate-spin w-4 h-4 md:w-5 md:h-5 mr-1.5 md:mr-2" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                <span wire:loading.remove wire:target="startConversation">Message</span>
                                                <span wire:loading wire:target="startConversation">...</span>
                                            </button>
                                        </div>
                                    @endif
                                @endauth
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bio & Interests Grid -->
                <div class="px-4 md:px-8 pb-6 md:pb-8">
                    <div class="grid grid-cols-1 gap-4 md:gap-6">
                        <!-- Bio -->
                        @if($user->bio)
                            <div>
                                <h3 class="text-base md:text-lg font-semibold text-white mb-2 md:mb-3">About</h3>
                                <p class="text-gray-300 leading-relaxed text-sm md:text-base">
                                    {{ $user->bio }}
                                </p>
                            </div>
                        @endif

                        <!-- Interests -->
                        @if($user->interests && count($user->interests) > 0)
                            <div class="bg-slate-800/30 rounded-xl md:rounded-2xl p-4 md:p-6 border border-white/5">
                                <h3 class="text-xs md:text-sm font-semibold text-gray-400 uppercase tracking-wider mb-3 md:mb-4">Interests</h3>
                                <div class="flex flex-wrap gap-2">
                                    @foreach($user->interests as $interest)
                                        <span class="inline-flex items-center px-2.5 md:px-3 py-1 md:py-1.5 rounded-full text-xs md:text-sm font-medium bg-purple-500/10 text-purple-300 border border-purple-500/20">
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
        <div class="grid grid-cols-2 gap-3 md:grid-cols-5 md:gap-6 mt-6 md:mt-8">
            <div class="bg-slate-800/50 border border-white/10 rounded-xl md:rounded-2xl p-3 md:p-6 text-center backdrop-blur-sm">
                <dt class="text-xs md:text-sm font-medium text-gray-400">Posts</dt>
                <dd class="mt-1 md:mt-2 text-2xl md:text-3xl font-bold text-white">{{ $postsCount }}</dd>
            </div>
            <div class="bg-slate-800/50 border border-white/10 rounded-xl md:rounded-2xl p-3 md:p-6 text-center backdrop-blur-sm">
                <dt class="text-xs md:text-sm font-medium text-gray-400">Events Hosted</dt>
                <dd class="mt-1 md:mt-2 text-2xl md:text-3xl font-bold text-white">{{ $hostedActivitiesCount }}</dd>
            </div>
            <div class="bg-slate-800/50 border border-white/10 rounded-xl md:rounded-2xl p-3 md:p-6 text-center backdrop-blur-sm">
                <dt class="text-xs md:text-sm font-medium text-gray-400">Attending</dt>
                <dd class="mt-1 md:mt-2 text-2xl md:text-3xl font-bold text-white">{{ $attendedActivitiesCount }}</dd>
            </div>
            <div class="bg-slate-800/50 border border-white/10 rounded-xl md:rounded-2xl p-3 md:p-6 text-center backdrop-blur-sm">
                <dt class="text-xs md:text-sm font-medium text-gray-400">Followers</dt>
                <dd class="mt-1 md:mt-2 text-2xl md:text-3xl font-bold text-white">{{ $followersCount }}</dd>
            </div>
            <div class="bg-slate-800/50 border border-white/10 rounded-xl md:rounded-2xl p-3 md:p-6 text-center backdrop-blur-sm col-span-2 md:col-span-1">
                <dt class="text-xs md:text-sm font-medium text-gray-400">Following</dt>
                <dd class="mt-1 md:mt-2 text-2xl md:text-3xl font-bold text-white">{{ $followingCount }}</dd>
            </div>
        </div>

        <!-- Content Tabs -->
        <div class="glass-card overflow-hidden mt-6 md:mt-8">
            <div class="top-accent-left"></div>

            <!-- Tab Headers - Horizontal scroll on mobile -->
            <div class="border-b border-white/10 overflow-x-auto scrollbar-hide">
                <nav class="flex min-w-max gap-1 md:gap-4 px-4 md:px-8 pt-4 md:pt-6" aria-label="Tabs">
                    <button wire:click="switchTab('posts')"
                            class="pb-3 md:pb-4 px-2 md:px-3 border-b-2 font-medium text-xs md:text-sm transition-all whitespace-nowrap {{ $activeTab === 'posts' ? 'border-pink-500 text-pink-400' : 'border-transparent text-gray-400 hover:text-gray-300' }}">
                        <div class="flex items-center gap-1.5 md:gap-2">
                            <svg class="w-4 h-4 md:w-5 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                            </svg>
                            <span>Posts</span>
                            <span class="{{ $activeTab === 'posts' ? 'bg-pink-500/20 text-pink-400' : 'bg-slate-700 text-gray-400' }} px-1.5 md:px-2 py-0.5 rounded-full text-xs font-semibold">{{ $postsCount }}</span>
                        </div>
                    </button>
                    <button wire:click="switchTab('hosted')"
                            class="pb-3 md:pb-4 px-2 md:px-3 border-b-2 font-medium text-xs md:text-sm transition-all whitespace-nowrap {{ $activeTab === 'hosted' ? 'border-purple-500 text-purple-400' : 'border-transparent text-gray-400 hover:text-gray-300' }}">
                        <div class="flex items-center gap-1.5 md:gap-2">
                            <svg class="w-4 h-4 md:w-5 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <span>Hosting</span>
                            <span class="{{ $activeTab === 'hosted' ? 'bg-purple-500/20 text-purple-400' : 'bg-slate-700 text-gray-400' }} px-1.5 md:px-2 py-0.5 rounded-full text-xs font-semibold">{{ $hostedActivitiesCount }}</span>
                        </div>
                    </button>
                    <button wire:click="switchTab('attending')"
                            class="pb-3 md:pb-4 px-2 md:px-3 border-b-2 font-medium text-xs md:text-sm transition-all whitespace-nowrap {{ $activeTab === 'attending' ? 'border-cyan-500 text-cyan-400' : 'border-transparent text-gray-400 hover:text-gray-300' }}">
                        <div class="flex items-center gap-1.5 md:gap-2">
                            <svg class="w-4 h-4 md:w-5 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span>Attending</span>
                            <span class="{{ $activeTab === 'attending' ? 'bg-cyan-500/20 text-cyan-400' : 'bg-slate-700 text-gray-400' }} px-1.5 md:px-2 py-0.5 rounded-full text-xs font-semibold">{{ $attendedActivitiesCount }}</span>
                        </div>
                    </button>
                    <button wire:click="switchTab('interested')"
                            class="pb-3 md:pb-4 px-2 md:px-3 border-b-2 font-medium text-xs md:text-sm transition-all whitespace-nowrap {{ $activeTab === 'interested' ? 'border-amber-500 text-amber-400' : 'border-transparent text-gray-400 hover:text-gray-300' }}">
                        <div class="flex items-center gap-1.5 md:gap-2">
                            <svg class="w-4 h-4 md:w-5 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                            </svg>
                            <span>Interested</span>
                            <span class="{{ $activeTab === 'interested' ? 'bg-amber-500/20 text-amber-400' : 'bg-slate-700 text-gray-400' }} px-1.5 md:px-2 py-0.5 rounded-full text-xs font-semibold">{{ $interestedPostsCount }}</span>
                        </div>
                    </button>
                </nav>
            </div>

            <!-- Tab Content -->
            <div class="p-4 md:p-8">
                @if($activeTab === 'posts')
                    @if(isset($posts) && $posts->count() > 0)
                        <div class="space-y-3 md:space-y-4">
                            @foreach($posts as $post)
                                <a href="{{ route('posts.show', $post->id) }}" class="block bg-slate-800/30 border border-white/10 rounded-xl p-4 md:p-6 hover:border-pink-500/30 transition-all group">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="flex-1 min-w-0">
                                            <h3 class="text-base md:text-lg font-semibold text-white group-hover:text-pink-400 transition-colors truncate">
                                                {{ $post->title }}
                                            </h3>
                                            @if($post->description)
                                                <p class="mt-1.5 md:mt-2 text-sm text-gray-400 line-clamp-2">
                                                    {{ $post->description }}
                                                </p>
                                            @endif
                                            <div class="mt-3 md:mt-4 flex flex-wrap items-center gap-3 md:gap-4 text-xs md:text-sm text-gray-500">
                                                <span class="flex items-center gap-1">
                                                    <svg class="w-3.5 h-3.5 md:w-4 md:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                    </svg>
                                                    {{ $post->created_at->diffForHumans() }}
                                                </span>
                                                @if($post->location_name)
                                                    <span class="flex items-center gap-1 truncate max-w-[120px] md:max-w-none">
                                                        <svg class="w-3.5 h-3.5 md:w-4 md:h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                                        </svg>
                                                        <span class="truncate">{{ $post->location_name }}</span>
                                                    </span>
                                                @endif
                                                <span class="flex items-center gap-1">
                                                    <svg class="w-3.5 h-3.5 md:w-4 md:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                                    </svg>
                                                    {{ $post->reaction_count ?? 0 }}
                                                </span>
                                            </div>
                                        </div>
                                        @if($post->status)
                                            <span class="flex-shrink-0 px-2 md:px-3 py-1 rounded-full text-xs font-semibold {{ $post->status === 'active' ? 'bg-green-500/20 text-green-400' : 'bg-gray-500/20 text-gray-400' }}">
                                                {{ ucfirst($post->status) }}
                                            </span>
                                        @endif
                                    </div>
                                </a>
                            @endforeach
                        </div>
                        <div class="mt-4 md:mt-6">
                            {{ $posts->links() }}
                        </div>
                    @else
                        <div class="text-center py-8 md:py-12">
                            <svg class="mx-auto h-10 w-10 md:h-12 md:w-12 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                            </svg>
                            <h3 class="mt-3 md:mt-4 text-base md:text-lg font-medium text-gray-400">No posts yet</h3>
                            <p class="mt-1.5 md:mt-2 text-xs md:text-sm text-gray-500">{{ auth()->id() === $user->id ? "You haven't created any posts yet." : "This user hasn't created any posts yet." }}</p>
                        </div>
                    @endif
                @elseif($activeTab === 'hosted' || $activeTab === 'attending')
                    @if(isset($activities) && $activities->count() > 0)
                        <div class="space-y-3 md:space-y-4">
                            @foreach($activities as $activity)
                                <a href="{{ route('activities.show', $activity->id) }}" class="block bg-slate-800/30 border border-white/10 rounded-xl p-4 md:p-6 hover:border-purple-500/30 transition-all group">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="flex-1 min-w-0">
                                            <h3 class="text-base md:text-lg font-semibold text-white group-hover:text-purple-400 transition-colors truncate">
                                                {{ $activity->title }}
                                            </h3>
                                            @if($activity->description)
                                                <p class="mt-1.5 md:mt-2 text-sm text-gray-400 line-clamp-2">
                                                    {{ $activity->description }}
                                                </p>
                                            @endif
                                            <div class="mt-3 md:mt-4 flex flex-wrap items-center gap-3 md:gap-4 text-xs md:text-sm text-gray-500">
                                                @if($activity->start_time)
                                                    <span class="flex items-center gap-1">
                                                        <svg class="w-3.5 h-3.5 md:w-4 md:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                        </svg>
                                                        {{ $activity->start_time->format('M j, Y') }}
                                                    </span>
                                                @endif
                                                @if($activity->location_name)
                                                    <span class="flex items-center gap-1 truncate max-w-[120px] md:max-w-none">
                                                        <svg class="w-3.5 h-3.5 md:w-4 md:h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                                        </svg>
                                                        <span class="truncate">{{ $activity->location_name }}</span>
                                                    </span>
                                                @endif
                                                <span class="flex items-center gap-1">
                                                    <svg class="w-3.5 h-3.5 md:w-4 md:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                                    </svg>
                                                    {{ $activity->current_attendees ?? 0 }}
                                                </span>
                                            </div>
                                        </div>
                                        @if($activity->status)
                                            <span class="flex-shrink-0 px-2 md:px-3 py-1 rounded-full text-xs font-semibold {{ $activity->status === 'published' ? 'bg-green-500/20 text-green-400' : 'bg-gray-500/20 text-gray-400' }}">
                                                {{ ucfirst($activity->status) }}
                                            </span>
                                        @endif
                                    </div>
                                </a>
                            @endforeach
                        </div>
                        <div class="mt-4 md:mt-6">
                            {{ $activities->links() }}
                        </div>
                    @else
                        <div class="text-center py-8 md:py-12">
                            <svg class="mx-auto h-10 w-10 md:h-12 md:w-12 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <h3 class="mt-3 md:mt-4 text-base md:text-lg font-medium text-gray-400">No events yet</h3>
                            <p class="mt-1.5 md:mt-2 text-xs md:text-sm text-gray-500">
                                @if($activeTab === 'hosted')
                                    {{ auth()->id() === $user->id ? "You haven't hosted any events yet." : "This user hasn't hosted any events yet." }}
                                @else
                                    {{ auth()->id() === $user->id ? "You're not attending any events yet." : "This user isn't attending any events yet." }}
                                @endif
                            </p>
                        </div>
                    @endif
                @elseif($activeTab === 'interested')
                    <livewire:profile.interested-tab :user="$user" :key="'interested-'.$user->id" />
                @endif
            </div>
        </div>
    </div>
</div>
