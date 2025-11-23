<?php

namespace App\Livewire\Payments;

use App\Services\StripeConnectService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class StripeOnboarding extends Component
{
    public ?string $status = null;
    public bool $isOnboarded = false;
    public bool $canAcceptPayments = false;
    public ?string $errorMessage = null;
    public ?array $requirements = null;

    public function mount()
    {
        $this->checkStatus();
    }

    public function checkStatus()
    {
        $user = Auth::user();
        
        if (!$user->stripeAccount) {
            $this->status = 'not_connected';
            return;
        }

        try {
            // Refresh status from Stripe
            $stripeConnectService = app(StripeConnectService::class);
            $stripeAccount = $stripeConnectService->refreshAccountStatus($user->stripeAccount);

            $this->isOnboarded = $stripeAccount->isOnboarded();
            $this->canAcceptPayments = $stripeAccount->canAcceptPayments();
            $this->requirements = $stripeAccount->requirements;

            if ($this->canAcceptPayments) {
                $this->status = 'complete';
            } elseif ($this->isOnboarded) {
                $this->status = 'pending_approval';
            } else {
                $this->status = 'incomplete';
            }
        } catch (\Exception $e) {
            $this->errorMessage = 'Error checking Stripe status: ' . $e->getMessage();
            $this->status = 'incomplete';
        }
    }

    public function startOnboarding()
    {
        $user = Auth::user();
        $stripeConnectService = app(StripeConnectService::class);

        try {
            // Create Stripe account if doesn't exist
            if (!$user->stripeAccount) {
                $stripeConnectService->createConnectAccount($user);
                $user->refresh();
            }

            // Generate onboarding link
            $onboardingUrl = $stripeConnectService->generateAccountLink($user->stripeAccount);

            // Redirect to Stripe onboarding
            return redirect()->away($onboardingUrl);
            
        } catch (\Exception $e) {
            $this->errorMessage = 'Error starting onboarding: ' . $e->getMessage();
        }
    }

    public function render()
    {
        return view('livewire.payments.stripe-onboarding')
            ->layout('layouts.app');
    }
}
