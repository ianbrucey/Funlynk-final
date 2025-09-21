# T04: Social Profile Features - Problem Definition

## Problem Statement

We need to implement comprehensive social profile features that enable users to build and showcase their social presence within the Funlynk community. This includes follower/following relationships, social proof indicators, profile sharing capabilities, social media integration, and community recognition features that foster meaningful connections and social engagement.

## Context

### Current State
- Core profile data structure is implemented (T01 completed)
- Profile media management handles photos and galleries (T02 completed)
- Profile customization allows personalized appearances (T03 completed)
- Profiles exist as isolated entities without social connections
- No follower/following relationship system
- No social proof indicators or community recognition
- No integration with external social media platforms

### Desired State
- Users can follow and be followed by other community members
- Social proof indicators show follower counts, activity stats, and community standing
- Profile sharing enables users to promote their profiles across platforms
- Social media integration connects external accounts and verifies identity
- Community badges and verification systems build trust and recognition
- Social activity feeds show profile-related interactions and updates

## Business Impact

### Why This Matters
- **Community Building**: Social features create connections and foster community growth
- **User Engagement**: Social profiles increase platform engagement by 45%
- **Trust and Credibility**: Social proof indicators build trust between users
- **Viral Growth**: Profile sharing drives organic user acquisition
- **Platform Stickiness**: Social connections increase user retention significantly
- **Network Effects**: Strong social features create valuable network effects

### Success Metrics
- Average follower count >25 per active user within 3 months
- Profile sharing rate >15% of users monthly
- Social media integration adoption >40% of users
- Social interaction rate >20% increase in profile views
- User satisfaction with social features >4.3/5
- Community verification badge adoption >60% of eligible users

## Technical Requirements

### Functional Requirements
- **Follow/Unfollow System**: Asymmetric following relationships like Twitter/Instagram
- **Social Proof Indicators**: Follower counts, activity stats, and engagement metrics
- **Profile Sharing**: Share profiles via links, social media, and in-app features
- **Social Media Integration**: Connect and verify external social media accounts
- **Community Recognition**: Badges, verification, and achievement systems
- **Social Activity Feed**: Track and display social interactions
- **Relationship Management**: Manage followers, following, and blocked users

### Non-Functional Requirements
- **Performance**: Social operations complete within 500ms
- **Scalability**: Support millions of follow relationships efficiently
- **Real-time Updates**: Social changes reflect immediately across platform
- **Privacy**: Respect user privacy preferences for social visibility
- **Security**: Prevent fake followers and social manipulation
- **Reliability**: 99.9% uptime for social features

## Social Relationship System

### Follow/Following Data Model
```typescript
interface UserRelationship {
  id: string;
  followerId: string;
  followeeId: string;
  relationshipType: RelationshipType;
  status: RelationshipStatus;
  createdAt: Date;
  updatedAt: Date;
  
  // Interaction metadata
  interactionScore: number; // Based on likes, comments, activity participation
  lastInteraction: Date;
  mutualFollowers: number;
  
  // Privacy and preferences
  notificationsEnabled: boolean;
  isClose: boolean; // Close friends designation
  isMuted: boolean;
  isBlocked: boolean;
}

enum RelationshipType {
  FOLLOW = 'follow',
  MUTUAL_FOLLOW = 'mutual_follow',
  CLOSE_FRIEND = 'close_friend',
  BLOCKED = 'blocked'
}

enum RelationshipStatus {
  ACTIVE = 'active',
  PENDING = 'pending', // For future private account features
  INACTIVE = 'inactive'
}

interface SocialStats {
  userId: string;
  followerCount: number;
  followingCount: number;
  mutualFollowCount: number;
  
  // Engagement metrics
  profileViews: number;
  profileViewsThisMonth: number;
  socialInteractions: number;
  averageInteractionRate: number;
  
  // Activity metrics
  activitiesHosted: number;
  activitiesAttended: number;
  averageRating: number;
  reviewCount: number;
  
  // Community metrics
  communityRank: number;
  influenceScore: number;
  trustScore: number;
  
  lastUpdated: Date;
}

class SocialRelationshipService {
  async followUser(followerId: string, followeeId: string): Promise<UserRelationship> {
    // Validate users exist and aren't the same
    if (followerId === followeeId) {
      throw new ValidationError('Users cannot follow themselves');
    }
    
    await this.validateUsersExist([followerId, followeeId]);
    
    // Check if relationship already exists
    const existingRelationship = await this.getRelationship(followerId, followeeId);
    if (existingRelationship) {
      if (existingRelationship.status === RelationshipStatus.ACTIVE) {
        throw new ConflictError('User is already being followed');
      }
      // Reactivate if previously unfollowed
      return await this.reactivateRelationship(existingRelationship.id);
    }
    
    // Check for blocks
    const isBlocked = await this.isUserBlocked(followerId, followeeId);
    if (isBlocked) {
      throw new ForbiddenError('Cannot follow blocked user');
    }
    
    // Create relationship
    const relationship: UserRelationship = {
      id: generateUUID(),
      followerId,
      followeeId,
      relationshipType: RelationshipType.FOLLOW,
      status: RelationshipStatus.ACTIVE,
      createdAt: new Date(),
      updatedAt: new Date(),
      interactionScore: 0,
      lastInteraction: new Date(),
      mutualFollowers: await this.calculateMutualFollowers(followerId, followeeId),
      notificationsEnabled: true,
      isClose: false,
      isMuted: false,
      isBlocked: false
    };
    
    const savedRelationship = await this.db.userRelationships.create(relationship);
    
    // Update social stats
    await this.updateSocialStats(followerId, 'following', 1);
    await this.updateSocialStats(followeeId, 'followers', 1);
    
    // Check if this creates a mutual follow
    const reverseRelationship = await this.getRelationship(followeeId, followerId);
    if (reverseRelationship?.status === RelationshipStatus.ACTIVE) {
      await this.updateRelationshipType(savedRelationship.id, RelationshipType.MUTUAL_FOLLOW);
      await this.updateRelationshipType(reverseRelationship.id, RelationshipType.MUTUAL_FOLLOW);
    }
    
    // Send notification
    await this.notificationService.sendNotification({
      userId: followeeId,
      type: 'social_follow',
      title: 'New Follower',
      body: `${await this.getUserDisplayName(followerId)} started following you`,
      actionUrl: `/profiles/${followerId}`,
      channels: ['push', 'in_app']
    });
    
    // Log social event
    await this.logSocialEvent({
      type: 'user_followed',
      actorId: followerId,
      targetId: followeeId,
      metadata: { relationshipId: savedRelationship.id }
    });
    
    return savedRelationship;
  }
  
  async unfollowUser(followerId: string, followeeId: string): Promise<void> {
    const relationship = await this.getActiveRelationship(followerId, followeeId);
    if (!relationship) {
      throw new NotFoundError('Follow relationship not found');
    }
    
    // Deactivate relationship
    await this.db.userRelationships.update(relationship.id, {
      status: RelationshipStatus.INACTIVE,
      updatedAt: new Date()
    });
    
    // Update social stats
    await this.updateSocialStats(followerId, 'following', -1);
    await this.updateSocialStats(followeeId, 'followers', -1);
    
    // Update mutual follow if applicable
    const reverseRelationship = await this.getRelationship(followeeId, followerId);
    if (reverseRelationship?.relationshipType === RelationshipType.MUTUAL_FOLLOW) {
      await this.updateRelationshipType(reverseRelationship.id, RelationshipType.FOLLOW);
    }
    
    // Log social event
    await this.logSocialEvent({
      type: 'user_unfollowed',
      actorId: followerId,
      targetId: followeeId,
      metadata: { relationshipId: relationship.id }
    });
  }
  
  async getFollowers(userId: string, options?: PaginationOptions): Promise<PaginatedResult<UserProfile>> {
    const relationships = await this.db.userRelationships.findMany({
      where: {
        followeeId: userId,
        status: RelationshipStatus.ACTIVE
      },
      include: {
        follower: {
          include: { profile: true }
        }
      },
      orderBy: { createdAt: 'desc' },
      ...this.getPaginationParams(options)
    });
    
    const followers = relationships.map(rel => rel.follower.profile);
    
    return {
      items: followers,
      total: await this.getFollowerCount(userId),
      page: options?.page || 1,
      pageSize: options?.pageSize || 20
    };
  }
  
  async getFollowing(userId: string, options?: PaginationOptions): Promise<PaginatedResult<UserProfile>> {
    const relationships = await this.db.userRelationships.findMany({
      where: {
        followerId: userId,
        status: RelationshipStatus.ACTIVE
      },
      include: {
        followee: {
          include: { profile: true }
        }
      },
      orderBy: { createdAt: 'desc' },
      ...this.getPaginationParams(options)
    });
    
    const following = relationships.map(rel => rel.followee.profile);
    
    return {
      items: following,
      total: await this.getFollowingCount(userId),
      page: options?.page || 1,
      pageSize: options?.pageSize || 20
    };
  }
}
```

### Social Proof and Statistics
```typescript
interface SocialProofIndicators {
  followerCount: number;
  followingCount: number;
  mutualConnections: number;
  
  // Activity metrics
  activitiesHosted: number;
  activitiesAttended: number;
  averageRating: number;
  totalReviews: number;
  
  // Engagement metrics
  profileViews: number;
  monthlyProfileViews: number;
  socialInteractions: number;
  responseRate: number;
  
  // Community standing
  joinedDate: Date;
  communityRank: CommunityRank;
  verificationLevel: VerificationLevel;
  badges: ProfileBadge[];
  
  // Trust indicators
  trustScore: number;
  backgroundCheckStatus?: BackgroundCheckStatus;
  identityVerified: boolean;
  phoneVerified: boolean;
  emailVerified: boolean;
}

enum CommunityRank {
  NEWCOMER = 'newcomer',
  MEMBER = 'member',
  ACTIVE_MEMBER = 'active_member',
  TRUSTED_MEMBER = 'trusted_member',
  COMMUNITY_LEADER = 'community_leader',
  AMBASSADOR = 'ambassador'
}

class SocialProofService {
  async calculateSocialProof(userId: string, viewerId?: string): Promise<SocialProofIndicators> {
    const [
      socialStats,
      activityStats,
      engagementStats,
      communityStats,
      verificationData
    ] = await Promise.all([
      this.getSocialStats(userId),
      this.getActivityStats(userId),
      this.getEngagementStats(userId),
      this.getCommunityStats(userId),
      this.getVerificationData(userId)
    ]);
    
    // Calculate mutual connections if viewer is provided
    let mutualConnections = 0;
    if (viewerId && viewerId !== userId) {
      mutualConnections = await this.getMutualConnectionCount(userId, viewerId);
    }
    
    return {
      followerCount: socialStats.followerCount,
      followingCount: socialStats.followingCount,
      mutualConnections,
      
      activitiesHosted: activityStats.hostedCount,
      activitiesAttended: activityStats.attendedCount,
      averageRating: activityStats.averageRating,
      totalReviews: activityStats.reviewCount,
      
      profileViews: engagementStats.totalViews,
      monthlyProfileViews: engagementStats.monthlyViews,
      socialInteractions: engagementStats.interactions,
      responseRate: engagementStats.responseRate,
      
      joinedDate: communityStats.joinedDate,
      communityRank: this.calculateCommunityRank(communityStats),
      verificationLevel: verificationData.level,
      badges: await this.getUserBadges(userId),
      
      trustScore: this.calculateTrustScore({
        socialStats,
        activityStats,
        verificationData,
        communityStats
      }),
      backgroundCheckStatus: verificationData.backgroundCheck,
      identityVerified: verificationData.identityVerified,
      phoneVerified: verificationData.phoneVerified,
      emailVerified: verificationData.emailVerified
    };
  }
  
  private calculateTrustScore(data: {
    socialStats: SocialStats;
    activityStats: any;
    verificationData: any;
    communityStats: any;
  }): number {
    let score = 0;
    
    // Base score from account age (max 20 points)
    const accountAgeMonths = this.getAccountAgeInMonths(data.communityStats.joinedDate);
    score += Math.min(accountAgeMonths * 2, 20);
    
    // Verification bonuses (max 30 points)
    if (data.verificationData.emailVerified) score += 5;
    if (data.verificationData.phoneVerified) score += 10;
    if (data.verificationData.identityVerified) score += 15;
    
    // Activity participation (max 25 points)
    const activityParticipation = data.activityStats.attendedCount + data.activityStats.hostedCount;
    score += Math.min(activityParticipation, 25);
    
    // Social engagement (max 15 points)
    const socialEngagement = Math.min(data.socialStats.followerCount / 10, 15);
    score += socialEngagement;
    
    // Rating quality (max 10 points)
    if (data.activityStats.averageRating >= 4.5) score += 10;
    else if (data.activityStats.averageRating >= 4.0) score += 7;
    else if (data.activityStats.averageRating >= 3.5) score += 5;
    
    return Math.min(Math.round(score), 100);
  }
}
```

### Profile Sharing System
```typescript
interface ProfileShareService {
  generateShareLink(userId: string, options?: ShareOptions): Promise<ShareLink>;
  shareToSocialMedia(userId: string, platform: SocialPlatform, message?: string): Promise<ShareResult>;
  trackShareEvent(shareId: string, event: ShareEvent): Promise<void>;
  getShareAnalytics(userId: string, timeRange?: TimeRange): Promise<ShareAnalytics>;
}

interface ShareOptions {
  includeStats: boolean;
  customMessage?: string;
  expiresAt?: Date;
  trackClicks: boolean;
  utmSource?: string;
  utmMedium?: string;
  utmCampaign?: string;
}

interface ShareLink {
  id: string;
  url: string;
  shortUrl: string;
  qrCode: string;
  metadata: ShareMetadata;
  analytics: ShareAnalytics;
  createdAt: Date;
  expiresAt?: Date;
}

interface ShareMetadata {
  title: string;
  description: string;
  imageUrl: string;
  ogTags: OpenGraphTags;
  twitterCard: TwitterCardData;
}

class ProfileShareServiceImpl implements ProfileShareService {
  async generateShareLink(userId: string, options?: ShareOptions): Promise<ShareLink> {
    const profile = await this.getProfile(userId);
    const socialProof = await this.socialProofService.calculateSocialProof(userId);
    
    // Generate share metadata
    const metadata = await this.generateShareMetadata(profile, socialProof, options);
    
    // Create share link
    const shareId = generateUUID();
    const baseUrl = process.env.APP_BASE_URL;
    const url = `${baseUrl}/profiles/${userId}?share=${shareId}`;
    
    // Generate short URL
    const shortUrl = await this.urlShortener.shorten(url);
    
    // Generate QR code
    const qrCode = await this.qrGenerator.generate(url);
    
    const shareLink: ShareLink = {
      id: shareId,
      url,
      shortUrl,
      qrCode,
      metadata,
      analytics: {
        views: 0,
        clicks: 0,
        conversions: 0,
        sources: {}
      },
      createdAt: new Date(),
      expiresAt: options?.expiresAt
    };
    
    // Store share link
    await this.db.shareLinks.create(shareLink);
    
    return shareLink;
  }
  
  private async generateShareMetadata(
    profile: UserProfile,
    socialProof: SocialProofIndicators,
    options?: ShareOptions
  ): Promise<ShareMetadata> {
    const title = `${profile.displayName} on Funlynk`;
    
    let description = profile.bio || `Check out ${profile.displayName}'s profile on Funlynk`;
    
    if (options?.includeStats) {
      description += ` • ${socialProof.followerCount} followers • ${socialProof.activitiesHosted} activities hosted`;
    }
    
    if (options?.customMessage) {
      description = options.customMessage;
    }
    
    return {
      title,
      description,
      imageUrl: profile.profileImageUrl || this.getDefaultProfileImage(),
      ogTags: {
        'og:title': title,
        'og:description': description,
        'og:image': profile.profileImageUrl || this.getDefaultProfileImage(),
        'og:type': 'profile',
        'og:url': `${process.env.APP_BASE_URL}/profiles/${profile.userId}`
      },
      twitterCard: {
        'twitter:card': 'summary_large_image',
        'twitter:title': title,
        'twitter:description': description,
        'twitter:image': profile.profileImageUrl || this.getDefaultProfileImage()
      }
    };
  }
  
  async shareToSocialMedia(
    userId: string,
    platform: SocialPlatform,
    message?: string
  ): Promise<ShareResult> {
    const shareLink = await this.generateShareLink(userId, {
      includeStats: true,
      customMessage: message,
      trackClicks: true,
      utmSource: platform,
      utmMedium: 'social',
      utmCampaign: 'profile_share'
    });
    
    const shareUrl = this.buildSocialShareUrl(platform, shareLink, message);
    
    // Track share attempt
    await this.trackShareEvent(shareLink.id, {
      type: 'share_initiated',
      platform,
      timestamp: new Date()
    });
    
    return {
      shareId: shareLink.id,
      shareUrl,
      platform,
      trackingUrl: shareLink.url
    };
  }
  
  private buildSocialShareUrl(
    platform: SocialPlatform,
    shareLink: ShareLink,
    message?: string
  ): string {
    const encodedUrl = encodeURIComponent(shareLink.shortUrl);
    const encodedMessage = encodeURIComponent(message || shareLink.metadata.description);
    
    switch (platform) {
      case SocialPlatform.TWITTER:
        return `https://twitter.com/intent/tweet?text=${encodedMessage}&url=${encodedUrl}`;
      case SocialPlatform.FACEBOOK:
        return `https://www.facebook.com/sharer/sharer.php?u=${encodedUrl}`;
      case SocialPlatform.LINKEDIN:
        return `https://www.linkedin.com/sharing/share-offsite/?url=${encodedUrl}`;
      case SocialPlatform.INSTAGRAM:
        // Instagram doesn't support direct URL sharing, return app deep link
        return `instagram://camera`;
      default:
        return shareLink.url;
    }
  }
}
```

### Social Media Integration
```typescript
interface SocialMediaAccount {
  id: string;
  userId: string;
  platform: SocialPlatform;
  platformUserId: string;
  username: string;
  displayName: string;
  profileUrl: string;
  avatarUrl?: string;
  followerCount?: number;
  isVerified: boolean;
  isPublic: boolean;
  connectedAt: Date;
  lastSyncAt: Date;
  accessToken?: string; // Encrypted
  refreshToken?: string; // Encrypted
}

class SocialMediaIntegrationService {
  async connectSocialAccount(
    userId: string,
    platform: SocialPlatform,
    authCode: string
  ): Promise<SocialMediaAccount> {
    // Exchange auth code for access token
    const tokenData = await this.exchangeAuthCode(platform, authCode);
    
    // Fetch user data from social platform
    const platformUserData = await this.fetchPlatformUserData(platform, tokenData.accessToken);
    
    // Check if account is already connected to another user
    const existingConnection = await this.db.socialMediaAccounts.findFirst({
      where: {
        platform,
        platformUserId: platformUserData.id
      }
    });
    
    if (existingConnection && existingConnection.userId !== userId) {
      throw new ConflictError('This social media account is already connected to another user');
    }
    
    // Create or update social media account
    const socialAccount: SocialMediaAccount = {
      id: existingConnection?.id || generateUUID(),
      userId,
      platform,
      platformUserId: platformUserData.id,
      username: platformUserData.username,
      displayName: platformUserData.displayName,
      profileUrl: platformUserData.profileUrl,
      avatarUrl: platformUserData.avatarUrl,
      followerCount: platformUserData.followerCount,
      isVerified: platformUserData.isVerified,
      isPublic: true,
      connectedAt: existingConnection?.connectedAt || new Date(),
      lastSyncAt: new Date(),
      accessToken: await this.encrypt(tokenData.accessToken),
      refreshToken: tokenData.refreshToken ? await this.encrypt(tokenData.refreshToken) : undefined
    };
    
    const savedAccount = existingConnection
      ? await this.db.socialMediaAccounts.update(existingConnection.id, socialAccount)
      : await this.db.socialMediaAccounts.create(socialAccount);
    
    // Update user profile with social link
    await this.updateProfileSocialLinks(userId, platform, platformUserData);
    
    // Award verification badge if applicable
    if (platformUserData.isVerified || platformUserData.followerCount > 10000) {
      await this.badgeService.awardBadge(userId, 'social_media_verified');
    }
    
    return savedAccount;
  }
  
  async verifySocialAccount(userId: string, platform: SocialPlatform): Promise<VerificationResult> {
    const socialAccount = await this.getSocialAccount(userId, platform);
    if (!socialAccount) {
      throw new NotFoundError('Social media account not connected');
    }
    
    // Verify account ownership by posting a verification message
    const verificationCode = this.generateVerificationCode();
    const verificationMessage = `Verifying my Funlynk profile: ${verificationCode}`;
    
    try {
      // Post verification message (implementation depends on platform API)
      await this.postVerificationMessage(socialAccount, verificationMessage);
      
      // Wait for user confirmation
      const verificationRequest = await this.createVerificationRequest(
        userId,
        platform,
        verificationCode
      );
      
      return {
        verificationId: verificationRequest.id,
        verificationCode,
        instructions: this.getVerificationInstructions(platform),
        expiresAt: new Date(Date.now() + 15 * 60 * 1000) // 15 minutes
      };
    } catch (error) {
      throw new ServiceError('Failed to post verification message', error);
    }
  }
}
```

## Constraints and Assumptions

### Constraints
- Must handle millions of follow relationships efficiently
- Must prevent fake followers and social manipulation
- Must respect user privacy preferences for social visibility
- Must integrate with external social media APIs reliably
- Must maintain real-time updates across all social features

### Assumptions
- Users want to build social connections within the platform
- Social proof indicators will increase trust and engagement
- Profile sharing will drive organic user acquisition
- Social media integration adds value for user verification
- Community recognition features will motivate positive behavior

## Acceptance Criteria

### Must Have
- [ ] Follow/unfollow system with asymmetric relationships
- [ ] Social proof indicators showing follower counts and activity stats
- [ ] Profile sharing with customizable share links and social media integration
- [ ] Social media account connection and verification
- [ ] Community badges and verification systems
- [ ] Real-time updates for social interactions
- [ ] Privacy controls for social visibility

### Should Have
- [ ] Mutual connection discovery and recommendations
- [ ] Advanced social analytics and insights
- [ ] Social activity feed showing profile interactions
- [ ] Bulk relationship management tools
- [ ] Social media content synchronization
- [ ] Community leaderboards and rankings

### Could Have
- [ ] AI-powered connection recommendations
- [ ] Advanced social media integration with content sharing
- [ ] Social proof optimization suggestions
- [ ] Community challenges and social competitions
- [ ] Advanced verification with identity documents

## Risk Assessment

### High Risk
- **Fake Followers**: System could be manipulated with fake accounts
- **Privacy Concerns**: Social features could compromise user privacy
- **Performance Issues**: Large social graphs could impact performance

### Medium Risk
- **Social Media API Changes**: External platform changes could break integrations
- **User Harassment**: Social features could enable harassment or abuse
- **Data Synchronization**: Keeping social data in sync could be challenging

### Low Risk
- **Feature Complexity**: Advanced social features might be complex to implement
- **User Adoption**: Users might not engage with social features

### Mitigation Strategies
- Implement fraud detection and fake account prevention
- Comprehensive privacy controls and user education
- Performance optimization for large-scale social operations
- Robust error handling for external API integrations
- Community moderation and reporting systems

## Dependencies

### Prerequisites
- T01: Core Profile Data Structure (completed)
- T02: Profile Media Management (completed)
- T03: Profile Customization System (completed)
- E01.F04: Notification Infrastructure (for social notifications)
- Social media API integrations (Twitter, Facebook, Instagram, LinkedIn)

### Blocks
- Profile analytics with social metrics (T06)
- User discovery and search features (F03)
- Activity management with social features (E03)
- Community and social features (E04)

## Definition of Done

### Technical Completion
- [ ] Follow/unfollow system works reliably with proper relationship management
- [ ] Social proof indicators calculate and display accurately
- [ ] Profile sharing generates working links with proper metadata
- [ ] Social media integration connects and verifies accounts
- [ ] Community badges and verification systems function correctly
- [ ] Real-time updates propagate social changes immediately
- [ ] Privacy controls protect user social information

### Integration Completion
- [ ] Social features integrate with profile system and notifications
- [ ] Social media APIs connect reliably with proper error handling
- [ ] Social proof data updates automatically with user activity
- [ ] Profile sharing works across web and mobile platforms
- [ ] Badge system connects with user achievements and verification
- [ ] Social analytics track engagement and relationship metrics

### Quality Completion
- [ ] Social operations meet performance requirements
- [ ] Fraud prevention systems detect and prevent fake followers
- [ ] Privacy controls work correctly across all social features
- [ ] Social media integrations handle API changes gracefully
- [ ] User testing confirms intuitive social feature experience
- [ ] Performance testing validates large-scale social operations
- [ ] Security testing confirms protection against social manipulation

---

**Task**: T04 Social Profile Features
**Feature**: F01 Profile Creation & Management
**Epic**: E02 User Profile Management
**Estimated Effort**: 3-4 hours
**Priority**: P1 (High)
**Dependencies**: T01-T03 Profile Features, E01.F04 Notification Infrastructure
**Status**: Ready for Research Phase
