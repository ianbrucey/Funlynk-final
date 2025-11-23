<?php

namespace App\Services;

use App\Models\StripeAccount;
use App\Models\User;
use Stripe\StripeClient;

class StripeConnectService
{
    protected StripeClient $stripe;

    public function __construct()
    {
        $stripeSecret = config('services.stripe.secret');
        
        if (empty($stripeSecret)) {
            throw new \Exception('Stripe secret key is not configured. Please add STRIPE_SECRET_KEY to your .env file.');
        }
        
        $this->stripe = new StripeClient($stripeSecret);
    }

    /**
     * Create a Stripe Connect Express account for a host
     */
    public function createConnectAccount(User $user): StripeAccount
    {
        // Check if user already has a Stripe account
        if ($user->stripeAccount) {
            return $user->stripeAccount;
        }

        $account = $this->stripe->accounts->create([
            'type' => 'express',
            'country' => 'US',
            'email' => $user->email,
            'capabilities' => [
                'card_payments' => ['requested' => true],
                'transfers' => ['requested' => true],
            ],
            'business_type' => 'individual',
            'metadata' => [
                'user_id' => $user->id,
                'display_name' => $user->display_name,
            ],
        ]);

        return StripeAccount::create([
            'user_id' => $user->id,
            'stripe_account_id' => $account->id,
            'onboarding_complete' => false,
            'charges_enabled' => false,
            'payouts_enabled' => false,
        ]);
    }

    /**
     * Generate onboarding link for host
     */
    public function generateAccountLink(StripeAccount $stripeAccount): string
    {
        $accountLink = $this->stripe->accountLinks->create([
            'account' => $stripeAccount->stripe_account_id,
            'refresh_url' => route('stripe.onboarding.refresh'),
            'return_url' => route('stripe.onboarding.return'),
            'type' => 'account_onboarding',
        ]);

        return $accountLink->url;
    }

    /**
     * Sync account status from Stripe
     */
    public function refreshAccountStatus(StripeAccount $stripeAccount): StripeAccount
    {
        $account = $this->stripe->accounts->retrieve($stripeAccount->stripe_account_id);

        $stripeAccount->update([
            'charges_enabled' => $account->charges_enabled,
            'payouts_enabled' => $account->payouts_enabled,
            'onboarding_complete' => $account->details_submitted,
            'requirements' => $account->requirements->toArray(),
            'onboarded_at' => $account->details_submitted ? now() : null,
        ]);

        return $stripeAccount->fresh();
    }

    /**
     * Check if account can accept payments
     */
    public function canAcceptPayments(User $user): bool
    {
        if (!$user->stripeAccount) {
            return false;
        }

        return $user->stripeAccount->canAcceptPayments();
    }
}
