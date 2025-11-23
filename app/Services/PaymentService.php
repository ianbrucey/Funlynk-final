<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Stripe\StripeClient;

class PaymentService
{
    protected StripeClient $stripe;
    protected float $platformFeePercentage = 0.10; // 10%

    public function __construct()
    {
        $stripeSecret = config('services.stripe.secret');
        
        if (empty($stripeSecret)) {
            throw new \Exception('Stripe secret key is not configured. Please add STRIPE_SECRET_KEY to your .env file.');
        }
        
        $this->stripe = new StripeClient($stripeSecret);
    }

    /**
     * Create a payment intent for activity RSVP
     */
    public function createPaymentIntent(Activity $activity, User $user): array
    {
        $amount = $activity->price_cents;
        $platformFee = (int) ($amount * $this->platformFeePercentage);

        // Check if host has Stripe Connect account
        if (!$activity->host->stripeAccount || !$activity->host->stripeAccount->canAcceptPayments()) {
            throw new \Exception('Host has not completed Stripe onboarding. Cannot process payment.');
        }

        $paymentIntent = $this->stripe->paymentIntents->create([
            'amount' => $amount,
            'currency' => $activity->currency ?? 'usd',
            'application_fee_amount' => $platformFee, // Platform fee (10%)
            'transfer_data' => [
                'destination' => $activity->host->stripeAccount->stripe_account_id, // Payment goes to host
            ],
            'metadata' => [
                'activity_id' => $activity->id,
                'user_id' => $user->id,
                'activity_title' => $activity->title,
                'host_id' => $activity->host_id,
            ],
        ]);

        return [
            'client_secret' => $paymentIntent->client_secret,
            'payment_intent_id' => $paymentIntent->id,
            'amount' => $amount,
            'platform_fee' => $platformFee,
        ];
    }

    /**
     * Verify payment status and create RSVP/Transaction
     * This replaces webhook handling - we poll Stripe directly
     */
    public function verifyAndProcessPayment(string $paymentIntentId, Activity $activity, User $user): Transaction
    {
        return DB::transaction(function () use ($paymentIntentId, $activity, $user) {
            // Check if transaction already exists (idempotency)
            $existingTransaction = Transaction::where('stripe_payment_intent_id', $paymentIntentId)->first();
            if ($existingTransaction) {
                return $existingTransaction;
            }

            // Fetch payment intent from Stripe to verify status
            $paymentIntent = $this->stripe->paymentIntents->retrieve($paymentIntentId);

            if ($paymentIntent->status !== 'succeeded') {
                throw new \Exception('Payment not successful. Status: ' . $paymentIntent->status);
            }

            $amount = $paymentIntent->amount;
            $platformFee = (int) ($amount * $this->platformFeePercentage);

            // Create RSVP
            $rsvp = app(RsvpService::class)->createRsvp($activity, $user, [
                'is_paid' => true,
                'payment_amount' => $amount,
                'payment_status' => 'paid',
                'payment_intent_id' => $paymentIntentId,
            ]);

            // Create transaction record
            $transaction = Transaction::create([
                'user_id' => $user->id,
                'activity_id' => $activity->id,
                'rsvp_id' => $rsvp->id,
                'stripe_payment_intent_id' => $paymentIntentId,
                'amount' => $amount,
                'platform_fee' => $platformFee,
                'host_earnings' => $amount - $platformFee,
                'currency' => $paymentIntent->currency,
                'status' => 'succeeded',
                'succeeded_at' => now(),
                'metadata' => [
                    'payment_method' => $paymentIntent->payment_method,
                    'receipt_url' => $paymentIntent->charges->data[0]->receipt_url ?? null,
                ],
            ]);

            return $transaction;
        });
    }

    /**
     * Process refund for canceled activity
     */
    public function processRefund(Transaction $transaction, ?int $amount = null): void
    {
        $refundAmount = $amount ?? $transaction->amount;

        $refund = $this->stripe->refunds->create([
            'payment_intent' => $transaction->stripe_payment_intent_id,
            'amount' => $refundAmount,
        ]);

        $transaction->update([
            'status' => 'refunded',
            'refunded_amount' => $refundAmount,
            'refunded_at' => now(),
        ]);

        // Update RSVP
        $transaction->rsvp?->update([
            'payment_status' => 'refunded',
        ]);
    }
}
