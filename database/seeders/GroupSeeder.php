<?php

namespace Database\Seeders;

use App\Models\Group;
use App\Models\GroupMember;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;

class GroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::factory(10)->create();
        $tags = Tag::factory(20)->create();
        $adminUser = $users->first();

        Group::factory(5)->create([
            'privacy' => 'public',
            'created_by' => $adminUser->id,
        ])->each(function ($group) use ($adminUser, $users, $tags) {
            // Add 1 admin
            GroupMember::factory()->create([
                'group_id' => $group->id,
                'user_id' => $adminUser->id,
                'role' => 'admin',
            ]);
            // Add 5 members, ensuring they are not the admin
            $members = $users->where('id', '!=', $adminUser->id)->random(5);
            $group->members()->attach($members);
            // Add 3 tags
            $group->tags()->attach($tags->random(3));
        });

        Group::factory(5)->create([
            'privacy' => 'private',
            'created_by' => $adminUser->id,
        ])->each(function ($group) use ($adminUser, $users, $tags) {
            // Add 1 admin
            GroupMember::factory()->create([
                'group_id' => $group->id,
                'user_id' => $adminUser->id,
                'role' => 'admin',
            ]);
            // Add 3 members, ensuring they are not the admin
            $members = $users->where('id', '!=', $adminUser->id)->random(3);
            $group->members()->attach($members);
            // Add 2 tags
            $group->tags()->attach($tags->random(2));
        });
    }
}
