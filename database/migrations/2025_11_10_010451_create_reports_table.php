<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('reporter_id')
                ->constrained('users', 'id')
                ->cascadeOnDelete();

            $table->foreignUuid('reported_user_id')
                ->nullable()
                ->constrained('users', 'id')
                ->cascadeOnDelete();

            $table->foreignUuid('reported_activity_id')
                ->nullable()
                ->constrained('activities', 'id')
                ->cascadeOnDelete();

            $table->foreignUuid('reported_comment_id')
                ->nullable()
                ->constrained('comments', 'id')
                ->cascadeOnDelete();

            $table->string('reason', 50); // spam, inappropriate, harassment, etc.
            $table->text('description')->nullable();
            $table->string('status', 20)->default('pending'); // pending, reviewed, resolved, dismissed
            $table->text('admin_notes')->nullable();

            $table->foreignUuid('reviewed_by')
                ->nullable()
                ->constrained('users', 'id')
                ->nullOnDelete();

            $table->timestampTz('reviewed_at')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            // Indexes
            $table->index('reporter_id', 'idx_reports_reporter');
            $table->index('status', 'idx_reports_status');
            $table->index('created_at', 'idx_reports_created');
        });

        // Add check constraint to ensure exactly one target is reported
        DB::statement('
            ALTER TABLE reports ADD CONSTRAINT report_target_check CHECK (
                (CASE WHEN reported_user_id IS NOT NULL THEN 1 ELSE 0 END +
                 CASE WHEN reported_activity_id IS NOT NULL THEN 1 ELSE 0 END +
                 CASE WHEN reported_comment_id IS NOT NULL THEN 1 ELSE 0 END) = 1
            )
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
