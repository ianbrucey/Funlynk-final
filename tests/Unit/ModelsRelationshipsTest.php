<?php

use App\Models\Activity;
use App\Models\Comment;
use App\Models\Follow;
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

it('links posts to users, reactions, and evolved activities', function () {
    $u = makeUser('poster');

    $post = Post::create([
        'user_id' => $u->id,
        'content' => 'Who is down for basketball tonight?',
    ]);

    PostReaction::create([
        'post_id' => $post->id,
        'user_id' => $u->id,
        'reaction_type' => 'im_down',
    ]);

    // Create an activity and set post->evolved_to_event_id
    $activity = makeActivity($u);
    $post->evolved_to_event_id = $activity->id;
    $post->save();

    expect($post->user->id)->toBe($u->id);
    expect($post->reactions()->count())->toBe(1);
    expect($post->evolvedActivity)->not()->toBeNull();
    expect($post->evolvedActivity->id)->toBe($activity->id);
});

it('supports threaded comments for activities', function () {
    $u = makeUser('chatter');
    $activity = makeActivity($u);

    $parent = Comment::create([
        'activity_id' => $activity->id,
        'user_id' => $u->id,
        'content' => 'Looks fun!',
    ]);

    $reply = Comment::create([
        'activity_id' => $activity->id,
        'user_id' => $u->id,
        'parent_comment_id' => $parent->id,
        'content' => 'Count me in!',
    ]);

    expect($reply->parent->id)->toBe($parent->id);
    expect($parent->replies()->pluck('id'))
        ->toContain($reply->id);
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
