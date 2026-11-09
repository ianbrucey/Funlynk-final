<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Spec 019: external event listings ("Around town").
     *
     * Backward-compatible: every existing row stays a hosted event
     * (kind defaults to "hosted" and host_id is already set). host_id
     * becomes nullable, guarded by a check constraint so hosted events
     * still require a host and listings never have one.
     */
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->string('kind', 20)->default('hosted')->index();
            $table->string('source_name')->nullable();
            $table->string('source_url')->nullable();
            $table->foreignUuid('approved_by')->nullable()->constrained('users', 'id')->nullOnDelete();
            $table->timestampTz('approved_at')->nullable();
        });

        DB::statement('ALTER TABLE activities ALTER COLUMN host_id DROP NOT NULL');

        DB::statement("
            ALTER TABLE activities ADD CONSTRAINT activities_kind_host_check CHECK (
                (kind = 'hosted' AND host_id IS NOT NULL) OR
                (kind = 'external' AND host_id IS NULL)
            )
        ");

        Schema::create('activity_interests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('activity_id')->constrained('activities', 'id')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users', 'id')->cascadeOnDelete();
            $table->timestampsTz();
            $table->unique(['activity_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_interests');

        DB::statement('ALTER TABLE activities DROP CONSTRAINT IF EXISTS activities_kind_host_check');
        DB::statement('ALTER TABLE activities ALTER COLUMN host_id SET NOT NULL');

        Schema::table('activities', function (Blueprint $table) {
            $table->dropConstrainedForeignId('approved_by');
            $table->dropColumn(['kind', 'source_name', 'source_url', 'approved_at']);
        });
    }
};
