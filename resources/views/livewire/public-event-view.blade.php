<div>
    <x-social-meta :activity="$activity" />

    {{-- Hero Section with Cover Image --}}
    <div class="relative h-[60vh] min-h-[400px] overflow-hidden">
        @if($activity->cover_image_url)
            <img src="{{ $activity->cover_image_url }}" alt="{{ $activity->title }}" class="absolute inset-0 w-full h-full object-cover">
            <div class="absolute inset-0 bg-gradient-to-b from-transparent via-black/50 to-black"></div>
        @else
            <div class="absolute inset-0 bg-gradient-to-br from-purple-900/50 via-blue-900/50 to-pink-900/50"></div>
        @endif

        {{-- Hero Content --}}
        <div class="relative z-10 h-full flex items-end">
            <div class="container mx-auto px-6 pb-12">
                <div class="max-w-4xl">
                    {{-- Event Category Badge --}}
                    @if($activity->tags->isNotEmpty())
                        <div class="mb-4">
                            <span class="px-4 py-2 bg-pink-500/20 border border-pink-500/50 rounded-full text-pink-300 text-sm font-semibold backdrop-blur-sm">
                                {{ $activity->tags->first()->name }}
                            </span>
                        </div>
                    @endif

                    {{-- Title --}}
                    <h1 class="text-5xl md:text-6xl font-bold text-white mb-4 drop-shadow-lg">
                        {{ $activity->title }}
                    </h1>

                    {{-- Host Info --}}
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-12 h-12 rounded-full bg-gradient-to-br from-pink-500 to-purple-500 flex items-center justify-center text-white font-bold text-lg">
                            {{ substr($activity->host->display_name, 0, 1) }}
                        </div>
                        <div>
                            <p class="text-white font-semibold">{{ $activity->host->display_name }}</p>
                            <p class="text-gray-300 text-sm">Event Host</p>
                        </div>
                    </div>

                    {{-- Quick Stats --}}
                    <div class="flex flex-wrap gap-6 text-white">
                        <div class="flex items-center gap-2">
                            <span class="text-2xl">👥</span>
                            <div>
                                <p class="font-bold text-lg">{{ $rsvpCount }}</p>
                                <p class="text-sm text-gray-300">Going</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-2xl">💫</span>
                            <div>
                                <p class="font-bold text-lg">{{ $interestedCount }}</p>
                                <p class="text-sm text-gray-300">Interested</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-2xl">🔗</span>
                            <div>
                                <p class="font-bold text-lg">{{ $shareCount }}</p>
                                <p class="text-sm text-gray-300">Shares</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Flash Messages --}}
    @if (session()->has('message'))
        <div class="container mx-auto px-6 mt-6">
            <div class="p-4 glass-card border border-cyan-500/50 text-cyan-400 max-w-4xl mx-auto">
                {{ session('message') }}
            </div>
        </div>
    @endif

    {{-- Main Content --}}
    <div class="container mx-auto px-6 py-12">
        <div class="max-w-4xl mx-auto">
            {{-- CTA Buttons (Sticky on Mobile) --}}
            <div class="sticky top-4 z-20 mb-8 flex flex-wrap gap-4 p-4 glass-card">
                <button wire:click="rsvp" class="flex-1 min-w-[200px] px-8 py-4 bg-gradient-to-r from-pink-500 to-purple-500 rounded-xl font-bold text-lg hover:scale-105 transition-all shadow-lg">
                    🎟️ Get Tickets
                </button>
                <button wire:click="showInterestModal = true" class="flex-1 min-w-[200px] px-8 py-4 bg-slate-800/80 border-2 border-white/20 rounded-xl font-semibold hover:border-cyan-500/50 transition">
                    💫 I'm Interested
                </button>
                <button wire:click="bookmark" class="px-6 py-4 bg-slate-800/80 border-2 border-white/20 rounded-xl hover:border-cyan-500/50 transition">
                    🔖
                </button>
            </div>

            {{-- Event Details Cards --}}
            <div class="grid md:grid-cols-2 gap-6 mb-12">
                {{-- Date & Time Card --}}
                <div class="relative p-6 glass-card">
                    <div class="top-accent-center"></div>
                    <div class="flex items-start gap-4">
                        <div class="w-16 h-16 rounded-xl bg-gradient-to-br from-pink-500 to-purple-500 flex flex-col items-center justify-center text-white">
                            <span class="text-2xl font-bold">{{ $activity->start_time->format('d') }}</span>
                            <span class="text-xs uppercase">{{ $activity->start_time->format('M') }}</span>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-white mb-2">📅 Date & Time</h3>
                            <p class="text-gray-300 font-medium">{{ $activity->start_time->format('l, F j, Y') }}</p>
                            <p class="text-gray-400">
                                {{ $activity->start_time->format('g:i A') }}
                                @if($activity->end_time)
                                    - {{ $activity->end_time->format('g:i A') }}
                                @endif
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Location Card --}}
                <div class="relative p-6 glass-card">
                    <div class="top-accent-center"></div>
                    <div class="flex items-start gap-4">
                        <div class="w-16 h-16 rounded-xl bg-gradient-to-br from-cyan-500 to-blue-500 flex items-center justify-center text-white text-3xl">
                            📍
                        </div>
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-white mb-2">Location</h3>
                            <p class="text-gray-300 font-medium">{{ $activity->location_name }}</p>
                            @if($activity->location_address)
                                <p class="text-gray-400 text-sm">{{ $activity->location_address }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Price Card --}}
            @if($activity->price > 0)
                <div class="relative p-8 glass-card border-2 border-purple-500/30 mb-12">
                    <div class="top-accent-center"></div>
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-2xl font-bold text-white mb-2">💳 Ticket Price</h3>
                            <p class="text-gray-400">Per person admission</p>
                        </div>
                        <div class="text-right">
                            <p class="text-5xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-pink-500 to-purple-500">
                                ${{ number_format($activity->price, 2) }}
                            </p>
                        </div>
                    </div>
                </div>
            @else
                <div class="relative p-8 glass-card border-2 border-green-500/30 mb-12">
                    <div class="top-accent-center"></div>
                    <div class="flex items-center gap-4">
                        <span class="text-6xl">🎉</span>
                        <div>
                            <h3 class="text-2xl font-bold text-white mb-1">Free Event!</h3>
                            <p class="text-gray-400">No ticket required - just show up</p>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Description Section --}}
            <div class="relative p-8 glass-card mb-12">
                <div class="top-accent-center"></div>
                <h2 class="text-3xl font-bold text-white mb-6">About This Event</h2>
                <div class="text-gray-300 text-lg leading-relaxed whitespace-pre-line">
                    {{ $activity->description }}
                </div>
            </div>

            {{-- What to Bring --}}
            @if($activity->what_to_bring)
                <div class="relative p-6 glass-card mb-12">
                    <h3 class="text-xl font-semibold text-white mb-4">🎒 What to Bring</h3>
                    <p class="text-gray-300 leading-relaxed whitespace-pre-line">{{ $activity->what_to_bring }}</p>
                </div>
            @endif

            {{-- Additional Notes --}}
            @if($activity->additional_notes)
                <div class="relative p-6 glass-card mb-12">
                    <h3 class="text-xl font-semibold text-white mb-4">📝 Additional Notes</h3>
                    <p class="text-gray-300 leading-relaxed whitespace-pre-line">{{ $activity->additional_notes }}</p>
                </div>
            @endif

            {{-- Tags Section --}}
            @if($activity->tags->isNotEmpty())
                <div class="relative p-6 glass-card mb-12">
                    <h3 class="text-xl font-semibold text-white mb-4">🏷️ Event Tags</h3>
                    <div class="flex flex-wrap gap-3">
                        @foreach($activity->tags as $tag)
                            <span class="px-4 py-2 bg-gradient-to-r from-pink-500/20 to-purple-500/20 border border-pink-500/30 rounded-full text-pink-300 font-medium">
                                #{{ $tag->name }}
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Share Section --}}
            <div class="relative p-8 glass-card">
                <div class="top-accent-center"></div>
                <h3 class="text-2xl font-bold text-white mb-6 text-center">📢 Share This Event</h3>
                <p class="text-gray-400 text-center mb-6">Help spread the word and invite your friends!</p>
                <div class="flex flex-wrap justify-center gap-4">
                    <button wire:click="share('instagram')" class="px-6 py-3 bg-gradient-to-br from-purple-600 to-pink-600 rounded-xl font-semibold hover:scale-105 transition-all shadow-lg">
                        📷 Instagram
                    </button>
                    <button wire:click="share('facebook')" class="px-6 py-3 bg-blue-600 rounded-xl font-semibold hover:scale-105 transition-all shadow-lg">
                        👍 Facebook
                    </button>
                    <button wire:click="share('twitter')" class="px-6 py-3 bg-sky-500 rounded-xl font-semibold hover:scale-105 transition-all shadow-lg">
                        🐦 Twitter
                    </button>
                    <button wire:click="share('whatsapp')" class="px-6 py-3 bg-green-600 rounded-xl font-semibold hover:scale-105 transition-all shadow-lg">
                        💬 WhatsApp
                    </button>
                    <button wire:click="share('copy_link')" class="px-6 py-3 bg-slate-700 rounded-xl font-semibold hover:scale-105 transition-all shadow-lg">
                        🔗 Copy Link
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Interest Modal --}}
    @if($showInterestModal)
        <div class="fixed inset-0 bg-black/80 backdrop-blur-sm flex items-center justify-center z-50" wire:click.self="showInterestModal = false">
            <div class="relative p-8 glass-card max-w-md mx-4">
                <div class="top-accent-center"></div>

                <h2 class="text-2xl font-bold text-white mb-4">Stay Updated</h2>
                <p class="text-gray-300 mb-6">Enter your email to receive updates and reminders about this event.</p>

                <form wire:submit="expressInterest">
                    <div class="mb-4">
                        <input
                            type="email"
                            wire:model="email"
                            placeholder="your@email.com"
                            class="w-full px-4 py-3 bg-slate-900/50 border border-white/10 rounded-xl text-white placeholder-gray-500 focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/50 transition"
                        >
                        @error('email') <span class="text-red-400 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex gap-3">
                        <button type="submit" class="flex-1 px-6 py-3 bg-gradient-to-r from-pink-500 to-purple-500 rounded-xl font-semibold hover:scale-105 transition-all">
                            Submit
                        </button>
                        <button type="button" wire:click="showInterestModal = false" class="px-6 py-3 bg-slate-800/50 border border-white/10 rounded-xl hover:border-cyan-500/50 transition">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Share URL Handler Script --}}
    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('share-url-generated', (event) => {
                const { platform, url } = event[0];

                if (platform === 'copy_link') {
                    navigator.clipboard.writeText(url);
                    alert('Link copied to clipboard!');
                } else {
                    window.open(url, '_blank');
                }
            });
        });
    </script>
</div>
