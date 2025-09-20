-- Enable PostGIS extension for geospatial queries
CREATE EXTENSION IF NOT EXISTS postgis;

-- Enable UUID extensions
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS pgcrypto;

-- Core Users Table
CREATE TABLE users (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    email VARCHAR(255) UNIQUE NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    display_name VARCHAR(100) NOT NULL,
    bio TEXT,
    profile_image_url TEXT,
    location_name VARCHAR(255), -- Human-readable location
    location_coordinates GEOGRAPHY(POINT, 4326), -- PostGIS point
    interests TEXT[], -- Array of interest tags
    is_host BOOLEAN DEFAULT FALSE, -- Can create paid activities
    stripe_account_id VARCHAR(255), -- Stripe Connect account
    stripe_onboarding_complete BOOLEAN DEFAULT FALSE,
    follower_count INTEGER DEFAULT 0,
    following_count INTEGER DEFAULT 0,
    activity_count INTEGER DEFAULT 0,
    is_verified BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    privacy_level VARCHAR(20) DEFAULT 'public', -- public, friends, private
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Indexes for users table
CREATE INDEX idx_users_username ON users(username);
CREATE INDEX idx_users_location ON users USING GIST(location_coordinates);
CREATE INDEX idx_users_stripe_account ON users(stripe_account_id);
CREATE INDEX idx_users_is_host ON users(is_host) WHERE is_host = TRUE;

-- Activities Table
CREATE TABLE activities (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    host_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    activity_type VARCHAR(50) NOT NULL, -- sports, music, social, etc.
    location_name VARCHAR(255) NOT NULL,
    location_coordinates GEOGRAPHY(POINT, 4326) NOT NULL,
    start_time TIMESTAMP WITH TIME ZONE NOT NULL,
    end_time TIMESTAMP WITH TIME ZONE,
    max_attendees INTEGER, -- NULL for unlimited
    current_attendees INTEGER DEFAULT 0,
    is_paid BOOLEAN DEFAULT FALSE,
    price_cents INTEGER, -- Price in cents, NULL for free
    currency VARCHAR(3) DEFAULT 'USD',
    stripe_price_id VARCHAR(255), -- Stripe Price object ID
    is_public BOOLEAN DEFAULT TRUE,
    requires_approval BOOLEAN DEFAULT FALSE,
    tags TEXT[] NOT NULL DEFAULT '{}',
    images TEXT[], -- Array of image URLs
    status VARCHAR(20) DEFAULT 'active', -- active, cancelled, completed
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    
    -- Constraints
    CONSTRAINT valid_price CHECK (
        (is_paid = FALSE AND price_cents IS NULL) OR 
        (is_paid = TRUE AND price_cents > 0)
    ),
    CONSTRAINT valid_times CHECK (end_time IS NULL OR end_time > start_time),
    CONSTRAINT valid_attendees CHECK (max_attendees IS NULL OR max_attendees > 0)
);

-- Indexes for activities table
CREATE INDEX idx_activities_host ON activities(host_id);
CREATE INDEX idx_activities_location ON activities USING GIST(location_coordinates);
CREATE INDEX idx_activities_start_time ON activities(start_time);
CREATE INDEX idx_activities_tags ON activities USING GIN(tags);
CREATE INDEX idx_activities_type ON activities(activity_type);
CREATE INDEX idx_activities_status ON activities(status);
CREATE INDEX idx_activities_is_paid ON activities(is_paid);

-- Follows Table (Social Graph)
CREATE TABLE follows (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    follower_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    following_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    
    -- Constraints
    UNIQUE(follower_id, following_id),
    CONSTRAINT no_self_follow CHECK (follower_id != following_id)
);

-- Indexes for follows table
CREATE INDEX idx_follows_follower ON follows(follower_id);
CREATE INDEX idx_follows_following ON follows(following_id);

-- RSVPs Table (Activity Attendance)
CREATE TABLE rsvps (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    activity_id UUID NOT NULL REFERENCES activities(id) ON DELETE CASCADE,
    status VARCHAR(20) NOT NULL DEFAULT 'attending', -- attending, maybe, declined
    is_paid BOOLEAN DEFAULT FALSE,
    payment_intent_id VARCHAR(255), -- Stripe PaymentIntent ID
    payment_status VARCHAR(20), -- pending, succeeded, failed, refunded
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    
    -- Constraints
    UNIQUE(user_id, activity_id)
);

-- Indexes for rsvps table
CREATE INDEX idx_rsvps_user ON rsvps(user_id);
CREATE INDEX idx_rsvps_activity ON rsvps(activity_id);
CREATE INDEX idx_rsvps_status ON rsvps(status);
CREATE INDEX idx_rsvps_payment_intent ON rsvps(payment_intent_id);

-- Comments Table
CREATE TABLE comments (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    activity_id UUID NOT NULL REFERENCES activities(id) ON DELETE CASCADE,
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    parent_comment_id UUID REFERENCES comments(id) ON DELETE CASCADE, -- For replies
    content TEXT NOT NULL,
    is_edited BOOLEAN DEFAULT FALSE,
    is_deleted BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    
    -- Constraints
    CONSTRAINT content_not_empty CHECK (LENGTH(TRIM(content)) > 0)
);

-- Indexes for comments table
CREATE INDEX idx_comments_activity ON comments(activity_id);
CREATE INDEX idx_comments_user ON comments(user_id);
CREATE INDEX idx_comments_parent ON comments(parent_comment_id);
CREATE INDEX idx_comments_created ON comments(created_at);

-- Tags Table (Category System)
CREATE TABLE tags (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name VARCHAR(50) UNIQUE NOT NULL,
    category VARCHAR(50), -- sports, music, social, etc.
    description TEXT,
    usage_count INTEGER DEFAULT 0,
    is_featured BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Indexes for tags table
CREATE INDEX idx_tags_name ON tags(name);
CREATE INDEX idx_tags_category ON tags(category);
CREATE INDEX idx_tags_usage_count ON tags(usage_count DESC);
CREATE INDEX idx_tags_featured ON tags(is_featured) WHERE is_featured = TRUE;

-- Notifications Table
CREATE TABLE notifications (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    type VARCHAR(50) NOT NULL, -- follow, rsvp, comment, activity_update, etc.
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    data JSONB, -- Additional structured data
    is_read BOOLEAN DEFAULT FALSE,
    delivery_status VARCHAR(20) DEFAULT 'pending', -- pending, sent, failed
    delivery_method VARCHAR(20) NOT NULL, -- push, email, in_app
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    read_at TIMESTAMP WITH TIME ZONE
);

-- Indexes for notifications table
CREATE INDEX idx_notifications_user ON notifications(user_id);
CREATE INDEX idx_notifications_type ON notifications(type);
CREATE INDEX idx_notifications_is_read ON notifications(is_read);
CREATE INDEX idx_notifications_created ON notifications(created_at DESC);

-- Flares Table (Activity Inquiries)
CREATE TABLE flares (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    activity_type VARCHAR(50) NOT NULL,
    location_name VARCHAR(255),
    location_coordinates GEOGRAPHY(POINT, 4326),
    preferred_time TIMESTAMP WITH TIME ZONE,
    max_participants INTEGER,
    tags TEXT[] NOT NULL DEFAULT '{}',
    status VARCHAR(20) DEFAULT 'active', -- active, fulfilled, expired
    expires_at TIMESTAMP WITH TIME ZONE,
    converted_activity_id UUID REFERENCES activities(id),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Indexes for flares table
CREATE INDEX idx_flares_user ON flares(user_id);
CREATE INDEX idx_flares_location ON flares USING GIST(location_coordinates);
CREATE INDEX idx_flares_type ON flares(activity_type);
CREATE INDEX idx_flares_status ON flares(status);
CREATE INDEX idx_flares_tags ON flares USING GIN(tags);

-- Reports Table (Content Moderation)
CREATE TABLE reports (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    reporter_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    reported_user_id UUID REFERENCES users(id) ON DELETE CASCADE,
    reported_activity_id UUID REFERENCES activities(id) ON DELETE CASCADE,
    reported_comment_id UUID REFERENCES comments(id) ON DELETE CASCADE,
    reason VARCHAR(50) NOT NULL, -- spam, inappropriate, harassment, etc.
    description TEXT,
    status VARCHAR(20) DEFAULT 'pending', -- pending, reviewed, resolved, dismissed
    admin_notes TEXT,
    reviewed_by UUID REFERENCES users(id),
    reviewed_at TIMESTAMP WITH TIME ZONE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    
    -- Constraints
    CONSTRAINT report_target_check CHECK (
        (reported_user_id IS NOT NULL)::int + 
        (reported_activity_id IS NOT NULL)::int + 
        (reported_comment_id IS NOT NULL)::int = 1
    )
);

-- Indexes for reports table
CREATE INDEX idx_reports_reporter ON reports(reporter_id);
CREATE INDEX idx_reports_status ON reports(status);
CREATE INDEX idx_reports_created ON reports(created_at DESC);

-- Database Functions and Triggers

-- Updated At Trigger Function
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Counter Update Function
CREATE OR REPLACE FUNCTION update_user_counters()
RETURNS TRIGGER AS $$
BEGIN
    -- Update follower/following counts
    IF TG_TABLE_NAME = 'follows' THEN
        IF TG_OP = 'INSERT' THEN
            UPDATE users SET following_count = following_count + 1 WHERE id = NEW.follower_id;
            UPDATE users SET follower_count = follower_count + 1 WHERE id = NEW.following_id;
        ELSIF TG_OP = 'DELETE' THEN
            UPDATE users SET following_count = following_count - 1 WHERE id = OLD.follower_id;
            UPDATE users SET follower_count = follower_count - 1 WHERE id = OLD.following_id;
        END IF;
    END IF;

    -- Update activity attendee counts
    IF TG_TABLE_NAME = 'rsvps' THEN
        IF TG_OP = 'INSERT' AND NEW.status = 'attending' THEN
            UPDATE activities SET current_attendees = current_attendees + 1 WHERE id = NEW.activity_id;
        ELSIF TG_OP = 'UPDATE' THEN
            IF OLD.status != 'attending' AND NEW.status = 'attending' THEN
                UPDATE activities SET current_attendees = current_attendees + 1 WHERE id = NEW.activity_id;
            ELSIF OLD.status = 'attending' AND NEW.status != 'attending' THEN
                UPDATE activities SET current_attendees = current_attendees - 1 WHERE id = NEW.activity_id;
            END IF;
        ELSIF TG_OP = 'DELETE' AND OLD.status = 'attending' THEN
            UPDATE activities SET current_attendees = current_attendees - 1 WHERE id = OLD.activity_id;
        END IF;
    END IF;

    RETURN COALESCE(NEW, OLD);
END;
$$ LANGUAGE plpgsql;

-- Apply updated_at triggers to all tables with updated_at column
CREATE TRIGGER update_users_updated_at BEFORE UPDATE ON users
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE TRIGGER update_activities_updated_at BEFORE UPDATE ON activities
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE TRIGGER update_rsvps_updated_at BEFORE UPDATE ON rsvps
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE TRIGGER update_comments_updated_at BEFORE UPDATE ON comments
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE TRIGGER update_flares_updated_at BEFORE UPDATE ON flares
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Apply counter triggers
CREATE TRIGGER update_follow_counts
    AFTER INSERT OR DELETE ON follows
    FOR EACH ROW EXECUTE FUNCTION update_user_counters();

CREATE TRIGGER update_rsvp_counts
    AFTER INSERT OR UPDATE OR DELETE ON rsvps
    FOR EACH ROW EXECUTE FUNCTION update_user_counters();