<?php

use App\Models\Activity;
use App\Models\Rsvp;
use App\Models\User;
use App\Services\CapacityService;
use App\Services\RsvpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('Capacity Service', function () {
    test('it can check if user can RSVP', function () {
        $user = User::factory()->create();
        $activity = Activity::factory()->create(['max_attendees' => 10, 'current_attendees' => 5]);
        
        $capacityService = new CapacityService();
        $result = $capacityService->canRsvp($activity, $user);
        
        expect($result['allowed'])->toBeTrue()
            ->and($result['status'])->toBe('attending');
    });

    test('it adds user to waitlist when activity is full', function () {
        $user = User::factory()->create();
        $activity = Activity::factory()->create(['max_attendees' => 10, 'current_attendees' => 10]);
        
        $capacityService = new CapacityService();
        $result = $capacityService->canRsvp($activity, $user);
        
        expect($result['allowed'])->toBeTrue()
            ->and($result['status'])->toBe('waitlist');
    });

    test('it prevents duplicate RSVPs', function () {
        $user = User::factory()->create();
        $activity = Activity::factory()->create();
        
        Rsvp::factory()->create([
            'user_id' => $user->id,
            'activity_id' => $activity->id,
            'status' => 'attending',
        ]);
        
        $capacityService = new CapacityService();
        $result = $capacityService->canRsvp($activity, $user);
        
        expect($result['allowed'])->toBeFalse();
    });

    test('it can reserve a spot for user', function () {
        $user = User::factory()->create();
        $activity = Activity::factory()->create(['max_attendees' => 10, 'current_attendees' => 5]);
        
        $capacityService = new CapacityService();
        $rsvp = $capacityService->reserve($activity, $user);
        
        expect($rsvp)->toBeInstanceOf(Rsvp::class)
            ->and($rsvp->status)->toBe('attending')
            ->and($activity->fresh()->current_attendees)->toBe(6);
    });

    test('it promotes from waitlist when spot opens', function () {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $activity = Activity::factory()->create(['max_attendees' => 1, 'current_attendees' => 1]);
        
        $rsvp1 = Rsvp::factory()->create([
            'user_id' => $user1->id,
            'activity_id' => $activity->id,
            'status' => 'attending',
        ]);
        
        $rsvp2 = Rsvp::factory()->create([
            'user_id' => $user2->id,
            'activity_id' => $activity->id,
            'status' => 'waitlist',
        ]);
        
        $capacityService = new CapacityService();
        $capacityService->cancelRsvp($rsvp1);
        
        expect($rsvp2->fresh()->status)->toBe('attending')
            ->and($activity->fresh()->current_attendees)->toBe(1);
    });
});

describe('RSVP Service', function () {
    test('it can create an RSVP', function () {
        $user = User::factory()->create();
        $activity = Activity::factory()->create(['status' => 'published']);
        
        $rsvpService = app(RsvpService::class);
        $rsvp = $rsvpService->createRsvp($activity, $user);
        
        expect($rsvp)->toBeInstanceOf(Rsvp::class)
            ->and($rsvp->user_id)->toBe($user->id)
            ->and($rsvp->activity_id)->toBe($activity->id);
    });

    test('it prevents RSVP to non-published activities', function () {
        $user = User::factory()->create();
        $activity = Activity::factory()->create(['status' => 'draft']);
        
        $rsvpService = app(RsvpService::class);
        
        expect(fn() => $rsvpService->createRsvp($activity, $user))
            ->toThrow(\Exception::class);
    });

    test('it can update RSVP status', function () {
        $user = User::factory()->create();
        $activity = Activity::factory()->create(['max_attendees' => 10, 'current_attendees' => 1]);
        $rsvp = Rsvp::factory()->create([
            'user_id' => $user->id,
            'activity_id' => $activity->id,
            'status' => 'attending',
        ]);
        
        $rsvpService = app(RsvpService::class);
        $updatedRsvp = $rsvpService->updateRsvp($rsvp, ['status' => 'maybe']);
        
        expect($updatedRsvp->status)->toBe('maybe')
            ->and($activity->fresh()->current_attendees)->toBe(0);
    });

    test('it can mark user as attended', function () {
        $rsvp = Rsvp::factory()->create(['attended' => false]);
        
        $rsvpService = app(RsvpService::class);
        $updatedRsvp = $rsvpService->markAttended($rsvp);
        
        expect($updatedRsvp->attended)->toBeTrue();
    });

    test('it can get attendance statistics', function () {
        $activity = Activity::factory()->create();
        
        Rsvp::factory()->create(['activity_id' => $activity->id, 'status' => 'attending', 'attended' => true]);
        Rsvp::factory()->create(['activity_id' => $activity->id, 'status' => 'attending', 'attended' => false]);
        Rsvp::factory()->create(['activity_id' => $activity->id, 'status' => 'maybe']);
        Rsvp::factory()->create(['activity_id' => $activity->id, 'status' => 'waitlist']);
        
        $rsvpService = app(RsvpService::class);
        $stats = $rsvpService->getAttendanceStats($activity);
        
        expect($stats['total_rsvps'])->toBe(4)
            ->and($stats['attending'])->toBe(2)
            ->and($stats['maybe'])->toBe(1)
            ->and($stats['waitlist'])->toBe(1)
            ->and($stats['attended'])->toBe(1)
            ->and($stats['attendance_rate'])->toBe(50.0);
    });
});

describe('RSVP Policy', function () {
    test('it allows user to view their own RSVP', function () {
        $user = User::factory()->create();
        $rsvp = Rsvp::factory()->create(['user_id' => $user->id]);
        
        expect($user->can('view', $rsvp))->toBeTrue();
    });

    test('it allows host to view RSVPs for their activity', function () {
        $host = User::factory()->create();
        $activity = Activity::factory()->create(['host_id' => $host->id]);
        $rsvp = Rsvp::factory()->create(['activity_id' => $activity->id]);
        
        expect($host->can('view', $rsvp))->toBeTrue();
    });

    test('it prevents others from viewing RSVP', function () {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $rsvp = Rsvp::factory()->create(['user_id' => $otherUser->id]);
        
        expect($user->can('view', $rsvp))->toBeFalse();
    });

    test('it allows user to update their own RSVP', function () {
        $user = User::factory()->create();
        $rsvp = Rsvp::factory()->create(['user_id' => $user->id]);
        
        expect($user->can('update', $rsvp))->toBeTrue();
    });

    test('it allows only host to mark attendance', function () {
        $host = User::factory()->create();
        $user = User::factory()->create();
        $activity = Activity::factory()->create(['host_id' => $host->id]);
        $rsvp = Rsvp::factory()->create(['activity_id' => $activity->id, 'user_id' => $user->id]);
        
        expect($host->can('markAttended', $rsvp))->toBeTrue()
            ->and($user->can('markAttended', $rsvp))->toBeFalse();
    });
});

describe('RSVP Button Component', function () {
    test('it can render RSVP button', function () {
        $activity = Activity::factory()->create(['status' => 'published']);
        
        Livewire::test(\App\Livewire\Activities\RsvpButton::class, ['activity' => $activity])
            ->assertSee('Join Activity');
    });

    test('it can create RSVP when clicked', function () {
        $user = User::factory()->create();
        $activity = Activity::factory()->create(['status' => 'published', 'current_attendees' => 0]);
        
        $this->actingAs($user);
        
        Livewire::test(\App\Livewire\Activities\RsvpButton::class, ['activity' => $activity])
            ->call('toggleRsvp')
            ->assertDispatched('rsvp-updated');
        
        expect(Rsvp::where('user_id', $user->id)->where('activity_id', $activity->id)->exists())->toBeTrue()
            ->and($activity->fresh()->current_attendees)->toBe(1);
    });

    test('it shows waitlist button when activity is full', function () {
        $activity = Activity::factory()->create([
            'status' => 'published',
            'max_attendees' => 1,
            'current_attendees' => 1,
        ]);
        
        Livewire::test(\App\Livewire\Activities\RsvpButton::class, ['activity' => $activity])
            ->assertSee('Join Waitlist');
    });
});
