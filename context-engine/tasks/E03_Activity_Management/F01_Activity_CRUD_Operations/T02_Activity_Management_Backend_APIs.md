# T02 Activity Management Backend APIs

## Problem Definition

### Task Overview
Implement comprehensive backend APIs for activity lifecycle management including creation, reading, updating, and deletion operations. This includes validation, business logic, database operations, and integration with other platform services.

### Problem Statement
The platform needs robust backend services to:
- **Manage activity lifecycle**: Handle all CRUD operations with proper validation and business logic
- **Ensure data integrity**: Validate activity data and maintain consistency across related systems
- **Support real-time updates**: Enable live updates for activity changes and status modifications
- **Handle concurrency**: Manage simultaneous operations on activities safely
- **Integrate services**: Connect with geolocation, notifications, user profiles, and other platform services

### Scope
**In Scope:**
- Activity CRUD API endpoints with comprehensive validation
- Activity status management (draft, published, cancelled, completed)
- Location validation and geocoding integration
- Activity search and filtering APIs for host management
- Real-time activity updates with Supabase subscriptions
- Integration with notification service for activity changes
- Activity analytics and metrics tracking

**Out of Scope:**
- Image upload and management (covered in T04)
- RSVP and attendance management (covered in F02)
- Payment processing integration (handled by E06)
- Advanced search and discovery (handled by E04)

### Success Criteria
- [ ] All activity operations complete in under 300ms
- [ ] API endpoints handle 1000+ concurrent requests efficiently
- [ ] Data validation prevents 99%+ of invalid activity states
- [ ] Real-time updates propagate within 2 seconds
- [ ] System scales to support 100,000+ activities
- [ ] Integration with external services is reliable and fault-tolerant

### Dependencies
- **Requires**: E01.F01 Database schema with activities table
- **Requires**: E01.F02 Authentication system for user verification
- **Requires**: E01.F03 Geolocation service for location validation
- **Requires**: E01.F04 Notification service for activity updates
- **Requires**: E02.F01 User profiles for host information
- **Blocks**: T03 Frontend implementation needs backend APIs
- **Blocks**: T04 Image management needs core activity APIs

### Acceptance Criteria

#### Activity CRUD Operations
- [ ] Create activity endpoint with comprehensive validation
- [ ] Read activity endpoint with filtering and pagination
- [ ] Update activity endpoint with partial updates and optimistic locking
- [ ] Delete activity endpoint with cascade handling and soft delete
- [ ] Bulk operations support for efficient activity management

#### Activity Status Management
- [ ] Status transitions follow defined business rules
- [ ] Status changes trigger appropriate notifications
- [ ] Draft activities are private until published
- [ ] Cancelled activities handle existing RSVPs appropriately
- [ ] Completed activities preserve historical data

#### Location & Validation
- [ ] Location validation with geocoding and address verification
- [ ] Geographic search capabilities for nearby activities
- [ ] Location privacy controls for sensitive activities
- [ ] Timezone handling for multi-timezone activities
- [ ] Address standardization and formatting

#### Real-time Updates
- [ ] Activity changes broadcast to subscribed clients
- [ ] Efficient subscription management with proper cleanup
- [ ] Conflict resolution for simultaneous updates
- [ ] Real-time capacity and RSVP count updates
- [ ] Status change notifications to relevant users

#### Integration & Performance
- [ ] Seamless integration with user profile and authentication
- [ ] Notification triggers for activity lifecycle events
- [ ] Database query optimization with proper indexing
- [ ] Caching strategy for frequently accessed activities
- [ ] Error handling with appropriate HTTP status codes

### Estimated Effort
**4 hours** for experienced backend developer

### Task Breakdown
1. **API Design & Database Operations** (120 minutes)
   - Design RESTful API endpoints for all activity operations
   - Implement database CRUD operations with Supabase
   - Add comprehensive validation and business logic
   - Create activity status management system

2. **Location & Integration Services** (90 minutes)
   - Integrate with geolocation service for location validation
   - Add notification triggers for activity lifecycle events
   - Implement real-time updates with Supabase subscriptions
   - Create activity search and filtering capabilities

3. **Performance & Quality** (30 minutes)
   - Optimize database queries and add proper indexing
   - Implement caching strategy for performance
   - Add comprehensive error handling and logging
   - Create API documentation and testing

### Deliverables
- [ ] Activity CRUD API endpoints with full documentation
- [ ] Activity status management system
- [ ] Location validation and geocoding integration
- [ ] Real-time activity update subscriptions
- [ ] Activity search and filtering APIs
- [ ] Integration with notification service
- [ ] Database migration scripts and optimizations
- [ ] Unit tests with 90%+ code coverage
- [ ] API performance benchmarks and monitoring

### Technical Specifications

#### Database Schema Enhancements
```sql
-- Enhanced activities table (building on E01.F01)
ALTER TABLE activities ADD COLUMN IF NOT EXISTS
  status VARCHAR(20) DEFAULT 'draft' CHECK (status IN ('draft', 'published', 'cancelled', 'completed')),
  capacity INTEGER,
  current_participants INTEGER DEFAULT 0,
  waitlist_enabled BOOLEAN DEFAULT false,
  requirements TEXT,
  equipment_needed TEXT,
  skill_level VARCHAR(20) CHECK (skill_level IN ('beginner', 'intermediate', 'advanced', 'all')),
  age_restriction VARCHAR(50),
  weather_policy TEXT,
  cancellation_policy TEXT,
  template_id UUID REFERENCES activity_templates(id),
  is_template BOOLEAN DEFAULT false,
  view_count INTEGER DEFAULT 0,
  updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW();

-- Activity templates table
CREATE TABLE activity_templates (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  name VARCHAR(100) NOT NULL,
  description TEXT,
  category_id UUID REFERENCES categories(id),
  template_data JSONB NOT NULL,
  is_public BOOLEAN DEFAULT false,
  created_by UUID REFERENCES users(id),
  usage_count INTEGER DEFAULT 0,
  created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Activity analytics table
CREATE TABLE activity_analytics (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  activity_id UUID REFERENCES activities(id) ON DELETE CASCADE,
  event_type VARCHAR(50) NOT NULL,
  user_id UUID REFERENCES users(id) ON DELETE SET NULL,
  metadata JSONB,
  created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);
```

#### API Endpoints
```typescript
// Core CRUD operations
POST   /api/activities              // Create new activity
GET    /api/activities              // List activities with filtering
GET    /api/activities/:id          // Get specific activity
PUT    /api/activities/:id          // Update activity
DELETE /api/activities/:id          // Delete activity

// Status management
PUT    /api/activities/:id/status   // Update activity status
POST   /api/activities/:id/publish  // Publish draft activity
POST   /api/activities/:id/cancel   // Cancel activity
POST   /api/activities/:id/complete // Mark activity as completed

// Host management
GET    /api/activities/my-activities // Get current user's activities
GET    /api/activities/:id/analytics // Get activity analytics
POST   /api/activities/:id/duplicate // Duplicate existing activity

// Search and filtering
GET    /api/activities/search       // Search activities
GET    /api/activities/nearby       // Get nearby activities
GET    /api/activities/by-category/:categoryId // Get activities by category
```

#### Activity Data Model
```typescript
interface Activity {
  id: string;
  title: string;
  description: string;
  host_id: string;
  category_id?: string;
  location: {
    latitude: number;
    longitude: number;
    address: string;
    venue_name?: string;
  };
  start_time: Date;
  end_time: Date;
  timezone: string;
  status: 'draft' | 'published' | 'cancelled' | 'completed';
  capacity?: number;
  current_participants: number;
  waitlist_enabled: boolean;
  requirements?: string;
  equipment_needed?: string;
  skill_level?: 'beginner' | 'intermediate' | 'advanced' | 'all';
  age_restriction?: string;
  weather_policy?: string;
  cancellation_policy?: string;
  template_id?: string;
  is_template: boolean;
  view_count: number;
  created_at: Date;
  updated_at: Date;
}

interface ActivityCreateRequest {
  title: string;
  description: string;
  category_id?: string;
  location: LocationInput;
  start_time: Date;
  end_time: Date;
  timezone: string;
  capacity?: number;
  waitlist_enabled?: boolean;
  requirements?: string;
  equipment_needed?: string;
  skill_level?: string;
  age_restriction?: string;
  weather_policy?: string;
  cancellation_policy?: string;
  template_id?: string;
  tags?: string[];
}
```

#### Business Logic Rules
```typescript
// Activity validation rules
const ACTIVITY_VALIDATION = {
  title: { minLength: 5, maxLength: 100 },
  description: { minLength: 20, maxLength: 2000 },
  capacity: { min: 1, max: 10000 },
  duration: { min: 15, max: 1440 }, // 15 minutes to 24 hours
  advance_booking: { min: 30, max: 365 * 24 * 60 }, // 30 minutes to 1 year
};

// Status transition rules
const STATUS_TRANSITIONS = {
  draft: ['published', 'cancelled'],
  published: ['cancelled', 'completed'],
  cancelled: [], // Terminal state
  completed: [], // Terminal state
};

// Notification triggers
const NOTIFICATION_EVENTS = {
  activity_published: ['followers', 'interested_users'],
  activity_updated: ['participants', 'waitlist'],
  activity_cancelled: ['participants', 'waitlist'],
  activity_reminder: ['participants'],
};
```

### Quality Checklist
- [ ] All API endpoints follow RESTful conventions
- [ ] Comprehensive input validation prevents invalid data
- [ ] Database queries are optimized with proper indexing
- [ ] Real-time subscriptions are efficient and properly managed
- [ ] Error handling provides appropriate HTTP status codes
- [ ] Integration with external services includes retry logic
- [ ] API documentation is complete and accurate
- [ ] Unit tests cover all business logic and edge cases

---

**Status**: 🔄 Ready for Problem Definition Phase  
**Assignee**: Backend Developer  
**Epic**: E03 Activity Management  
**Feature**: F01 Activity CRUD Operations  
**Dependencies**: Database Schema (E01.F01), Auth (E01.F02), Geolocation (E01.F03), Notifications (E01.F04)  
**Blocks**: T03 Frontend Implementation, T04 Image Management
