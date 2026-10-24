@props(['messages', 'pinned' => null, 'count' => 0, 'href'])

<div {{ $attributes->class(['relative glass-card p-5 lg:p-6 lg:rounded-xl']) }}>
    <div class="top-accent"></div>
    <div class="flex items-center justify-between gap-3">
        <h2 class="text-xl font-bold text-white">Chat</h2>
        <span class="text-xs text-gray-400">{{ $count }} {{ \Illuminate\Support\Str::plural('person', $count) }}</span>
    </div>

    @if($pinned)
        <a href="{{ $href }}" wire:navigate class="mt-3 flex min-h-[44px] items-center gap-2 rounded-xl border border-cyan-400/25 bg-cyan-400/10 px-3 text-xs text-cyan-200">
            <span aria-hidden="true">📌</span><span class="truncate">{{ $pinned->body }}</span>
        </a>
    @endif

    <div class="mt-3 space-y-1.5">
        @forelse($messages as $message)
            <div class="rounded-xl bg-black/25 px-3 py-2 text-sm text-gray-200">
                <span class="font-semibold text-cyan-300">{{ $message->user->display_name ?? $message->user->username }}</span>
                <span class="line-clamp-2">{{ $message->body }}</span>
            </div>
        @empty
            <p class="rounded-xl bg-black/25 px-3 py-4 text-center text-sm text-gray-400">No messages yet. Start the plan.</p>
        @endforelse
    </div>

    <a href="{{ $href }}" wire:navigate class="mt-4 flex min-h-[44px] w-full items-center justify-center rounded-xl bg-gradient-to-r from-cyan-500 to-blue-500 text-sm font-semibold text-white transition hover:scale-[1.02]">💬 Open chat</a>
</div>
