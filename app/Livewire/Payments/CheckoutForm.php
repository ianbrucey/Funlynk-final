<?php

namespace App\Livewire\Payments;

use App\Models\Activity;
use App\Services\PaymentService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class CheckoutForm extends Component
{
    public Activity $activity;

    public ?string $paymentIntentId = null;

    public ?string $clientSecret = null;

    public bool $processing = false;

    public ?string $errorMessage = null;

    public function mount(Activity $activity)
    {
        // Check if user is authenticated
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        // Check if activity requires online payment
        if ($activity->payment_type !== 'online') {
            return redirect()->route('events.show', $activity);
        }

        $this->activity = $activity;

        $hostStripeAccount = $activity->host->stripeAccount;

        if (! $hostStripeAccount || ! $hostStripeAccount->canAcceptPayments()) {
            $this->errorMessage = 'The host of this event hasn\'t finished setting up payments yet, so tickets can\'t be purchased right now. Please check back later or contact the host.';

            return;
        }

        try {
            $intent = app(PaymentService::class)->createPaymentIntent($activity, Auth::user());

            $this->paymentIntentId = $intent['payment_intent_id'];
            $this->clientSecret = $intent['client_secret'];
        } catch (\Exception $e) {
            report($e);
            $this->errorMessage = 'We couldn\'t start your payment. Please try again in a moment.';
        }
    }

    public function verifyPayment()
    {
        $this->processing = true;
        $this->errorMessage = null;

        try {
            $paymentService = app(PaymentService::class);

            // Verify payment and create RSVP/Transaction
            $transaction = $paymentService->verifyAndProcessPayment(
                $this->paymentIntentId,
                $this->activity,
                Auth::user()
            );

            session()->flash('success', 'Payment successful! You\'re all set for this activity.');

            return redirect()->route('events.show', $this->activity);

        } catch (\Exception $e) {
            $this->errorMessage = $e->getMessage();
            $this->processing = false;
        }
    }

    public function render()
    {
        return view('livewire.payments.checkout-form')
            ->layout('layouts.app');
    }
}
