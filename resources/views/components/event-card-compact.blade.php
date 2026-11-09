@props(['event'])

@php
    $isListing = $event['is_listing'] ?? false;
    $showFollow = ! $isListing && ! ($event['is_own'] ?? false) && ! empty($event['host_id']);
@endphp

<div class="relative rounded-[18px] p-4 h-full flex flex-col group"
     style="background: linear-gradient(180deg, rgba(255,255,255,0.03), rgba(255,255,255,0.01)), #111933; border: 1px solid rgba(255,255,255,0.08); border-left: 3px solid #f0abfc; box-shadow: 0 12px 30px rgba(0,0,0,0.35);">

    {{-- Type Badge --}}
    @if($isListing)
        <span class="absolute top-3 left-3 z-10 px-2.5 py-1 rounded-full text-[0.7rem] font-bold" style="background: rgba(232,161,58,0.95); color: #451a03;">AROUND TOWN · {{ $event['start_time_human'] }}</span>
    @else
        <span class="absolute top-3 left-3 z-10 px-2.5 py-1 rounded-full text-[0.7rem] font-bold" style="background: rgba(240,171,252,0.95); color: #3b0764;">EVENT · {{ $event['start_time_human'] }}</span>
    @endif

    {{-- Converted Badge --}}
    @if($event['converted_from_post'])
        <div class="absolute top-3 right-3 z-10">
            <span class="px-2 py-0.5 rounded-full text-xs font-bold text-purple-300"
                  style="background: rgba(139,92,246,0.3); border: 1px solid rgba(139,92,246,0.5);">
                ⭐
            </span>
        </div>
    @endif

    {{-- Card Header --}}
    @if($isListing)
    <div class="flex items-center gap-3 mt-7">
        <div class="w-11 h-11 rounded-full grid place-items-center font-bold text-white shrink-0"
             style="background: linear-gradient(135deg, #e8a13a, #b45309);">
            {{ strtoupper(substr($event['source_name'] ?? 'Around town', 0, 1)) }}
        </div>
        <div class="flex-1 min-w-0">
            <p class="font-semibold text-white leading-tight truncate">{{ $event['source_name'] ?? 'Around town' }}</p>
            <p class="text-xs truncate" style="color: #8a93b2;">Listed by FunLynk</p>
            @if(! empty($event['source_url']))
                <a href="{{ $event['source_url'] }}" target="_blank" rel="noopener noreferrer" onclick="event.stopPropagation()"
                   class="text-xs hover:underline" style="color: #67e8f9;">at {{ $event['source_name'] ?? 'the source' }} ↗</a>
            @endif
        </div>
        @if(($event['price'] ?? null) !== null)
            <span class="text-xs font-semibold" style="color: #8a93b2;">${{ number_format($event['price'], 2) }}</span>
        @endif
    </div>
    @else
    <div class="flex items-center gap-3 mt-7">
        {{-- Avatar --}}
        <a @if(!empty($event['author_url'])) href="{{ $event['author_url'] }}" @endif class="block shrink-0 transition hover:opacity-80" onclick="event.stopPropagation()">
            @if($event['author_image_url'])
                <img
                    src="{{ \App\Services\ImageProcessor::url($event['author_image_url']) }}"
                    alt="{{ $event['author_name'] }}"
                    class="w-11 h-11 rounded-full object-cover"
                >
            @else
                <div class="w-11 h-11 rounded-full grid place-items-center font-bold text-white"
                     style="background: linear-gradient(135deg, #06b6d4, #3b82f6);">
                    {{ strtoupper(substr($event['author_name'], 0, 1)) }}
                </div>
            @endif
        </a>

        {{-- Host & Role --}}
        <div class="flex-1 min-w-0">
            <a @if(!empty($event['author_url'])) href="{{ $event['author_url'] }}" @endif class="block font-semibold text-white leading-tight truncate hover:text-cyan-400 transition" onclick="event.stopPropagation()">
                {{ $event['author_name'] }}
            </a>
            <p class="text-xs truncate" style="color: #8a93b2;">
                Hosting
                @if(!empty($event['group_name']))
                    · in <a href="{{ $event['group_url'] }}" class="text-cyan-400 hover:underline" onclick="event.stopPropagation()">{{ $event['group_name'] }}</a>
                @endif
            </p>
        </div>

        @if($showFollow)
            <x-fl.follow-pill :host-id="$event['host_id']" :name="$event['author_name']" :following="$event['is_following'] ?? false" variant="icon" wire:key="follow-{{ $event['id'] }}" />
        @else
            {{-- Price Badge --}}
            <span class="text-xs font-semibold" style="color: #8a93b2;">
                @if($event['is_paid'])
                    ${{ number_format($event['price'], 2) }}
                @else
                    Free
                @endif
            </span>
        @endif
    </div>
    @endif

    {{-- Event Image: own image, then group cover, then group avatar, then placeholder --}}
    @php
        $heroImage = ! empty($event['images']) ? $event['images'][0] : ($event['group_cover_url'] ?? null);
        $heroAvatar = $heroImage ? null : ($event['group_avatar_url'] ?? null);
        $dateStrip = $event['start_time_strip'] ?? $event['start_time_human'];
    @endphp
    <div class="relative mt-3 -mx-4 overflow-hidden aspect-video cursor-pointer"
         @if($heroImage || $heroAvatar) @else style="background: rgba(255,255,255,0.03);" @endif
         onclick="window.location.href='{{ route('events.show', $event['id']) }}'">
        @if($heroImage)
            <img src="{{ \App\Services\ImageProcessor::url($heroImage) }}"
                 class="w-full h-full object-cover"
                 alt="{{ $event['title'] }}">
        @elseif($heroAvatar)
            <img src="{{ \App\Services\ImageProcessor::url($heroAvatar) }}" alt="" aria-hidden="true"
                 class="absolute inset-0 w-full h-full object-cover scale-125 blur-2xl brightness-50">
            <div class="absolute inset-0 grid place-items-center">
                <img src="{{ \App\Services\ImageProcessor::url($heroAvatar) }}"
                     class="w-[38%] aspect-square rounded-full object-cover shadow-xl"
                     style="border: 3px solid rgba(255,255,255,0.35);"
                     alt="{{ $event['group_name'] ?? $event['title'] }}">
            </div>
        @else
            <div class="w-full h-full flex items-center justify-center">
                <span class="text-3xl opacity-40">📅</span>
            </div>
        @endif

        @if(! empty($event['images']) && count($event['images']) > 1)
            <div class="absolute top-2 right-2 px-2 py-1 rounded-full text-xs text-white"
                 style="background: rgba(15,23,42,0.8); backdrop-filter: blur(4px);">
                +{{ count($event['images']) - 1 }}
            </div>
        @endif

        @if($dateStrip)
            <div class="absolute inset-x-0 bottom-0 px-3.5 pt-7 pb-2.5 text-[0.95rem] font-bold pointer-events-none"
                 style="background: linear-gradient(to top, rgba(0,0,0,0.88), rgba(0,0,0,0)); color: #f5f5f5; text-shadow: 0 1px 3px rgba(0,0,0,0.6);">
                {{ $dateStrip }}
            </div>
        @endif
    </div>

    {{-- Card Body --}}
    <div class="mt-3 flex-1 cursor-pointer" onclick="window.location.href='{{ route('events.show', $event['id']) }}'">
        <h3 class="text-[1.1rem] font-bold text-white mb-1.5 line-clamp-2">{{ $event['title'] }}</h3>

        {{-- Meta Row --}}
        <div class="flex flex-wrap items-center gap-2 text-[0.85rem]" style="color: #8a93b2;">
            @if($showFollow)
                <span class="py-1 px-2.5 rounded-full" style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.08);">
                    {{ $event['is_paid'] ? '$'.number_format($event['price'], 2) : 'Free' }}
                </span>
            @endif
            @if(isset($event['distance_km']))
                <span class="py-1 px-2.5 rounded-full" style="background: rgba(34,211,238,0.12); border: 1px solid rgba(34,211,238,0.3); color: #67e8f9;">
                    {{ number_format($event['distance_km'] * 0.621371, 1) }} mi
                </span>
            @endif
            @if($event['location_name'])
                <span class="py-1 px-2.5 rounded-full"
                      style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.08);">
                    📍 {{ Str::limit($event['location_name'], 25) }}
                </span>
            @endif
        </div>
    </div>

    {{-- Card Actions --}}
    @if($isListing)
    <div class="mt-3.5">
        <livewire:listings.interest-toggle
            :activity-id="$event['id']"
            :interested="$event['viewer_interested'] ?? false"
            :count="$event['interest_count'] ?? 0"
            :key="'interest-'.$event['id']" />
    </div>
    @else
    <div class="grid gap-2.5 mt-3.5" style="grid-template-columns: 1fr auto;">
        <button
            onclick="event.stopPropagation(); window.location.href='{{ route('events.show', $event['id']) }}'"
            class="py-3 rounded-xl font-semibold cursor-pointer transition-all hover:scale-[1.02]"
            style="background: linear-gradient(90deg, #06b6d4, #3b82f6); border: none; color: white;">
            🎟️ RSVP
        </button>
        <button
            wire:click.stop="$dispatch('openActivityInviteModal', { activityId: '{{ $event['id'] }}' })"
            class="px-3.5 py-3 rounded-xl cursor-pointer transition-all hover:bg-white/5"
            style="background: transparent; border: 1px solid rgba(255,255,255,0.08); color: #eef1ff;">
            Invite
        </button>
    </div>
    @endif

    {{-- Footer --}}
    @if($isListing)
    <div class="flex justify-between items-center mt-3 pt-3 text-[0.8rem] border-t border-white/10" style="color: #8a93b2;">
        <span>Source: {{ $event['source_name'] ?? 'Organizer' }}</span>
        <span><span class="font-semibold" style="color: #e8a13a;">{{ $event['interest_count'] ?? 0 }}</span> going</span>
    </div>
    @else
    <div class="flex justify-between items-center mt-3 pt-3 text-[0.8rem] border-t border-white/10" style="color: #8a93b2;">
        <a href="{{ route('events.show', $event['id']) }}" class="flex items-center gap-1.5 hover:text-cyan-400 transition">
            💬 Group Chat
        </a>
        <span>
            @if($event['max_attendees'])
                <span class="text-cyan-400 font-semibold">{{ $event['spots_remaining'] }}</span> spots left
            @else
                <span class="text-cyan-400 font-semibold">{{ $event['rsvp_count'] }}</span> going
            @endif
        </span>
    </div>
    @endif
</div>
