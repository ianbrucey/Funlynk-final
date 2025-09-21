# T06 Save Collections & Personal Recommendations

## Problem Definition

### Task Overview
Implement comprehensive save/bookmark system with collections and personal recommendation engine that enables users to organize, rediscover, and get personalized suggestions based on their saved activities. This includes building intelligent collection management and recommendation algorithms that drive continued engagement.

### Problem Statement
Users need effective ways to save and rediscover activities that:
- **Enable easy saving**: Provide one-click saving with immediate feedback
- **Support organization**: Allow users to create collections and categorize saved activities
- **Drive rediscovery**: Help users find and engage with previously saved activities
- **Provide personalization**: Generate recommendations based on saved activity patterns
- **Enhance engagement**: Use save data to improve overall platform personalization

### Scope
**In Scope:**
- Activity save/bookmark functionality with collections
- Personal collection creation and management
- Save-based recommendation algorithms
- Saved activity rediscovery and notification features
- Save analytics and user behavior tracking
- Integration with discovery and recommendation systems

**Out of Scope:**
- Comment saving features (handled by F01)
- Sharing saved activities (covered in T03)
- Community collections (handled by F03)
- Monetization of saved content (handled by E06)

### Success Criteria
- [ ] Save functionality achieves 40%+ adoption rate among active users
- [ ] Saved activities have 60%+ return engagement rate
- [ ] Collections drive 25% increase in user session duration
- [ ] Save-based recommendations improve relevance by 30%
- [ ] Personal recommendations increase activity discovery by 35%
- [ ] Save data enhances overall platform personalization by 20%

### Dependencies
- **Requires**: T02 Backend sharing infrastructure for save data storage
- **Requires**: T04 Reaction system for engagement correlation
- **Requires**: E03 Activity data for save content and metadata
- **Requires**: E04 Discovery engine for recommendation integration
- **Blocks**: Complete personal engagement experience
- **Informs**: Platform-wide personalization and recommendation systems

### Acceptance Criteria

#### Save/Bookmark System
- [ ] One-click save functionality with immediate visual feedback
- [ ] Save status persistence across app sessions and devices
- [ ] Bulk save operations and management tools
- [ ] Save history and activity tracking
- [ ] Save removal and management capabilities

#### Collection Management
- [ ] Personal collection creation with custom names and descriptions
- [ ] Activity organization into multiple collections
- [ ] Collection sharing and privacy controls
- [ ] Collection browsing and search functionality
- [ ] Smart collection suggestions based on save patterns

#### Personal Recommendations
- [ ] Save-based activity recommendation algorithms
- [ ] Similar activity suggestions based on saved content
- [ ] Personalized discovery based on save patterns
- [ ] Time-based recommendations (seasonal, trending)
- [ ] Cross-category recommendations for interest expansion

#### Rediscovery Features
- [ ] Saved activity browsing with filtering and sorting
- [ ] Reminder notifications for upcoming saved activities
- [ ] Save anniversary and milestone notifications
- [ ] Saved activity sharing and recommendation to friends
- [ ] Save-based activity calendar integration

#### Analytics Integration
- [ ] Save behavior tracking and analysis
- [ ] Collection usage analytics and optimization
- [ ] Save-to-engagement conversion tracking
- [ ] Personal recommendation effectiveness measurement
- [ ] Save data integration with platform-wide analytics

### Estimated Effort
**3-4 hours** for experienced full-stack developer

### Task Breakdown
1. **Save System & Collections** (90 minutes)
   - Build save/bookmark functionality with backend storage
   - Implement collection creation and management
   - Create save analytics and behavior tracking
   - Add save-based user profiling and insights

2. **Personal Recommendations** (90 minutes)
   - Build save-based recommendation algorithms
   - Implement personalized discovery and suggestions
   - Create rediscovery features and notifications
   - Add recommendation effectiveness tracking

3. **Frontend & Integration** (60 minutes)
   - Implement save and collection frontend components
   - Add recommendation display and interaction
   - Create comprehensive testing and validation
   - Build integration with discovery systems

### Deliverables
- [ ] Save/bookmark system with backend storage
- [ ] Personal collection creation and management
- [ ] Save-based recommendation algorithms
- [ ] Rediscovery features and notifications
- [ ] Save analytics and behavior tracking
- [ ] Frontend save and collection components
- [ ] Personal recommendation display and interaction
- [ ] Integration with discovery and recommendation systems
- [ ] Save data enhancement for platform personalization

### Technical Specifications

#### Save System Backend
```typescript
interface SavedActivity {
  id: string;
  userId: string;
  activityId: string;
  collectionIds: string[];
  notes?: string;
  tags: string[];
  savedAt: Date;
  lastAccessedAt?: Date;
  accessCount: number;
  
  // Metadata for recommendations
  saveReason?: 'interested' | 'planning' | 'backup' | 'recommendation';
  saveContext?: SaveContext;
}

interface Collection {
  id: string;
  userId: string;
  name: string;
  description?: string;
  isPrivate: boolean;
  activityCount: number;
  createdAt: Date;
  updatedAt: Date;
  
  // Collection metadata
  tags: string[];
  color?: string;
  icon?: string;
  sortOrder: number;
}

class SaveService {
  async saveActivity(
    userId: string,
    activityId: string,
    options: SaveActivityOptions = {}
  ): Promise<SavedActivity> {
    // Check if already saved
    const existingSave = await this.getUserActivitySave(userId, activityId);
    if (existingSave) {
      // Update existing save
      return await this.updateSave(existingSave.id, options);
    }
    
    // Create new save
    const savedActivity: SavedActivity = {
      id: generateId(),
      userId,
      activityId,
      collectionIds: options.collectionIds || [],
      notes: options.notes,
      tags: options.tags || [],
      savedAt: new Date(),
      accessCount: 0,
      saveReason: options.reason || 'interested',
      saveContext: options.context,
    };
    
    await this.storeSavedActivity(savedActivity);
    
    // Update collection counts
    if (savedActivity.collectionIds.length > 0) {
      await this.updateCollectionCounts(savedActivity.collectionIds);
    }
    
    // Track save event
    await this.trackSaveEvent(savedActivity);
    
    // Update user profile for recommendations
    await this.updateUserSaveProfile(userId, savedActivity);
    
    return savedActivity;
  }
  
  async createCollection(
    userId: string,
    collectionData: CreateCollectionRequest
  ): Promise<Collection> {
    const collection: Collection = {
      id: generateId(),
      userId,
      name: collectionData.name,
      description: collectionData.description,
      isPrivate: collectionData.isPrivate ?? true,
      activityCount: 0,
      createdAt: new Date(),
      updatedAt: new Date(),
      tags: collectionData.tags || [],
      color: collectionData.color,
      icon: collectionData.icon,
      sortOrder: await this.getNextSortOrder(userId),
    };
    
    await this.storeCollection(collection);
    
    // Track collection creation
    await this.trackCollectionEvent({
      userId,
      collectionId: collection.id,
      eventType: 'created',
    });
    
    return collection;
  }
  
  async getUserSavedActivities(
    userId: string,
    options: GetSavedActivitiesOptions = {}
  ): Promise<SavedActivityWithDetails[]> {
    const savedActivities = await this.db.savedActivities.findMany({
      where: {
        userId,
        collectionIds: options.collectionId ? { has: options.collectionId } : undefined,
        tags: options.tags ? { hasSome: options.tags } : undefined,
      },
      include: {
        activity: {
          include: {
            host: true,
            location: true,
            category: true,
          },
        },
      },
      orderBy: options.sortBy === 'saved_date' ? { savedAt: 'desc' } :
               options.sortBy === 'activity_date' ? { activity: { startTime: 'asc' } } :
               { lastAccessedAt: 'desc' },
      take: options.limit || 50,
      skip: options.offset || 0,
    });
    
    // Update access tracking
    await this.updateAccessTracking(savedActivities.map(s => s.id));
    
    return savedActivities;
  }
  
  async generateSmartCollectionSuggestions(
    userId: string
  ): Promise<CollectionSuggestion[]> {
    const savedActivities = await this.getUserSavedActivities(userId);
    const suggestions: CollectionSuggestion[] = [];
    
    // Analyze save patterns
    const categoryGroups = this.groupByCategory(savedActivities);
    const locationGroups = this.groupByLocation(savedActivities);
    const timeGroups = this.groupByTimePattern(savedActivities);
    
    // Suggest category-based collections
    for (const [category, activities] of categoryGroups.entries()) {
      if (activities.length >= 3) {
        suggestions.push({
          type: 'category',
          name: `${category} Activities`,
          description: `Collection for your ${activities.length} saved ${category.toLowerCase()} activities`,
          activityIds: activities.map(a => a.activityId),
          confidence: Math.min(activities.length / 10, 1.0),
        });
      }
    }
    
    // Suggest location-based collections
    for (const [location, activities] of locationGroups.entries()) {
      if (activities.length >= 2) {
        suggestions.push({
          type: 'location',
          name: `${location} Activities`,
          description: `Activities you've saved in ${location}`,
          activityIds: activities.map(a => a.activityId),
          confidence: Math.min(activities.length / 5, 1.0),
        });
      }
    }
    
    return suggestions.sort((a, b) => b.confidence - a.confidence);
  }
}
```

#### Personal Recommendation Engine
```typescript
class SaveBasedRecommendationEngine {
  async generatePersonalRecommendations(
    userId: string,
    limit: number = 20
  ): Promise<PersonalRecommendation[]> {
    const userSaveProfile = await this.getUserSaveProfile(userId);
    const savedActivities = await this.getUserSavedActivities(userId);
    
    // Generate recommendations from multiple sources
    const [
      similarActivityRecs,
      categoryExpansionRecs,
      collaborativeRecs,
      trendingRecs,
    ] = await Promise.all([
      this.getSimilarActivityRecommendations(savedActivities),
      this.getCategoryExpansionRecommendations(userSaveProfile),
      this.getCollaborativeRecommendations(userId, userSaveProfile),
      this.getTrendingRecommendations(userSaveProfile),
    ]);
    
    // Combine and score recommendations
    const allRecommendations = [
      ...similarActivityRecs,
      ...categoryExpansionRecs,
      ...collaborativeRecs,
      ...trendingRecs,
    ];
    
    // Remove duplicates and already saved activities
    const filteredRecs = this.filterRecommendations(allRecommendations, savedActivities);
    
    // Score and rank recommendations
    const scoredRecs = await this.scoreRecommendations(filteredRecs, userSaveProfile);
    
    return scoredRecs
      .sort((a, b) => b.score - a.score)
      .slice(0, limit);
  }
  
  private async getSimilarActivityRecommendations(
    savedActivities: SavedActivity[]
  ): Promise<PersonalRecommendation[]> {
    const recommendations: PersonalRecommendation[] = [];
    
    for (const saved of savedActivities.slice(0, 10)) { // Top 10 recent saves
      const similarActivities = await this.findSimilarActivities(saved.activityId);
      
      for (const similar of similarActivities.slice(0, 3)) {
        recommendations.push({
          activityId: similar.id,
          type: 'similar_to_saved',
          score: similar.similarityScore * 0.8, // Base score for similar activities
          reason: `Similar to "${saved.activity.title}" which you saved`,
          confidence: similar.similarityScore,
          sourceActivityId: saved.activityId,
        });
      }
    }
    
    return recommendations;
  }
  
  private async getCategoryExpansionRecommendations(
    userProfile: UserSaveProfile
  ): Promise<PersonalRecommendation[]> {
    const recommendations: PersonalRecommendation[] = [];
    
    // Find adjacent categories to user's interests
    const adjacentCategories = await this.findAdjacentCategories(
      userProfile.topCategories
    );
    
    for (const category of adjacentCategories) {
      const topActivities = await this.getTopActivitiesInCategory(category.id);
      
      for (const activity of topActivities.slice(0, 2)) {
        recommendations.push({
          activityId: activity.id,
          type: 'category_expansion',
          score: category.relevanceScore * activity.popularityScore,
          reason: `Popular ${category.name} activity - expanding your interests`,
          confidence: category.relevanceScore,
          sourceCategory: category.name,
        });
      }
    }
    
    return recommendations;
  }
  
  private async getCollaborativeRecommendations(
    userId: string,
    userProfile: UserSaveProfile
  ): Promise<PersonalRecommendation[]> {
    // Find users with similar save patterns
    const similarUsers = await this.findSimilarSaveUsers(userId, userProfile);
    
    const recommendations: PersonalRecommendation[] = [];
    
    for (const similarUser of similarUsers.slice(0, 5)) {
      const theirSaves = await this.getUserSavedActivities(similarUser.userId);
      
      for (const save of theirSaves.slice(0, 3)) {
        recommendations.push({
          activityId: save.activityId,
          type: 'collaborative',
          score: similarUser.similarity * 0.7,
          reason: 'Saved by users with similar interests',
          confidence: similarUser.similarity,
          sourceUserId: similarUser.userId,
        });
      }
    }
    
    return recommendations;
  }
  
  async generateRediscoveryNotifications(userId: string): Promise<RediscoveryNotification[]> {
    const savedActivities = await this.getUserSavedActivities(userId);
    const notifications: RediscoveryNotification[] = [];
    
    // Find upcoming saved activities
    const upcomingActivities = savedActivities.filter(save => {
      const activity = save.activity;
      const daysUntil = (activity.startTime.getTime() - Date.now()) / (1000 * 60 * 60 * 24);
      return daysUntil > 0 && daysUntil <= 7; // Within next week
    });
    
    for (const save of upcomingActivities) {
      notifications.push({
        type: 'upcoming_saved',
        activityId: save.activityId,
        title: 'Saved Activity Coming Up!',
        message: `"${save.activity.title}" is happening soon`,
        scheduledFor: new Date(save.activity.startTime.getTime() - 24 * 60 * 60 * 1000), // 1 day before
      });
    }
    
    // Find forgotten saves (saved but not accessed recently)
    const forgottenSaves = savedActivities.filter(save => {
      const daysSinceAccess = save.lastAccessedAt ? 
        (Date.now() - save.lastAccessedAt.getTime()) / (1000 * 60 * 60 * 24) : 
        (Date.now() - save.savedAt.getTime()) / (1000 * 60 * 60 * 24);
      
      return daysSinceAccess > 30 && save.accessCount < 3;
    });
    
    if (forgottenSaves.length > 0) {
      const randomSave = forgottenSaves[Math.floor(Math.random() * forgottenSaves.length)];
      notifications.push({
        type: 'rediscover_saved',
        activityId: randomSave.activityId,
        title: 'Rediscover Your Saved Activity',
        message: `Remember "${randomSave.activity.title}"? You saved it ${this.formatTimeAgo(randomSave.savedAt)}`,
        scheduledFor: new Date(Date.now() + 24 * 60 * 60 * 1000), // Tomorrow
      });
    }
    
    return notifications;
  }
}
```

### Quality Checklist
- [ ] Save functionality is intuitive and provides immediate feedback
- [ ] Collections enable effective organization and management of saved activities
- [ ] Personal recommendations are relevant and drive engagement
- [ ] Rediscovery features help users reconnect with saved content
- [ ] Save analytics provide insights for platform optimization
- [ ] Integration with discovery systems enhances overall personalization
- [ ] Performance optimized for users with large numbers of saves
- [ ] Privacy controls respect user preferences for collection sharing

---

**Status**: 🔄 Ready for Problem Definition Phase  
**Assignee**: Full-Stack Developer  
**Epic**: E05 Social Interaction  
**Feature**: F02 Social Sharing & Engagement  
**Dependencies**: T02 Backend Infrastructure, T04 Reaction System, Activity Data (E03), Discovery Engine (E04)  
**Blocks**: Complete Personal Engagement Experience
