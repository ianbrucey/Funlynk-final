<div>
    @if($userRsvp)
        @if($userRsvp->status === 'waitlist')
            <button 
                wire:click="toggleRsvp"
                wire:loading.attr="disabled"
                class="w-full py-4 bg-slate-800/50 border border-yellow-500/50 rounded-xl font-bold text-yellow-400 hover:bg-yellow-500/10 transition-all shadow-lg disabled:opacity-50 disabled:cursor-not-allowed"
            >
                <span wire:loading.remove>On Waitlist - Click to Cancel</span>
                <span wire:loading>Processing...</span>
            </button>
        @else
            <button 
                wire:click="toggleRsvp"
                wire:loading.attr="disabled"
                class="w-full py-4 bg-slate-800/50 border border-green-500/50 rounded-xl font-bold text-green-400 hover:bg-green-500/10 transition-all shadow-lg disabled:opacity-50 disabled:cursor-not-allowed"
            >
                <span wire:loading.remove>✓ Attending - Click to Cancel</span>
                <span wire:loading>Processing...</span>
            </button>
        @endif
    @else
        @if($activity->max_attendees && $activity->current_attendees >= $activity->max_attendees)
            <button 
                wire:click="toggleRsvp"
                wire:loading.attr="disabled"
                class="w-full py-4 bg-gradient-to-r from-yellow-500 to-orange-500 rounded-xl font-bold text-white hover:scale-105 transition-all shadow-lg disabled:opacity-50 disabled:cursor-not-allowed"
            >
                <span wire:loading.remove>Join Waitlist</span>
                <span wire:loading>Processing...</span>
            </button>
        @else
            <button 
                wire:click="toggleRsvp"
                wire:loading.attr="disabled"
                class="w-full py-4 bg-gradient-to-r from-pink-500 to-purple-500 rounded-xl font-bold text-white hover:scale-105 transition-all shadow-lg disabled:opacity-50 disabled:cursor-not-allowed"
            >
                <span wire:loading.remove>Join Activity</span>
                <span wire:loading>Processing...</span>
            </button>
        @endif
    @endif

    @if($activity->max_attendees)
        <div class="mt-2 text-center text-sm text-gray-400">
            @if($activity->current_attendees >= $activity->max_attendees)
                <span class="text-yellow-400">Activity Full</span>
            @else
                {{ $activity->max_attendees - $activity->current_attendees }} spot(s) remaining
            @endif
        </div>
    @endif
</div>
