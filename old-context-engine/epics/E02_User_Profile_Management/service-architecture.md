# E02 User & Profile Management - Service Architecture

## Architecture Overview

The User & Profile Management epic provides two main services that build on Core Infrastructure: the Profile Service and the Social Graph Service. These services work together to provide comprehensive user identity management and social networking capabilities.

## Service Design Principles

### 1. User-Centric Design
All services prioritize user privacy, control, and experience while enabling social discovery.

### 2. Privacy by Design
Privacy controls are built into every service operation, not added as an afterthought.

### 3. Performance Optimization
Services are optimized for the most common user operations: viewing profiles and managing follows.

### 4. Scalable Social Graph
Social graph operations are designed to scale to thousands of connections per user.

## Core Services

### 2.1 Profile Service

**Purpose**: Manages comprehensive user profile data, preferences, and profile-related operations

**Responsibilities**:
- User profile CRUD operations
- Profile image upload and management
- User preferences and privacy settings
- Profile completion tracking and gamification
- User discovery and search
- Profile verification and badges

**Service Interface**:
```typescript
interface ProfileService {
  // Profile Management
  getProfile(userId: string, viewerId?: string): Promise<UserProfile>
  updateProfile(userId: string, updates: ProfileUpdate): Promise<UserProfile>
  deleteProfile(userId: string): Promise<void>
  
  // Profile Images
  uploadProfileImage(userId: string, imageFile: File): Promise<ProfileImage>
  updatePrimaryImage(userId: string, imageId: string): Promise<void>
  deleteProfileImage(userId: string, imageId: string): Promise<void>
  
  // User Preferences
  getPreferences(userId: string): Promise<UserPreferences>
  updatePreferences(userId: string, preferences: Partial<UserPreferences>): Promise<UserPreferences>
  
  // User Discovery
  searchUsers(query: string, filters?: UserSearchFilters): Promise<UserSearchResult[]>
  getUsersNearby(location: Coordinates, radius: number): Promise<NearbyUser[]>
  getUsersByInterests(interests: string[], limit?: number): Promise<User[]>
  
  // Profile Analytics
  getProfileCompletion(userId: string): Promise<ProfileCompletion>
  getProfileViews(userId: string, timeframe?: string): Promise<ProfileAnalytics>
}
```

**Implementation Architecture**:
- **Data Layer**: Direct Supabase database access with RLS policies
- **Storage Layer**: Supabase Storage for profile images with CDN
- **Caching Layer**: Redis for frequently accessed profiles (15-minute TTL)
- **Image Processing**: Automatic resizing and optimization pipeline

**Privacy Enforcement**:
```typescript
class ProfilePrivacyService {
  async canViewProfile(viewerId: string, targetUserId: string): Promise<boolean> {
    const targetUser = await this.getUser(targetUserId);
    const preferences = await this.getPreferences(targetUserId);
    
    // Public profiles are always viewable
    if (preferences.profile_visibility === 'public') return true;
    
    // Private profiles only viewable by owner
    if (preferences.profile_visibility === 'private') {
      return viewerId === targetUserId;
    }
    
    // Followers-only profiles
    if (preferences.profile_visibility === 'followers') {
      return viewerId === targetUserId || 
             await this.socialGraphService.isFollowing(viewerId, targetUserId);
    }
    
    return false;
  }
  
  async filterProfileData(profile: UserProfile, viewerId: string): Promise<UserProfile> {
    const canView = await this.canViewProfile(viewerId, profile.id);
    if (!canView) throw new Error('Access denied');
    
    const preferences = await this.getPreferences(profile.id);
    
    // Filter location based on privacy settings
    if (preferences.location_visibility === 'hidden') {
      profile.location_name = null;
      profile.location_coordinates = null;
    } else if (preferences.location_visibility === 'city') {
      profile.location_coordinates = null; // Keep city name only
    }
    
    return profile;
  }
}
```

### 2.2 Social Graph Service

**Purpose**: Manages social connections, relationships, and social discovery features

**Responsibilities**:
- Follow/unfollow operations
- Follower and following list management
- Follow recommendations and suggestions
- Mutual connection detection
- Social graph analytics
- Block and report functionality

**Service Interface**:
```typescript
interface SocialGraphService {
  // Follow Operations
  followUser(followerId: string, targetUserId: string): Promise<FollowResult>
  unfollowUser(followerId: string, targetUserId: string): Promise<void>
  isFollowing(followerId: string, targetUserId: string): Promise<boolean>
  areMutualFollowers(user1Id: string, user2Id: string): Promise<boolean>
  
  // Follower Management
  getFollowers(userId: string, pagination: Pagination): Promise<PaginatedUsers>
  getFollowing(userId: string, pagination: Pagination): Promise<PaginatedUsers>
  getFollowerCount(userId: string): Promise<number>
  getFollowingCount(userId: string): Promise<number>
  
  // Follow Recommendations
  getFollowRecommendations(userId: string, limit?: number): Promise<RecommendedUser[]>
  getRecommendationsByMutualConnections(userId: string): Promise<RecommendedUser[]>
  getRecommendationsByInterests(userId: string): Promise<RecommendedUser[]>
  getRecommendationsByLocation(userId: string): Promise<RecommendedUser[]>
  
  // Block Management
  blockUser(blockerId: string, targetUserId: string, reason?: string): Promise<void>
  unblockUser(blockerId: string, targetUserId: string): Promise<void>
  isBlocked(blockerId: string, targetUserId: string): Promise<boolean>
  getBlockedUsers(userId: string): Promise<User[]>
}
```

**Follow Recommendation Engine**:
```typescript
class FollowRecommendationEngine {
  async generateRecommendations(userId: string, limit: number = 10): Promise<RecommendedUser[]> {
    const [
      mutualRecommendations,
      interestRecommendations,
      locationRecommendations
    ] = await Promise.all([
      this.getMutualConnectionRecommendations(userId, limit / 3),
      this.getInterestBasedRecommendations(userId, limit / 3),
      this.getLocationBasedRecommendations(userId, limit / 3)
    ]);
    
    // Combine and deduplicate recommendations
    const allRecommendations = [
      ...mutualRecommendations,
      ...interestRecommendations,
      ...locationRecommendations
    ];
    
    // Score and rank recommendations
    return this.scoreAndRankRecommendations(userId, allRecommendations, limit);
  }
  
  private async scoreAndRankRecommendations(
    userId: string, 
    recommendations: RecommendedUser[], 
    limit: number
  ): Promise<RecommendedUser[]> {
    const scoredRecommendations = recommendations.map(rec => ({
      ...rec,
      score: this.calculateRecommendationScore(rec)
    }));
    
    return scoredRecommendations
      .sort((a, b) => b.score - a.score)
      .slice(0, limit);
  }
  
  private calculateRecommendationScore(recommendation: RecommendedUser): number {
    let score = 0;
    
    // Mutual connections (highest weight)
    score += recommendation.mutual_connections * 10;
    
    // Shared interests
    score += recommendation.shared_interests * 5;
    
    // Location proximity (closer = higher score)
    if (recommendation.distance_km !== null) {
      score += Math.max(0, 50 - recommendation.distance_km);
    }
    
    // User activity level
    score += recommendation.follower_count * 0.1;
    score += recommendation.activity_count * 0.5;
    
    return score;
  }
}
```

## Service Communication Patterns

### Inter-Service Communication
```typescript
// Profile Service uses Social Graph Service
class ProfileService {
  async getEnhancedProfile(userId: string, viewerId: string): Promise<EnhancedProfile> {
    const [profile, isFollowing, mutualConnections] = await Promise.all([
      this.getProfile(userId, viewerId),
      this.socialGraphService.isFollowing(viewerId, userId),
      this.socialGraphService.getMutualConnections(viewerId, userId)
    ]);
    
    return {
      ...profile,
      relationship: {
        is_following: isFollowing,
        mutual_connections: mutualConnections.length,
        mutual_connection_previews: mutualConnections.slice(0, 3)
      }
    };
  }
}
```

### Event-Driven Operations
```typescript
// Follow operations trigger multiple side effects
class SocialGraphService {
  async followUser(followerId: string, targetUserId: string): Promise<FollowResult> {
    // 1. Create follow relationship
    const follow = await this.createFollow(followerId, targetUserId);
    
    // 2. Update counters (handled by database triggers)
    
    // 3. Send notification
    await this.notificationService.sendNotification(targetUserId, {
      type: 'new_follower',
      title: 'New Follower',
      message: `${followerUser.display_name} started following you`,
      data: { follower_id: followerId }
    });
    
    // 4. Update recommendation cache
    await this.updateRecommendationCache(followerId, targetUserId);
    
    return { success: true, follow };
  }
}
```

## Performance Optimizations

### Caching Strategy
```typescript
class ProfileCacheService {
  private readonly PROFILE_CACHE_TTL = 15 * 60; // 15 minutes
  private readonly FOLLOW_STATUS_CACHE_TTL = 5 * 60; // 5 minutes
  
  async getCachedProfile(userId: string): Promise<UserProfile | null> {
    const cacheKey = `profile:${userId}`;
    return await this.redis.get(cacheKey);
  }
  
  async setCachedProfile(userId: string, profile: UserProfile): Promise<void> {
    const cacheKey = `profile:${userId}`;
    await this.redis.setex(cacheKey, this.PROFILE_CACHE_TTL, JSON.stringify(profile));
  }
  
  async invalidateProfileCache(userId: string): Promise<void> {
    const cacheKey = `profile:${userId}`;
    await this.redis.del(cacheKey);
  }
}
```

### Database Query Optimization
```typescript
class SocialGraphQueryOptimizer {
  // Optimized follower list with pagination
  async getFollowersPaginated(userId: string, limit: number, offset: number): Promise<PaginatedUsers> {
    const query = `
      SELECT 
        u.id, u.username, u.display_name, u.profile_image_url,
        f.created_at as followed_at,
        COUNT(*) OVER() as total_count
      FROM follows f
      JOIN users u ON f.follower_id = u.id
      WHERE f.following_id = $1
      AND u.is_active = TRUE
      ORDER BY f.created_at DESC
      LIMIT $2 OFFSET $3
    `;
    
    return await this.database.query(query, [userId, limit, offset]);
  }
  
  // Batch follow status checks
  async getBatchFollowStatus(viewerId: string, targetUserIds: string[]): Promise<Record<string, boolean>> {
    const query = `
      SELECT following_id, true as is_following
      FROM follows
      WHERE follower_id = $1 AND following_id = ANY($2)
    `;
    
    const results = await this.database.query(query, [viewerId, targetUserIds]);
    
    // Convert to lookup object
    const followStatus: Record<string, boolean> = {};
    targetUserIds.forEach(id => followStatus[id] = false);
    results.forEach(row => followStatus[row.following_id] = true);
    
    return followStatus;
  }
}
```

## Security and Privacy

### Privacy Enforcement
- All profile access goes through privacy checks
- Location data is filtered based on user preferences
- Follower lists respect privacy settings
- Block functionality prevents all interactions

### Rate Limiting
- Follow operations: 50 per hour per user
- Profile updates: 10 per hour per user
- Search operations: 100 per hour per user
- Image uploads: 5 per hour per user

### Content Moderation
- Profile images are scanned for inappropriate content
- Bio text is filtered for spam and inappropriate content
- Automated detection of fake profiles
- User reporting system for manual review

## Error Handling

### Common Error Scenarios
```typescript
class ProfileServiceErrors {
  static PROFILE_NOT_FOUND = 'PROFILE_NOT_FOUND';
  static ACCESS_DENIED = 'ACCESS_DENIED';
  static INVALID_IMAGE_FORMAT = 'INVALID_IMAGE_FORMAT';
  static IMAGE_TOO_LARGE = 'IMAGE_TOO_LARGE';
  static RATE_LIMIT_EXCEEDED = 'RATE_LIMIT_EXCEEDED';
}

class SocialGraphServiceErrors {
  static ALREADY_FOLLOWING = 'ALREADY_FOLLOWING';
  static NOT_FOLLOWING = 'NOT_FOLLOWING';
  static CANNOT_FOLLOW_SELF = 'CANNOT_FOLLOW_SELF';
  static USER_BLOCKED = 'USER_BLOCKED';
  static FOLLOW_LIMIT_EXCEEDED = 'FOLLOW_LIMIT_EXCEEDED';
}
```

## Monitoring and Analytics

### Key Metrics
- Profile completion rates
- Profile view counts
- Follow/unfollow rates
- Recommendation click-through rates
- Image upload success rates
- Search query performance

### Health Checks
- Database connection health
- Image upload pipeline status
- Cache service availability
- Recommendation engine performance

---

**Service Architecture Status**: ✅ Complete
**Next Steps**: Define API contracts for profile and social graph operations
