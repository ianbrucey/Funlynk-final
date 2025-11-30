<div>
    @if($show)
        <div class="fixed inset-0 z-50 overflow-y-auto" x-data="{ showPreview: @entangle('showPreview') }">
            {{-- Backdrop --}}
            <div class="fixed inset-0 bg-black/80 backdrop-blur-sm" wire:click="close"></div>

            {{-- Modal --}}
            <div class="relative min-h-screen flex items-center justify-center p-4">
                <div class="relative glass-card max-w-4xl w-full p-8 max-h-[90vh] overflow-y-auto">
                    <div class="top-accent-center"></div>

                    {{-- Header --}}
                    <div class="flex items-start justify-between mb-6">
                        <div>
                            <h2 class="text-2xl font-bold text-white mb-2">Convert Post to Event</h2>
                            <p class="text-gray-400 text-sm">
                                {{ $interestedCount + $invitedCount }} people will be notified
                            </p>
                        </div>
                        <button wire:click="close" class="text-gray-400 hover:text-white transition">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    {{-- Form / Preview Toggle --}}
                    <div class="flex gap-2 mb-6">
                        <button
                            @click="showPreview = false"
                            :class="!showPreview ? 'bg-gradient-to-r from-pink-500 to-purple-500' : 'bg-slate-800/50'"
                            class="px-4 py-2 rounded-lg transition-all">
                            Edit Details
                        </button>
                        <button
                            @click="showPreview = true"
                            :class="showPreview ? 'bg-gradient-to-r from-pink-500 to-purple-500' : 'bg-slate-800/50'"
                            class="px-4 py-2 rounded-lg transition-all">
                            Preview Event
                        </button>
                    </div>

                    {{-- Form Section --}}
                    <div x-show="!showPreview" x-transition>
                        {{-- Validation Errors Summary --}}
                        @if ($errors->any())
                            <div class="mb-6 p-4 bg-red-500/10 border border-red-500/50 rounded-xl">
                                <h4 class="text-red-400 font-semibold mb-2">Please fix the following errors:</h4>
                                <ul class="list-disc list-inside text-red-300 text-sm space-y-1">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form wire:submit.prevent="submit" class="space-y-6" @submit="console.log('Form submitted!')"
                            {{-- Section 1: Basic Details --}}
                            <div class="space-y-4">
                                <h3 class="text-lg font-semibold text-white border-b border-white/10 pb-2">
                                    Event Details
                                </h3>

                                {{-- Title --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-300 mb-2">
                                        Event Title *
                                    </label>
                                    <input
                                        type="text"
                                        wire:model="title"
                                        class="w-full px-4 py-3 bg-slate-900/50 border border-white/10 rounded-xl text-white focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition"
                                        placeholder="Give your event a catchy title">
                                    @error('title') <span class="text-red-400 text-sm">{{ $message }}</span> @enderror
                                </div>

                                {{-- Description --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-300 mb-2">
                                        Description *
                                    </label>
                                    <textarea
                                        wire:model="description"
                                        rows="4"
                                        class="w-full px-4 py-3 bg-slate-900/50 border border-white/10 rounded-xl text-white focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition"
                                        placeholder="Describe what attendees can expect"></textarea>
                                    @error('description') <span class="text-red-400 text-sm">{{ $message }}</span> @enderror
                                </div>

                                {{-- Location --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-300 mb-2">
                                        Location *
                                    </label>
                                    <input
                                        type="text"
                                        wire:model="location_name"
                                        class="w-full px-4 py-3 bg-slate-900/50 border border-white/10 rounded-xl text-white focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition"
                                        placeholder="Where will this happen?">
                                    @error('location_name') <span class="text-red-400 text-sm">{{ $message }}</span> @enderror
                                </div>

                                {{-- Tags --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-300 mb-2">
                                        Tags
                                    </label>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($availableTags as $tag)
                                            <label class="cursor-pointer">
                                                <input
                                                    type="checkbox"
                                                    wire:model="selectedTags"
                                                    value="{{ $tag->id }}"
                                                    class="sr-only peer">
                                                <span class="inline-block px-3 py-1 rounded-full text-sm border border-white/10 peer-checked:bg-gradient-to-r peer-checked:from-pink-500 peer-checked:to-purple-500 peer-checked:border-transparent transition">
                                                    {{ $tag->name }}
                                                </span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            </div>

                            {{-- Section 2: Event Specifics --}}
                            <div class="space-y-4">
                                <h3 class="text-lg font-semibold text-white border-b border-white/10 pb-2">
                                    Event Specifics
                                </h3>

                                {{-- Date/Time --}}
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-300 mb-2">
                                            Start Time *
                                        </label>
                                        <input
                                            type="datetime-local"
                                            wire:model="start_time"
                                            class="w-full px-4 py-3 bg-slate-900/50 border border-white/10 rounded-xl text-white focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition">
                                        @error('start_time') <span class="text-red-400 text-sm">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-300 mb-2">
                                            End Time *
                                        </label>
                                        <input
                                            type="datetime-local"
                                            wire:model="end_time"
                                            class="w-full px-4 py-3 bg-slate-900/50 border border-white/10 rounded-xl text-white focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition">
                                        @error('end_time') <span class="text-red-400 text-sm">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                {{-- Capacity & Price --}}
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-300 mb-2">
                                            Max Attendees *
                                        </label>
                                        <input
                                            type="number"
                                            wire:model="max_attendees"
                                            min="1"
                                            class="w-full px-4 py-3 bg-slate-900/50 border border-white/10 rounded-xl text-white focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition">
                                        @if($post)
                                            <p class="text-xs text-gray-500 mt-1">
                                                Suggested: {{ max((int) ceil($post->reaction_count * 1.5), 10) }} based on interest
                                            </p>
                                        @endif
                                        @error('max_attendees') <span class="text-red-400 text-sm">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-300 mb-2">
                                            Price (USD) *
                                        </label>
                                        <input
                                            type="number"
                                            wire:model="price"
                                            min="0"
                                            step="0.01"
                                            class="w-full px-4 py-3 bg-slate-900/50 border border-white/10 rounded-xl text-white focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition"
                                            placeholder="0.00 for free">
                                        @error('price') <span class="text-red-400 text-sm">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                {{-- Image Upload --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-300 mb-2">
                                        Event Image (Optional)
                                    </label>
                                    <input
                                        type="file"
                                        wire:model="image"
                                        accept="image/*"
                                        class="w-full px-4 py-3 bg-slate-900/50 border border-white/10 rounded-xl text-white file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-gradient-to-r file:from-pink-500 file:to-purple-500 file:text-white hover:file:scale-105 transition">
                                    @error('image') <span class="text-red-400 text-sm">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            {{-- Section 3: Interested Users --}}
                            <div class="glass-card p-4 bg-cyan-500/5 border border-cyan-500/20">
                                <h4 class="text-white font-semibold mb-2">Who will be notified?</h4>
                                <div class="flex items-center gap-4 text-sm text-gray-300">
                                    <div>
                                        <span class="text-cyan-400 font-bold">{{ $interestedCount }}</span> interested users
                                    </div>
                                    @if($invitedCount > 0)
                                        <div>
                                            <span class="text-purple-400 font-bold">{{ $invitedCount }}</span> invited users
                                        </div>
                                    @endif
                                    <div class="ml-auto">
                                        <span class="text-white font-bold">{{ $interestedCount + $invitedCount }}</span> total
                                    </div>
                                </div>
                                <p class="text-xs text-gray-400 mt-2">
                                    ℹ️ All users will receive an invitation to RSVP (not auto-enrolled)
                                </p>
                            </div>

                            {{-- Actions --}}
                            <div class="flex gap-3 pt-4">
                                <button
                                    type="button"
                                    wire:click="close"
                                    class="flex-1 px-6 py-3 bg-slate-800/50 border border-white/10 rounded-xl hover:border-red-500/50 transition"
                                    wire:loading.attr="disabled">
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    class="flex-1 px-6 py-3 bg-gradient-to-r from-pink-500 to-purple-500 rounded-xl font-semibold hover:scale-105 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                                    wire:loading.attr="disabled"
                                    wire:target="submit">
                                    <span wire:loading.remove wire:target="submit">Create Event</span>
                                    <span wire:loading wire:target="submit">Converting...</span>
                                </button>
                            </div>
                        </form>
                    </div>

                    {{-- Preview Section --}}
                    <div x-show="showPreview" x-transition>
                        <livewire:modals.event-preview-card
                            :title="$title"
                            :description="$description"
                            :location="$location_name"
                            :startTime="$start_time"
                            :endTime="$end_time"
                            :maxAttendees="$max_attendees"
                            :price="$price"
                            :interestedCount="$interestedCount"
                            :tags="collect($availableTags)->whereIn('id', $selectedTags)->map(fn($tag) => ['name' => $tag->name])->toArray()"
                            :imagePreview="$this->imagePreview"
                            :key="'preview-'.now()->timestamp" />
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
