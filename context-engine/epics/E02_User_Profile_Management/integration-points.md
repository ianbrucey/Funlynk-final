# E02 User & Profile Management - Integration Points

## Overview

This document defines how User & Profile Management services integrate with other epics and external systems. It establishes the data flow patterns, shared functionality, and integration requirements that enable seamless user experience across the platform.

## Integration Architecture

### Core Infrastructure Dependencies

**E01 Core Infrastructure Integration**:
- **Authentication Service**: Profile operations require authenticated users
- **Database Schema**: Direct access to users, follows, and related tables
- **Notification Service**: Follow notifications and profile update alerts
- **Geolocation Service**: Location-based user discovery and privacy filtering

```typescript
// Profile Service integrates with Core Infrastructure
class ProfileService {
  constructor(
    private authService: AuthenticationService,
    private notificationService: NotificationService,
    private geolocationService: GeolocationService
  ) {}
  
  async updateProfile(userId: string, updates: ProfileUpdate): Promise<UserProfile> {
    // Validate user authentication
    const user = await this.authService.getCurrentUser();
    if (user.id !== userId) throw new Error('Unauthorized');
    
    // Validate location coordinates if provided
    if (updates.location_coordinates) {
      const isValid = await this.geolocationService.validateCoordinates(
        updates.location_coordinates.lat,
        updates.location_coordinates.lng
      );
      if (!isValid) throw new Error('Invalid coordinates');
    }
    
    // Update profile
    const updatedProfile = await this.updateUserProfile(userId, updates);
    
    // Send notification to followers about significant changes
    if (this.isSignificantUpdate(updates)) {
      const followers = await this.socialGraphService.getFollowers(userId);
      await this.notificationService.sendBulkNotifications(followers, {
        type: 'profile_updated',
        message: `${updatedProfile.display_name} updated their profile`
      });
    }
    
    return updatedProfile;
  }
}
```

## Epic Integration Details

### E03 Activity Management Integration

**Profile Data for Activities**:
- Host profiles provide credibility and trust signals
- User interests enable activity recommendations
- Location data supports activity discovery
- Social connections influence activity visibility

**Integration Points**:
```typescript
// Activity Service uses Profile Service for host information
class ActivityService {
  async createActivity(activityData: ActivityCreate): Promise<Activity> {
    // Get host profile for validation
    const hostProfile = await this.profileService.getProfile(activityData.host_id);
    
    // Validate host eligibility
    if (!hostProfile.is_host) {
      throw new Error('User must complete host onboarding');
    }
    
    // Create activity with host information
    const activity = await this.createActivityRecord({
      ...activityData,
      host_profile: {
        username: hostProfile.username,
        display_name: hostProfile.display_name,
        profile_image_url: hostProfile.profile_image_url,
        is_verified: hostProfile.is_verified
      }
    });
    
    // Notify followers about new activity
    const followers = await this.socialGraphService.getFollowers(activityData.host_id);
    await this.notificationService.sendBulkNotifications(followers, {
      type: 'new_activity',
      message: `${hostProfile.display_name} created a new activity`
    });
    
    return activity;
  }
}

// Social Graph influences activity recommendations
class ActivityRecommendationEngine {
  async getPersonalizedActivities(userId: string): Promise<Activity[]> {
    const [userProfile, following] = await Promise.all([
      this.profileService.getProfile(userId),
      this.socialGraphService.getFollowing(userId)
    ]);
    
    // Get activities from followed users
    const followingActivities = await this.getActivitiesFromUsers(following.map(u => u.id));
    
    // Get activities matching user interests
    const interestActivities = await this.getActivitiesByInterests(userProfile.interests);
    
    // Get location-based activities
    const nearbyActivities = await this.geolocationService.findActivitiesNearby(
      userProfile.location_coordinates,
      10 // 10km radius
    );
    
    return this.combineAndRankActivities([
      followingActivities,
      interestActivities,
      nearbyActivities
    ]);
  }
}
```

### E04 Discovery Engine Integration

**User Discovery Powers Feed Generation**:
- Social graph determines activity feed content
- User interests influence recommendation algorithms
- Profile completion affects discovery ranking
- Follow relationships create personalized feeds

**Integration Points**:
```typescript
// Discovery Engine uses Social Graph for feed generation
class FeedService {
  async generatePersonalizedFeed(userId: string): Promise<FeedItem[]> {
    const [following, userProfile] = await Promise.all([
      this.socialGraphService.getFollowing(userId),
      this.profileService.getProfile(userId)
    ]);
    
    // Get activities from followed users (high priority)
    const followingFeed = await this.getFollowingActivities(following.map(u => u.id));
    
    // Get recommended activities based on interests
    const interestFeed = await this.getInterestBasedActivities(userProfile.interests);
    
    // Get location-based activities
    const locationFeed = await this.getLocationBasedActivities(
      userProfile.location_coordinates
    );
    
    // Combine feeds with personalized ranking
    return this.rankFeedItems([
      ...followingFeed.map(item => ({ ...item, source: 'following', weight: 1.0 })),
      ...interestFeed.map(item => ({ ...item, source: 'interests', weight: 0.7 })),
      ...locationFeed.map(item => ({ ...item, source: 'location', weight: 0.5 }))
    ]);
  }
}

// Profile data enhances search results
class SearchService {
  async searchUsers(query: string, searcherId: string): Promise<UserSearchResult[]> {
    const searcherProfile = await this.profileService.getProfile(searcherId);
    
    // Basic text search
    const textResults = await this.performTextSearch(query);
    
    // Enhance results with relationship data
    const enhancedResults = await Promise.all(
      textResults.map(async (user) => {
        const [isFollowing, mutualConnections] = await Promise.all([
          this.socialGraphService.isFollowing(searcherId, user.id),
          this.socialGraphService.getMutualConnections(searcherId, user.id)
        ]);
        
        return {
          ...user,
          is_following: isFollowing,
          mutual_connections: mutualConnections.length,
          shared_interests: this.calculateSharedInterests(
            searcherProfile.interests,
            user.interests
          )
        };
      })
    );
    
    // Rank results based on relevance and relationships
    return this.rankSearchResults(enhancedResults, searcherProfile);
  }
}
```

### E05 Social Interaction Integration

**Profile Context for Interactions**:
- User profiles provide context for comments and reactions
- Social relationships determine interaction permissions
- Block functionality prevents unwanted interactions
- Profile verification affects interaction trust

**Integration Points**:
```typescript
// Comment Service uses Profile Service for user context
class CommentService {
  async createComment(commentData: CommentCreate): Promise<Comment> {
    const [commenterProfile, activityHost] = await Promise.all([
      this.profileService.getProfile(commentData.user_id),
      this.activityService.getActivityHost(commentData.activity_id)
    ]);
    
    // Check if commenter is blocked by activity host
    const isBlocked = await this.socialGraphService.isBlocked(
      activityHost.id,
      commentData.user_id
    );
    
    if (isBlocked) {
      throw new Error('Cannot comment on this activity');
    }
    
    // Create comment with profile context
    const comment = await this.createCommentRecord({
      ...commentData,
      commenter_profile: {
        username: commenterProfile.username,
        display_name: commenterProfile.display_name,
        profile_image_url: commenterProfile.profile_image_url,
        is_verified: commenterProfile.is_verified
      }
    });
    
    // Notify activity host (if not self-comment)
    if (commentData.user_id !== activityHost.id) {
      await this.notificationService.sendNotification(activityHost.id, {
        type: 'new_comment',
        message: `${commenterProfile.display_name} commented on your activity`
      });
    }
    
    return comment;
  }
}
```

### E06 Payments & Monetization Integration

**Profile Trust and Payment Capabilities**:
- Profile verification affects payment limits
- Host profiles build trust for paid activities
- Social connections influence payment decisions
- Profile completion affects host eligibility

**Integration Points**:
```typescript
// Payment Service uses Profile Service for trust signals
class PaymentService {
  async processActivityPayment(paymentData: ActivityPayment): Promise<PaymentResult> {
    const [hostProfile, payerProfile] = await Promise.all([
      this.profileService.getProfile(paymentData.host_id),
      this.profileService.getProfile(paymentData.payer_id)
    ]);
    
    // Calculate trust score based on profile and social data
    const hostTrustScore = await this.calculateTrustScore(hostProfile);
    const payerTrustScore = await this.calculateTrustScore(payerProfile);
    
    // Apply risk assessment based on trust scores
    const riskAssessment = this.assessPaymentRisk(hostTrustScore, payerTrustScore);
    
    // Process payment with appropriate safeguards
    return await this.processPaymentWithRisk(paymentData, riskAssessment);
  }
  
  private async calculateTrustScore(profile: UserProfile): Promise<number> {
    let score = 0;
    
    // Profile completion (0-30 points)
    score += profile.profile_completion * 0.3;
    
    // Verification status (0-20 points)
    if (profile.is_verified) score += 20;
    
    // Social connections (0-25 points)
    const socialScore = Math.min(25, profile.follower_count * 0.1);
    score += socialScore;
    
    // Activity history (0-25 points)
    const activityScore = Math.min(25, profile.activity_count * 0.5);
    score += activityScore;
    
    return Math.min(100, score);
  }
}
```

### E07 Administration Integration

**Profile Data for Moderation**:
- Profile reports and moderation actions
- User analytics and behavior tracking
- Account verification and trust management
- Platform-wide user statistics

**Integration Points**:
```typescript
// Admin Service uses Profile Service for user management
class AdminModerationService {
  async reviewProfileReport(reportId: string): Promise<ModerationAction> {
    const report = await this.getProfileReport(reportId);
    const reportedProfile = await this.profileService.getProfile(report.reported_user_id);
    
    // Get user's social graph for context
    const [followers, following, recentActivity] = await Promise.all([
      this.socialGraphService.getFollowers(report.reported_user_id),
      this.socialGraphService.getFollowing(report.reported_user_id),
      this.activityService.getUserRecentActivity(report.reported_user_id)
    ]);
    
    // Analyze user behavior patterns
    const behaviorAnalysis = await this.analyzeBehaviorPatterns({
      profile: reportedProfile,
      social_connections: { followers, following },
      recent_activity: recentActivity,
      report_history: await this.getUserReportHistory(report.reported_user_id)
    });
    
    // Determine moderation action
    const action = this.determineModerationAction(report, behaviorAnalysis);
    
    // Execute moderation action
    return await this.executeModerationAction(action);
  }
}
```

## Shared Data Patterns

### Real-time Profile Updates
```typescript
// Profile changes trigger real-time updates across the platform
class ProfileUpdateBroadcaster {
  async broadcastProfileUpdate(userId: string, changes: ProfileChanges): Promise<void> {
    // Notify followers of significant changes
    if (changes.display_name || changes.profile_image_url) {
      const followers = await this.socialGraphService.getFollowers(userId);
      await this.realtimeService.broadcastToUsers(followers.map(f => f.id), {
        type: 'profile_updated',
        user_id: userId,
        changes: changes
      });
    }
    
    // Update cached profile data
    await this.cacheService.invalidateProfile(userId);
    
    // Update search indexes
    await this.searchService.updateUserIndex(userId, changes);
  }
}
```

### Cross-Epic Privacy Enforcement
```typescript
// Privacy settings affect all epic interactions
class PrivacyEnforcementService {
  async canUserInteract(actorId: string, targetId: string, action: string): Promise<boolean> {
    // Check if users are blocked
    const isBlocked = await this.socialGraphService.isBlocked(targetId, actorId);
    if (isBlocked) return false;
    
    // Get target user's privacy preferences
    const preferences = await this.profileService.getPreferences(targetId);
    
    // Apply privacy rules based on action
    switch (action) {
      case 'view_profile':
        return this.canViewProfile(actorId, targetId, preferences);
      case 'send_message':
        return this.canSendMessage(actorId, targetId, preferences);
      case 'invite_to_activity':
        return this.canInviteToActivity(actorId, targetId, preferences);
      default:
        return false;
    }
  }
}
```

## Performance Considerations

### Caching Strategy
- Profile data cached for 15 minutes
- Follow status cached for 5 minutes
- Recommendation data cached for 1 hour
- Search results cached for 10 minutes

### Database Optimization
- Denormalized follower/following counts for performance
- Spatial indexes for location-based queries
- Composite indexes for common query patterns
- Read replicas for heavy read operations

### Real-time Updates
- WebSocket connections for live follow notifications
- Server-sent events for profile update broadcasts
- Optimistic UI updates with rollback on failure

---

**Integration Points Status**: ✅ Complete
**E02 User & Profile Management Epic Status**: ✅ Complete - Ready for dependent epics
