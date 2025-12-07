# E01 Core Infrastructure - Integration Points

## Overview

This document defines how other epics integrate with Core Infrastructure services. It establishes the data flow patterns, event-driven interactions, and shared infrastructure that enables the entire platform to function cohesively.

## Integration Architecture

### Data Flow Patterns

#### 1. Request-Response Pattern
Direct API calls for immediate data needs:
- User authentication checks
- Geolocation queries
- Notification delivery
- Database CRUD operations

#### 2. Event-Driven Pattern
Database triggers and real-time subscriptions for automatic actions:
- User registration → welcome notification
- Activity creation → follower notifications
- RSVP changes → host notifications
- Payment completion → attendance confirmation

#### 3. Shared Database Pattern
Direct database access through Supabase client:
- All epics share the same database schema
- Row Level Security ensures data isolation
- Real-time subscriptions for live updates

## Epic Integration Details

### E02 User & Profile Management Integration

**Dependencies on Core Infrastructure**:
- **Authentication Service**: User login/logout, session management
- **Database Schema**: Users, follows tables
- **Notification Service**: Follow notifications, profile update alerts

**Integration Points**:
```typescript
// Profile Service uses Authentication Service
const user = await authService.getCurrentUser();
const profile = await profileService.getProfile(user.id);

// Social Graph Service uses Notification Service
await followService.followUser(targetUserId);
await notificationService.sendNotification(targetUserId, {
  type: 'new_follower',
  message: `${currentUser.display_name} started following you`
});
```

**Data Flow**:
1. User authenticates → Authentication Service validates
2. Profile operations → Direct database access with RLS
3. Follow actions → Database triggers update counters + send notifications

### E03 Activity Management Integration

**Dependencies on Core Infrastructure**:
- **Authentication Service**: Host verification, user permissions
- **Database Schema**: Activities, RSVPs, comments tables
- **Geolocation Service**: Location validation, spatial queries
- **Notification Service**: Activity updates, RSVP notifications

**Integration Points**:
```typescript
// Activity Creation Flow
const activity = await activityService.createActivity({
  title: "Pickup Basketball",
  location_coordinates: await geolocationService.validateCoordinates(lat, lng),
  host_id: currentUser.id
});

// Notify followers of new activity
const followers = await socialGraphService.getFollowers(currentUser.id);
await notificationService.sendBulkNotifications(followers, {
  type: 'new_activity',
  message: `${currentUser.display_name} created a new activity`
});
```

**Data Flow**:
1. Activity creation → Geolocation validation → Database insert
2. RSVP actions → Database triggers update counters → Notification to host
3. Location searches → Geolocation Service spatial queries

### E04 Discovery Engine Integration

**Dependencies on Core Infrastructure**:
- **Authentication Service**: User context for personalized feeds
- **Database Schema**: All tables for feed generation
- **Geolocation Service**: Location-based discovery
- **Notification Service**: Feed update notifications

**Integration Points**:
```typescript
// Feed Generation
const nearbyActivities = await geolocationService.findActivitiesNearby(
  userLocation, 
  radiusKm
);

const followingActivities = await feedService.getFollowingFeed(
  currentUser.id,
  socialGraphService.getFollowing(currentUser.id)
);

// Search Integration
const searchResults = await searchService.searchActivities({
  location: userLocation,
  radius: 10,
  tags: ['basketball', 'sports']
});
```

**Data Flow**:
1. User opens app → Authentication check → Location permission
2. Feed generation → Multiple database queries + geolocation filtering
3. Search queries → Geolocation + text search + filtering

### E05 Social Interaction Integration

**Dependencies on Core Infrastructure**:
- **Authentication Service**: Comment author verification
- **Database Schema**: Comments, notifications tables
- **Notification Service**: Comment notifications, flare responses

**Integration Points**:
```typescript
// Comment System
await commentService.createComment({
  activity_id: activityId,
  user_id: currentUser.id,
  content: commentText
});

// Notify activity host and participants
await notificationService.sendNotification(activity.host_id, {
  type: 'new_comment',
  message: `${currentUser.display_name} commented on your activity`
});

// Flare System
const flare = await flareService.createFlare(flareData);
const interestedUsers = await geolocationService.findUsersNearby(
  flare.location_coordinates,
  flare.radius
);
await notificationService.sendBulkNotifications(interestedUsers, {
  type: 'new_flare',
  message: 'Someone is looking for activity partners nearby'
});
```

### E06 Payments & Monetization Integration

**Dependencies on Core Infrastructure**:
- **Authentication Service**: Host verification, payment authorization
- **Database Schema**: Users (stripe_account_id), RSVPs (payment data)
- **Notification Service**: Payment confirmations, payout notifications

**Integration Points**:
```typescript
// Stripe Connect Integration
const stripeAccount = await paymentsService.createConnectAccount(hostId);
await userService.updateUser(hostId, {
  stripe_account_id: stripeAccount.id,
  is_host: true
});

// Payment Processing
const paymentIntent = await paymentsService.createPaymentIntent({
  amount: activity.price_cents,
  connected_account: activity.host.stripe_account_id
});

// Payment Success Webhook
await rsvpService.confirmPayment(rsvpId, paymentIntentId);
await notificationService.sendNotification(userId, {
  type: 'payment_confirmed',
  message: 'Your payment was successful! You\'re registered for the activity.'
});
```

### E07 Administration Integration

**Dependencies on Core Infrastructure**:
- **Authentication Service**: Admin role verification
- **Database Schema**: All tables for moderation and analytics
- **Notification Service**: Moderation alerts, system notifications

**Integration Points**:
```typescript
// Content Moderation
const report = await moderationService.createReport({
  reporter_id: currentUser.id,
  reported_activity_id: activityId,
  reason: 'inappropriate_content'
});

await notificationService.sendNotification(adminUserId, {
  type: 'new_report',
  message: 'New content report requires review'
});

// Admin Dashboard Analytics
const analytics = await analyticsService.getDashboardData({
  user_count: await userService.getTotalUsers(),
  activity_count: await activityService.getTotalActivities(),
  payment_volume: await paymentsService.getPaymentVolume()
});
```

## Shared Infrastructure Components

### Real-time Subscriptions

**Activity Updates**:
```typescript
// Subscribe to activity changes
const subscription = supabase
  .channel('activity_updates')
  .on('postgres_changes', {
    event: 'UPDATE',
    schema: 'public',
    table: 'activities',
    filter: `id=eq.${activityId}`
  }, (payload) => {
    // Update UI with activity changes
    updateActivityDisplay(payload.new);
  })
  .subscribe();
```

**Notification Updates**:
```typescript
// Subscribe to new notifications
const notificationSubscription = supabase
  .channel('user_notifications')
  .on('postgres_changes', {
    event: 'INSERT',
    schema: 'public',
    table: 'notifications',
    filter: `user_id=eq.${currentUser.id}`
  }, (payload) => {
    // Show new notification in UI
    showNotificationToast(payload.new);
  })
  .subscribe();
```

### Caching Strategy

**User Data Caching**:
- Cache user profiles for 15 minutes
- Cache authentication status for session duration
- Invalidate cache on profile updates

**Location Data Caching**:
- Cache geolocation queries for 5 minutes
- Cache geocoding results for 24 hours
- Use location-based cache keys

**Activity Data Caching**:
- Cache activity lists for 2 minutes
- Cache individual activities for 10 minutes
- Invalidate cache on activity updates

### Error Handling Patterns

**Service Degradation**:
```typescript
// Graceful degradation for geolocation service
try {
  const nearbyActivities = await geolocationService.findActivitiesNearby(location, radius);
} catch (error) {
  // Fallback to non-location-based feed
  const fallbackActivities = await activityService.getRecentActivities();
  logger.warn('Geolocation service unavailable, using fallback', { error });
}
```

**Notification Failures**:
```typescript
// Retry logic for notification delivery
const maxRetries = 3;
let attempt = 0;

while (attempt < maxRetries) {
  try {
    await notificationService.sendPushNotification(userId, notification);
    break;
  } catch (error) {
    attempt++;
    if (attempt === maxRetries) {
      // Store for later retry or use alternative delivery method
      await notificationService.queueForRetry(userId, notification);
    }
    await delay(Math.pow(2, attempt) * 1000); // Exponential backoff
  }
}
```

## Performance Considerations

### Database Query Optimization
- Use proper indexes for common query patterns
- Implement query result caching
- Use database connection pooling
- Monitor slow query performance

### API Rate Limiting
- Implement per-user rate limiting
- Use sliding window rate limiting
- Provide rate limit headers in responses
- Implement graceful degradation on rate limit hits

### Real-time Performance
- Limit real-time subscription scope
- Use database triggers efficiently
- Implement client-side debouncing
- Monitor subscription connection health

---

**Integration Points Status**: ✅ Complete
**E01 Core Infrastructure Epic Status**: ✅ Complete - Ready for dependent epics
