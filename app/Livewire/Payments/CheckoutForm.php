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
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        // Check if activity is paid
        if (!$activity->is_paid) {
            return redirect()->route('activities.show', $activity);
        }

        $this->activity = $activity;

        // Create payment intent
        $paymentService = app(PaymentService::class);
        $intent = $paymentService->createPaymentIntent($activity, Auth::user());

        $this->paymentIntentId = $intent['payment_intent_id'];
        $this->clientSecret = $intent['client_secret'];
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
            
            return redirect()->route('activities.show', $this->activity);
            
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
