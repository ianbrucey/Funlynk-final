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
        Schema::table('users', function (Blueprint $table) {
            // Notification preferences
            $table->string('notification_preference')->default('all')->comment('all, in_app_only, email_only, none');
            $table->boolean('email_on_post_converted')->default(true)->comment('Send email when post is converted to event');
            $table->boolean('email_on_event_invitation')->default(true)->comment('Send email when invited to event');
            $table->boolean('email_on_rsvp_update')->default(true)->comment('Send email on RSVP updates');
            $table->boolean('email_on_comment')->default(true)->comment('Send email on new comments');
            $table->boolean('email_on_reaction')->default(false)->comment('Send email on post reactions');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'notification_preference',
                'email_on_post_converted',
                'email_on_event_invitation',
                'email_on_rsvp_update',
                'email_on_comment',
                'email_on_reaction',
            ]);
        });
    }
};
