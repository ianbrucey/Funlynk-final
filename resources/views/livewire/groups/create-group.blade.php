<div class="min-h-screen flex items-center justify-center px-4 py-12">
    <div class="w-full max-w-2xl">
        <div class="text-center mb-8">
            <h2 class="text-3xl font-bold">
                <span class="text-yellow-400">Create</span> <span class="text-cyan-400">New Group</span>
            </h2>
            <p class="text-gray-400 mt-2">Start a new community around your interests</p>
        </div>

        <div class="relative p-8 glass-card">
            <div class="top-accent-center"></div>

            @if (session()->has('message'))
                <div class="mb-6 p-4 rounded-xl bg-green-500/20 border border-green-500/50 flex items-center gap-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2m4-4l-4 4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="text-green-200">{{ session('message') }}</span>
                </div>
            @endif

            @if (session()->has('error'))
                <div class="mb-6 p-4 rounded-xl bg-red-500/20 border border-red-500/50 flex items-center gap-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="text-red-200">{{ session('error') }}</span>
                </div>
            @endif

            <form wire:submit.prevent="createGroup">
                <div class="mb-4">
                    <label for="name" class="block text-gray-300 text-sm font-semibold mb-2">Group Name</label>
                    <input type="text" id="name" wire:model.live="name"
                           class="w-full px-4 py-3 bg-slate-800/50 border border-white/10 rounded-2xl focus:border-cyan-500/50 focus:outline-none transition text-white"
                           placeholder="e.g., Local Hiking Enthusiasts">
                    @error('name') <span class="text-red-400 text-xs mt-1">{{ $message }}</span> @enderror
                </div>

                <div class="mb-4">
                    <label for="description" class="block text-gray-300 text-sm font-semibold mb-2">Description</label>
                    <textarea id="description" wire:model.live="description"
                              class="w-full px-4 py-3 bg-slate-800/50 border border-white/10 rounded-2xl focus:border-cyan-500/50 focus:outline-none transition text-white"
                              rows="4" placeholder="Tell us what your group is about..."></textarea>
                    @error('description') <span class="text-red-400 text-xs mt-1">{{ $message }}</span> @enderror
                </div>

                <div class="mb-4">
                    <label for="privacy" class="block text-gray-300 text-sm font-semibold mb-2">Privacy</label>
                    <select id="privacy" wire:model="privacy"
                            class="w-full px-4 py-3 bg-slate-800/50 border border-white/10 rounded-2xl focus:border-cyan-500/50 focus:outline-none transition text-white">
                        <option value="public">Public (Anyone can see and join)</option>
                        <option value="private">Private (Only members can see content, join by invitation or approval)</option>
                    </select>
                    @error('privacy') <span class="text-red-400 text-xs mt-1">{{ $message }}</span> @enderror
                </div>

                <div class="mb-4">
                    <label for="tags" class="block text-gray-300 text-sm font-semibold mb-2">Tags (Optional)</label>
                    <div class="relative">
                        <select id="tags" wire:change="addTag($event.target.value)"
                                class="w-full px-4 py-3 bg-slate-800/50 border border-white/10 rounded-2xl focus:border-cyan-500/50 focus:outline-none transition text-white">
                            <option value="">Select tags...</option>
                            @foreach($availableTags as $id => $name)
                                <option value="{{ $id }}" @if(in_array($id, $selectedTags)) disabled @endif>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach($selectedTags as $tagId)
                            <span class="px-3 py-1 bg-purple-500/20 text-purple-300 rounded-full text-xs flex items-center gap-1">
                                {{ $availableTags[$tagId] ?? 'Unknown Tag' }}
                                <button type="button" wire:click="removeTag({{ $tagId }})" class="text-purple-300 hover:text-white transition">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </span>
                        @endforeach
                    </div>
                    @error('selectedTags') <span class="text-red-400 text-xs mt-1">{{ $message }}</span> @enderror
                </div>

                <div class="mb-6">
                    <label for="location" class="block text-gray-300 text-sm font-semibold mb-2">Location (Optional)</label>
                    <input type="text" id="location" wire:model.live="location"
                           class="w-full px-4 py-3 bg-slate-800/50 border border-white/10 rounded-2xl focus:border-cyan-500/50 focus:outline-none transition text-white"
                           placeholder="e.g., New York, NY">
                    @error('location') <span class="text-red-400 text-xs mt-1">{{ $message }}</span> @enderror
                </div>

                <div>
                    <button type="submit" class="w-full px-6 py-3 bg-gradient-to-r from-pink-500 to-purple-500 rounded-xl font-semibold hover:scale-105 transition-all">
                        Create Group
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
