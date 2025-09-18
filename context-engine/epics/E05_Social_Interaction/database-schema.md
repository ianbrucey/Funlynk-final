# E05 Social Interaction - Database Schema

## Schema Overview

This document details the database schema extensions for social interaction features. While core tables exist from previous epics, this epic adds tables for comments, reactions, sharing, communities, and real-time social features.

## Comment & Discussion System Schema

### Comments Table Enhancement
```sql
-- Extend existing comments table with advanced features
ALTER TABLE comments ADD COLUMN IF NOT EXISTS parent_comment_id UUID REFERENCES comments(id);
ALTER TABLE comments ADD COLUMN IF NOT EXISTS thread_depth INTEGER DEFAULT 0;
ALTER TABLE comments ADD COLUMN IF NOT EXISTS is_edited BOOLEAN DEFAULT FALSE;
ALTER TABLE comments ADD COLUMN IF NOT EXISTS edit_history JSONB DEFAULT '[]';
ALTER TABLE comments ADD COLUMN IF NOT EXISTS mentions JSONB DEFAULT '[]'; -- Array of mentioned user IDs
ALTER TABLE comments ADD COLUMN IF NOT EXISTS attachments JSONB DEFAULT '[]'; -- Media attachments
ALTER TABLE comments ADD COLUMN IF NOT EXISTS is_pinned BOOLEAN DEFAULT FALSE;
ALTER TABLE comments ADD COLUMN IF NOT EXISTS is_hidden BOOLEAN DEFAULT FALSE;
ALTER TABLE comments ADD COLUMN IF NOT EXISTS moderation_status VARCHAR(20) DEFAULT 'approved';

-- Add indexes for comment threading and performance
CREATE INDEX idx_comments_parent_thread ON comments(parent_comment_id, thread_depth);
CREATE INDEX idx_comments_activity_created ON comments(activity_id, created_at DESC);
CREATE INDEX idx_comments_user_created ON comments(user_id, created_at DESC);
CREATE INDEX idx_comments_mentions ON comments USING GIN(mentions);
CREATE INDEX idx_comments_moderation ON comments(moderation_status, created_at DESC);

-- Comment threading constraints
ALTER TABLE comments ADD CONSTRAINT check_thread_depth CHECK (thread_depth >= 0 AND thread_depth <= 10);
ALTER TABLE comments ADD CONSTRAINT check_parent_not_self CHECK (id != parent_comment_id);
```

### Comment Reactions Table
```sql
CREATE TABLE comment_reactions (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    comment_id UUID NOT NULL REFERENCES comments(id) ON DELETE CASCADE,
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    reaction_type VARCHAR(20) NOT NULL, -- like, helpful, funny, insightful, disagree
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    
    UNIQUE(comment_id, user_id, reaction_type),
    INDEX idx_comment_reactions_comment (comment_id, reaction_type),
    INDEX idx_comment_reactions_user (user_id, created_at DESC)
);

-- Trigger to update comment reaction counts
CREATE OR REPLACE FUNCTION update_comment_reaction_counts()
RETURNS TRIGGER AS $$
BEGIN
    IF TG_OP = 'INSERT' THEN
        UPDATE comments 
        SET reaction_counts = COALESCE(reaction_counts, '{}'::jsonb) || 
            jsonb_build_object(NEW.reaction_type, 
                COALESCE((reaction_counts->NEW.reaction_type)::int, 0) + 1)
        WHERE id = NEW.comment_id;
        RETURN NEW;
    ELSIF TG_OP = 'DELETE' THEN
        UPDATE comments 
        SET reaction_counts = COALESCE(reaction_counts, '{}'::jsonb) || 
            jsonb_build_object(OLD.reaction_type, 
                GREATEST(0, COALESCE((reaction_counts->OLD.reaction_type)::int, 0) - 1))
        WHERE id = OLD.comment_id;
        RETURN OLD;
    END IF;
    RETURN NULL;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trigger_comment_reaction_counts
    AFTER INSERT OR DELETE ON comment_reactions
    FOR EACH ROW EXECUTE FUNCTION update_comment_reaction_counts();
```

## Social Sharing & Engagement Schema

### Activity Reactions Table
```sql
CREATE TABLE activity_reactions (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    activity_id UUID NOT NULL REFERENCES activities(id) ON DELETE CASCADE,
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    reaction_type VARCHAR(20) NOT NULL, -- like, love, excited, interested, going
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    
    UNIQUE(activity_id, user_id, reaction_type),
    INDEX idx_activity_reactions_activity (activity_id, reaction_type),
    INDEX idx_activity_reactions_user (user_id, created_at DESC),
    INDEX idx_activity_reactions_type_time (reaction_type, created_at DESC)
);

-- Trigger to update activity reaction counts
CREATE OR REPLACE FUNCTION update_activity_reaction_counts()
RETURNS TRIGGER AS $$
BEGIN
    IF TG_OP = 'INSERT' THEN
        UPDATE activities 
        SET reaction_counts = COALESCE(reaction_counts, '{}'::jsonb) || 
            jsonb_build_object(NEW.reaction_type, 
                COALESCE((reaction_counts->NEW.reaction_type)::int, 0) + 1)
        WHERE id = NEW.activity_id;
        RETURN NEW;
    ELSIF TG_OP = 'DELETE' THEN
        UPDATE activities 
        SET reaction_counts = COALESCE(reaction_counts, '{}'::jsonb) || 
            jsonb_build_object(OLD.reaction_type, 
                GREATEST(0, COALESCE((reaction_counts->OLD.reaction_type)::int, 0) - 1))
        WHERE id = OLD.activity_id;
        RETURN OLD;
    END IF;
    RETURN NULL;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trigger_activity_reaction_counts
    AFTER INSERT OR DELETE ON activity_reactions
    FOR EACH ROW EXECUTE FUNCTION update_activity_reaction_counts();
```

### Activity Shares Table
```sql
CREATE TABLE activity_shares (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    activity_id UUID NOT NULL REFERENCES activities(id) ON DELETE CASCADE,
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    share_type VARCHAR(20) NOT NULL, -- internal, instagram, twitter, facebook, link, message
    share_platform VARCHAR(50), -- Platform-specific identifier
    share_message TEXT,
    recipient_user_id UUID REFERENCES users(id), -- For internal shares
    share_url TEXT, -- Generated share URL
    click_count INTEGER DEFAULT 0,
    conversion_count INTEGER DEFAULT 0, -- RSVPs from this share
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    
    INDEX idx_activity_shares_activity (activity_id, created_at DESC),
    INDEX idx_activity_shares_user (user_id, created_at DESC),
    INDEX idx_activity_shares_type (share_type, created_at DESC),
    INDEX idx_activity_shares_performance (click_count DESC, conversion_count DESC)
);

-- Track share clicks and conversions
CREATE TABLE share_interactions (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    share_id UUID NOT NULL REFERENCES activity_shares(id) ON DELETE CASCADE,
    interaction_type VARCHAR(20) NOT NULL, -- click, view, rsvp
    user_id UUID REFERENCES users(id), -- May be null for anonymous clicks
    ip_address INET,
    user_agent TEXT,
    referrer TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    
    INDEX idx_share_interactions_share (share_id, created_at DESC),
    INDEX idx_share_interactions_type (interaction_type, created_at DESC)
);
```

### Saved Activities Table
```sql
CREATE TABLE saved_activities (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    activity_id UUID NOT NULL REFERENCES activities(id) ON DELETE CASCADE,
    save_note TEXT, -- Personal note about why they saved it
    save_category VARCHAR(50), -- User-defined category like "weekend plans", "date ideas"
    reminder_time TIMESTAMP WITH TIME ZONE, -- Optional reminder
    is_private BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    
    UNIQUE(user_id, activity_id),
    INDEX idx_saved_activities_user (user_id, created_at DESC),
    INDEX idx_saved_activities_category (user_id, save_category),
    INDEX idx_saved_activities_reminder (reminder_time) WHERE reminder_time IS NOT NULL
);
```

## Community Features Schema

### Communities Table
```sql
CREATE TABLE communities (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name VARCHAR(100) NOT NULL,
    description TEXT,
    community_type VARCHAR(20) NOT NULL, -- activity_based, interest_based, location_based
    source_activity_id UUID REFERENCES activities(id), -- For activity-based communities
    interest_tags TEXT[], -- For interest-based communities
    location_coordinates GEOGRAPHY(POINT, 4326), -- For location-based communities
    location_radius_km INTEGER, -- For location-based communities
    
    -- Community settings
    is_public BOOLEAN DEFAULT TRUE,
    requires_approval BOOLEAN DEFAULT FALSE,
    allow_member_posts BOOLEAN DEFAULT TRUE,
    allow_member_events BOOLEAN DEFAULT FALSE,
    
    -- Community metadata
    member_count INTEGER DEFAULT 0,
    activity_count INTEGER DEFAULT 0,
    created_by UUID NOT NULL REFERENCES users(id),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    
    -- Community image and branding
    cover_image_url TEXT,
    icon_image_url TEXT,
    theme_color VARCHAR(7), -- Hex color code
    
    INDEX idx_communities_type (community_type),
    INDEX idx_communities_location (location_coordinates) WHERE location_coordinates IS NOT NULL,
    INDEX idx_communities_tags (interest_tags) WHERE interest_tags IS NOT NULL,
    INDEX idx_communities_member_count (member_count DESC),
    INDEX idx_communities_created (created_at DESC)
);

-- Community membership
CREATE TABLE community_members (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    community_id UUID NOT NULL REFERENCES communities(id) ON DELETE CASCADE,
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    role VARCHAR(20) NOT NULL DEFAULT 'member', -- admin, moderator, member
    join_status VARCHAR(20) NOT NULL DEFAULT 'active', -- active, pending, banned
    joined_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    last_active_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    
    UNIQUE(community_id, user_id),
    INDEX idx_community_members_community (community_id, role),
    INDEX idx_community_members_user (user_id, joined_at DESC),
    INDEX idx_community_members_active (last_active_at DESC)
);

-- Community posts/discussions
CREATE TABLE community_posts (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    community_id UUID NOT NULL REFERENCES communities(id) ON DELETE CASCADE,
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    post_type VARCHAR(20) NOT NULL DEFAULT 'discussion', -- discussion, announcement, event, poll
    title VARCHAR(200) NOT NULL,
    content TEXT,
    attachments JSONB DEFAULT '[]',
    
    -- Post metadata
    is_pinned BOOLEAN DEFAULT FALSE,
    is_locked BOOLEAN DEFAULT FALSE,
    comment_count INTEGER DEFAULT 0,
    reaction_count INTEGER DEFAULT 0,
    view_count INTEGER DEFAULT 0,
    
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    
    INDEX idx_community_posts_community (community_id, created_at DESC),
    INDEX idx_community_posts_user (user_id, created_at DESC),
    INDEX idx_community_posts_type (post_type, created_at DESC),
    INDEX idx_community_posts_pinned (community_id, is_pinned, created_at DESC)
);
```

## Real-time Social Features Schema

### Real-time Chat Messages
```sql
CREATE TABLE chat_messages (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    chat_room_id VARCHAR(100) NOT NULL, -- activity:uuid, community:uuid, direct:uuid1:uuid2
    chat_room_type VARCHAR(20) NOT NULL, -- activity, community, direct
    sender_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    message_type VARCHAR(20) NOT NULL DEFAULT 'text', -- text, image, emoji, system
    content TEXT NOT NULL,
    attachments JSONB DEFAULT '[]',
    
    -- Message metadata
    reply_to_message_id UUID REFERENCES chat_messages(id),
    is_edited BOOLEAN DEFAULT FALSE,
    is_deleted BOOLEAN DEFAULT FALSE,
    read_by JSONB DEFAULT '{}', -- {user_id: timestamp} for read receipts
    
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    
    INDEX idx_chat_messages_room (chat_room_id, created_at DESC),
    INDEX idx_chat_messages_sender (sender_id, created_at DESC),
    INDEX idx_chat_messages_type (chat_room_type, created_at DESC)
);

-- Partition chat messages by month for performance
CREATE TABLE chat_messages_y2025m09 PARTITION OF chat_messages
    FOR VALUES FROM ('2025-09-01') TO ('2025-10-01');
```

### User Presence and Status
```sql
CREATE TABLE user_presence (
    user_id UUID PRIMARY KEY REFERENCES users(id) ON DELETE CASCADE,
    status VARCHAR(20) NOT NULL DEFAULT 'offline', -- online, away, busy, offline
    last_seen TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    current_activity_id UUID REFERENCES activities(id), -- If at an activity
    custom_status TEXT,
    is_available_for_chat BOOLEAN DEFAULT TRUE,
    
    -- Real-time connection info
    connection_count INTEGER DEFAULT 0,
    last_connection_id VARCHAR(100),
    
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    
    INDEX idx_user_presence_status (status, last_seen DESC),
    INDEX idx_user_presence_activity (current_activity_id) WHERE current_activity_id IS NOT NULL
);

-- Real-time notifications queue
CREATE TABLE real_time_notifications (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    notification_type VARCHAR(50) NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    data JSONB DEFAULT '{}',
    
    -- Delivery tracking
    is_delivered BOOLEAN DEFAULT FALSE,
    delivery_attempts INTEGER DEFAULT 0,
    delivered_at TIMESTAMP WITH TIME ZONE,
    
    -- Notification metadata
    priority VARCHAR(10) DEFAULT 'normal', -- high, normal, low
    expires_at TIMESTAMP WITH TIME ZONE,
    
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    
    INDEX idx_realtime_notifications_user (user_id, created_at DESC),
    INDEX idx_realtime_notifications_delivery (is_delivered, created_at),
    INDEX idx_realtime_notifications_expires (expires_at) WHERE expires_at IS NOT NULL
);
```

## Social Analytics Schema

### Social Engagement Metrics
```sql
CREATE TABLE social_engagement_metrics (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    metric_date DATE NOT NULL DEFAULT CURRENT_DATE,
    activity_id UUID REFERENCES activities(id) ON DELETE CASCADE,
    community_id UUID REFERENCES communities(id) ON DELETE CASCADE,
    
    -- Engagement metrics
    comment_count INTEGER DEFAULT 0,
    reaction_count INTEGER DEFAULT 0,
    share_count INTEGER DEFAULT 0,
    save_count INTEGER DEFAULT 0,
    
    -- Interaction metrics
    unique_commenters INTEGER DEFAULT 0,
    unique_reactors INTEGER DEFAULT 0,
    unique_sharers INTEGER DEFAULT 0,
    
    -- Viral metrics
    share_click_count INTEGER DEFAULT 0,
    share_conversion_count INTEGER DEFAULT 0,
    viral_coefficient FLOAT DEFAULT 0,
    
    -- Community metrics (for community_id entries)
    new_members INTEGER DEFAULT 0,
    active_members INTEGER DEFAULT 0,
    post_count INTEGER DEFAULT 0,
    
    UNIQUE(metric_date, activity_id) WHERE activity_id IS NOT NULL,
    UNIQUE(metric_date, community_id) WHERE community_id IS NOT NULL,
    INDEX idx_social_metrics_date (metric_date DESC),
    INDEX idx_social_metrics_activity (activity_id, metric_date DESC),
    INDEX idx_social_metrics_community (community_id, metric_date DESC)
);
```

## Database Functions for Social Features

### Comment Threading Function
```sql
CREATE OR REPLACE FUNCTION get_comment_thread(
    p_activity_id UUID,
    p_parent_comment_id UUID DEFAULT NULL,
    p_max_depth INTEGER DEFAULT 5
)
RETURNS TABLE(
    comment_id UUID,
    parent_id UUID,
    depth INTEGER,
    content TEXT,
    author_username VARCHAR,
    created_at TIMESTAMP WITH TIME ZONE,
    reaction_counts JSONB,
    child_count INTEGER
) AS $$
BEGIN
    RETURN QUERY
    WITH RECURSIVE comment_tree AS (
        -- Base case: top-level comments or specific parent
        SELECT 
            c.id,
            c.parent_comment_id,
            c.thread_depth,
            c.content,
            u.username,
            c.created_at,
            c.reaction_counts,
            0 as level
        FROM comments c
        JOIN users u ON c.user_id = u.id
        WHERE c.activity_id = p_activity_id
        AND c.parent_comment_id = p_parent_comment_id
        AND c.is_hidden = FALSE
        
        UNION ALL
        
        -- Recursive case: child comments
        SELECT 
            c.id,
            c.parent_comment_id,
            c.thread_depth,
            c.content,
            u.username,
            c.created_at,
            c.reaction_counts,
            ct.level + 1
        FROM comments c
        JOIN users u ON c.user_id = u.id
        JOIN comment_tree ct ON c.parent_comment_id = ct.comment_id
        WHERE ct.level < p_max_depth
        AND c.is_hidden = FALSE
    )
    SELECT 
        ct.comment_id,
        ct.parent_comment_id,
        ct.thread_depth,
        ct.content,
        ct.username,
        ct.created_at,
        ct.reaction_counts,
        (SELECT COUNT(*)::INTEGER FROM comments WHERE parent_comment_id = ct.comment_id) as child_count
    FROM comment_tree ct
    ORDER BY ct.level, ct.created_at;
END;
$$ LANGUAGE plpgsql;
```

### Social Proof Calculation
```sql
CREATE OR REPLACE FUNCTION calculate_social_proof(
    p_activity_id UUID,
    p_user_id UUID
)
RETURNS JSONB AS $$
DECLARE
    social_proof JSONB := '{}';
    friends_attending INTEGER;
    friends_interested INTEGER;
    friends_shared INTEGER;
    total_social_signals INTEGER;
BEGIN
    -- Count friends who RSVP'd
    SELECT COUNT(*) INTO friends_attending
    FROM rsvps r
    JOIN follows f ON r.user_id = f.following_id
    WHERE r.activity_id = p_activity_id
    AND f.follower_id = p_user_id
    AND r.status = 'confirmed';
    
    -- Count friends who reacted with interest
    SELECT COUNT(*) INTO friends_interested
    FROM activity_reactions ar
    JOIN follows f ON ar.user_id = f.following_id
    WHERE ar.activity_id = p_activity_id
    AND f.follower_id = p_user_id
    AND ar.reaction_type IN ('interested', 'excited');
    
    -- Count friends who shared
    SELECT COUNT(*) INTO friends_shared
    FROM activity_shares ash
    JOIN follows f ON ash.user_id = f.following_id
    WHERE ash.activity_id = p_activity_id
    AND f.follower_id = p_user_id;
    
    total_social_signals := friends_attending + friends_interested + friends_shared;
    
    social_proof := jsonb_build_object(
        'friends_attending', friends_attending,
        'friends_interested', friends_interested,
        'friends_shared', friends_shared,
        'total_social_signals', total_social_signals,
        'social_proof_score', LEAST(1.0, total_social_signals * 0.1)
    );
    
    RETURN social_proof;
END;
$$ LANGUAGE plpgsql;
```

---

**Database Schema Status**: ✅ Complete - Social interaction data structures defined
**Next Steps**: Define service architecture for social features
