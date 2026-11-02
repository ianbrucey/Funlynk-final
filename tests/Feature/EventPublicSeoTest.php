<?php

use App\Livewire\Activities\ActivityDetail;
use App\Livewire\PublicEventView;
use App\Models\Activity;
use App\Models\User;
use Livewire\Livewire;

/**
 * SEO + privacy contract for the public event pages.
 *
 * Covers the events.public restoration follow-through:
 *  - guests get a fully-formed SEO head on the slug page
 *    (per-page <title>, meta description, canonical, and a
 *    schema.org Event JSON-LD whose price comes from price_cents);
 *  - non-public events are not guest-visible on either URL;
 *  - the legacy UUID URL permanently redirects guests to the
 *    canonical slug URL while signed-in users keep the app page;
 *  - the share actions generate slug URLs without throwing.
 */
function seoEvent(array $attributes = []): Activity
{
    return Activity::factory()->create(array_merge([
        'host_id' => User::factory()->create()->id,
        'title' => 'Sunset Skate SEO Social',
        'description' => 'A weekly sunset skate meetup by the beach with music and lights.',
    ], $attributes));
}

test('guest gets a complete SEO head on the public slug page', function () {
    $activity = seoEvent([
        'price_cents' => 2500,
        'currency' => 'USD',
        'payment_type' => 'online',
    ]);

    $url = route('events.public', $activity->slug);

    $response = $this->get($url);
    $response->assertOk();

    $html = $response->getContent();
    $head = explode('</head>', $html)[0];

    // Per-page title (no more generic site title on detail pages)
    expect($head)->toContain('<title>Sunset Skate SEO Social');

    // Meta description lives in <head>, not the body
    expect($head)->toContain('name="description"');

    // Canonical resolves to the slug URL
    expect($head)->toContain('<link rel="canonical" href="'.$url.'">');

    // OpenGraph URL is the canonical URL too
    expect($head)->toContain('<meta property="og:url" content="'.$url.'">');

    // schema.org Event JSON-LD is emitted in <head> ...
    expect($head)->toContain('application/ld+json');

    expect(preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $head, $m))->toBe(1);

    $schema = json_decode($m[1], true);

    // ... and its offer price comes from price_cents (2500 -> "25.00")
    expect($schema['@type'])->toBe('Event')
        ->and($schema['name'])->toBe('Sunset Skate SEO Social')
        ->and($schema['offers']['price'])->toBe('25.00')
        ->and($schema['offers']['priceCurrency'])->toBe('USD')
        ->and($schema['offers']['url'])->toBe($url)
        ->and($schema['isAccessibleForFree'] ?? null)->not->toBeTrue();

    // Share UI renders on the page
    expect($html)->toContain('wire:click="share(');
});

test('free event JSON-LD has no paid offer and is marked free', function () {
    $activity = seoEvent(['price_cents' => null, 'payment_type' => 'free']);

    $response = $this->get(route('events.public', $activity->slug));
    $response->assertOk();

    $head = explode('</head>', $response->getContent())[0];
    expect(preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $head, $m))->toBe(1);

    $schema = json_decode($m[1], true);

    expect($schema['isAccessibleForFree'] ?? null)->toBeTrue()
        ->and($schema)->not->toHaveKey('offers');
});

test('guests cannot see a non-public event on the public slug page', function () {
    $activity = seoEvent([
        'is_public' => false,
        'title' => 'Secret Rehearsal Night',
    ]);

    $response = $this->get(route('events.public', $activity->slug));

    $response->assertNotFound();
    expect($response->getContent())->not->toContain('Secret Rehearsal Night');
});

test('guests cannot see a non-public event on the UUID app page', function () {
    $activity = seoEvent([
        'is_public' => false,
        'title' => 'Secret Rehearsal Night',
    ]);

    $response = $this->get(route('events.show', $activity->id));

    $response->assertNotFound();
    expect($response->getContent())->not->toContain('Secret Rehearsal Night');
});

test('the host can still open their non-public event on both pages', function () {
    $host = User::factory()->create();
    $activity = seoEvent([
        'host_id' => $host->id,
        'is_public' => false,
    ]);

    $this->actingAs($host)
        ->get(route('events.show', $activity->id))
        ->assertOk();

    $this->actingAs($host)
        ->get(route('events.public', $activity->slug))
        ->assertOk();
});

test('guest hitting the UUID URL of a public event is permanently redirected to the slug page', function () {
    $activity = seoEvent();

    $response = $this->get(route('events.show', $activity->id));

    $response->assertStatus(301);
    expect($response->headers->get('Location'))
        ->toBe(route('events.public', $activity->slug));
});

test('the guest redirect preserves referral and UTM query parameters', function () {
    $activity = seoEvent();

    $response = $this->get(route('events.show', $activity->id).'?utm_source=instagram&ref=abc123');

    $response->assertStatus(301);

    // Both parameters survive the redirect (Symfony normalizes the
    // order of the query string, so assert presence, not sequence).
    $location = $response->headers->get('Location');

    expect($location)->toStartWith(route('events.public', $activity->slug).'?')
        ->and($location)->toContain('utm_source=instagram')
        ->and($location)->toContain('ref=abc123');
});

test('signed-in users keep the app event page and it canonicalizes to the slug URL', function () {
    $host = User::factory()->create();
    $activity = seoEvent(['host_id' => $host->id]);

    $response = $this->actingAs($host)->get(route('events.show', $activity->id));

    $response->assertOk();

    $head = explode('</head>', $response->getContent())[0];

    expect($head)->toContain('<title>Sunset Skate SEO Social')
        ->and($head)->toContain('name="description"')
        ->and($head)->toContain('<link rel="canonical" href="'.route('events.public', $activity->slug).'">');
});

test('share action on the public page generates the slug URL without throwing', function () {
    $activity = seoEvent();

    // The dispatched browser event fires (previously this call chain
    // threw RouteNotFoundException on the missing events.public route).
    Livewire::test(PublicEventView::class, ['activity' => $activity])
        ->call('share', 'twitter')
        ->assertDispatched('share-url-generated');

    $this->assertDatabaseHas('social_shares', [
        'activity_id' => $activity->id,
        'platform' => 'twitter',
    ]);

    // ... and the URL it hands out is the canonical slug URL.
    $url = app(\App\Services\SocialShareService::class)
        ->generateShareUrl($activity, 'twitter');

    expect($url)->toContain('/e/'.$activity->slug);
});

test('share action on the app event page generates the slug URL without throwing', function () {
    $host = User::factory()->create();
    $activity = seoEvent(['host_id' => $host->id]);

    $this->actingAs($host);

    Livewire::test(ActivityDetail::class, ['activity' => $activity])
        ->call('share', 'facebook')
        ->assertDispatched('share-url-generated');

    $this->assertDatabaseHas('social_shares', [
        'activity_id' => $activity->id,
        'platform' => 'facebook',
    ]);
});
