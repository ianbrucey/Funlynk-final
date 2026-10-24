<?php

use App\Jobs\NotifyGroupAskEvent;
use App\Livewire\Groups\GroupFunds;
use App\Models\Group;
use App\Models\GroupAsk;
use App\Models\GroupAskEntry;
use App\Models\Notification;
use App\Models\User;
use App\Services\GroupAskService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

function askPerson(Group $group, string $role = 'member', array $attrs = []): User
{
    $user = User::factory()->create($attrs + ['onboarding_completed_at' => now(), 'notification_preference' => 'all']);
    $group->members()->attach($user->id, ['role' => $role, 'joined_at' => now()]);

    return $user;
}

function askGroup(string $privacy = 'public'): array
{
    $group = Group::factory()->create(['privacy' => $privacy]);
    $admin = askPerson($group, 'admin');
    $member = askPerson($group);

    return [$group, $admin, $member];
}

function askData(array $over = []): array
{
    return $over + ['title' => 'Floor cleaner', 'why' => 'The rink is sticky', 'target_cents' => 6000, 'payment_url' => 'https://venmo.com/u/club'];
}

function openAsk(Group $group, User $author, User $admin): GroupAsk
{
    $svc = app(GroupAskService::class);

    return $svc->approve($admin, $svc->propose($author, $group, askData()));
}

function fakeImage(): UploadedFile
{
    return UploadedFile::fake()->image('p.jpg', 300, 300);
}

test('member proposal is hidden from other members until approved', function () {
    [$group, $admin, $member] = askGroup();
    $other = askPerson($group);
    $ask = app(GroupAskService::class)->propose($member, $group, askData());

    Livewire::actingAs($other)->test(GroupFunds::class, ['group' => $group])->assertDontSee('Floor cleaner');
    Livewire::actingAs($member)->test(GroupFunds::class, ['group' => $group])->assertSee('Floor cleaner');
    Livewire::actingAs($admin)->test(GroupFunds::class, ['group' => $group])->assertSee('Floor cleaner');
    Livewire::actingAs($other)->test(GroupFunds::class, ['group' => $group])->call('openAsk', $ask->id)->assertNotFound();

    app(GroupAskService::class)->approve($admin, $ask);
    Livewire::actingAs($other)->test(GroupFunds::class, ['group' => $group])->assertSee('Floor cleaner');
});

test('only admins approve or decline and a decline needs a reason', function () {
    [$group, $admin, $member] = askGroup();
    $svc = app(GroupAskService::class);
    $ask = $svc->propose($member, $group, askData());

    expect(fn () => $svc->approve($member, $ask))->toThrow(AuthorizationException::class);
    expect(fn () => $svc->decline($admin, $ask, '  '))->toThrow(ValidationException::class);
    $svc->decline($admin, $ask, 'Too vague');
    expect($ask->fresh()->status)->toBe('declined')->and($ask->fresh()->decline_reason)->toBe('Too vague');
});

test('an admin cannot approve their own ask when another admin exists', function () {
    [$group, $admin] = askGroup();
    $svc = app(GroupAskService::class);
    $second = askPerson($group, 'admin');
    $mine = $svc->propose($admin, $group, askData());
    expect(fn () => $svc->approve($admin, $mine))->toThrow(AuthorizationException::class);

    expect($svc->approve($second, $mine)->status)->toBe('open');

    [$solo, $soloAdmin] = askGroup();
    expect($svc->approve($soloAdmin, $svc->propose($soloAdmin, $solo, askData()))->status)->toBe('open');
});

test('only admins record entries', function () {
    Storage::fake('s3');
    [$group, $admin, $member] = askGroup();
    $ask = openAsk($group, $member, $admin);
    $svc = app(GroupAskService::class);

    expect(fn () => $svc->recordReceived($member, $ask, 5000))->toThrow(AuthorizationException::class);
    expect(fn () => $svc->recordPurchase($member, $ask, 1000, 'x', fakeImage(), fakeImage()))->toThrow(AuthorizationException::class);
    expect($svc->recordReceived($admin, $ask, 5000, 'Dana')->amount_cents)->toBe(5000);
});

test('entries are append-only with void', function () {
    [$group, $admin, $member] = askGroup();
    $ask = openAsk($group, $member, $admin);
    $svc = app(GroupAskService::class);
    $entry = $svc->recordReceived($admin, $ask, 5000);

    expect(GroupAskEntry::find($entry->id)->update(['amount_cents' => 1]))->toBeFalse();
    expect($entry->fresh()->amount_cents)->toBe(5000);
    expect(GroupAskEntry::find($entry->id)->delete())->toBeFalse();
    expect(GroupAskEntry::count())->toBe(1);

    expect(fn () => $svc->voidEntry($admin, $entry, ''))->toThrow(ValidationException::class);
    expect(fn () => $svc->voidEntry($member, $entry, 'typo'))->toThrow(AuthorizationException::class);
    $svc->voidEntry($admin, $entry, 'Typo in amount');
    expect($entry->fresh()->isVoided())->toBeTrue()->and(GroupAskEntry::count())->toBe(1);
    expect(fn () => $svc->voidEntry($admin, $entry->fresh(), 'again'))->toThrow(ValidationException::class);
});

test('totals derive from non voided entries', function () {
    Storage::fake('s3');
    [$group, $admin, $member] = askGroup();
    $ask = openAsk($group, $member, $admin);
    $svc = app(GroupAskService::class);
    $svc->recordReceived($admin, $ask, 5000);
    $gone = $svc->recordReceived($admin, $ask, 2500);
    $svc->recordPurchase($admin, $ask, 3000, 'Cleaner', fakeImage(), fakeImage());
    $svc->voidEntry($admin, $gone, 'Wrong ask');

    Livewire::actingAs($member)->test(GroupFunds::class, ['group' => $group])
        ->call('openAsk', $ask->id)->assertSee('$50')->assertSee('$30')->assertSee('$20')->assertSee('Voided: Wrong ask');
});

test('a purchase requires amount description and both photos', function () {
    Storage::fake('s3');
    [$group, $admin, $member] = askGroup();
    $ask = openAsk($group, $member, $admin);
    $svc = app(GroupAskService::class);

    expect(fn () => $svc->recordPurchase($admin, $ask, 1000, 'Cleaner', fakeImage(), null))->toThrow(ValidationException::class);
    expect(fn () => $svc->recordPurchase($admin, $ask, 1000, ' ', fakeImage(), fakeImage()))->toThrow(ValidationException::class);
    expect(fn () => $svc->recordPurchase($admin, $ask, 0, 'Cleaner', fakeImage(), fakeImage()))->toThrow(ValidationException::class);
    $entry = $svc->recordPurchase($admin, $ask, 1000, 'Cleaner', fakeImage(), fakeImage());
    Storage::disk('s3')->assertExists([$entry->item_photo_path, $entry->receipt_photo_path]);
});

test('close rules', function () {
    Storage::fake('s3');
    [$group, $admin, $member] = askGroup();
    $svc = app(GroupAskService::class);
    $ask = openAsk($group, $member, $admin);

    expect(fn () => $svc->close($admin, $ask, 'completed', 'Rest to next ask'))->toThrow(ValidationException::class);
    expect(fn () => $svc->close($admin, $ask, 'cancelled', ''))->toThrow(ValidationException::class);
    expect(fn () => $svc->close($member, $ask, 'cancelled', 'x'))->toThrow(AuthorizationException::class);

    $purchase = $svc->recordPurchase($admin, $ask, 1000, 'Cleaner', fakeImage(), fakeImage());
    $svc->voidEntry($admin, $purchase, 'Mistake');
    expect(fn () => $svc->close($admin, $ask, 'completed', 'note'))->toThrow(ValidationException::class);

    $svc->recordPurchase($admin, $ask, 1000, 'Cleaner', fakeImage(), fakeImage());
    expect($svc->close($admin, $ask, 'completed', 'Leftover to next ask')->status)->toBe('completed');
    expect(fn () => $svc->recordReceived($admin, $ask, 100))->toThrow(ValidationException::class);

    $other = openAsk($group, $member, $admin);
    expect($svc->close($admin, $other, 'cancelled', 'Refunded everyone')->status)->toBe('cancelled');
});

test('payment link rules', function (string $url, bool $ok) {
    [$group, , $member] = askGroup();
    $call = fn () => app(GroupAskService::class)->propose($member, $group, askData(['payment_url' => $url]));
    $ok ? expect($call()->payment_url)->toBe($url) : expect($call)->toThrow(ValidationException::class);
})->with([
    'venmo' => ['https://venmo.com/u/club', true],
    'subdomain' => ['https://www.paypal.com/pool/1', true],
    'http' => ['http://venmo.com/u/club', false],
    'other host' => ['https://evil.example/pay', false],
    'lookalike' => ['https://venmo.com.evil.example/x', false],
    'credentials' => ['https://venmo.com@evil.example/x', false],
    'javascript' => ['javascript:alert(1)', false],
]);

test('payment link shows only while the ask is open', function () {
    [$group, $admin, $member] = askGroup();
    $ask = app(GroupAskService::class)->propose($member, $group, askData());
    Livewire::actingAs($admin)->test(GroupFunds::class, ['group' => $group])->call('openAsk', $ask->id)->assertDontSee('venmo.com');
    app(GroupAskService::class)->approve($admin, $ask);
    Livewire::actingAs($member)->test(GroupFunds::class, ['group' => $group])->call('openAsk', $ask->id)->assertSee('venmo.com');
});

test('asks follow group visibility', function () {
    [$group, $admin, $member] = askGroup('private');
    openAsk($group, $member, $admin);
    $outsider = User::factory()->create(['onboarding_completed_at' => now()]);

    Livewire::actingAs($outsider)->test(GroupFunds::class, ['group' => $group])->assertForbidden();
    Livewire::actingAs($member)->test(GroupFunds::class, ['group' => $group])->assertSee('Floor cleaner');

    [$public, $padmin, $pmember] = askGroup('public');
    openAsk($public, $pmember, $padmin);
    Livewire::actingAs($outsider)->test(GroupFunds::class, ['group' => $public])->assertSee('Floor cleaner')->assertDontSee('Propose an ask');
    expect(fn () => app(GroupAskService::class)->propose($outsider, $public, askData()))->toThrow(AuthorizationException::class);
});

test('chipped in is self reported once and never counted', function () {
    [$group, $admin, $member] = askGroup();
    $ask = openAsk($group, $member, $admin);
    $svc = app(GroupAskService::class);
    $outsider = User::factory()->create();

    $svc->chipIn($member, $ask);
    $svc->chipIn($member, $ask);
    expect($ask->chips()->count())->toBe(1)->and($ask->entries()->count())->toBe(0);
    expect(fn () => $svc->chipIn($outsider, $ask))->toThrow(AuthorizationException::class);
});

test('ask notifications', function () {
    [$group, $admin, $member] = askGroup();
    $quiet = askPerson($group, 'member', ['notification_preference' => 'none']);
    $svc = app(GroupAskService::class);
    $ask = $svc->propose($member, $group, askData());

    NotifyGroupAskEvent::dispatchSync($ask->id, 'proposed', $member->id);
    expect(Notification::where('type', 'group_ask_proposed')->pluck('user_id')->all())->toBe([$admin->id]);

    $svc->approve($admin, $ask);
    NotifyGroupAskEvent::dispatchSync($ask->id, 'opened', $admin->id);
    $ids = Notification::where('type', 'group_ask_opened')->pluck('user_id')->all();
    expect($ids)->toBe([$member->id])->not->toContain($admin->id)->not->toContain($quiet->id);
    expect(Notification::where('type', 'group_ask_opened')->first()->data['url'])->toContain('tab=funds');

    Storage::fake('s3');
    $entry = $svc->recordPurchase($admin, $ask, 1000, 'Cleaner', fakeImage(), fakeImage());
    NotifyGroupAskEvent::dispatchSync($ask->id, 'purchase', $admin->id, $entry->id);
    expect(Notification::where('type', 'group_ask_purchase')->where('user_id', $member->id)->exists())->toBeTrue();

    $declined = $svc->propose($member, $group, askData());
    $svc->decline($admin, $declined, 'Not now');
    NotifyGroupAskEvent::dispatchSync($declined->id, 'declined', $admin->id);
    expect(Notification::where('type', 'group_ask_declined')->where('user_id', $member->id)->count())->toBe(1);
});

test('the component queues notifications', function () {
    Queue::fake();
    [$group, $admin, $member] = askGroup();
    Livewire::actingAs($member)->test(GroupFunds::class, ['group' => $group])
        ->call('openSheet', 'propose')
        ->set('title', 'Cleaner')->set('why', 'Sticky')->set('target', '60')->set('paymentUrl', 'https://venmo.com/u/club')
        ->call('propose')->assertHasNoErrors();
    Queue::assertPushed(NotifyGroupAskEvent::class, fn ($j) => $j->event === 'proposed');
    expect(GroupAsk::first()->target_cents)->toBe(6000);
});

test('ask limits hold', function () {
    [$group, $admin, $member] = askGroup();
    $svc = app(GroupAskService::class);

    foreach ([['target_cents', 99], ['target_cents', 100_000_001], ['title', str_repeat('a', 81)], ['why', '']] as [$field, $value]) {
        expect(fn () => $svc->propose($member, $group, askData([$field => $value])))->toThrow(ValidationException::class);
    }
    expect(fn () => $svc->propose($member, $group, askData(['deadline' => now()->subDay()->toDateString()])))->toThrow(ValidationException::class);

    for ($i = 0; $i < GroupAsk::MAX_OPEN_PER_GROUP; $i++) {
        GroupAsk::create(askData() + ['group_id' => $group->id, 'created_by' => $member->id, 'status' => 'open']);
    }
    $extra = $svc->propose($member, $group, askData());
    expect(fn () => $svc->approve($admin, $extra))->toThrow(ValidationException::class);
});

test('the funds tab uses a constant number of queries', function () {
    [$group, $admin, $member] = askGroup();
    $count = function () use ($group, $member) {
        DB::flushQueryLog();
        DB::enableQueryLog();
        Livewire::actingAs($member)->test(GroupFunds::class, ['group' => $group]);
        $n = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $n;
    };

    openAsk($group, $member, $admin);
    $few = $count();
    foreach (range(1, 8) as $i) {
        GroupAsk::create(askData() + ['group_id' => $group->id, 'created_by' => $member->id, 'status' => 'open']);
    }

    expect($count())->toBe($few);
});
