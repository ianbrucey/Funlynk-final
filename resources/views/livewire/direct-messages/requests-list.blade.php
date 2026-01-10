<div class="space-y-3">
    {{-- Flash Messages --}}
    @if (session()->has('message'))
        <div class="p-4 glass-card rounded-xl border-cyan-500/50 bg-cyan-500/10">
            <p class="text-sm text-cyan-300">{{ session('message') }}</p>
        </div>
    @endif

    @forelse($requests as $request)
        <div class="relative p-5 glass-card rounded-xl border-white/10 hover:border-purple-500/50 transition-all duration-300">
            <div class="flex items-start gap-4">
                {{-- Avatar --}}
                <a href="{{ route('profile.view', $request['sender']['username']) }}"
                   class="flex-shrink-0 group">
                    <img src="{{ $request['sender']['avatar'] }}"
                         alt="{{ $request['sender']['name'] }}"
                         class="w-14 h-14 rounded-full border-2 border-white/20 object-cover group-hover:border-purple-500/50 transition">
                </a>

                {{-- Content --}}
                <div class="flex-1 min-w-0">
                    {{-- Header --}}
                    <div class="flex items-start justify-between mb-2">
                        <div>
                            <a href="{{ route('profile.view', $request['sender']['username']) }}"
                               class="text-white font-semibold hover:text-purple-400 transition">
                                {{ $request['sender']['name'] }}
                            </a>
                            <p class="text-sm text-gray-400">@{{ $request['sender']['username'] }}</p>
                        </div>
                        <span class="text-xs text-gray-500 flex-shrink-0 ml-2">
                            {{ $request['received_at']->diffForHumans() }}
                        </span>
                    </div>

                    {{-- Message Preview --}}
                    <p class="text-sm text-gray-300 mb-4 line-clamp-2">
                        {{ $request['first_message'] }}
                    </p>

                    {{-- Action Buttons --}}
                    <div class="flex gap-3">
                        <button wire:click="acceptRequest('{{ $request['id'] }}')"
                                wire:loading.attr="disabled"
                                wire:target="acceptRequest('{{ $request['id'] }}')"
                                class="flex-1 px-4 py-3 sm:py-2.5 bg-gradient-to-r from-cyan-500 to-blue-500 rounded-lg text-sm font-semibold text-white hover:scale-105 transition-all shadow-lg hover:shadow-cyan-500/50 disabled:opacity-50 disabled:cursor-not-allowed active:scale-95">
                            <span wire:loading.remove wire:target="acceptRequest('{{ $request['id'] }}')">
                                Accept
                            </span>
                            <span wire:loading wire:target="acceptRequest('{{ $request['id'] }}')">
                                Accepting...
                            </span>
                        </button>

                        <button wire:click="declineRequest('{{ $request['id'] }}')"
                                wire:loading.attr="disabled"
                                wire:target="declineRequest('{{ $request['id'] }}')"
                                class="flex-1 px-4 py-3 sm:py-2.5 bg-slate-800/50 border border-white/10 rounded-lg text-sm font-semibold text-gray-300 hover:border-red-500/50 hover:text-red-400 transition-all disabled:opacity-50 disabled:cursor-not-allowed active:scale-95">
                            <span wire:loading.remove wire:target="declineRequest('{{ $request['id'] }}')">
                                Decline
                            </span>
                            <span wire:loading wire:target="declineRequest('{{ $request['id'] }}')">
                                Declining...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @empty
        {{-- Empty State --}}
        <div class="text-center py-16">
            <div class="relative inline-block mb-6">
                <div class="absolute inset-0 bg-gradient-to-r from-purple-500/20 to-pink-500/20 blur-2xl rounded-full"></div>
                <svg class="relative w-20 h-20 mx-auto text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                </svg>
            </div>

            <h3 class="text-xl font-semibold text-white mb-2">No message requests</h3>
            <p class="text-gray-400">You're all caught up!</p>
        </div>
    @endforelse
</div>
