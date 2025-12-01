<div>
    <x-galaxy-layout>
        <x-slot name="title">
            Dashboard
        </x-slot>

        <div class="container mx-auto px-6 py-8">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">

                <!-- My Groups Card -->
                <div class="relative p-8 glass-card">
                    <div class="top-accent-center"></div>
                    <h2 class="text-2xl font-bold mb-4">My Groups</h2>
                    <div class="text-gray-400">
                        <!-- Placeholder for group cards -->
                        <p>You are not a member of any groups yet.</p>
                    </div>
                </div>

                <!-- Recent Notifications Card -->
                <div class="relative p-8 glass-card">
                    <div class="top-accent-center"></div>
                    <h2 class="text-2xl font-bold mb-4">Recent Notifications</h2>
                    <div class="text-gray-400">
                        <!-- Placeholder for notifications -->
                        <p>No new notifications.</p>
                    </div>
                </div>

                <!-- Interested Posts Card -->
                <div class="relative p-8 glass-card">
                    <div class="top-accent-center"></div>
                    <h2 class="text-2xl font-bold mb-4">Interested Posts</h2>
                    <div class="text-gray-400">
                        <!-- Placeholder for posts -->
                        <p>You have not reacted to any posts yet.</p>
                    </div>
                </div>

                <!-- Upcoming Events Card -->
                <div class="relative p-8 glass-card">
                    <div class="top-accent-center"></div>
                    <h2 class="text-2xl font-bold mb-4">Upcoming Events</h2>
                    <div class="text-gray-400">
                        <!-- Placeholder for events -->
                        <p>No upcoming events.</p>
                    </div>
                </div>

            </div>
        </div>
    </x-galaxy-layout>
</div>