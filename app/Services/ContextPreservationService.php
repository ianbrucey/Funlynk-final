<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Group;
use Illuminate\Support\Facades\Session;

class ContextPreservationService
{
    /**
     * Capture user intent to interact with an activity
     *
     * @param  string  $intentType  'rsvp', 'view', 'interested'
     * @param  array  $metadata  ['source', 'referral_code', 'utm_params']
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
     * Capture user intent to join a group
     *
     * @param  string  $intentType  'join', 'view'
     * @param  array  $metadata  ['source', 'referral_code', 'utm_params']
     */
    public function captureGroupIntent(Group $group, string $intentType, array $metadata = []): void
    {
        Session::put('intended_action', [
            'type' => $intentType,
            'group_id' => $group->id,
            'group_slug' => $group->slug,
            'source' => $metadata['source'] ?? null,
            'referral_code' => $metadata['referral_code'] ?? null,
            'utm_params' => $metadata['utm_params'] ?? null,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Get intended action from session
     */
    public function getIntendedAction(): ?array
    {
        return Session::get('intended_action');
    }

    /**
     * Clear intended action from session
     */
    public function clearIntendedAction(): void
    {
        Session::forget('intended_action');
    }

    /**
     * Get redirect URL based on intended action
     */
    public function getRedirectUrl(array $action): string
    {
        // Handle group intents
        if (isset($action['group_id'])) {
            return match ($action['type']) {
                'join' => route('groups.show', $action['group_slug']),
                'view' => route('groups.public', $action['group_slug']),
                default => route('dashboard')
            };
        }

        // Handle activity intents
        return match ($action['type']) {
            'rsvp' => route('events.checkout', $action['activity_id']),
            'view' => route('events.show', $action['activity_id']),
            'interested' => route('events.show', $action['activity_id']),
            default => route('dashboard')
        };
    }

    /**
     * Capture referral code from URL
     */
    public function captureReferralCode(string $referralCode): void
    {
        Session::put('referral_code', $referralCode);
    }

    /**
     * Capture UTM parameters from URL
     *
     * @param  array  $utmParams  ['source', 'medium', 'campaign']
     */
    public function captureUtmParams(array $utmParams): void
    {
        Session::put('utm_params', $utmParams);
    }

    /**
     * Get all tracking metadata from session
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
     */
    public function hasIntendedAction(): bool
    {
        return Session::has('intended_action');
    }

    /**
     * Get referral code from session
     */
    public function getReferralCode(): ?string
    {
        return Session::get('referral_code');
    }

    /**
     * Get UTM parameters from session
     */
    public function getUtmParams(): ?array
    {
        return Session::get('utm_params');
    }

    /**
     * Clear all tracking metadata
     */
    public function clearTrackingMetadata(): void
    {
        Session::forget(['intended_action', 'referral_code', 'utm_params']);
    }
}
