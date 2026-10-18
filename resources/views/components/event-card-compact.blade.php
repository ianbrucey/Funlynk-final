@props(['event'])

<div class="relative rounded-[18px] p-4 h-full flex flex-col group"
     style="background: linear-gradient(180deg, rgba(255,255,255,0.03), rgba(255,255,255,0.01)), #111933; border: 1px solid rgba(255,255,255,0.08); border-left: 3px solid #f0abfc; box-shadow: 0 12px 30px rgba(0,0,0,0.35);">

    {{-- Type Badge --}}
    <span class="absolute top-3 left-3 z-10 px-2.5 py-1 rounded-full text-[0.7rem] font-bold" style="background: rgba(240,171,252,0.95); color: #3b0764;">EVENT · {{ $event['start_time_human'] }}</span>

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
    <div class="flex items-center gap-3 mt-7">
        {{-- Avatar --}}
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

        {{-- Host & Role --}}
        <div class="flex-1 min-w-0">
            <p class="font-semibold text-white leading-tight truncate">
                {{ $event['author_name'] }}
            </p>
            <p class="text-xs" style="color: #8a93b2;">Hosting</p>
        </div>

        {{-- Price Badge --}}
        <span class="text-xs font-semibold" style="color: #8a93b2;">
            @if($event['is_paid'])
                ${{ number_format($event['price'], 2) }}
            @else
                Free
            @endif
        </span>
    </div>

    {{-- Event Image --}}
    @if(!empty($event['images']))
        <div class="relative mt-3 -mx-4 overflow-hidden aspect-video">
            <img src="{{ \App\Services\ImageProcessor::url($event['images'][0]) }}"
                 class="w-full h-full object-cover cursor-pointer"
                 alt="{{ $event['title'] }}"
                 onclick="window.location.href='{{ route('events.show', $event['id']) }}'">
            @if(count($event['images']) > 1)
                <div class="absolute bottom-2 right-2 px-2 py-1 rounded-full text-xs text-white"
                     style="background: rgba(15,23,42,0.8); backdrop-filter: blur(4px);">
                    +{{ count($event['images']) - 1 }}
                </div>
            @endif
        </div>
    @else
        <div class="relative mt-3 -mx-4 aspect-video flex items-center justify-center"
             style="background: rgba(255,255,255,0.03);">
            <span class="text-3xl opacity-40">📅</span>
        </div>
    @endif

    {{-- Card Body --}}
    <div class="mt-3 flex-1 cursor-pointer" onclick="window.location.href='{{ route('events.show', $event['id']) }}'">
        <h3 class="text-[1.1rem] font-bold text-white mb-1.5 line-clamp-2">{{ $event['title'] }}</h3>

        {{-- Meta Row --}}
        <div class="flex flex-wrap items-center gap-2 text-[0.85rem]" style="color: #8a93b2;">
            <span class="py-1 px-2.5 rounded-full"
                  style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.08);">
                📅 {{ $event['start_time_human'] }}
            </span>
            @if($event['location_name'])
                <span class="py-1 px-2.5 rounded-full"
                      style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.08);">
                    📍 {{ Str::limit($event['location_name'], 25) }}
                </span>
            @endif
        </div>
    </div>

    {{-- Card Actions --}}
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

    {{-- Footer --}}
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
</div>
