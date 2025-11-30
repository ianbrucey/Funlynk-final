<?php

use App\Models\Post;
use App\Models\PostConversion;
use Illuminate\Support\Facades\Schema;

test('posts table has conversion tracking columns', function () {
    expect(Schema::hasColumn('posts', 'conversion_prompted_at'))->toBeTrue();
    expect(Schema::hasColumn('posts', 'conversion_dismissed_at'))->toBeTrue();
    expect(Schema::hasColumn('posts', 'conversion_dismiss_count'))->toBeTrue();
});

test('posts table has conversion tracking indexes', function () {
    $indexes = Schema::getIndexes('posts');
    $indexNames = array_column($indexes, 'name');

    expect($indexNames)->toContain('posts_conversion_prompted_at_index');
    expect($indexNames)->toContain('posts_status_reaction_count_index');
});

test('post_conversions table has notification tracking columns', function () {
    expect(Schema::hasColumn('post_conversions', 'interested_users_notified'))->toBeTrue();
    expect(Schema::hasColumn('post_conversions', 'invited_users_notified'))->toBeTrue();
    expect(Schema::hasColumn('post_conversions', 'notification_sent_at'))->toBeTrue();
});

test('post_reactions table has composite index', function () {
    $indexes = Schema::getIndexes('post_reactions');
    $indexNames = array_column($indexes, 'name');

    expect($indexNames)->toContain('post_reactions_post_id_reaction_type_index');
});

test('post model casts conversion tracking columns correctly', function () {
    $post = Post::factory()->create([
        'conversion_prompted_at' => now(),
        'conversion_dismissed_at' => now(),
        'conversion_dismiss_count' => 2,
    ]);

    expect($post->conversion_prompted_at)->toBeInstanceOf(\Carbon\Carbon::class);
    expect($post->conversion_dismissed_at)->toBeInstanceOf(\Carbon\Carbon::class);
    expect($post->conversion_dismiss_count)->toBe(2);
});

test('post model eligibleForConversion scope works', function () {
    Post::factory()->create(['status' => 'active', 'reaction_count' => 3]);
    Post::factory()->create(['status' => 'active', 'reaction_count' => 5]);
    Post::factory()->create(['status' => 'active', 'reaction_count' => 10]);
    Post::factory()->create(['status' => 'expired', 'reaction_count' => 5]);

    $eligible = Post::eligibleForConversion()->get();

    expect($eligible)->toHaveCount(2);
    expect($eligible->min('reaction_count'))->toBe(5);
});

test('post model notPrompted scope works', function () {
    Post::factory()->create(['conversion_prompted_at' => now()]);
    Post::factory()->create(['conversion_prompted_at' => null]);
    Post::factory()->create(['conversion_prompted_at' => null]);

    $notPrompted = Post::notPrompted()->get();

    expect($notPrompted)->toHaveCount(2);
});

test('post model convertedPosts scope works', function () {
    Post::factory()->create(['status' => 'active']);
    Post::factory()->create(['status' => 'converted']);
    Post::factory()->create(['status' => 'converted']);

    $converted = Post::convertedPosts()->get();

    expect($converted)->toHaveCount(2);
});

test('post isEligibleForConversion helper works', function () {
    $eligible = Post::factory()->create([
        'status' => 'active',
        'reaction_count' => 5,
        'conversion_dismiss_count' => 0,
    ]);

    $notEnoughReactions = Post::factory()->create([
        'status' => 'active',
        'reaction_count' => 3,
    ]);

    $dismissLimitReached = Post::factory()->create([
        'status' => 'active',
        'reaction_count' => 5,
        'conversion_dismiss_count' => 3,
    ]);

    expect($eligible->isEligibleForConversion())->toBeTrue();
    expect($notEnoughReactions->isEligibleForConversion())->toBeFalse();
    expect($dismissLimitReached->isEligibleForConversion())->toBeFalse();
});

test('post hasReachedDismissLimit helper works', function () {
    $post1 = Post::factory()->create(['conversion_dismiss_count' => 2]);
    $post2 = Post::factory()->create(['conversion_dismiss_count' => 3]);
    $post3 = Post::factory()->create(['conversion_dismiss_count' => 5]);

    expect($post1->hasReachedDismissLimit())->toBeFalse();
    expect($post2->hasReachedDismissLimit())->toBeTrue();
    expect($post3->hasReachedDismissLimit())->toBeTrue();
});

test('post shouldReprompt helper works', function () {
    $neverDismissed = Post::factory()->create(['conversion_dismissed_at' => null]);
    $recentlyDismissed = Post::factory()->create(['conversion_dismissed_at' => now()->subDays(3)]);
    $oldDismissal = Post::factory()->create(['conversion_dismissed_at' => now()->subDays(8)]);

    expect($neverDismissed->shouldReprompt())->toBeFalse();
    expect($recentlyDismissed->shouldReprompt())->toBeFalse();
    expect($oldDismissal->shouldReprompt())->toBeTrue();
});

test('post_conversion model casts notification tracking columns correctly', function () {
    $conversion = PostConversion::factory()->create([
        'interested_users_notified' => 10,
        'invited_users_notified' => 5,
        'notification_sent_at' => now(),
    ]);

    expect($conversion->interested_users_notified)->toBe(10);
    expect($conversion->invited_users_notified)->toBe(5);
    expect($conversion->notification_sent_at)->toBeInstanceOf(\Carbon\Carbon::class);
});
