# E02 User & Profile Management - Database Schema

## Schema Overview

This document details the database schema requirements specific to User & Profile Management. The core `users` and `follows` tables were defined in E01 Core Infrastructure. This epic focuses on schema optimizations, additional tables, and specific queries needed for profile and social graph functionality.

## Core Tables Review

### Users Table (from E01)
The users table from E01 Core Infrastructure provides the foundation:

```sql
-- Core user data (defined in E01)
CREATE TABLE users (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    email VARCHAR(255) UNIQUE NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    display_name VARCHAR(100) NOT NULL,
    bio TEXT,
    profile_image_url TEXT,
    location_name VARCHAR(255),
    location_coordinates GEOGRAPHY(POINT, 4326),
    interests TEXT[],
    is_host BOOLEAN DEFAULT FALSE,
    stripe_account_id VARCHAR(255),
    stripe_onboarding_complete BOOLEAN DEFAULT FALSE,
    follower_count INTEGER DEFAULT 0,
    following_count INTEGER DEFAULT 0,
    activity_count INTEGER DEFAULT 0,
    is_verified BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    privacy_level VARCHAR(20) DEFAULT 'public',
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);
```

### Follows Table (from E01)
The social graph foundation:

```sql
-- Social graph relationships (defined in E01)
CREATE TABLE follows (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    follower_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    following_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    
    UNIQUE(follower_id, following_id),
    CONSTRAINT no_self_follow CHECK (follower_id != following_id)
);
```

## Additional Tables for E02

### User Profile Images Table
```sql
CREATE TABLE user_profile_images (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    image_url TEXT NOT NULL,
    image_type VARCHAR(20) NOT NULL, -- profile, cover, gallery
    file_size INTEGER NOT NULL,
    width INTEGER NOT NULL,
    height INTEGER NOT NULL,
    is_primary BOOLEAN DEFAULT FALSE,
    upload_status VARCHAR(20) DEFAULT 'processing', -- processing, ready, failed
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    
    -- Ensure only one primary profile image
    CONSTRAINT one_primary_profile_image 
        EXCLUDE (user_id WITH =) WHERE (is_primary = TRUE AND image_type = 'profile')
);

-- Indexes
CREATE INDEX idx_user_profile_images_user ON user_profile_images(user_id);
CREATE INDEX idx_user_profile_images_type ON user_profile_images(image_type);
CREATE INDEX idx_user_profile_images_primary ON user_profile_images(is_primary) WHERE is_primary = TRUE;
```

### User Preferences Table
```sql
CREATE TABLE user_preferences (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    
    -- Privacy preferences
    profile_visibility VARCHAR(20) DEFAULT 'public', -- public, followers, private
    location_visibility VARCHAR(20) DEFAULT 'city', -- exact, city, hidden
    activity_visibility VARCHAR(20) DEFAULT 'public', -- public, followers, private
    follower_list_visibility VARCHAR(20) DEFAULT 'public', -- public, followers, private
    following_list_visibility VARCHAR(20) DEFAULT 'public', -- public, followers, private
    
    -- Discovery preferences
    discoverable_by_location BOOLEAN DEFAULT TRUE,
    discoverable_by_interests BOOLEAN DEFAULT TRUE,
    show_in_suggestions BOOLEAN DEFAULT TRUE,
    
    -- Notification preferences (detailed)
    notify_new_followers BOOLEAN DEFAULT TRUE,
    notify_activity_invites BOOLEAN DEFAULT TRUE,
    notify_activity_updates BOOLEAN DEFAULT TRUE,
    notify_comments BOOLEAN DEFAULT TRUE,
    notify_mentions BOOLEAN DEFAULT TRUE,
    
    -- Communication preferences
    email_notifications BOOLEAN DEFAULT TRUE,
    push_notifications BOOLEAN DEFAULT TRUE,
    sms_notifications BOOLEAN DEFAULT FALSE,
    
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    
    UNIQUE(user_id)
);

-- Indexes
CREATE INDEX idx_user_preferences_user ON user_preferences(user_id);
CREATE INDEX idx_user_preferences_discoverable ON user_preferences(discoverable_by_location, discoverable_by_interests);
```

### User Blocks Table
```sql
CREATE TABLE user_blocks (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    blocker_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    blocked_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    reason VARCHAR(50), -- harassment, spam, inappropriate, other
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    
    UNIQUE(blocker_id, blocked_id),
    CONSTRAINT no_self_block CHECK (blocker_id != blocked_id)
);

-- Indexes
CREATE INDEX idx_user_blocks_blocker ON user_blocks(blocker_id);
CREATE INDEX idx_user_blocks_blocked ON user_blocks(blocked_id);
```

### Follow Requests Table (for future private accounts)
```sql
CREATE TABLE follow_requests (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    requester_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    requested_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    status VARCHAR(20) DEFAULT 'pending', -- pending, accepted, rejected
    message TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    
    UNIQUE(requester_id, requested_id),
    CONSTRAINT no_self_request CHECK (requester_id != requested_id)
);

-- Indexes
CREATE INDEX idx_follow_requests_requester ON follow_requests(requester_id);
CREATE INDEX idx_follow_requests_requested ON follow_requests(requested_id);
CREATE INDEX idx_follow_requests_status ON follow_requests(status);
```

## Enhanced Indexes for Profile & Social Features

### User Discovery Indexes
```sql
-- Optimize location-based user discovery
CREATE INDEX idx_users_location_discoverable ON users 
    USING GIST(location_coordinates) 
    WHERE is_active = TRUE AND privacy_level = 'public';

-- Optimize interest-based discovery
CREATE INDEX idx_users_interests_gin ON users USING GIN(interests) 
    WHERE is_active = TRUE;

-- Optimize username search
CREATE INDEX idx_users_username_search ON users(username text_pattern_ops);
CREATE INDEX idx_users_display_name_search ON users(display_name text_pattern_ops);
```

### Social Graph Optimization Indexes
```sql
-- Optimize follower/following queries
CREATE INDEX idx_follows_follower_created ON follows(follower_id, created_at DESC);
CREATE INDEX idx_follows_following_created ON follows(following_id, created_at DESC);

-- Optimize mutual follow detection
CREATE INDEX idx_follows_mutual ON follows(follower_id, following_id);
```

## Row Level Security Policies for E02

### User Profile Images Policies
```sql
-- Users can view images for profiles they can access
CREATE POLICY "Profile images viewable based on profile access" ON user_profile_images
    FOR SELECT USING (
        EXISTS (
            SELECT 1 FROM users 
            WHERE id = user_id 
            AND (privacy_level = 'public' OR id = auth.uid())
        )
    );

-- Users can manage their own profile images
CREATE POLICY "Users can manage own profile images" ON user_profile_images
    FOR ALL USING (user_id = auth.uid());
```

### User Preferences Policies
```sql
-- Users can only access their own preferences
CREATE POLICY "Users can access own preferences" ON user_preferences
    FOR ALL USING (user_id = auth.uid());
```

### User Blocks Policies
```sql
-- Users can view their own blocks
CREATE POLICY "Users can view own blocks" ON user_blocks
    FOR SELECT USING (blocker_id = auth.uid());

-- Users can manage their own blocks
CREATE POLICY "Users can manage own blocks" ON user_blocks
    FOR INSERT WITH CHECK (blocker_id = auth.uid());

CREATE POLICY "Users can delete own blocks" ON user_blocks
    FOR DELETE USING (blocker_id = auth.uid());
```

## Database Functions for E02

### Profile Completion Function
```sql
CREATE OR REPLACE FUNCTION calculate_profile_completion(user_uuid UUID)
RETURNS INTEGER AS $$
DECLARE
    completion_score INTEGER := 0;
    user_record RECORD;
BEGIN
    SELECT * INTO user_record FROM users WHERE id = user_uuid;
    
    IF user_record IS NULL THEN
        RETURN 0;
    END IF;
    
    -- Basic info (40 points)
    IF user_record.display_name IS NOT NULL AND LENGTH(user_record.display_name) > 0 THEN
        completion_score := completion_score + 10;
    END IF;
    
    IF user_record.bio IS NOT NULL AND LENGTH(user_record.bio) > 10 THEN
        completion_score := completion_score + 15;
    END IF;
    
    IF user_record.location_name IS NOT NULL THEN
        completion_score := completion_score + 15;
    END IF;
    
    -- Profile image (20 points)
    IF user_record.profile_image_url IS NOT NULL THEN
        completion_score := completion_score + 20;
    END IF;
    
    -- Interests (20 points)
    IF array_length(user_record.interests, 1) >= 3 THEN
        completion_score := completion_score + 20;
    ELSIF array_length(user_record.interests, 1) >= 1 THEN
        completion_score := completion_score + 10;
    END IF;
    
    -- Social connections (20 points)
    IF user_record.follower_count > 0 THEN
        completion_score := completion_score + 10;
    END IF;
    
    IF user_record.following_count > 0 THEN
        completion_score := completion_score + 10;
    END IF;
    
    RETURN completion_score;
END;
$$ LANGUAGE plpgsql;
```

### Mutual Follow Detection Function
```sql
CREATE OR REPLACE FUNCTION are_mutual_followers(user1_uuid UUID, user2_uuid UUID)
RETURNS BOOLEAN AS $$
BEGIN
    RETURN EXISTS (
        SELECT 1 FROM follows 
        WHERE follower_id = user1_uuid AND following_id = user2_uuid
    ) AND EXISTS (
        SELECT 1 FROM follows 
        WHERE follower_id = user2_uuid AND following_id = user1_uuid
    );
END;
$$ LANGUAGE plpgsql;
```

### Follow Recommendation Function
```sql
CREATE OR REPLACE FUNCTION get_follow_recommendations(user_uuid UUID, limit_count INTEGER DEFAULT 10)
RETURNS TABLE(
    recommended_user_id UUID,
    mutual_connections INTEGER,
    shared_interests INTEGER,
    distance_km FLOAT
) AS $$
BEGIN
    RETURN QUERY
    WITH user_following AS (
        SELECT following_id FROM follows WHERE follower_id = user_uuid
    ),
    user_data AS (
        SELECT location_coordinates, interests FROM users WHERE id = user_uuid
    ),
    mutual_counts AS (
        SELECT 
            u.id as recommended_user_id,
            COUNT(f.following_id) as mutual_connections,
            array_length(array(
                SELECT unnest(u.interests) 
                INTERSECT 
                SELECT unnest(ud.interests)
            ), 1) as shared_interests,
            CASE 
                WHEN u.location_coordinates IS NOT NULL AND ud.location_coordinates IS NOT NULL
                THEN ST_Distance(u.location_coordinates, ud.location_coordinates) / 1000
                ELSE NULL
            END as distance_km
        FROM users u
        CROSS JOIN user_data ud
        LEFT JOIN follows f ON f.follower_id IN (SELECT following_id FROM user_following) 
                            AND f.following_id = u.id
        WHERE u.id != user_uuid
        AND u.id NOT IN (SELECT following_id FROM user_following)
        AND u.is_active = TRUE
        AND u.privacy_level = 'public'
        GROUP BY u.id, u.interests, u.location_coordinates, ud.interests, ud.location_coordinates
    )
    SELECT 
        mc.recommended_user_id,
        mc.mutual_connections,
        COALESCE(mc.shared_interests, 0) as shared_interests,
        mc.distance_km
    FROM mutual_counts mc
    ORDER BY 
        mc.mutual_connections DESC,
        COALESCE(mc.shared_interests, 0) DESC,
        COALESCE(mc.distance_km, 999999) ASC
    LIMIT limit_count;
END;
$$ LANGUAGE plpgsql;
```

## Query Patterns for E02

### Common Profile Queries
```sql
-- Get complete user profile with preferences
SELECT 
    u.*,
    up.profile_visibility,
    up.location_visibility,
    calculate_profile_completion(u.id) as completion_percentage
FROM users u
LEFT JOIN user_preferences up ON u.id = up.user_id
WHERE u.id = $1;

-- Search users by username/display name
SELECT id, username, display_name, profile_image_url, follower_count
FROM users
WHERE (username ILIKE $1 OR display_name ILIKE $1)
AND is_active = TRUE
AND privacy_level = 'public'
ORDER BY follower_count DESC
LIMIT 20;
```

### Common Social Graph Queries
```sql
-- Get followers with pagination
SELECT 
    u.id, u.username, u.display_name, u.profile_image_url,
    f.created_at as followed_at
FROM follows f
JOIN users u ON f.follower_id = u.id
WHERE f.following_id = $1
ORDER BY f.created_at DESC
LIMIT $2 OFFSET $3;

-- Check if user A follows user B
SELECT EXISTS(
    SELECT 1 FROM follows 
    WHERE follower_id = $1 AND following_id = $2
) as is_following;
```

---

**Database Schema Status**: ✅ Complete - Additional tables and optimizations defined
**Next Steps**: Define service architecture for profile and social graph services
