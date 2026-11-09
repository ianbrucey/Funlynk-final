<div class="mx-auto w-full max-w-3xl px-4 py-6">
    <a href="{{ url()->previous() }}" class="text-sm hover:text-cyan-400 transition" style="color: #8a93b2;">← Back</a>

    <div class="relative mt-4 rounded-[18px] p-5"
         style="background: linear-gradient(180deg, rgba(255,255,255,0.03), rgba(255,255,255,0.01)), #111933; border: 1px solid rgba(255,255,255,0.08); border-left: 3px solid #e8a13a; box-shadow: 0 12px 30px rgba(0,0,0,0.35);">

        <span class="inline-block px-2.5 py-1 rounded-full text-[0.7rem] font-bold"
              style="background: rgba(232,161,58,0.95); color: #451a03;">AROUND TOWN</span>

        <h1 class="mt-3 text-2xl font-bold text-white">{{ $activity->title }}</h1>

        <p class="mt-1 text-sm" style="color: #8a93b2;">
            Listed by FunLynk
            @if($activity->source_name)
                · via {{ $activity->source_name }}
            @endif
        </p>

        {{-- Spec 020: stored image with the date strip over it; the 019
             placeholder stays as the fallback when there is no image. --}}
        <div class="relative mt-4 -mx-5 overflow-hidden aspect-video" style="background: rgba(255,255,255,0.03);">
            @if(! empty($activity->images))
                <img src="{{ \App\Services\ImageProcessor::url($activity->images[0]) }}"
                     alt="{{ $activity->title }}"
                     class="absolute inset-0 w-full h-full object-cover">
            @else
                <div class="w-full h-full flex items-center justify-center">
                    <span class="text-4xl opacity-40">📅</span>
                </div>
            @endif
            <div class="absolute inset-x-0 bottom-0 px-4 pt-7 pb-2.5 text-[0.95rem] font-bold"
                 style="background: linear-gradient(to top, rgba(0,0,0,0.88), rgba(0,0,0,0)); color: #f5f5f5; text-shadow: 0 1px 3px rgba(0,0,0,0.6);">
                {{ optional($activity->start_time)->format('D, M j · g:i A') }}
            </div>
        </div>

        <div class="mt-4 space-y-2 text-[0.95rem]" style="color: #c6cbe0;">
            <p>🕖 {{ optional($activity->start_time)->format('l, F j, Y · g:i A') }}
                @if($activity->end_time)
                    to {{ $activity->end_time->format('g:i A') }}
                @endif
            </p>
            <p>📍 {{ $activity->location_name }}
                @if($activity->location_address)
                    · {{ $activity->location_address }}
                @endif
            </p>
            @if($activity->price_cents !== null)
                <p>🎟️ ${{ number_format($activity->price_cents / 100, 2) }}</p>
            @endif
        </div>

        @if($activity->description)
            <p class="mt-4 whitespace-pre-line leading-relaxed" style="color: #e6e9f5;">{{ $activity->description }}</p>
        @endif

        <div class="mt-5 rounded-xl px-4 py-3 text-sm"
             style="background: rgba(232,161,58,0.10); border: 1px solid rgba(232,161,58,0.35); color: #f3d9ae;">
            FunLynk does not run this event. It is listed here so you can see what is on around town. Details, tickets and changes are handled by the organizer.
        </div>

        <div class="mt-5 flex flex-wrap items-center gap-2.5">
            @auth
                <livewire:listings.interest-toggle
                    :activity-id="$activity->id"
                    :interested="$viewerInterested"
                    :count="$interestCount"
                    :key="'interest-detail-'.$activity->id" />
            @endauth
        </div>

        @if($interestedUsers->isNotEmpty())
            <div class="mt-6 pt-4 border-t border-white/10">
                <h2 class="text-sm font-semibold text-white">Who's going ({{ $interestCount }})</h2>
                <ul class="mt-3 space-y-2">
                    @foreach($interestedUsers as $interestedUser)
                        <li>
                            <a href="{{ route('profile.view', $interestedUser->username) }}"
                               class="flex items-center gap-3 hover:text-cyan-400 transition">
                                @if($interestedUser->profile_image_url)
                                    <img src="{{ \App\Services\ImageProcessor::url($interestedUser->profile_image_url) }}"
                                         alt="{{ $interestedUser->display_name ?? $interestedUser->username }}"
                                         class="w-9 h-9 rounded-full object-cover">
                                @else
                                    <span class="w-9 h-9 rounded-full grid place-items-center font-bold text-white"
                                          style="background: linear-gradient(135deg, #06b6d4, #3b82f6);">
                                        {{ strtoupper(substr($interestedUser->display_name ?? $interestedUser->username ?? '?', 0, 1)) }}
                                    </span>
                                @endif
                                <span class="text-sm" style="color: #e6e9f5;">{{ $interestedUser->display_name ?? $interestedUser->username }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Spec 020 chat section: signed-in users get the listing chat;
             guests get a sign-in prompt in its place. --}}
        <div class="mt-6 pt-4 border-t border-white/10">
            <h2 class="text-sm font-semibold text-white">Chat</h2>
            @auth
                <p class="mt-2 text-sm" style="color: #c6cbe0;">Chat with others going to this event. FunLynk does not run this event; the organizer's page is linked below.</p>
                @if(! $viewerInterested)
                    <p class="mt-2 text-sm" style="color: #f3d9ae;">Mark Going to join the chat.</p>
                @endif
                @if(! $activity->isCurrentListing())
                    <p class="mt-2 text-sm" style="color: #8a93b2;">This event has ended.</p>
                @endif
                @if($activity->status === 'published' && config('listings.enabled', true))
                    <div class="mt-3 h-[480px]">
                        <livewire:chat.chat-component :conversationable="$activity" :key="'listing-chat-'.$activity->id" />
                    </div>
                @endif
            @else
                <p class="mt-2 text-sm" style="color: #c6cbe0;">
                    <a href="{{ route('login') }}" class="hover:underline" style="color: #67e8f9;">Sign in</a> to see the chat.
                </p>
            @endauth
        </div>

        {{-- Spec 020: the source link is demoted to a quiet footer block,
             with the photo credit beside it when the listing has one. --}}
        @if($activity->source_url)
            <div class="mt-6 pt-4 border-t border-white/10 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm" style="color: #8a93b2;">
                <a href="{{ $activity->source_url }}" target="_blank" rel="noopener noreferrer"
                   class="hover:underline" style="color: #67e8f9;">
                    Details at {{ $activity->source_name }} ↗
                </a>
                @if($activity->image_source_url)
                    <span>Photo: <a href="{{ $activity->source_url }}" target="_blank" rel="noopener noreferrer" class="hover:underline">{{ $activity->source_name }}</a></span>
                @endif
            </div>
        @endif
    </div>
</div>
