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
        Schema::table('posts', function (Blueprint $table) {
            $table->timestamp('conversion_prompted_at')->nullable()->after('expires_at');
            $table->timestamp('conversion_dismissed_at')->nullable()->after('conversion_prompted_at');
            $table->integer('conversion_dismiss_count')->default(0)->after('conversion_dismissed_at');

            // Indexes for performance
            $table->index('conversion_prompted_at');
            $table->index(['status', 'reaction_count']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex(['conversion_prompted_at']);
            $table->dropIndex(['status', 'reaction_count']);
            $table->dropColumn(['conversion_prompted_at', 'conversion_dismissed_at', 'conversion_dismiss_count']);
        });
    }
};
