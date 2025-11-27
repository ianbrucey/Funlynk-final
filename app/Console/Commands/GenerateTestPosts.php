<?php

namespace App\Console\Commands;

use App\Models\Post;
use App\Models\User;
use Illuminate\Console\Command;
use MatanYadaev\EloquentSpatial\Objects\Point;

class GenerateTestPosts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'posts:generate-test {count=100} {--post-id=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate test posts around a specific location';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $count = (int) $this->argument('count');
        $postId = $this->option('post-id');

        // Get reference post for coordinates
        if ($postId) {
            $referencePost = Post::find($postId);
            if (! $referencePost) {
                $this->error("Post with ID {$postId} not found!");
                return 1;
            }
        } else {
            $referencePost = Post::first();
            if (! $referencePost) {
                $this->error('No posts found in database!');
                return 1;
            }
        }

        $baseLat = $referencePost->location_coordinates->latitude;
        $baseLng = $referencePost->location_coordinates->longitude;
        $locationName = $referencePost->location_name;

        $this->info("Generating {$count} test posts around {$locationName} ({$baseLat}, {$baseLng})");

        // Get a user to be the author (use first user or create one)
        $user = User::first();
        if (! $user) {
            $this->error('No users found in database! Please create a user first.');
            return 1;
        }

        $activities = [
            'Basketball pickup game',
            'Coffee meetup',
            'Dog park hangout',
            'Running group',
            'Board game night',
            'Yoga in the park',
            'Photography walk',
            'Book club discussion',
            'Hiking trail',
            'Bike ride',
            'Tennis match',
            'Frisbee golf',
            'Picnic gathering',
            'Art gallery visit',
            'Live music jam',
            'Food truck tour',
            'Farmers market trip',
            'Beach volleyball',
            'Rock climbing',
            'Kayaking adventure',
            'Meditation session',
            'Dance class',
            'Cooking together',
            'Movie night',
            'Trivia night',
            'Karaoke session',
            'Wine tasting',
            'Craft beer sampling',
            'Pottery class',
            'Painting workshop',
        ];

        $timeHints = [
            'this afternoon',
            'tonight',
            'tomorrow morning',
            'tomorrow evening',
            'this weekend',
            'Saturday morning',
            'Sunday afternoon',
            'next week',
            'in a few hours',
            'later today',
        ];

        $descriptions = [
            'Anyone interested? Let me know!',
            'Looking for people to join!',
            'Who\'s down for this?',
            'Would love some company!',
            'First time trying this, join me!',
            'Regular meetup, newcomers welcome!',
            'Casual and fun, all levels welcome!',
            'Let\'s make this happen!',
            'Perfect weather for this!',
            'Bring your friends!',
        ];

        $progressBar = $this->output->createProgressBar($count);
        $progressBar->start();

        for ($i = 0; $i < $count; $i++) {
            // Generate random coordinates within ~10km radius
            // 1 degree latitude ≈ 111km, 1 degree longitude ≈ 85km (at this latitude)
            $latOffset = (rand(-90, 90) / 1000); // ±0.09 degrees ≈ ±10km
            $lngOffset = (rand(-90, 90) / 1000); // ±0.09 degrees ≈ ±7.5km

            $lat = $baseLat + $latOffset;
            $lng = $baseLng + $lngOffset;

            $activity = $activities[array_rand($activities)];
            $timeHint = $timeHints[array_rand($timeHints)];
            $description = $descriptions[array_rand($descriptions)];

            Post::create([
                'user_id' => $user->id,
                'title' => $activity,
                'description' => $description,
                'time_hint' => $timeHint,
                'location_name' => $locationName.' Area',
                'location_coordinates' => new Point($lat, $lng, 4326),
                'status' => 'active',
                'expires_at' => now()->addHours(rand(24, 48)),
            ]);

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);
        $this->info("✅ Successfully created {$count} test posts!");
        $this->info("📍 Location: {$locationName} ({$baseLat}, {$baseLng})");
        $this->info("🔍 Posts are spread within ~10km radius");

        return 0;
    }
}
