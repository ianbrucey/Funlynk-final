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
        Schema::create('tags', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('name', 50)->unique();
            $table->string('category', 50)->nullable(); // sports, music, social, etc.
            $table->text('description')->nullable();
            $table->unsignedInteger('usage_count')->default(0);
            $table->boolean('is_featured')->default(false);

            $table->timestampTz('created_at')->useCurrent();

            // Indexes
            $table->index('name', 'idx_tags_name');
            $table->index('category', 'idx_tags_category');
            $table->index('usage_count', 'idx_tags_usage_count');
        });

        // Add partial index for featured tags
        DB::statement('CREATE INDEX idx_tags_featured ON tags(is_featured) WHERE is_featured = TRUE');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tags');
    }
};
