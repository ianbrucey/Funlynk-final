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
        Schema::create('activity_edit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('activity_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('editor_id')->constrained('users')->cascadeOnDelete();

            // What changed
            $table->string('field_name', 50);
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();

            // Classification: cosmetic, minor, significant, blocked
            $table->string('change_category', 20);

            // Context
            $table->text('reason')->nullable();
            $table->boolean('triggered_refund_window')->default(false);

            // Snapshot at time of edit
            $table->integer('paid_attendee_count')->default(0);

            $table->timestamps();

            $table->index('activity_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_edit_logs');
    }
};
