# E03 Activity Management - Integration Points

## Overview

This document defines how Activity Management services integrate with other epics and external systems. It establishes the data flow patterns, shared functionality, and integration requirements that enable seamless activity lifecycle management across the platform.

## Integration Architecture

### Core Infrastructure Dependencies

**E01 Core Infrastructure Integration**:
- **Authentication Service**: Activity operations require authenticated users and host verification
- **Database Schema**: Direct access to activities, RSVPs, tags, and related tables
- **Notification Service**: Activity notifications, RSVP confirmations, and reminders
- **Geolocation Service**: Location validation, geocoding, and proximity-based features

```typescript
// Activity Service integrates with Core Infrastructure
class ActivityCRUDService {
  constructor(
    private authService: AuthenticationService,
    private notificationService: NotificationService,
    private geolocationService: GeolocationService
  ) {}
  
  async createActivity(activityData: ActivityCreate): Promise<Activity> {
    // Validate user authentication and host permissions
    const user = await this.authService.getCurrentUser();
    if (!user.is_host && activityData.price_cents > 0) {
      throw new Error('User must complete host onboarding for paid activities');
    }
    
    // Validate and geocode location
    const locationData = await this.geolocationService.validateAndGeocode(
      activityData.location_name,
      activityData.location_coordinates
    );
    
    // Create activity with validated location
    const activity = await this.createActivityRecord({
      ...activityData,
      location_coordinates: locationData.coordinates,
      location_name: locationData.formatted_address
    });
    
    // Send notifications to followers if published
    if (activity.status === 'published') {
      const followers = await this.socialGraphService.getFollowers(user.id);
      await this.notificationService.sendBulkNotifications(followers, {
        type: 'new_activity',
        title: 'New Activity',
        message: `${user.display_name} created: ${activity.title}`,
        data: { activity_id: activity.id }
      });
    }
    
    return activity;
  }
}
```

### User & Profile Management Dependencies

**E02 User & Profile Management Integration**:
- **Profile Service**: Host profiles provide credibility and trust signals
- **Social Graph Service**: Follower relationships enable activity notifications
- **User Preferences**: Interest matching for activity recommendations

```typescript
// Activity creation leverages user profile data
class ActivityEnrichmentService {
  async enrichActivityWithHostData(activity: Activity): Promise<EnrichedActivity> {
    const hostProfile = await this.profileService.getProfile(activity.host_id);
    
    return {
      ...activity,
      host: {
        id: hostProfile.id,
        username: hostProfile.username,
        display_name: hostProfile.display_name,
        profile_image_url: hostProfile.profile_image_url,
        is_verified: hostProfile.is_verified,
        host_rating: await this.calculateHostRating(hostProfile.id),
        activities_hosted: hostProfile.activity_count
      }
    };
  }
  
  async getPersonalizedActivityRecommendations(userId: string): Promise<Activity[]> {
    const [userProfile, following] = await Promise.all([
      this.profileService.getProfile(userId),
      this.socialGraphService.getFollowing(userId)
    ]);
    
    // Get activities from followed users
    const followingActivities = await this.getActivitiesFromHosts(following.map(u => u.id));
    
    // Get activities matching user interests
    const interestActivities = await this.getActivitiesByTags(userProfile.interests);
    
    // Get location-based activities
    const nearbyActivities = await this.geolocationService.findActivitiesNearby(
      userProfile.location_coordinates,
      15 // 15km radius
    );
    
    return this.combineAndRankActivities([
      followingActivities,
      interestActivities,
      nearbyActivities
    ]);
  }
}
```

## Epic Integration Details

### E04 Discovery Engine Integration

**Activity Data Powers Discovery**:
- Activity metadata enables search and filtering
- Tag data supports category-based discovery
- RSVP data influences activity popularity ranking
- Location data enables proximity-based recommendations

**Integration Points**:
```typescript
// Discovery Engine uses Activity Management for search
class ActivityDiscoveryService {
  async searchActivities(query: SearchQuery, userId: string): Promise<SearchResult[]> {
    // Get user context for personalization
    const userProfile = await this.profileService.getProfile(userId);
    
    // Build search filters
    const filters: ActivityFilters = {
      text_query: query.text,
      tags: query.tags,
      location: query.location || userProfile.location_coordinates,
      radius: query.radius || 25,
      start_time_after: query.start_time || new Date(),
      price_range: query.price_range,
      skill_level: query.skill_level
    };
    
    // Execute search with ranking
    const activities = await this.activityService.searchActivities(filters);
    
    // Enhance with user-specific data
    const enhancedActivities = await Promise.all(
      activities.map(async (activity) => {
        const [rsvpStatus, hostRelationship] = await Promise.all([
          this.rsvpService.getUserRSVPStatus(userId, activity.id),
          this.socialGraphService.isFollowing(userId, activity.host_id)
        ]);
        
        return {
          ...activity,
          user_rsvp_status: rsvpStatus,
          following_host: hostRelationship,
          relevance_score: this.calculateRelevanceScore(activity, userProfile)
        };
      })
    );
    
    return this.rankSearchResults(enhancedActivities, userProfile);
  }
  
  async getFeedActivities(userId: string): Promise<FeedActivity[]> {
    const [following, userInterests] = await Promise.all([
      this.socialGraphService.getFollowing(userId),
      this.profileService.getProfile(userId).then(p => p.interests)
    ]);
    
    // Get activities from followed hosts (high priority)
    const followingActivities = await this.activityService.getActivitiesFromHosts(
      following.map(u => u.id)
    );
    
    // Get activities matching interests (medium priority)
    const interestActivities = await this.activityService.getActivitiesByTags(userInterests);
    
    // Get trending activities (low priority)
    const trendingActivities = await this.activityService.getTrendingActivities();
    
    // Combine and rank for personalized feed
    return this.generatePersonalizedFeed([
      ...followingActivities.map(a => ({ ...a, source: 'following', weight: 1.0 })),
      ...interestActivities.map(a => ({ ...a, source: 'interests', weight: 0.7 })),
      ...trendingActivities.map(a => ({ ...a, source: 'trending', weight: 0.3 }))
    ]);
  }
}
```

### E05 Social Interaction Integration

**Activity Context for Social Features**:
- Activity comments and reactions
- RSVP-based interaction permissions
- Host-participant relationship management
- Activity-specific social features

**Integration Points**:
```typescript
// Social Interaction uses Activity context
class ActivitySocialService {
  async createActivityComment(commentData: ActivityCommentCreate): Promise<Comment> {
    // Get activity and validate permissions
    const activity = await this.activityService.getActivity(commentData.activity_id);
    const userRSVP = await this.rsvpService.getUserRSVP(commentData.user_id, activity.id);
    
    // Check comment permissions based on activity settings
    const canComment = await this.checkCommentPermissions(
      commentData.user_id,
      activity,
      userRSVP
    );
    
    if (!canComment) {
      throw new Error('User cannot comment on this activity');
    }
    
    // Create comment with activity context
    const comment = await this.commentService.createComment({
      ...commentData,
      context_type: 'activity',
      context_id: activity.id
    });
    
    // Notify relevant users
    await this.notifyActivityComment(comment, activity, userRSVP);
    
    return comment;
  }
  
  private async checkCommentPermissions(
    userId: string,
    activity: Activity,
    userRSVP: RSVP | null
  ): Promise<boolean> {
    // Host can always comment
    if (userId === activity.host_id) return true;
    
    // Check activity comment settings
    if (activity.comment_permissions === 'participants_only') {
      return userRSVP?.status === 'confirmed';
    }
    
    if (activity.comment_permissions === 'followers_only') {
      return await this.socialGraphService.isFollowing(activity.host_id, userId);
    }
    
    // Public commenting allowed
    return activity.comment_permissions === 'public';
  }
}
```

### E06 Payments & Monetization Integration

**Activity Pricing and Payment Processing**:
- Paid activity creation and management
- RSVP-triggered payment processing
- Host payout calculations
- Payment-gated activity access

**Integration Points**:
```typescript
// Payment Service integrates with Activity Management
class ActivityPaymentService {
  async createPaidActivityRSVP(rsvpData: PaidRSVPCreate): Promise<RSVPResult> {
    const activity = await this.activityService.getActivity(rsvpData.activity_id);
    
    if (activity.price_cents > 0) {
      // Create payment intent before RSVP
      const paymentIntent = await this.paymentService.createPaymentIntent({
        amount: activity.price_cents,
        currency: activity.currency,
        connected_account: activity.host.stripe_account_id,
        metadata: {
          activity_id: activity.id,
          user_id: rsvpData.user_id,
          guest_count: rsvpData.guest_count
        }
      });
      
      // Create RSVP with payment pending
      const rsvp = await this.rsvpService.createRSVP({
        ...rsvpData,
        payment_status: 'pending',
        payment_intent_id: paymentIntent.id
      });
      
      return {
        rsvp,
        payment_required: true,
        payment_intent: paymentIntent
      };
    } else {
      // Free activity - create RSVP directly
      return await this.rsvpService.createRSVP(rsvpData);
    }
  }
  
  async handlePaymentSuccess(paymentIntentId: string): Promise<void> {
    // Find RSVP by payment intent
    const rsvp = await this.rsvpService.getRSVPByPaymentIntent(paymentIntentId);
    
    // Confirm RSVP and update payment status
    await this.rsvpService.updateRSVP(rsvp.id, {
      payment_status: 'completed',
      status: 'confirmed'
    });
    
    // Update activity capacity if moving from waitlist
    if (rsvp.status === 'waitlisted') {
      await this.rsvpService.promoteFromWaitlist(rsvp.activity_id, 1);
    }
    
    // Send confirmation notifications
    await this.notificationService.sendNotification(rsvp.user_id, {
      type: 'payment_confirmed',
      message: 'Payment successful! You\'re registered for the activity.'
    });
  }
}
```

### E07 Administration Integration

**Activity Moderation and Analytics**:
- Activity content moderation
- Host performance tracking
- Platform-wide activity analytics
- Automated policy enforcement

**Integration Points**:
```typescript
// Admin Service uses Activity Management for moderation
class ActivityModerationService {
  async reviewActivityReport(reportId: string): Promise<ModerationAction> {
    const report = await this.getActivityReport(reportId);
    const activity = await this.activityService.getActivity(report.activity_id);
    
    // Analyze activity content and host behavior
    const [contentAnalysis, hostHistory] = await Promise.all([
      this.analyzeActivityContent(activity),
      this.getHostModerationHistory(activity.host_id)
    ]);
    
    // Check for policy violations
    const violations = await this.checkPolicyViolations(activity, contentAnalysis);
    
    // Determine moderation action
    const action = this.determineModerationAction(violations, hostHistory);
    
    // Execute moderation action
    if (action.type === 'remove_activity') {
      await this.activityService.removeActivity(activity.id, action.reason);
      await this.notifyParticipantsOfCancellation(activity, action.reason);
    } else if (action.type === 'suspend_host') {
      await this.userService.suspendUser(activity.host_id, action.duration);
    }
    
    return action;
  }
  
  async getActivityAnalytics(timeframe: string): Promise<ActivityAnalytics> {
    const activities = await this.activityService.getActivitiesInTimeframe(timeframe);
    
    return {
      total_activities: activities.length,
      activities_by_category: this.groupByCategory(activities),
      average_capacity: this.calculateAverageCapacity(activities),
      rsvp_conversion_rate: await this.calculateRSVPConversionRate(activities),
      top_hosts: await this.getTopHostsByActivity(activities),
      geographic_distribution: this.analyzeGeographicDistribution(activities)
    };
  }
}
```

## Shared Data Patterns

### Real-time Activity Updates
```typescript
// Activity changes trigger real-time updates
class ActivityUpdateBroadcaster {
  async broadcastActivityUpdate(activityId: string, changes: ActivityChanges): Promise<void> {
    // Get all participants for this activity
    const participants = await this.rsvpService.getActivityParticipants(activityId);
    
    // Broadcast to all participants
    await this.realtimeService.broadcastToUsers(
      participants.map(p => p.user_id),
      {
        type: 'activity_updated',
        activity_id: activityId,
        changes: changes
      }
    );
    
    // Update cached activity data
    await this.cacheService.invalidateActivity(activityId);
    
    // Update search indexes
    await this.searchService.updateActivityIndex(activityId, changes);
  }
  
  async broadcastRSVPUpdate(activityId: string, rsvpChange: RSVPChange): Promise<void> {
    // Broadcast capacity changes to all viewers
    await this.realtimeService.broadcastToChannel(`activity:${activityId}`, {
      type: 'rsvp_updated',
      activity_id: activityId,
      new_rsvp_count: rsvpChange.new_count,
      capacity_status: rsvpChange.capacity_status
    });
  }
}
```

### Cross-Epic Data Synchronization
```typescript
// Activity data synchronization across epics
class ActivityDataSyncService {
  async syncActivityWithProfile(hostId: string): Promise<void> {
    // Update host's activity count
    const activityCount = await this.activityService.getHostActivityCount(hostId);
    await this.profileService.updateProfile(hostId, {
      activity_count: activityCount
    });
  }
  
  async syncRSVPWithPayments(rsvpId: string): Promise<void> {
    const rsvp = await this.rsvpService.getRSVP(rsvpId);
    
    if (rsvp.payment_status === 'completed') {
      // Update payment analytics
      await this.paymentService.recordActivityPayment({
        activity_id: rsvp.activity_id,
        amount: rsvp.activity.price_cents,
        host_id: rsvp.activity.host_id
      });
    }
  }
}
```

## Performance Considerations

### Activity Query Optimization
- Spatial indexes for location-based queries
- Tag indexes for category filtering
- Composite indexes for common search patterns
- Read replicas for heavy read operations

### RSVP Concurrency Management
- Row-level locking for capacity management
- Optimistic concurrency control for popular activities
- Real-time updates with conflict resolution
- Queue-based processing for high-traffic events

### Caching Strategy
- Activity data cached for 10 minutes
- RSVP status cached for 2 minutes
- Tag data cached for 1 hour
- Search results cached for 5 minutes

---

**Integration Points Status**: ✅ Complete
**E03 Activity Management Epic Status**: ✅ Complete - Ready for dependent epics
