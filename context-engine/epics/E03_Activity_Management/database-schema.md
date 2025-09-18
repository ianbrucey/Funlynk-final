# E03 Activity Management - Database Schema

## Schema Overview

This document details the database schema requirements specific to Activity Management. The core `activities`, `rsvps`, `tags`, and `activity_tags` tables were defined in E01 Core Infrastructure. This epic focuses on schema optimizations, additional tables, and specific queries needed for activity management functionality.

## Core Tables Review

### Activities Table (from E01)
The activities table from E01 Core Infrastructure provides the foundation:

```sql
-- Core activity data (defined in E01)
CREATE TABLE activities (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    host_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    location_name VARCHAR(255) NOT NULL,
    location_coordinates GEOGRAPHY(POINT, 4326) NOT NULL,
    start_time TIMESTAMP WITH TIME ZONE NOT NULL,
    end_time TIMESTAMP WITH TIME ZONE,
    capacity INTEGER,
    price_cents INTEGER DEFAULT 0,
    currency VARCHAR(3) DEFAULT 'USD',
    status VARCHAR(20) DEFAULT 'published',
    rsvp_count INTEGER DEFAULT 0,
    waitlist_count INTEGER DEFAULT 0,
    image_url TEXT,
    requirements TEXT,
    equipment_provided TEXT,
    skill_level VARCHAR(20),
    is_recurring BOOLEAN DEFAULT FALSE,
    recurring_pattern JSONB,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);
```

### RSVPs Table (from E01)
The participant registration foundation:

```sql
-- RSVP data (defined in E01)
CREATE TABLE rsvps (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    activity_id UUID NOT NULL REFERENCES activities(id) ON DELETE CASCADE,
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    status VARCHAR(20) DEFAULT 'confirmed',
    rsvp_time TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    attended BOOLEAN,
    attendance_time TIMESTAMP WITH TIME ZONE,
    guest_count INTEGER DEFAULT 0,
    special_requests TEXT,
    payment_status VARCHAR(20) DEFAULT 'pending',
    payment_intent_id VARCHAR(255),
    
    UNIQUE(activity_id, user_id)
);
```

### Tags and Activity_Tags Tables (from E01)
The tagging system foundation:

```sql
-- Tags system (defined in E01)
CREATE TABLE tags (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name VARCHAR(50) UNIQUE NOT NULL,
    category VARCHAR(50),
    usage_count INTEGER DEFAULT 0,
    is_verified BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE TABLE activity_tags (
    activity_id UUID NOT NULL REFERENCES activities(id) ON DELETE CASCADE,
    tag_id UUID NOT NULL REFERENCES tags(id) ON DELETE CASCADE,
    PRIMARY KEY (activity_id, tag_id)
);
```

## Additional Tables for E03

### Activity Images Table
```sql
CREATE TABLE activity_images (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    activity_id UUID NOT NULL REFERENCES activities(id) ON DELETE CASCADE,
    image_url TEXT NOT NULL,
    image_order INTEGER NOT NULL DEFAULT 0,
    caption TEXT,
    file_size INTEGER NOT NULL,
    width INTEGER NOT NULL,
    height INTEGER NOT NULL,
    upload_status VARCHAR(20) DEFAULT 'processing', -- processing, ready, failed
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    
    -- Ensure reasonable image order
    CONSTRAINT valid_image_order CHECK (image_order >= 0 AND image_order < 10)
);

-- Indexes
CREATE INDEX idx_activity_images_activity ON activity_images(activity_id);
CREATE INDEX idx_activity_images_order ON activity_images(activity_id, image_order);
CREATE INDEX idx_activity_images_status ON activity_images(upload_status);
```

### Activity Templates Table
```sql
CREATE TABLE activity_templates (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name VARCHAR(255) NOT NULL,
    category VARCHAR(50) NOT NULL,
    description TEXT,
    default_title VARCHAR(255),
    default_description TEXT,
    default_duration_minutes INTEGER,
    default_capacity INTEGER,
    suggested_tags TEXT[],
    requirements_template TEXT,
    equipment_template TEXT,
    default_skill_level VARCHAR(20),
    is_active BOOLEAN DEFAULT TRUE,
    usage_count INTEGER DEFAULT 0,
    created_by UUID REFERENCES users(id),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Indexes
CREATE INDEX idx_activity_templates_category ON activity_templates(category);
CREATE INDEX idx_activity_templates_active ON activity_templates(is_active);
CREATE INDEX idx_activity_templates_usage ON activity_templates(usage_count DESC);
```

### Activity Waitlist Table
```sql
CREATE TABLE activity_waitlist (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    activity_id UUID NOT NULL REFERENCES activities(id) ON DELETE CASCADE,
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    position INTEGER NOT NULL,
    joined_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    notified_at TIMESTAMP WITH TIME ZONE,
    expires_at TIMESTAMP WITH TIME ZONE,
    
    UNIQUE(activity_id, user_id),
    UNIQUE(activity_id, position)
);

-- Indexes
CREATE INDEX idx_activity_waitlist_activity ON activity_waitlist(activity_id);
CREATE INDEX idx_activity_waitlist_position ON activity_waitlist(activity_id, position);
CREATE INDEX idx_activity_waitlist_user ON activity_waitlist(user_id);
```

### Activity Requirements Table
```sql
CREATE TABLE activity_requirements (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    activity_id UUID NOT NULL REFERENCES activities(id) ON DELETE CASCADE,
    requirement_type VARCHAR(50) NOT NULL, -- question, waiver, equipment, skill
    title VARCHAR(255) NOT NULL,
    description TEXT,
    is_required BOOLEAN DEFAULT TRUE,
    options JSONB, -- For multiple choice questions
    validation_rules JSONB, -- For input validation
    display_order INTEGER NOT NULL DEFAULT 0,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Indexes
CREATE INDEX idx_activity_requirements_activity ON activity_requirements(activity_id);
CREATE INDEX idx_activity_requirements_order ON activity_requirements(activity_id, display_order);
```

### RSVP Responses Table
```sql
CREATE TABLE rsvp_responses (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    rsvp_id UUID NOT NULL REFERENCES rsvps(id) ON DELETE CASCADE,
    requirement_id UUID NOT NULL REFERENCES activity_requirements(id) ON DELETE CASCADE,
    response_text TEXT,
    response_data JSONB, -- For structured responses
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    
    UNIQUE(rsvp_id, requirement_id)
);

-- Indexes
CREATE INDEX idx_rsvp_responses_rsvp ON rsvp_responses(rsvp_id);
CREATE INDEX idx_rsvp_responses_requirement ON rsvp_responses(requirement_id);
```

### Tag Categories Table
```sql
CREATE TABLE tag_categories (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name VARCHAR(50) UNIQUE NOT NULL,
    parent_id UUID REFERENCES tag_categories(id),
    description TEXT,
    icon_name VARCHAR(50),
    color_hex VARCHAR(7),
    display_order INTEGER DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Indexes
CREATE INDEX idx_tag_categories_parent ON tag_categories(parent_id);
CREATE INDEX idx_tag_categories_active ON tag_categories(is_active);
CREATE INDEX idx_tag_categories_order ON tag_categories(display_order);
```

## Enhanced Indexes for Activity Management

### Activity Discovery Indexes
```sql
-- Optimize location-based activity discovery
CREATE INDEX idx_activities_location_time ON activities 
    USING GIST(location_coordinates, start_time) 
    WHERE status = 'published' AND start_time > NOW();

-- Optimize category-based discovery
CREATE INDEX idx_activities_category_time ON activities(status, start_time DESC)
    WHERE status = 'published';

-- Optimize host-based queries
CREATE INDEX idx_activities_host_status ON activities(host_id, status, start_time DESC);

-- Optimize capacity-based queries
CREATE INDEX idx_activities_capacity ON activities(capacity, rsvp_count)
    WHERE status = 'published' AND capacity IS NOT NULL;
```

### RSVP Performance Indexes
```sql
-- Optimize user RSVP history
CREATE INDEX idx_rsvps_user_time ON rsvps(user_id, rsvp_time DESC);

-- Optimize activity participant lists
CREATE INDEX idx_rsvps_activity_status ON rsvps(activity_id, status, rsvp_time);

-- Optimize attendance tracking
CREATE INDEX idx_rsvps_attendance ON rsvps(activity_id, attended, attendance_time);
```

### Tagging System Indexes
```sql
-- Optimize tag search and autocomplete
CREATE INDEX idx_tags_name_search ON tags(name text_pattern_ops);
CREATE INDEX idx_tags_usage ON tags(usage_count DESC, name);

-- Optimize activity tag queries
CREATE INDEX idx_activity_tags_tag ON activity_tags(tag_id);
CREATE INDEX idx_activity_tags_activity ON activity_tags(activity_id);
```

## Row Level Security Policies for E03

### Activity Images Policies
```sql
-- Public can view images for published activities
CREATE POLICY "Activity images viewable for published activities" ON activity_images
    FOR SELECT USING (
        EXISTS (
            SELECT 1 FROM activities 
            WHERE id = activity_id 
            AND status = 'published'
        )
    );

-- Hosts can manage their activity images
CREATE POLICY "Hosts can manage activity images" ON activity_images
    FOR ALL USING (
        EXISTS (
            SELECT 1 FROM activities 
            WHERE id = activity_id 
            AND host_id = auth.uid()
        )
    );
```

### Activity Requirements Policies
```sql
-- Public can view requirements for published activities
CREATE POLICY "Activity requirements viewable for published activities" ON activity_requirements
    FOR SELECT USING (
        EXISTS (
            SELECT 1 FROM activities 
            WHERE id = activity_id 
            AND status = 'published'
        )
    );

-- Hosts can manage their activity requirements
CREATE POLICY "Hosts can manage activity requirements" ON activity_requirements
    FOR ALL USING (
        EXISTS (
            SELECT 1 FROM activities 
            WHERE id = activity_id 
            AND host_id = auth.uid()
        )
    );
```

### RSVP Responses Policies
```sql
-- Users can view their own RSVP responses
CREATE POLICY "Users can view own RSVP responses" ON rsvp_responses
    FOR SELECT USING (
        EXISTS (
            SELECT 1 FROM rsvps 
            WHERE id = rsvp_id 
            AND user_id = auth.uid()
        )
    );

-- Hosts can view responses for their activities
CREATE POLICY "Hosts can view RSVP responses for their activities" ON rsvp_responses
    FOR SELECT USING (
        EXISTS (
            SELECT 1 FROM rsvps r
            JOIN activities a ON r.activity_id = a.id
            WHERE r.id = rsvp_id 
            AND a.host_id = auth.uid()
        )
    );

-- Users can manage their own responses
CREATE POLICY "Users can manage own RSVP responses" ON rsvp_responses
    FOR INSERT WITH CHECK (
        EXISTS (
            SELECT 1 FROM rsvps 
            WHERE id = rsvp_id 
            AND user_id = auth.uid()
        )
    );
```

## Database Functions for E03

### Activity Capacity Management Function
```sql
CREATE OR REPLACE FUNCTION check_activity_capacity(activity_uuid UUID)
RETURNS TABLE(
    has_capacity BOOLEAN,
    current_rsvps INTEGER,
    max_capacity INTEGER,
    waitlist_position INTEGER
) AS $$
DECLARE
    activity_record RECORD;
    current_count INTEGER;
    waitlist_pos INTEGER;
BEGIN
    -- Get activity details
    SELECT capacity, rsvp_count INTO activity_record
    FROM activities 
    WHERE id = activity_uuid;
    
    IF activity_record IS NULL THEN
        RETURN QUERY SELECT FALSE, 0, 0, 0;
        RETURN;
    END IF;
    
    current_count := activity_record.rsvp_count;
    
    -- Check if unlimited capacity
    IF activity_record.capacity IS NULL THEN
        RETURN QUERY SELECT TRUE, current_count, NULL::INTEGER, 0;
        RETURN;
    END IF;
    
    -- Check if has capacity
    IF current_count < activity_record.capacity THEN
        RETURN QUERY SELECT TRUE, current_count, activity_record.capacity, 0;
    ELSE
        -- Get waitlist position
        SELECT COALESCE(MAX(position), 0) + 1 INTO waitlist_pos
        FROM activity_waitlist 
        WHERE activity_id = activity_uuid;
        
        RETURN QUERY SELECT FALSE, current_count, activity_record.capacity, waitlist_pos;
    END IF;
END;
$$ LANGUAGE plpgsql;
```

### RSVP Creation Function
```sql
CREATE OR REPLACE FUNCTION create_rsvp(
    p_activity_id UUID,
    p_user_id UUID,
    p_guest_count INTEGER DEFAULT 0
)
RETURNS TABLE(
    rsvp_id UUID,
    status VARCHAR(20),
    waitlist_position INTEGER
) AS $$
DECLARE
    capacity_info RECORD;
    new_rsvp_id UUID;
    final_status VARCHAR(20);
    waitlist_pos INTEGER := 0;
BEGIN
    -- Check capacity
    SELECT * INTO capacity_info 
    FROM check_activity_capacity(p_activity_id);
    
    -- Generate RSVP ID
    new_rsvp_id := gen_random_uuid();
    
    -- Determine status based on capacity
    IF capacity_info.has_capacity THEN
        final_status := 'confirmed';
        
        -- Create confirmed RSVP
        INSERT INTO rsvps (id, activity_id, user_id, status, guest_count)
        VALUES (new_rsvp_id, p_activity_id, p_user_id, final_status, p_guest_count);
        
        -- Update activity RSVP count
        UPDATE activities 
        SET rsvp_count = rsvp_count + 1 + p_guest_count,
            updated_at = NOW()
        WHERE id = p_activity_id;
        
    ELSE
        final_status := 'waitlisted';
        waitlist_pos := capacity_info.waitlist_position;
        
        -- Create waitlisted RSVP
        INSERT INTO rsvps (id, activity_id, user_id, status, guest_count)
        VALUES (new_rsvp_id, p_activity_id, p_user_id, final_status, p_guest_count);
        
        -- Add to waitlist
        INSERT INTO activity_waitlist (activity_id, user_id, position)
        VALUES (p_activity_id, p_user_id, waitlist_pos);
        
        -- Update waitlist count
        UPDATE activities 
        SET waitlist_count = waitlist_count + 1,
            updated_at = NOW()
        WHERE id = p_activity_id;
    END IF;
    
    RETURN QUERY SELECT new_rsvp_id, final_status, waitlist_pos;
END;
$$ LANGUAGE plpgsql;
```

### Tag Usage Update Function
```sql
CREATE OR REPLACE FUNCTION update_tag_usage()
RETURNS TRIGGER AS $$
BEGIN
    IF TG_OP = 'INSERT' THEN
        -- Increment usage count
        UPDATE tags 
        SET usage_count = usage_count + 1 
        WHERE id = NEW.tag_id;
        RETURN NEW;
    ELSIF TG_OP = 'DELETE' THEN
        -- Decrement usage count
        UPDATE tags 
        SET usage_count = GREATEST(0, usage_count - 1) 
        WHERE id = OLD.tag_id;
        RETURN OLD;
    END IF;
    RETURN NULL;
END;
$$ LANGUAGE plpgsql;

-- Create trigger
CREATE TRIGGER trigger_update_tag_usage
    AFTER INSERT OR DELETE ON activity_tags
    FOR EACH ROW EXECUTE FUNCTION update_tag_usage();
```

## Query Patterns for E03

### Common Activity Queries
```sql
-- Get activities with full details including tags and RSVP status
SELECT 
    a.*,
    u.username as host_username,
    u.display_name as host_display_name,
    u.profile_image_url as host_profile_image,
    u.is_verified as host_is_verified,
    array_agg(DISTINCT t.name) as tags,
    r.status as user_rsvp_status,
    r.rsvp_time as user_rsvp_time
FROM activities a
JOIN users u ON a.host_id = u.id
LEFT JOIN activity_tags at ON a.id = at.activity_id
LEFT JOIN tags t ON at.tag_id = t.id
LEFT JOIN rsvps r ON a.id = r.activity_id AND r.user_id = $1
WHERE a.status = 'published'
AND a.start_time > NOW()
GROUP BY a.id, u.username, u.display_name, u.profile_image_url, u.is_verified, r.status, r.rsvp_time
ORDER BY a.start_time ASC;

-- Get user's RSVP history with activity details
SELECT 
    r.*,
    a.title,
    a.start_time,
    a.location_name,
    a.host_id,
    u.display_name as host_name
FROM rsvps r
JOIN activities a ON r.activity_id = a.id
JOIN users u ON a.host_id = u.id
WHERE r.user_id = $1
ORDER BY r.rsvp_time DESC;
```

### Tag and Category Queries
```sql
-- Get popular tags with usage statistics
SELECT 
    t.name,
    t.category,
    t.usage_count,
    COUNT(at.activity_id) as recent_usage
FROM tags t
LEFT JOIN activity_tags at ON t.id = at.tag_id
LEFT JOIN activities a ON at.activity_id = a.id 
    AND a.created_at > NOW() - INTERVAL '30 days'
WHERE t.usage_count > 0
GROUP BY t.id, t.name, t.category, t.usage_count
ORDER BY t.usage_count DESC, recent_usage DESC
LIMIT 50;

-- Get tag suggestions for autocomplete
SELECT name, usage_count
FROM tags
WHERE name ILIKE $1 || '%'
AND usage_count > 0
ORDER BY usage_count DESC, name ASC
LIMIT 10;
```

---

**Database Schema Status**: ✅ Complete - Additional tables and optimizations for activity management
**Next Steps**: Define service architecture for activity, tagging, and RSVP services
