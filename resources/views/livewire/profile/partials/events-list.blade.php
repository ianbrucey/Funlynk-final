@if(isset($activities) && $activities->count() > 0)
    @php $section = null; @endphp
    <div class="grid gap-2 md:grid-cols-2">
        @foreach($activities as $activity)
            @php
                $upcoming = $activity->start_time && $activity->start_time->gte(now());
                $heading = $upcoming ? 'Upcoming' : 'Past';
                $tags = [];
                if ($activity->group_id) { $tags[] = ['label' => 'Group', 'tone' => 'cyan']; }
                if ($activity->is_paid) { $tags[] = ['label' => 'Paid', 'tone' => 'amber']; }
                $place = $activity->location_name ? trim(explode(',', $activity->location_name)[0]) : null;
                $going = ($activity->current_attendees ?? 0).($upcoming ? ' going' : ' went');
                $meta = collect([$activity->start_time?->format('D, M j'), $place, $going])->filter()->implode(' · ');
            @endphp
            @if($section !== $heading)
                @php $section = $heading; @endphp
                <div class="mt-2 px-1 text-xs font-medium uppercase tracking-wider text-gray-400 md:col-span-2">{{ $heading }}</div>
            @endif
            <x-fl.profile-row :href="route('events.show', $activity->id)" :title="$activity->title" :meta="$meta"
                              :badge="$activity->status === 'cancelled' ? 'Cancelled' : $heading" :tone="$activity->status === 'cancelled' ? 'muted' : ($upcoming ? 'purple' : 'muted')"
                              :tags="$tags" :image="$activity->images[0] ?? null" icon="📅" wire:key="activity-{{ $activity->id }}" />
        @endforeach
    </div>
    <div class="mt-4">{{ $activities->links() }}</div>
@else
    <div class="px-4 py-12 text-center">
        <div class="text-4xl" aria-hidden="true">📅</div>
        <h3 class="mt-3 text-sm font-medium text-gray-300">{{ $activeTab === 'hosted' ? 'No events yet' : 'Not going to anything yet' }}</h3>
        <p class="mt-1 text-xs text-gray-500">
            @if($activeTab === 'hosted')
                {{ auth()->id() === $user->id ? "You haven't hosted any events yet." : "This user hasn't hosted any events yet." }}
            @else
                {{ auth()->id() === $user->id ? "You're not attending any events yet." : "This user isn't attending any events yet." }}
            @endif
        </p>
        @if(auth()->id() === $user->id)
            <a href="{{ route('feed.nearby') }}" wire:navigate class="mt-4 inline-flex min-h-[44px] items-center rounded-xl bg-gradient-to-r from-pink-500 to-purple-500 px-6 text-sm font-semibold text-white">Discover nearby</a>
        @endif
    </div>
@endif
