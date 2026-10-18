<?php

use App\Models\Activity;
use App\Models\StripeAccount;
use App\Models\Transaction;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Stripe\StripeClient;

uses(RefreshDatabase::class);

/**
 * Build a PaymentService whose Stripe client is a fake, so no network call is made.
 *
 * @param  array<string, object>  $services  e.g. ['paymentIntents' => $fake]
 */
function paymentServiceWithFakeStripe(array $services): PaymentService
{
    config(['services.stripe.secret' => 'sk_test_fake']);

    $service = new PaymentService;
    $client = new class($services) extends StripeClient
    {
        /** @param array<string, object> $fakes */
        public function __construct(private array $fakes) {}

        public function __get($name)
        {
            return $this->fakes[$name];
        }
    };

    $property = new ReflectionProperty($service, 'stripe');
    $property->setValue($service, $client);

    return $service;
}

function paidActivity(bool $hostCanAcceptPayments = true): Activity
{
    $host = User::factory()->create();
    StripeAccount::create([
        'user_id' => $host->id,
        'stripe_account_id' => 'acct_test_123',
        'onboarding_complete' => $hostCanAcceptPayments,
        'charges_enabled' => $hostCanAcceptPayments,
        'payouts_enabled' => $hostCanAcceptPayments,
    ]);

    return Activity::factory()->create([
        'host_id' => $host->id,
        'payment_type' => 'online',
        'price_cents' => 5000,
    ]);
}

it('creates a payment intent that pays the host with an 8 percent platform fee', function () {
    $activity = paidActivity();
    $attendee = User::factory()->create();
    $captured = null;

    $intents = new class($captured)
    {
        public function __construct(public mixed &$captured) {}

        public function create(array $params): object
        {
            $this->captured = $params;

            return (object) ['id' => 'pi_test_1', 'client_secret' => 'pi_test_1_secret'];
        }
    };

    $result = paymentServiceWithFakeStripe(['paymentIntents' => $intents])
        ->createPaymentIntent($activity, $attendee);

    expect($result['payment_intent_id'])->toBe('pi_test_1')
        ->and($result['platform_fee'])->toBe(400)
        ->and($captured['amount'])->toBe(5000)
        ->and($captured['transfer_data']['destination'])->toBe('acct_test_123')
        ->and($captured['metadata']['user_id'])->toBe($attendee->id);
});

it('refuses to create a payment intent when the host has not finished onboarding', function () {
    $activity = paidActivity(hostCanAcceptPayments: false);

    paymentServiceWithFakeStripe(['paymentIntents' => new stdClass])
        ->createPaymentIntent($activity, User::factory()->create());
})->throws(Exception::class, 'Host has not completed Stripe onboarding');

it('creates the rsvp and transaction once the payment succeeded, and is idempotent', function () {
    $activity = paidActivity();
    $attendee = User::factory()->create();

    $intents = new class
    {
        public function retrieve(string $id): object
        {
            return (object) [
                'id' => $id,
                'status' => 'succeeded',
                'amount' => 5000,
                'currency' => 'usd',
                'payment_method' => 'pm_test',
                'charges' => (object) ['data' => []],
            ];
        }
    };

    $service = paymentServiceWithFakeStripe(['paymentIntents' => $intents]);

    $first = $service->verifyAndProcessPayment('pi_test_1', $activity, $attendee);
    $second = $service->verifyAndProcessPayment('pi_test_1', $activity, $attendee);

    expect($first->status)->toBe('succeeded')
        ->and($first->platform_fee)->toBe(400)
        ->and($first->host_earnings)->toBe(4600)
        ->and($second->id)->toBe($first->id)
        ->and(Transaction::count())->toBe(1)
        ->and($activity->rsvps()->where('user_id', $attendee->id)->count())->toBe(1);
});

it('does not create an rsvp when the payment has not succeeded', function () {
    $activity = paidActivity();
    $attendee = User::factory()->create();

    $intents = new class
    {
        public function retrieve(string $id): object
        {
            return (object) ['id' => $id, 'status' => 'requires_payment_method'];
        }
    };

    expect(fn () => paymentServiceWithFakeStripe(['paymentIntents' => $intents])
        ->verifyAndProcessPayment('pi_test_2', $activity, $attendee))
        ->toThrow(Exception::class, 'Payment not successful');

    expect(Transaction::count())->toBe(0)
        ->and($activity->rsvps()->count())->toBe(0);
});
