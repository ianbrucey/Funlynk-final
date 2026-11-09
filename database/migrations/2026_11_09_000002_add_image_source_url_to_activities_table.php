<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Spec 020: provenance for a listing image downloaded at import
     * time. The image itself lives in the existing `images` array as a
     * storage path (never a source URL, which would silently hotlink);
     * this column records where it came from for credit and takedown.
     */
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->string('image_source_url')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropColumn('image_source_url');
        });
    }
};
