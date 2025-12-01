<div class="relative p-6 glass-card h-full flex flex-col">
    <div class="flex-grow">
        <!-- Group Avatar -->
        <div class="mb-4">
            <img src="{{ $group->avatar_url ?? 'https://via.placeholder.com/150' }}" alt="{{ $group->name }}" class="w-24 h-24 rounded-lg object-cover mx-auto">
        </div>

        <!-- Group Name -->
        <h3 class="text-xl font-bold text-center mb-2">{{ $group->name }}</h3>

        <!-- Member Count -->
        <p class="text-sm text-gray-400 text-center mb-4">{{ $group->members_count ?? 0 }} members</p>

        <!-- Tags -->
        <div class="flex flex-wrap justify-center gap-2 mb-4">
            @foreach($group->tags ?? [] as $tag)
                <span class="px-2 py-1 bg-slate-800/50 text-xs rounded-full">{{ $tag->name }}</span>
            @endforeach
        </div>
    </div>

    <!-- View Group Button -->
    <div class="mt-auto">
        <a href="{{ route('groups.show', $group) }}" class="block w-full text-center px-4 py-2 bg-slate-800/50 border border-white/10 rounded-lg hover:bg-white/10 transition">
            View Group
        </a>
    </div>
</div>