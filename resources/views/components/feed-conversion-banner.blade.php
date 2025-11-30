@props(['post'])

@if($post->isEligibleForConversion() && !$post->hasReachedDismissLimit() && auth()->check() && auth()->id() === $post->user_id)
    @php
    $threshold = $post->reaction_count >= 10 ? 'strong' : 'soft';
    $dismissed = session()->has("conversion_banner_dismissed_{$post->id}");
    @endphp

    @if(!$dismissed)
        <div class="glass-card p-4 mb-4 border-l-4 {{ $threshold === 'strong' ? 'border-pink-500' : 'border-amber-500' }}">
            <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="text-2xl">
                        {{ $threshold === 'strong' ? '🔥' : '🌟' }}
                    </div>
                    <div>
                        <h4 class="text-white font-semibold">
                            @if($threshold === 'strong')
                                {{ $post->reaction_count }}+ people want to join!
                            @else
                                {{ $post->reaction_count }} people are interested
                            @endif
                        </h4>
                        <p class="text-gray-400 text-sm">
                            @if($threshold === 'strong')
                                Turn this into an event now and start planning!
                            @else
                                Consider creating an event from this post
                            @endif
                        </p>
                    </div>
                </div>

                <div class="flex gap-2">
                    <button
                        wire:click="openConversionModal('{{ $post->id }}')"
                        class="px-4 py-2 bg-gradient-to-r from-pink-500 to-purple-500 rounded-lg text-sm font-semibold hover:scale-105 transition-all whitespace-nowrap">
                        Convert to Event
                    </button>
                    <button
                        wire:click="dismissBanner('{{ $post->id }}')"
                        class="px-3 py-2 bg-slate-800/50 border border-white/10 rounded-lg text-sm hover:border-red-500/50 transition">
                        ✕
                    </button>
                </div>
            </div>
        </div>
    @endif
@endif