<div>
    {{-- Modals at root level for proper z-index and positioning --}}

    <!-- Create/Edit Modal -->
    <div x-data="{ open: false, isEdit: false }"
         x-show="open"
         x-cloak
         x-on:open-create-schedule-modal.window="open = true; isEdit = false; $wire.call('handleOpenCreateModal')"
         x-on:open-edit-schedule-modal.window="open = true; isEdit = true; $wire.call('handleOpenEditModal', $event.detail.scheduleId)"
         x-on:close-schedule-modal.window="open = false"
         x-on:keydown.escape.window="open = false"
         class="relative z-50">
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
            <div class="flex items-end sm:items-center justify-center min-h-screen px-0 sm:px-4 pt-4 pb-0 sm:pb-20">
                <div class="fixed inset-0 bg-black/70 transition-opacity" @click="open = false"></div>

                <div class="relative w-full sm:max-w-lg p-4 sm:p-6 glass-card rounded-t-2xl sm:rounded-xl max-h-[90vh] overflow-y-auto">
                    <div class="top-accent-center"></div>

                    <h3 class="text-lg sm:text-xl font-bold mb-4" x-text="isEdit ? 'Edit Schedule' : 'Create Recurring Schedule'"></h3>

                    <form wire:submit.prevent="{{ $showEditModal ? 'updateSchedule' : 'createSchedule' }}">
                        <!-- Title -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-300 mb-1">Event Title</label>
                            <input type="text" wire:model="title" placeholder="e.g., Morning Training"
                                class="w-full rounded-xl bg-slate-800/50 border border-white/10 text-white px-4 py-3 focus:border-cyan-500 focus:ring-cyan-500 transition">
                            @error('title') <span class="text-red-400 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <!-- Description -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-300 mb-1">Description (Optional)</label>
                            <textarea wire:model="description" rows="2" placeholder="Event description..."
                                class="w-full rounded-xl bg-slate-800/50 border border-white/10 text-white px-4 py-3 focus:border-cyan-500 focus:ring-cyan-500 transition"></textarea>
                        </div>

                        <!-- Location with Google Places Autocomplete -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-300 mb-1">Location (Optional)</label>
                            <div
                                class="flex items-center gap-2 p-3 rounded-xl bg-slate-800/50 border border-white/10"
                                x-data="{
                                    init() {
                                        // Small delay to ensure DOM is ready
                                        setTimeout(() => this.loadGoogleMaps(), 100);
                                    },
                                    loadGoogleMaps() {
                                        if (window.google && window.google.maps && window.google.maps.places) {
                                            this.initAutocomplete();
                                            return;
                                        }
                                        if (!document.getElementById('google-maps-script')) {
                                            const script = document.createElement('script');
                                            script.id = 'google-maps-script';
                                            script.src = 'https://maps.googleapis.com/maps/api/js?key={{ config('services.google.places_api_key') }}&libraries=places';
                                            script.async = true;
                                            script.defer = true;
                                            script.onload = () => {
                                                this.initAutocomplete();
                                            };
                                            document.head.appendChild(script);
                                        } else {
                                            // Wait for existing script
                                            const checkInterval = setInterval(() => {
                                                if (window.google && window.google.maps && window.google.maps.places) {
                                                    clearInterval(checkInterval);
                                                    this.initAutocomplete();
                                                }
                                            }, 100);
                                        }
                                    },
                                    initAutocomplete() {
                                        const input = this.$refs.locationInput;
                                        if (!input) {
                                            console.error('Location input not found');
                                            return;
                                        }

                                        const autocomplete = new google.maps.places.Autocomplete(input, {
                                            types: ['establishment', 'geocode'],
                                            fields: ['formatted_address', 'geometry', 'name']
                                        });

                                        autocomplete.addListener('place_changed', () => {
                                            const place = autocomplete.getPlace();
                                            if (!place.geometry) return;

                                            const name = place.name || place.formatted_address;
                                            const lat = place.geometry.location.lat();
                                            const lng = place.geometry.location.lng();

                                            @this.setLocationData(name, lat, lng);
                                        });
                                    }
                                }"
                                wire:ignore
                            >
                                <svg class="w-5 h-5 text-pink-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <input
                                    x-ref="locationInput"
                                    type="text"
                                    wire:model="locationName"
                                    placeholder="Search for a location..."
                                    autocomplete="off"
                                    class="flex-1 bg-transparent border-none text-white placeholder-gray-500 focus:ring-0 px-0"
                                >
                            </div>
                        </div>

                        <!-- Frequency -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-300 mb-1">Frequency</label>
                            <select wire:model.live="frequency"
                                class="w-full rounded-xl bg-slate-800/50 border border-white/10 text-white px-4 py-3 focus:border-cyan-500 focus:ring-cyan-500 transition">
                                <option value="daily">Daily</option>
                                <option value="weekly">Weekly</option>
                                <option value="monthly">Monthly</option>
                            </select>
                        </div>

                        <!-- Days of Week (for weekly) - Using Alpine.js for instant updates -->
                        @if($frequency === 'weekly')
                            <div class="mb-4" x-data="{ 
                                days: @entangle('daysOfWeek'),
                                toggle(day) {
                                    if (this.days.includes(day)) {
                                        this.days = this.days.filter(d => d !== day);
                                    } else {
                                        this.days = [...this.days, day];
                                    }
                                }
                            }">
                                <label class="block text-sm font-medium text-gray-300 mb-2">Days of Week</label>
                                <div class="grid grid-cols-4 sm:flex sm:flex-wrap gap-2">
                                    @foreach(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'] as $day)
                                        <button type="button" @click="toggle('{{ $day }}')"
                                            class="px-3 py-2 sm:py-1.5 rounded-lg text-sm transition min-h-[44px] sm:min-h-0"
                                            :class="days.includes('{{ $day }}') ? 'bg-cyan-500/30 text-cyan-300 border border-cyan-500/50' : 'bg-slate-700/50 text-gray-400 border border-white/10 active:scale-95'">
                                            {{ ucfirst(substr($day, 0, 3)) }}
                                        </button>
                                    @endforeach
                                </div>
                                @error('daysOfWeek') <span class="text-red-400 text-xs">{{ $message }}</span> @enderror
                            </div>
                        @endif

                        <!-- Day of Month (for monthly) -->
                        @if($frequency === 'monthly')
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-300 mb-1">Day of Month</label>
                                <select wire:model="dayOfMonth"
                                    class="w-full rounded-xl bg-slate-800/50 border border-white/10 text-white px-4 py-3 focus:border-cyan-500 focus:ring-cyan-500 transition">
                                    @for($i = 1; $i <= 31; $i++)
                                        <option value="{{ $i }}">{{ $i }}</option>
                                    @endfor
                                </select>
                            </div>
                        @endif

                        <!-- Start Time -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-300 mb-1">Start Time</label>
                            <div class="grid grid-cols-3 gap-2">
                                <select wire:model="startTimeHour" class="rounded-xl bg-slate-800/50 border border-white/10 text-white px-3 py-3 min-h-[44px]">
                                    @for($h = 1; $h <= 12; $h++)
                                        <option value="{{ $h }}">{{ $h }}</option>
                                    @endfor
                                </select>
                                <select wire:model="startTimeMinute" class="rounded-xl bg-slate-800/50 border border-white/10 text-white px-3 py-3 min-h-[44px]">
                                    @foreach(['00', '15', '30', '45'] as $m)
                                        <option value="{{ $m }}">{{ $m }}</option>
                                    @endforeach
                                </select>
                                <select wire:model="startTimePeriod" class="rounded-xl bg-slate-800/50 border border-white/10 text-white px-3 py-3 min-h-[44px]">
                                    <option value="AM">AM</option>
                                    <option value="PM">PM</option>
                                </select>
                            </div>
                        </div>

                        <!-- End Time (Optional) -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-300 mb-1">End Time (Optional)</label>
                            <div class="grid grid-cols-3 gap-2">
                                <select wire:model="endTimeHour" class="rounded-xl bg-slate-800/50 border border-white/10 text-white px-3 py-3 min-h-[44px]">
                                    <option value="">--</option>
                                    @for($h = 1; $h <= 12; $h++)
                                        <option value="{{ $h }}">{{ $h }}</option>
                                    @endfor
                                </select>
                                <select wire:model="endTimeMinute" class="rounded-xl bg-slate-800/50 border border-white/10 text-white px-3 py-3 min-h-[44px]">
                                    <option value="">--</option>
                                    @foreach(['00', '15', '30', '45'] as $m)
                                        <option value="{{ $m }}">{{ $m }}</option>
                                    @endforeach
                                </select>
                                <select wire:model="endTimePeriod" class="rounded-xl bg-slate-800/50 border border-white/10 text-white px-3 py-3 min-h-[44px]">
                                    <option value="">--</option>
                                    <option value="AM">AM</option>
                                    <option value="PM">PM</option>
                                </select>
                            </div>
                        </div>

                        <!-- Generate Weeks Ahead -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-300 mb-1">Generate events for next</label>
                            <select wire:model="generateWeeksAhead"
                                class="w-full rounded-xl bg-slate-800/50 border border-white/10 text-white px-4 py-3 focus:border-cyan-500 focus:ring-cyan-500 transition min-h-[44px]">
                                @for($w = 1; $w <= 12; $w++)
                                    <option value="{{ $w }}">{{ $w }} {{ $w === 1 ? 'week' : 'weeks' }}</option>
                                @endfor
                            </select>
                        </div>

                        <!-- Actions -->
                        <div class="flex flex-col-reverse sm:flex-row gap-3 pb-4 sm:pb-0">
                            <button type="button" @click="open = false"
                                class="flex-1 px-4 py-3 bg-slate-800/50 border border-white/10 rounded-xl hover:border-cyan-500/50 transition min-h-[44px]">
                                Cancel
                            </button>
                            <button type="submit"
                                class="flex-1 px-4 py-3 bg-gradient-to-r from-pink-500 to-purple-500 rounded-xl font-semibold hover:scale-105 transition-all min-h-[44px]">
                                <span wire:loading.remove x-text="isEdit ? 'Save Changes' : 'Create Schedule'"></span>
                                <span wire:loading>Saving...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div x-data="{ open: false, scheduleId: null }"
         x-show="open"
         x-cloak
         x-on:open-delete-schedule-modal.window="open = true; scheduleId = $event.detail.scheduleId; $wire.call('handleOpenDeleteModal', $event.detail.scheduleId)"
         x-on:close-delete-modal.window="open = false"
         x-on:keydown.escape.window="open = false"
         class="relative z-50">
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-black/70 transition-opacity" @click="open = false"></div>

                <div class="relative w-full max-w-md p-6 glass-card">
                    <h3 class="text-xl font-bold text-red-400 mb-4">Delete Schedule?</h3>
                    <p class="text-gray-300 mb-6">
                        This will permanently delete this schedule and all its <strong>future</strong> events.
                        Past events will be kept for history.
                    </p>
                    <div class="flex gap-3">
                        <button @click="open = false"
                            class="flex-1 px-4 py-3 bg-slate-800/50 border border-white/10 rounded-xl hover:border-cyan-500/50 transition">
                            Cancel
                        </button>
                        <button wire:click="deleteSchedule"
                            class="flex-1 px-4 py-3 bg-red-600 rounded-xl font-semibold hover:bg-red-700 transition">
                            <span wire:loading.remove>Delete Schedule</span>
                            <span wire:loading>Deleting...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- View Events Modal -->
    <div x-data="{ open: false }"
         x-show="open"
         x-cloak
         x-on:open-events-modal.window="open = true; $wire.call('handleOpenEventsModal', $event.detail.scheduleId)"
         x-on:close-events-modal.window="open = false"
         x-on:keydown.escape.window="open = false"
         class="relative z-50">
        @if($viewingSchedule)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20">
                <div class="fixed inset-0 bg-black/70 transition-opacity" @click="open = false"></div>

                <div class="relative w-full max-w-lg p-6 glass-card max-h-[80vh] overflow-y-auto">
                    <div class="top-accent-center"></div>

                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xl font-bold">{{ $viewingSchedule->title }}</h3>
                        <button @click="open = false" class="text-gray-400 hover:text-white">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <p class="text-gray-400 text-sm mb-4">{{ $viewingSchedule->schedule_description }} at {{ $viewingSchedule->time_range }}</p>

                    <h4 class="text-sm font-semibold text-gray-300 mb-3">Upcoming Events</h4>

                    @if($upcomingEvents->isEmpty())
                        <p class="text-gray-500 text-sm">No upcoming events</p>
                    @else
                        <div class="space-y-2">
                            @foreach($upcomingEvents as $event)
                                <div class="flex items-center justify-between p-3 bg-slate-800/50 rounded-xl {{ $event->status === 'cancelled' ? 'opacity-50' : '' }}">
                                    <div>
                                        <p class="text-white text-sm">
                                            {{ $event->start_time->format('D, M j') }}
                                            <span class="text-gray-400">at {{ $event->start_time->format('g:i A') }}</span>
                                        </p>
                                        @if($event->status === 'cancelled')
                                            <span class="text-red-400 text-xs">Cancelled</span>
                                        @endif
                                    </div>
                                    @if($event->status !== 'cancelled')
                                        <button wire:click="cancelEvent('{{ $event->id }}')"
                                            wire:confirm="Cancel this event?"
                                            class="px-2 py-1 text-xs bg-red-500/20 hover:bg-red-500/30 text-red-300 rounded-lg transition">
                                            Cancel
                                        </button>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
        @endif
    </div>

</div>
