<?php

use App\Jobs\SendNeedsCheckReminders;
use App\Livewire\Events\EventNeeds;
use App\Models\Activity;
use App\Models\EventNeed;
use App\Models\EventNeedClaim;
use App\Models\Group;
use App\Models\Notification;
use App\Models\Rsvp;
use App\Models\User;
use App\Services\EventNeedService;
use App\Services\RsvpService;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

function needsUser(array $attributes = []): User
{
    return User::factory()->create($attributes + ['onboarding_completed_at' => now(), 'notification_preference' => 'all']);
}

function needsEvent(User $host, array $attributes = []): Activity
{
    return Activity::factory()->create($attributes + ['host_id' => $host->id, 'status' => 'published', 'is_public' => true, 'start_time' => now()->addDays(3)]);
}

function goingTo(Activity $event, User $user): Rsvp
{
    return Rsvp::factory()->create(['activity_id' => $event->id, 'user_id' => $user->id, 'status' => 'attending']);
}

function needItem(Activity $event, string $name = 'Folding tables', int $quantity = 1): EventNeed
{
    return EventNeed::create(['activity_id' => $event->id, 'name' => $name, 'quantity' => $quantity, 'sort_order' => EventNeed::where('activity_id', $event->id)->count()]);
}

function needsAs(User $user, Activity $event)
{
    return Livewire::actingAs($user)->test(EventNeeds::class, ['activity' => $event]);
}

test('only the host or a group admin can edit the list', function () {
    $host = needsUser();
    $guest = needsUser();
    $event = needsEvent($host);
    goingTo($event, $guest);

    needsAs($host, $event)->call('saveNeed', null, 'Grill', 'Charcoal too', 1);
    expect(EventNeed::where('activity_id', $event->id)->where('name', 'Grill')->exists())->toBeTrue();

    needsAs($guest, $event)->call('saveNeed', null, 'Sneaky', '', 1)->assertForbidden();
    expect(EventNeed::where('name', 'Sneaky')->exists())->toBeFalse();

    $admin = needsUser();
    $group = Group::factory()->create(['privacy' => 'public', 'created_by' => $host->id]);
    $group->memberships()->create(['user_id' => $admin->id, 'role' => 'admin']);
    $groupEvent = needsEvent($host, ['group_id' => $group->id, 'is_public' => false]);
    needsAs($admin, $groupEvent)->call('saveNeed', null, 'Tent', '', 1);
    expect(EventNeed::where('activity_id', $groupEvent->id)->where('name', 'Tent')->exists())->toBeTrue();

    $need = EventNeed::where('name', 'Grill')->first();
    needsAs($host, $event)->call('saveNeed', $need->id, 'Big grill', '', 2)->call('removeNeed', $need->id);
    expect(EventNeed::find($need->id))->toBeNull();
});

test('only people going can claim', function () {
    $host = needsUser();
    $going = needsUser();
    $outsider = needsUser();
    $event = needsEvent($host);
    $need = needItem($event);
    goingTo($event, $going);

    needsAs($outsider, $event)->assertSee('RSVP to claim')->call('claim', $need->id, 1)->assertForbidden();
    expect(EventNeedClaim::count())->toBe(0);

    needsAs($going, $event)->call('claim', $need->id, 1);
    expect(EventNeedClaim::where('user_id', $going->id)->count())->toBe(1);

    $guestView = Livewire::test(EventNeeds::class, ['activity' => $event]);
    $guestView->assertSee('RSVP to claim')->call('claim', $need->id, 1)->assertStatus(401);
});

test('claims cannot exceed the quantity needed', function () {
    $host = needsUser();
    [$a, $b, $c] = [needsUser(), needsUser(), needsUser()];
    $event = needsEvent($host);
    $need = needItem($event, 'Water jugs', 3);
    foreach ([$a, $b, $c] as $u) {
        goingTo($event, $u);
    }
    $service = app(EventNeedService::class);

    $service->claim($need, $a, 2);
    expect(fn () => $service->claim($need, $b, 2))->toThrow(DomainException::class, 'Only 1 still needed.');
    $service->claim($need, $b, 1);
    expect(fn () => $service->claim($need, $c, 1))->toThrow(DomainException::class, 'Someone just took the last one.');
    expect(fn () => $service->claim($need, $a, 1))->toThrow(DomainException::class);
    expect((int) EventNeedClaim::where('event_need_id', $need->id)->sum('quantity'))->toBe(3);

    needsAs($c, $event)->call('claim', $need->id, 1)->assertDispatched('show-toast', message: 'Someone just took the last one.', type: 'error');
    expect(fn () => $service->updateNeed($need, 'Water jugs', null, 2))->toThrow(DomainException::class);
});

test('release reopens the item and statuses follow claims', function () {
    $host = needsUser();
    [$a, $b] = [needsUser(), needsUser()];
    $event = needsEvent($host);
    $need = needItem($event, 'Speakers', 2);
    goingTo($event, $a);
    goingTo($event, $b);
    $service = app(EventNeedService::class);

    expect($need->fresh()->load('claims')->status())->toBe('open');
    $service->claim($need, $a, 1);
    expect($need->fresh()->load('claims')->status())->toBe('partial');
    $service->claim($need, $b, 1);
    expect($need->fresh()->load('claims')->status())->toBe('covered');

    needsAs($a, $event)->call('release', $need->id);
    expect($need->fresh()->load('claims')->status())->toBe('partial');
    expect(EventNeedClaim::where('user_id', $a->id)->exists())->toBeFalse();
    needsAs($a, $event)->call('release', $need->id)->assertStatus(404);
});

test('a manager can assign someone going and mark things brought', function () {
    $host = needsUser();
    [$going, $notGoing] = [needsUser(), needsUser()];
    $event = needsEvent($host);
    $need = needItem($event, 'Grill');
    goingTo($event, $going);

    needsAs($host, $event)->call('assign', $need->id, $notGoing->id)->assertDispatched('show-toast', message: 'Only people who are going can be assigned an item.', type: 'error');
    needsAs($host, $event)->call('assign', $need->id, $going->id);
    expect(EventNeedClaim::where('user_id', $going->id)->exists())->toBeTrue();

    needsAs($host, $event)->call('toggleBrought', $need->id);
    expect($need->fresh()->load('claims')->status())->toBe('brought');
    needsAs($host, $event)->call('toggleBrought', $need->id);
    expect($need->fresh()->load('claims')->status())->toBe('covered');
    needsAs($going, $event)->call('toggleBrought', $need->id)->assertForbidden();
});

test('copying copies items without claims, only from events the user manages', function () {
    $host = needsUser();
    $other = needsUser();
    $past = needsEvent($host, ['title' => 'Last Tuesday']);
    $theirs = needsEvent($other, ['title' => 'Not mine']);
    $item = needItem($past, 'Tent', 2);
    needItem($theirs, 'Secret list');
    goingTo($past, $other);
    app(EventNeedService::class)->claim($item, $other, 1);
    $new = needsEvent($host, ['title' => 'This Tuesday']);

    needsAs($host, $new)->assertSee('Last Tuesday')->assertDontSee('Not mine')->call('copyFrom', $past->id);
    $copied = EventNeed::where('activity_id', $new->id)->get();
    expect($copied)->toHaveCount(1)->and($copied->first()->name)->toBe('Tent')->and($copied->first()->quantity)->toBe(2);
    expect(EventNeedClaim::whereIn('event_need_id', $copied->pluck('id'))->count())->toBe(0);

    needsAs($host, $new)->call('copyFrom', $theirs->id)->assertForbidden();
    expect(EventNeed::where('name', 'Secret list')->where('activity_id', $new->id)->exists())->toBeFalse();
});

test('the day-before check notifies each claimer once', function () {
    $host = needsUser();
    [$a, $quiet, $notGoing] = [needsUser(), needsUser(['notification_preference' => 'none']), needsUser()];
    $soon = needsEvent($host, ['start_time' => now()->addHours(20), 'title' => 'Tomorrow Jam']);
    $later = needsEvent($host, ['start_time' => now()->addDays(3)]);
    $one = needItem($soon, 'Tables');
    $two = needItem($soon, 'Chairs');
    $far = needItem($later, 'Lights');
    foreach ([$a, $quiet] as $u) {
        goingTo($soon, $u);
    }
    goingTo($later, $a);
    $service = app(EventNeedService::class);
    foreach ([[$one, $a], [$two, $a], [$one, $quiet]] as [$n, $u]) {
        $service->claim($n, $u, 1)->forceFill(['created_at' => now()->subHours(5)])->save();
    }
    $farClaim = $service->claim($far, $a, 1);
    $farClaim->forceFill(['created_at' => now()->subHours(5)])->save();
    $fresh = $service->claim(needItem($soon, 'Snacks'), $a, 1);

    SendNeedsCheckReminders::dispatchSync();
    SendNeedsCheckReminders::dispatchSync();

    $notes = Notification::where('user_id', $a->id)->where('type', 'event_need_check')->get();
    expect($notes)->toHaveCount(1)->and($notes->first()->title)->toBe('Still bringing Tables and 1 more?');
    expect($notes->first()->data['url'])->toContain('#needs');
    expect(Notification::where('user_id', $quiet->id)->count())->toBe(0);
    expect($farClaim->fresh()->reminded_at)->toBeNull()->and($fresh->fresh()->reminded_at)->toBeNull();
    expect(EventNeedClaim::where('user_id', $quiet->id)->first()->reminded_at)->not->toBeNull();

    needsAs($a, $soon)->call('confirm', $one->id);
    expect(EventNeedClaim::where('user_id', $a->id)->where('event_need_id', $one->id)->first()->confirmed_at)->not->toBeNull();
});

test('needs limits hold', function () {
    $host = needsUser();
    $event = needsEvent($host);
    $service = app(EventNeedService::class);

    expect(fn () => $service->addNeed($event, $host, '', null, 1))->toThrow(DomainException::class);
    expect(fn () => $service->addNeed($event, $host, str_repeat('x', 81), null, 1))->toThrow(DomainException::class);
    expect(fn () => $service->addNeed($event, $host, 'Ok', str_repeat('n', 201), 1))->toThrow(DomainException::class);
    expect(fn () => $service->addNeed($event, $host, 'Ok', null, 0))->toThrow(DomainException::class);
    expect(fn () => $service->addNeed($event, $host, 'Ok', null, 100))->toThrow(DomainException::class);

    foreach (range(1, EventNeed::MAX_PER_EVENT) as $i) {
        $service->addNeed($event, $host, "Item {$i}", null, 1);
    }
    expect(fn () => $service->addNeed($event, $host, 'One too many', null, 1))->toThrow(DomainException::class);

    $past = needsEvent($host, ['start_time' => now()->subDay()]);
    expect(fn () => $service->addNeed($past, $host, 'Late', null, 1))->toThrow(DomainException::class);
});

test('cancelling an RSVP releases the claims', function () {
    $host = needsUser();
    $user = needsUser();
    $event = needsEvent($host);
    $need = needItem($event);
    $rsvp = goingTo($event, $user);
    app(EventNeedService::class)->claim($need, $user, 1);

    app(RsvpService::class)->cancelRsvp($rsvp);

    expect(EventNeedClaim::where('user_id', $user->id)->count())->toBe(0);
    expect($need->fresh()->load('claims')->status())->toBe('open');
});

test('needs follow event visibility', function () {
    $host = needsUser();
    $outsider = needsUser();
    $group = Group::factory()->create(['privacy' => 'private', 'created_by' => $host->id]);
    $event = needsEvent($host, ['group_id' => $group->id, 'is_public' => false]);
    needItem($event, 'Private grill');

    needsAs($outsider, $event)->assertForbidden();
    $this->actingAs($outsider)->get(route('events.show', $event))->assertForbidden();
});

test('the needs section uses a constant number of queries', function () {
    $count = function (int $items) {
        $host = needsUser();
        $event = needsEvent($host);
        foreach (range(1, $items) as $i) {
            $need = needItem($event, "Item {$i}", 2);
            $u = needsUser();
            goingTo($event, $u);
            app(EventNeedService::class)->claim($need, $u, 1);
        }
        $viewer = needsUser();
        DB::flushQueryLog();
        DB::enableQueryLog();
        needsAs($viewer, $event);
        $n = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $n;
    };

    expect($count(12))->toBe($count(3));
});

test('the event page shows the needs card', function () {
    $host = needsUser();
    $event = needsEvent($host);
    needItem($event, 'Pop-up tent');

    $this->actingAs(needsUser())->get(route('events.show', $event))->assertOk()->assertSee('What we need')->assertSee('Pop-up tent');
});
