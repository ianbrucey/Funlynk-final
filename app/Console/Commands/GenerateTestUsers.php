<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use MatanYadaev\EloquentSpatial\Objects\Point;

class GenerateTestUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:generate-test {count=50}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate test users with various interests and locations';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $count = (int) $this->argument('count');

        // Base location (Lawrenceville, GA)
        $baseLat = 33.9507556;
        $baseLng = -83.9875616;

        $this->info("Generating {$count} test users around Lawrenceville, GA");

        $firstNames = [
            'Alex', 'Jordan', 'Taylor', 'Morgan', 'Casey', 'Riley', 'Avery', 'Quinn',
            'Skylar', 'Sage', 'River', 'Phoenix', 'Dakota', 'Rowan', 'Finley', 'Reese',
            'Charlie', 'Jamie', 'Drew', 'Blake', 'Cameron', 'Emerson', 'Hayden', 'Kendall',
            'Logan', 'Parker', 'Peyton', 'Reagan', 'Sawyer', 'Spencer',
        ];

        $lastNames = [
            'Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis',
            'Rodriguez', 'Martinez', 'Hernandez', 'Lopez', 'Gonzalez', 'Wilson', 'Anderson',
            'Thomas', 'Taylor', 'Moore', 'Jackson', 'Martin', 'Lee', 'Thompson', 'White',
            'Harris', 'Clark', 'Lewis', 'Robinson', 'Walker', 'Young', 'Hall',
        ];

        $interests = [
            'Basketball', 'Coffee', 'Hiking', 'Photography', 'Yoga', 'Running', 'Cooking',
            'Music', 'Art', 'Gaming', 'Reading', 'Travel', 'Fitness', 'Dancing', 'Cycling',
            'Swimming', 'Tennis', 'Golf', 'Meditation', 'Painting', 'Writing', 'Gardening',
            'Volunteering', 'Tech', 'Fashion', 'Food', 'Movies', 'Theater', 'Crafts', 'Pets',
        ];

        $progressBar = $this->output->createProgressBar($count);
        $progressBar->start();

        for ($i = 0; $i < $count; $i++) {
            $firstName = $firstNames[array_rand($firstNames)];
            $lastName = $lastNames[array_rand($lastNames)];
            $username = strtolower($firstName).strtolower($lastName).rand(100, 999);

            // Random location within ~25km radius
            $latOffset = (rand(-225, 225) / 1000); // ±0.225 degrees ≈ ±25km
            $lngOffset = (rand(-225, 225) / 1000);

            $lat = $baseLat + $latOffset;
            $lng = $baseLng + $lngOffset;

            // Random interests (2-5 interests per user)
            $userInterests = array_rand(array_flip($interests), rand(2, 5));
            if (! is_array($userInterests)) {
                $userInterests = [$userInterests];
            }

            User::create([
                'name' => $firstName.' '.$lastName,
                'username' => $username,
                'email' => $username.'@example.com',
                'password' => Hash::make('password'),
                'display_name' => $firstName.' '.$lastName,
                'location_name' => 'Lawrenceville Area',
                'location_coordinates' => new Point($lat, $lng, 4326),
                'interests' => $userInterests,
                'is_active' => true,
                'follower_count' => rand(0, 100),
                'following_count' => rand(0, 100),
                'onboarding_completed_at' => now(),
            ]);

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);
        $this->info("✅ Successfully created {$count} test users!");
        $this->info('📍 Location: Lawrenceville, GA area (~25km radius)');
        $this->info('🔍 Users have 2-5 random interests each');
        $this->newLine();
        $this->warn('⚠️  Remember to import users to Meilisearch:');
        $this->line('   php artisan scout:import "App\Models\User"');

        return 0;
    }
}
