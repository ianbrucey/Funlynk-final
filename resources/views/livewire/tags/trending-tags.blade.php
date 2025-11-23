<div>
    <style>
        .gradient-text {
            background: linear-gradient(to right, #fbbf24, #06b6d4);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

       

        .top-accent {
            position: absolute;
            top: 0;
            left: 0;
            width: 8rem;
            height: 0.25rem;
            background: linear-gradient(to right, #ec4899, #8b5cf6, transparent);
            border-radius: 9999px;
        }
    </style>

    <div class="relative p-6 glass-card">
        <div class="top-accent"></div>

        {{-- Header --}}
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-bold flex items-center gap-2">
                <svg class="w-5 h-5 text-pink-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                </svg>
                <span class="gradient-text">Trending Tags</span>
            </h3>
            <div class="text-xs text-gray-400">
                Last {{ $days }} days
            </div>
        </div>

        {{-- Trending Tags List --}}
        @if ($trendingTags->count() > 0)
            <div class="space-y-2">
                @foreach ($trendingTags as $index => $tag)
                    <div 
                        wire:click="tagClicked('{{ $tag->id }}')"
                        class="flex items-center justify-between p-3 rounded-xl bg-slate-800/30 border border-white/10 {{ $clickable ? 'hover:border-purple-500/50 cursor-pointer' : '' }} transition-all group">
                        
                        {{-- Rank & Tag Info --}}
                        <div class="flex items-center gap-3">
                            {{-- Rank Badge --}}
                            <div class="flex-shrink-0 w-8 h-8 rounded-lg flex items-center justify-center font-bold text-sm
                                {{ $index === 0 ? 'bg-gradient-to-br from-yellow-500 to-orange-500 text-white' : '' }}
                                {{ $index === 1 ? 'bg-gradient-to-br from-gray-300 to-gray-400 text-gray-900' : '' }}
                                {{ $index === 2 ? 'bg-gradient-to-br from-orange-600 to-orange-700 text-white' : '' }}
                                {{ $index > 2 ? 'bg-slate-700/50 text-gray-400' : '' }}">
                                {{ $index + 1 }}
                            </div>

                            {{-- Tag Name & Category --}}
                            <div>
                                <div class="font-semibold text-white group-hover:text-purple-300 transition">
                                    {{ $tag->name }}
                                </div>
                                @if ($tag->category)
                                    <div class="text-xs text-gray-400">
                                        {{ ucfirst($tag->category) }}
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Usage Count --}}
                        @if ($showUsageCount)
                            <div class="flex items-center gap-2">
                                <div class="text-right">
                                    <div class="text-lg font-bold text-cyan-400">
                                        {{ $tag->usage_count }}
                                    </div>
                                    <div class="text-xs text-gray-400">
                                        {{ $tag->usage_count === 1 ? 'use' : 'uses' }}
                                    </div>
                                </div>
                                
                                {{-- Trending Indicator --}}
                                @if ($index < 3)
                                    <svg class="w-4 h-4 text-pink-500 animate-pulse" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12.76 3.76a.75.75 0 11-1.52 0 .75.75 0 011.52 0zM12 8.25a.75.75 0 01.75.75v3.75a.75.75 0 01-1.5 0V9a.75.75 0 01.75-.75zm0 8.25a.75.75 0 100 1.5.75.75 0 000-1.5z"/>
                                    </svg>
                                @endif
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            {{-- Legend for Top 3 --}}
            <div class="mt-4 pt-4 border-t border-white/10">
                <div class="flex items-center gap-4 text-xs text-gray-400">
                    <div class="flex items-center gap-1">
                        <div class="w-3 h-3 rounded bg-gradient-to-br from-yellow-500 to-orange-500"></div>
                        <span>1st</span>
                    </div>
                    <div class="flex items-center gap-1">
                        <div class="w-3 h-3 rounded bg-gradient-to-br from-gray-300 to-gray-400"></div>
                        <span>2nd</span>
                    </div>
                    <div class="flex items-center gap-1">
                        <div class="w-3 h-3 rounded bg-gradient-to-br from-orange-600 to-orange-700"></div>
                        <span>3rd</span>
                    </div>
                </div>
            </div>
        @else
            {{-- Empty State --}}
            <div class="text-center py-8">
                <svg class="w-16 h-16 mx-auto mb-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                </svg>
                <p class="text-gray-400">No trending tags yet</p>
                <p class="text-sm text-gray-500 mt-2">Tags will appear here as activities are created</p>
            </div>
        @endif
    </div>
</div>
