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
                                <p class="mt-1 text-base text-gray-400">{{ '@'.$user->username }}</p>
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
                                    <p class="mt-2 text-base text-gray-400 flex items-center gap-2">
                                        <svg class="w-5 h-5 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                                       class="inline-flex items-center justify-center px-6 py-3 border border-white/10 rounded-xl text-sm font-semibold text-white bg-slate-800/50 hover:bg-slate-700 hover:border-cyan-500/50 transition-all shadow-lg hover:shadow-cyan-500/20">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                        Edit Profile
                                    </a>
                                @else
                                    @if($isFollowing)
                                        <button wire:click="unfollow" 
                                                wire:loading.attr="disabled"
                                                class="inline-flex items-center justify-center px-6 py-3 border border-purple-500/50 rounded-xl text-sm font-semibold text-white bg-purple-500/20 hover:bg-purple-500/30 hover:border-purple-500 transition-all shadow-lg hover:shadow-purple-500/20">
                                            <svg wire:loading.remove wire:target="unfollow" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                            <svg wire:loading wire:target="unfollow" class="animate-spin w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            <span wire:loading.remove wire:target="unfollow">Following</span>
                                            <span wire:loading wire:target="unfollow">Unfollowing...</span>
                                        </button>
                                    @else
                                        <button wire:click="follow" 
                                                wire:loading.attr="disabled"
                                                class="inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-pink-500 to-purple-500 rounded-xl text-sm font-semibold text-white hover:scale-105 transition-all shadow-lg hover:shadow-purple-500/50">
                                            <svg wire:loading.remove wire:target="follow" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                            </svg>
                                            <svg wire:loading wire:target="follow" class="animate-spin w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            <span wire:loading.remove wire:target="follow">Follow</span>
                                            <span wire:loading wire:target="follow">Following...</span>
                                        </button>
                                    @endif
                                @endif
                            @endauth
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
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
            <div class="bg-slate-800/50 border border-white/10 rounded-2xl p-6 text-center backdrop-blur-sm hover:border-pink-500/30 transition-colors group">
                <dt class="text-sm font-medium text-gray-400 group-hover:text-pink-400 transition-colors">Posts</dt>
                <dd class="mt-2 text-3xl font-bold text-white">{{ $postsCount }}</dd>
            </div>
            <div class="bg-slate-800/50 border border-white/10 rounded-2xl p-6 text-center backdrop-blur-sm hover:border-purple-500/30 transition-colors group">
                <dt class="text-sm font-medium text-gray-400 group-hover:text-purple-400 transition-colors">Events Hosted</dt>
                <dd class="mt-2 text-3xl font-bold text-white">{{ $hostedActivitiesCount }}</dd>
            </div>
            <div class="bg-slate-800/50 border border-white/10 rounded-2xl p-6 text-center backdrop-blur-sm hover:border-cyan-500/30 transition-colors group">
                <dt class="text-sm font-medium text-gray-400 group-hover:text-cyan-400 transition-colors">Attending</dt>
                <dd class="mt-2 text-3xl font-bold text-white">{{ $attendedActivitiesCount }}</dd>
            </div>
            <div class="bg-slate-800/50 border border-white/10 rounded-2xl p-6 text-center backdrop-blur-sm hover:border-indigo-500/30 transition-colors group cursor-pointer">
                <dt class="text-sm font-medium text-gray-400 group-hover:text-indigo-400 transition-colors">Followers</dt>
                <dd class="mt-2 text-3xl font-bold text-white">{{ $followersCount }}</dd>
            </div>
            <div class="bg-slate-800/50 border border-white/10 rounded-2xl p-6 text-center backdrop-blur-sm hover:border-teal-500/30 transition-colors group cursor-pointer">
                <dt class="text-sm font-medium text-gray-400 group-hover:text-teal-400 transition-colors">Following</dt>
                <dd class="mt-2 text-3xl font-bold text-white">{{ $followingCount }}</dd>
            </div>
        </div>

        <!-- Content Tabs -->
        <div class="glass-card">
            <div class="top-accent-left"></div>
            
            <!-- Tab Headers -->
            <div class="border-b border-white/10">
                <nav class="flex space-x-8 px-8 pt-6" aria-label="Tabs">
                    <button wire:click="switchTab('posts')" 
                            class="pb-4 px-1 border-b-2 font-medium text-sm transition-all {{ $activeTab === 'posts' ? 'border-pink-500 text-pink-400' : 'border-transparent text-gray-400 hover:text-gray-300 hover:border-gray-600' }}">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                            </svg>
                            <span>Posts</span>
                            <span class="{{ $activeTab === 'posts' ? 'bg-pink-500/20 text-pink-400' : 'bg-slate-700 text-gray-400' }} px-2 py-0.5 rounded-full text-xs font-semibold">{{ $postsCount }}</span>
                        </div>
                    </button>
                    <button wire:click="switchTab('hosted')" 
                            class="pb-4 px-1 border-b-2 font-medium text-sm transition-all {{ $activeTab === 'hosted' ? 'border-purple-500 text-purple-400' : 'border-transparent text-gray-400 hover:text-gray-300 hover:border-gray-600' }}">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <span>Hosting</span>
                            <span class="{{ $activeTab === 'hosted' ? 'bg-purple-500/20 text-purple-400' : 'bg-slate-700 text-gray-400' }} px-2 py-0.5 rounded-full text-xs font-semibold">{{ $hostedActivitiesCount }}</span>
                        </div>
                    </button>
                    <button wire:click="switchTab('attending')" 
                            class="pb-4 px-1 border-b-2 font-medium text-sm transition-all {{ $activeTab === 'attending' ? 'border-cyan-500 text-cyan-400' : 'border-transparent text-gray-400 hover:text-gray-300 hover:border-gray-600' }}">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span>Attending</span>
                            <span class="{{ $activeTab === 'attending' ? 'bg-cyan-500/20 text-cyan-400' : 'bg-slate-700 text-gray-400' }} px-2 py-0.5 rounded-full text-xs font-semibold">{{ $attendedActivitiesCount }}</span>
                        </div>
                    </button>
                </nav>
            </div>

            <!-- Tab Content -->
            <div class="p-8">
                @if($activeTab === 'posts')
                    @if(isset($posts) && $posts->count() > 0)
                        <div class="space-y-4">
                            @foreach($posts as $post)
                                <div class="bg-slate-800/30 border border-white/10 rounded-xl p-6 hover:border-pink-500/30 transition-all group">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <h3 class="text-lg font-semibold text-white group-hover:text-pink-400 transition-colors">
                                                {{ $post->title }}
                                            </h3>
                                            @if($post->description)
                                                <p class="mt-2 text-gray-400 line-clamp-2">
                                                    {{ $post->description }}
                                                </p>
                                            @endif
                                            <div class="mt-4 flex items-center gap-6 text-sm text-gray-500">
                                                <span class="flex items-center gap-1">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                    </svg>
                                                    {{ $post->created_at->diffForHumans() }}
                                                </span>
                                                @if($post->location_name)
                                                    <span class="flex items-center gap-1">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                                        </svg>
                                                        {{ $post->location_name }}
                                                    </span>
                                                @endif
                                                <span class="flex items-center gap-1">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                                    </svg>
                                                    {{ $post->reaction_count ?? 0 }} reactions
                                                </span>
                                            </div>
                                        </div>
                                        @if($post->status)
                                            <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $post->status === 'active' ? 'bg-green-500/20 text-green-400' : 'bg-gray-500/20 text-gray-400' }}">
                                                {{ ucfirst($post->status) }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-6">
                            {{ $posts->links() }}
                        </div>
                    @else
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                            </svg>
                            <h3 class="mt-4 text-lg font-medium text-gray-400">No posts yet</h3>
                            <p class="mt-2 text-sm text-gray-500">{{ auth()->id() === $user->id ? "You haven't created any posts yet." : "This user hasn't created any posts yet." }}</p>
                        </div>
                    @endif
                @elseif($activeTab === 'hosted' || $activeTab === 'attending')
                    @if(isset($activities) && $activities->count() > 0)
                        <div class="space-y-4">
                            @foreach($activities as $activity)
                                <div class="bg-slate-800/30 border border-white/10 rounded-xl p-6 hover:border-purple-500/30 transition-all group">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <h3 class="text-lg font-semibold text-white group-hover:text-purple-400 transition-colors">
                                                {{ $activity->title }}
                                            </h3>
                                            @if($activity->description)
                                                <p class="mt-2 text-gray-400 line-clamp-2">
                                                    {{ $activity->description }}
                                                </p>
                                            @endif
                                            <div class="mt-4 flex items-center gap-6 text-sm text-gray-500">
                                                @if($activity->start_time)
                                                    <span class="flex items-center gap-1">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                        </svg>
                                                        {{ $activity->start_time->format('M j, Y') }}
                                                    </span>
                                                @endif
                                                @if($activity->location_name)
                                                    <span class="flex items-center gap-1">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                                        </svg>
                                                        {{ $activity->location_name }}
                                                    </span>
                                                @endif
                                                <span class="flex items-center gap-1">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                                    </svg>
                                                    {{ $activity->current_attendees ?? 0 }} attending
                                                </span>
                                            </div>
                                        </div>
                                        @if($activity->status)
                                            <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $activity->status === 'published' ? 'bg-green-500/20 text-green-400' : 'bg-gray-500/20 text-gray-400' }}">
                                                {{ ucfirst($activity->status) }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-6">
                            {{ $activities->links() }}
                        </div>
                    @else
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <h3 class="mt-4 text-lg font-medium text-gray-400">No events yet</h3>
                            <p class="mt-2 text-sm text-gray-500">
                                @if($activeTab === 'hosted')
                                    {{ auth()->id() === $user->id ? "You haven't hosted any events yet." : "This user hasn't hosted any events yet." }}
                                @else
                                    {{ auth()->id() === $user->id ? "You're not attending any events yet." : "This user isn't attending any events yet." }}
                                @endif
                            </p>
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>
</div>
