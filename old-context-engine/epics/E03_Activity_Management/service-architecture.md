# E03 Activity Management - Service Architecture

## Architecture Overview

The Activity Management epic provides three main services that enable the complete activity lifecycle: Activity CRUD Service, Tagging & Category Service, and RSVP & Attendance Service. These services work together to provide comprehensive activity creation, discovery, and participation functionality.

## Service Design Principles

### 1. Activity-Centric Design
All services are designed around the activity lifecycle from creation to completion.

### 2. Real-time Consistency
RSVP operations and capacity management maintain real-time consistency across all clients.

### 3. Scalable Participation
Services are optimized to handle popular activities with hundreds of participants.

### 4. Host Empowerment
Services provide hosts with powerful tools to manage their activities effectively.

## Core Services

### 3.1 Activity CRUD Service

**Purpose**: Manages the complete lifecycle of activities from creation to deletion

**Responsibilities**:
- Activity creation with validation and enrichment
- Activity updates and modifications
- Activity deletion and cancellation workflows
- Activity status management
- Activity image upload and management
- Activity template management

**Service Interface**:
```typescript
interface ActivityCRUDService {
  // Activity Management
  createActivity(activityData: ActivityCreate): Promise<Activity>
  getActivity(activityId: string, viewerId?: string): Promise<Activity>
  updateActivity(activityId: string, updates: ActivityUpdate): Promise<Activity>
  deleteActivity(activityId: string, hostId: string): Promise<void>
  
  // Activity Status Management
  publishActivity(activityId: string, hostId: string): Promise<Activity>
  cancelActivity(activityId: string, hostId: string, reason?: string): Promise<Activity>
  completeActivity(activityId: string, hostId: string): Promise<Activity>
  
  // Activity Images
  uploadActivityImage(activityId: string, imageFile: File): Promise<ActivityImage>
  reorderActivityImages(activityId: string, imageOrder: string[]): Promise<void>
  deleteActivityImage(activityId: string, imageId: string): Promise<void>
  
  // Activity Templates
  getActivityTemplates(category?: string): Promise<ActivityTemplate[]>
  createActivityFromTemplate(templateId: string, customizations: TemplateCustomization): Promise<Activity>
  
  // Host Management
  getHostActivities(hostId: string, filters?: ActivityFilters): Promise<PaginatedActivities>
  getActivityAnalytics(activityId: string, hostId: string): Promise<ActivityAnalytics>
}
```

**Activity Creation Pipeline**:
```typescript
class ActivityCreationPipeline {
  async createActivity(data: ActivityCreate): Promise<Activity> {
    // 1. Validate input data
    await this.validateActivityData(data);
    
    // 2. Verify host permissions
    await this.verifyHostPermissions(data.host_id);
    
    // 3. Geocode and validate location
    const locationData = await this.geolocationService.validateAndGeocode(
      data.location_name,
      data.location_coordinates
    );
    
    // 4. Process and optimize images
    const processedImages = await this.processActivityImages(data.images);
    
    // 5. Create activity record
    const activity = await this.createActivityRecord({
      ...data,
      location_coordinates: locationData.coordinates,
      location_name: locationData.formatted_address,
      status: data.publish_immediately ? 'published' : 'draft'
    });
    
    // 6. Add tags and categories
    if (data.tags?.length > 0) {
      await this.taggingService.addActivityTags(activity.id, data.tags);
    }
    
    // 7. Send notifications if published
    if (activity.status === 'published') {
      await this.notifyFollowersOfNewActivity(activity);
    }
    
    return activity;
  }
  
  private async validateActivityData(data: ActivityCreate): Promise<void> {
    // Validate required fields
    if (!data.title || data.title.trim().length === 0) {
      throw new ValidationError('Activity title is required');
    }
    
    // Validate time constraints
    if (data.start_time <= new Date()) {
      throw new ValidationError('Activity start time must be in the future');
    }
    
    if (data.end_time && data.end_time <= data.start_time) {
      throw new ValidationError('Activity end time must be after start time');
    }
    
    // Validate capacity
    if (data.capacity !== null && data.capacity < 1) {
      throw new ValidationError('Activity capacity must be at least 1');
    }
    
    // Validate pricing
    if (data.price_cents < 0) {
      throw new ValidationError('Activity price cannot be negative');
    }
  }
}
```

### 3.2 Tagging & Category Service

**Purpose**: Provides flexible categorization and tagging for activity organization and discovery

**Responsibilities**:
- Tag creation and management
- Category hierarchy management
- Tag validation and moderation
- Tag popularity tracking
- Activity-tag relationship management
- Tag suggestions and autocomplete

**Service Interface**:
```typescript
interface TaggingCategoryService {
  // Tag Management
  createTag(name: string, category?: string): Promise<Tag>
  getTag(tagId: string): Promise<Tag>
  searchTags(query: string, limit?: number): Promise<Tag[]>
  getPopularTags(category?: string, limit?: number): Promise<Tag[]>
  
  // Category Management
  getCategories(): Promise<CategoryTree>
  getCategoryTags(categoryId: string): Promise<Tag[]>
  
  // Activity Tagging
  addActivityTags(activityId: string, tags: string[]): Promise<void>
  removeActivityTags(activityId: string, tagIds: string[]): Promise<void>
  getActivityTags(activityId: string): Promise<Tag[]>
  
  // Tag Suggestions
  suggestTagsForActivity(activityData: ActivityData): Promise<Tag[]>
  getTagSuggestions(query: string): Promise<TagSuggestion[]>
  
  // Tag Analytics
  getTagUsageStats(tagId: string): Promise<TagUsageStats>
  getTrendingTags(timeframe?: string): Promise<Tag[]>
}
```

**Tag Intelligence Engine**:
```typescript
class TagIntelligenceEngine {
  async suggestTagsForActivity(activityData: ActivityData): Promise<Tag[]> {
    const suggestions: Tag[] = [];
    
    // 1. Extract keywords from title and description
    const keywords = this.extractKeywords(
      `${activityData.title} ${activityData.description}`
    );
    
    // 2. Match keywords to existing tags
    const keywordTags = await this.findTagsByKeywords(keywords);
    suggestions.push(...keywordTags);
    
    // 3. Location-based suggestions
    const locationTags = await this.getLocationBasedTags(activityData.location_name);
    suggestions.push(...locationTags);
    
    // 4. Time-based suggestions (morning, evening, weekend, etc.)
    const timeTags = await this.getTimeBasedTags(activityData.start_time);
    suggestions.push(...timeTags);
    
    // 5. Host's previous activity tags
    const hostTags = await this.getHostFrequentTags(activityData.host_id);
    suggestions.push(...hostTags);
    
    // 6. Remove duplicates and rank by relevance
    return this.rankAndDeduplicateTags(suggestions, activityData);
  }
  
  private extractKeywords(text: string): string[] {
    // Simple keyword extraction (could be enhanced with NLP)
    return text
      .toLowerCase()
      .replace(/[^\w\s]/g, ' ')
      .split(/\s+/)
      .filter(word => word.length > 2)
      .filter(word => !this.isStopWord(word));
  }
  
  private async findTagsByKeywords(keywords: string[]): Promise<Tag[]> {
    const query = `
      SELECT DISTINCT t.*
      FROM tags t
      WHERE t.name = ANY($1)
      OR EXISTS (
        SELECT 1 FROM unnest($1) AS keyword
        WHERE t.name ILIKE '%' || keyword || '%'
      )
      ORDER BY t.usage_count DESC
      LIMIT 10
    `;
    
    return await this.database.query(query, [keywords]);
  }
}
```

### 3.3 RSVP & Attendance Service

**Purpose**: Manages participant registration, attendance tracking, and capacity control

**Responsibilities**:
- RSVP creation and management
- Capacity enforcement and waitlist management
- Attendance tracking and check-in
- RSVP notifications and reminders
- Guest management and group RSVPs
- No-show tracking and penalties

**Service Interface**:
```typescript
interface RSVPAttendanceService {
  // RSVP Management
  createRSVP(rsvpData: RSVPCreate): Promise<RSVPResult>
  cancelRSVP(rsvpId: string, userId: string): Promise<void>
  updateRSVP(rsvpId: string, updates: RSVPUpdate): Promise<RSVP>
  
  // Capacity Management
  checkActivityCapacity(activityId: string): Promise<CapacityInfo>
  promoteFromWaitlist(activityId: string, count?: number): Promise<RSVP[]>
  getWaitlistPosition(activityId: string, userId: string): Promise<number>
  
  // Attendance Tracking
  checkInParticipant(activityId: string, userId: string, method: CheckInMethod): Promise<AttendanceRecord>
  markAttendance(rsvpId: string, attended: boolean): Promise<void>
  getAttendanceStats(activityId: string): Promise<AttendanceStats>
  
  // Participant Management
  getActivityParticipants(activityId: string, filters?: ParticipantFilters): Promise<Participant[]>
  getParticipantDetails(rsvpId: string): Promise<ParticipantDetails>
  
  // User RSVP History
  getUserRSVPs(userId: string, filters?: RSVPFilters): Promise<PaginatedRSVPs>
  getUserAttendanceHistory(userId: string): Promise<AttendanceHistory>
}
```

**RSVP Concurrency Manager**:
```typescript
class RSVPConcurrencyManager {
  async createRSVP(rsvpData: RSVPCreate): Promise<RSVPResult> {
    // Use database transaction with row-level locking
    return await this.database.transaction(async (trx) => {
      // 1. Lock activity row to prevent race conditions
      const activity = await trx.query(
        'SELECT * FROM activities WHERE id = $1 FOR UPDATE',
        [rsvpData.activity_id]
      );
      
      if (!activity) {
        throw new Error('Activity not found');
      }
      
      // 2. Check if user already has RSVP
      const existingRSVP = await trx.query(
        'SELECT id FROM rsvps WHERE activity_id = $1 AND user_id = $2',
        [rsvpData.activity_id, rsvpData.user_id]
      );
      
      if (existingRSVP) {
        throw new Error('User already has RSVP for this activity');
      }
      
      // 3. Check capacity and create RSVP
      const totalGuests = 1 + (rsvpData.guest_count || 0);
      const currentCount = activity.rsvp_count;
      const capacity = activity.capacity;
      
      let status: RSVPStatus;
      let waitlistPosition: number | null = null;
      
      if (capacity === null || currentCount + totalGuests <= capacity) {
        // Has capacity - confirm immediately
        status = 'confirmed';
        
        // Update activity RSVP count
        await trx.query(
          'UPDATE activities SET rsvp_count = rsvp_count + $1 WHERE id = $2',
          [totalGuests, rsvpData.activity_id]
        );
      } else {
        // No capacity - add to waitlist
        status = 'waitlisted';
        
        // Get next waitlist position
        const waitlistResult = await trx.query(
          'SELECT COALESCE(MAX(position), 0) + 1 as position FROM activity_waitlist WHERE activity_id = $1',
          [rsvpData.activity_id]
        );
        waitlistPosition = waitlistResult[0].position;
        
        // Add to waitlist
        await trx.query(
          'INSERT INTO activity_waitlist (activity_id, user_id, position) VALUES ($1, $2, $3)',
          [rsvpData.activity_id, rsvpData.user_id, waitlistPosition]
        );
        
        // Update waitlist count
        await trx.query(
          'UPDATE activities SET waitlist_count = waitlist_count + 1 WHERE id = $1',
          [rsvpData.activity_id]
        );
      }
      
      // 4. Create RSVP record
      const rsvp = await trx.query(
        `INSERT INTO rsvps (activity_id, user_id, status, guest_count, special_requests)
         VALUES ($1, $2, $3, $4, $5) RETURNING *`,
        [rsvpData.activity_id, rsvpData.user_id, status, rsvpData.guest_count, rsvpData.special_requests]
      );
      
      return {
        rsvp: rsvp[0],
        status,
        waitlist_position: waitlistPosition
      };
    });
  }
}
```

## Service Communication Patterns

### Inter-Service Communication
```typescript
// Activity Service uses Tagging Service
class ActivityCRUDService {
  async createActivity(data: ActivityCreate): Promise<Activity> {
    // Create activity
    const activity = await this.createActivityRecord(data);
    
    // Add tags through Tagging Service
    if (data.tags?.length > 0) {
      await this.taggingService.addActivityTags(activity.id, data.tags);
    }
    
    // Get tag suggestions for host
    const suggestedTags = await this.taggingService.suggestTagsForActivity(data);
    
    return {
      ...activity,
      suggested_tags: suggestedTags
    };
  }
}

// RSVP Service triggers notifications
class RSVPAttendanceService {
  async createRSVP(rsvpData: RSVPCreate): Promise<RSVPResult> {
    const result = await this.rsvpConcurrencyManager.createRSVP(rsvpData);
    
    // Send notifications based on RSVP status
    if (result.status === 'confirmed') {
      await this.notificationService.sendNotification(rsvpData.user_id, {
        type: 'rsvp_confirmed',
        message: 'Your RSVP has been confirmed!'
      });
      
      // Notify host of new participant
      const activity = await this.activityService.getActivity(rsvpData.activity_id);
      await this.notificationService.sendNotification(activity.host_id, {
        type: 'new_participant',
        message: `Someone joined your activity: ${activity.title}`
      });
    } else if (result.status === 'waitlisted') {
      await this.notificationService.sendNotification(rsvpData.user_id, {
        type: 'rsvp_waitlisted',
        message: `You're #${result.waitlist_position} on the waitlist`
      });
    }
    
    return result;
  }
}
```

## Performance Optimizations

### Activity Query Optimization
```typescript
class ActivityQueryOptimizer {
  async getActivitiesWithDetails(filters: ActivityFilters): Promise<Activity[]> {
    // Single query to get activities with all related data
    const query = `
      SELECT 
        a.*,
        u.username as host_username,
        u.display_name as host_display_name,
        u.profile_image_url as host_profile_image,
        u.is_verified as host_is_verified,
        array_agg(DISTINCT t.name) FILTER (WHERE t.name IS NOT NULL) as tags,
        array_agg(DISTINCT ai.image_url) FILTER (WHERE ai.image_url IS NOT NULL) as images,
        COUNT(DISTINCT r.id) as confirmed_rsvps
      FROM activities a
      JOIN users u ON a.host_id = u.id
      LEFT JOIN activity_tags at ON a.id = at.activity_id
      LEFT JOIN tags t ON at.tag_id = t.id
      LEFT JOIN activity_images ai ON a.id = ai.activity_id AND ai.upload_status = 'ready'
      LEFT JOIN rsvps r ON a.id = r.activity_id AND r.status = 'confirmed'
      WHERE a.status = 'published'
      AND a.start_time > NOW()
      GROUP BY a.id, u.username, u.display_name, u.profile_image_url, u.is_verified
      ORDER BY a.start_time ASC
    `;
    
    return await this.database.query(query);
  }
}
```

### RSVP Performance Optimization
```typescript
class RSVPPerformanceOptimizer {
  // Batch RSVP status checks
  async getBatchRSVPStatus(userId: string, activityIds: string[]): Promise<Record<string, RSVPStatus>> {
    const query = `
      SELECT activity_id, status
      FROM rsvps
      WHERE user_id = $1 AND activity_id = ANY($2)
    `;
    
    const results = await this.database.query(query, [userId, activityIds]);
    
    // Convert to lookup object
    const rsvpStatus: Record<string, RSVPStatus> = {};
    activityIds.forEach(id => rsvpStatus[id] = null);
    results.forEach(row => rsvpStatus[row.activity_id] = row.status);
    
    return rsvpStatus;
  }
}
```

## Error Handling and Resilience

### Activity Service Error Handling
```typescript
class ActivityServiceErrors {
  static ACTIVITY_NOT_FOUND = 'ACTIVITY_NOT_FOUND';
  static UNAUTHORIZED_HOST = 'UNAUTHORIZED_HOST';
  static INVALID_ACTIVITY_TIME = 'INVALID_ACTIVITY_TIME';
  static CAPACITY_EXCEEDED = 'CAPACITY_EXCEEDED';
  static ACTIVITY_CANCELLED = 'ACTIVITY_CANCELLED';
  static IMAGE_UPLOAD_FAILED = 'IMAGE_UPLOAD_FAILED';
}

class ActivityErrorHandler {
  async handleActivityCreation(error: Error, activityData: ActivityCreate): Promise<void> {
    if (error.message.includes('geocoding')) {
      // Retry with fallback geocoding service
      await this.retryWithFallbackGeocoding(activityData);
    } else if (error.message.includes('image')) {
      // Continue without images, process them async
      await this.processImagesAsync(activityData);
    } else {
      // Log error and notify user
      this.logger.error('Activity creation failed', { error, activityData });
      throw error;
    }
  }
}
```

---

**Service Architecture Status**: ✅ Complete
**Next Steps**: Define API contracts for activity management operations
