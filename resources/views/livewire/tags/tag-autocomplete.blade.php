<div class="relative">
    {{-- Flash Messages --}}
    @if (session()->has('success'))
        <div class="mb-4 p-3 bg-green-500/20 border border-green-500/50 rounded-xl text-green-300 text-sm">
            {{ session('success') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="mb-4 p-3 bg-red-500/20 border border-red-500/50 rounded-xl text-red-300 text-sm">
            {{ session('error') }}
        </div>
    @endif

    {{-- Selected Tags Display --}}
    @if (count($selectedTags) > 0)
        <div class="mb-4 flex flex-wrap gap-2">
            @foreach ($selectedTags as $index => $tag)
                <div class="flex items-center gap-2 px-3 py-1.5 bg-purple-500/20 border border-purple-500/30 rounded-lg text-purple-300 text-sm hover:border-purple-500/50 transition">
                    <span class="font-medium">{{ $tag['name'] }}</span>
                    @if ($tag['category'])
                        <span class="text-xs text-purple-400/70">{{ $tag['category'] }}</span>
                    @endif
                    <button 
                        wire:click="removeTag({{ $index }})"
                        class="ml-1 text-purple-400 hover:text-white transition"
                        type="button">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            @endforeach

            @if (count($selectedTags) > 1)
                <button 
                    wire:click="clearAll"
                    class="px-3 py-1.5 text-xs text-gray-400 hover:text-white border border-white/10 rounded-lg hover:border-red-500/50 transition"
                    type="button">
                    Clear All
                </button>
            @endif
        </div>
    @endif

    {{-- Search Input --}}
    <div class="relative">
        <div class="relative">
            <svg class="absolute left-4 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
            </svg>
            <input 
                type="text" 
                wire:model.live.debounce.300ms="search"
                wire:focus="showSuggestions = true"
                placeholder="Search or create tags..." 
                class="w-full pl-12 pr-4 py-3 bg-slate-800/50 border border-white/10 rounded-2xl focus:border-cyan-500/50 focus:outline-none transition text-white"
                autocomplete="off"
            />
        </div>

        {{-- Tag Limit Indicator --}}
        <div class="mt-2 text-xs text-gray-400">
            {{ count($selectedTags) }} / {{ $maxTags }} tags selected
        </div>

        {{-- Suggestions Dropdown --}}
        @if ($showSuggestions && (count($suggestions) > 0 || strlen($search) >= 2))
            <div class="absolute z-50 w-full mt-2 p-2 bg-slate-800/95 backdrop-blur-lg border border-white/20 rounded-2xl shadow-2xl max-h-64 overflow-y-auto">
                @if (count($suggestions) > 0)
                    {{-- Existing Tags --}}
                    <div class="mb-2">
                        <div class="px-3 py-1 text-xs text-gray-400 font-semibold uppercase tracking-wide">
                            Existing Tags
                        </div>
                        @foreach ($suggestions as $suggestion)
                            <button 
                                wire:click="selectTag('{{ $suggestion['id'] }}')"
                                class="w-full px-3 py-2 text-left rounded-xl hover:bg-purple-500/20 transition flex items-center justify-between group"
                                type="button">
                                <div class="flex items-center gap-2">
                                    <span class="text-white">{{ $suggestion['name'] }}</span>
                                    @if ($suggestion['category'])
                                        <span class="px-2 py-0.5 bg-purple-500/20 text-purple-300 rounded text-xs">
                                            {{ $suggestion['category'] }}
                                        </span>
                                    @endif
                                </div>
                                <span class="text-xs text-gray-400 group-hover:text-gray-300">
                                    {{ $suggestion['usage_count'] }} uses
                                </span>
                            </button>
                        @endforeach
                    </div>
                @endif

                @if (strlen($search) >= 2)
                    {{-- Create New Tag Option --}}
                    <div class="border-t border-white/10 pt-2">
                        <button 
                            wire:click="createAndSelectTag"
                            class="w-full px-3 py-2 text-left rounded-xl hover:bg-cyan-500/20 transition flex items-center gap-2 group"
                            type="button">
                            <svg class="w-4 h-4 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            <span class="text-cyan-400 group-hover:text-cyan-300">
                                Create "<span class="font-semibold">{{ $search }}</span>"
                            </span>
                        </button>
                    </div>
                @endif
            </div>
        @endif
    </div>

    {{-- Helper Text --}}
    <div class="mt-3 text-xs text-gray-400">
        <svg class="inline w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        Type to search existing tags or create new ones. Tags help people discover your activity.
    </div>
</div>
