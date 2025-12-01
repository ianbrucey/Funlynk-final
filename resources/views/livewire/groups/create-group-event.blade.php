<div class="fixed inset-0 z-50 overflow-y-auto" x-show="showCreateGroupEventModal" style="display: none;">
    <div class="flex items-end justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <!-- Background overlay -->
        <div class="fixed inset-0 transition-opacity bg-gray-900 bg-opacity-75" aria-hidden="true"></div>

        <!-- Modal content -->
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="relative inline-block px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform rounded-lg shadow-xl glass-card sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
            <div class="top-accent-center"></div>
            <h3 class="text-xl font-bold text-white mb-4">Create New Event</h3>

            <form wire:submit.prevent="createEvent">
                <div class="mb-4">
                    <label for="title" class="block text-sm font-medium text-gray-300">Title</label>
                    <input type="text" id="title" wire:model.defer="title" class="mt-1 block w-full rounded-md bg-slate-800/50 border border-white/10 text-white focus:border-cyan-500 focus:ring-cyan-500">
                    @error('title') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div class="mb-4">
                    <label for="description" class="block text-sm font-medium text-gray-300">Description</label>
                    <textarea id="description" wire:model.defer="description" rows="3" class="mt-1 block w-full rounded-md bg-slate-800/50 border border-white/10 text-white focus:border-cyan-500 focus:ring-cyan-500"></textarea>
                    @error('description') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div class="mb-4">
                    <label for="locationName" class="block text-sm font-medium text-gray-300">Location Name</label>
                    <input type="text" id="locationName" wire:model.defer="locationName" class="mt-1 block w-full rounded-md bg-slate-800/50 border border-white/10 text-white focus:border-cyan-500 focus:ring-cyan-500">
                    @error('locationName') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div class="mb-4">
                    <label for="startTime" class="block text-sm font-medium text-gray-300">Start Time</label>
                    <input type="datetime-local" id="startTime" wire:model.defer="startTime" class="mt-1 block w-full rounded-md bg-slate-800/50 border border-white/10 text-white focus:border-cyan-500 focus:ring-cyan-500">
                    @error('startTime') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div class="mb-4">
                    <label for="endTime" class="block text-sm font-medium text-gray-300">End Time</label>
                    <input type="datetime-local" id="endTime" wire:model.defer="endTime" class="mt-1 block w-full rounded-md bg-slate-800/50 border border-white/10 text-white focus:border-cyan-500 focus:ring-cyan-500">
                    @error('endTime') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div class="mb-4">
                    <label for="maxAttendees" class="block text-sm font-medium text-gray-300">Max Attendees (Optional)</label>
                    <input type="number" id="maxAttendees" wire:model.defer="maxAttendees" class="mt-1 block w-full rounded-md bg-slate-800/50 border border-white/10 text-white focus:border-cyan-500 focus:ring-cyan-500">
                    @error('maxAttendees') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div class="mb-4">
                    <label for="selectedTags" class="block text-sm font-medium text-gray-300">Tags (Optional)</label>
                    <select id="selectedTags" wire:model.defer="selectedTags" multiple class="mt-1 block w-full rounded-md bg-slate-800/50 border border-white/10 text-white focus:border-cyan-500 focus:ring-cyan-500">
                        @foreach($availableTags as $tag)
                            <option value="{{ $tag->id }}">{{ $tag->name }}</option>
                        @endforeach
                    </select>
                    @error('selectedTags') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div class="flex justify-end space-x-4">
                    <button type="button" @click="showCreateGroupEventModal = false" class="px-6 py-3 bg-slate-800/50 border border-white/10 rounded-xl hover:border-cyan-500/50 transition text-white">
                        Cancel
                    </button>
                    <button type="submit" class="px-6 py-3 bg-gradient-to-r from-pink-500 to-purple-500 rounded-xl font-semibold hover:scale-105 transition-all text-white">
                        Create Event
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
