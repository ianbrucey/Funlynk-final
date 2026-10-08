<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Group;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GroupChatService extends ChatService
{
    public function getOrCreateGroupChat(Group $group): Conversation
    {
        return DB::transaction(function () use ($group) {
            // Reload the relationship to ensure we get the latest data
            $group->load('conversation');
            $conversation = $group->conversation;

            if (! $conversation) {
                $conversation = $group->conversation()->create([
                    'type' => 'group',
                ]);

                // Add all existing group members as participants
                $memberIds = $group->members->pluck('id')->toArray();
                if (! empty($memberIds)) {
                    $conversation->participants()->attach($memberIds);
                }
            }

            return $conversation;
        });
    }

    public function sendGroupMessage(Group $group, User $user, string $messageContent): Message
    {
        // Ensure the user is a member of the group before sending a message
        if (! $group->members()->where('user_id', $user->id)->exists()) {
            throw new \Exception('User is not a member of this group.');
        }

        $conversation = $this->getOrCreateGroupChat($group);

        // Use the parent ChatService's sendMessage method
        return parent::sendMessage($conversation, $user, $messageContent);
    }

    public function getGroupChatMessages(Group $group, int $limit = 50): Collection
    {
        $conversation = $group->conversation;

        if (! $conversation) {
            return collect(); // No chat yet, return empty collection
        }

        return $conversation->messages()->with('user')->oldest()->limit($limit)->get();
    }

    public function addParticipantToGroupChat(Group $group, User $user): void
    {
        $conversation = $this->getOrCreateGroupChat($group);

        if (! $conversation->participants()->where('user_id', $user->id)->exists()) {
            $conversation->participants()->attach($user->id);
        }
    }

    public function removeParticipantFromGroupChat(Group $group, User $user): void
    {
        $conversation = $group->conversation;

        if ($conversation) {
            $conversation->participants()->detach($user->id);
        }
    }
}
