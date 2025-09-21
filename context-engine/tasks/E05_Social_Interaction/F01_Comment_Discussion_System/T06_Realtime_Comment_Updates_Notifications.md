# T06 Real-time Comment Updates & Notifications

## Problem Definition

### Task Overview
Implement real-time comment updates and notification systems to provide immediate feedback and live discussion experiences. This includes building WebSocket-based real-time synchronization, intelligent notification delivery, and performance optimization for high-concurrency comment scenarios.

### Problem Statement
The comment system needs real-time capabilities to:
- **Provide immediate feedback**: Show new comments and reactions as they happen
- **Enable live discussions**: Support real-time conversations during active events
- **Deliver timely notifications**: Alert users to relevant comment activity and @mentions
- **Maintain performance**: Handle thousands of concurrent users without degradation
- **Ensure reliability**: Provide consistent real-time experience across devices and network conditions

### Scope
**In Scope:**
- Real-time comment synchronization via WebSockets
- Live comment notifications and @mention alerts
- Real-time reaction updates and engagement indicators
- Connection management and automatic reconnection
- Performance optimization for high-concurrency scenarios
- Notification batching and intelligent delivery
- Real-time moderation alerts and updates

**Out of Scope:**
- Basic comment infrastructure (covered in T02)
- Comment frontend components (covered in T03)
- Comment moderation tools (covered in T04)
- Comment analytics (covered in T05)

### Success Criteria
- [ ] Real-time updates appear within 3 seconds for 95% of users
- [ ] System handles 1,000+ concurrent WebSocket connections per activity
- [ ] Notification delivery achieves 98%+ reliability
- [ ] Connection reliability maintains 99%+ uptime during peak usage
- [ ] Real-time features work seamlessly across mobile and web platforms
- [ ] Performance optimization reduces server costs by 20%

### Dependencies
- **Requires**: T02 Comment backend infrastructure for real-time integration
- **Requires**: T03 Comment frontend components for real-time UI updates
- **Requires**: WebSocket infrastructure and connection management
- **Requires**: Push notification services for mobile alerts
- **Blocks**: Complete real-time comment experience
- **Informs**: Production deployment and scaling requirements

### Acceptance Criteria

#### Real-time Comment Updates
- [ ] WebSocket-based real-time comment synchronization
- [ ] Live comment insertion and thread updates
- [ ] Real-time reaction updates and engagement indicators
- [ ] Optimistic UI updates with rollback on failure
- [ ] Efficient update batching to prevent UI thrashing

#### Notification System
- [ ] @mention notifications with immediate delivery
- [ ] Comment reply notifications for thread participants
- [ ] Activity comment notifications for hosts and interested users
- [ ] Notification preferences and customization options
- [ ] Push notification integration for mobile devices

#### Connection Management
- [ ] Automatic WebSocket connection and reconnection handling
- [ ] Connection health monitoring and status indicators
- [ ] Graceful degradation when real-time is unavailable
- [ ] Connection pooling and load balancing
- [ ] Efficient resource cleanup and memory management

#### Performance Optimization
- [ ] Selective real-time updates based on user context
- [ ] Connection scaling and horizontal load distribution
- [ ] Message queuing and delivery optimization
- [ ] Bandwidth optimization for mobile connections
- [ ] Real-time analytics and performance monitoring

#### Reliability & Resilience
- [ ] Message delivery guarantees and retry mechanisms
- [ ] Offline message queuing and synchronization
- [ ] Error handling and recovery procedures
- [ ] Real-time system health monitoring
- [ ] Automated scaling and load management

### Estimated Effort
**3-4 hours** for experienced backend developer with real-time systems expertise

### Task Breakdown
1. **Real-time Infrastructure & WebSockets** (90 minutes)
   - Build WebSocket-based real-time comment system
   - Implement connection management and reconnection handling
   - Create real-time comment synchronization and updates
   - Add performance optimization for high concurrency

2. **Notifications & Delivery** (90 minutes)
   - Build notification system with @mention and reply alerts
   - Implement push notification integration for mobile
   - Create notification batching and intelligent delivery
   - Add notification preferences and customization

3. **Performance & Monitoring** (60 minutes)
   - Implement connection scaling and load balancing
   - Add real-time performance monitoring and analytics
   - Create reliability and fault tolerance mechanisms
   - Build comprehensive testing and validation

### Deliverables
- [ ] Real-time comment update system with WebSocket integration
- [ ] Notification system with @mention and reply alerts
- [ ] Connection management and automatic reconnection
- [ ] Performance optimization for high-concurrency scenarios
- [ ] Push notification integration for mobile devices
- [ ] Real-time moderation alerts and updates
- [ ] Connection scaling and load balancing
- [ ] Real-time performance monitoring and analytics
- [ ] Reliability and fault tolerance mechanisms

### Technical Specifications

#### Real-time Comment System
```typescript
interface CommentUpdate {
  updateId: string;
  activityId: string;
  updateType: 'new_comment' | 'comment_edit' | 'comment_delete' | 'reaction_update' | 'moderation_action';
  comment?: Comment;
  reactions?: ReactionUpdate;
  moderationAction?: ModerationAction;
  timestamp: Date;
  userId: string;
}

class RealTimeCommentManager {
  private connections = new Map<string, WebSocket>();
  private activitySubscriptions = new Map<string, Set<string>>(); // activityId -> userIds
  private userSubscriptions = new Map<string, Set<string>>(); // userId -> activityIds
  private updateQueue = new Map<string, CommentUpdate[]>(); // userId -> updates
  
  async subscribeToActivityComments(
    userId: string,
    activityId: string,
    ws: WebSocket
  ): Promise<void> {
    // Store connection
    this.connections.set(userId, ws);
    
    // Add to subscriptions
    if (!this.activitySubscriptions.has(activityId)) {
      this.activitySubscriptions.set(activityId, new Set());
    }
    this.activitySubscriptions.get(activityId)!.add(userId);
    
    if (!this.userSubscriptions.has(userId)) {
      this.userSubscriptions.set(userId, new Set());
    }
    this.userSubscriptions.get(userId)!.add(activityId);
    
    // Set up connection handlers
    ws.on('close', () => {
      this.handleConnectionClose(userId, activityId);
    });
    
    ws.on('error', (error) => {
      console.error(`WebSocket error for user ${userId}:`, error);
      this.handleConnectionError(userId, error);
    });
    
    // Send connection confirmation
    ws.send(JSON.stringify({
      type: 'subscription_confirmed',
      activityId,
      timestamp: new Date().toISOString(),
    }));
    
    // Send any queued updates
    await this.flushQueuedUpdates(userId);
  }
  
  async broadcastCommentUpdate(update: CommentUpdate): Promise<void> {
    const subscribers = this.activitySubscriptions.get(update.activityId) || new Set();
    
    // Filter subscribers based on update type and permissions
    const targetUsers = await this.filterTargetUsers(subscribers, update);
    
    for (const userId of targetUsers) {
      await this.sendUpdateToUser(userId, update);
    }
  }
  
  private async sendUpdateToUser(userId: string, update: CommentUpdate): Promise<void> {
    const connection = this.connections.get(userId);
    
    if (connection && connection.readyState === WebSocket.OPEN) {
      try {
        // Check if user should receive this update
        const shouldReceive = await this.shouldUserReceiveUpdate(userId, update);
        if (!shouldReceive) return;
        
        // Send update
        connection.send(JSON.stringify({
          type: 'comment_update',
          data: update,
          timestamp: new Date().toISOString(),
        }));
      } catch (error) {
        console.error(`Failed to send update to user ${userId}:`, error);
        // Queue update for retry
        this.queueUpdateForUser(userId, update);
      }
    } else {
      // Connection not available, queue update
      this.queueUpdateForUser(userId, update);
    }
  }
  
  private queueUpdateForUser(userId: string, update: CommentUpdate): void {
    if (!this.updateQueue.has(userId)) {
      this.updateQueue.set(userId, []);
    }
    
    const queue = this.updateQueue.get(userId)!;
    queue.push(update);
    
    // Limit queue size to prevent memory issues
    if (queue.length > 100) {
      queue.shift(); // Remove oldest update
    }
  }
  
  private async flushQueuedUpdates(userId: string): Promise<void> {
    const updates = this.updateQueue.get(userId) || [];
    if (updates.length === 0) return;
    
    // Clear queue
    this.updateQueue.delete(userId);
    
    // Send batched updates
    const connection = this.connections.get(userId);
    if (connection && connection.readyState === WebSocket.OPEN) {
      try {
        connection.send(JSON.stringify({
          type: 'batched_updates',
          updates: updates.slice(-20), // Send last 20 updates
          timestamp: new Date().toISOString(),
        }));
      } catch (error) {
        console.error(`Failed to send queued updates to user ${userId}:`, error);
        // Re-queue updates
        this.updateQueue.set(userId, updates);
      }
    }
  }
  
  private async shouldUserReceiveUpdate(
    userId: string,
    update: CommentUpdate
  ): Promise<boolean> {
    // Don't send updates for user's own actions
    if (update.userId === userId) return false;
    
    // Check user's notification preferences
    const preferences = await this.getUserNotificationPreferences(userId);
    
    switch (update.updateType) {
      case 'new_comment':
        return preferences.newComments;
      case 'comment_edit':
        return preferences.commentEdits;
      case 'reaction_update':
        return preferences.reactions;
      case 'moderation_action':
        return preferences.moderationActions;
      default:
        return true;
    }
  }
}
```

#### Notification System
```typescript
interface CommentNotification {
  id: string;
  userId: string;
  type: 'mention' | 'reply' | 'reaction' | 'activity_comment';
  commentId: string;
  activityId: string;
  triggeredBy: string;
  message: string;
  data: NotificationData;
  status: 'pending' | 'sent' | 'read' | 'dismissed';
  createdAt: Date;
  sentAt?: Date;
  readAt?: Date;
}

class CommentNotificationService {
  async createMentionNotification(
    commentId: string,
    mentionedUserId: string,
    mentioningUserId: string
  ): Promise<void> {
    const comment = await this.getComment(commentId);
    if (!comment) return;
    
    // Check if user wants mention notifications
    const preferences = await this.getUserNotificationPreferences(mentionedUserId);
    if (!preferences.mentions) return;
    
    // Create notification
    const notification: CommentNotification = {
      id: generateId(),
      userId: mentionedUserId,
      type: 'mention',
      commentId,
      activityId: comment.activityId,
      triggeredBy: mentioningUserId,
      message: await this.generateMentionMessage(comment, mentioningUserId),
      data: {
        commentPreview: this.truncateComment(comment.content, 100),
        activityTitle: comment.activity.title,
        mentioningUserName: await this.getUserName(mentioningUserId),
      },
      status: 'pending',
      createdAt: new Date(),
    };
    
    await this.saveNotification(notification);
    
    // Send notification immediately
    await this.sendNotification(notification);
  }
  
  async createReplyNotification(
    replyCommentId: string,
    parentCommentId: string
  ): Promise<void> {
    const [reply, parentComment] = await Promise.all([
      this.getComment(replyCommentId),
      this.getComment(parentCommentId),
    ]);
    
    if (!reply || !parentComment) return;
    
    // Don't notify if replying to own comment
    if (reply.authorId === parentComment.authorId) return;
    
    // Check notification preferences
    const preferences = await this.getUserNotificationPreferences(parentComment.authorId);
    if (!preferences.replies) return;
    
    // Create notification
    const notification: CommentNotification = {
      id: generateId(),
      userId: parentComment.authorId,
      type: 'reply',
      commentId: replyCommentId,
      activityId: reply.activityId,
      triggeredBy: reply.authorId,
      message: await this.generateReplyMessage(reply, parentComment),
      data: {
        replyPreview: this.truncateComment(reply.content, 100),
        originalCommentPreview: this.truncateComment(parentComment.content, 50),
        activityTitle: reply.activity.title,
        replyingUserName: await this.getUserName(reply.authorId),
      },
      status: 'pending',
      createdAt: new Date(),
    };
    
    await this.saveNotification(notification);
    await this.sendNotification(notification);
  }
  
  private async sendNotification(notification: CommentNotification): Promise<void> {
    try {
      // Send real-time notification if user is online
      await this.sendRealTimeNotification(notification);
      
      // Send push notification for mobile
      await this.sendPushNotification(notification);
      
      // Send email notification if configured
      await this.sendEmailNotification(notification);
      
      // Update notification status
      await this.updateNotificationStatus(notification.id, 'sent');
    } catch (error) {
      console.error(`Failed to send notification ${notification.id}:`, error);
      // Retry logic could be implemented here
    }
  }
  
  private async sendRealTimeNotification(notification: CommentNotification): Promise<void> {
    const connection = this.realTimeManager.getConnection(notification.userId);
    
    if (connection && connection.readyState === WebSocket.OPEN) {
      connection.send(JSON.stringify({
        type: 'notification',
        data: notification,
        timestamp: new Date().toISOString(),
      }));
    }
  }
  
  private async sendPushNotification(notification: CommentNotification): Promise<void> {
    const userDevices = await this.getUserDevices(notification.userId);
    
    for (const device of userDevices) {
      if (device.pushToken && device.notificationsEnabled) {
        await this.pushNotificationService.send({
          token: device.pushToken,
          title: this.generatePushTitle(notification),
          body: notification.message,
          data: {
            type: 'comment_notification',
            commentId: notification.commentId,
            activityId: notification.activityId,
          },
        });
      }
    }
  }
  
  async batchNotifications(
    userId: string,
    notifications: CommentNotification[]
  ): Promise<void> {
    if (notifications.length === 0) return;
    
    // Group notifications by type and activity
    const grouped = this.groupNotifications(notifications);
    
    // Create batched notifications
    for (const [key, group] of grouped.entries()) {
      const batchedNotification = this.createBatchedNotification(group);
      await this.sendNotification(batchedNotification);
    }
  }
  
  private groupNotifications(
    notifications: CommentNotification[]
  ): Map<string, CommentNotification[]> {
    const groups = new Map<string, CommentNotification[]>();
    
    for (const notification of notifications) {
      const key = `${notification.type}_${notification.activityId}`;
      
      if (!groups.has(key)) {
        groups.set(key, []);
      }
      groups.get(key)!.push(notification);
    }
    
    return groups;
  }
}
```

#### Performance Optimization
```typescript
class RealTimePerformanceOptimizer {
  private connectionPool: ConnectionPool;
  private messageQueue: MessageQueue;
  private rateLimiter: RateLimiter;
  
  constructor() {
    this.connectionPool = new ConnectionPool({
      maxConnections: 10000,
      connectionTimeout: 30000,
      heartbeatInterval: 30000,
    });
    
    this.messageQueue = new MessageQueue({
      maxQueueSize: 1000,
      batchSize: 10,
      batchTimeout: 1000,
    });
    
    this.rateLimiter = new RateLimiter({
      maxUpdatesPerSecond: 10,
      burstLimit: 50,
    });
  }
  
  async optimizeConnectionDistribution(): Promise<void> {
    const connections = this.connectionPool.getAllConnections();
    const serverLoad = await this.getServerLoad();
    
    // Redistribute connections if load is uneven
    if (serverLoad.maxLoad - serverLoad.minLoad > 0.3) {
      await this.redistributeConnections(connections, serverLoad);
    }
  }
  
  async optimizeMessageDelivery(
    updates: CommentUpdate[]
  ): Promise<void> {
    // Batch similar updates
    const batched = this.batchSimilarUpdates(updates);
    
    // Apply rate limiting
    const rateLimited = await this.applyRateLimit(batched);
    
    // Queue for delivery
    for (const update of rateLimited) {
      await this.messageQueue.enqueue(update);
    }
  }
  
  private batchSimilarUpdates(updates: CommentUpdate[]): CommentUpdate[] {
    const batches = new Map<string, CommentUpdate[]>();
    
    for (const update of updates) {
      const key = `${update.activityId}_${update.updateType}`;
      
      if (!batches.has(key)) {
        batches.set(key, []);
      }
      batches.get(key)!.push(update);
    }
    
    // Create batched updates
    const batchedUpdates: CommentUpdate[] = [];
    
    for (const [key, batch] of batches.entries()) {
      if (batch.length === 1) {
        batchedUpdates.push(batch[0]);
      } else {
        batchedUpdates.push(this.createBatchedUpdate(batch));
      }
    }
    
    return batchedUpdates;
  }
  
  async monitorPerformance(): Promise<PerformanceMetrics> {
    const metrics = {
      activeConnections: this.connectionPool.getActiveConnectionCount(),
      messageQueueSize: this.messageQueue.getSize(),
      averageLatency: await this.calculateAverageLatency(),
      throughput: await this.calculateThroughput(),
      errorRate: await this.calculateErrorRate(),
      memoryUsage: process.memoryUsage(),
      cpuUsage: await this.getCPUUsage(),
    };
    
    // Check for performance issues
    await this.checkPerformanceThresholds(metrics);
    
    return metrics;
  }
  
  private async checkPerformanceThresholds(metrics: PerformanceMetrics): Promise<void> {
    // Check connection limits
    if (metrics.activeConnections > 8000) {
      await this.alertManager.sendAlert({
        type: 'high_connection_count',
        severity: 'warning',
        message: `High connection count: ${metrics.activeConnections}`,
      });
    }
    
    // Check latency
    if (metrics.averageLatency > 5000) {
      await this.alertManager.sendAlert({
        type: 'high_latency',
        severity: 'critical',
        message: `High average latency: ${metrics.averageLatency}ms`,
      });
    }
    
    // Check error rate
    if (metrics.errorRate > 0.05) {
      await this.alertManager.sendAlert({
        type: 'high_error_rate',
        severity: 'critical',
        message: `High error rate: ${(metrics.errorRate * 100).toFixed(2)}%`,
      });
    }
  }
}
```

#### Connection Management
```typescript
class ConnectionManager {
  private connections = new Map<string, ConnectionInfo>();
  private heartbeatInterval: NodeJS.Timeout;
  
  constructor() {
    // Start heartbeat monitoring
    this.heartbeatInterval = setInterval(() => {
      this.performHeartbeatCheck();
    }, 30000); // Every 30 seconds
  }
  
  async addConnection(userId: string, ws: WebSocket): Promise<void> {
    // Close existing connection if any
    const existing = this.connections.get(userId);
    if (existing) {
      existing.socket.close();
    }
    
    // Add new connection
    this.connections.set(userId, {
      socket: ws,
      userId,
      connectedAt: new Date(),
      lastHeartbeat: new Date(),
      subscriptions: new Set(),
    });
    
    // Set up connection handlers
    ws.on('pong', () => {
      this.updateHeartbeat(userId);
    });
    
    ws.on('close', () => {
      this.removeConnection(userId);
    });
    
    ws.on('error', (error) => {
      console.error(`Connection error for user ${userId}:`, error);
      this.removeConnection(userId);
    });
  }
  
  private async performHeartbeatCheck(): Promise<void> {
    const now = new Date();
    const timeout = 60000; // 1 minute timeout
    
    for (const [userId, connection] of this.connections.entries()) {
      const timeSinceHeartbeat = now.getTime() - connection.lastHeartbeat.getTime();
      
      if (timeSinceHeartbeat > timeout) {
        // Connection is stale, remove it
        console.log(`Removing stale connection for user ${userId}`);
        this.removeConnection(userId);
      } else if (timeSinceHeartbeat > timeout / 2) {
        // Send ping to check connection
        try {
          connection.socket.ping();
        } catch (error) {
          console.error(`Failed to ping user ${userId}:`, error);
          this.removeConnection(userId);
        }
      }
    }
  }
  
  private updateHeartbeat(userId: string): void {
    const connection = this.connections.get(userId);
    if (connection) {
      connection.lastHeartbeat = new Date();
    }
  }
  
  private removeConnection(userId: string): void {
    const connection = this.connections.get(userId);
    if (connection) {
      try {
        connection.socket.close();
      } catch (error) {
        // Connection already closed
      }
      
      this.connections.delete(userId);
    }
  }
  
  getConnectionStats(): ConnectionStats {
    const connections = Array.from(this.connections.values());
    
    return {
      totalConnections: connections.length,
      averageConnectionAge: this.calculateAverageConnectionAge(connections),
      connectionsByStatus: this.groupConnectionsByStatus(connections),
      subscriptionStats: this.calculateSubscriptionStats(connections),
    };
  }
}
```

### Quality Checklist
- [ ] Real-time updates deliver consistently within target timeframes
- [ ] Notification system provides relevant, timely alerts
- [ ] Connection management handles failures and reconnections gracefully
- [ ] Performance optimization maintains responsiveness under high load
- [ ] WebSocket connections scale efficiently with user growth
- [ ] Message delivery guarantees prevent lost updates
- [ ] Real-time features enhance rather than disrupt user experience
- [ ] Monitoring and alerting detect issues before they impact users

---

**Status**: 🔄 Ready for Problem Definition Phase  
**Assignee**: Backend Developer (Real-time Systems)  
**Epic**: E05 Social Interaction  
**Feature**: F01 Comment & Discussion System  
**Dependencies**: T02 Comment Infrastructure, T03 Comment Frontend, WebSocket Infrastructure, Push Notification Services  
**Blocks**: Complete Real-time Comment Experience
