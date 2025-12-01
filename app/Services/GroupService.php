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

class GroupService
{
    public function createGroup(User $user, array $data): Group
    {
        return DB::transaction(function () use ($user, $data) {
            $group = Group::create([
                'name' => $data['name'],
                'slug' => str($data['name'])->slug(),
                'description' => $data['description'] ?? null,
                'avatar_url' => $data['avatar_url'] ?? null,
                'cover_image_url' => $data['cover_image_url'] ?? null,
                'privacy' => $data['privacy'],
                'created_by' => $user->id,
            ]);

            $this->addMember($group, $user, 'admin');

            if (isset($data['tags'])) {
                $group->tags()->sync($data['tags']);
            }

            GroupCreated::dispatch($group);

            return $group;
        });
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
                $group->tags()->sync($data['tags']);
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
                'status' => 'approved',
            ]);

            // Member count is updated by UpdateGroupMemberCount listener
            // Removed: $group->increment('member_count'); to prevent duplicate update

            GroupMemberJoined::dispatch($group, $user);

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

            GroupMemberRemoved::dispatch($group, $user);

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
