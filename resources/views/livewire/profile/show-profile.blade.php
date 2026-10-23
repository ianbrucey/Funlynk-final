<div class="min-h-screen overflow-x-hidden pb-24 md:pb-8" x-data="{ confirmUnfollow: false }">
    <div class="container mx-auto px-4 py-4 md:py-8 max-w-4xl">
        {{-- Header --}}
        <div class="glass-card p-5 md:p-6 mb-4">
            <div class="flex items-center gap-4 md:gap-8">
                <x-fl.avatar :name="$user->display_name ?? $user->name" :src="$user->profile_image_url"
                             class="h-20 w-20 md:h-32 md:w-32 flex-shrink-0 ring-2 ring-white/10" />
                <div class="min-w-0 flex-1">
                    <h1 class="truncate text-xl md:text-2xl font-bold text-white">{{ $user->username }}</h1>
                    @if($user->location_name)
                        @php $displayLocation = collect(explode(',', $user->location_name))->take(2)->map(fn ($part) => trim($part))->filter()->implode(', '); @endphp
                        <p class="mt-1 flex items-center gap-1 text-sm text-gray-400">
                            <svg class="w-3.5 h-3.5 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                            {{ $displayLocation }}
                        </p>
                    @endif
                    @auth
                        @if(auth()->id() !== $user->id)
                            <div class="hidden md:flex gap-2 mt-4">
                                @include('livewire.profile.partials.actions')
                            </div>
                        @else
                            <a href="{{ route('profile.edit') }}" wire:navigate class="hidden md:inline-flex mt-4 min-h-[44px] items-center px-5 rounded-xl border border-white/15 bg-slate-800/50 text-sm font-semibold text-white hover:border-cyan-400/50 transition">Edit profile</a>
                        @endif
                    @endauth
                </div>
            </div>

            <p class="mt-4 text-sm font-semibold text-white">{{ $user->display_name ?? $user->name }}</p>
            @if($user->bio)
                <p class="mt-1 text-sm leading-relaxed text-gray-300">{{ $user->bio }}</p>
            @endif
            @if($user->interests && count($user->interests) > 0)
                <div class="mt-3 flex flex-wrap gap-1.5">
                    @foreach($user->interests as $interest)
                        <span class="inline-flex items-center rounded-full border border-purple-500/25 bg-purple-500/10 px-2.5 py-0.5 text-xs font-medium text-purple-300">{{ $interest }}</span>
                    @endforeach
                </div>
            @endif

            @auth
                <div class="mt-4 flex gap-2 md:hidden">
                    @if(auth()->id() === $user->id)
                        <a href="{{ route('profile.edit') }}" wire:navigate class="flex min-h-[44px] flex-1 items-center justify-center rounded-xl border border-white/15 bg-slate-800/50 text-sm font-semibold text-white">Edit profile</a>
                    @else
                        @include('livewire.profile.partials.actions')
                    @endif
                </div>
            @endauth

            <div class="mt-4 grid grid-cols-2 border-t border-white/10 pt-3 text-center">
                <a href="{{ route('profile.followers', $user->username) }}" wire:navigate class="group flex min-h-[44px] flex-col items-center justify-center rounded-xl hover:bg-white/5"><span class="font-semibold text-white group-hover:text-cyan-300">{{ $followersCount }}</span><span class="text-xs text-gray-400">followers</span></a>
                <a href="{{ route('profile.following', $user->username) }}" wire:navigate class="group flex min-h-[44px] flex-col items-center justify-center rounded-xl hover:bg-white/5"><span class="font-semibold text-white group-hover:text-cyan-300">{{ $followingCount }}</span><span class="text-xs text-gray-400">following</span></a>
            </div>
        </div>

        @unless($canViewContent)
            <div class="glass-card px-4 py-14 text-center">
                <div class="text-4xl" aria-hidden="true">🔒</div>
                <h3 class="mt-3 text-sm font-medium text-gray-300">This profile is {{ $user->privacy_level === 'followers' ? 'only visible to followers' : 'private' }}</h3>
                @if($user->privacy_level === 'followers')
                    <p class="mt-1 text-xs text-gray-500">Follow {{ $user->username }} to see their posts and events.</p>
                @endif
            </div>
        @else
            @php $tabs = ['posts' => ['Posts', $postsCount], 'hosted' => ['Events', $hostedActivitiesCount], 'attending' => ['Going', $attendedActivitiesCount], 'interested' => ['Saved Posts', $interestedPostsCount]]; @endphp
            <div class="glass-card mb-3 flex gap-1 overflow-x-auto p-1" role="tablist">
                @foreach($tabs as $key => [$label, $count])
                    <button type="button" role="tab" aria-selected="{{ $activeTab === $key ? 'true' : 'false' }}"
                            wire:click="switchTab('{{ $key }}')" wire:key="tab-{{ $key }}"
                            class="min-h-[48px] flex-1 rounded-xl px-3 py-1.5 text-center text-xs leading-tight transition {{ $activeTab === $key ? 'bg-cyan-400/10 text-cyan-300 ring-1 ring-cyan-400/40' : 'text-gray-400 hover:text-gray-200' }}">
                        <span class="block text-base font-semibold {{ $activeTab === $key ? 'text-white' : 'text-gray-200' }}">{{ $count }}</span>
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            <div class="glass-card p-3">
                <div wire:loading wire:target="switchTab" class="grid gap-2 md:grid-cols-2" aria-hidden="true">
                    @for($i = 0; $i < 4; $i++)<div class="h-[76px] animate-pulse rounded-2xl bg-white/10"></div>@endfor
                </div>

                <div wire:loading.remove wire:target="switchTab">
                    @if($activeTab === 'posts')
                        @include('livewire.profile.partials.posts-list')
                    @elseif($activeTab === 'hosted' || $activeTab === 'attending')
                        @include('livewire.profile.partials.events-list')
                    @else
                        <livewire:profile.interested-tab :user="$user" :key="'interested-'.$user->id" />
                    @endif
                </div>
            </div>
        @endunless
    </div>

    <div x-show="confirmUnfollow" x-cloak x-on:keydown.escape.window="confirmUnfollow = false" class="fixed inset-0 z-50 flex items-end justify-center bg-black/60 p-3 md:items-center">
        <div class="glass-card w-full max-w-sm p-5" x-on:click.outside="confirmUnfollow = false">
            <p class="font-semibold text-white">Unfollow {{ $user->username }}?</p>
            <p class="mt-1 text-sm text-gray-300">You will stop seeing their activity in your feed.</p>
            <button type="button" x-on:click="$wire.unfollow(); confirmUnfollow = false" class="mt-4 min-h-[44px] w-full rounded-xl border border-red-400/50 text-sm font-semibold text-red-300">Unfollow</button>
            <button type="button" x-on:click="confirmUnfollow = false" class="mt-2 min-h-[44px] w-full rounded-xl border border-white/15 bg-slate-800/50 text-sm font-semibold text-white">Cancel</button>
        </div>
    </div>
</div>
