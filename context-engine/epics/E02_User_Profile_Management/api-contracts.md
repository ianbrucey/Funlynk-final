# E02 User & Profile Management - API Contracts

## Overview

This document defines the API contracts for Profile Service and Social Graph Service operations. These APIs enable comprehensive user profile management and social networking functionality while enforcing privacy controls.

## Profile Service API

### Base Configuration
- **Base URL**: `/api/v1/profiles`
- **Authentication**: Required (JWT token)
- **Rate Limiting**: 100 requests per minute per user

### Profile Management Endpoints

#### GET /api/v1/profiles/{userId}
**Purpose**: Get user profile with privacy filtering

**Parameters**:
- `userId` (path): User ID to retrieve

**Query Parameters**:
- `include_analytics` (optional): Include profile analytics (owner only)

**Response (200)**:
```json
{
  "id": "uuid",
  "username": "johndoe",
  "display_name": "John Doe",
  "bio": "Basketball enthusiast and weekend warrior",
  "profile_image_url": "https://storage.supabase.co/...",
  "location_name": "Brooklyn, NY",
  "location_coordinates": {
    "lat": 40.6782,
    "lng": -73.9442
  },
  "interests": ["basketball", "fitness", "music"],
  "follower_count": 156,
  "following_count": 89,
  "activity_count": 23,
  "is_verified": false,
  "is_host": true,
  "created_at": "2025-01-15T10:30:00Z",
  "relationship": {
    "is_following": true,
    "is_followed_by": false,
    "is_blocked": false,
    "mutual_connections": 5
  },
  "profile_completion": 85
}
```

#### PUT /api/v1/profiles/{userId}
**Purpose**: Update user profile (owner only)

**Request**:
```json
{
  "display_name": "John Smith",
  "bio": "Updated bio text",
  "location_name": "Manhattan, NY",
  "location_coordinates": {
    "lat": 40.7831,
    "lng": -73.9712
  },
  "interests": ["basketball", "fitness", "photography"]
}
```

**Response (200)**: Updated profile object

#### GET /api/v1/profiles/{userId}/images
**Purpose**: Get user's profile images

**Response (200)**:
```json
{
  "images": [
    {
      "id": "uuid",
      "image_url": "https://storage.supabase.co/...",
      "image_type": "profile",
      "is_primary": true,
      "width": 400,
      "height": 400,
      "created_at": "2025-09-17T14:30:00Z"
    }
  ]
}
```

#### POST /api/v1/profiles/{userId}/images
**Purpose**: Upload profile image

**Content-Type**: `multipart/form-data`

**Request**:
- `image`: Image file (max 5MB, JPEG/PNG)
- `image_type`: "profile" | "cover" | "gallery"

**Response (201)**:
```json
{
  "id": "uuid",
  "image_url": "https://storage.supabase.co/...",
  "image_type": "profile",
  "upload_status": "processing",
  "created_at": "2025-09-17T15:45:00Z"
}
```

### User Discovery Endpoints

#### GET /api/v1/profiles/search
**Purpose**: Search users by username or display name

**Query Parameters**:
- `q` (required): Search query
- `limit` (optional): Max results (default 20, max 100)
- `include_location` (optional): Include location in results

**Response (200)**:
```json
{
  "users": [
    {
      "id": "uuid",
      "username": "johndoe",
      "display_name": "John Doe",
      "profile_image_url": "https://...",
      "follower_count": 156,
      "is_verified": false,
      "location_name": "Brooklyn, NY",
      "mutual_connections": 3
    }
  ],
  "total_count": 45
}
```

#### GET /api/v1/profiles/nearby
**Purpose**: Find users nearby

**Query Parameters**:
- `lat` (required): Latitude
- `lng` (required): Longitude
- `radius` (required): Search radius in km (max 50)
- `limit` (optional): Max results (default 20, max 50)

**Response (200)**:
```json
{
  "users": [
    {
      "id": "uuid",
      "username": "sarahw",
      "display_name": "Sarah Wilson",
      "profile_image_url": "https://...",
      "distance_km": 1.2,
      "shared_interests": ["running", "fitness"],
      "mutual_connections": 2
    }
  ]
}
```

### User Preferences Endpoints

#### GET /api/v1/profiles/{userId}/preferences
**Purpose**: Get user preferences (owner only)

**Response (200)**:
```json
{
  "privacy": {
    "profile_visibility": "public",
    "location_visibility": "city",
    "follower_list_visibility": "public",
    "following_list_visibility": "public"
  },
  "discovery": {
    "discoverable_by_location": true,
    "discoverable_by_interests": true,
    "show_in_suggestions": true
  },
  "notifications": {
    "notify_new_followers": true,
    "notify_activity_invites": true,
    "notify_comments": false,
    "email_notifications": true,
    "push_notifications": true
  }
}
```

#### PUT /api/v1/profiles/{userId}/preferences
**Purpose**: Update user preferences (owner only)

**Request**: Partial preferences object

**Response (200)**: Updated preferences object

## Social Graph Service API

### Base Configuration
- **Base URL**: `/api/v1/social`
- **Authentication**: Required (JWT token)
- **Rate Limiting**: 200 requests per minute per user

### Follow Management Endpoints

#### POST /api/v1/social/follow
**Purpose**: Follow a user

**Request**:
```json
{
  "target_user_id": "uuid"
}
```

**Response (201)**:
```json
{
  "success": true,
  "follow": {
    "id": "uuid",
    "follower_id": "uuid",
    "following_id": "uuid",
    "created_at": "2025-09-17T16:00:00Z"
  },
  "notification_sent": true
}
```

#### DELETE /api/v1/social/follow/{targetUserId}
**Purpose**: Unfollow a user

**Response (204)**: No content

#### GET /api/v1/social/follow-status
**Purpose**: Check follow status for multiple users

**Query Parameters**:
- `user_ids`: Comma-separated list of user IDs

**Response (200)**:
```json
{
  "follow_status": {
    "uuid1": {
      "is_following": true,
      "is_followed_by": false,
      "are_mutual": false
    },
    "uuid2": {
      "is_following": false,
      "is_followed_by": true,
      "are_mutual": false
    }
  }
}
```

### Follower/Following Lists

#### GET /api/v1/social/{userId}/followers
**Purpose**: Get user's followers

**Query Parameters**:
- `limit` (optional): Max results (default 20, max 100)
- `offset` (optional): Pagination offset
- `search` (optional): Filter by username/display name

**Response (200)**:
```json
{
  "followers": [
    {
      "id": "uuid",
      "username": "follower1",
      "display_name": "Follower One",
      "profile_image_url": "https://...",
      "followed_at": "2025-09-15T10:30:00Z",
      "mutual_connections": 8
    }
  ],
  "total_count": 156,
  "has_more": true
}
```

#### GET /api/v1/social/{userId}/following
**Purpose**: Get users that this user follows

**Query Parameters**: Same as followers endpoint

**Response (200)**: Similar structure to followers response

### Follow Recommendations

#### GET /api/v1/social/recommendations
**Purpose**: Get personalized follow recommendations

**Query Parameters**:
- `limit` (optional): Max results (default 10, max 50)
- `type` (optional): "mutual" | "interests" | "location" | "all"

**Response (200)**:
```json
{
  "recommendations": [
    {
      "user": {
        "id": "uuid",
        "username": "recommended1",
        "display_name": "Recommended User",
        "profile_image_url": "https://...",
        "follower_count": 89,
        "bio": "Tennis player and coffee enthusiast"
      },
      "recommendation_reason": {
        "type": "mutual_connections",
        "mutual_connections": 5,
        "mutual_connection_previews": [
          {
            "id": "uuid",
            "username": "mutual1",
            "display_name": "Mutual Friend"
          }
        ],
        "shared_interests": ["tennis", "coffee"],
        "distance_km": 2.3
      },
      "score": 85
    }
  ]
}
```

### Block Management

#### POST /api/v1/social/block
**Purpose**: Block a user

**Request**:
```json
{
  "target_user_id": "uuid",
  "reason": "harassment"
}
```

**Response (201)**:
```json
{
  "success": true,
  "blocked_at": "2025-09-17T16:30:00Z"
}
```

#### DELETE /api/v1/social/block/{targetUserId}
**Purpose**: Unblock a user

**Response (204)**: No content

#### GET /api/v1/social/blocked
**Purpose**: Get list of blocked users

**Response (200)**:
```json
{
  "blocked_users": [
    {
      "id": "uuid",
      "username": "blockeduser",
      "blocked_at": "2025-09-10T14:20:00Z",
      "reason": "spam"
    }
  ]
}
```

## Error Response Format

All APIs use consistent error response format:

```json
{
  "error": {
    "code": "PROFILE_NOT_FOUND",
    "message": "The requested profile could not be found",
    "details": {
      "user_id": "uuid"
    }
  },
  "request_id": "uuid"
}
```

### Profile Service Error Codes
- `PROFILE_NOT_FOUND`: User profile doesn't exist
- `ACCESS_DENIED`: Insufficient permissions to view/edit profile
- `INVALID_IMAGE_FORMAT`: Uploaded image format not supported
- `IMAGE_TOO_LARGE`: Image file exceeds size limit
- `PROFILE_UPDATE_FAILED`: Profile update operation failed
- `PREFERENCES_UPDATE_FAILED`: Preferences update failed

### Social Graph Service Error Codes
- `ALREADY_FOLLOWING`: User is already following the target
- `NOT_FOLLOWING`: User is not following the target
- `CANNOT_FOLLOW_SELF`: Users cannot follow themselves
- `USER_BLOCKED`: Operation blocked due to user block
- `FOLLOW_LIMIT_EXCEEDED`: User has reached follow limit
- `BLOCK_FAILED`: Block operation failed

## Data Types and Schemas

### Common Types
```typescript
interface UserProfile {
  id: string;
  username: string;
  display_name: string;
  bio?: string;
  profile_image_url?: string;
  location_name?: string;
  location_coordinates?: Coordinates;
  interests: string[];
  follower_count: number;
  following_count: number;
  activity_count: number;
  is_verified: boolean;
  is_host: boolean;
  created_at: string;
  relationship?: UserRelationship;
  profile_completion?: number;
}

interface UserRelationship {
  is_following: boolean;
  is_followed_by: boolean;
  is_blocked: boolean;
  mutual_connections: number;
}

interface UserPreferences {
  privacy: PrivacySettings;
  discovery: DiscoverySettings;
  notifications: NotificationSettings;
}

interface RecommendedUser {
  user: UserProfile;
  recommendation_reason: RecommendationReason;
  score: number;
}
```

## Integration Guidelines

### Privacy Considerations
1. Always check user privacy settings before returning data
2. Filter location data based on user preferences
3. Respect block relationships in all operations
4. Audit profile access for security monitoring

### Performance Best Practices
1. Use pagination for large result sets
2. Cache frequently accessed profiles
3. Batch follow status checks when possible
4. Implement client-side caching for static data

### Error Handling
1. Provide clear error messages for user-facing errors
2. Log detailed error information for debugging
3. Implement retry logic for transient failures
4. Gracefully degrade functionality when services are unavailable

---

**API Contracts Status**: ✅ Complete
**Next Steps**: Define integration points with other epics
