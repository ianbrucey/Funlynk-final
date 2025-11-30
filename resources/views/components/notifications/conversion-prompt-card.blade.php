@props(['notification'])

<div class="bg-gradient-to-r from-pink-500/10 to-purple-500/10 rounded-lg p-3 border border-pink-500/20">
    <div class="flex items-start justify-between gap-3">
        <div>
            <h4 class="text-pink-400 font-semibold text-sm">
                {{ $notification->title }}
            </h4>
            <p class="text-gray-300 text-xs mt-1">
                {{ $notification->message }}
            </p>
            
            @if(isset($notification->data['reaction_count']))
                <div class="flex items-center gap-2 mt-2">
                    <span class="text-xs bg-pink-500/20 text-pink-300 px-2 py-0.5 rounded-full">
                        {{ $notification->data['reaction_count'] }} reactions
                    </span>
                    @if(($notification->data['threshold'] ?? '') === 'strong')
                        <span class="text-xs bg-purple-500/20 text-purple-300 px-2 py-0.5 rounded-full">
                            Ready to convert!
                        </span>
                    @endif
                </div>
            @endif
        </div>
        
        <button wire:click="convertPost('{{ $notification->data['post_id'] ?? '' }}')" 
                class="px-3 py-1.5 bg-gradient-to-r from-pink-500 to-purple-500 rounded-lg text-xs font-semibold hover:scale-105 transition shadow-lg shadow-pink-500/20">
            Convert
        </button>
    </div>
</div>