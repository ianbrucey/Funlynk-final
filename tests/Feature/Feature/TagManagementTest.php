<?php

use App\Jobs\UpdateTagAnalytics;
use App\Models\Activity;
use App\Models\Tag;
use App\Models\User;
use App\Services\TagService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

describe('Tag Model', function () {
    it('can create a tag', function () {
        $tag = Tag::create([
            'name' => 'Basketball',
            'category' => 'sports',
            'description' => 'Basketball activities',
            'usage_count' => 0,
            'is_featured' => false,
        ]);

        expect($tag)->toBeInstanceOf(Tag::class)
            ->and($tag->name)->toBe('Basketball')
            ->and($tag->category)->toBe('sports')
            ->and($tag->usage_count)->toBe(0);
    });

    it('has activities relationship', function () {
        $tag = Tag::factory()->create();
        $activity = Activity::factory()->create();

        $activity->tags()->attach($tag->id);

        expect($tag->activities)->toHaveCount(1)
            ->and($tag->activities->first()->id)->toBe($activity->id);
    });
});

describe('TagService', function () {
    beforeEach(function () {
        $this->tagService = app(TagService::class);
    });

    it('can create a tag with auto-generated slug', function () {
        $tag = $this->tagService->createTag('Basketball', 'sports', 'Basketball activities');

        expect($tag->name)->toBe('Basketball')
            ->and($tag->slug)->toBe('basketball')
            ->and($tag->category)->toBe('sports')
            ->and($tag->usage_count)->toBe(0);
    });

    it('generates unique slugs for duplicate names', function () {
        $tag1 = $this->tagService->createTag('Basketball');
        $tag2 = $this->tagService->createTag('Basketball');

        expect($tag1->slug)->toBe('basketball')
            ->and($tag2->slug)->toBe('basketball-1');
    });

    it('validates tag names correctly', function () {
        // Valid tag
        $result = $this->tagService->validateTag('Basketball');
        expect($result['valid'])->toBeTrue();

        // Too short
        $result = $this->tagService->validateTag('A');
        expect($result['valid'])->toBeFalse()
            ->and($result['message'])->toContain('at least 2 characters');

        // Too long
        $result = $this->tagService->validateTag(str_repeat('A', 51));
        expect($result['valid'])->toBeFalse()
            ->and($result['message'])->toContain('50 characters or less');

        // Already exists
        Tag::factory()->create(['name' => 'Existing']);
        $result = $this->tagService->validateTag('Existing');
        expect($result['valid'])->toBeFalse()
            ->and($result['message'])->toContain('already exists');
    });

    it('can get tag suggestions', function () {
        Tag::factory()->create(['name' => 'Basketball', 'usage_count' => 10]);
        Tag::factory()->create(['name' => 'Baseball', 'usage_count' => 5]);
        Tag::factory()->create(['name' => 'Football', 'usage_count' => 3]);

        $suggestions = $this->tagService->getSuggestions('ball', 10);

        expect($suggestions)->toHaveCount(3)
            ->and($suggestions->first()->name)->toBe('Basketball') // Highest usage first
            ->and($suggestions->last()->name)->toBe('Football');
    });

    it('returns empty suggestions for empty query', function () {
        $suggestions = $this->tagService->getSuggestions('', 10);

        expect($suggestions)->toHaveCount(0);
    });

    it('can increment usage count', function () {
        $tag = Tag::factory()->create(['usage_count' => 5]);

        $this->tagService->incrementUsage($tag);

        expect($tag->fresh()->usage_count)->toBe(6);
    });

    it('can decrement usage count', function () {
        $tag = Tag::factory()->create(['usage_count' => 5]);

        $this->tagService->decrementUsage($tag);

        expect($tag->fresh()->usage_count)->toBe(4);
    });

    it('can get trending tags', function () {
        // Create tags with different usage counts
        $tag1 = Tag::factory()->create(['name' => 'Popular', 'usage_count' => 100]);
        $tag2 = Tag::factory()->create(['name' => 'Medium', 'usage_count' => 50]);
        $tag3 = Tag::factory()->create(['name' => 'Low', 'usage_count' => 10]);
        $tag4 = Tag::factory()->create(['name' => 'Unused', 'usage_count' => 0]);

        $trending = $this->tagService->getTrendingTags(3, 7);

        expect($trending)->toHaveCount(3)
            ->and($trending->first()->name)->toBe('Popular')
            ->and($trending->pluck('name')->contains('Unused'))->toBeFalse(); // Unused tags excluded
    });

    it('caches trending tags', function () {
        Tag::factory()->create(['usage_count' => 100]);

        // First call
        $trending1 = $this->tagService->getTrendingTags(10, 7);

        // Second call should use cache
        Cache::shouldReceive('remember')
            ->once()
            ->andReturn($trending1);

        $trending2 = $this->tagService->getTrendingTags(10, 7);

        expect($trending1)->toEqual($trending2);
    });

    it('can get featured tags', function () {
        Tag::factory()->create(['name' => 'Featured1', 'is_featured' => true, 'usage_count' => 100]);
        Tag::factory()->create(['name' => 'Featured2', 'is_featured' => true, 'usage_count' => 50]);
        Tag::factory()->create(['name' => 'NotFeatured', 'is_featured' => false, 'usage_count' => 200]);

        $featured = $this->tagService->getFeaturedTags(5);

        expect($featured)->toHaveCount(2)
            ->and($featured->first()->name)->toBe('Featured1') // Highest usage first
            ->and($featured->pluck('is_featured')->unique())->toEqual(collect([true]));
    });

    it('can get analytics by category', function () {
        Tag::factory()->create(['category' => 'sports', 'usage_count' => 100]);
        Tag::factory()->create(['category' => 'sports', 'usage_count' => 50]);
        Tag::factory()->create(['category' => 'music', 'usage_count' => 75]);

        $analytics = $this->tagService->getAnalyticsByCategory();

        expect($analytics)->toHaveCount(2)
            ->and($analytics->first()->category)->toBe('sports')
            ->and($analytics->first()->total_usage)->toBe(150);
    });

    it('can get unused tags', function () {
        Tag::factory()->create(['usage_count' => 0, 'created_at' => now()->subDays(40)]);
        Tag::factory()->create(['usage_count' => 0, 'created_at' => now()->subDays(20)]); // Too recent
        Tag::factory()->create(['usage_count' => 5, 'created_at' => now()->subDays(40)]); // In use

        $unused = $this->tagService->getUnusedTags(30);

        expect($unused)->toHaveCount(1);
    });

    it('can merge tags', function () {
        $source = Tag::factory()->create(['name' => 'Soccer', 'usage_count' => 5]);
        $target = Tag::factory()->create(['name' => 'Football', 'usage_count' => 10]);

        $activity1 = Activity::factory()->create();
        $activity2 = Activity::factory()->create();

        $activity1->tags()->attach($source->id);
        $activity2->tags()->attach($source->id);

        $this->tagService->mergeTags($source, $target);

        expect(Tag::find($source->id))->toBeNull() // Source deleted
            ->and($target->fresh()->usage_count)->toBeGreaterThanOrEqual(10); // Usage count updated
    });

    it('can recalculate usage counts', function () {
        $tag1 = Tag::factory()->create(['usage_count' => 999]); // Wrong count
        $tag2 = Tag::factory()->create(['usage_count' => 999]); // Wrong count

        $activity = Activity::factory()->create();
        $activity->tags()->attach([$tag1->id, $tag2->id]);

        $this->tagService->recalculateUsageCounts();

        expect($tag1->fresh()->usage_count)->toBe(1)
            ->and($tag2->fresh()->usage_count)->toBe(1);
    });
});

describe('UpdateTagAnalytics Job', function () {
    it('can update specific tag usage count', function () {
        $tag = Tag::factory()->create(['usage_count' => 0]);
        $activity = Activity::factory()->create();
        $activity->tags()->attach($tag->id);

        UpdateTagAnalytics::dispatch($tag->id);

        expect($tag->fresh()->usage_count)->toBe(1);
    });

    it('can update all tags usage counts', function () {
        $tag1 = Tag::factory()->create(['usage_count' => 999]);
        $tag2 = Tag::factory()->create(['usage_count' => 999]);

        $activity = Activity::factory()->create();
        $activity->tags()->attach([$tag1->id, $tag2->id]);

        UpdateTagAnalytics::dispatch();

        expect($tag1->fresh()->usage_count)->toBe(1)
            ->and($tag2->fresh()->usage_count)->toBe(1);
    });

    it('clears trending cache after update', function () {
        Cache::shouldReceive('forget')
            ->once()
            ->with('tags:trending');

        $tag = Tag::factory()->create();
        UpdateTagAnalytics::dispatch($tag->id);
    });
});

describe('TagPolicy', function () {
    it('allows anyone to view tags', function () {
        $user = User::factory()->create();
        $tag = Tag::factory()->create();

        expect($user->can('viewAny', Tag::class))->toBeTrue()
            ->and($user->can('view', $tag))->toBeTrue();
    });

    it('allows authenticated users to create tags', function () {
        $user = User::factory()->create();

        expect($user->can('create', Tag::class))->toBeTrue();
    });

    it('prevents deletion of tags in use', function () {
        $user = User::factory()->create();
        $tag = Tag::factory()->create(['usage_count' => 5]);

        expect($user->can('delete', $tag))->toBeFalse();
    });

    it('allows deletion of unused tags', function () {
        $user = User::factory()->create();
        $tag = Tag::factory()->create(['usage_count' => 0]);

        expect($user->can('delete', $tag))->toBeTrue();
    });
});

describe('Tag Autocomplete Component', function () {
    it('can render tag autocomplete component', function () {
        Livewire::test('tags.tag-autocomplete')
            ->assertStatus(200)
            ->assertSee('Search or create tags');
    });

    it('shows suggestions when searching', function () {
        Tag::factory()->create(['name' => 'Basketball']);

        Livewire::test('tags.tag-autocomplete')
            ->set('search', 'basket')
            ->assertSet('showSuggestions', true)
            ->assertSee('Basketball');
    });

    it('can select a tag', function () {
        $tag = Tag::factory()->create(['name' => 'Basketball']);

        Livewire::test('tags.tag-autocomplete')
            ->call('selectTag', $tag->id)
            ->assertSet('selectedTags', function ($selectedTags) use ($tag) {
                return count($selectedTags) === 1 && $selectedTags[0]['id'] === $tag->id;
            });
    });

    it('can remove a tag', function () {
        $tag = Tag::factory()->create();

        Livewire::test('tags.tag-autocomplete', ['selectedTags' => [['id' => $tag->id, 'name' => $tag->name]]])
            ->call('removeTag', 0)
            ->assertSet('selectedTags', []);
    });

    it('respects max tags limit', function () {
        $tags = Tag::factory()->count(15)->create();

        $component = Livewire::test('tags.tag-autocomplete')
            ->set('maxTags', 10);

        foreach ($tags->take(10) as $tag) {
            $component->call('selectTag', $tag->id);
        }

        // Try to add 11th tag
        $component->call('selectTag', $tags->last()->id);

        expect($component->get('selectedTags'))->toHaveCount(10);
    });
});

describe('Trending Tags Component', function () {
    it('can render trending tags component', function () {
        Livewire::test('tags.trending-tags')
            ->assertStatus(200)
            ->assertSee('Trending Tags');
    });

    it('displays trending tags in order', function () {
        Tag::factory()->create(['name' => 'Popular', 'usage_count' => 100]);
        Tag::factory()->create(['name' => 'Medium', 'usage_count' => 50]);

        Livewire::test('tags.trending-tags', ['limit' => 10])
            ->assertSee('Popular')
            ->assertSee('Medium')
            ->assertSeeInOrder(['Popular', 'Medium']);
    });

    it('shows empty state when no trending tags', function () {
        Livewire::test('tags.trending-tags')
            ->assertSee('No trending tags yet');
    });
});
