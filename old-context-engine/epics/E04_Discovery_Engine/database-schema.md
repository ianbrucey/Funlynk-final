# E04 Discovery Engine - Database Schema

## Schema Overview

This document details the database schema requirements specific to Discovery Engine functionality, including the **Posts vs Events dual model** that differentiates FunLynk from traditional event platforms. This schema supports spontaneous post discovery, temporal intelligence, social resonance, and post-to-event evolution.

## Core Tables: Posts vs Events

### Posts Table (NEW)
Ephemeral, spontaneous "energy signals" that auto-expire after 24-48 hours.

```sql
CREATE TABLE posts (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,

    -- Content
    content TEXT NOT NULL,
    tags TEXT[] DEFAULT '{}',

    -- Location (optional but recommended)
    location_name VARCHAR(255),
    location_coordinates GEOGRAPHY(POINT, 4326),
    geo_hash VARCHAR(12), -- For efficient proximity queries

    -- Temporal metadata
    approximate_time TIMESTAMP, -- "tonight", "this weekend", etc.
    expires_at TIMESTAMP NOT NULL DEFAULT (NOW() + INTERVAL '48 hours'),

    -- Mood/vibe tagging
    mood VARCHAR(50), -- creative, social, active, chill, adventurous

    -- Evolution tracking
    evolved_to_event_id UUID REFERENCES activities(id),
    conversion_triggered_at TIMESTAMP,

    -- Engagement metrics
    view_count INTEGER DEFAULT 0,
    reaction_count INTEGER DEFAULT 0,

    -- Timestamps
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),

    -- Indexes for discovery
    INDEX idx_posts_location USING GIST(location_coordinates),
    INDEX idx_posts_geo_hash (geo_hash),
    INDEX idx_posts_expires_at (expires_at),
    INDEX idx_posts_created_at (created_at DESC),
    INDEX idx_posts_tags USING GIN(tags),
    INDEX idx_posts_user (user_id),
    INDEX idx_posts_active (expires_at) WHERE expires_at > NOW()
);

-- Full-text search for posts
CREATE INDEX idx_posts_fulltext ON posts
USING GIN(to_tsvector('english', content));

-- Trigger to update updated_at
CREATE TRIGGER update_posts_updated_at
    BEFORE UPDATE ON posts
    FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();
```

### Post Reactions Table (NEW)
Social resonance interactions: "I'm down" / "Join me" buttons.

```sql
CREATE TABLE post_reactions (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    post_id UUID NOT NULL REFERENCES posts(id) ON DELETE CASCADE,
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,

    -- Reaction types
    reaction_type VARCHAR(20) NOT NULL, -- im_down, join_me, interested

    -- Metadata
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),

    -- Constraints
    UNIQUE(post_id, user_id, reaction_type),

    -- Indexes
    INDEX idx_post_reactions_post (post_id, reaction_type),
    INDEX idx_post_reactions_user (user_id, created_at DESC)
);
```

### Post Conversions Table (NEW)
Track post-to-event evolution metrics.

```sql
CREATE TABLE post_conversions (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    post_id UUID NOT NULL REFERENCES posts(id),
    event_id UUID NOT NULL REFERENCES activities(id),

    -- Conversion context
    trigger_type VARCHAR(50) NOT NULL, -- manual, engagement_threshold, time_sensitive
    reactions_at_conversion INTEGER DEFAULT 0,
    comments_at_conversion INTEGER DEFAULT 0,
    views_at_conversion INTEGER DEFAULT 0,

    -- Conversion outcome
    rsvp_conversion_rate DECIMAL(5,2), -- % of engaged users who RSVP'd

    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),

    -- Indexes
    INDEX idx_post_conversions_post (post_id),
    INDEX idx_post_conversions_event (event_id),
    INDEX idx_post_conversions_trigger (trigger_type)
);
```

### Events Table Updates
Add fields to track events originated from posts.

```sql
-- Add columns to existing activities table
ALTER TABLE activities ADD COLUMN IF NOT EXISTS originated_from_post_id UUID REFERENCES posts(id);
ALTER TABLE activities ADD COLUMN IF NOT EXISTS conversion_date TIMESTAMP WITH TIME ZONE;

-- Add index for post-originated events
CREATE INDEX IF NOT EXISTS idx_activities_originated_from_post ON activities(originated_from_post_id)
WHERE originated_from_post_id IS NOT NULL;
```

## Implicit Communities Schema (Phase 2)

### Activity Clusters Table (NEW)
Auto-generated communities from activity patterns.

```sql
CREATE TABLE activity_clusters (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),

    -- Cluster identity
    cluster_name VARCHAR(100) NOT NULL, -- e.g., "Atlanta Musicians", "Midtown Runners"
    cluster_description TEXT,

    -- Cluster characteristics
    tags TEXT[] DEFAULT '{}',
    location_center GEOGRAPHY(POINT, 4326),
    location_radius_km DECIMAL(10,2) DEFAULT 5.0,

    -- Cluster metrics
    member_count INTEGER DEFAULT 0,
    post_count INTEGER DEFAULT 0,
    event_count INTEGER DEFAULT 0,
    activity_score FLOAT DEFAULT 0, -- Engagement-based scoring

    -- Temporal tracking
    last_activity_at TIMESTAMP WITH TIME ZONE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),

    -- Indexes
    INDEX idx_activity_clusters_location USING GIST(location_center),
    INDEX idx_activity_clusters_tags USING GIN(tags),
    INDEX idx_activity_clusters_score (activity_score DESC)
);

-- Cluster membership (implicit, auto-generated)
CREATE TABLE cluster_members (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    cluster_id UUID NOT NULL REFERENCES activity_clusters(id) ON DELETE CASCADE,
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,

    -- Membership strength
    affinity_score FLOAT DEFAULT 0.5, -- How strongly user matches cluster

    -- Engagement tracking
    posts_in_cluster INTEGER DEFAULT 0,
    events_in_cluster INTEGER DEFAULT 0,
    last_activity_at TIMESTAMP WITH TIME ZONE,

    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),

    UNIQUE(cluster_id, user_id),
    INDEX idx_cluster_members_cluster (cluster_id, affinity_score DESC),
    INDEX idx_cluster_members_user (user_id)
);
```

## Discovery Feed Optimization Schema

### Full-Text Search Indexes
```sql
-- Enable required extensions
CREATE EXTENSION IF NOT EXISTS pg_trgm;
CREATE EXTENSION IF NOT EXISTS postgis;

-- Full-text search for posts (already created above)
-- Full-text search for events/activities
CREATE INDEX IF NOT EXISTS idx_activities_fulltext ON activities
USING GIN(to_tsvector('english', title || ' ' || COALESCE(description, '') || ' ' || COALESCE(requirements, '')));

-- Create search index for activity location and tags
CREATE INDEX IF NOT EXISTS idx_activities_search_composite ON activities
USING GIN(
    to_tsvector('english', title || ' ' || COALESCE(description, ''))
) WHERE status = 'published' AND start_time > NOW();

-- Create user search index
CREATE INDEX IF NOT EXISTS idx_users_search ON users
USING GIN(to_tsvector('english', username || ' ' || display_name || ' ' || COALESCE(bio, '')))
WHERE is_active = TRUE;

-- Create tag search index with trigram support for autocomplete
CREATE INDEX IF NOT EXISTS idx_tags_trigram ON tags USING GIN(name gin_trgm_ops);
```

### Search Analytics Tables
```sql
CREATE TABLE search_queries (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID REFERENCES users(id),
    query_text TEXT NOT NULL,
    filters JSONB,
    result_count INTEGER NOT NULL,
    clicked_results UUID[], -- Array of activity IDs clicked
    search_time TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    response_time_ms INTEGER,
    session_id VARCHAR(255),
    
    -- Index for analytics
    INDEX idx_search_queries_user_time (user_id, search_time DESC),
    INDEX idx_search_queries_text (query_text),
    INDEX idx_search_queries_session (session_id)
);

CREATE TABLE search_suggestions (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    query_prefix VARCHAR(100) NOT NULL,
    suggestion TEXT NOT NULL,
    suggestion_type VARCHAR(20) NOT NULL, -- activity, tag, location, user
    popularity_score FLOAT DEFAULT 0,
    last_used TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    
    UNIQUE(query_prefix, suggestion),
    INDEX idx_search_suggestions_prefix (query_prefix),
    INDEX idx_search_suggestions_popularity (popularity_score DESC)
);
```

## Recommendation Engine Schema

### User Behavior Tracking
```sql
-- Track interactions with both posts and events
CREATE TABLE user_post_interactions (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    post_id UUID NOT NULL REFERENCES posts(id) ON DELETE CASCADE,
    interaction_type VARCHAR(20) NOT NULL, -- view, click, reaction, share, dm
    interaction_time TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    session_id VARCHAR(255),
    source VARCHAR(50), -- nearby_feed, for_you_feed, map_view, notification
    context_data JSONB, -- Additional context like feed position, recommendation reason

    INDEX idx_user_post_interactions_user_time (user_id, interaction_time DESC),
    INDEX idx_user_post_interactions_post (post_id, interaction_type),
    INDEX idx_user_post_interactions_type_time (interaction_type, interaction_time DESC)
);

CREATE TABLE user_activity_interactions (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    activity_id UUID NOT NULL REFERENCES activities(id) ON DELETE CASCADE,
    interaction_type VARCHAR(20) NOT NULL, -- view, click, rsvp, share, save
    interaction_time TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    session_id VARCHAR(255),
    source VARCHAR(50), -- search, feed, recommendation, social
    context_data JSONB, -- Additional context like search query, recommendation reason

    INDEX idx_user_interactions_user_time (user_id, interaction_time DESC),
    INDEX idx_user_interactions_activity (activity_id, interaction_type),
    INDEX idx_user_interactions_type_time (interaction_type, interaction_time DESC)
);

CREATE TABLE user_preferences_learned (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    preference_type VARCHAR(50) NOT NULL, -- tag_affinity, location_preference, time_preference, price_sensitivity
    preference_value TEXT NOT NULL,
    confidence_score FLOAT NOT NULL DEFAULT 0.5,
    last_updated TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    
    UNIQUE(user_id, preference_type, preference_value),
    INDEX idx_user_preferences_user (user_id),
    INDEX idx_user_preferences_type (preference_type)
);
```

### Recommendation Cache Tables
```sql
CREATE TABLE recommendation_cache (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    recommendation_type VARCHAR(50) NOT NULL, -- interest_based, social, location, trending
    activity_id UUID NOT NULL REFERENCES activities(id) ON DELETE CASCADE,
    score FLOAT NOT NULL,
    reasoning JSONB, -- Explanation for the recommendation
    generated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    expires_at TIMESTAMP WITH TIME ZONE NOT NULL,
    
    INDEX idx_recommendation_cache_user_type (user_id, recommendation_type),
    INDEX idx_recommendation_cache_score (user_id, score DESC),
    INDEX idx_recommendation_cache_expires (expires_at)
);

-- Automatically clean up expired recommendations
CREATE OR REPLACE FUNCTION cleanup_expired_recommendations()
RETURNS void AS $$
BEGIN
    DELETE FROM recommendation_cache WHERE expires_at < NOW();
END;
$$ LANGUAGE plpgsql;

-- Schedule cleanup every hour
SELECT cron.schedule('cleanup-recommendations', '0 * * * *', 'SELECT cleanup_expired_recommendations();');
```

### Collaborative Filtering Data
```sql
CREATE TABLE user_similarity_scores (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_a_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    user_b_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    similarity_score FLOAT NOT NULL,
    similarity_type VARCHAR(20) NOT NULL, -- interest, behavior, location, social
    calculated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    
    UNIQUE(user_a_id, user_b_id, similarity_type),
    INDEX idx_user_similarity_user_a (user_a_id, similarity_score DESC),
    INDEX idx_user_similarity_calculated (calculated_at),
    
    CONSTRAINT no_self_similarity CHECK (user_a_id != user_b_id)
);

CREATE TABLE activity_similarity_scores (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    activity_a_id UUID NOT NULL REFERENCES activities(id) ON DELETE CASCADE,
    activity_b_id UUID NOT NULL REFERENCES activities(id) ON DELETE CASCADE,
    similarity_score FLOAT NOT NULL,
    similarity_factors JSONB, -- tags, location, time, host, etc.
    calculated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    
    UNIQUE(activity_a_id, activity_b_id),
    INDEX idx_activity_similarity_a (activity_a_id, similarity_score DESC),
    INDEX idx_activity_similarity_calculated (calculated_at),
    
    CONSTRAINT no_self_similarity CHECK (activity_a_id != activity_b_id)
);
```

## Feed Generation Schema

### Feed Cache Tables
```sql
CREATE TABLE user_feeds (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    feed_type VARCHAR(20) NOT NULL, -- home, social, trending, category
    activity_id UUID NOT NULL REFERENCES activities(id) ON DELETE CASCADE,
    position INTEGER NOT NULL,
    score FLOAT NOT NULL,
    reasoning JSONB,
    generated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    expires_at TIMESTAMP WITH TIME ZONE NOT NULL,
    
    UNIQUE(user_id, feed_type, activity_id),
    INDEX idx_user_feeds_user_type_position (user_id, feed_type, position),
    INDEX idx_user_feeds_expires (expires_at)
);

CREATE TABLE feed_generation_logs (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    feed_type VARCHAR(20) NOT NULL,
    generation_time_ms INTEGER NOT NULL,
    activities_considered INTEGER NOT NULL,
    activities_included INTEGER NOT NULL,
    algorithm_version VARCHAR(20) NOT NULL,
    generated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    
    INDEX idx_feed_logs_user_time (user_id, generated_at DESC),
    INDEX idx_feed_logs_performance (generation_time_ms, generated_at DESC)
);
```

### Trending and Popular Content
```sql
-- Trending posts (velocity-based)
CREATE TABLE trending_posts (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    post_id UUID NOT NULL REFERENCES posts(id) ON DELETE CASCADE,
    trend_score FLOAT NOT NULL,
    trend_factors JSONB, -- reaction_velocity, view_count, share_count, etc.
    time_window VARCHAR(20) NOT NULL, -- hourly, 6hour, 12hour
    calculated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),

    UNIQUE(post_id, time_window, calculated_at::date),
    INDEX idx_trending_posts_score (trend_score DESC, calculated_at DESC),
    INDEX idx_trending_posts_window (time_window, calculated_at DESC)
);

CREATE TABLE trending_activities (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    activity_id UUID NOT NULL REFERENCES activities(id) ON DELETE CASCADE,
    trend_score FLOAT NOT NULL,
    trend_factors JSONB, -- rsvp_velocity, view_count, share_count, etc.
    time_window VARCHAR(20) NOT NULL, -- hourly, daily, weekly
    calculated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),

    UNIQUE(activity_id, time_window, calculated_at::date),
    INDEX idx_trending_activities_score (trend_score DESC, calculated_at DESC),
    INDEX idx_trending_activities_window (time_window, calculated_at DESC)
);

CREATE TABLE popular_tags (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tag_id UUID NOT NULL REFERENCES tags(id) ON DELETE CASCADE,
    popularity_score FLOAT NOT NULL,
    usage_velocity FLOAT NOT NULL, -- Rate of recent usage
    time_window VARCHAR(20) NOT NULL,
    calculated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),

    UNIQUE(tag_id, time_window, calculated_at::date),
    INDEX idx_popular_tags_score (popularity_score DESC, calculated_at DESC),
    INDEX idx_popular_tags_velocity (usage_velocity DESC, calculated_at DESC)
);
```

## Discovery Analytics Schema

### Content Performance Tracking
```sql
-- Post discovery metrics
CREATE TABLE post_discovery_metrics (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    post_id UUID NOT NULL REFERENCES posts(id) ON DELETE CASCADE,
    metric_date DATE NOT NULL DEFAULT CURRENT_DATE,

    -- Discovery metrics
    nearby_feed_impressions INTEGER DEFAULT 0,
    nearby_feed_clicks INTEGER DEFAULT 0,
    for_you_feed_impressions INTEGER DEFAULT 0,
    for_you_feed_clicks INTEGER DEFAULT 0,
    map_view_impressions INTEGER DEFAULT 0,
    map_view_clicks INTEGER DEFAULT 0,

    -- Engagement metrics
    im_down_count INTEGER DEFAULT 0,
    join_me_count INTEGER DEFAULT 0,
    dm_initiated_count INTEGER DEFAULT 0,
    share_count INTEGER DEFAULT 0,

    -- Conversion metrics
    converted_to_event BOOLEAN DEFAULT FALSE,
    conversion_trigger VARCHAR(50),

    UNIQUE(post_id, metric_date),
    INDEX idx_post_metrics_date (metric_date DESC),
    INDEX idx_post_metrics_post (post_id)
);

CREATE TABLE activity_discovery_metrics (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    activity_id UUID NOT NULL REFERENCES activities(id) ON DELETE CASCADE,
    metric_date DATE NOT NULL DEFAULT CURRENT_DATE,

    -- Discovery metrics
    search_impressions INTEGER DEFAULT 0,
    search_clicks INTEGER DEFAULT 0,
    recommendation_impressions INTEGER DEFAULT 0,
    recommendation_clicks INTEGER DEFAULT 0,
    feed_impressions INTEGER DEFAULT 0,
    feed_clicks INTEGER DEFAULT 0,

    -- Conversion metrics
    views_to_rsvp_rate FLOAT DEFAULT 0,
    discovery_to_rsvp_count INTEGER DEFAULT 0,

    -- Engagement metrics
    average_view_duration_seconds INTEGER DEFAULT 0,
    share_count INTEGER DEFAULT 0,
    save_count INTEGER DEFAULT 0,

    UNIQUE(activity_id, metric_date),
    INDEX idx_activity_metrics_date (metric_date DESC),
    INDEX idx_activity_metrics_performance (search_clicks + recommendation_clicks + feed_clicks DESC)
);

CREATE TABLE user_discovery_patterns (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    pattern_date DATE NOT NULL DEFAULT CURRENT_DATE,
    
    -- Usage patterns
    search_queries_count INTEGER DEFAULT 0,
    recommendations_viewed INTEGER DEFAULT 0,
    feed_sessions INTEGER DEFAULT 0,
    
    -- Preference patterns
    preferred_discovery_method VARCHAR(20), -- search, recommendations, feed, social
    most_active_time_of_day INTEGER, -- Hour of day (0-23)
    average_session_duration_minutes INTEGER DEFAULT 0,
    
    -- Engagement patterns
    click_through_rate FLOAT DEFAULT 0,
    conversion_rate FLOAT DEFAULT 0,
    
    UNIQUE(user_id, pattern_date),
    INDEX idx_user_patterns_date (pattern_date DESC),
    INDEX idx_user_patterns_engagement (click_through_rate DESC, conversion_rate DESC)
);
```

## Database Functions for Discovery

### Search Ranking Function
```sql
CREATE OR REPLACE FUNCTION calculate_search_relevance(
    activity_record activities,
    search_query TEXT,
    user_location GEOGRAPHY DEFAULT NULL,
    user_interests TEXT[] DEFAULT NULL
)
RETURNS FLOAT AS $$
DECLARE
    relevance_score FLOAT := 0;
    text_score FLOAT;
    location_score FLOAT := 0;
    interest_score FLOAT := 0;
    popularity_score FLOAT;
    time_score FLOAT;
BEGIN
    -- Text relevance (40% weight)
    SELECT ts_rank(
        to_tsvector('english', activity_record.title || ' ' || COALESCE(activity_record.description, '')),
        plainto_tsquery('english', search_query)
    ) INTO text_score;
    relevance_score := relevance_score + (text_score * 0.4);
    
    -- Location proximity (25% weight)
    IF user_location IS NOT NULL AND activity_record.location_coordinates IS NOT NULL THEN
        location_score := 1.0 - LEAST(1.0, ST_Distance(user_location, activity_record.location_coordinates) / 50000); -- 50km max
        relevance_score := relevance_score + (location_score * 0.25);
    END IF;
    
    -- Interest matching (20% weight)
    IF user_interests IS NOT NULL AND array_length(user_interests, 1) > 0 THEN
        SELECT COUNT(*) * 1.0 / array_length(user_interests, 1) INTO interest_score
        FROM unnest(user_interests) AS user_interest
        WHERE user_interest = ANY(
            SELECT t.name FROM activity_tags at 
            JOIN tags t ON at.tag_id = t.id 
            WHERE at.activity_id = activity_record.id
        );
        relevance_score := relevance_score + (interest_score * 0.2);
    END IF;
    
    -- Popularity score (10% weight)
    popularity_score := LEAST(1.0, activity_record.rsvp_count * 1.0 / GREATEST(1, activity_record.capacity));
    relevance_score := relevance_score + (popularity_score * 0.1);
    
    -- Time relevance (5% weight) - prefer activities starting soon but not too soon
    time_score := CASE 
        WHEN activity_record.start_time < NOW() + INTERVAL '2 hours' THEN 0.1
        WHEN activity_record.start_time < NOW() + INTERVAL '1 day' THEN 1.0
        WHEN activity_record.start_time < NOW() + INTERVAL '1 week' THEN 0.8
        WHEN activity_record.start_time < NOW() + INTERVAL '1 month' THEN 0.6
        ELSE 0.3
    END;
    relevance_score := relevance_score + (time_score * 0.05);
    
    RETURN GREATEST(0, LEAST(1, relevance_score));
END;
$$ LANGUAGE plpgsql;
```

### Post Discovery Scoring Function (NEW)
```sql
-- Temporal decay scoring for posts
CREATE OR REPLACE FUNCTION calculate_post_discovery_score(
    p_user_id UUID,
    p_post_id UUID
)
RETURNS TABLE(
    score FLOAT,
    reasoning JSONB
) AS $$
DECLARE
    user_profile RECORD;
    post_record RECORD;
    final_score FLOAT := 0;
    score_breakdown JSONB := '{}';
    recency_score FLOAT := 0;
    location_score FLOAT := 0;
    interest_score FLOAT := 0;
    social_score FLOAT := 0;
    hours_since_creation FLOAT;
BEGIN
    -- Get user and post data
    SELECT * INTO user_profile FROM users WHERE id = p_user_id;
    SELECT * INTO post_record FROM posts WHERE id = p_post_id;

    -- Calculate hours since creation
    hours_since_creation := EXTRACT(EPOCH FROM (NOW() - post_record.created_at)) / 3600;

    -- Temporal decay (40% weight) - CRITICAL for posts
    -- Formula: 1 / (1 + hours_since_creation)
    recency_score := 1.0 / (1.0 + hours_since_creation);
    final_score := final_score + (recency_score * 0.40);
    score_breakdown := jsonb_set(score_breakdown, '{recency_score}', to_jsonb(recency_score));
    score_breakdown := jsonb_set(score_breakdown, '{hours_old}', to_jsonb(hours_since_creation));

    -- Location proximity (30% weight)
    IF user_profile.location_coordinates IS NOT NULL AND post_record.location_coordinates IS NOT NULL THEN
        location_score := 1.0 - LEAST(1.0, ST_Distance(user_profile.location_coordinates, post_record.location_coordinates) / 8000); -- 8km max for posts
        final_score := final_score + (location_score * 0.30);
    END IF;
    score_breakdown := jsonb_set(score_breakdown, '{location_score}', to_jsonb(location_score));

    -- Interest matching (20% weight)
    IF user_profile.interests IS NOT NULL AND array_length(user_profile.interests, 1) > 0 AND post_record.tags IS NOT NULL THEN
        SELECT COUNT(*) * 1.0 / array_length(user_profile.interests, 1) INTO interest_score
        FROM unnest(user_profile.interests) AS user_interest
        WHERE user_interest = ANY(post_record.tags);
    END IF;
    final_score := final_score + (interest_score * 0.20);
    score_breakdown := jsonb_set(score_breakdown, '{interest_score}', to_jsonb(interest_score));

    -- Social boost (10% weight) - friends who reacted
    SELECT COUNT(*) * 0.1 INTO social_score
    FROM post_reactions pr
    JOIN follows f ON pr.user_id = f.following_id
    WHERE pr.post_id = p_post_id
    AND f.follower_id = p_user_id;

    final_score := final_score + (social_score * 0.10);
    score_breakdown := jsonb_set(score_breakdown, '{social_score}', to_jsonb(social_score));

    -- Return final score and breakdown
    RETURN QUERY SELECT GREATEST(0, LEAST(1, final_score)), score_breakdown;
END;
$$ LANGUAGE plpgsql;
```

### Event Recommendation Scoring Function
```sql
CREATE OR REPLACE FUNCTION calculate_recommendation_score(
    p_user_id UUID,
    p_activity_id UUID
)
RETURNS TABLE(
    score FLOAT,
    reasoning JSONB
) AS $$
DECLARE
    user_profile RECORD;
    activity_record RECORD;
    final_score FLOAT := 0;
    score_breakdown JSONB := '{}';
    interest_score FLOAT := 0;
    social_score FLOAT := 0;
    location_score FLOAT := 0;
    behavior_score FLOAT := 0;
BEGIN
    -- Get user and activity data
    SELECT * INTO user_profile FROM users WHERE id = p_user_id;
    SELECT * INTO activity_record FROM activities WHERE id = p_activity_id;

    -- Interest matching (35% weight)
    IF user_profile.interests IS NOT NULL AND array_length(user_profile.interests, 1) > 0 THEN
        SELECT COUNT(*) * 1.0 / array_length(user_profile.interests, 1) INTO interest_score
        FROM unnest(user_profile.interests) AS user_interest
        WHERE user_interest = ANY(
            SELECT t.name FROM activity_tags at
            JOIN tags t ON at.tag_id = t.id
            WHERE at.activity_id = p_activity_id
        );
    END IF;
    final_score := final_score + (interest_score * 0.35);
    score_breakdown := jsonb_set(score_breakdown, '{interest_score}', to_jsonb(interest_score));

    -- Social signals (30% weight)
    SELECT COUNT(*) * 1.0 / GREATEST(1, (SELECT following_count FROM users WHERE id = p_user_id)) INTO social_score
    FROM follows f
    WHERE f.follower_id = p_user_id
    AND f.following_id = activity_record.host_id;

    -- Add social boost for activities with RSVPs from followed users
    social_score := social_score + (
        SELECT COUNT(*) * 0.1 FROM rsvps r
        JOIN follows f ON r.user_id = f.following_id
        WHERE r.activity_id = p_activity_id
        AND f.follower_id = p_user_id
        AND r.status = 'confirmed'
    );

    final_score := final_score + (social_score * 0.30);
    score_breakdown := jsonb_set(score_breakdown, '{social_score}', to_jsonb(social_score));

    -- Location proximity (20% weight)
    IF user_profile.location_coordinates IS NOT NULL AND activity_record.location_coordinates IS NOT NULL THEN
        location_score := 1.0 - LEAST(1.0, ST_Distance(user_profile.location_coordinates, activity_record.location_coordinates) / 25000); -- 25km max
        final_score := final_score + (location_score * 0.20);
    END IF;
    score_breakdown := jsonb_set(score_breakdown, '{location_score}', to_jsonb(location_score));
    
    -- Behavioral patterns (15% weight)
    SELECT COALESCE(AVG(
        CASE uai.interaction_type
            WHEN 'rsvp' THEN 1.0
            WHEN 'click' THEN 0.5
            WHEN 'view' THEN 0.2
            ELSE 0.1
        END
    ), 0) INTO behavior_score
    FROM user_activity_interactions uai
    JOIN activities a ON uai.activity_id = a.id
    JOIN activity_tags at ON a.id = at.activity_id
    JOIN activity_tags at2 ON at2.activity_id = p_activity_id
    WHERE uai.user_id = p_user_id
    AND at.tag_id = at2.tag_id
    AND uai.interaction_time > NOW() - INTERVAL '30 days';
    
    final_score := final_score + (behavior_score * 0.15);
    score_breakdown := jsonb_set(score_breakdown, '{behavior_score}', to_jsonb(behavior_score));
    
    -- Normalize final score
    final_score := GREATEST(0, LEAST(1, final_score));
    
    RETURN QUERY SELECT final_score, score_breakdown;
END;
$$ LANGUAGE plpgsql;
```

## Query Patterns for Discovery

### Advanced Search Query
```sql
-- Complex activity search with multiple filters and ranking
WITH search_results AS (
    SELECT 
        a.*,
        calculate_search_relevance(a, $1, $2::geography, $3::text[]) as relevance_score,
        u.username as host_username,
        u.display_name as host_display_name,
        u.is_verified as host_is_verified,
        array_agg(DISTINCT t.name) as tags
    FROM activities a
    JOIN users u ON a.host_id = u.id
    LEFT JOIN activity_tags at ON a.id = at.activity_id
    LEFT JOIN tags t ON at.tag_id = t.id
    WHERE a.status = 'published'
    AND a.start_time > NOW()
    AND ($4::text IS NULL OR to_tsvector('english', a.title || ' ' || COALESCE(a.description, '')) @@ plainto_tsquery('english', $4))
    AND ($5::geography IS NULL OR ST_DWithin(a.location_coordinates, $5, $6))
    AND ($7::integer IS NULL OR a.price_cents <= $7)
    AND ($8::text[] IS NULL OR EXISTS (
        SELECT 1 FROM activity_tags at2 
        JOIN tags t2 ON at2.tag_id = t2.id 
        WHERE at2.activity_id = a.id AND t2.name = ANY($8)
    ))
    GROUP BY a.id, u.username, u.display_name, u.is_verified
)
SELECT * FROM search_results
WHERE relevance_score > 0.1
ORDER BY relevance_score DESC, start_time ASC
LIMIT $9 OFFSET $10;
```

---

**Database Schema Status**: ✅ Complete - Search optimization and recommendation data structures defined
**Next Steps**: Define service architecture for search, recommendations, and feed generation
