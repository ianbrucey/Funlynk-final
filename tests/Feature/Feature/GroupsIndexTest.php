<?php

namespace Tests\Feature\Feature;

use App\Livewire\Groups\GroupsIndex;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GroupsIndexTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_displays_groups_with_case_insensitive_search(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Group::factory()->create(['name' => 'Hiking Club', 'privacy' => 'public']);
        Group::factory()->create(['name' => 'HIKING ADVENTURES', 'privacy' => 'public']);
        Group::factory()->create(['name' => 'casual hiking', 'privacy' => 'public']);
        Group::factory()->create(['name' => 'Photography Group', 'privacy' => 'public']);

        Livewire::test(GroupsIndex::class)
            ->set('search', 'hiking')
            ->assertSee('Hiking Club')
            ->assertSee('HIKING ADVENTURES')
            ->assertSee('casual hiking')
            ->assertDontSee('Photography Group');

        Livewire::test(GroupsIndex::class)
            ->set('search', 'HIKING')
            ->assertSee('Hiking Club')
            ->assertSee('HIKING ADVENTURES')
            ->assertSee('casual hiking')
            ->assertDontSee('Photography Group');

        Livewire::test(GroupsIndex::class)
            ->set('search', 'HiKiNg')
            ->assertSee('Hiking Club')
            ->assertSee('HIKING ADVENTURES')
            ->assertSee('casual hiking')
            ->assertDontSee('Photography Group');
    }

    /** @test */
    public function it_displays_groups_with_case_insensitive_search_by_description(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Group::factory()->create(['name' => 'Group 1', 'description' => 'A club for hiking enthusiasts', 'privacy' => 'public']);
        Group::factory()->create(['name' => 'Group 2', 'description' => 'Explore HIKING trails', 'privacy' => 'public']);
        Group::factory()->create(['name' => 'Group 3', 'description' => 'Enjoy casual hiking trips', 'privacy' => 'public']);
        Group::factory()->create(['name' => 'Group 4', 'description' => 'Photography is fun', 'privacy' => 'public']);

        Livewire::test(GroupsIndex::class)
            ->set('search', 'hiking')
            ->assertSee('Group 1')
            ->assertSee('Group 2')
            ->assertSee('Group 3')
            ->assertDontSee('Group 4');

        Livewire::test(GroupsIndex::class)
            ->set('search', 'HIKING')
            ->assertSee('Group 1')
            ->assertSee('Group 2')
            ->assertSee('Group 3')
            ->assertDontSee('Group 4');

        Livewire::test(GroupsIndex::class)
            ->set('search', 'HiKiNg')
            ->assertSee('Group 1')
            ->assertSee('Group 2')
            ->assertSee('Group 3')
            ->assertDontSee('Group 4');
    }
}
