<?php

use App\Events\PostConversionPrompted;
use App\Models\Post;
use App\Services\ConversionEligibilityService;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->service = new ConversionEligibilityService;
});

test('prompts post at soft threshold (5 reactions)', function () {
    Event::fake();

    $post = Post::factory()->create([
        'status' => 'active',
        'reaction_count' => 5,
        'conversion_prompted_at' => null,
    ]);

    $result = $this->service->checkAndPrompt($post);

    expect($result['should_prompt'])->toBeTrue();
    expect($result['threshold'])->toBe('soft');
    expect($result['reaction_count'])->toBe(5);

    Event::assertDispatched(PostConversionPrompted::class, function ($event) use ($post) {
        return $event->post->id === $post->id && $event->threshold === 'soft';
    });

    $post->refresh();
    expect($post->conversion_prompted_at)->not->toBeNull();
});

test('prompts post at strong threshold (10 reactions)', function () {
    Event::fake();

    $post = Post::factory()->create([
        'status' => 'active',
        'reaction_count' => 10,
        'conversion_prompted_at' => null,
    ]);

    $result = $this->service->checkAndPrompt($post);

    expect($result['should_prompt'])->toBeTrue();
    expect($result['threshold'])->toBe('strong');
    expect($result['reaction_count'])->toBe(10);

    Event::assertDispatched(PostConversionPrompted::class, function ($event) use ($post) {
        return $event->post->id === $post->id && $event->threshold === 'strong';
    });
});

test('does not prompt if already prompted within 7 days', function () {
    Event::fake();

    $post = Post::factory()->create([
        'status' => 'active',
        'reaction_count' => 5,
        'conversion_prompted_at' => now()->subDays(3),
    ]);

    $result = $this->service->checkAndPrompt($post);

    expect($result['should_prompt'])->toBeFalse();
    expect($result['reason'])->toBe('already_prompted');

    Event::assertNotDispatched(PostConversionPrompted::class);
});

test('re-prompts after 7 days', function () {
    Event::fake();

    $post = Post::factory()->create([
        'status' => 'active',
        'reaction_count' => 5,
        'conversion_prompted_at' => now()->subDays(8),
        'conversion_dismissed_at' => now()->subDays(8),
    ]);

    $result = $this->service->checkAndPrompt($post);

    expect($result['should_prompt'])->toBeTrue();

    Event::assertDispatched(PostConversionPrompted::class);
});

test('does not prompt after 3 dismissals', function () {
    Event::fake();

    $post = Post::factory()->create([
        'status' => 'active',
        'reaction_count' => 5,
        'conversion_dismiss_count' => 3,
    ]);

    $result = $this->service->checkAndPrompt($post);

    expect($result['should_prompt'])->toBeFalse();
    expect($result['reason'])->toBe('dismiss_limit_reached');

    Event::assertNotDispatched(PostConversionPrompted::class);
});

test('does not prompt if post is not active', function () {
    Event::fake();

    $post = Post::factory()->create([
        'status' => 'expired',
        'reaction_count' => 5,
    ]);

    $result = $this->service->checkAndPrompt($post);

    expect($result['should_prompt'])->toBeFalse();
    expect($result['reason'])->toBe('post_not_active');

    Event::assertNotDispatched(PostConversionPrompted::class);
});

test('does not prompt if insufficient reactions', function () {
    Event::fake();

    $post = Post::factory()->create([
        'status' => 'active',
        'reaction_count' => 3,
    ]);

    $result = $this->service->checkAndPrompt($post);

    expect($result['should_prompt'])->toBeFalse();
    expect($result['reason'])->toBe('insufficient_reactions');

    Event::assertNotDispatched(PostConversionPrompted::class);
});

test('idempotency - multiple calls do not create duplicate prompts', function () {
    Event::fake();

    $post = Post::factory()->create([
        'status' => 'active',
        'reaction_count' => 5,
        'conversion_prompted_at' => null,
    ]);

    // First call
    $result1 = $this->service->checkAndPrompt($post);
    expect($result1['should_prompt'])->toBeTrue();

    $post->refresh();
    $firstPromptedAt = $post->conversion_prompted_at;

    // Second call immediately after
    $result2 = $this->service->checkAndPrompt($post->fresh());
    expect($result2['should_prompt'])->toBeFalse();
    expect($result2['reason'])->toBe('already_prompted');

    // Verify only one event was dispatched
    Event::assertDispatchedTimes(PostConversionPrompted::class, 1);

    // Verify timestamp didn't change
    $post->refresh();
    expect($post->conversion_prompted_at->timestamp)->toBe($firstPromptedAt->timestamp);
});

test('threshold level is soft for 5-9 reactions', function () {
    $post5 = Post::factory()->create(['reaction_count' => 5]);
    $post7 = Post::factory()->create(['reaction_count' => 7]);
    $post9 = Post::factory()->create(['reaction_count' => 9]);

    $result5 = $this->service->checkAndPrompt($post5);
    $result7 = $this->service->checkAndPrompt($post7);
    $result9 = $this->service->checkAndPrompt($post9);

    expect($result5['threshold'])->toBe('soft');
    expect($result7['threshold'])->toBe('soft');
    expect($result9['threshold'])->toBe('soft');
});

test('threshold level is strong for 10+ reactions', function () {
    $post10 = Post::factory()->create(['reaction_count' => 10]);
    $post15 = Post::factory()->create(['reaction_count' => 15]);
    $post100 = Post::factory()->create(['reaction_count' => 100]);

    $result10 = $this->service->checkAndPrompt($post10);
    $result15 = $this->service->checkAndPrompt($post15);
    $result100 = $this->service->checkAndPrompt($post100);

    expect($result10['threshold'])->toBe('strong');
    expect($result15['threshold'])->toBe('strong');
    expect($result100['threshold'])->toBe('strong');
});
