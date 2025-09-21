# T02 Comment Backend Infrastructure & APIs

## Problem Definition

### Task Overview
Implement comprehensive backend infrastructure for comment and discussion systems including threaded comment storage, rich text processing, comment APIs, and performance optimization. This includes building scalable comment systems that support complex threading while maintaining high performance.

### Problem Statement
The platform needs robust comment infrastructure to:
- **Store threaded discussions**: Efficiently store and retrieve nested comment structures
- **Process rich content**: Handle formatted text, media attachments, and @mentions
- **Scale with engagement**: Support thousands of comments per activity without performance degradation
- **Enable real-time features**: Provide APIs that support real-time comment updates
- **Support moderation**: Include moderation capabilities and content filtering

### Scope
**In Scope:**
- Comment database schema with efficient threading support
- Comment CRUD APIs with threading and pagination
- Rich text processing and sanitization
- @mention processing and notification triggers
- Comment search and filtering capabilities
- Performance optimization for large comment threads
- Comment moderation API endpoints

**Out of Scope:**
- Frontend comment components (covered in T03)
- Real-time WebSocket infrastructure (covered in T06)
- Comment moderation UI (covered in T04)
- Comment analytics (covered in T05)

### Success Criteria
- [ ] Comment APIs respond in under 200ms for 95% of requests
- [ ] Threading queries scale to 10,000+ comments per activity
- [ ] Rich text processing handles all common formatting safely
- [ ] @mention processing triggers notifications within 5 seconds
- [ ] Comment search returns results in under 100ms
- [ ] API supports 1,000+ concurrent comment operations

### Dependencies
- **Requires**: E01 Database infrastructure and user authentication
- **Requires**: E02 User profiles for comment attribution and @mentions
- **Requires**: E03 Activity data for comment context
- **Requires**: Notification system for @mention alerts
- **Blocks**: T03 Frontend implementation needs comment APIs
- **Blocks**: T04 Moderation system needs comment infrastructure

### Acceptance Criteria

#### Comment Data Model
- [ ] Efficient threaded comment storage with parent-child relationships
- [ ] Rich text content storage with formatting preservation
- [ ] Comment metadata (author, timestamp, edit history, reactions)
- [ ] Soft deletion support for moderated content
- [ ] Optimized indexing for threading and search queries

#### Comment APIs
- [ ] RESTful comment CRUD operations with proper error handling
- [ ] Threaded comment retrieval with configurable depth limits
- [ ] Pagination support for large comment threads
- [ ] Comment search and filtering by author, content, date
- [ ] Bulk operations for moderation and management

#### Rich Text Processing
- [ ] Safe HTML sanitization preventing XSS attacks
- [ ] Markdown-to-HTML conversion with formatting support
- [ ] Media attachment processing and validation
- [ ] Link preview generation and metadata extraction
- [ ] @mention parsing and user resolution

#### Performance Optimization
- [ ] Database query optimization for threaded comment retrieval
- [ ] Caching strategies for popular comment threads
- [ ] Efficient pagination for large comment sets
- [ ] Background processing for heavy operations
- [ ] Connection pooling and query batching

#### Security & Validation
- [ ] Input validation and sanitization for all comment data
- [ ] Rate limiting to prevent comment spam
- [ ] Authentication and authorization for comment operations
- [ ] Content filtering for inappropriate material
- [ ] Audit logging for moderation and security

### Estimated Effort
**4 hours** for experienced backend developer

### Task Breakdown
1. **Database Schema & Models** (90 minutes)
   - Design comment database schema with threading support
   - Create comment data models and relationships
   - Implement database migrations and indexing
   - Add soft deletion and audit trail support

2. **Comment APIs & Processing** (120 minutes)
   - Build RESTful comment CRUD APIs
   - Implement threaded comment retrieval and pagination
   - Create rich text processing and sanitization
   - Add @mention processing and notification triggers

3. **Performance & Security** (30 minutes)
   - Implement caching strategies and query optimization
   - Add rate limiting and security measures
   - Create comment search and filtering capabilities
   - Build comprehensive testing and validation

### Deliverables
- [ ] Comment database schema with efficient threading support
- [ ] RESTful comment APIs with full CRUD operations
- [ ] Rich text processing and sanitization system
- [ ] @mention processing and notification integration
- [ ] Comment search and filtering capabilities
- [ ] Performance optimization with caching strategies
- [ ] Security measures and input validation
- [ ] API documentation and testing suite
- [ ] Database migrations and indexing optimization

### Technical Specifications

#### Comment Database Schema
```sql
-- Comments table with threaded structure
CREATE TABLE comments (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    activity_id UUID NOT NULL REFERENCES activities(id),
    author_id UUID NOT NULL REFERENCES users(id),
    parent_id UUID REFERENCES comments(id), -- For threading
    content TEXT NOT NULL,
    content_html TEXT, -- Processed HTML version
    thread_path LTREE, -- Materialized path for efficient queries
    depth INTEGER DEFAULT 0,
    
    -- Engagement metrics
    like_count INTEGER DEFAULT 0,
    reply_count INTEGER DEFAULT 0,
    reaction_counts JSONB DEFAULT '{}',
    
    -- Moderation
    status comment_status DEFAULT 'active',
    moderated_at TIMESTAMP,
    moderated_by UUID REFERENCES users(id),
    moderation_reason TEXT,
    
    -- Metadata
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),
    deleted_at TIMESTAMP,
    edit_history JSONB DEFAULT '[]',
    
    -- Indexes
    INDEX idx_comments_activity_thread (activity_id, thread_path),
    INDEX idx_comments_author (author_id),
    INDEX idx_comments_parent (parent_id),
    INDEX idx_comments_status (status),
    INDEX idx_comments_created (created_at DESC)
);

-- Comment reactions table
CREATE TABLE comment_reactions (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    comment_id UUID NOT NULL REFERENCES comments(id),
    user_id UUID NOT NULL REFERENCES users(id),
    reaction_type reaction_type NOT NULL,
    created_at TIMESTAMP DEFAULT NOW(),
    
    UNIQUE(comment_id, user_id, reaction_type),
    INDEX idx_comment_reactions_comment (comment_id),
    INDEX idx_comment_reactions_user (user_id)
);

-- Comment mentions table
CREATE TABLE comment_mentions (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    comment_id UUID NOT NULL REFERENCES comments(id),
    mentioned_user_id UUID NOT NULL REFERENCES users(id),
    mention_text TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT NOW(),
    
    INDEX idx_comment_mentions_user (mentioned_user_id),
    INDEX idx_comment_mentions_comment (comment_id)
);
```

#### Comment API Endpoints
```typescript
// Comment CRUD operations
POST   /api/activities/:activityId/comments           // Create comment
GET    /api/activities/:activityId/comments           // Get comments (threaded)
GET    /api/comments/:commentId                       // Get specific comment
PUT    /api/comments/:commentId                       // Update comment
DELETE /api/comments/:commentId                       // Delete comment

// Comment threading and replies
POST   /api/comments/:commentId/replies               // Reply to comment
GET    /api/comments/:commentId/replies               // Get comment replies
GET    /api/comments/:commentId/thread                // Get full thread

// Comment reactions
POST   /api/comments/:commentId/reactions             // Add reaction
DELETE /api/comments/:commentId/reactions/:type       // Remove reaction
GET    /api/comments/:commentId/reactions             // Get reactions

// Comment search and filtering
GET    /api/activities/:activityId/comments/search    // Search comments
GET    /api/users/:userId/comments                    // Get user's comments

// Comment moderation
POST   /api/comments/:commentId/report                // Report comment
PUT    /api/comments/:commentId/moderate              // Moderate comment
GET    /api/comments/moderation-queue                 // Get moderation queue
```

#### Comment Service Implementation
```typescript
interface Comment {
  id: string;
  activityId: string;
  authorId: string;
  parentId?: string;
  content: string;
  contentHtml: string;
  threadPath: string;
  depth: number;
  
  // Engagement
  likeCount: number;
  replyCount: number;
  reactionCounts: Record<string, number>;
  
  // Moderation
  status: 'active' | 'pending' | 'hidden' | 'deleted';
  moderatedAt?: Date;
  moderatedBy?: string;
  moderationReason?: string;
  
  // Metadata
  createdAt: Date;
  updatedAt: Date;
  deletedAt?: Date;
  editHistory: CommentEdit[];
}

class CommentService {
  async createComment(
    activityId: string,
    authorId: string,
    content: string,
    parentId?: string
  ): Promise<Comment> {
    // Validate input and permissions
    await this.validateCommentPermissions(activityId, authorId);
    
    // Process rich text content
    const processedContent = await this.processRichText(content);
    
    // Calculate thread path and depth
    const { threadPath, depth } = await this.calculateThreadPosition(parentId);
    
    // Create comment
    const comment = await this.db.comments.create({
      data: {
        activityId,
        authorId,
        parentId,
        content,
        contentHtml: processedContent.html,
        threadPath,
        depth,
      },
    });
    
    // Process @mentions
    await this.processMentions(comment.id, processedContent.mentions);
    
    // Update parent reply count
    if (parentId) {
      await this.updateReplyCount(parentId);
    }
    
    return comment;
  }
  
  async getThreadedComments(
    activityId: string,
    options: CommentQueryOptions = {}
  ): Promise<ThreadedComment[]> {
    const {
      limit = 50,
      offset = 0,
      maxDepth = 10,
      sortBy = 'created_at',
      order = 'asc',
    } = options;
    
    // Query comments with threading
    const comments = await this.db.comments.findMany({
      where: {
        activityId,
        status: 'active',
        depth: { lte: maxDepth },
      },
      orderBy: [
        { threadPath: 'asc' },
        { [sortBy]: order },
      ],
      take: limit,
      skip: offset,
      include: {
        author: {
          select: { id: true, name: true, avatar: true },
        },
        reactions: {
          include: {
            user: {
              select: { id: true, name: true },
            },
          },
        },
      },
    });
    
    // Build threaded structure
    return this.buildThreadedStructure(comments);
  }
  
  private async processRichText(content: string): Promise<ProcessedContent> {
    // Sanitize HTML to prevent XSS
    const sanitizedHtml = this.sanitizeHtml(content);
    
    // Extract @mentions
    const mentions = this.extractMentions(content);
    
    // Process links and generate previews
    const processedHtml = await this.processLinks(sanitizedHtml);
    
    return {
      html: processedHtml,
      mentions,
    };
  }
  
  private async calculateThreadPosition(parentId?: string): Promise<ThreadPosition> {
    if (!parentId) {
      // Root comment
      const commentCount = await this.db.comments.count({
        where: { parentId: null },
      });
      
      return {
        threadPath: `${commentCount + 1}`,
        depth: 0,
      };
    }
    
    // Get parent comment
    const parent = await this.db.comments.findUnique({
      where: { id: parentId },
      select: { threadPath: true, depth: true },
    });
    
    if (!parent) {
      throw new Error('Parent comment not found');
    }
    
    // Calculate child position
    const siblingCount = await this.db.comments.count({
      where: { parentId },
    });
    
    return {
      threadPath: `${parent.threadPath}.${siblingCount + 1}`,
      depth: parent.depth + 1,
    };
  }
  
  private async processMentions(
    commentId: string,
    mentions: string[]
  ): Promise<void> {
    for (const mention of mentions) {
      // Find mentioned user
      const user = await this.db.users.findFirst({
        where: {
          OR: [
            { username: mention },
            { email: mention },
          ],
        },
      });
      
      if (user) {
        // Store mention
        await this.db.commentMentions.create({
          data: {
            commentId,
            mentionedUserId: user.id,
            mentionText: mention,
          },
        });
        
        // Trigger notification
        await this.notificationService.createNotification({
          userId: user.id,
          type: 'comment_mention',
          entityId: commentId,
          entityType: 'comment',
        });
      }
    }
  }
}
```

#### Performance Optimization
```typescript
class CommentPerformanceOptimizer {
  private cacheManager: CacheManager;
  
  async getCachedComments(
    activityId: string,
    options: CommentQueryOptions
  ): Promise<ThreadedComment[] | null> {
    const cacheKey = this.generateCacheKey(activityId, options);
    
    try {
      const cached = await this.cacheManager.get(cacheKey);
      if (cached) {
        return JSON.parse(cached);
      }
    } catch (error) {
      console.error('Cache retrieval error:', error);
    }
    
    return null;
  }
  
  async cacheComments(
    activityId: string,
    options: CommentQueryOptions,
    comments: ThreadedComment[]
  ): Promise<void> {
    const cacheKey = this.generateCacheKey(activityId, options);
    const ttl = 300; // 5 minutes
    
    try {
      await this.cacheManager.setex(
        cacheKey,
        ttl,
        JSON.stringify(comments)
      );
    } catch (error) {
      console.error('Cache storage error:', error);
    }
  }
  
  async invalidateCommentCache(activityId: string): Promise<void> {
    const pattern = `comments:${activityId}:*`;
    
    try {
      const keys = await this.cacheManager.keys(pattern);
      if (keys.length > 0) {
        await this.cacheManager.del(...keys);
      }
    } catch (error) {
      console.error('Cache invalidation error:', error);
    }
  }
}
```

### Quality Checklist
- [ ] Comment database schema efficiently supports threading and queries
- [ ] APIs provide comprehensive comment functionality with proper error handling
- [ ] Rich text processing is secure and prevents XSS attacks
- [ ] @mention processing triggers notifications reliably
- [ ] Performance optimization handles large comment threads effectively
- [ ] Security measures prevent spam and abuse
- [ ] API documentation is comprehensive and accurate
- [ ] Testing covers all comment operations and edge cases

---

**Status**: 🔄 Ready for Problem Definition Phase  
**Assignee**: Backend Developer  
**Epic**: E05 Social Interaction  
**Feature**: F01 Comment & Discussion System  
**Dependencies**: Database Infrastructure (E01), User Profiles (E02), Activity Data (E03), Notification System  
**Blocks**: T03 Frontend Implementation, T04 Moderation System
