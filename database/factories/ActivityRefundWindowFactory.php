<?php

namespace Database\Factories;

use App\Models\Activity;
use App\Models\ActivityRefundWindow;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ActivityRefundWindow>
 */
class ActivityRefundWindowFactory extends Factory
{
    protected $model = ActivityRefundWindow::class;

    public function definition(): array
    {
        $triggeredAt = now();

        return [
            'activity_id' => Activity::factory(),
            'triggered_at' => $triggeredAt,
            'expires_at' => $triggeredAt->copy()->addHours(ActivityRefundWindow::WINDOW_HOURS),
            'trigger_edit_log_id' => null,
            'changes_summary' => [
                [
                    'field' => 'start_time',
                    'old_display' => 'December 15, 2024 at 7:00 PM',
                    'new_display' => 'December 22, 2024 at 7:00 PM',
                ],
            ],
            'status' => ActivityRefundWindow::STATUS_ACTIVE,
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => [
            'status' => ActivityRefundWindow::STATUS_ACTIVE,
            'triggered_at' => now(),
            'expires_at' => now()->addHours(ActivityRefundWindow::WINDOW_HOURS),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'status' => ActivityRefundWindow::STATUS_EXPIRED,
            'triggered_at' => now()->subHours(80),
            'expires_at' => now()->subHours(8),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => [
            'status' => ActivityRefundWindow::STATUS_CANCELLED,
        ]);
    }

    public function expiringsSoon(): static
    {
        return $this->state(fn () => [
            'status' => ActivityRefundWindow::STATUS_ACTIVE,
            'triggered_at' => now()->subHours(70),
            'expires_at' => now()->addHours(2),
        ]);
    }
}
