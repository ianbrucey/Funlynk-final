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

            $table->foreignUuid('reported_message_id')
                ->nullable()
                ->constrained('messages', 'id')
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
                 CASE WHEN reported_message_id IS NOT NULL THEN 1 ELSE 0 END) = 1
            )
        ');

        // Additional indexes for performance
        DB::statement('CREATE INDEX idx_reports_pending ON reports(status, created_at DESC) WHERE status = \'pending\'');
        DB::statement('CREATE INDEX idx_reports_reporter_created ON reports(reporter_id, created_at DESC)');
        DB::statement('CREATE INDEX idx_reports_user_target ON reports(reported_user_id, status) WHERE reported_user_id IS NOT NULL');
        DB::statement('CREATE INDEX idx_reports_activity_target ON reports(reported_activity_id, status) WHERE reported_activity_id IS NOT NULL');
        DB::statement('CREATE INDEX idx_reports_message_target ON reports(reported_message_id, status) WHERE reported_message_id IS NOT NULL');
        DB::statement('CREATE INDEX idx_reports_reviewed_by ON reports(reviewed_by, reviewed_at DESC) WHERE reviewed_by IS NOT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
