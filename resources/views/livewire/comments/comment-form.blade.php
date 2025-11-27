<div>
    @if(session()->has('comment-success'))
        <div class="mb-4 p-4 rounded-xl bg-green-500/20 border border-green-500/50 flex items-center gap-3">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span class="text-green-200">{{ session('comment-success') }}</span>
        </div>
    @endif

    <form wire:submit="submit" class="space-y-4">
        <div>
            <label for="content" class="block text-sm font-medium text-gray-300 mb-2">
                @if($parent)
                    <span class="flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-cyan-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                        </svg>
                        Reply to comment
                    </span>
                @else
                    Your comment
                @endif
            </label>
            <textarea
                wire:model.live="content"
                id="content"
                rows="3"
                maxlength="500"
                placeholder="{{ $parent ? 'Write your reply...' : 'Share your thoughts...' }}"
                class="w-full px-4 py-3 bg-slate-800/50 border border-white/10 rounded-xl text-white placeholder-gray-500 focus:border-cyan-500/50 focus:ring-2 focus:ring-cyan-500/20 transition resize-none"
            ></textarea>
            @error('content')
                <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
            @enderror
            <p class="mt-2 text-xs text-gray-500 text-right">
                {{ strlen($content) }}/500 characters
            </p>
        </div>

        <div class="flex items-center gap-3">
            <button
                type="submit"
                class="px-6 py-2 bg-gradient-to-r from-cyan-500 to-blue-500 rounded-xl font-semibold hover:scale-105 transition-all flex items-center gap-2"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                </svg>
                Post Comment
            </button>

            @if($parent)
                <button
                    type="button"
                    wire:click="cancel"
                    class="px-6 py-2 bg-slate-800/50 border border-white/10 rounded-xl hover:border-red-500/50 transition"
                >
                    Cancel
                </button>
            @endif
        </div>
    </form>
</div>
