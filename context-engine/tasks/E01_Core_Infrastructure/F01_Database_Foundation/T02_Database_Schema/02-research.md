# T02: Core Database Schema Implementation - Research

## Research Objectives

1. Analyze complete schema requirements from all epic planning documents
2. Determine optimal PostgreSQL data types and constraints for each table
3. Research performance implications of schema design decisions
4. Plan foreign key relationships and referential integrity
5. Identify indexing requirements for expected query patterns

## Schema Requirements Analysis

### Epic Planning Review
Based on comprehensive epic planning, the database must support:

#### E01 Core Infrastructure Tables
- `users` - Core user accounts and authentication
- `user_sessions` - Session management and tracking
- `notifications` - Platform notification system
- `audit_logs` - Security and compliance audit trail

#### E02 User & Profile Management Tables
- `user_profiles` - Extended user profile information
- `user_preferences` - User settings and preferences
- `user_blocks` - User blocking relationships
- `follow_relationships` - Social following connections
- `user_verification_requests` - Identity verification workflow
- `profile_images` - User profile image management

#### E03 Activity Management Tables
- `activities` - Core activity information and scheduling
- `activity_images` - Activity photo management
- `activity_categories` - Activity categorization system
- `activity_tags` - Flexible tagging system
- `rsvps` - Activity reservations and attendance
- `rsvp_responses` - RSVP form responses and data
- `waitlists` - Activity waitlist management

#### E04 Discovery Engine Tables
- `user_interests` - User interest tracking and preferences
- `search_queries` - Search analytics and optimization
- `recommendation_cache` - Cached recommendation results
- `user_activity_interactions` - Engagement tracking and analytics

#### E05 Social Interaction Tables
- `comments` - Activity comments and discussions
- `comment_reactions` - Comment likes and reactions
- `activity_reactions` - Activity engagement tracking
- `communities` - Community groups and management
- `community_members` - Community membership tracking
- `messages` - Direct messaging system
- `social_shares` - Content sharing analytics

#### E06 Payments & Monetization Tables
- `payment_methods` - User payment information
- `transactions` - Payment transaction records
- `host_earnings` - Host revenue tracking
- `payouts` - Host payout processing
- `subscriptions` - User subscription management
- `subscription_plans` - Available subscription tiers
- `discount_codes` - Promotional code system
- `pricing_strategies` - Dynamic pricing management

#### E07 Administration Tables
- `analytics_events` - Event tracking and analytics
- `analytics_metrics` - Aggregated metrics and KPIs
- `moderation_queue` - Content moderation workflow
- `moderation_actions` - Moderation decisions and history
- `support_tickets` - Customer support system
- `system_health_metrics` - System monitoring data
- `admin_users` - Administrative access management

## PostgreSQL Data Type Decisions

### Primary Keys and IDs
**Decision**: Use UUID for all primary keys
```sql
id UUID PRIMARY KEY DEFAULT gen_random_uuid()
```
**Rationale**: UUIDs provide better security, avoid enumeration attacks, and support distributed systems

### Timestamps
**Decision**: Use TIMESTAMPTZ for all timestamp fields
```sql
created_at TIMESTAMPTZ DEFAULT NOW(),
updated_at TIMESTAMPTZ DEFAULT NOW()
```
**Rationale**: TIMESTAMPTZ handles timezone conversions automatically and provides better international support

### Geolocation Data
**Decision**: Use PostGIS GEOGRAPHY type for location data
```sql
location GEOGRAPHY(POINT, 4326)
```
**Rationale**: PostGIS provides optimized geospatial queries and distance calculations

### JSON Data
**Decision**: Use JSONB for flexible data storage
```sql
metadata JSONB,
preferences JSONB
```
**Rationale**: JSONB provides better performance than JSON and supports indexing

### Text Fields
**Decision**: Use TEXT for variable-length strings, VARCHAR only when length limits are required
```sql
description TEXT,
email VARCHAR(255) -- length limit for validation
```
**Rationale**: TEXT is more flexible and PostgreSQL optimizes storage automatically

## Relationship Design

### Foreign Key Constraints
All foreign key relationships will use CASCADE or RESTRICT based on business logic:

```sql
-- User-owned data: CASCADE delete
user_id UUID REFERENCES users(id) ON DELETE CASCADE

-- Reference data: RESTRICT delete
category_id UUID REFERENCES activity_categories(id) ON DELETE RESTRICT

-- Optional relationships: SET NULL
host_id UUID REFERENCES users(id) ON DELETE SET NULL
```

### Many-to-Many Relationships
Use junction tables with composite primary keys:

```sql
-- User interests junction table
CREATE TABLE user_interests (
    user_id UUID REFERENCES users(id) ON DELETE CASCADE,
    interest_category TEXT NOT NULL,
    interest_level INTEGER DEFAULT 1,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    PRIMARY KEY (user_id, interest_category)
);
```

## Performance Considerations

### Indexing Strategy
Based on expected query patterns from epic planning:

#### Geographic Queries
```sql
-- Spatial index for location-based searches
CREATE INDEX idx_activities_location ON activities USING GIST (location);
```

#### User Lookup Patterns
```sql
-- User email lookup (unique constraint provides index)
ALTER TABLE users ADD CONSTRAINT users_email_unique UNIQUE (email);

-- Username lookup
CREATE INDEX idx_users_username ON users (username);
```

#### Activity Discovery
```sql
-- Activity search by category and date
CREATE INDEX idx_activities_discovery ON activities (category_id, start_date, visibility);

-- Activity location and date composite
CREATE INDEX idx_activities_geo_date ON activities (start_date, location) WHERE visibility = 'public';
```

#### Social Queries
```sql
-- Follow relationships
CREATE INDEX idx_follow_relationships_follower ON follow_relationships (follower_id);
CREATE INDEX idx_follow_relationships_following ON follow_relationships (following_id);

-- Comments by activity
CREATE INDEX idx_comments_activity ON comments (activity_id, created_at);
```

### Query Optimization
- Use partial indexes for filtered queries
- Implement composite indexes for multi-column searches
- Consider expression indexes for computed values
- Plan for query pattern evolution

## Data Integrity and Constraints

### Check Constraints
```sql
-- Email format validation
ALTER TABLE users ADD CONSTRAINT users_email_format 
    CHECK (email ~* '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$');

-- Activity capacity validation
ALTER TABLE activities ADD CONSTRAINT activities_capacity_positive 
    CHECK (max_participants > 0);

-- Date validation
ALTER TABLE activities ADD CONSTRAINT activities_date_future 
    CHECK (start_date > created_at);
```

### Unique Constraints
```sql
-- Prevent duplicate usernames
ALTER TABLE users ADD CONSTRAINT users_username_unique UNIQUE (username);

-- Prevent duplicate RSVPs
ALTER TABLE rsvps ADD CONSTRAINT rsvps_user_activity_unique 
    UNIQUE (user_id, activity_id);
```

### Enum Types
```sql
-- Activity visibility levels
CREATE TYPE activity_visibility AS ENUM ('public', 'private', 'invite_only');

-- RSVP status
CREATE TYPE rsvp_status AS ENUM ('pending', 'confirmed', 'cancelled', 'waitlisted');

-- User verification status
CREATE TYPE verification_status AS ENUM ('unverified', 'pending', 'verified', 'rejected');
```

## Security Considerations

### Sensitive Data Handling
- Password fields will be handled by Supabase Auth (not stored in users table)
- Payment information will be tokenized (no raw card data stored)
- Personal information will be encrypted where required

### Audit Trail Design
```sql
-- Comprehensive audit logging
CREATE TABLE audit_logs (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID REFERENCES users(id),
    action TEXT NOT NULL,
    table_name TEXT NOT NULL,
    record_id UUID,
    old_values JSONB,
    new_values JSONB,
    ip_address INET,
    user_agent TEXT,
    created_at TIMESTAMPTZ DEFAULT NOW()
);
```

## Implementation Strategy

### Phase 1: Core Tables (1 hour)
1. Create users and user_profiles tables
2. Implement basic constraints and indexes
3. Test user creation and profile linking

### Phase 2: Activity System (1 hour)
1. Create activities, categories, and tags tables
2. Implement RSVP and waitlist tables
3. Add geographic and search indexes

### Phase 3: Social Features (1 hour)
1. Create social interaction tables (comments, reactions, follows)
2. Implement community and messaging tables
3. Add social query optimization indexes

### Phase 4: Platform Features (1 hour)
1. Create payment and subscription tables
2. Implement analytics and administration tables
3. Add performance and monitoring indexes

### Phase 5: Validation and Testing (30 minutes)
1. Validate all constraints and relationships
2. Test sample data insertion
3. Verify query performance

## Migration Considerations

### Schema Evolution
- Design tables to support future feature additions
- Use JSONB fields for flexible metadata
- Plan for backward-compatible changes
- Consider partitioning for large tables

### Data Migration
- Plan for importing existing user data (if any)
- Design seed data for development and testing
- Consider data anonymization for non-production environments

## Technical Decisions

### Decision 1: UUID vs Integer Primary Keys
**Choice**: UUID primary keys for all tables
**Rationale**: Better security, no enumeration attacks, supports distributed architecture
**Trade-offs**: Slightly larger storage, but negligible performance impact with modern PostgreSQL

### Decision 2: JSONB vs Separate Tables
**Choice**: JSONB for flexible metadata, separate tables for structured data
**Rationale**: JSONB provides flexibility for evolving requirements while maintaining query performance
**Trade-offs**: Some queries may be more complex, but indexing support mitigates this

### Decision 3: PostGIS vs Simple Coordinates
**Choice**: PostGIS GEOGRAPHY type for location data
**Rationale**: Optimized geospatial queries, distance calculations, and spatial indexing
**Trade-offs**: Additional extension dependency, but essential for location-based features

### Decision 4: Enum Types vs String Constraints
**Choice**: PostgreSQL ENUM types for fixed value sets
**Rationale**: Better performance, data integrity, and clear schema documentation
**Trade-offs**: Schema changes required for new enum values, but provides better type safety

## Next Steps

1. **Proceed to Planning Phase**: Create detailed implementation plan with SQL scripts
2. **Schema Documentation**: Document all tables, relationships, and constraints
3. **Performance Testing**: Plan performance validation approach
4. **Migration Scripts**: Prepare migration files for schema deployment

---

**Research Status**: ✅ Complete
**Key Decisions**: UUID PKs, JSONB metadata, PostGIS geography, PostgreSQL enums
**Next Phase**: Planning (03-plan-enhanced.md)
**Estimated Implementation Time**: 4-5 hours total
