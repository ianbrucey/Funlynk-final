# E05 Social Interaction - Service Architecture

## Architecture Overview

The Social Interaction epic provides four main services that enable rich social features and community building: Comment & Discussion Service, Social Engagement Service, Community Management Service, and Real-time Social Service. These services work together to create a vibrant social ecosystem around activities.

## Service Design Principles

### 1. Community-First Design
All social features prioritize building genuine communities and meaningful connections over vanity metrics.

### 2. Real-time Responsiveness
Social interactions feel immediate and engaging through real-time updates and notifications.

### 3. Moderation-Ready
Built-in moderation tools and workflows ensure healthy community interactions.

### 4. Privacy-Aware
All social features respect user privacy settings and consent preferences.

## Core Services

### 5.1 Comment & Discussion Service

**Purpose**: Enables rich conversations and discussions around activities with threading and moderation

**Responsibilities**:
- Comment creation, editing, and deletion
- Threaded comment organization and retrieval
- Comment reactions and engagement
- @mention handling and notifications
- Comment moderation and reporting
- Rich media attachment support

**Service Interface**:
```typescript
interface CommentService {
  // Comment Management
  createComment(activityId: string, userId: string, comment: CreateCommentRequest): Promise<Comment>
  updateComment(commentId: string, userId: string, updates: UpdateCommentRequest): Promise<Comment>
  deleteComment(commentId: string, userId: string): Promise<void>
  
  // Comment Retrieval
  getActivityComments(activityId: string, options?: CommentQueryOptions): Promise<CommentThread[]>
  getCommentThread(commentId: string, maxDepth?: number): Promise<CommentThread>
  getUserComments(userId: string, options?: PaginationOptions): Promise<Comment[]>
  
  // Comment Reactions
  addCommentReaction(commentId: string, userId: string, reactionType: ReactionType): Promise<void>
  removeCommentReaction(commentId: string, userId: string, reactionType: ReactionType): Promise<void>
  getCommentReactions(commentId: string): Promise<CommentReaction[]>
  
  // Moderation
  reportComment(commentId: string, reporterId: string, reason: ReportReason): Promise<void>
  moderateComment(commentId: string, moderatorId: string, action: ModerationAction): Promise<void>
  getCommentModerationQueue(moderatorId: string): Promise<ModerationItem[]>
}
```

**Comment Threading Architecture**:
```typescript
class IntelligentCommentService {
  constructor(
    private database: DatabaseService,
    private notificationService: NotificationService,
    private moderationService: ModerationService,
    private mentionService: MentionService
  ) {}
  
  async createComment(activityId: string, userId: string, request: CreateCommentRequest): Promise<Comment> {
    // Validate user permissions
    await this.validateCommentPermissions(activityId, userId);
    
    // Process mentions and rich content
    const processedContent = await this.processCommentContent(request.content);
    const mentions = this.extractMentions(request.content);
    
    // Determine thread depth and parent
    const threadInfo = await this.calculateThreadInfo(request.parentCommentId);
    
    // Create comment with moderation check
    const comment = await this.database.transaction(async (tx) => {
      const newComment = await tx.comments.create({
        activity_id: activityId,
        user_id: userId,
        parent_comment_id: request.parentCommentId,
        content: processedContent.content,
        thread_depth: threadInfo.depth,
        mentions: mentions,
        attachments: request.attachments || [],
        moderation_status: await this.moderationService.preModerateContent(processedContent.content)
      });
      
      // Update activity comment count
      await tx.activities.update(activityId, {
        comment_count: { increment: 1 }
      });
      
      return newComment;
    });
    
    // Send notifications for mentions and replies
    await this.handleCommentNotifications(comment, mentions);
    
    // Broadcast real-time update
    await this.broadcastCommentUpdate(activityId, comment, 'created');
    
    return comment;
  }
  
  async getActivityComments(
    activityId: string, 
    options: CommentQueryOptions = {}
  ): Promise<CommentThread[]> {
    // Get top-level comments with threading
    const comments = await this.database.query(`
      SELECT * FROM get_comment_thread($1, NULL, $2)
      ORDER BY created_at ${options.sort === 'oldest' ? 'ASC' : 'DESC'}
      LIMIT $3 OFFSET $4
    `, [activityId, options.maxDepth || 5, options.limit || 20, options.offset || 0]);
    
    // Build threaded structure
    const threadedComments = this.buildCommentThreads(comments);
    
    // Add user reaction context
    if (options.userId) {
      await this.addUserReactionContext(threadedComments, options.userId);
    }
    
    return threadedComments;
  }
  
  private buildCommentThreads(flatComments: Comment[]): CommentThread[] {
    const commentMap = new Map<string, CommentThread>();
    const rootComments: CommentThread[] = [];
    
    // First pass: create all comment nodes
    flatComments.forEach(comment => {
      commentMap.set(comment.id, {
        ...comment,
        replies: [],
        reply_count: comment.child_count,
        user_reaction: null
      });
    });
    
    // Second pass: build tree structure
    flatComments.forEach(comment => {
      const commentNode = commentMap.get(comment.id)!;
      
      if (comment.parent_id) {
        const parent = commentMap.get(comment.parent_id);
        if (parent) {
          parent.replies.push(commentNode);
        }
      } else {
        rootComments.push(commentNode);
      }
    });
    
    return rootComments;
  }
}
```

### 5.2 Social Engagement Service

**Purpose**: Manages social engagement features like reactions, sharing, and social proof

**Responsibilities**:
- Activity and comment reactions
- Social sharing to internal and external platforms
- Save/bookmark functionality
- Social proof calculation and display
- Viral growth tracking and optimization
- Engagement analytics and insights

**Service Interface**:
```typescript
interface SocialEngagementService {
  // Reactions
  addActivityReaction(activityId: string, userId: string, reactionType: ReactionType): Promise<void>
  removeActivityReaction(activityId: string, userId: string, reactionType: ReactionType): Promise<void>
  getActivityReactions(activityId: string): Promise<ActivityReactionSummary>
  getUserReactionHistory(userId: string, options?: PaginationOptions): Promise<UserReaction[]>
  
  // Sharing
  shareActivity(activityId: string, userId: string, shareRequest: ShareActivityRequest): Promise<ActivityShare>
  trackShareInteraction(shareId: string, interactionType: ShareInteractionType, context?: any): Promise<void>
  getActivityShares(activityId: string): Promise<ActivityShare[]>
  getShareAnalytics(shareId: string): Promise<ShareAnalytics>
  
  // Save/Bookmark
  saveActivity(activityId: string, userId: string, saveOptions?: SaveActivityOptions): Promise<SavedActivity>
  unsaveActivity(activityId: string, userId: string): Promise<void>
  getUserSavedActivities(userId: string, options?: SavedActivityQueryOptions): Promise<SavedActivity[]>
  
  // Social Proof
  calculateSocialProof(activityId: string, userId: string): Promise<SocialProof>
  getSocialContext(activityId: string, userId: string): Promise<SocialContext>
  
  // Analytics
  getEngagementMetrics(activityId: string, timeframe: string): Promise<EngagementMetrics>
  getViralMetrics(activityId: string): Promise<ViralMetrics>
}
```

**Social Engagement Architecture**:
```typescript
class ComprehensiveSocialEngagementService {
  async addActivityReaction(
    activityId: string, 
    userId: string, 
    reactionType: ReactionType
  ): Promise<void> {
    // Validate reaction permissions
    await this.validateReactionPermissions(activityId, userId);
    
    // Handle reaction logic (toggle or add)
    await this.database.transaction(async (tx) => {
      // Check for existing reaction
      const existingReaction = await tx.activityReactions.findFirst({
        where: { activity_id: activityId, user_id: userId, reaction_type: reactionType }
      });
      
      if (existingReaction) {
        // Remove existing reaction (toggle off)
        await tx.activityReactions.delete(existingReaction.id);
      } else {
        // Add new reaction
        await tx.activityReactions.create({
          activity_id: activityId,
          user_id: userId,
          reaction_type: reactionType
        });
        
        // Create notification for activity host
        await this.notificationService.createNotification({
          user_id: await this.getActivityHostId(activityId),
          type: 'activity_reaction',
          title: 'Someone reacted to your activity',
          data: { activity_id: activityId, reactor_id: userId, reaction_type: reactionType }
        });
      }
    });
    
    // Update real-time reaction counts
    await this.broadcastReactionUpdate(activityId, reactionType);
    
    // Update recommendation signals
    await this.updateRecommendationSignals(userId, activityId, 'reaction', reactionType);
  }
  
  async shareActivity(
    activityId: string, 
    userId: string, 
    shareRequest: ShareActivityRequest
  ): Promise<ActivityShare> {
    // Generate share URL and tracking
    const shareUrl = await this.generateShareUrl(activityId, userId);
    const shareId = generateUUID();
    
    // Create share record
    const share = await this.database.activityShares.create({
      id: shareId,
      activity_id: activityId,
      user_id: userId,
      share_type: shareRequest.shareType,
      share_platform: shareRequest.platform,
      share_message: shareRequest.message,
      recipient_user_id: shareRequest.recipientUserId,
      share_url: shareUrl
    });
    
    // Handle different share types
    switch (shareRequest.shareType) {
      case 'internal':
        await this.handleInternalShare(share, shareRequest);
        break;
      case 'external':
        await this.handleExternalShare(share, shareRequest);
        break;
      case 'direct_message':
        await this.handleDirectMessageShare(share, shareRequest);
        break;
    }
    
    // Track viral metrics
    await this.updateViralMetrics(activityId, userId, shareRequest.shareType);
    
    // Update discovery signals
    await this.updateDiscoverySignals(activityId, 'share', shareRequest.shareType);
    
    return share;
  }
  
  async calculateSocialProof(activityId: string, userId: string): Promise<SocialProof> {
    // Get social proof data from database function
    const socialProofData = await this.database.query(
      'SELECT calculate_social_proof($1, $2) as social_proof',
      [activityId, userId]
    );
    
    // Enhance with additional context
    const [friendsAttending, mutualConnections, popularitySignals] = await Promise.all([
      this.getFriendsAttending(activityId, userId),
      this.getMutualConnections(activityId, userId),
      this.getPopularitySignals(activityId)
    ]);
    
    return {
      ...socialProofData[0].social_proof,
      friends_attending_details: friendsAttending,
      mutual_connections: mutualConnections,
      popularity_indicators: popularitySignals,
      social_proof_message: this.generateSocialProofMessage(socialProofData[0].social_proof)
    };
  }
}
```

### 5.3 Community Management Service

**Purpose**: Manages community creation, membership, and governance around activities and interests

**Responsibilities**:
- Community creation and configuration
- Membership management and roles
- Community content and discussions
- Community moderation and governance
- Community discovery and recommendations
- Community analytics and insights

**Service Interface**:
```typescript
interface CommunityService {
  // Community Management
  createCommunity(creatorId: string, communityData: CreateCommunityRequest): Promise<Community>
  updateCommunity(communityId: string, userId: string, updates: UpdateCommunityRequest): Promise<Community>
  deleteCommunity(communityId: string, userId: string): Promise<void>
  
  // Membership
  joinCommunity(communityId: string, userId: string): Promise<CommunityMembership>
  leaveCommunity(communityId: string, userId: string): Promise<void>
  updateMemberRole(communityId: string, memberId: string, role: CommunityRole, updaterId: string): Promise<void>
  getCommunityMembers(communityId: string, options?: MemberQueryOptions): Promise<CommunityMember[]>
  
  // Community Content
  createCommunityPost(communityId: string, userId: string, post: CreatePostRequest): Promise<CommunityPost>
  getCommunityPosts(communityId: string, options?: PostQueryOptions): Promise<CommunityPost[]>
  moderateCommunityPost(postId: string, moderatorId: string, action: ModerationAction): Promise<void>
  
  // Discovery
  discoverCommunities(userId: string, options?: CommunityDiscoveryOptions): Promise<Community[]>
  searchCommunities(query: string, options?: SearchOptions): Promise<Community[]>
  getRecommendedCommunities(userId: string): Promise<Community[]>
  
  // Analytics
  getCommunityAnalytics(communityId: string, timeframe: string): Promise<CommunityAnalytics>
  getCommunityHealth(communityId: string): Promise<CommunityHealthMetrics>
}
```

### 5.4 Real-time Social Service

**Purpose**: Provides real-time social interactions including chat, presence, and live updates

**Responsibilities**:
- Real-time chat for activities and communities
- User presence and status management
- Real-time social notifications
- Live social updates and broadcasts
- WebSocket connection management
- Real-time moderation and safety

**Service Interface**:
```typescript
interface RealTimeSocialService {
  // Chat Management
  sendChatMessage(roomId: string, userId: string, message: ChatMessageRequest): Promise<ChatMessage>
  getChatHistory(roomId: string, options?: ChatHistoryOptions): Promise<ChatMessage[]>
  joinChatRoom(roomId: string, userId: string): Promise<void>
  leaveChatRoom(roomId: string, userId: string): Promise<void>
  
  // Presence Management
  updateUserPresence(userId: string, presence: UserPresence): Promise<void>
  getUserPresence(userId: string): Promise<UserPresence>
  getOnlineUsers(contextId: string, contextType: 'activity' | 'community'): Promise<OnlineUser[]>
  
  // Real-time Notifications
  sendRealTimeNotification(userId: string, notification: RealTimeNotification): Promise<void>
  subscribeToNotifications(userId: string, connectionId: string): Promise<void>
  unsubscribeFromNotifications(connectionId: string): Promise<void>
  
  // Live Updates
  broadcastActivityUpdate(activityId: string, update: ActivityUpdate): Promise<void>
  broadcastCommunityUpdate(communityId: string, update: CommunityUpdate): Promise<void>
  subscribeToActivityUpdates(activityId: string, userId: string): Promise<void>
  
  // Connection Management
  handleConnection(connectionId: string, userId: string): Promise<void>
  handleDisconnection(connectionId: string): Promise<void>
  getActiveConnections(userId: string): Promise<Connection[]>
}
```

**Real-time Architecture**:
```typescript
class ScalableRealTimeSocialService {
  constructor(
    private websocketManager: WebSocketManager,
    private redisClient: RedisClient,
    private messageQueue: MessageQueue,
    private moderationService: ModerationService
  ) {}
  
  async sendChatMessage(
    roomId: string, 
    userId: string, 
    messageRequest: ChatMessageRequest
  ): Promise<ChatMessage> {
    // Validate user permissions for the chat room
    await this.validateChatPermissions(roomId, userId);
    
    // Pre-moderate message content
    const moderationResult = await this.moderationService.moderateMessage(messageRequest.content);
    
    if (moderationResult.blocked) {
      throw new Error('Message blocked by moderation');
    }
    
    // Create message record
    const message = await this.database.chatMessages.create({
      chat_room_id: roomId,
      chat_room_type: this.extractRoomType(roomId),
      sender_id: userId,
      message_type: messageRequest.type || 'text',
      content: messageRequest.content,
      attachments: messageRequest.attachments || [],
      reply_to_message_id: messageRequest.replyToMessageId
    });
    
    // Broadcast to all room participants
    await this.broadcastToRoom(roomId, {
      type: 'chat_message',
      data: {
        message,
        sender: await this.getUserProfile(userId)
      }
    });
    
    // Send push notifications to offline users
    await this.notifyOfflineUsers(roomId, message);
    
    return message;
  }
  
  async updateUserPresence(userId: string, presence: UserPresence): Promise<void> {
    // Update presence in database
    await this.database.userPresence.upsert({
      user_id: userId,
      status: presence.status,
      last_seen: new Date(),
      current_activity_id: presence.currentActivityId,
      custom_status: presence.customStatus,
      is_available_for_chat: presence.isAvailableForChat,
      updated_at: new Date()
    });
    
    // Update presence in Redis for real-time access
    await this.redisClient.setex(
      `presence:${userId}`, 
      300, // 5 minute TTL
      JSON.stringify(presence)
    );
    
    // Broadcast presence update to relevant contexts
    await this.broadcastPresenceUpdate(userId, presence);
  }
  
  private async broadcastToRoom(roomId: string, message: any): Promise<void> {
    // Get all active connections for the room
    const connections = await this.getRoomConnections(roomId);
    
    // Send message to all connections
    await Promise.all(
      connections.map(connection => 
        this.websocketManager.sendMessage(connection.connectionId, message)
      )
    );
    
    // Store message in Redis for recent message cache
    await this.redisClient.lpush(`room:${roomId}:recent`, JSON.stringify(message));
    await this.redisClient.ltrim(`room:${roomId}:recent`, 0, 99); // Keep last 100 messages
  }
  
  private async notifyOfflineUsers(roomId: string, message: ChatMessage): Promise<void> {
    // Get room participants who are offline
    const offlineUsers = await this.getOfflineRoomParticipants(roomId);
    
    // Send push notifications
    await Promise.all(
      offlineUsers.map(user => 
        this.notificationService.sendPushNotification(user.id, {
          title: `New message in ${this.getRoomDisplayName(roomId)}`,
          body: message.content,
          data: {
            type: 'chat_message',
            room_id: roomId,
            message_id: message.id
          }
        })
      )
    );
  }
}
```

## Service Communication Patterns

### Cross-Service Integration
```typescript
// Social services enhance discovery and activity management
class SocialIntegrationService {
  async enhanceActivityWithSocialContext(
    activityId: string, 
    userId: string
  ): Promise<SociallyEnhancedActivity> {
    const [socialProof, comments, reactions, shares] = await Promise.all([
      this.socialEngagementService.calculateSocialProof(activityId, userId),
      this.commentService.getActivityComments(activityId, { limit: 3, userId }),
      this.socialEngagementService.getActivityReactions(activityId),
      this.socialEngagementService.getActivityShares(activityId)
    ]);
    
    return {
      social_proof: socialProof,
      recent_comments: comments,
      reaction_summary: reactions,
      share_count: shares.length,
      social_engagement_score: this.calculateEngagementScore(reactions, comments, shares)
    };
  }
  
  async updateDiscoveryWithSocialSignals(
    activityId: string, 
    socialEvent: SocialEvent
  ): Promise<void> {
    // Update discovery algorithms with social engagement data
    await this.discoveryService.updateSocialSignals(activityId, {
      event_type: socialEvent.type,
      engagement_value: this.calculateEngagementValue(socialEvent),
      user_id: socialEvent.userId,
      timestamp: socialEvent.timestamp
    });
  }
}
```

## Performance Optimizations

### Real-time Performance
```typescript
class RealTimeOptimization {
  private readonly CONNECTION_POOL_SIZE = 10000;
  private readonly MESSAGE_BATCH_SIZE = 100;
  private readonly PRESENCE_UPDATE_THROTTLE = 30000; // 30 seconds
  
  async optimizeWebSocketConnections(): Promise<void> {
    // Connection pooling and load balancing
    await this.websocketManager.configureConnectionPool({
      maxConnections: this.CONNECTION_POOL_SIZE,
      loadBalancingStrategy: 'round_robin',
      heartbeatInterval: 30000,
      connectionTimeout: 60000
    });
  }
  
  async batchMessageDelivery(messages: RealTimeMessage[]): Promise<void> {
    // Batch messages for efficient delivery
    const batches = this.chunkArray(messages, this.MESSAGE_BATCH_SIZE);
    
    await Promise.all(
      batches.map(batch => this.deliverMessageBatch(batch))
    );
  }
  
  async throttlePresenceUpdates(userId: string, presence: UserPresence): Promise<void> {
    // Throttle presence updates to reduce load
    const lastUpdate = await this.redisClient.get(`presence_throttle:${userId}`);
    const now = Date.now();
    
    if (!lastUpdate || now - parseInt(lastUpdate) > this.PRESENCE_UPDATE_THROTTLE) {
      await this.updateUserPresence(userId, presence);
      await this.redisClient.setex(`presence_throttle:${userId}`, 30, now.toString());
    }
  }
}
```

---

**Service Architecture Status**: ✅ Complete
**Next Steps**: Define API contracts for social interaction features
