<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            // Link to recurring schedule (if this event was auto-generated)
            $table->foreignUuid('recurring_schedule_id')
                ->nullable()
                ->after('group_id')
                ->constrained('recurring_schedules')
                ->nullOnDelete();

            // The specific date this occurrence represents (for recurring events)
            $table->date('recurrence_date')->nullable()->after('recurring_schedule_id');

            // Index for finding events by schedule
            $table->index(['recurring_schedule_id', 'recurrence_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropForeign(['recurring_schedule_id']);
            $table->dropIndex(['recurring_schedule_id', 'recurrence_date']);
            $table->dropColumn(['recurring_schedule_id', 'recurrence_date']);
        });
    }
};
