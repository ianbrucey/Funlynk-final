<div class="min-h-screen overflow-x-hidden pb-20 md:pb-8 bg-slate-900">
    <div class="container mx-auto px-4 py-4 md:py-8 max-w-4xl">
        <!-- Profile Header - Instagram Style -->
        <div class="mb-8 bg-slate-800/50 rounded-2xl p-6 border border-white/5">
            <div class="flex gap-4 md:gap-8 items-start">
                <!-- Avatar -->
                <div class="flex-shrink-0">
                    @if($user->profile_image_url)
                        <img src="{{ Storage::url($user->profile_image_url) }}"
                             alt="{{ $user->display_name ?? $user->name }}"
                             class="h-20 w-20 md:h-36 md:w-36 rounded-full object-cover bg-slate-800 ring-2 ring-white/10">
                    @else
                        <div class="h-20 w-20 md:h-36 md:w-36 rounded-full bg-gradient-to-br from-pink-500 to-purple-600 flex items-center justify-center ring-2 ring-white/10">
                            <span class="text-2xl md:text-5xl font-bold text-white">
                                {{ strtoupper(substr($user->display_name ?? $user->name, 0, 1)) }}
                            </span>
                        </div>
                    @endif
                </div>

                <!-- Info & Stats -->
                <div class="flex-1 min-w-0">
                    <!-- Username & Actions -->
                    <div class="flex flex-col md:flex-row md:items-center gap-3 md:gap-4 mb-4">
                        <h1 class="text-xl md:text-2xl font-semibold text-white">
                            {{ $user->username }}
                        </h1>

                        @auth
                            @if(auth()->id() === $user->id)
                                <a href="{{ route('profile.edit') }}"
                                   class="inline-flex items-center justify-center px-4 py-1.5 border border-white/20 rounded-lg text-sm font-semibold text-white bg-slate-800/50 hover:bg-slate-700 transition-all">
                                    Edit Profile
                                </a>
                            @else
                                <div class="flex gap-2">
                                    @if($isFollowing)
                                        <button wire:click="unfollow"
                                                wire:loading.attr="disabled"
                                                class="inline-flex items-center justify-center px-6 py-1.5 border border-white/20 rounded-lg text-sm font-semibold text-white bg-slate-800/50 hover:bg-slate-700 transition-all">
                                            <span wire:loading.remove wire:target="unfollow">Following</span>
                                            <span wire:loading wire:target="unfollow">...</span>
                                        </button>
                                    @else
                                        <button wire:click="follow"
                                                wire:loading.attr="disabled"
                                                class="inline-flex items-center justify-center px-6 py-1.5 bg-gradient-to-r from-pink-500 to-purple-500 rounded-lg text-sm font-semibold text-white hover:scale-105 transition-all">
                                            <span wire:loading.remove wire:target="follow">Follow</span>
                                            <span wire:loading wire:target="follow">...</span>
                                        </button>
                                    @endif
                                    <button wire:click="startConversation"
                                            wire:loading.attr="disabled"
                                            class="inline-flex items-center justify-center px-6 py-1.5 border border-white/20 rounded-lg text-sm font-semibold text-white bg-slate-800/50 hover:bg-slate-700 transition-all">
                                        <span wire:loading.remove wire:target="startConversation">Message</span>
                                        <span wire:loading wire:target="startConversation">...</span>
                                    </button>
                                </div>
                            @endif
                        @endauth
                    </div>

                    <!-- Stats - Instagram Style (inline) -->
                    <div class="hidden md:flex gap-8 mb-4 text-sm">
                        <div>
                            <span class="font-semibold text-white">{{ $postsCount }}</span>
                            <span class="text-gray-400"> posts</span>
                        </div>
                        <div>
                            <span class="font-semibold text-white">{{ $followersCount }}</span>
                            <span class="text-gray-400"> followers</span>
                        </div>
                        <div>
                            <span class="font-semibold text-white">{{ $followingCount }}</span>
                            <span class="text-gray-400"> following</span>
                        </div>
                    </div>

                    <!-- Name & Bio -->
                    <div class="space-y-1">
                        <p class="font-semibold text-white text-sm">
                            {{ $user->display_name ?? $user->name }}
                        </p>
                        @if($user->bio)
                            <p class="text-sm text-gray-300 leading-relaxed">
                                {{ $user->bio }}
                            </p>
                        @endif
                        @if($user->location_name)
                            @php
                                $locationParts = explode(',', $user->location_name);
                                $city = trim($locationParts[0] ?? '');
                                $state = trim($locationParts[1] ?? '');
                                $displayLocation = $city;
                                if ($state) {
                                    $displayLocation .= ', ' . $state;
                                }
                            @endphp
                            <p class="text-sm text-gray-400 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                </svg>
                                {{ $displayLocation }}
                            </p>
                        @endif
                    </div>

                    <!-- Interests -->
                    @if($user->interests && count($user->interests) > 0)
                        <div class="mt-3 flex flex-wrap gap-1.5">
                            @foreach($user->interests as $interest)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-purple-500/10 text-purple-300 border border-purple-500/20">
                                    {{ $interest }}
                                </span>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <!-- Mobile Stats -->
            <div class="md:hidden grid grid-cols-3 gap-4 mt-6 pt-4 border-t border-white/10">
                <div class="text-center">
                    <div class="font-semibold text-white">{{ $postsCount }}</div>
                    <div class="text-xs text-gray-400">posts</div>
                </div>
                <div class="text-center">
                    <div class="font-semibold text-white">{{ $followersCount }}</div>
                    <div class="text-xs text-gray-400">followers</div>
                </div>
                <div class="text-center">
                    <div class="font-semibold text-white">{{ $followingCount }}</div>
                    <div class="text-xs text-gray-400">following</div>
                </div>
            </div>
        </div>
        </div>

        <!-- Content Tabs - Instagram Style -->
        <div class="bg-slate-800/50 rounded-2xl border border-white/5 overflow-hidden">
            <!-- Tab Headers -->
            <div class="flex justify-center border-b border-white/10 bg-slate-800/30">
                <button wire:click="switchTab('posts')"
                        class="flex-1 max-w-[120px] py-3 border-t-2 font-medium text-xs uppercase tracking-wider transition-all {{ $activeTab === 'posts' ? 'border-white text-white' : 'border-transparent text-gray-500' }}">
                    <div class="flex items-center justify-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                        </svg>
                        <span class="hidden sm:inline">Posts</span>
                    </div>
                </button>
                <button wire:click="switchTab('hosted')"
                        class="flex-1 max-w-[120px] py-3 border-t-2 font-medium text-xs uppercase tracking-wider transition-all {{ $activeTab === 'hosted' ? 'border-white text-white' : 'border-transparent text-gray-500' }}">
                    <div class="flex items-center justify-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <span class="hidden sm:inline">Events</span>
                    </div>
                </button>
                <button wire:click="switchTab('attending')"
                        class="flex-1 max-w-[120px] py-3 border-t-2 font-medium text-xs uppercase tracking-wider transition-all {{ $activeTab === 'attending' ? 'border-white text-white' : 'border-transparent text-gray-500' }}">
                    <div class="flex items-center justify-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span class="hidden sm:inline">Going</span>
                    </div>
                </button>
                <button wire:click="switchTab('interested')"
                        class="flex-1 max-w-[120px] py-3 border-t-2 font-medium text-xs uppercase tracking-wider transition-all {{ $activeTab === 'interested' ? 'border-white text-white' : 'border-transparent text-gray-500' }}">
                    <div class="flex items-center justify-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                        </svg>
                        <span class="hidden sm:inline">Saved</span>
                    </div>
                </button>
            </div>

            <!-- Tab Content -->
            <div class="py-4">
                @if($activeTab === 'posts')
                    @if(isset($posts) && $posts->count() > 0)
                        <div class="space-y-2 px-4 py-2">
                            @foreach($posts as $post)
                                <a href="{{ route('posts.show', $post->id) }}" class="block bg-slate-700/60 border border-white/15 rounded-lg p-4 hover:bg-slate-700/75 hover:border-pink-500/40 transition-all group">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="flex-1 min-w-0">
                                            <h3 class="text-sm font-semibold text-white group-hover:text-pink-400 transition-colors">
                                                {{ $post->title }}
                                            </h3>
                                            @if($post->description)
                                                <p class="mt-1 text-xs text-gray-400 line-clamp-1">
                                                    {{ $post->description }}
                                                </p>
                                            @endif
                                            <div class="mt-2 flex items-center gap-3 text-xs text-gray-500">
                                                <span>{{ $post->created_at->diffForHumans() }}</span>
                                                @if($post->location_name)
                                                    <span class="truncate max-w-[100px]">{{ explode(',', $post->location_name)[0] }}</span>
                                                @endif
                                                <span>{{ $post->reaction_count ?? 0 }} reactions</span>
                                            </div>
                                        </div>
                                        @if($post->status === 'active')
                                            <div class="flex-shrink-0 w-2 h-2 rounded-full bg-green-500 ring-2 ring-green-500/30"></div>
                                        @endif
                                    </div>
                                </a>
                            @endforeach
                        </div>
                        <div class="mt-6">
                            {{ $posts->links() }}
                        </div>
                    @else
                        <div class="text-center py-16">
                            <svg class="mx-auto h-12 w-12 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                            </svg>
                            <h3 class="mt-4 text-sm font-medium text-gray-400">No posts yet</h3>
                            <p class="mt-1 text-xs text-gray-500">{{ auth()->id() === $user->id ? "You haven't created any posts yet." : "This user hasn't created any posts yet." }}</p>
                        </div>
                    @endif
                @elseif($activeTab === 'hosted' || $activeTab === 'attending')
                    @if(isset($activities) && $activities->count() > 0)
                        <div class="space-y-2 px-4 py-2">
                            @foreach($activities as $activity)
                                <a href="{{ route('events.show', $activity->id) }}" class="block bg-slate-700/60 border border-white/15 rounded-lg p-4 hover:bg-slate-700/75 hover:border-purple-500/40 transition-all group">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="flex-1 min-w-0">
                                            <h3 class="text-sm font-semibold text-white group-hover:text-purple-400 transition-colors">
                                                {{ $activity->title }}
                                            </h3>
                                            @if($activity->description)
                                                <p class="mt-1 text-xs text-gray-400 line-clamp-1">
                                                    {{ $activity->description }}
                                                </p>
                                            @endif
                                            <div class="mt-2 flex items-center gap-3 text-xs text-gray-500">
                                                @if($activity->start_time)
                                                    <span>{{ $activity->start_time->format('M j') }}</span>
                                                @endif
                                                @if($activity->location_name)
                                                    <span class="truncate max-w-[100px]">{{ explode(',', $activity->location_name)[0] }}</span>
                                                @endif
                                                <span>{{ $activity->current_attendees ?? 0 }} going</span>
                                            </div>
                                        </div>
                                        @if($activity->status === 'published')
                                            <div class="flex-shrink-0 w-2 h-2 rounded-full bg-green-500 ring-2 ring-green-500/30"></div>
                                        @endif
                                    </div>
                                </a>
                            @endforeach
                        </div>
                        <div class="mt-6">
                            {{ $activities->links() }}
                        </div>
                    @else
                        <div class="text-center py-16">
                            <svg class="mx-auto h-12 w-12 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <h3 class="mt-4 text-sm font-medium text-gray-400">No events yet</h3>
                            <p class="mt-1 text-xs text-gray-500">
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
