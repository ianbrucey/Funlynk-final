<div class="container mx-auto px-6 py-8">
    <div class="relative p-8 glass-card max-w-2xl mx-auto">
        <div class="top-accent-center"></div>

        <h2 class="text-3xl font-bold text-white mb-2">🔔 Notification Preferences</h2>
        <p class="text-gray-400 mb-8">Manage how you receive notifications from FunLynk</p>

        <form wire:submit.prevent="save" class="space-y-8">
            {{-- Overall Preference --}}
            <div class="space-y-4">
                <h3 class="text-lg font-semibold text-white">Notification Method</h3>
                <p class="text-sm text-gray-400 mb-4">Choose how you want to receive notifications</p>

                <div class="space-y-3">
                    <label class="flex items-center gap-3 p-3 rounded-lg border border-white/10 hover:border-cyan-500/50 cursor-pointer transition">
                        <input type="radio" wire:model="notification_preference" value="all" class="w-4 h-4">
                        <div>
                            <div class="font-medium text-white">All Notifications</div>
                            <div class="text-xs text-gray-400">Receive both in-app and email notifications</div>
                        </div>
                    </label>

                    <label class="flex items-center gap-3 p-3 rounded-lg border border-white/10 hover:border-cyan-500/50 cursor-pointer transition">
                        <input type="radio" wire:model="notification_preference" value="in_app_only" class="w-4 h-4">
                        <div>
                            <div class="font-medium text-white">In-App Only</div>
                            <div class="text-xs text-gray-400">Only see notifications in the app</div>
                        </div>
                    </label>

                    <label class="flex items-center gap-3 p-3 rounded-lg border border-white/10 hover:border-cyan-500/50 cursor-pointer transition">
                        <input type="radio" wire:model="notification_preference" value="email_only" class="w-4 h-4">
                        <div>
                            <div class="font-medium text-white">Email Only</div>
                            <div class="text-xs text-gray-400">Only receive email notifications</div>
                        </div>
                    </label>

                    <label class="flex items-center gap-3 p-3 rounded-lg border border-white/10 hover:border-cyan-500/50 cursor-pointer transition">
                        <input type="radio" wire:model="notification_preference" value="none" class="w-4 h-4">
                        <div>
                            <div class="font-medium text-white">Disable All</div>
                            <div class="text-xs text-gray-400">Don't receive any notifications</div>
                        </div>
                    </label>
                </div>
            </div>

            {{-- Specific Email Preferences --}}
            @if($notification_preference !== 'none')
                <div class="space-y-4 pt-6 border-t border-white/10">
                    <h3 class="text-lg font-semibold text-white">Email Notification Types</h3>
                    <p class="text-sm text-gray-400 mb-4">Choose which types of emails you want to receive</p>

                    <label class="flex items-center gap-3 p-3 rounded-lg border border-white/10 hover:border-cyan-500/50 cursor-pointer transition">
                        <input type="checkbox" wire:model="email_on_post_converted" class="w-4 h-4">
                        <div>
                            <div class="font-medium text-white">Post Converted to Event</div>
                            <div class="text-xs text-gray-400">When a post you reacted to becomes an event</div>
                        </div>
                    </label>

                    <label class="flex items-center gap-3 p-3 rounded-lg border border-white/10 hover:border-cyan-500/50 cursor-pointer transition">
                        <input type="checkbox" wire:model="email_on_event_invitation" class="w-4 h-4">
                        <div>
                            <div class="font-medium text-white">Event Invitations</div>
                            <div class="text-xs text-gray-400">When you're invited to an event</div>
                        </div>
                    </label>

                    <label class="flex items-center gap-3 p-3 rounded-lg border border-white/10 hover:border-cyan-500/50 cursor-pointer transition">
                        <input type="checkbox" wire:model="email_on_rsvp_update" class="w-4 h-4">
                        <div>
                            <div class="font-medium text-white">RSVP Updates</div>
                            <div class="text-xs text-gray-400">When someone RSVPs to your event</div>
                        </div>
                    </label>

                    <label class="flex items-center gap-3 p-3 rounded-lg border border-white/10 hover:border-cyan-500/50 cursor-pointer transition">
                        <input type="checkbox" wire:model="email_on_comment" class="w-4 h-4">
                        <div>
                            <div class="font-medium text-white">New Comments</div>
                            <div class="text-xs text-gray-400">When someone comments on your post or event</div>
                        </div>
                    </label>

                    <label class="flex items-center gap-3 p-3 rounded-lg border border-white/10 hover:border-cyan-500/50 cursor-pointer transition">
                        <input type="checkbox" wire:model="email_on_reaction" class="w-4 h-4">
                        <div>
                            <div class="font-medium text-white">Post Reactions</div>
                            <div class="text-xs text-gray-400">When someone reacts to your post</div>
                        </div>
                    </label>
                </div>
            @endif

            {{-- Save Button --}}
            <div class="flex gap-3 pt-6 border-t border-white/10">
                <button type="submit" class="px-6 py-3 bg-gradient-to-r from-pink-500 to-purple-500 rounded-xl font-semibold hover:scale-105 transition-all">
                    💾 Save Preferences
                </button>
            </div>
        </form>
    </div>
</div>
