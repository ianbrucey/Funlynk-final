<?php

namespace Database\Factories;

use App\Models\ActivityRefundWindow;
use App\Models\Rsvp;
use App\Models\RsvpChangeResponse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RsvpChangeResponse>
 */
class RsvpChangeResponseFactory extends Factory
{
    protected $model = RsvpChangeResponse::class;

    public function definition(): array
    {
        return [
            'rsvp_id' => Rsvp::factory(),
            'refund_window_id' => ActivityRefundWindow::factory(),
            'response' => null,
            'responded_at' => null,
            'transaction_id' => null,
            'notified_at' => now(),
            'notification_id' => fake()->uuid(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => [
            'response' => null,
            'responded_at' => null,
        ]);
    }

    public function accepted(): static
    {
        return $this->state(fn () => [
            'response' => RsvpChangeResponse::RESPONSE_ACCEPTED,
            'responded_at' => now(),
        ]);
    }

    public function refunded(): static
    {
        return $this->state(fn () => [
            'response' => RsvpChangeResponse::RESPONSE_REFUNDED,
            'responded_at' => now(),
        ]);
    }
}
