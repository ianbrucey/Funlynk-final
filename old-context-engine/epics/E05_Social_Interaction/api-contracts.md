# E05 Social Interaction - API Contracts

## Overview

This document defines the API contracts for Comment & Discussion Service, Social Engagement Service, Community Management Service, and Real-time Social Service. These APIs enable rich social interactions, community building, and real-time engagement around activities.

## Comment & Discussion Service API

### Base Configuration
- **Base URL**: `/api/v1/comments`
- **Authentication**: Required (JWT token)
- **Rate Limiting**: 300 requests per minute per user

### Comment Management Endpoints

#### POST /api/v1/comments
**Purpose**: Create a new comment on an activity

**Request**:
```json
{
  "activity_id": "uuid",
  "parent_comment_id": "uuid", // Optional for replies
  "content": "This looks like a great activity! Count me in.",
  "attachments": [
    {
      "type": "image",
      "url": "https://...",
      "thumbnail_url": "https://..."
    }
  ]
}
```

**Response (201)**:
```json
{
  "comment": {
    "id": "uuid",
    "activity_id": "uuid",
    "parent_comment_id": "uuid",
    "user_id": "uuid",
    "content": "This looks like a great activity! Count me in.",
    "thread_depth": 1,
    "attachments": [
      {
        "type": "image",
        "url": "https://...",
        "thumbnail_url": "https://..."
      }
    ],
    "mentions": [
      {
        "user_id": "uuid",
        "username": "basketballpro",
        "position": 25
      }
    ],
    "reaction_counts": {
      "like": 0,
      "helpful": 0
    },
    "reply_count": 0,
    "is_edited": false,
    "is_pinned": false,
    "created_at": "2025-09-18T14:30:00Z",
    "updated_at": "2025-09-18T14:30:00Z",
    "author": {
      "id": "uuid",
      "username": "tennispro",
      "display_name": "Alex Chen",
      "profile_image_url": "https://...",
      "is_verified": true
    }
  }
}
```

#### GET /api/v1/comments/activity/{activityId}
**Purpose**: Get comments for an activity with threading

**Query Parameters**:
- `sort` (optional): newest, oldest, popular (default: newest)
- `max_depth` (optional): Maximum thread depth (default: 5, max: 10)
- `limit` (optional): Max comments per page (default: 20, max: 100)
- `offset` (optional): Pagination offset

**Response (200)**:
```json
{
  "comments": [
    {
      "id": "uuid",
      "content": "This looks amazing! Who else is going?",
      "thread_depth": 0,
      "reply_count": 3,
      "reaction_counts": {
        "like": 5,
        "helpful": 2
      },
      "user_reaction": "like", // Current user's reaction
      "author": {
        "username": "outdoorlover",
        "display_name": "Sarah Wilson"
      },
      "created_at": "2025-09-18T14:00:00Z",
      "replies": [
        {
          "id": "uuid",
          "content": "I'll be there! Looking forward to meeting everyone.",
          "thread_depth": 1,
          "reply_count": 0,
          "author": {
            "username": "hikingfan",
            "display_name": "Mike Johnson"
          },
          "created_at": "2025-09-18T14:15:00Z",
          "replies": []
        }
      ]
    }
  ],
  "total_count": 47,
  "has_more": true
}
```

#### POST /api/v1/comments/{commentId}/reactions
**Purpose**: Add or remove a reaction to a comment

**Request**:
```json
{
  "reaction_type": "helpful" // like, helpful, funny, insightful, disagree
}
```

**Response (200)**:
```json
{
  "success": true,
  "action": "added", // added or removed
  "reaction_counts": {
    "like": 3,
    "helpful": 8,
    "funny": 1
  }
}
```

## Social Engagement Service API

### Base Configuration
- **Base URL**: `/api/v1/social`
- **Authentication**: Required (JWT token)
- **Rate Limiting**: 200 requests per minute per user

### Activity Reactions

#### POST /api/v1/social/activities/{activityId}/reactions
**Purpose**: React to an activity

**Request**:
```json
{
  "reaction_type": "excited" // like, love, excited, interested, going
}
```

**Response (200)**:
```json
{
  "success": true,
  "action": "added",
  "reaction_summary": {
    "like": 23,
    "love": 8,
    "excited": 15,
    "interested": 31,
    "going": 12
  },
  "user_reactions": ["excited", "interested"]
}
```

### Activity Sharing

#### POST /api/v1/social/activities/{activityId}/share
**Purpose**: Share an activity

**Request**:
```json
{
  "share_type": "external", // internal, external, direct_message
  "platform": "instagram", // instagram, twitter, facebook, link
  "message": "Check out this amazing hiking trip!",
  "recipient_user_id": "uuid" // For internal shares
}
```

**Response (201)**:
```json
{
  "share": {
    "id": "uuid",
    "share_url": "https://funlynk.com/share/abc123",
    "share_type": "external",
    "platform": "instagram",
    "message": "Check out this amazing hiking trip!",
    "created_at": "2025-09-18T15:00:00Z"
  },
  "tracking_id": "uuid"
}
```

#### GET /api/v1/social/activities/{activityId}/social-proof
**Purpose**: Get social proof information for an activity

**Response (200)**:
```json
{
  "social_proof": {
    "friends_attending": 3,
    "friends_interested": 7,
    "friends_shared": 2,
    "total_social_signals": 12,
    "social_proof_score": 0.85,
    "friends_attending_details": [
      {
        "user_id": "uuid",
        "username": "bestfriend",
        "display_name": "Emma Davis",
        "profile_image_url": "https://..."
      }
    ],
    "social_proof_message": "Emma Davis and 2 other friends are attending",
    "mutual_connections": 5,
    "popularity_indicators": {
      "trending_score": 0.78,
      "engagement_velocity": 0.92,
      "community_buzz": 0.65
    }
  }
}
```

### Save/Bookmark Activities

#### POST /api/v1/social/activities/{activityId}/save
**Purpose**: Save an activity for later

**Request**:
```json
{
  "save_note": "Perfect for a weekend date!",
  "save_category": "date_ideas",
  "reminder_time": "2025-09-25T10:00:00Z"
}
```

**Response (201)**:
```json
{
  "saved_activity": {
    "id": "uuid",
    "activity_id": "uuid",
    "save_note": "Perfect for a weekend date!",
    "save_category": "date_ideas",
    "reminder_time": "2025-09-25T10:00:00Z",
    "created_at": "2025-09-18T15:30:00Z"
  }
}
```

#### GET /api/v1/social/saved-activities
**Purpose**: Get user's saved activities

**Query Parameters**:
- `category` (optional): Filter by save category
- `sort` (optional): newest, oldest, reminder_time
- `limit` (optional): Max results (default: 20, max: 100)

**Response (200)**:
```json
{
  "saved_activities": [
    {
      "id": "uuid",
      "save_note": "Perfect for a weekend date!",
      "save_category": "date_ideas",
      "reminder_time": "2025-09-25T10:00:00Z",
      "created_at": "2025-09-18T15:30:00Z",
      "activity": {
        "id": "uuid",
        "title": "Wine Tasting in Napa Valley",
        "start_time": "2025-09-28T14:00:00Z",
        "location_name": "Napa Valley Winery",
        "price_cents": 7500
      }
    }
  ],
  "categories": [
    {
      "name": "date_ideas",
      "count": 8
    },
    {
      "name": "weekend_plans",
      "count": 12
    }
  ]
}
```

## Community Management Service API

### Base Configuration
- **Base URL**: `/api/v1/communities`
- **Authentication**: Required (JWT token)
- **Rate Limiting**: 150 requests per minute per user

### Community Management

#### POST /api/v1/communities
**Purpose**: Create a new community

**Request**:
```json
{
  "name": "Bay Area Hikers",
  "description": "A community for hiking enthusiasts in the San Francisco Bay Area",
  "community_type": "interest_based", // activity_based, interest_based, location_based
  "interest_tags": ["hiking", "outdoors", "nature"],
  "location_coordinates": {
    "lat": 37.7749,
    "lng": -122.4194
  },
  "location_radius_km": 50,
  "is_public": true,
  "requires_approval": false,
  "allow_member_posts": true,
  "allow_member_events": true,
  "cover_image_url": "https://...",
  "theme_color": "#2E7D32"
}
```

**Response (201)**:
```json
{
  "community": {
    "id": "uuid",
    "name": "Bay Area Hikers",
    "description": "A community for hiking enthusiasts in the San Francisco Bay Area",
    "community_type": "interest_based",
    "interest_tags": ["hiking", "outdoors", "nature"],
    "location_coordinates": {
      "lat": 37.7749,
      "lng": -122.4194
    },
    "location_radius_km": 50,
    "is_public": true,
    "requires_approval": false,
    "member_count": 1,
    "activity_count": 0,
    "cover_image_url": "https://...",
    "theme_color": "#2E7D32",
    "created_by": "uuid",
    "created_at": "2025-09-18T16:00:00Z",
    "user_membership": {
      "role": "admin",
      "join_status": "active",
      "joined_at": "2025-09-18T16:00:00Z"
    }
  }
}
```

#### POST /api/v1/communities/{communityId}/join
**Purpose**: Join a community

**Response (200)**:
```json
{
  "membership": {
    "id": "uuid",
    "community_id": "uuid",
    "user_id": "uuid",
    "role": "member",
    "join_status": "active", // active, pending (if approval required)
    "joined_at": "2025-09-18T16:15:00Z"
  },
  "community": {
    "id": "uuid",
    "name": "Bay Area Hikers",
    "member_count": 156
  }
}
```

#### GET /api/v1/communities/{communityId}/posts
**Purpose**: Get community posts and discussions

**Query Parameters**:
- `post_type` (optional): discussion, announcement, event, poll
- `sort` (optional): newest, oldest, popular
- `limit` (optional): Max posts (default: 20, max: 50)

**Response (200)**:
```json
{
  "posts": [
    {
      "id": "uuid",
      "post_type": "discussion",
      "title": "Best hiking trails for beginners?",
      "content": "I'm new to hiking and looking for some beginner-friendly trails...",
      "attachments": [
        {
          "type": "image",
          "url": "https://..."
        }
      ],
      "comment_count": 23,
      "reaction_count": 15,
      "view_count": 89,
      "is_pinned": false,
      "author": {
        "username": "newbie_hiker",
        "display_name": "Jessica Kim",
        "role": "member"
      },
      "created_at": "2025-09-18T13:00:00Z"
    }
  ]
}
```

### Community Discovery

#### GET /api/v1/communities/discover
**Purpose**: Discover communities based on interests and location

**Query Parameters**:
- `interests` (optional): Comma-separated interest tags
- `location` (optional): lat,lng coordinates
- `radius` (optional): Search radius in km
- `community_type` (optional): Filter by community type

**Response (200)**:
```json
{
  "communities": [
    {
      "id": "uuid",
      "name": "SF Photography Walks",
      "description": "Weekly photography walks around San Francisco",
      "member_count": 234,
      "activity_count": 45,
      "cover_image_url": "https://...",
      "match_score": 0.89,
      "match_reasons": [
        "Matches your interest in photography",
        "Located near you",
        "Active community with regular events"
      ],
      "recent_activity": {
        "new_members_this_week": 12,
        "posts_this_week": 8,
        "upcoming_events": 3
      }
    }
  ],
  "recommended_interests": [
    "photography",
    "urban_exploration",
    "street_art"
  ]
}
```

## Real-time Social Service API

### WebSocket Connection
- **URL**: `wss://api.funlynk.com/ws/social`
- **Authentication**: JWT token in connection query or header
- **Protocol**: JSON message format

### WebSocket Message Types

#### Connection Events
```json
// Client connects
{
  "type": "connect",
  "data": {
    "user_id": "uuid",
    "presence": {
      "status": "online",
      "current_activity_id": "uuid"
    }
  }
}

// Server acknowledges connection
{
  "type": "connected",
  "data": {
    "connection_id": "uuid",
    "user_id": "uuid",
    "server_time": "2025-09-18T16:30:00Z"
  }
}
```

#### Chat Messages
```json
// Send chat message
{
  "type": "chat_message",
  "data": {
    "room_id": "activity:uuid",
    "content": "Hey everyone! Excited for tomorrow's hike!",
    "message_type": "text",
    "reply_to_message_id": "uuid"
  }
}

// Receive chat message
{
  "type": "chat_message_received",
  "data": {
    "message": {
      "id": "uuid",
      "room_id": "activity:uuid",
      "sender_id": "uuid",
      "content": "Hey everyone! Excited for tomorrow's hike!",
      "message_type": "text",
      "created_at": "2025-09-18T16:35:00Z",
      "sender": {
        "username": "hikingfan",
        "display_name": "Mike Johnson",
        "profile_image_url": "https://..."
      }
    }
  }
}
```

#### Presence Updates
```json
// Update presence
{
  "type": "presence_update",
  "data": {
    "status": "at_activity",
    "current_activity_id": "uuid",
    "custom_status": "At the hiking meetup!"
  }
}

// Receive presence update
{
  "type": "user_presence_changed",
  "data": {
    "user_id": "uuid",
    "status": "at_activity",
    "current_activity_id": "uuid",
    "custom_status": "At the hiking meetup!",
    "updated_at": "2025-09-18T16:40:00Z"
  }
}
```

#### Live Social Updates
```json
// Real-time reaction
{
  "type": "activity_reaction",
  "data": {
    "activity_id": "uuid",
    "user_id": "uuid",
    "reaction_type": "excited",
    "action": "added",
    "reaction_counts": {
      "excited": 15,
      "interested": 23
    }
  }
}

// Real-time comment
{
  "type": "new_comment",
  "data": {
    "activity_id": "uuid",
    "comment": {
      "id": "uuid",
      "content": "Can't wait for this!",
      "author": {
        "username": "outdoorlover",
        "display_name": "Sarah Wilson"
      },
      "created_at": "2025-09-18T16:45:00Z"
    }
  }
}
```

### REST Endpoints for Real-time Features

#### GET /api/v1/realtime/chat/{roomId}/history
**Purpose**: Get chat message history

**Query Parameters**:
- `limit` (optional): Max messages (default: 50, max: 200)
- `before` (optional): Get messages before this timestamp

**Response (200)**:
```json
{
  "messages": [
    {
      "id": "uuid",
      "content": "Looking forward to meeting everyone!",
      "sender": {
        "username": "newbie",
        "display_name": "Alex Chen"
      },
      "created_at": "2025-09-18T16:20:00Z"
    }
  ],
  "has_more": true
}
```

#### GET /api/v1/realtime/presence/activity/{activityId}
**Purpose**: Get online users for an activity

**Response (200)**:
```json
{
  "online_users": [
    {
      "user_id": "uuid",
      "username": "hikingpro",
      "display_name": "Sarah Wilson",
      "status": "online",
      "last_seen": "2025-09-18T16:50:00Z",
      "is_at_activity": true
    }
  ],
  "total_online": 12,
  "total_at_activity": 8
}
```

## Error Response Format

All APIs use consistent error response format:

```json
{
  "error": {
    "code": "COMMENT_THREAD_TOO_DEEP",
    "message": "Comment thread exceeds maximum depth of 10 levels",
    "details": {
      "current_depth": 11,
      "max_depth": 10,
      "parent_comment_id": "uuid"
    }
  },
  "request_id": "uuid"
}
```

### Social Interaction Error Codes
- `COMMENT_THREAD_TOO_DEEP`: Comment nesting exceeds maximum depth
- `COMMUNITY_MEMBERSHIP_REQUIRED`: Action requires community membership
- `MODERATION_BLOCKED`: Content blocked by moderation system
- `CHAT_ROOM_FULL`: Chat room has reached capacity
- `PRESENCE_UPDATE_THROTTLED`: Presence updates are being throttled
- `SOCIAL_FEATURE_DISABLED`: Social feature is disabled for this context

---

**API Contracts Status**: ✅ Complete
**Next Steps**: Define integration points with other epics
