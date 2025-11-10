<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration adds comprehensive performance indexes to all core tables.
     * Indexes are organized by category:
     * - GIN indexes for array/JSONB columns (tags, interests, images, data)
     * - Partial indexes for boolean flags (is_host, is_public, is_active)
     * - Composite indexes for common query patterns
     * - Temporal indexes for time-based queries
     */
    public function up(): void
    {
        // ============================================================
        // USERS TABLE - Additional Indexes
        // ============================================================

        // Note: interests is stored as TEXT (JSON), not native array, so we skip GIN index
        // It will be queried using JSON functions which can use regular indexes

        // Partial index for active users (most queries filter by is_active)
        DB::statement('CREATE INDEX idx_users_active ON users(is_active) WHERE is_active = TRUE');

        // Partial index for verified users
        DB::statement('CREATE INDEX idx_users_verified ON users(is_verified) WHERE is_verified = TRUE');

        // Composite index for location-based user discovery
        DB::statement('CREATE INDEX idx_users_location_active ON users USING GIST(location_coordinates) WHERE is_active = TRUE AND privacy_level = \'public\'');

        // Text search indexes for username and display name
        DB::statement('CREATE INDEX idx_users_username_search ON users(username text_pattern_ops)');
        DB::statement('CREATE INDEX idx_users_display_name_search ON users(display_name text_pattern_ops)');

        // Composite index for follower/following counts (leaderboards)
        DB::statement('CREATE INDEX idx_users_follower_count ON users(follower_count DESC) WHERE is_active = TRUE');

        // ============================================================
        // POSTS TABLE - Additional Indexes
        // ============================================================

        // Note: tags is stored as TEXT (JSON), not native array, so we skip GIN index
        // It will be queried using JSON functions which can use regular indexes

        // Partial index for active posts (not expired)

        // Composite index for location + time queries (most common discovery pattern)

        // Index for mood-based discovery
        DB::statement('CREATE INDEX idx_posts_mood ON posts(mood)');

        // Composite index for user's post history
        DB::statement('CREATE INDEX idx_posts_user_created ON posts(user_id, created_at DESC)');

        // Index for posts that evolved into events
        DB::statement('CREATE INDEX idx_posts_evolved ON posts(evolved_to_event_id) WHERE evolved_to_event_id IS NOT NULL');

        // Composite index for engagement metrics
        DB::statement('CREATE INDEX idx_posts_engagement ON posts(reaction_count DESC, view_count DESC)');

        // ============================================================
        // ACTIVITIES TABLE - Additional Indexes
        // ============================================================

        // Note: tags and images are stored as TEXT (JSON), not native arrays, so we skip GIN indexes
        // They will be queried using JSON functions which can use regular indexes

        // Partial index for public activities
        DB::statement('CREATE INDEX idx_activities_public ON activities(is_public) WHERE is_public = TRUE AND status = \'active\'');

        // Composite index for location + time queries (most common discovery pattern)

        // Composite index for activity type + time
        DB::statement('CREATE INDEX idx_activities_type_time ON activities(activity_type, start_time DESC) WHERE status = \'active\'');

        // Composite index for host's activity history
        DB::statement('CREATE INDEX idx_activities_host_time ON activities(host_id, start_time DESC)');

        // Index for paid activities
        DB::statement('CREATE INDEX idx_activities_paid ON activities(is_paid, price_cents) WHERE is_paid = TRUE AND status = \'active\'');

        // Index for activities that originated from posts
        DB::statement('CREATE INDEX idx_activities_from_post ON activities(originated_from_post_id) WHERE originated_from_post_id IS NOT NULL');

        // Composite index for capacity tracking
        DB::statement('CREATE INDEX idx_activities_capacity ON activities(current_attendees, max_attendees) WHERE status = \'active\' AND max_attendees IS NOT NULL');

        // ============================================================
        // POST_REACTIONS TABLE - Additional Indexes
        // ============================================================

        // Composite index for user's reaction history
        DB::statement('CREATE INDEX idx_post_reactions_user_created ON post_reactions(user_id, created_at DESC)');

        // Composite index for post reactions by type
        DB::statement('CREATE INDEX idx_post_reactions_post_type ON post_reactions(post_id, reaction_type, created_at DESC)');

        // ============================================================
        // POST_CONVERSIONS TABLE - Additional Indexes
        // ============================================================

        // Index for conversion metrics analysis

        // Index for conversion rate analysis
        DB::statement('CREATE INDEX idx_post_conversions_rate ON post_conversions(rsvp_conversion_rate DESC) WHERE rsvp_conversion_rate IS NOT NULL');

        // ============================================================
        // FOLLOWS TABLE - Additional Indexes
        // ============================================================

        // Composite indexes for follower/following queries with time
        DB::statement('CREATE INDEX idx_follows_follower_created ON follows(follower_id, created_at DESC)');
        DB::statement('CREATE INDEX idx_follows_following_created ON follows(following_id, created_at DESC)');

        // Composite index for mutual follow detection
        DB::statement('CREATE INDEX idx_follows_mutual ON follows(follower_id, following_id)');

        // ============================================================
        // RSVPS TABLE - Additional Indexes
        // ============================================================

        // Composite index for user's RSVP history
        DB::statement('CREATE INDEX idx_rsvps_user_created ON rsvps(user_id, created_at DESC)');

        // Composite index for activity participant lists by status
        DB::statement('CREATE INDEX idx_rsvps_activity_status_created ON rsvps(activity_id, status, created_at DESC)');

        // Index for paid RSVPs
        DB::statement('CREATE INDEX idx_rsvps_paid ON rsvps(is_paid, payment_status) WHERE is_paid = TRUE');

        // Composite index for payment tracking
        DB::statement('CREATE INDEX idx_rsvps_payment_status ON rsvps(payment_status, created_at DESC) WHERE payment_status IS NOT NULL');

        // ============================================================
        // COMMENTS TABLE - Additional Indexes
        // ============================================================

        // Composite index for activity comments ordered by time
        DB::statement('CREATE INDEX idx_comments_activity_created ON comments(activity_id, created_at DESC) WHERE is_deleted = FALSE');

        // Composite index for user's comment history
        DB::statement('CREATE INDEX idx_comments_user_created ON comments(user_id, created_at DESC)');

        // Index for top-level comments (no parent)
        DB::statement('CREATE INDEX idx_comments_top_level ON comments(activity_id, created_at DESC) WHERE parent_comment_id IS NULL AND is_deleted = FALSE');

        // Index for comment replies
        DB::statement('CREATE INDEX idx_comments_replies ON comments(parent_comment_id, created_at ASC) WHERE parent_comment_id IS NOT NULL AND is_deleted = FALSE');

        // ============================================================
        // TAGS TABLE - Additional Indexes
        // ============================================================

        // Composite index for popular tags
        DB::statement('CREATE INDEX idx_tags_usage ON tags(usage_count DESC, name)');

        // Text search index for tag autocomplete
        DB::statement('CREATE INDEX idx_tags_name_search ON tags(name text_pattern_ops)');

        // Index for tags by category
        DB::statement('CREATE INDEX idx_tags_category_usage ON tags(category, usage_count DESC) WHERE category IS NOT NULL');

        // ============================================================
        // NOTIFICATIONS TABLE - Additional Indexes
        // ============================================================

        // Composite index for user's unread notifications
        DB::statement('CREATE INDEX idx_notifications_user_unread ON notifications(user_id, created_at DESC) WHERE is_read = FALSE');

        // Composite index for notification delivery tracking
        DB::statement('CREATE INDEX idx_notifications_delivery ON notifications(delivery_status, delivery_method, created_at DESC)');

        // Index for notification type analysis
        DB::statement('CREATE INDEX idx_notifications_type_created ON notifications(type, created_at DESC)');

        // GIN index for notification data (flexible querying)
        DB::statement('CREATE INDEX idx_notifications_data_gin ON notifications USING GIN(data) WHERE data IS NOT NULL');

        // ============================================================
        // FLARES TABLE - Additional Indexes
        // ============================================================

        // Composite index for active flares by location
        DB::statement('CREATE INDEX idx_flares_location_active ON flares USING GIST(location_coordinates) WHERE status = \'active\'');

        // Composite index for flare discovery by type
        DB::statement('CREATE INDEX idx_flares_type_created ON flares(activity_type, created_at DESC) WHERE status = \'active\'');

        // Composite index for user's flare history
        DB::statement('CREATE INDEX idx_flares_user_created ON flares(user_id, created_at DESC)');

        // Index for flares that converted to activities
        DB::statement('CREATE INDEX idx_flares_converted ON flares(converted_activity_id, created_at DESC) WHERE converted_activity_id IS NOT NULL');

        // Index for expiring flares
        DB::statement('CREATE INDEX idx_flares_expires ON flares(expires_at) WHERE expires_at IS NOT NULL AND status = \'active\'');

        // ============================================================
        // REPORTS TABLE - Additional Indexes
        // ============================================================

        // Composite index for pending reports (admin dashboard)
        DB::statement('CREATE INDEX idx_reports_pending ON reports(status, created_at DESC) WHERE status = \'pending\'');

        // Composite index for reporter's report history
        DB::statement('CREATE INDEX idx_reports_reporter_created ON reports(reporter_id, created_at DESC)');

        // Index for reports by target type
        DB::statement('CREATE INDEX idx_reports_user_target ON reports(reported_user_id, status) WHERE reported_user_id IS NOT NULL');
        DB::statement('CREATE INDEX idx_reports_activity_target ON reports(reported_activity_id, status) WHERE reported_activity_id IS NOT NULL');
        DB::statement('CREATE INDEX idx_reports_comment_target ON reports(reported_comment_id, status) WHERE reported_comment_id IS NOT NULL');

        // Index for admin review tracking
        DB::statement('CREATE INDEX idx_reports_reviewed_by ON reports(reviewed_by, reviewed_at DESC) WHERE reviewed_by IS NOT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop all indexes in reverse order

        // Reports indexes
        DB::statement('DROP INDEX IF EXISTS idx_reports_reviewed_by');
        DB::statement('DROP INDEX IF EXISTS idx_reports_comment_target');
        DB::statement('DROP INDEX IF EXISTS idx_reports_activity_target');
        DB::statement('DROP INDEX IF EXISTS idx_reports_user_target');
        DB::statement('DROP INDEX IF EXISTS idx_reports_reporter_created');
        DB::statement('DROP INDEX IF EXISTS idx_reports_pending');

        // Flares indexes
        DB::statement('DROP INDEX IF EXISTS idx_flares_expires');
        DB::statement('DROP INDEX IF EXISTS idx_flares_converted');
        DB::statement('DROP INDEX IF EXISTS idx_flares_user_created');
        DB::statement('DROP INDEX IF EXISTS idx_flares_type_created');
        DB::statement('DROP INDEX IF EXISTS idx_flares_location_active');

        // Notifications indexes
        DB::statement('DROP INDEX IF EXISTS idx_notifications_data_gin');
        DB::statement('DROP INDEX IF EXISTS idx_notifications_type_created');
        DB::statement('DROP INDEX IF EXISTS idx_notifications_delivery');
        DB::statement('DROP INDEX IF EXISTS idx_notifications_user_unread');

        // Tags indexes
        DB::statement('DROP INDEX IF EXISTS idx_tags_category_usage');
        DB::statement('DROP INDEX IF EXISTS idx_tags_name_search');
        DB::statement('DROP INDEX IF EXISTS idx_tags_usage');

        // Comments indexes
        DB::statement('DROP INDEX IF EXISTS idx_comments_replies');
        DB::statement('DROP INDEX IF EXISTS idx_comments_top_level');
        DB::statement('DROP INDEX IF EXISTS idx_comments_user_created');
        DB::statement('DROP INDEX IF EXISTS idx_comments_activity_created');

        // RSVPs indexes
        DB::statement('DROP INDEX IF EXISTS idx_rsvps_payment_status');
        DB::statement('DROP INDEX IF EXISTS idx_rsvps_paid');
        DB::statement('DROP INDEX IF EXISTS idx_rsvps_activity_status_created');
        DB::statement('DROP INDEX IF EXISTS idx_rsvps_user_created');

        // Follows indexes
        DB::statement('DROP INDEX IF EXISTS idx_follows_mutual');
        DB::statement('DROP INDEX IF EXISTS idx_follows_following_created');
        DB::statement('DROP INDEX IF EXISTS idx_follows_follower_created');

        // Post conversions indexes
        DB::statement('DROP INDEX IF EXISTS idx_post_conversions_rate');

        // Post reactions indexes
        DB::statement('DROP INDEX IF EXISTS idx_post_reactions_post_type');
        DB::statement('DROP INDEX IF EXISTS idx_post_reactions_user_created');

        // Activities indexes
        DB::statement('DROP INDEX IF EXISTS idx_activities_capacity');
        DB::statement('DROP INDEX IF EXISTS idx_activities_from_post');
        DB::statement('DROP INDEX IF EXISTS idx_activities_paid');
        DB::statement('DROP INDEX IF EXISTS idx_activities_host_time');
        DB::statement('DROP INDEX IF EXISTS idx_activities_type_time');
        DB::statement('DROP INDEX IF EXISTS idx_activities_location_time');
        DB::statement('DROP INDEX IF EXISTS idx_activities_public');

        // Posts indexes
        DB::statement('DROP INDEX IF EXISTS idx_posts_engagement');
        DB::statement('DROP INDEX IF EXISTS idx_posts_evolved');
        DB::statement('DROP INDEX IF EXISTS idx_posts_user_created');
        DB::statement('DROP INDEX IF EXISTS idx_posts_mood');
        DB::statement('DROP INDEX IF EXISTS idx_posts_location_time');
        DB::statement('DROP INDEX IF EXISTS idx_posts_active');

        // Users indexes
        DB::statement('DROP INDEX IF EXISTS idx_users_follower_count');
        DB::statement('DROP INDEX IF EXISTS idx_users_display_name_search');
        DB::statement('DROP INDEX IF EXISTS idx_users_username_search');
        DB::statement('DROP INDEX IF EXISTS idx_users_location_active');
        DB::statement('DROP INDEX IF EXISTS idx_users_verified');
        DB::statement('DROP INDEX IF EXISTS idx_users_active');
    }
};
