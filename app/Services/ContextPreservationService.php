<?php

namespace App\Services;

use App\Models\Activity;
use Illuminate\Support\Facades\Session;

class ContextPreservationService
{
    /**
     * Capture user intent to interact with an activity
     *
     * @param  Activity  $activity
     * @param  string  $intentType  'rsvp', 'view', 'interested'
     * @param  array  $metadata  ['source', 'referral_code', 'utm_params']
     * @return void
     */
    public function captureIntent(Activity $activity, string $intentType, array $metadata = []): void
    {
        Session::put('intended_action', [
            'type' => $intentType,
            'activity_id' => $activity->id,
            'activity_slug' => $activity->slug,
            'source' => $metadata['source'] ?? null,
            'referral_code' => $metadata['referral_code'] ?? null,
            'utm_params' => $metadata['utm_params'] ?? null,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Get intended action from session
     *
     * @return array|null
     */
    public function getIntendedAction(): ?array
    {
        return Session::get('intended_action');
    }

    /**
     * Clear intended action from session
     *
     * @return void
     */
    public function clearIntendedAction(): void
    {
        Session::forget('intended_action');
    }

    /**
     * Get redirect URL based on intended action
     *
     * @param  array  $action
     * @return string
     */
    public function getRedirectUrl(array $action): string
    {
        return match ($action['type']) {
            'rsvp' => route('events.checkout', $action['activity_id']),
            'view' => route('events.show', $action['activity_id']),
            'interested' => route('events.show', $action['activity_id']),
            default => route('dashboard')
        };
    }

    /**
     * Capture referral code from URL
     *
     * @param  string  $referralCode
     * @return void
     */
    public function captureReferralCode(string $referralCode): void
    {
        Session::put('referral_code', $referralCode);
    }

    /**
     * Capture UTM parameters from URL
     *
     * @param  array  $utmParams  ['source', 'medium', 'campaign']
     * @return void
     */
    public function captureUtmParams(array $utmParams): void
    {
        Session::put('utm_params', $utmParams);
    }

    /**
     * Get all tracking metadata from session
     *
     * @return array
     */
    public function getTrackingMetadata(): array
    {
        return [
            'referral_code' => Session::get('referral_code'),
            'utm_params' => Session::get('utm_params'),
            'intended_action' => Session::get('intended_action'),
        ];
    }

    /**
     * Check if there is an intended action
     *
     * @return bool
     */
    public function hasIntendedAction(): bool
    {
        return Session::has('intended_action');
    }

    /**
     * Get referral code from session
     *
     * @return string|null
     */
    public function getReferralCode(): ?string
    {
        return Session::get('referral_code');
    }

    /**
     * Get UTM parameters from session
     *
     * @return array|null
     */
    public function getUtmParams(): ?array
    {
        return Session::get('utm_params');
    }

    /**
     * Clear all tracking metadata
     *
     * @return void
     */
    public function clearTrackingMetadata(): void
    {
        Session::forget(['intended_action', 'referral_code', 'utm_params']);
    }
}

