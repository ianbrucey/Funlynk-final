<?php

namespace Database\Seeders;

use App\Models\Post;
use App\Models\PostReaction;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PostSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get existing users or create one
        $users = User::all();
        if ($users->count() === 0) {
            $users = User::factory()->count(10)->create();
        }

        // 30 active posts (standard, no special reactions)
        echo "Creating 30 active posts...\n";
        Post::factory()
            ->count(30)
            ->create([
                'user_id' => $users->random()->id,
                'expires_at' => now()->addHours(rand(24, 48)),
                'status' => 'active',
            ]);

        // 10 posts with 3-7 reactions (eligible for conversion suggestion)
        echo "Creating 10 posts with 3-7 reactions...\n";
        Post::factory()
            ->count(10)
            ->create([
                'user_id' => $users->random()->id,
                'expires_at' => now()->addHours(rand(24, 48)),
                'status' => 'active',
            ])
            ->each(function ($post) use ($users) {
                $reactionCount = rand(3, 7);
                $reactingUsers = $users->random(min($reactionCount, $users->count()));
                
                foreach ($reactingUsers as $index => $user) {
                    if ($index >= $reactionCount) break;
                    PostReaction::create([
                        'post_id' => $post->id,
                        'user_id' => $user->id,
                        'reaction_type' => fake()->randomElement(['im_down', 'join_me']),
                        'created_at' => now(),
                    ]);
                }
                
                // Update reaction count
                $post->update(['reaction_count' => $reactionCount]);
            });

        // 5 posts with 10+ reactions (eligible for auto-conversion)
        echo "Creating 5 posts with 10+ reactions...\n";
        Post::factory()
            ->count(5)
            ->create([
                'user_id' => $users->random()->id,
                'expires_at' => now()->addHours(rand(24, 48)),
                'status' => 'active',
            ])
            ->each(function ($post) use ($users) {
                $reactionCount = rand(10, 15);
                $reactingUsers = $users->random(min($reactionCount, $users->count()));
                
                foreach ($reactingUsers as $index => $user) {
                    if ($index >= $reactionCount) break;
                    PostReaction::create([
                        'post_id' => $post->id,
                        'user_id' => $user->id,
                        'reaction_type' => fake()->randomElement(['im_down', 'join_me']),
                        'created_at' => now(),
                    ]);
                }
                
                // Update reaction count
                $post->update(['reaction_count' => $reactionCount]);
            });

        // 5 expired posts
        echo "Creating 5 expired posts...\n";
        Post::factory()
            ->count(5)
            ->create([
                'user_id' => $users->random()->id,
                'expires_at' => now()->subHours(rand(1, 24)),
                'status' => 'expired',
            ]);

        echo "Posts seeded successfully! Total: 50 posts\n";
    }
}
