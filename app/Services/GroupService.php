<?php

namespace App\Services;

use App\Events\GroupCreated;
use App\Events\GroupJoinRequestApproved;
use App\Events\GroupJoinRequestReceived;
use App\Events\GroupMemberJoined;
use App\Events\GroupMemberRemoved;
use App\Models\Group;
use App\Models\GroupJoinRequest;
use App\Models\GroupMember;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use MatanYadaev\EloquentSpatial\Objects\Point;

class GroupService
{
    public function createGroup(User $user, array $data): Group
    {
        return DB::transaction(function () use ($user, $data) {
            // Build location coordinates if provided
            $locationCoordinates = null;
            if (isset($data['latitude']) && isset($data['longitude'])) {
                $locationCoordinates = new Point($data['latitude'], $data['longitude']);
            }

            $group = Group::create([
                'name' => $data['name'],
                'slug' => str($data['name'])->slug(),
                'description' => $data['description'] ?? null,
                'avatar_url' => $data['avatar_url'] ?? null,
                'cover_image_url' => $data['cover_image_url'] ?? null,
                'privacy' => $data['privacy'],
                'created_by' => $user->id,
                'location_name' => $data['location_name'] ?? null,
                'location_coordinates' => $locationCoordinates,
            ]);

            // Add creator as admin directly within this transaction
            // to avoid nested transaction issues
            $group->memberships()->create([
                'user_id' => $user->id,
                'role' => 'admin',
            ]);

            // Handle tags - can be array of tag IDs or tag names
            if (isset($data['tags']) && ! empty($data['tags'])) {
                $this->syncTagsByName($group, $data['tags']);
            }

            // Dispatch events AFTER transaction commits to avoid serialization issues
            DB::afterCommit(function () use ($group, $user) {
                GroupCreated::dispatch($group);
                GroupMemberJoined::dispatch($group, $user);
            });

            return $group;
        });
    }

    /**
     * Sync tags by name, creating new tags if they don't exist.
     */
    protected function syncTagsByName(Group $group, array $tagNames): void
    {
        $tagIds = [];
        foreach ($tagNames as $tagName) {
            // Skip if it's already an ID (UUID string check)
            if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $tagName)
                || preg_match('/^[0-9a-f]{32}$/i', $tagName)) {
                $tagIds[] = $tagName;

                continue;
            }

            // Find or create tag by name
            $tag = Tag::firstOrCreate(
                ['name' => trim($tagName)],
                ['category' => 'general']
            );
            $tagIds[] = $tag->id;
        }

        $group->tags()->sync($tagIds);
    }

    public function updateGroup(Group $group, array $data): Group
    {
        return DB::transaction(function () use ($group, $data) {
            $group->update([
                'name' => $data['name'] ?? $group->name,
                'slug' => isset($data['name']) ? str($data['name'])->slug() : $group->slug,
                'description' => $data['description'] ?? $group->description,
                'avatar_url' => $data['avatar_url'] ?? $group->avatar_url,
                'cover_image_url' => $data['cover_image_url'] ?? $group->cover_image_url,
                'privacy' => $data['privacy'] ?? $group->privacy,
            ]);

            if (isset($data['tags'])) {
                $this->syncTagsByName($group, $data['tags']);
            }

            return $group;
        });
    }

    public function deleteGroup(Group $group): bool
    {
        return DB::transaction(function () use ($group) {
            $group->memberships()->delete();
            $group->joinRequests()->delete();
            $group->posts()->delete();
            $group->activities()->delete();
            $group->tags()->detach();
            $group->conversation()?->delete();

            return $group->delete();
        });
    }

    public function addMember(Group $group, User $user, string $role = 'member'): GroupMember
    {
        return DB::transaction(function () use ($group, $user, $role) {
            $member = $group->memberships()->create([
                'user_id' => $user->id,
                'role' => $role,
                // Note: 'status' column doesn't exist in group_members table
            ]);

            // Member count is updated by UpdateGroupMemberCount listener
            // Removed: $group->increment('member_count'); to prevent duplicate update

            // Dispatch event AFTER transaction commits to avoid serialization issues
            DB::afterCommit(function () use ($group, $user) {
                GroupMemberJoined::dispatch($group, $user);
            });

            return $member;
        });
    }

    public function removeMember(Group $group, User $user): bool
    {
        return DB::transaction(function () use ($group, $user) {
            $member = $group->memberships()->where('user_id', $user->id)->firstOrFail();

            if ($member->role === 'admin' && $group->memberships()->where('role', 'admin')->count() === 1) {
                throw new \Exception('Cannot remove the last admin from the group.');
            }

            $member->delete();

            // Member count is updated by UpdateGroupMemberCount listener
            // Removed: $group->decrement('member_count'); to prevent duplicate update

            // Dispatch event AFTER transaction commits to avoid serialization issues
            DB::afterCommit(function () use ($group, $user) {
                GroupMemberRemoved::dispatch($group, $user);
            });

            return true;
        });
    }

    public function updateMemberRole(Group $group, User $user, string $role): GroupMember
    {
        $member = $group->memberships()->where('user_id', $user->id)->firstOrFail();
        $member->update(['role' => $role]);

        return $member;
    }

    public function searchPublicGroups(string $query, ?array $tagIds = null): Collection
    {
        $groups = Group::where('privacy', 'public')
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', '%'.$query.'%')
                    ->orWhere('description', 'like', '%'.$query.'%');
            });

        if ($tagIds) {
            $groups->whereHas('tags', function ($q) use ($tagIds) {
                $q->whereIn('tags.id', $tagIds);
            });
        }

        return $groups->get();
    }

    public function getGroupsByTag(Tag $tag): Collection
    {
        return $tag->groups()->where('privacy', 'public')->get();
    }

    public function getUserGroups(User $user): Collection
    {
        return $user->groups()->get();
    }

    public function getGroupMembers(Group $group): Collection
    {
        return $group->memberships()->with('user')->get();
    }

    public function syncGroupTags(Group $group, array $tagIds): void
    {
        $group->tags()->sync($tagIds);
    }

    public function createJoinRequest(Group $group, User $user): GroupJoinRequest
    {
        return DB::transaction(function () use ($group, $user) {
            // Check for existing pending request - return it instead of creating duplicate
            $existingPending = $group->joinRequests()
                ->where('user_id', $user->id)
                ->where('status', 'pending')
                ->first();

            if ($existingPending) {
                return $existingPending;
            }

            // Delete any existing requests (approved/denied) to avoid unique constraint issues
            // when re-requesting to join after being removed or denied
            $group->joinRequests()
                ->where('user_id', $user->id)
                ->whereIn('status', ['approved', 'denied'])
                ->delete();

            $request = $group->joinRequests()->create([
                'user_id' => $user->id,
                'status' => 'pending',
            ]);

            GroupJoinRequestReceived::dispatch($group, $user, $request);

            return $request;
        });
    }

    public function approveJoinRequest(GroupJoinRequest $request, User $admin): bool
    {
        return DB::transaction(function () use ($request, $admin) {
            // Delete any existing approved/denied requests for this user to avoid unique constraint violation
            $request->group->joinRequests()
                ->where('user_id', $request->user_id)
                ->where('id', '!=', $request->id)
                ->whereIn('status', ['approved', 'denied'])
                ->delete();

            $request->update(['status' => 'approved']);
            $this->addMember($request->group, $request->user);

            GroupJoinRequestApproved::dispatch($request->group, $request->user, $admin);

            return true;
        });
    }

    public function denyJoinRequest(GroupJoinRequest $request, User $admin): bool
    {
        $request->update(['status' => 'denied']);

        return true;
    }

    public function isGroupMember(Group $group, User $user): bool
    {
        return $group->members()->where('user_id', $user->id)->exists();
    }

    public function isGroupAdmin(Group $group, User $user): bool
    {
        return $group->members()->where('user_id', $user->id)->where('role', 'admin')->exists();
    }
}
