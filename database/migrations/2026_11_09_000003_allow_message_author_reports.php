<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Spec 020 build fix: the original reports check constraint allows
     * exactly one target column, which makes a message report that also
     * names the message's author (reported_message_id + reported_user_id,
     * as the 020 plan requires) impossible to store. The relaxed check
     * keeps every other combination exactly as strict: a report targets
     * a user alone, an activity alone, a message alone, or a message
     * together with its author, and nothing else.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE reports DROP CONSTRAINT IF EXISTS report_target_check');
        DB::statement('
            ALTER TABLE reports ADD CONSTRAINT report_target_check CHECK (
                (CASE WHEN reported_user_id IS NOT NULL THEN 1 ELSE 0 END +
                 CASE WHEN reported_activity_id IS NOT NULL THEN 1 ELSE 0 END +
                 CASE WHEN reported_message_id IS NOT NULL THEN 1 ELSE 0 END) BETWEEN 1 AND 2
                AND NOT (reported_activity_id IS NOT NULL AND (reported_user_id IS NOT NULL OR reported_message_id IS NOT NULL))
            )
        ');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE reports DROP CONSTRAINT IF EXISTS report_target_check');
        DB::statement('
            ALTER TABLE reports ADD CONSTRAINT report_target_check CHECK (
                (CASE WHEN reported_user_id IS NOT NULL THEN 1 ELSE 0 END +
                 CASE WHEN reported_activity_id IS NOT NULL THEN 1 ELSE 0 END +
                 CASE WHEN reported_message_id IS NOT NULL THEN 1 ELSE 0 END) = 1
            )
        ');
    }
};
