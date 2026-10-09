<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use MatanYadaev\EloquentSpatial\Objects\Point;

class GeorgiaTestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Georgia area coordinates (Dacula/Duluth area)
        $centerLat = 33.9897;
        $centerLng = -83.8979;
        $radiusKm = 15; // 15km radius around Dacula

        $this->command->info('Creating admin and test1 users...');

        // Create admin user
        // Point constructor: (latitude, longitude, srid)
        $admin = User::create([
            'username' => 'admin',
            'email' => 'admin@funlynk.com',
            'password' => Hash::make('password'),
            'display_name' => 'Admin',
            'location_name' => 'Lawrenceville, GA, USA',
            'location_coordinates' => new Point(33.9507556, -83.9875616),
            'email_verified_at' => now(),
            'onboarding_completed_at' => now(),
        ]);

        // Create test1 user
        $test1 = User::create([
            'username' => 'test1',
            'email' => 'test1@funlynk.com',
            'password' => Hash::make('password'),
            'display_name' => 'Test User 1',
            'location_name' => 'Lawrenceville, GA, USA',
            'location_coordinates' => new Point(33.9507556, -83.9875616),
            'email_verified_at' => now(),
            'onboarding_completed_at' => now(),
        ]);

        $this->command->info('Creating 150 random users in Georgia area...');

        // Create 150 random users - all with same Lawrenceville, GA coordinates
        // Point constructor: (latitude, longitude)
        // Target: SRID=4326;POINT(-83.9875616 33.9507556)
        $users = [];
        for ($i = 0; $i < 150; $i++) {
            $user = User::factory()->create([
                'location_coordinates' => new Point(33.9507556, -83.9875616),
                'location_name' => 'Lawrenceville, GA, USA',
            ]);
            $users[] = $user;
        }

        $this->command->info('Creating 100 posts with Georgia coordinates...');

        // Create 100 posts with Georgia coordinates
        $postTitles = [
            'Basketball game at the park',
            'Coffee meetup',
            'Hiking trail exploration',
            'Picnic by the lake',
            'Outdoor yoga session',
            'Frisbee game',
            'Bike ride',
            'Dinner gathering',
            'Movie night',
            'Board game night',
        ];

        $postDescriptions = [
            'Looking for people to join!',
            'Come hang out with us',
            'First time? No problem!',
            'All skill levels welcome',
            'Bring your friends',
            'Free event',
            'Fun and casual',
            'Great way to meet people',
            'Let\'s have some fun',
            'You\'ll love this',
        ];

        for ($i = 0; $i < 100; $i++) {
            $lat = $centerLat + (rand(-$radiusKm * 100, $radiusKm * 100) / 100) * 0.01;
            $lng = $centerLng + (rand(-$radiusKm * 100, $radiusKm * 100) / 100) * 0.01;

            // Point constructor: (latitude, longitude, srid)
            Post::factory()->create([
                'user_id' => $users[array_rand($users)]->id,
                'title' => $postTitles[array_rand($postTitles)].' #'.($i + 1),
                'description' => $postDescriptions[array_rand($postDescriptions)],
                'location_coordinates' => new Point($lat, $lng),
                'location_name' => 'Dacula, GA',
                'tags' => ['sports', 'social', 'outdoor'],
                'expires_at' => now()->addDays(rand(1, 2)),
            ]);
        }

        $this->command->info('Creating 50 activities with Georgia coordinates...');

        // Create 50 activities
        $activityTitles = [
            'Basketball Tournament',
            'Community Yoga Class',
            'Hiking Adventure',
            'Picnic Gathering',
            'Outdoor Movie Night',
            'Frisbee Championship',
            'Cycling Tour',
            'Dinner Party',
            'Game Night',
            'Sports Meetup',
        ];

        for ($i = 0; $i < 50; $i++) {
            $lat = $centerLat + (rand(-$radiusKm * 100, $radiusKm * 100) / 100) * 0.01;
            $lng = $centerLng + (rand(-$radiusKm * 100, $radiusKm * 100) / 100) * 0.01;

            // Point constructor: (latitude, longitude, srid)
            Activity::factory()->create([
                'host_id' => $users[array_rand($users)]->id,
                'title' => $activityTitles[array_rand($activityTitles)].' #'.($i + 1),
                'location_coordinates' => new Point($lat, $lng),
                'location_name' => 'Dacula, GA',
                'start_time' => now()->addDays(rand(1, 30)),
                'activity_type' => ['sports', 'social', 'outdoor'][array_rand(['sports', 'social', 'outdoor'])],
            ]);
        }

        $this->command->info('✅ Seeding complete! Created 150 users, 100 posts, and 50 activities.');
    }
}
