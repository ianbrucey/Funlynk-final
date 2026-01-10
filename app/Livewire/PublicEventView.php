<?php

namespace App\Livewire;

use App\Models\Activity;
use App\Services\ContextPreservationService;
use App\Services\GuestEngagementService;
use App\Services\SocialShareService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Livewire\Component;

class PublicEventView extends Component
{
    public Activity $activity;

    public bool $showInterestModal = false;

    public string $email = '';

    public string $source = '';

    public ?string $referralCode = null;

    public array $utmParams = [];

    protected $rules = [
        'email' => 'required|email',
    ];

    public function mount(
        Activity $activity,
        ContextPreservationService $contextService,
        SocialShareService $shareService
    ) {
        $this->activity = $activity;

        // Capture referral code from URL
        if (request()->has('ref')) {
            $this->referralCode = request('ref');
            $contextService->captureReferralCode($this->referralCode);

            // Track click on shared link
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
    }

    public function expressInterest(GuestEngagementService $guestService)
    {
        $this->validate();

        // Record interest
        $guestService->recordInterest($this->activity, $this->email, [
            'source' => $this->source,
            'utm_campaign' => $this->utmParams['campaign'] ?? null,
            'utm_source' => $this->utmParams['source'] ?? null,
            'utm_medium' => $this->utmParams['medium'] ?? null,
        ]);

        $this->showInterestModal = false;
        $this->email = '';

        session()->flash('message', 'Thanks! We\'ll send you event updates and reminders.');
    }

    public function bookmark(GuestEngagementService $guestService)
    {
        $guestToken = Cookie::get('guest_token');

        $result = $guestService->createBookmark($this->activity, $guestToken, $this->source);

        // Set cookie if new token was generated
        if (! $guestToken) {
            Cookie::queue('guest_token', $result['token'], 60 * 24 * 365); // 1 year
        }

        session()->flash('message', 'Event bookmarked! Find it in your saved events.');
    }

    public function rsvp(ContextPreservationService $contextService)
    {
        // Capture intent to RSVP
        $contextService->captureIntent($this->activity, 'rsvp', [
            'source' => $this->source,
            'referral_code' => $this->referralCode,
            'utm_params' => $this->utmParams,
        ]);

        // Redirect to login/register
        return redirect()->route('login');
    }

    public function share(string $platform, SocialShareService $shareService)
    {
        $user = Auth::check() ? Auth::user() : null;
        $shareUrl = $shareService->generateShareUrl($this->activity, $platform, $user);

        // Return share URL for frontend to handle
        $this->dispatch('share-url-generated', [
            'platform' => $platform,
            'url' => $shareUrl,
        ]);
    }

    public function render()
    {
        return view('livewire.public-event-view', [
            'interestedCount' => $this->activity->interested_count,
            'shareCount' => $this->activity->share_count,
            'rsvpCount' => $this->activity->rsvps()->where('status', 'confirmed')->count(),
        ])->layout('layouts.galaxy', [
            'title' => $this->activity->title.' - '.config('app.name'),
        ]);
    }
}
