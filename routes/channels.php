<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('user.{userId}', \App\Broadcasting\UserChannel::class);

Broadcast::channel('conversation.{conversationId}', function ($user, string $conversationId) {
    $conversation = \App\Models\Conversation::find($conversationId);

    return $conversation !== null && $user->can('view', $conversation);
});
