<div class="min-h-screen overflow-x-hidden pb-24 md:pb-8">
    <div class="container mx-auto max-w-2xl px-4 py-4 md:py-8">
        <div class="glass-card mb-3 p-4">
            <a href="{{ route('profile.view', $profile->username) }}" wire:navigate class="inline-flex min-h-[44px] items-center gap-1.5 text-sm text-gray-400 hover:text-white">← {{ $profile->username }}</a>
            <h1 class="text-lg font-bold text-white">{{ $profile->display_name ?? $profile->username }}</h1>
            @if($profile->location_name)
                <p class="text-xs text-gray-400">{{ collect(explode(',', $profile->location_name))->take(2)->map(fn ($part) => trim($part))->filter()->implode(', ') }}</p>
            @endif

            <div class="mt-3 grid grid-cols-2 gap-1 rounded-2xl border border-white/10 bg-black/30 p-1" role="tablist">
                @foreach(['followers' => 'Followers', 'following' => 'Following'] as $key => $label)
                    <a href="{{ route('profile.'.$key, $profile->username) }}" wire:navigate role="tab" aria-selected="{{ $tab === $key ? 'true' : 'false' }}"
                       class="flex min-h-[44px] items-center justify-center gap-1.5 rounded-xl text-sm font-semibold transition {{ $tab === $key ? 'bg-gradient-to-r from-pink-500 to-purple-500 text-white' : 'text-gray-400 hover:text-gray-200' }}">
                        <span class="{{ $tab === $key ? 'text-white' : 'text-gray-200' }}">{{ $counts[$key] ?? '–' }}</span> {{ $label }}
                    </a>
                @endforeach
            </div>

            @if($canView)
                <input type="search" wire:model.live.debounce.300ms="search" aria-label="Search this list"
                       placeholder="Search {{ $tab }} by name or @handle"
                       class="mt-3 min-h-[44px] w-full rounded-xl border border-white/10 bg-black/30 px-4 text-sm text-white placeholder-gray-500 focus:border-cyan-400 focus:outline-none focus:ring-2 focus:ring-cyan-400/30">
            @endif
        </div>

        @if(! $canView)
            <div class="glass-card px-4 py-14 text-center">
                <div class="text-4xl" aria-hidden="true">🔒</div>
                <h3 class="mt-3 text-sm font-medium text-gray-300">This profile is {{ $profile->privacy_level === 'followers' ? 'only visible to followers' : 'private' }}</h3>
                @if($profile->privacy_level === 'followers')
                    <p class="mt-1 text-xs text-gray-500">Follow {{ $profile->username }} to see who follows them and who they follow.</p>
                    <a href="{{ route('profile.view', $profile->username) }}" wire:navigate class="mt-4 inline-flex min-h-[44px] items-center rounded-xl bg-gradient-to-r from-pink-500 to-purple-500 px-6 text-sm font-semibold text-white">Go to profile</a>
                @endif
            </div>
        @else
            <div wire:loading.flex wire:target="search" class="glass-card mb-3 flex-col gap-2 p-3" aria-hidden="true">
                @for($i = 0; $i < 4; $i++)<div class="h-14 animate-pulse rounded-2xl bg-white/10"></div>@endfor
            </div>

            <div wire:loading.remove wire:target="search">
                @if($people->isEmpty())
                    <div class="glass-card px-4 py-12 text-center">
                        @if(trim($search) !== '')
                            <div class="text-4xl" aria-hidden="true">🔍</div>
                            <h3 class="mt-3 text-sm font-medium text-gray-300">No one matches "{{ $search }}"</h3>
                            <p class="mt-1 text-xs text-gray-500">Try a different name or handle.</p>
                            <button type="button" wire:click="$set('search', '')" class="mt-4 min-h-[44px] rounded-xl bg-gradient-to-r from-pink-500 to-purple-500 px-6 text-sm font-semibold text-white">Clear search</button>
                        @elseif($tab === 'followers')
                            <div class="text-4xl" aria-hidden="true">🌱</div>
                            <h3 class="mt-3 text-sm font-medium text-gray-300">{{ $isOwner ? 'Nobody follows you yet' : 'No followers yet' }}</h3>
                            <p class="mt-1 text-xs text-gray-500">{{ $isOwner ? 'Host an event or post something and people will find you.' : 'Check back later.' }}</p>
                        @else
                            <div class="text-4xl" aria-hidden="true">🧭</div>
                            <h3 class="mt-3 text-sm font-medium text-gray-300">{{ $isOwner ? 'You are not following anyone yet' : 'Not following anyone yet' }}</h3>
                            <p class="mt-1 text-xs text-gray-500">{{ $isOwner ? 'Follow hosts to get notified when they publish events.' : 'Check back later.' }}</p>
                            @if($isOwner)
                                <a href="{{ route('search.users') }}" wire:navigate class="mt-4 inline-flex min-h-[44px] items-center rounded-xl bg-gradient-to-r from-pink-500 to-purple-500 px-6 text-sm font-semibold text-white">Find people</a>
                            @endif
                        @endif
                    </div>
                @else
                    <div class="glass-card px-2 py-1">
                        @foreach($people as $person)
                            @php $name = $person->display_name ?? $person->username; @endphp
                            <div wire:key="person-{{ $person->id }}" class="flex min-h-[68px] items-center gap-3 border-white/10 px-1.5 py-2.5 {{ $loop->first ? '' : 'border-t' }}">
                                <a href="{{ route('profile.view', $person->username) }}" wire:navigate class="flex min-w-0 flex-1 items-center gap-3">
                                    <x-fl.avatar :name="$name" :src="$person->profile_image_url" class="h-12 w-12 flex-shrink-0" />
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-white">{{ $name }}</p>
                                        <p class="truncate text-xs text-gray-400">{{ '@'.$person->username }}@if($person->location_name) · {{ collect(explode(',', $person->location_name))->take(2)->map(fn ($part) => trim($part))->filter()->implode(', ') }}@endif</p>
                                    </div>
                                </a>
                                @if(auth()->id() !== $person->id)
                                    <x-fl.follow-pill :host-id="$person->id" :name="$name" :following="in_array((string) $person->id, $followedIds, true)" />
                                @endif
                            </div>
                        @endforeach
                    </div>
                    @if($hasMore)
                        <button type="button" wire:click="loadMore" wire:loading.attr="disabled" wire:target="loadMore"
                                class="mt-3 min-h-[44px] w-full rounded-xl border border-white/10 bg-slate-800/50 text-sm font-semibold text-white hover:border-cyan-400/50">
                            <span wire:loading.remove wire:target="loadMore">Load more</span>
                            <span wire:loading wire:target="loadMore">Loading…</span>
                        </button>
                    @endif
                @endif
            </div>
        @endif
    </div>

    <x-fl.follow-modals />
</div>
