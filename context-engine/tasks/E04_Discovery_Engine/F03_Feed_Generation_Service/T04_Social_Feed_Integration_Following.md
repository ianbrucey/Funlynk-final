# T04 Social Feed Integration & Following

## Problem Definition

### Task Overview
Implement social feed features and following-based content curation that integrates social connections, friend activities, and following relationships into personalized feed experiences. This includes building systems that surface relevant social content while respecting privacy and providing meaningful social discovery.

### Problem Statement
Users need social feed experiences that:
- **Surface friend activities**: Show relevant activities and updates from social connections
- **Enable following-based discovery**: Curate content from followed hosts, organizers, and interests
- **Maintain privacy**: Respect user privacy settings while enabling social discovery
- **Provide social context**: Show social proof and friend engagement with activities
- **Foster community**: Build connections through shared activity interests and participation

### Scope
**In Scope:**
- Social feed generation with friend activity integration
- Following-based content curation and feed personalization
- Social context and engagement indicators in feeds
- Privacy-compliant social content processing
- Social discovery and friend recommendation in feeds
- Following management and content preference controls
- Social feed analytics and engagement tracking

**Out of Scope:**
- Social networking core features (handled by E05)
- User profile and connection management (handled by E02)
- Basic feed infrastructure (covered in T02 and T03)
- Real-time infrastructure (covered in T06)

### Success Criteria
- [ ] Social feeds improve engagement by 40% over non-social feeds
- [ ] Following-based content achieves 50%+ click-through rate
- [ ] Social context increases activity RSVP rate by 30%
- [ ] Privacy controls maintain user trust while enabling social features
- [ ] Social discovery drives 25%+ new connection formation
- [ ] Following feeds reduce content discovery time by 35%

### Dependencies
- **Requires**: T02 Feed generation backend for social integration
- **Requires**: T03 Feed frontend components for social UI
- **Requires**: E02 User profiles and social connections
- **Requires**: E05 Social features for social graph data
- **Blocks**: Complete social feed experience
- **Informs**: T05 Feed analytics (social engagement metrics)

### Acceptance Criteria

#### Social Feed Generation
- [ ] Friend activity aggregation and relevance scoring
- [ ] Social context integration (friends going, friends interested)
- [ ] Social proof indicators and engagement metrics
- [ ] Privacy-aware social content filtering
- [ ] Social feed personalization based on relationship strength

#### Following-Based Curation
- [ ] Content curation from followed hosts and organizers
- [ ] Following-based activity recommendations
- [ ] Interest-based following and content discovery
- [ ] Location-based following for local content
- [ ] Following feed customization and preference controls

#### Social Context & Discovery
- [ ] Friend engagement indicators on activities
- [ ] Social activity recommendations and suggestions
- [ ] Friend discovery through shared activity interests
- [ ] Social milestone celebrations and notifications
- [ ] Community-based social content curation

#### Privacy & Controls
- [ ] Granular privacy controls for social feed content
- [ ] Following visibility and privacy settings
- [ ] Social content opt-out mechanisms
- [ ] Anonymous social signals where appropriate
- [ ] Social feed audit and transparency tools

#### Integration Features
- [ ] Seamless integration with main feed experience
- [ ] Social feed tab and navigation
- [ ] Social content filtering and customization
- [ ] Social engagement actions (like, comment, share)
- [ ] Social notification integration with feeds

### Estimated Effort
**3-4 hours** for experienced full-stack developer

### Task Breakdown
1. **Social Feed Backend Integration** (90 minutes)
   - Build social content aggregation and scoring
   - Implement following-based content curation
   - Create privacy-compliant social data processing
   - Add social context and engagement tracking

2. **Following System & Discovery** (90 minutes)
   - Build following management and content preferences
   - Implement social discovery and friend recommendations
   - Create community-based content curation
   - Add social milestone and celebration features

3. **Frontend Integration & Privacy** (60 minutes)
   - Integrate social feeds with main feed interface
   - Build privacy controls and transparency features
   - Add social engagement actions and interactions
   - Create comprehensive testing and validation

### Deliverables
- [ ] Social feed generation with friend activity integration
- [ ] Following-based content curation and personalization
- [ ] Social context and engagement indicators
- [ ] Privacy controls and transparency for social feeds
- [ ] Social discovery and friend recommendation features
- [ ] Following management and content preference controls
- [ ] Social feed analytics and engagement tracking
- [ ] Integration with main feed experience
- [ ] Social engagement actions and interactions

### Technical Specifications

#### Social Feed Generation
```typescript
interface SocialFeedItem extends FeedItem {
  socialContext: {
    friendsGoing: FriendInfo[];
    friendsInterested: FriendInfo[];
    socialProof: SocialProof;
    relationshipStrength: number;
    socialEngagement: SocialEngagement;
  };
  followingContext?: {
    followedHost: HostInfo;
    followedCategory: CategoryInfo;
    followingReason: string;
  };
}

class SocialFeedGenerator {
  async generateSocialFeed(
    userId: string,
    feedType: 'social' | 'following',
    limit: number = 20
  ): Promise<SocialFeedItem[]> {
    const userProfile = await this.getUserProfile(userId);
    const socialGraph = await this.getUserSocialGraph(userId);
    
    let contentSources: Promise<SocialFeedItem[]>[] = [];
    
    if (feedType === 'social') {
      contentSources = [
        this.getFriendActivities(userId, socialGraph),
        this.getSocialUpdates(userId, socialGraph),
        this.getSocialRecommendations(userId, socialGraph),
      ];
    } else if (feedType === 'following') {
      contentSources = [
        this.getFollowedHostContent(userId),
        this.getFollowedCategoryContent(userId),
        this.getFollowedLocationContent(userId),
      ];
    }
    
    const contentArrays = await Promise.all(contentSources);
    const allContent = contentArrays.flat();
    
    // Score and rank social content
    const scoredContent = await this.scoreSocialContent(allContent, userProfile, socialGraph);
    
    // Apply social feed optimization
    const optimizedFeed = await this.optimizeSocialFeed(scoredContent, feedType);
    
    return optimizedFeed.slice(0, limit);
  }
  
  private async getFriendActivities(
    userId: string,
    socialGraph: SocialGraph
  ): Promise<SocialFeedItem[]> {
    const friendActivities: SocialFeedItem[] = [];
    
    for (const friend of socialGraph.friends) {
      const friendRSVPs = await this.getFriendRSVPs(friend.friendId);
      
      for (const rsvp of friendRSVPs) {
        const activity = await this.getActivity(rsvp.activityId);
        if (!activity) continue;
        
        const socialContext = await this.buildSocialContext(activity.id, userId, socialGraph);
        
        friendActivities.push({
          ...this.convertActivityToFeedItem(activity),
          socialContext,
          contentType: 'friend_activity',
          socialScore: this.calculateSocialScore(socialContext, friend),
        });
      }
    }
    
    return friendActivities;
  }
  
  private async buildSocialContext(
    activityId: string,
    userId: string,
    socialGraph: SocialGraph
  ): Promise<SocialContext> {
    const [friendsGoing, friendsInterested, socialProof] = await Promise.all([
      this.getFriendsGoing(activityId, socialGraph.friends),
      this.getFriendsInterested(activityId, socialGraph.friends),
      this.getSocialProof(activityId, userId),
    ]);
    
    const relationshipStrength = this.calculateAverageRelationshipStrength(
      [...friendsGoing, ...friendsInterested],
      socialGraph
    );
    
    return {
      friendsGoing,
      friendsInterested,
      socialProof,
      relationshipStrength,
      socialEngagement: await this.getSocialEngagement(activityId),
    };
  }
  
  private async scoreSocialContent(
    content: SocialFeedItem[],
    userProfile: UserProfile,
    socialGraph: SocialGraph
  ): Promise<SocialFeedItem[]> {
    return content.map(item => {
      let socialScore = item.socialScore || 0;
      
      // Boost based on friend relationship strength
      if (item.socialContext.friendsGoing.length > 0) {
        socialScore += item.socialContext.relationshipStrength * 0.4;
      }
      
      // Boost based on social proof
      socialScore += item.socialContext.socialProof.strength * 0.3;
      
      // Boost based on user interests
      const interestMatch = this.calculateInterestMatch(
        item.tags || [],
        userProfile.interests
      );
      socialScore += interestMatch * 0.3;
      
      return {
        ...item,
        socialScore: Math.min(socialScore, 1.0),
      };
    }).sort((a, b) => (b.socialScore || 0) - (a.socialScore || 0));
  }
}
```

#### Following System
```typescript
interface FollowingRelationship {
  id: string;
  userId: string;
  targetType: 'user' | 'host' | 'category' | 'location';
  targetId: string;
  followedAt: Date;
  notificationPreferences: NotificationPreferences;
  contentPreferences: ContentPreferences;
}

class FollowingManager {
  async followTarget(
    userId: string,
    targetType: string,
    targetId: string,
    preferences?: FollowingPreferences
  ): Promise<FollowingRelationship> {
    // Check if already following
    const existing = await this.getFollowingRelationship(userId, targetType, targetId);
    if (existing) {
      throw new Error('Already following this target');
    }
    
    // Create following relationship
    const following: FollowingRelationship = {
      id: generateId(),
      userId,
      targetType,
      targetId,
      followedAt: new Date(),
      notificationPreferences: preferences?.notifications || this.getDefaultNotificationPreferences(),
      contentPreferences: preferences?.content || this.getDefaultContentPreferences(),
    };
    
    await this.saveFollowingRelationship(following);
    
    // Update user's following feed
    await this.updateFollowingFeed(userId);
    
    return following;
  }
  
  async getFollowingFeedContent(
    userId: string,
    limit: number = 20
  ): Promise<SocialFeedItem[]> {
    const followingRelationships = await this.getUserFollowing(userId);
    const contentSources: Promise<SocialFeedItem[]>[] = [];
    
    for (const following of followingRelationships) {
      switch (following.targetType) {
        case 'host':
          contentSources.push(this.getHostContent(following.targetId, following.contentPreferences));
          break;
        case 'category':
          contentSources.push(this.getCategoryContent(following.targetId, following.contentPreferences));
          break;
        case 'location':
          contentSources.push(this.getLocationContent(following.targetId, following.contentPreferences));
          break;
        case 'user':
          contentSources.push(this.getUserContent(following.targetId, following.contentPreferences));
          break;
      }
    }
    
    const contentArrays = await Promise.all(contentSources);
    const allContent = contentArrays.flat();
    
    // Score and rank following content
    const scoredContent = this.scoreFollowingContent(allContent, followingRelationships);
    
    return scoredContent.slice(0, limit);
  }
  
  private async getHostContent(
    hostId: string,
    preferences: ContentPreferences
  ): Promise<SocialFeedItem[]> {
    const hostActivities = await this.getHostActivities(hostId, {
      includeUpcoming: preferences.includeUpcoming,
      includePast: preferences.includePast,
      categoryFilter: preferences.categories,
    });
    
    return hostActivities.map(activity => ({
      ...this.convertActivityToFeedItem(activity),
      followingContext: {
        followedHost: await this.getHostInfo(hostId),
        followingReason: 'following_host',
      },
      contentType: 'following_host',
    }));
  }
  
  async suggestFollowing(userId: string): Promise<FollowingSuggestion[]> {
    const userProfile = await this.getUserProfile(userId);
    const userBehavior = await this.getUserBehavior(userId);
    const socialGraph = await this.getUserSocialGraph(userId);
    
    const suggestions: FollowingSuggestion[] = [];
    
    // Suggest hosts based on attended activities
    const attendedActivities = await this.getUserAttendedActivities(userId);
    const hostSuggestions = await this.suggestHostsFromActivities(attendedActivities);
    suggestions.push(...hostSuggestions);
    
    // Suggest categories based on interests
    const categorySuggestions = await this.suggestCategoriesFromInterests(userProfile.interests);
    suggestions.push(...categorySuggestions);
    
    // Suggest based on friend following
    const friendFollowingSuggestions = await this.suggestFromFriendFollowing(socialGraph.friends);
    suggestions.push(...friendFollowingSuggestions);
    
    // Score and rank suggestions
    return this.rankFollowingSuggestions(suggestions, userProfile, userBehavior);
  }
}
```

#### Social Context Integration
```typescript
class SocialContextProvider {
  async addSocialContextToFeedItems(
    feedItems: FeedItem[],
    userId: string
  ): Promise<SocialFeedItem[]> {
    const socialGraph = await this.getUserSocialGraph(userId);
    
    return await Promise.all(
      feedItems.map(async (item) => {
        const socialContext = await this.buildSocialContext(item.id, userId, socialGraph);
        
        return {
          ...item,
          socialContext,
        } as SocialFeedItem;
      })
    );
  }
  
  async getSocialProof(activityId: string, userId: string): Promise<SocialProof> {
    const [totalRSVPs, friendRSVPs, networkEngagement] = await Promise.all([
      this.getActivityRSVPCount(activityId),
      this.getFriendRSVPCount(activityId, userId),
      this.getNetworkEngagement(activityId, userId),
    ]);
    
    return {
      totalParticipants: totalRSVPs,
      friendParticipants: friendRSVPs,
      networkEngagement,
      strength: this.calculateSocialProofStrength(totalRSVPs, friendRSVPs, networkEngagement),
      displayText: this.generateSocialProofText(totalRSVPs, friendRSVPs),
    };
  }
  
  private generateSocialProofText(totalRSVPs: number, friendRSVPs: number): string {
    if (friendRSVPs > 0) {
      if (friendRSVPs === 1) {
        return `1 friend is going`;
      } else {
        return `${friendRSVPs} friends are going`;
      }
    } else if (totalRSVPs > 0) {
      return `${totalRSVPs} people are going`;
    } else {
      return 'Be the first to RSVP';
    }
  }
}
```

#### Privacy Controls
```typescript
class SocialFeedPrivacyManager {
  async applySocialPrivacyFilters(
    userId: string,
    socialFeedItems: SocialFeedItem[]
  ): Promise<SocialFeedItem[]> {
    const privacySettings = await this.getUserSocialPrivacySettings(userId);
    
    return socialFeedItems.filter(item => {
      // Check if user allows friend activity in feed
      if (item.contentType === 'friend_activity' && !privacySettings.showFriendActivities) {
        return false;
      }
      
      // Check if user allows following content
      if (item.followingContext && !privacySettings.showFollowingContent) {
        return false;
      }
      
      // Check specific friend privacy settings
      if (item.socialContext.friendsGoing.some(friend => 
        privacySettings.blockedFriends.includes(friend.id)
      )) {
        return false;
      }
      
      // Check content visibility settings
      if (item.visibility === 'private' && !this.canViewPrivateContent(userId, item)) {
        return false;
      }
      
      return true;
    });
  }
  
  async anonymizeSocialContext(
    socialFeedItems: SocialFeedItem[],
    privacyLevel: 'full' | 'partial' | 'minimal'
  ): Promise<SocialFeedItem[]> {
    return socialFeedItems.map(item => {
      const anonymizedItem = { ...item };
      
      switch (privacyLevel) {
        case 'minimal':
          // Remove all specific friend information
          anonymizedItem.socialContext = {
            ...item.socialContext,
            friendsGoing: [],
            friendsInterested: [],
            socialProof: {
              ...item.socialContext.socialProof,
              friendParticipants: 0,
            },
          };
          break;
          
        case 'partial':
          // Keep counts but remove specific friend details
          anonymizedItem.socialContext.friendsGoing = 
            item.socialContext.friendsGoing.map(() => ({ id: 'anonymous', name: 'Friend' }));
          anonymizedItem.socialContext.friendsInterested = 
            item.socialContext.friendsInterested.map(() => ({ id: 'anonymous', name: 'Friend' }));
          break;
          
        case 'full':
          // Keep all social context
          break;
      }
      
      return anonymizedItem;
    });
  }
}
```

### Quality Checklist
- [ ] Social feeds provide meaningful social context and engagement
- [ ] Following system enables effective content discovery and curation
- [ ] Privacy controls give users meaningful control over social features
- [ ] Social discovery drives new connections and community building
- [ ] Integration with main feed experience is seamless
- [ ] Performance optimized for social data processing
- [ ] Social engagement actions work reliably
- [ ] Analytics provide insights into social feed effectiveness

---

**Status**: 🔄 Ready for Problem Definition Phase  
**Assignee**: Full-Stack Developer  
**Epic**: E04 Discovery Engine  
**Feature**: F03 Feed Generation Service  
**Dependencies**: T02 Feed Backend, T03 Feed Frontend, User Profiles (E02), Social Features (E05)  
**Blocks**: Complete Social Feed Experience
