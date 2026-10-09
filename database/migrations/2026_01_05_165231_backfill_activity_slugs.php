<?php

use App\Models\Activity;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Generate slugs for existing activities
        Activity::whereNull('slug')->each(function ($activity) {
            $baseSlug = Str::slug($activity->title);
            $slug = $baseSlug;
            $counter = 1;

            // Ensure uniqueness
            while (Activity::where('slug', $slug)->where('id', '!=', $activity->id)->exists()) {
                $slug = $baseSlug.'-'.$counter;
                $counter++;
            }

            $activity->update(['slug' => $slug]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Set all slugs to null
        Activity::query()->update(['slug' => null]);
    }
};
