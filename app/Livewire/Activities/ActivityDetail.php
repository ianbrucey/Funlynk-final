<?php

namespace App\Livewire\Activities;

use App\Models\Activity;
use App\Models\ActivityRefundWindow;
use App\Models\Rsvp;
use App\Models\RsvpChangeResponse;
use App\Services\ActivityService;
use App\Services\ContextPreservationService;
use App\Services\GuestEngagementService;
use App\Services\RefundWindowService;
use App\Services\SocialShareService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Cookie;
use Livewire\Component;

class ActivityDetail extends Component
{
    use AuthorizesRequests;

    public Activity $activity;

    public ?Rsvp $userRsvp = null;

    public $isHost = false;

    public $spotsRemaining = null;

    public $isGroupEvent = false;

    public $group = null;

    public bool $isFollowingHost = false;

    // Refund window state
    public ?ActivityRefundWindow $activeRefundWindow = null;

    public ?RsvpChangeResponse $pendingChangeResponse = null;

    public bool $showRefundModal = false;

    // Guest engagement state
    public bool $showInterestModal = false;

    public string $email = '';

    public string $source = '';

    public ?string $referralCode = null;

    public array $utmParams = [];

    protected ActivityService $activityService;

    protected RefundWindowService $refundWindowService;

    protected $rules = [
        'email' => 'required|email',
    ];

    public function boot(ActivityService $activityService, RefundWindowService $refundWindowService)
    {
        $this->activityService = $activityService;
        $this->refundWindowService = $refundWindowService;
    }

    public function mount(
        Activity $activity,
        ContextPreservationService $contextService,
        SocialShareService $shareService
    ) {
        $this->activity = $activity->load(['host', 'tags', 'group']);

        // Check authorization - only restrict non-public activities for authenticated users
        if (! $this->activity->is_public && auth()->check()) {
            $this->authorize('view', $this->activity);
        }

        $this->isHost = auth()->check() && auth()->id() === $this->activity->host_id;
        $this->spotsRemaining = $this->activityService->getAvailableSpots($this->activity);

        // Check if this is a group event
        $this->isGroupEvent = $this->activity->group_id !== null;
        $this->group = $this->activity->group;

        // Capture referral code from URL
        if (request()->has('ref')) {
            $this->referralCode = request('ref');
            $contextService->captureReferralCode($this->referralCode);
            $shareService->trackClick($this->referralCode);
        }

        // Capture UTM parameters
        if (request()->has('utm_source')) {
            $this->utmParams = [
                'source' => request('utm_source'),
                'medium' => request('utm_medium'),
                'campaign' => request('utm_campaign'),
            ];
            $contextService->captureUtmParams($this->utmParams);
        }

        // Determine source
        $this->source = request('utm_source', 'direct');

        // Load user's RSVP if they have one
        if (auth()->check()) {
            $this->userRsvp = Rsvp::where('activity_id', $this->activity->id)
                ->where('user_id', auth()->id())
                ->first();

            // Check for active refund window and pending response
            $this->loadRefundWindowState();
        }

        // Check if following host
        if (auth()->check() && auth()->id() !== $this->activity->host_id) {
            $this->isFollowingHost = \App\Models\Follow::where('follower_id', auth()->id())
                ->where('following_id', $this->activity->host_id)
                ->exists();
        }
    }

    protected function loadRefundWindowState(): void
    {
        if (! $this->userRsvp || ! $this->userRsvp->is_paid) {
            return;
        }

        $this->activeRefundWindow = $this->activity->activeRefundWindow;

        if ($this->activeRefundWindow) {
            $this->pendingChangeResponse = RsvpChangeResponse::where('rsvp_id', $this->userRsvp->id)
                ->where('refund_window_id', $this->activeRefundWindow->id)
                ->first();
        }
    }

    /**
     * Guest engagement: Express interest via email
     */
    public function expressInterest(GuestEngagementService $guestService)
    {
        $this->validate();

        $guestService->recordInterest($this->activity, $this->email, [
            'source' => $this->source,
            'utm_campaign' => $this->utmParams['campaign'] ?? null,
            'utm_source' => $this->utmParams['source'] ?? null,
            'utm_medium' => $this->utmParams['medium'] ?? null,
        ]);

        $this->showInterestModal = false;
        $this->email = '';

        session()->flash('success', 'Thanks! We\'ll send you event updates and reminders.');
    }

    /**
     * Guest engagement: Bookmark event using cookie token
     */
    public function bookmark(GuestEngagementService $guestService)
    {
        $guestToken = Cookie::get('guest_token');

        $result = $guestService->createBookmark($this->activity, $guestToken, $this->source);

        if (! $guestToken) {
            Cookie::queue('guest_token', $result['token'], 60 * 24 * 365); // 1 year
        }

        session()->flash('success', 'Event bookmarked! Create an account to access your saved events.');
    }

    /**
     * Guest engagement: RSVP action - captures intent and redirects to login
     */
    public function guestRsvp(ContextPreservationService $contextService)
    {
        $contextService->captureIntent($this->activity, 'rsvp', [
            'source' => $this->source,
            'referral_code' => $this->referralCode,
            'utm_params' => $this->utmParams,
        ]);

        return redirect()->route('login');
    }

    /**
     * Share event on social platforms
     */
    public function share(string $platform, SocialShareService $shareService)
    {
        $user = auth()->check() ? auth()->user() : null;
        $shareUrl = $shareService->generateShareUrl($this->activity, $platform, $user);

        $this->dispatch('share-url-generated', [
            'platform' => $platform,
            'url' => $shareUrl,
        ]);
    }

    public function deleteActivity()
    {
        $this->authorize('delete', $this->activity);

        if ($this->activityService->canDelete($this->activity, auth()->user())) {
            $this->activity->delete();
            session()->flash('success', 'Activity deleted successfully.');

            return redirect()->route('events.dashboard');
        } else {
            session()->flash('error', 'Cannot delete activity. It may have attendees or be completed.');
        }
    }

    public function acceptChanges()
    {
        if (! $this->userRsvp || ! $this->activeRefundWindow || ! $this->pendingChangeResponse) {
            session()->flash('error', 'No pending changes to accept.');

            return;
        }

        try {
            $this->refundWindowService->processResponse(
                $this->userRsvp,
                $this->activeRefundWindow,
                RsvpChangeResponse::RESPONSE_ACCEPTED
            );

            session()->flash('success', 'You have accepted the changes. Your RSVP is confirmed.');
            $this->loadRefundWindowState();
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function requestRefund()
    {
        if (! $this->userRsvp || ! $this->activeRefundWindow || ! $this->pendingChangeResponse) {
            session()->flash('error', 'No pending changes to respond to.');

            return;
        }

        try {
            $this->refundWindowService->processResponse(
                $this->userRsvp,
                $this->activeRefundWindow,
                RsvpChangeResponse::RESPONSE_REFUNDED
            );

            session()->flash('success', 'Your refund has been processed. You will receive your money back within 5-10 business days.');
            $this->showRefundModal = false;
            $this->loadRefundWindowState();
            $this->userRsvp = $this->userRsvp->fresh();
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function openRefundModal()
    {
        $this->showRefundModal = true;
    }

    public function closeRefundModal()
    {
        $this->showRefundModal = false;
    }

    public function copyPublicLink()
    {
        $publicUrl = route('events.show', $this->activity);
        $this->dispatch('copy-to-clipboard', url: $publicUrl);
        session()->flash('success', 'Public link copied to clipboard!');
    }

    public function render()
    {
        $data = [
            'interestedCount' => $this->activity->interested_count ?? 0,
            'shareCount' => $this->activity->share_count ?? 0,
            'rsvpCount' => $this->activity->rsvps()->where('status', 'attending')->count(),
        ];

        return view('livewire.activities.activity-detail', $data)
            ->layout('layouts.app');
    }
}
