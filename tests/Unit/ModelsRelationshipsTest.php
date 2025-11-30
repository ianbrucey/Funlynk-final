<?php

use App\Models\Activity;
use App\Models\Conversation;
use App\Models\Follow;
use App\Models\Message;
use App\Models\Post;
use App\Models\PostReaction;
use App\Models\Rsvp;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

function makeUser(string $name): User
{
    return User::create([
        'email' => $name.'@example.test',
        'username' => $name.'_'.Str::random(6),
        'display_name' => ucfirst($name),
        'password' => 'password',
    ]);
}

function makeActivity(User $host): Activity
{
    $id = (string) Str::uuid();

    // Minimal required fields; PostGIS geography set via ST_GeogFromText
    DB::insert(
        "INSERT INTO activities (id, host_id, title, description, activity_type, location_name, start_time, created_at, updated_at, location_coordinates)
         VALUES (?, ?, ?, ?, ?, ?, NOW() + interval '1 hour', NOW(), NOW(), ST_GeogFromText(?))",
        [
            $id,
            $host->id,
            'Pickup Game',
            'Casual game in the park',
            'sports',
            'Dolores Park',
            'SRID=4326;POINT(-122.4194 37.7749)',
        ]
    );

    return Activity::findOrFail($id);
}

it('handles follow relationships', function () {
    $alice = makeUser('alice');
    $bob = makeUser('bob');

    Follow::create([
        'follower_id' => $alice->id,
        'following_id' => $bob->id,
    ]);

    expect($alice->following()->pluck('users.id'))
        ->toContain($bob->id);

    expect($bob->followers()->pluck('users.id'))
        ->toContain($alice->id);
});

it('links posts to users, reactions, and converted activities', function () {
    $u = makeUser('poster');

    $post = Post::create([
        'user_id' => $u->id,
        'title' => 'Who is down for basketball tonight?',
        'description' => 'Looking for people to play basketball',
        'location_name' => 'Venice Beach',
        'location_coordinates' => new \MatanYadaev\EloquentSpatial\Objects\Point(34.0195, -118.4912, 4326),
        'status' => 'active',
        'expires_at' => now()->addHours(48),
    ]);

    PostReaction::create([
        'post_id' => $post->id,
        'user_id' => $u->id,
        'reaction_type' => 'im_down',
    ]);

    // Create an activity and set post->converted_to_activity_id
    $activity = makeActivity($u);
    $post->converted_to_activity_id = $activity->id;
    $post->status = 'converted';
    $post->save();

    expect($post->user->id)->toBe($u->id);
    expect($post->reactions()->count())->toBe(1);
    expect($post->convertedActivity)->not()->toBeNull();
    expect($post->convertedActivity->id)->toBe($activity->id);
});

it('supports threaded messages in activity conversations', function () {
    $u = makeUser('chatter');
    $activity = makeActivity($u);

    // Create conversation for activity
    $conversation = Conversation::create([
        'type' => 'group',
        'conversationable_type' => Activity::class,
        'conversationable_id' => $activity->id,
        'last_message_at' => now(),
    ]);

    // Add user as participant
    $conversation->participants()->attach($u->id, [
        'id' => \Illuminate\Support\Str::uuid()->toString(),
        'role' => 'member',
    ]);

    // Create parent message
    $parent = Message::create([
        'conversation_id' => $conversation->id,
        'user_id' => $u->id,
        'body' => 'Looks fun!',
        'type' => 'text',
    ]);

    // Create reply message
    $reply = Message::create([
        'conversation_id' => $conversation->id,
        'user_id' => $u->id,
        'reply_to_message_id' => $parent->id,
        'body' => 'Count me in!',
        'type' => 'text',
    ]);

    expect($reply->replyTo->id)->toBe($parent->id);
    expect($activity->conversation->id)->toBe($conversation->id);
});

it('connects RSVPs between users and activities', function () {
    $host = makeUser('host');
    $guest = makeUser('guest');
    $activity = makeActivity($host);

    Rsvp::create([
        'user_id' => $guest->id,
        'activity_id' => $activity->id,
        'status' => 'attending',
    ]);

    expect($guest->rsvps()->first()->activity->id)
        ->toBe($activity->id);
});
