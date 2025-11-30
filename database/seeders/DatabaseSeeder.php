<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\Conversation;
use App\Models\Follow;
use App\Models\Message;
use App\Models\Post;
use App\Models\PostConversion;
use App\Models\PostReaction;
use App\Models\Rsvp;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Ensure deterministic test users exist
        $testUsers = [
            ['email' => 'test1@funlynk.test', 'username' => 'test1', 'display_name' => 'Test One'],
            ['email' => 'test2@funlynk.test', 'username' => 'test2', 'display_name' => 'Test Two'],
            ['email' => 'admin@funlynk.test', 'username' => 'admin', 'display_name' => 'Admin', 'is_verified' => true, 'is_host' => true],
        ];
        foreach ($testUsers as $tu) {
            User::updateOrCreate(
                ['email' => $tu['email']],
                [
                    'username' => $tu['username'],
                    'display_name' => $tu['display_name'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                    'is_verified' => $tu['is_verified'] ?? false,
                    'is_host' => $tu['is_host'] ?? false,
                    'is_active' => true,
                    'privacy_level' => 'public',
                ]
            );
        }

        // Create additional users to reach at least 50 total
        $current = User::count();
        if ($current < 50) {
            User::factory(50 - $current)->create();
        }

        $users = User::all();

        // Tags (deterministic, idempotent)
        $tagNames = ['sports', 'music', 'outdoors', 'gaming', 'fitness', 'art', 'food', 'travel', 'tech', 'dance'];
        foreach ($tagNames as $name) {
            Tag::updateOrCreate(
                ['name' => $name],
                [
                    'category' => in_array($name, ['sports', 'fitness', 'outdoors']) ? 'sports' : (in_array($name, ['music', 'dance', 'art']) ? 'music' : 'social'),
                    'description' => ucfirst($name).' related',
                    'usage_count' => rand(0, 1000),
                    'is_featured' => in_array($name, ['sports', 'music', 'outdoors']),
                ]
            );
        }

        // Posts
        $posts = Post::factory(100)->recycle($users)->create();

        // Activities (with geospatial location set in factory afterCreating)
        $activities = Activity::factory(30)->recycle($users)->create();

        // Post Reactions (unique user/post pairs)
        $pairs = [];
        $targetReactions = 200;
        while (count($pairs) < $targetReactions) {
            $post = $posts->random();
            $user = $users->random();
            $key = $post->id.':'.$user->id;
            if (isset($pairs[$key])) {
                continue;
            }
            PostReaction::query()->create([
                'post_id' => $post->id,
                'user_id' => $user->id,
                'reaction_type' => fake()->randomElement(['im_down', 'join_me', 'interested']),
                'created_at' => now(),
            ]);
            $pairs[$key] = true;
        }

        // RSVPs (unique user/activity pairs)
        $rsvpPairs = [];
        $targetRsvps = 50;
        while (count($rsvpPairs) < $targetRsvps) {
            $activity = $activities->random();
            $user = $users->random();
            $key = $activity->id.':'.$user->id;
            if (isset($rsvpPairs[$key])) {
                continue;
            }
            Rsvp::query()->create([
                'activity_id' => $activity->id,
                'user_id' => $user->id,
                'status' => fake()->randomElement(['attending', 'maybe', 'declined']),
                'is_paid' => false,
                'payment_intent_id' => null,
                'payment_status' => null,
            ]);
            $rsvpPairs[$key] = true;
        }

        // Follows (unique, no self-follow)
        $followPairs = [];
        $targetFollows = 20;
        while (count($followPairs) < $targetFollows) {
            $follower = $users->random();
            $following = $users->random();
            if ($follower->id === $following->id) {
                continue;
            }
            $key = $follower->id.':'.$following->id;
            if (isset($followPairs[$key])) {
                continue;
            }
            Follow::query()->create([
                'follower_id' => $follower->id,
                'following_id' => $following->id,
            ]);
            $followPairs[$key] = true;
        }

        // Conversations and Messages (one per activity, plus a reply)
        foreach ($activities as $activity) {
            // Create conversation for activity
            $conversation = Conversation::query()->create([
                'type' => 'group',
                'conversationable_type' => Activity::class,
                'conversationable_id' => $activity->id,
                'last_message_at' => now(),
            ]);

            // Add host as participant
            $conversation->participants()->attach($activity->host_id, [
                'id' => Str::uuid()->toString(),
                'role' => 'admin',
            ]);

            // First message - ensure author is not the host
            $author = $users->where('id', '!=', $activity->host_id)->random();
            if (! $conversation->participants()->where('user_id', $author->id)->exists()) {
                $conversation->participants()->attach($author->id, [
                    'id' => Str::uuid()->toString(),
                    'role' => 'member',
                ]);
            }

            $firstMessage = Message::query()->create([
                'conversation_id' => $conversation->id,
                'user_id' => $author->id,
                'body' => 'Excited for this!',
                'type' => 'text',
            ]);

            // Reply message - ensure replier is not the author or host
            $replier = $users->whereNotIn('id', [$activity->host_id, $author->id])->random();
            if (! $conversation->participants()->where('user_id', $replier->id)->exists()) {
                $conversation->participants()->attach($replier->id, [
                    'id' => Str::uuid()->toString(),
                    'role' => 'member',
                ]);
            }

            Message::query()->create([
                'conversation_id' => $conversation->id,
                'user_id' => $replier->id,
                'reply_to_message_id' => $firstMessage->id,
                'body' => 'Me too! See you there.',
                'type' => 'text',
            ]);
        }

        // Post-to-Event conversions (link some posts to activities)
        $samplePosts = $posts->random(10);
        foreach ($samplePosts as $post) {
            $activity = $activities->random();
            // Link both sides - use DB update to avoid spatial data issues
            $post->update(['converted_to_activity_id' => $activity->id, 'status' => 'converted']);
            DB::table('activities')
                ->where('id', $activity->id)
                ->update([
                    'originated_from_post_id' => $post->id,
                    'conversion_date' => now(),
                ]);

            // Create conversion metrics record
            PostConversion::query()->create([
                'post_id' => $post->id,
                'event_id' => $activity->id,
                'trigger_type' => fake()->randomElement(['manual', 'automatic', 'threshold']),
                'reactions_at_conversion' => fake()->numberBetween(0, 50),
                'comments_at_conversion' => fake()->numberBetween(0, 50),
                'views_at_conversion' => fake()->numberBetween(0, 200),
                'rsvp_conversion_rate' => fake()->randomFloat(2, 0, 1),
                'created_at' => now(),
            ]);
        }
    }
}
