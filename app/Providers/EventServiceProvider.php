<?php

namespace App\Providers;

use App\Events\PostReacted;
use App\Listeners\BroadcastGroupUpdate;
use App\Listeners\CheckPostConversion;
use App\Listeners\ManageGroupChatParticipants;
use App\Listeners\SendGroupNotification;
use App\Listeners\SendPostReactionNotification;
use App\Listeners\UpdateGroupMemberCount;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        PostReacted::class => [
            SendPostReactionNotification::class,
            CheckPostConversion::class,
        ],
    ];

    /**
     * The subscriber classes to register.
     *
     * @var array
     */
    protected $subscribe = [
        SendGroupNotification::class,
        BroadcastGroupUpdate::class,
        UpdateGroupMemberCount::class,
        ManageGroupChatParticipants::class,
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
