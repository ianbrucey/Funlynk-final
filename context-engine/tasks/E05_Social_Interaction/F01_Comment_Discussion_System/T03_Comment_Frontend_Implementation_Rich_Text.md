# T03 Comment Frontend Implementation & Rich Text

## Problem Definition

### Task Overview
Implement React Native comment components and interfaces following UX designs, including threaded comment display, rich text editing, comment reactions, and @mention functionality. This includes building engaging comment experiences that encourage meaningful discussions while maintaining performance.

### Problem Statement
Users need intuitive, responsive comment interfaces that:
- **Display threaded discussions clearly**: Show complex comment hierarchies in readable formats
- **Enable rich content creation**: Provide powerful yet simple rich text editing capabilities
- **Support social interactions**: Allow reactions, @mentions, and social engagement
- **Perform smoothly**: Handle large comment threads without performance issues
- **Work seamlessly across devices**: Provide consistent experience on mobile and desktop

### Scope
**In Scope:**
- Threaded comment display components with visual hierarchy
- Rich text comment editor with formatting and media support
- Comment reaction and engagement components
- @mention functionality with user autocomplete
- Comment navigation and thread management
- Mobile-optimized comment interactions
- Comment loading states and error handling

**Out of Scope:**
- Backend comment APIs (covered in T02)
- Comment moderation interfaces (covered in T04)
- Real-time update mechanisms (covered in T06)
- Comment analytics (covered in T05)

### Success Criteria
- [ ] Comment components achieve 90%+ user satisfaction in testing
- [ ] Rich text editor is intuitive for 95% of users without training
- [ ] Threading visualization supports 10+ levels without confusion
- [ ] Comment performance maintains 60fps with 1000+ comments
- [ ] @mention functionality has 95%+ accuracy in user matching
- [ ] Mobile comment experience drives 35%+ higher engagement

### Dependencies
- **Requires**: T01 UX designs and component specifications
- **Requires**: T02 Backend comment APIs and infrastructure
- **Requires**: Funlynk design system components
- **Requires**: User profile data for @mentions and attribution
- **Blocks**: User acceptance testing and comment workflows
- **Informs**: T04 Moderation system (frontend integration points)

### Acceptance Criteria

#### Threaded Comment Display
- [ ] Visual hierarchy clearly shows comment relationships and nesting
- [ ] Threading supports up to 10 levels with clear visual indicators
- [ ] Collapse/expand functionality for managing long threads
- [ ] Smooth scrolling and navigation within comment threads
- [ ] Loading states and pagination for large comment sets

#### Rich Text Editor
- [ ] Intuitive formatting toolbar with common text options
- [ ] Real-time preview of formatted content
- [ ] Media attachment support for images and links
- [ ] @mention autocomplete with user search
- [ ] Keyboard shortcuts for power users

#### Comment Interactions
- [ ] Quick reaction buttons with multiple emotion options
- [ ] Reply functionality that maintains thread context
- [ ] Comment sharing and permalink generation
- [ ] Save/bookmark comments for later reference
- [ ] Report comment functionality with clear options

#### Performance Optimization
- [ ] Virtualized scrolling for large comment threads
- [ ] Lazy loading of comment content and media
- [ ] Efficient re-rendering with proper memoization
- [ ] Memory management for extended comment sessions
- [ ] Smooth animations and transitions

#### Mobile Optimization
- [ ] Touch-friendly comment interactions with appropriate tap targets
- [ ] Swipe gestures for comment actions
- [ ] Optimized keyboard handling for text input
- [ ] Responsive design adapting to different screen sizes
- [ ] Offline comment viewing with cached data

### Estimated Effort
**4 hours** for experienced React Native developer

### Task Breakdown
1. **Core Comment Components** (120 minutes)
   - Build threaded comment display components
   - Implement rich text comment editor
   - Create comment reaction and engagement components
   - Add comment navigation and thread management

2. **Advanced Features & Performance** (90 minutes)
   - Implement @mention functionality with autocomplete
   - Add performance optimization for large comment threads
   - Create comment loading states and error handling
   - Build comment sharing and permalink features

3. **Mobile Optimization & Integration** (60 minutes)
   - Optimize components for mobile interactions
   - Add swipe gestures and touch-friendly controls
   - Implement offline comment viewing
   - Create comprehensive testing and validation

### Deliverables
- [ ] Threaded comment display components with visual hierarchy
- [ ] Rich text comment editor with formatting and media support
- [ ] Comment reaction and engagement components
- [ ] @mention functionality with user autocomplete
- [ ] Comment navigation and thread management
- [ ] Performance optimization for large comment threads
- [ ] Mobile-optimized comment interactions
- [ ] Comment loading states and error handling
- [ ] Component tests with 90%+ coverage

### Technical Specifications

#### Comment Component Architecture
```typescript
interface CommentComponentProps {
  comment: Comment;
  depth: number;
  maxDepth?: number;
  onReply: (parentId: string) => void;
  onReact: (commentId: string, reaction: ReactionType) => void;
  onReport: (commentId: string) => void;
  onMention: (userId: string) => void;
  showReplies?: boolean;
  isCollapsed?: boolean;
}

const CommentComponent: React.FC<CommentComponentProps> = ({
  comment,
  depth,
  maxDepth = 10,
  onReply,
  onReact,
  onReport,
  onMention,
  showReplies = true,
  isCollapsed = false,
}) => {
  const [repliesVisible, setRepliesVisible] = useState(!isCollapsed);
  const [replyEditorVisible, setReplyEditorVisible] = useState(false);
  
  const indentationStyle = {
    marginLeft: Math.min(depth * 20, maxDepth * 20),
    borderLeftWidth: depth > 0 ? 2 : 0,
    borderLeftColor: colors.gray[200],
    paddingLeft: depth > 0 ? 12 : 0,
  };
  
  const handleReply = () => {
    setReplyEditorVisible(true);
  };
  
  const handleReplySubmit = (content: string) => {
    onReply(comment.id);
    setReplyEditorVisible(false);
  };
  
  const handleReaction = (reaction: ReactionType) => {
    onReact(comment.id, reaction);
  };
  
  return (
    <View style={[styles.commentContainer, indentationStyle]}>
      <View style={styles.commentHeader}>
        <UserAvatar
          user={comment.author}
          size="small"
          onPress={() => onMention(comment.author.id)}
        />
        <View style={styles.commentMeta}>
          <Text style={styles.authorName}>{comment.author.name}</Text>
          <Text style={styles.timestamp}>
            {formatRelativeTime(comment.createdAt)}
          </Text>
          {comment.editHistory.length > 0 && (
            <Text style={styles.editedIndicator}>edited</Text>
          )}
        </View>
      </View>
      
      <View style={styles.commentContent}>
        <RichTextDisplay
          content={comment.contentHtml}
          onMentionPress={onMention}
          onLinkPress={(url) => Linking.openURL(url)}
        />
      </View>
      
      <CommentActions
        comment={comment}
        onReply={handleReply}
        onReact={handleReaction}
        onReport={() => onReport(comment.id)}
        onShare={() => shareComment(comment)}
      />
      
      {replyEditorVisible && (
        <CommentEditor
          parentId={comment.id}
          onSubmit={handleReplySubmit}
          onCancel={() => setReplyEditorVisible(false)}
          placeholder={`Reply to ${comment.author.name}...`}
        />
      )}
      
      {showReplies && comment.replies && comment.replies.length > 0 && (
        <View style={styles.repliesContainer}>
          <TouchableOpacity
            style={styles.toggleReplies}
            onPress={() => setRepliesVisible(!repliesVisible)}
          >
            <Icon
              name={repliesVisible ? 'chevron-up' : 'chevron-down'}
              size={16}
              color={colors.gray[500]}
            />
            <Text style={styles.replyCount}>
              {comment.replies.length} {comment.replies.length === 1 ? 'reply' : 'replies'}
            </Text>
          </TouchableOpacity>
          
          {repliesVisible && (
            <View style={styles.replies}>
              {comment.replies.map((reply) => (
                <CommentComponent
                  key={reply.id}
                  comment={reply}
                  depth={depth + 1}
                  maxDepth={maxDepth}
                  onReply={onReply}
                  onReact={onReact}
                  onReport={onReport}
                  onMention={onMention}
                />
              ))}
            </View>
          )}
        </View>
      )}
    </View>
  );
};
```

#### Rich Text Comment Editor
```typescript
interface CommentEditorProps {
  parentId?: string;
  initialContent?: string;
  placeholder?: string;
  onSubmit: (content: string, mentions: string[]) => void;
  onCancel?: () => void;
  maxLength?: number;
}

const CommentEditor: React.FC<CommentEditorProps> = ({
  parentId,
  initialContent = '',
  placeholder = 'Write a comment...',
  onSubmit,
  onCancel,
  maxLength = 2000,
}) => {
  const [content, setContent] = useState(initialContent);
  const [mentions, setMentions] = useState<string[]>([]);
  const [showPreview, setShowPreview] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);
  
  const editorRef = useRef<RichTextEditor>(null);
  
  const handleSubmit = async () => {
    if (content.trim().length === 0) return;
    
    setIsSubmitting(true);
    try {
      await onSubmit(content, mentions);
      setContent('');
      setMentions([]);
    } catch (error) {
      console.error('Failed to submit comment:', error);
    } finally {
      setIsSubmitting(false);
    }
  };
  
  const handleMentionSelect = (user: User) => {
    const mentionText = `@${user.username}`;
    const newMentions = [...mentions, user.username];
    setMentions(newMentions);
    
    // Insert mention into editor
    editorRef.current?.insertText(mentionText);
  };
  
  return (
    <View style={styles.editorContainer}>
      <View style={styles.editorHeader}>
        <FormattingToolbar
          onFormat={(format) => editorRef.current?.applyFormat(format)}
          onTogglePreview={() => setShowPreview(!showPreview)}
        />
      </View>
      
      {showPreview ? (
        <View style={styles.previewContainer}>
          <RichTextDisplay content={content} />
        </View>
      ) : (
        <RichTextEditor
          ref={editorRef}
          value={content}
          onChangeText={setContent}
          placeholder={placeholder}
          maxLength={maxLength}
          multiline
          style={styles.editor}
          onMentionTrigger={(query) => (
            <MentionAutocomplete
              query={query}
              onSelect={handleMentionSelect}
            />
          )}
        />
      )}
      
      <View style={styles.editorFooter}>
        <Text style={styles.characterCount}>
          {content.length}/{maxLength}
        </Text>
        
        <View style={styles.editorActions}>
          {onCancel && (
            <TouchableOpacity
              style={styles.cancelButton}
              onPress={onCancel}
            >
              <Text style={styles.cancelButtonText}>Cancel</Text>
            </TouchableOpacity>
          )}
          
          <TouchableOpacity
            style={[
              styles.submitButton,
              content.trim().length === 0 && styles.submitButtonDisabled,
            ]}
            onPress={handleSubmit}
            disabled={content.trim().length === 0 || isSubmitting}
          >
            {isSubmitting ? (
              <ActivityIndicator size="small" color={colors.white} />
            ) : (
              <Text style={styles.submitButtonText}>
                {parentId ? 'Reply' : 'Comment'}
              </Text>
            )}
          </TouchableOpacity>
        </View>
      </View>
    </View>
  );
};
```

#### Comment Reactions Component
```typescript
interface CommentActionsProps {
  comment: Comment;
  onReply: () => void;
  onReact: (reaction: ReactionType) => void;
  onReport: () => void;
  onShare: () => void;
}

const CommentActions: React.FC<CommentActionsProps> = ({
  comment,
  onReply,
  onReact,
  onReport,
  onShare,
}) => {
  const [showReactions, setShowReactions] = useState(false);
  const [userReactions, setUserReactions] = useState<Set<ReactionType>>(new Set());
  
  const reactionTypes: ReactionType[] = ['like', 'helpful', 'funny', 'insightful', 'disagree'];
  
  const handleReaction = (reaction: ReactionType) => {
    const newReactions = new Set(userReactions);
    
    if (newReactions.has(reaction)) {
      newReactions.delete(reaction);
    } else {
      newReactions.add(reaction);
    }
    
    setUserReactions(newReactions);
    onReact(reaction);
    setShowReactions(false);
  };
  
  return (
    <View style={styles.actionsContainer}>
      <View style={styles.primaryActions}>
        <TouchableOpacity
          style={styles.actionButton}
          onPress={onReply}
        >
          <Icon name="reply" size={16} color={colors.gray[600]} />
          <Text style={styles.actionText}>Reply</Text>
        </TouchableOpacity>
        
        <TouchableOpacity
          style={styles.actionButton}
          onPress={() => setShowReactions(!showReactions)}
        >
          <Icon name="heart" size={16} color={colors.gray[600]} />
          <Text style={styles.actionText}>React</Text>
        </TouchableOpacity>
        
        <TouchableOpacity
          style={styles.actionButton}
          onPress={onShare}
        >
          <Icon name="share" size={16} color={colors.gray[600]} />
          <Text style={styles.actionText}>Share</Text>
        </TouchableOpacity>
        
        <TouchableOpacity
          style={styles.actionButton}
          onPress={onReport}
        >
          <Icon name="flag" size={16} color={colors.gray[600]} />
          <Text style={styles.actionText}>Report</Text>
        </TouchableOpacity>
      </View>
      
      {showReactions && (
        <View style={styles.reactionPicker}>
          {reactionTypes.map((reaction) => (
            <TouchableOpacity
              key={reaction}
              style={[
                styles.reactionButton,
                userReactions.has(reaction) && styles.reactionButtonActive,
              ]}
              onPress={() => handleReaction(reaction)}
            >
              <ReactionIcon type={reaction} size={20} />
              <Text style={styles.reactionCount}>
                {comment.reactionCounts[reaction] || 0}
              </Text>
            </TouchableOpacity>
          ))}
        </View>
      )}
      
      {Object.keys(comment.reactionCounts).length > 0 && (
        <View style={styles.reactionSummary}>
          {Object.entries(comment.reactionCounts).map(([reaction, count]) => (
            <View key={reaction} style={styles.reactionSummaryItem}>
              <ReactionIcon type={reaction as ReactionType} size={14} />
              <Text style={styles.reactionSummaryCount}>{count}</Text>
            </View>
          ))}
        </View>
      )}
    </View>
  );
};
```

#### @Mention Autocomplete
```typescript
interface MentionAutocompleteProps {
  query: string;
  onSelect: (user: User) => void;
  maxResults?: number;
}

const MentionAutocomplete: React.FC<MentionAutocompleteProps> = ({
  query,
  onSelect,
  maxResults = 5,
}) => {
  const [users, setUsers] = useState<User[]>([]);
  const [loading, setLoading] = useState(false);
  
  const debouncedQuery = useDebounce(query, 300);
  
  useEffect(() => {
    if (debouncedQuery.length < 2) {
      setUsers([]);
      return;
    }
    
    searchUsers();
  }, [debouncedQuery]);
  
  const searchUsers = async () => {
    setLoading(true);
    try {
      const results = await userService.searchUsers({
        query: debouncedQuery,
        limit: maxResults,
      });
      setUsers(results);
    } catch (error) {
      console.error('Failed to search users:', error);
      setUsers([]);
    } finally {
      setLoading(false);
    }
  };
  
  if (loading) {
    return (
      <View style={styles.autocompleteContainer}>
        <ActivityIndicator size="small" color={colors.gray[400]} />
      </View>
    );
  }
  
  if (users.length === 0) {
    return null;
  }
  
  return (
    <View style={styles.autocompleteContainer}>
      {users.map((user) => (
        <TouchableOpacity
          key={user.id}
          style={styles.autocompleteItem}
          onPress={() => onSelect(user)}
        >
          <UserAvatar user={user} size="small" />
          <View style={styles.autocompleteUserInfo}>
            <Text style={styles.autocompleteUserName}>{user.name}</Text>
            <Text style={styles.autocompleteUsername}>@{user.username}</Text>
          </View>
        </TouchableOpacity>
      ))}
    </View>
  );
};
```

### Quality Checklist
- [ ] Comment components provide clear, readable threaded discussions
- [ ] Rich text editor is intuitive and supports all required formatting
- [ ] Comment reactions encourage positive engagement
- [ ] @mention functionality works accurately and efficiently
- [ ] Performance optimized for large comment threads
- [ ] Mobile interactions are touch-friendly and responsive
- [ ] Accessibility features support users with disabilities
- [ ] Component tests cover all user interactions and edge cases

---

**Status**: 🔄 Ready for Problem Definition Phase  
**Assignee**: Frontend Developer (React Native)  
**Epic**: E05 Social Interaction  
**Feature**: F01 Comment & Discussion System  
**Dependencies**: T01 UX Design, T02 Backend APIs, Design System, User Profile Data  
**Blocks**: User Acceptance Testing, Comment Workflows
