<div class="container mx-auto px-6 py-8">
    <!-- Flash Messages -->
    @if (session()->has('success'))
        <div class="alert alert-success mb-4 p-4 rounded-xl bg-green-500/20 text-green-300">
            {{ session('success') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="alert alert-error mb-4 p-4 rounded-xl bg-red-500/20 text-red-300">
            {{ session('error') }}
        </div>
    @endif

    <!-- Header -->
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold">Group Settings</h1>
            <p class="text-gray-400 mt-1">Manage settings for {{ $group->name }}</p>
        </div>
        <a href="{{ route('groups.show', $group) }}" class="px-4 py-2 bg-slate-800/50 border border-white/10 rounded-xl hover:border-cyan-500/50 transition">
            ← Back to Group
        </a>
    </div>

    <!-- Settings Form -->
    <div class="relative p-8 glass-card max-w-3xl mx-auto mb-8">
        <div class="top-accent-center"></div>

        <form wire:submit.prevent="updateGroup">
            <!-- Group Name -->
            <div class="mb-6">
                <label for="name" class="block text-sm font-medium text-gray-300 mb-2">Group Name</label>
                <input type="text" id="name" wire:model="name"
                    class="w-full rounded-xl bg-slate-800/50 border border-white/10 text-white px-4 py-3 focus:border-cyan-500 focus:ring-cyan-500 transition">
                @error('name') <span class="text-red-500 text-sm mt-1">{{ $message }}</span> @enderror
            </div>

            <!-- Description -->
            <div class="mb-6">
                <label for="description" class="block text-sm font-medium text-gray-300 mb-2">Description</label>
                <textarea id="description" wire:model="description" rows="4"
                    class="w-full rounded-xl bg-slate-800/50 border border-white/10 text-white px-4 py-3 focus:border-cyan-500 focus:ring-cyan-500 transition"></textarea>
                @error('description') <span class="text-red-500 text-sm mt-1">{{ $message }}</span> @enderror
            </div>

            <!-- Privacy -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-300 mb-2">Privacy</label>
                <div class="flex gap-4">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" wire:model="privacy" value="public" class="text-cyan-500 focus:ring-cyan-500">
                        <span class="text-gray-300">Public</span>
                        <span class="text-gray-500 text-sm">(Anyone can find and join)</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" wire:model="privacy" value="private" class="text-cyan-500 focus:ring-cyan-500">
                        <span class="text-gray-300">Private</span>
                        <span class="text-gray-500 text-sm">(Invite or request to join)</span>
                    </label>
                </div>
                @error('privacy') <span class="text-red-500 text-sm mt-1">{{ $message }}</span> @enderror
            </div>

            <!-- Dynamic Tags Input (type-and-enter) -->
            <div class="mb-6">
                <label class="block text-gray-300 text-sm font-semibold mb-2">Tags (Optional)</label>
                <div class="flex gap-2 mb-2">
                    <input type="text"
                           id="tag-input"
                           wire:model="newTag"
                           wire:keydown.enter.prevent="addTag"
                           placeholder="Type a tag and press Enter"
                           class="flex-1 px-4 py-3 bg-slate-800/50 border border-white/10 rounded-2xl focus:border-cyan-500/50 focus:outline-none transition text-white" />
                    <button type="button"
                            wire:click="addTag"
                            class="px-4 py-3 bg-gradient-to-r from-pink-500 to-purple-500 rounded-xl font-semibold hover:scale-105 transition-all">
                        Add
                    </button>
                </div>
                @if(count($tags) > 0)
                    <div class="flex flex-wrap gap-2">
                        @foreach($tags as $index => $tag)
                            <span class="px-3 py-1 bg-purple-500/20 text-purple-300 rounded-full text-sm flex items-center gap-2">
                                {{ $tag }}
                                <button type="button" wire:click="removeTag({{ $index }})" class="text-purple-300 hover:text-white transition">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </span>
                        @endforeach
                    </div>
                @endif
                <p class="text-xs text-gray-500 mt-1">{{ count($tags) }}/10 tags</p>
                @error('tags') <span class="text-red-400 text-xs mt-1">{{ $message }}</span> @enderror
            </div>

            <!-- Avatar Image -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-300 mb-2">Group Avatar</label>
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 rounded-xl overflow-hidden bg-slate-700/50 flex-shrink-0">
                        @if($avatarImage)
                            <img src="{{ $avatarImage->temporaryUrl() }}" alt="Preview" class="w-full h-full object-cover">
                        @elseif($group->avatar_url)
                            <img src="{{ $group->avatar_url }}" alt="Current avatar" class="w-full h-full object-cover">
                        @else
                            <div class="w-full h-full flex items-center justify-center text-gray-500">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                        @endif
                    </div>
                    <div class="flex-grow">
                        <input type="file" wire:model="avatarImage" accept="image/*"
                            class="text-gray-300 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:bg-purple-500/20 file:text-purple-300 hover:file:bg-purple-500/30">
                        @if($avatarImage)
                            <p class="text-green-400 text-sm mt-2">✓ New image ready to save</p>
                        @endif
                        <div wire:loading wire:target="avatarImage" class="text-cyan-400 text-sm mt-2 animate-pulse">
                            Uploading preview...
                        </div>
                    </div>
                </div>
                @error('avatarImage') <span class="text-red-500 text-sm mt-1">{{ $message }}</span> @enderror
            </div>

            <!-- Cover Image -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-300 mb-2">Cover Image</label>
                <div class="flex items-center gap-4">
                    <div class="w-32 h-16 rounded-xl overflow-hidden bg-slate-700/50 flex-shrink-0">
                        @if($coverImage)
                            <img src="{{ $coverImage->temporaryUrl() }}" alt="Preview" class="w-full h-full object-cover">
                        @elseif($group->cover_image_url)
                            <img src="{{ $group->cover_image_url }}" alt="Current cover" class="w-full h-full object-cover">
                        @else
                            <div class="w-full h-full flex items-center justify-center text-gray-500">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                        @endif
                    </div>
                    <div class="flex-grow">
                        <input type="file" wire:model="coverImage" accept="image/*"
                            class="text-gray-300 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:bg-purple-500/20 file:text-purple-300 hover:file:bg-purple-500/30">
                        @if($coverImage)
                            <p class="text-green-400 text-sm mt-2">✓ New image ready to save</p>
                        @endif
                        <div wire:loading wire:target="coverImage" class="text-cyan-400 text-sm mt-2 animate-pulse">
                            Uploading preview...
                        </div>
                    </div>
                </div>
                @error('coverImage') <span class="text-red-500 text-sm mt-1">{{ $message }}</span> @enderror
            </div>

            <!-- Submit Button -->
            <div class="flex justify-end">
                <button type="submit" class="px-6 py-3 bg-gradient-to-r from-pink-500 to-purple-500 rounded-xl font-semibold hover:scale-105 transition-all">
                    <span wire:loading.remove wire:target="updateGroup">Save Changes</span>
                    <span wire:loading wire:target="updateGroup">Saving...</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Danger Zone -->
    <div class="relative p-8 glass-card max-w-3xl mx-auto border border-red-500/30">
        <h3 class="text-xl font-bold text-red-400 mb-4">Danger Zone</h3>
        <p class="text-gray-400 mb-4">Once you delete a group, there is no going back. Please be certain.</p>

        @if($confirmingDeletion)
            <div class="bg-red-500/10 border border-red-500/30 rounded-xl p-4 mb-4">
                <p class="text-red-300 mb-4">Are you sure you want to delete this group? This action cannot be undone.</p>
                <div class="flex gap-4">
                    <button wire:click="deleteGroup" class="px-6 py-3 bg-red-600 rounded-xl font-semibold hover:bg-red-700 transition">
                        <span wire:loading.remove wire:target="deleteGroup">Yes, Delete Group</span>
                        <span wire:loading wire:target="deleteGroup">Deleting...</span>
                    </button>
                    <button wire:click="cancelDeletion" class="px-6 py-3 bg-slate-800/50 border border-white/10 rounded-xl hover:border-cyan-500/50 transition">
                        Cancel
                    </button>
                </div>
            </div>
        @else
            <button wire:click="confirmDeletion" class="px-6 py-3 bg-red-600/50 border border-red-500/30 rounded-xl font-semibold hover:bg-red-600 transition">
                Delete Group
            </button>
        @endif
    </div>
</div>
