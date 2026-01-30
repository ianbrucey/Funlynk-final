<div>
    {{-- Modals at root level for proper positioning --}}
    <livewire:groups.manage-recurring-schedules :group="$group" />

    <div class="container mx-auto sm:px-6 py-6 sm:py-8 pb-24 sm:pb-8">
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
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6 sm:mb-8">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold">Group Settings</h1>
            <p class="text-gray-400 mt-1 text-sm sm:text-base">Manage settings for {{ $group->name }}</p>
        </div>
        <a href="{{ route('groups.show', $group) }}" class="inline-flex items-center justify-center gap-2 px-4 py-3 sm:py-2 bg-slate-800/50 border border-white/10 rounded-xl hover:border-cyan-500/50 transition min-h-[44px] text-sm sm:text-base">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Back to Group
        </a>
    </div>

    <!-- Settings Form -->
    <div class="relative p-4 sm:p-6 lg:p-8 glass-card max-w-3xl mx-auto mb-6 sm:mb-8">
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
                <label class="block text-sm font-medium text-gray-300 mb-3">Privacy</label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <label class="flex items-start gap-3 p-3 sm:p-2 rounded-xl cursor-pointer bg-slate-800/30 border transition min-h-[44px]
                        {{ $privacy === 'public' ? 'border-cyan-500/50 bg-cyan-500/10' : 'border-white/10 hover:border-white/20' }}">
                        <input type="radio" wire:model="privacy" value="public" class="text-cyan-500 focus:ring-cyan-500 mt-0.5">
                        <div>
                            <span class="text-gray-200 font-medium">Public</span>
                            <p class="text-gray-500 text-xs mt-0.5">Anyone can find and join</p>
                        </div>
                    </label>
                    <label class="flex items-start gap-3 p-3 sm:p-2 rounded-xl cursor-pointer bg-slate-800/30 border transition min-h-[44px]
                        {{ $privacy === 'private' ? 'border-cyan-500/50 bg-cyan-500/10' : 'border-white/10 hover:border-white/20' }}">
                        <input type="radio" wire:model="privacy" value="private" class="text-cyan-500 focus:ring-cyan-500 mt-0.5">
                        <div>
                            <span class="text-gray-200 font-medium">Private</span>
                            <p class="text-gray-500 text-xs mt-0.5">Invite or request to join</p>
                        </div>
                    </label>
                </div>
                @error('privacy') <span class="text-red-500 text-sm mt-1">{{ $message }}</span> @enderror
            </div>

            <!-- Content Permissions -->
            <div class="mb-6 p-3 sm:p-4 rounded-xl bg-slate-800/30 border border-white/5">
                <label class="block text-sm font-medium text-gray-300 mb-4">Content Permissions</label>

                <!-- Post Permission -->
                <div class="mb-4">
                    <p class="text-sm text-gray-400 mb-2">Who can create posts?</p>
                    <div class="grid grid-cols-2 gap-2">
                        <label class="flex items-center gap-2 p-3 rounded-lg cursor-pointer border transition min-h-[44px]
                            {{ $postPermission === 'everyone' ? 'border-cyan-500/50 bg-cyan-500/10' : 'border-white/10 bg-slate-800/50 hover:border-white/20' }}">
                            <input type="radio" wire:model="postPermission" value="everyone" class="text-cyan-500 focus:ring-cyan-500">
                            <span class="text-gray-200 text-sm">All Members</span>
                        </label>
                        <label class="flex items-center gap-2 p-3 rounded-lg cursor-pointer border transition min-h-[44px]
                            {{ $postPermission === 'admins' ? 'border-cyan-500/50 bg-cyan-500/10' : 'border-white/10 bg-slate-800/50 hover:border-white/20' }}">
                            <input type="radio" wire:model="postPermission" value="admins" class="text-cyan-500 focus:ring-cyan-500">
                            <span class="text-gray-200 text-sm">Admins Only</span>
                        </label>
                    </div>
                    @error('postPermission') <span class="text-red-500 text-sm mt-1">{{ $message }}</span> @enderror
                </div>

                <!-- Event Permission -->
                <div>
                    <p class="text-sm text-gray-400 mb-2">Who can create events?</p>
                    <div class="grid grid-cols-2 gap-2">
                        <label class="flex items-center gap-2 p-3 rounded-lg cursor-pointer border transition min-h-[44px]
                            {{ $eventPermission === 'everyone' ? 'border-cyan-500/50 bg-cyan-500/10' : 'border-white/10 bg-slate-800/50 hover:border-white/20' }}">
                            <input type="radio" wire:model="eventPermission" value="everyone" class="text-cyan-500 focus:ring-cyan-500">
                            <span class="text-gray-200 text-sm">All Members</span>
                        </label>
                        <label class="flex items-center gap-2 p-3 rounded-lg cursor-pointer border transition min-h-[44px]
                            {{ $eventPermission === 'admins' ? 'border-cyan-500/50 bg-cyan-500/10' : 'border-white/10 bg-slate-800/50 hover:border-white/20' }}">
                            <input type="radio" wire:model="eventPermission" value="admins" class="text-cyan-500 focus:ring-cyan-500">
                            <span class="text-gray-200 text-sm">Admins Only</span>
                        </label>
                    </div>
                    @error('eventPermission') <span class="text-red-500 text-sm mt-1">{{ $message }}</span> @enderror
                </div>
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
                <div class="flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-4">
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
                    <div class="min-w-0 flex-1">
                        <input type="file" wire:model="avatarImage" accept="image/*"
                            class="w-full text-sm text-gray-300 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:bg-purple-500/20 file:text-purple-300 hover:file:bg-purple-500/30">
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
                <div class="flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-4">
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
                    <div class="min-w-0 flex-1">
                        <input type="file" wire:model="coverImage" accept="image/*"
                            class="w-full text-sm text-gray-300 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:bg-purple-500/20 file:text-purple-300 hover:file:bg-purple-500/30">
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

    <!-- Recurring Schedules Section -->
    <div class="relative p-4 sm:p-6 lg:p-8 glass-card max-w-3xl mx-auto mb-6 sm:mb-8">
        <div class="top-accent-center"></div>

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
            <div>
                <h3 class="text-lg sm:text-xl font-bold text-white">Recurring Schedules</h3>
                <p class="text-gray-400 text-sm">Auto-generate events on a regular schedule</p>
            </div>
            <button
                @click="$dispatch('open-create-schedule-modal')"
                class="inline-flex items-center justify-center gap-2 px-4 py-3 sm:py-2 bg-gradient-to-r from-pink-500 to-purple-500 rounded-xl font-semibold hover:scale-105 transition-all text-sm min-h-[44px]">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                New Schedule
            </button>
        </div>

        <!-- Schedule List -->
        @if($group->recurringSchedules->isEmpty())
            <div class="p-6 sm:p-8 text-center bg-slate-800/30 rounded-xl border border-white/5">
                <div class="text-4xl mb-3">📅</div>
                <p class="text-gray-400">No recurring schedules yet</p>
                <p class="text-gray-500 text-sm mt-1">Create a schedule to auto-generate events</p>
            </div>
        @else
            <div class="space-y-4">
                @foreach($group->recurringSchedules as $schedule)
                    <div class="p-3 sm:p-4 bg-slate-800/30 rounded-xl border border-white/5 {{ !$schedule->is_active ? 'opacity-60' : '' }}">
                        <!-- Schedule Info -->
                        <div class="mb-3">
                            <div class="flex items-center gap-2 flex-wrap">
                                <h4 class="font-semibold text-white">{{ $schedule->title }}</h4>
                                @if(!$schedule->is_active)
                                    <span class="px-2 py-0.5 bg-yellow-500/20 text-yellow-300 rounded-full text-xs">Paused</span>
                                @endif
                            </div>
                            <p class="text-gray-400 text-sm mt-1">
                                {{ $schedule->schedule_description }} at {{ $schedule->time_range }}
                            </p>
                            @if($schedule->location_name)
                                <p class="text-gray-500 text-xs mt-1">📍 {{ $schedule->location_name }}</p>
                            @endif
                            <p class="text-gray-500 text-xs mt-2">
                                {{ $schedule->upcoming_activities_count }} upcoming events
                            </p>
                        </div>
                        
                        <!-- Action Buttons - Grid on mobile, flex on desktop -->
                        <div class="grid grid-cols-2 sm:flex sm:flex-wrap gap-2">
                            <button wire:click="$dispatch('open-events-modal', { scheduleId: '{{ $schedule->id }}' })"
                                class="flex items-center justify-center gap-1.5 px-3 py-2 text-xs bg-slate-700/50 hover:bg-slate-700 rounded-lg transition min-h-[38px]" title="View Events">
                                <span>📋</span> Events
                            </button>
                            <button wire:click="$dispatch('toggle-schedule-pause', { scheduleId: '{{ $schedule->id }}' })"
                                class="flex items-center justify-center gap-1.5 px-3 py-2 text-xs bg-slate-700/50 hover:bg-slate-700 rounded-lg transition min-h-[38px]">
                                {{ $schedule->is_active ? '⏸️ Pause' : '▶️ Resume' }}
                            </button>
                            <button wire:click="$dispatch('open-edit-schedule-modal', { scheduleId: '{{ $schedule->id }}' })"
                                class="flex items-center justify-center gap-1.5 px-3 py-2 text-xs bg-slate-700/50 hover:bg-slate-700 rounded-lg transition min-h-[38px]">
                                <span>✏️</span> Edit
                            </button>
                            <button wire:click="$dispatch('open-delete-schedule-modal', { scheduleId: '{{ $schedule->id }}' })"
                                class="flex items-center justify-center gap-1.5 px-3 py-2 text-xs bg-red-500/20 hover:bg-red-500/30 text-red-300 rounded-lg transition min-h-[38px]">
                                <span>🗑️</span> Delete
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Danger Zone -->
    <div class="relative p-4 sm:p-6 lg:p-8 glass-card max-w-3xl mx-auto border border-red-500/30">
        <h3 class="text-lg sm:text-xl font-bold text-red-400 mb-3 sm:mb-4">Danger Zone</h3>
        <p class="text-gray-400 mb-4 text-sm sm:text-base">Once you delete a group, there is no going back. Please be certain.</p>

        @if($confirmingDeletion)
            <div class="bg-red-500/10 border border-red-500/30 rounded-xl p-4 mb-4">
                <p class="text-red-300 mb-4 text-sm sm:text-base">Are you sure you want to delete this group? This action cannot be undone.</p>
                <div class="flex flex-col sm:flex-row gap-3">
                    <button wire:click="deleteGroup" class="flex-1 sm:flex-initial inline-flex items-center justify-center px-6 py-3 bg-red-600 rounded-xl font-semibold hover:bg-red-700 transition min-h-[44px]">
                        <span wire:loading.remove wire:target="deleteGroup">Yes, Delete Group</span>
                        <span wire:loading wire:target="deleteGroup">Deleting...</span>
                    </button>
                    <button wire:click="cancelDeletion" class="flex-1 sm:flex-initial inline-flex items-center justify-center px-6 py-3 bg-slate-800/50 border border-white/10 rounded-xl hover:border-cyan-500/50 transition min-h-[44px]">
                        Cancel
                    </button>
                </div>
            </div>
        @else
            <button wire:click="confirmDeletion" class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3 bg-red-600/50 border border-red-500/30 rounded-xl font-semibold hover:bg-red-600 transition min-h-[44px]">
                Delete Group
            </button>
        @endif
    </div>
    </div>
</div>
