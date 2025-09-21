# T02: Core Database Schema Implementation - Enhanced Planning

## Planning Overview

This document provides detailed specifications for implementing the complete database schema across UX, Backend, Frontend, and Third-party service domains. The implementation will create all tables, relationships, constraints, and indexes required for the Funlynk platform.

## UX Specification

### User Experience Considerations

#### Developer Experience (Primary Users)
- **Schema Clarity**: Clear table and column naming conventions
- **Documentation**: Comprehensive schema documentation with examples
- **Development Tools**: Easy schema exploration and testing
- **Error Messages**: Clear constraint violation messages

#### Database Administration Experience
- **Monitoring**: Schema health and performance monitoring
- **Maintenance**: Easy schema updates and migrations
- **Backup**: Clear backup and recovery procedures
- **Performance**: Query performance optimization tools

### UX Requirements
- [ ] Schema documentation is comprehensive and searchable
- [ ] Table relationships are clearly visualized
- [ ] Constraint violations provide helpful error messages
- [ ] Development team can easily explore and understand schema
- [ ] Database administration tools are intuitive and effective

### UX Success Metrics
- Schema exploration time < 15 minutes for new developers
- Zero critical schema misunderstandings during development
- Database administration tasks complete without errors
- Schema documentation clarity rating > 4.5/5 from team

## Backend Specification

### Database Schema Implementation

#### Core Infrastructure Tables
```sql
-- Users table (Supabase Auth integration)
CREATE TABLE users (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    email VARCHAR(255) UNIQUE NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    display_name TEXT,
    avatar_url TEXT,
    phone VARCHAR(20),
    date_of_birth DATE,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW(),
    last_seen_at TIMESTAMPTZ DEFAULT NOW(),
    is_active BOOLEAN DEFAULT true,
    
    CONSTRAINT users_email_format CHECK (email ~* '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$'),
    CONSTRAINT users_username_format CHECK (username ~* '^[a-zA-Z0-9_]{3,50}$')
);

-- User profiles (extended information)
CREATE TABLE user_profiles (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID UNIQUE REFERENCES users(id) ON DELETE CASCADE,
    bio TEXT,
    location GEOGRAPHY(POINT, 4326),
    location_name TEXT,
    privacy_level TEXT DEFAULT 'public' CHECK (privacy_level IN ('public', 'friends', 'private')),
    show_location BOOLEAN DEFAULT true,
    show_followers BOOLEAN DEFAULT true,
    show_following BOOLEAN DEFAULT true,
    verification_status TEXT DEFAULT 'unverified' CHECK (verification_status IN ('unverified', 'pending', 'verified', 'rejected')),
    preferences JSONB DEFAULT '{}',
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

-- Activity categories
CREATE TABLE activity_categories (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name TEXT UNIQUE NOT NULL,
    description TEXT,
    icon_url TEXT,
    color_hex VARCHAR(7),
    is_active BOOLEAN DEFAULT true,
    sort_order INTEGER DEFAULT 0,
    created_at TIMESTAMPTZ DEFAULT NOW()
);

-- Activities
CREATE TABLE activities (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    host_id UUID REFERENCES users(id) ON DELETE SET NULL,
    category_id UUID REFERENCES activity_categories(id) ON DELETE RESTRICT,
    title TEXT NOT NULL,
    description TEXT,
    location GEOGRAPHY(POINT, 4326),
    location_name TEXT NOT NULL,
    start_date TIMESTAMPTZ NOT NULL,
    end_date TIMESTAMPTZ,
    max_participants INTEGER,
    min_participants INTEGER DEFAULT 1,
    price_cents INTEGER DEFAULT 0,
    currency VARCHAR(3) DEFAULT 'USD',
    visibility TEXT DEFAULT 'public' CHECK (visibility IN ('public', 'private', 'invite_only')),
    status TEXT DEFAULT 'active' CHECK (status IN ('draft', 'active', 'cancelled', 'completed')),
    requirements TEXT,
    what_to_bring TEXT,
    cancellation_policy TEXT,
    metadata JSONB DEFAULT '{}',
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW(),
    
    CONSTRAINT activities_max_participants_positive CHECK (max_participants > 0),
    CONSTRAINT activities_min_max_participants CHECK (min_participants <= max_participants),
    CONSTRAINT activities_end_after_start CHECK (end_date IS NULL OR end_date > start_date),
    CONSTRAINT activities_price_non_negative CHECK (price_cents >= 0)
);
```

#### Social and Interaction Tables
```sql
-- Follow relationships
CREATE TABLE follow_relationships (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    follower_id UUID REFERENCES users(id) ON DELETE CASCADE,
    following_id UUID REFERENCES users(id) ON DELETE CASCADE,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    
    UNIQUE(follower_id, following_id),
    CONSTRAINT follow_no_self_follow CHECK (follower_id != following_id)
);

-- RSVPs
CREATE TABLE rsvps (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    activity_id UUID REFERENCES activities(id) ON DELETE CASCADE,
    user_id UUID REFERENCES users(id) ON DELETE CASCADE,
    status TEXT DEFAULT 'pending' CHECK (status IN ('pending', 'confirmed', 'cancelled', 'waitlisted')),
    response_data JSONB DEFAULT '{}',
    payment_status TEXT DEFAULT 'pending' CHECK (payment_status IN ('pending', 'paid', 'refunded', 'failed')),
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW(),
    
    UNIQUE(activity_id, user_id)
);

-- Comments
CREATE TABLE comments (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    activity_id UUID REFERENCES activities(id) ON DELETE CASCADE,
    user_id UUID REFERENCES users(id) ON DELETE SET NULL,
    parent_comment_id UUID REFERENCES comments(id) ON DELETE CASCADE,
    content TEXT NOT NULL,
    is_edited BOOLEAN DEFAULT false,
    is_deleted BOOLEAN DEFAULT false,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW(),
    
    CONSTRAINT comments_content_not_empty CHECK (LENGTH(TRIM(content)) > 0)
);
```

#### Payment and Monetization Tables
```sql
-- Payment methods
CREATE TABLE payment_methods (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID REFERENCES users(id) ON DELETE CASCADE,
    stripe_payment_method_id TEXT UNIQUE NOT NULL,
    type TEXT NOT NULL CHECK (type IN ('card', 'bank_account', 'digital_wallet')),
    last_four VARCHAR(4),
    brand TEXT,
    exp_month INTEGER,
    exp_year INTEGER,
    is_default BOOLEAN DEFAULT false,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

-- Transactions
CREATE TABLE transactions (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    stripe_payment_intent_id TEXT UNIQUE,
    activity_id UUID REFERENCES activities(id) ON DELETE SET NULL,
    payer_id UUID REFERENCES users(id) ON DELETE SET NULL,
    recipient_id UUID REFERENCES users(id) ON DELETE SET NULL,
    amount_cents INTEGER NOT NULL,
    currency VARCHAR(3) DEFAULT 'USD',
    platform_fee_cents INTEGER DEFAULT 0,
    status TEXT DEFAULT 'pending' CHECK (status IN ('pending', 'processing', 'succeeded', 'failed', 'cancelled', 'refunded')),
    failure_reason TEXT,
    metadata JSONB DEFAULT '{}',
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW(),
    
    CONSTRAINT transactions_amount_positive CHECK (amount_cents > 0),
    CONSTRAINT transactions_fee_non_negative CHECK (platform_fee_cents >= 0)
);
```

### Performance Optimization

#### Strategic Indexes
```sql
-- Geographic search optimization
CREATE INDEX idx_activities_location_gist ON activities USING GIST (location);
CREATE INDEX idx_user_profiles_location_gist ON user_profiles USING GIST (location);

-- Activity discovery optimization
CREATE INDEX idx_activities_discovery ON activities (category_id, start_date, visibility) WHERE status = 'active';
CREATE INDEX idx_activities_host_date ON activities (host_id, start_date DESC);

-- User search and lookup
CREATE INDEX idx_users_username_lower ON users (LOWER(username));
CREATE INDEX idx_users_email_lower ON users (LOWER(email));

-- Social interaction optimization
CREATE INDEX idx_follow_relationships_follower ON follow_relationships (follower_id, created_at DESC);
CREATE INDEX idx_follow_relationships_following ON follow_relationships (following_id, created_at DESC);
CREATE INDEX idx_comments_activity_date ON comments (activity_id, created_at DESC) WHERE is_deleted = false;

-- RSVP and payment optimization
CREATE INDEX idx_rsvps_user_status ON rsvps (user_id, status, created_at DESC);
CREATE INDEX idx_rsvps_activity_status ON rsvps (activity_id, status);
CREATE INDEX idx_transactions_user_date ON transactions (payer_id, created_at DESC);
```

### Database Functions and Triggers
```sql
-- Update timestamp trigger function
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$ language 'plpgsql';

-- Apply to all tables with updated_at
CREATE TRIGGER update_users_updated_at BEFORE UPDATE ON users
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_user_profiles_updated_at BEFORE UPDATE ON user_profiles
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- User profile creation trigger
CREATE OR REPLACE FUNCTION create_user_profile()
RETURNS TRIGGER AS $$
BEGIN
    INSERT INTO user_profiles (user_id) VALUES (NEW.id);
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER create_user_profile_trigger AFTER INSERT ON users
    FOR EACH ROW EXECUTE FUNCTION create_user_profile();
```

## Frontend Specification

### Frontend Integration Requirements

#### Database Client Configuration
```typescript
// Supabase client types
interface Database {
  public: {
    Tables: {
      users: {
        Row: User;
        Insert: UserInsert;
        Update: UserUpdate;
      };
      activities: {
        Row: Activity;
        Insert: ActivityInsert;
        Update: ActivityUpdate;
      };
      // ... other tables
    };
  };
}

// Type-safe database client
const supabase = createClient<Database>(
  process.env.NEXT_PUBLIC_SUPABASE_URL!,
  process.env.NEXT_PUBLIC_SUPABASE_ANON_KEY!
);
```

#### Query Patterns
```typescript
// Activity discovery with location
const { data: activities } = await supabase
  .from('activities')
  .select(`
    *,
    host:users(username, display_name, avatar_url),
    category:activity_categories(name, icon_url),
    rsvp_count:rsvps(count)
  `)
  .eq('visibility', 'public')
  .eq('status', 'active')
  .gte('start_date', new Date().toISOString())
  .order('start_date', { ascending: true });

// User profile with social stats
const { data: profile } = await supabase
  .from('user_profiles')
  .select(`
    *,
    user:users(*),
    follower_count:follow_relationships!following_id(count),
    following_count:follow_relationships!follower_id(count)
  `)
  .eq('user_id', userId)
  .single();
```

### Real-time Subscriptions
```typescript
// Activity comments real-time
const commentsSubscription = supabase
  .channel('activity-comments')
  .on('postgres_changes', {
    event: '*',
    schema: 'public',
    table: 'comments',
    filter: `activity_id=eq.${activityId}`
  }, (payload) => {
    // Handle real-time comment updates
  })
  .subscribe();

// RSVP updates real-time
const rsvpSubscription = supabase
  .channel('activity-rsvps')
  .on('postgres_changes', {
    event: '*',
    schema: 'public',
    table: 'rsvps',
    filter: `activity_id=eq.${activityId}`
  }, (payload) => {
    // Handle real-time RSVP updates
  })
  .subscribe();
```

## Third-Party Services Specification

### Supabase Integration

#### Database Configuration
```sql
-- Enable necessary extensions
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS "postgis";
CREATE EXTENSION IF NOT EXISTS "pg_stat_statements";

-- Configure connection pooling
ALTER SYSTEM SET max_connections = 200;
ALTER SYSTEM SET shared_preload_libraries = 'pg_stat_statements';
SELECT pg_reload_conf();
```

#### Row Level Security Preparation
```sql
-- Enable RLS on all tables (will be configured in T03)
ALTER TABLE users ENABLE ROW LEVEL SECURITY;
ALTER TABLE user_profiles ENABLE ROW LEVEL SECURITY;
ALTER TABLE activities ENABLE ROW LEVEL SECURITY;
ALTER TABLE rsvps ENABLE ROW LEVEL SECURITY;
ALTER TABLE comments ENABLE ROW LEVEL SECURITY;
ALTER TABLE follow_relationships ENABLE ROW LEVEL SECURITY;
ALTER TABLE payment_methods ENABLE ROW LEVEL SECURITY;
ALTER TABLE transactions ENABLE ROW LEVEL SECURITY;
```

### External Service Integration Points

#### Stripe Integration
- Payment methods table stores Stripe payment method IDs
- Transactions table tracks Stripe payment intent IDs
- Webhook handling for payment status updates

#### PostGIS/Geographic Services
- Location data stored as PostGIS GEOGRAPHY type
- Spatial indexes for efficient geographic queries
- Distance calculations using PostGIS functions

#### Analytics and Monitoring
- Database performance monitoring via pg_stat_statements
- Query performance tracking and optimization
- Connection pool monitoring and alerting

## Implementation Sequence

### Phase 1: Core Tables (1.5 hours)
1. **User Management** (30 min)
   - Create users and user_profiles tables
   - Implement constraints and triggers
   - Test user creation workflow

2. **Activity System** (45 min)
   - Create activity_categories and activities tables
   - Implement geographic and constraint validation
   - Test activity creation and querying

3. **Basic Relationships** (15 min)
   - Create follow_relationships table
   - Test social graph functionality

### Phase 2: Interaction Tables (1 hour)
1. **RSVP System** (30 min)
   - Create rsvps table with constraints
   - Test RSVP creation and status updates

2. **Social Features** (30 min)
   - Create comments table with threading support
   - Test comment creation and retrieval

### Phase 3: Monetization Tables (1 hour)
1. **Payment Infrastructure** (45 min)
   - Create payment_methods and transactions tables
   - Implement financial constraints and validation

2. **Testing and Validation** (15 min)
   - Test all table relationships
   - Validate constraint enforcement

### Phase 4: Performance Optimization (30 minutes)
1. **Index Creation** (20 min)
   - Create all strategic indexes
   - Test query performance improvements

2. **Final Validation** (10 min)
   - Run comprehensive schema validation
   - Verify all constraints and relationships

## Quality Assurance

### Testing Checklist
- [ ] All tables created successfully
- [ ] Foreign key relationships work correctly
- [ ] Constraints prevent invalid data
- [ ] Indexes improve query performance
- [ ] Triggers execute properly
- [ ] Real-time subscriptions work with schema
- [ ] Type safety maintained in frontend integration

### Performance Validation
- [ ] Geographic queries execute in < 50ms
- [ ] User lookup queries execute in < 10ms
- [ ] Activity discovery queries execute in < 100ms
- [ ] Social graph queries execute in < 50ms
- [ ] Index usage is optimal for all query patterns

### Data Integrity Validation
- [ ] All constraints prevent invalid data entry
- [ ] Foreign key relationships maintain referential integrity
- [ ] Triggers execute correctly for all operations
- [ ] Enum types enforce valid values
- [ ] Check constraints validate business rules

---

**Planning Status**: ✅ Complete
**Implementation Ready**: Yes
**Estimated Total Time**: 4-5 hours
**Next Phase**: Implementation (04-implementation-enhanced.md)
