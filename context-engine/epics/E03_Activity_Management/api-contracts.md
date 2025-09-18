# E03 Activity Management - API Contracts

## Overview

This document defines the API contracts for Activity CRUD Service, Tagging & Category Service, and RSVP & Attendance Service. These APIs enable comprehensive activity lifecycle management from creation to completion.

## Activity CRUD Service API

### Base Configuration
- **Base URL**: `/api/v1/activities`
- **Authentication**: Required (JWT token)
- **Rate Limiting**: 100 requests per minute per user

### Activity Management Endpoints

#### POST /api/v1/activities
**Purpose**: Create a new activity

**Request**:
```json
{
  "title": "Pickup Basketball Game",
  "description": "Casual basketball game for all skill levels. Bring water and good vibes!",
  "location_name": "Central Park Basketball Courts",
  "location_coordinates": {
    "lat": 40.7829,
    "lng": -73.9654
  },
  "start_time": "2025-09-20T18:00:00Z",
  "end_time": "2025-09-20T20:00:00Z",
  "capacity": 10,
  "price_cents": 0,
  "tags": ["basketball", "sports", "pickup", "central-park"],
  "requirements": "Bring your own basketball shoes",
  "equipment_provided": "Basketballs provided",
  "skill_level": "beginner",
  "publish_immediately": true
}
```

**Response (201)**:
```json
{
  "id": "uuid",
  "host_id": "uuid",
  "title": "Pickup Basketball Game",
  "description": "Casual basketball game for all skill levels...",
  "location_name": "Central Park Basketball Courts, New York, NY",
  "location_coordinates": {
    "lat": 40.7829,
    "lng": -73.9654
  },
  "start_time": "2025-09-20T18:00:00Z",
  "end_time": "2025-09-20T20:00:00Z",
  "capacity": 10,
  "price_cents": 0,
  "currency": "USD",
  "status": "published",
  "rsvp_count": 0,
  "waitlist_count": 0,
  "tags": ["basketball", "sports", "pickup", "central-park"],
  "suggested_tags": ["outdoor", "evening", "manhattan"],
  "requirements": "Bring your own basketball shoes",
  "equipment_provided": "Basketballs provided",
  "skill_level": "beginner",
  "host": {
    "id": "uuid",
    "username": "basketballpro",
    "display_name": "Mike Johnson",
    "profile_image_url": "https://...",
    "is_verified": false
  },
  "created_at": "2025-09-17T22:00:00Z",
  "updated_at": "2025-09-17T22:00:00Z"
}
```

#### GET /api/v1/activities/{activityId}
**Purpose**: Get activity details

**Query Parameters**:
- `include_participants` (optional): Include participant list (host only)
- `include_analytics` (optional): Include activity analytics (host only)

**Response (200)**:
```json
{
  "id": "uuid",
  "host_id": "uuid",
  "title": "Pickup Basketball Game",
  "description": "Casual basketball game for all skill levels...",
  "location_name": "Central Park Basketball Courts, New York, NY",
  "location_coordinates": {
    "lat": 40.7829,
    "lng": -73.9654
  },
  "start_time": "2025-09-20T18:00:00Z",
  "end_time": "2025-09-20T20:00:00Z",
  "capacity": 10,
  "price_cents": 0,
  "status": "published",
  "rsvp_count": 7,
  "waitlist_count": 2,
  "tags": ["basketball", "sports", "pickup", "central-park"],
  "images": [
    {
      "id": "uuid",
      "image_url": "https://storage.supabase.co/...",
      "caption": "Basketball court view"
    }
  ],
  "requirements": "Bring your own basketball shoes",
  "equipment_provided": "Basketballs provided",
  "skill_level": "beginner",
  "host": {
    "id": "uuid",
    "username": "basketballpro",
    "display_name": "Mike Johnson",
    "profile_image_url": "https://...",
    "is_verified": false
  },
  "user_rsvp": {
    "status": "confirmed",
    "rsvp_time": "2025-09-18T10:30:00Z",
    "guest_count": 1
  },
  "created_at": "2025-09-17T22:00:00Z",
  "updated_at": "2025-09-18T14:20:00Z"
}
```

#### PUT /api/v1/activities/{activityId}
**Purpose**: Update activity (host only)

**Request**: Partial activity object with updates

**Response (200)**: Updated activity object

#### DELETE /api/v1/activities/{activityId}
**Purpose**: Delete activity (host only)

**Response (204)**: No content

#### POST /api/v1/activities/{activityId}/cancel
**Purpose**: Cancel activity (host only)

**Request**:
```json
{
  "reason": "Weather conditions unsafe",
  "notify_participants": true
}
```

**Response (200)**:
```json
{
  "id": "uuid",
  "status": "cancelled",
  "cancellation_reason": "Weather conditions unsafe",
  "cancelled_at": "2025-09-19T08:00:00Z",
  "participants_notified": true
}
```

### Activity Images

#### POST /api/v1/activities/{activityId}/images
**Purpose**: Upload activity image

**Content-Type**: `multipart/form-data`

**Request**:
- `image`: Image file (max 10MB, JPEG/PNG)
- `caption`: Optional image caption

**Response (201)**:
```json
{
  "id": "uuid",
  "image_url": "https://storage.supabase.co/...",
  "caption": "Basketball court view",
  "image_order": 0,
  "upload_status": "processing",
  "created_at": "2025-09-17T22:30:00Z"
}
```

#### PUT /api/v1/activities/{activityId}/images/order
**Purpose**: Reorder activity images

**Request**:
```json
{
  "image_order": ["uuid1", "uuid2", "uuid3"]
}
```

**Response (200)**: Success confirmation

### Activity Templates

#### GET /api/v1/activities/templates
**Purpose**: Get activity templates

**Query Parameters**:
- `category` (optional): Filter by category

**Response (200)**:
```json
{
  "templates": [
    {
      "id": "uuid",
      "name": "Pickup Basketball",
      "category": "sports",
      "description": "Template for casual basketball games",
      "default_title": "Pickup Basketball Game",
      "default_description": "Casual basketball game for all skill levels...",
      "default_duration_minutes": 120,
      "default_capacity": 10,
      "suggested_tags": ["basketball", "sports", "pickup"],
      "requirements_template": "Bring your own basketball shoes",
      "equipment_template": "Basketballs provided",
      "default_skill_level": "beginner"
    }
  ]
}
```

## Tagging & Category Service API

### Base Configuration
- **Base URL**: `/api/v1/tags`
- **Authentication**: Required (JWT token)
- **Rate Limiting**: 200 requests per minute per user

### Tag Management Endpoints

#### GET /api/v1/tags/search
**Purpose**: Search and autocomplete tags

**Query Parameters**:
- `q` (required): Search query
- `limit` (optional): Max results (default 10, max 50)
- `category` (optional): Filter by category

**Response (200)**:
```json
{
  "tags": [
    {
      "id": "uuid",
      "name": "basketball",
      "category": "sports",
      "usage_count": 1247,
      "is_verified": true
    },
    {
      "id": "uuid",
      "name": "pickup-basketball",
      "category": "sports",
      "usage_count": 89,
      "is_verified": false
    }
  ]
}
```

#### GET /api/v1/tags/popular
**Purpose**: Get popular tags

**Query Parameters**:
- `category` (optional): Filter by category
- `timeframe` (optional): "week" | "month" | "all" (default: "month")
- `limit` (optional): Max results (default 20, max 100)

**Response (200)**:
```json
{
  "tags": [
    {
      "id": "uuid",
      "name": "basketball",
      "category": "sports",
      "usage_count": 1247,
      "recent_usage": 45,
      "trend": "up"
    }
  ]
}
```

#### GET /api/v1/tags/categories
**Purpose**: Get tag categories hierarchy

**Response (200)**:
```json
{
  "categories": [
    {
      "id": "uuid",
      "name": "Sports",
      "icon_name": "sports",
      "color_hex": "#FF6B35",
      "subcategories": [
        {
          "id": "uuid",
          "name": "Basketball",
          "parent_id": "uuid"
        },
        {
          "id": "uuid",
          "name": "Soccer",
          "parent_id": "uuid"
        }
      ]
    }
  ]
}
```

#### POST /api/v1/tags/suggest
**Purpose**: Get tag suggestions for activity

**Request**:
```json
{
  "title": "Pickup Basketball Game",
  "description": "Casual basketball game in Central Park",
  "location_name": "Central Park",
  "start_time": "2025-09-20T18:00:00Z"
}
```

**Response (200)**:
```json
{
  "suggestions": [
    {
      "tag": {
        "id": "uuid",
        "name": "basketball",
        "category": "sports"
      },
      "confidence": 0.95,
      "reason": "keyword_match"
    },
    {
      "tag": {
        "id": "uuid",
        "name": "central-park",
        "category": "location"
      },
      "confidence": 0.88,
      "reason": "location_match"
    }
  ]
}
```

## RSVP & Attendance Service API

### Base Configuration
- **Base URL**: `/api/v1/rsvps`
- **Authentication**: Required (JWT token)
- **Rate Limiting**: 100 requests per minute per user

### RSVP Management Endpoints

#### POST /api/v1/rsvps
**Purpose**: Create RSVP for activity

**Request**:
```json
{
  "activity_id": "uuid",
  "guest_count": 1,
  "special_requests": "Vegetarian meal preference"
}
```

**Response (201)**:
```json
{
  "rsvp": {
    "id": "uuid",
    "activity_id": "uuid",
    "user_id": "uuid",
    "status": "confirmed",
    "rsvp_time": "2025-09-18T10:30:00Z",
    "guest_count": 1,
    "special_requests": "Vegetarian meal preference"
  },
  "waitlist_position": null,
  "capacity_info": {
    "current_rsvps": 8,
    "max_capacity": 10,
    "has_capacity": true
  }
}
```

#### DELETE /api/v1/rsvps/{rsvpId}
**Purpose**: Cancel RSVP

**Response (204)**: No content

#### GET /api/v1/rsvps/user/{userId}
**Purpose**: Get user's RSVP history

**Query Parameters**:
- `status` (optional): Filter by RSVP status
- `limit` (optional): Max results (default 20, max 100)
- `offset` (optional): Pagination offset

**Response (200)**:
```json
{
  "rsvps": [
    {
      "id": "uuid",
      "activity": {
        "id": "uuid",
        "title": "Pickup Basketball Game",
        "start_time": "2025-09-20T18:00:00Z",
        "location_name": "Central Park Basketball Courts",
        "host": {
          "username": "basketballpro",
          "display_name": "Mike Johnson"
        }
      },
      "status": "confirmed",
      "rsvp_time": "2025-09-18T10:30:00Z",
      "attended": null
    }
  ],
  "total_count": 15,
  "has_more": true
}
```

### Activity Participants

#### GET /api/v1/activities/{activityId}/participants
**Purpose**: Get activity participants (host only)

**Query Parameters**:
- `status` (optional): Filter by RSVP status
- `include_waitlist` (optional): Include waitlisted participants

**Response (200)**:
```json
{
  "participants": [
    {
      "rsvp": {
        "id": "uuid",
        "status": "confirmed",
        "rsvp_time": "2025-09-18T10:30:00Z",
        "guest_count": 1,
        "attended": null
      },
      "user": {
        "id": "uuid",
        "username": "player1",
        "display_name": "Sarah Wilson",
        "profile_image_url": "https://..."
      }
    }
  ],
  "waitlist": [
    {
      "rsvp": {
        "id": "uuid",
        "status": "waitlisted",
        "rsvp_time": "2025-09-18T14:20:00Z"
      },
      "user": {
        "id": "uuid",
        "username": "player2",
        "display_name": "John Smith"
      },
      "waitlist_position": 1
    }
  ],
  "stats": {
    "confirmed_count": 8,
    "waitlist_count": 2,
    "total_capacity": 10
  }
}
```

### Attendance Management

#### POST /api/v1/activities/{activityId}/checkin
**Purpose**: Check in participant

**Request**:
```json
{
  "user_id": "uuid",
  "check_in_method": "qr_code",
  "location_coordinates": {
    "lat": 40.7829,
    "lng": -73.9654
  }
}
```

**Response (200)**:
```json
{
  "attendance": {
    "rsvp_id": "uuid",
    "attended": true,
    "attendance_time": "2025-09-20T18:05:00Z",
    "check_in_method": "qr_code"
  },
  "message": "Successfully checked in!"
}
```

#### GET /api/v1/activities/{activityId}/attendance
**Purpose**: Get attendance statistics (host only)

**Response (200)**:
```json
{
  "stats": {
    "total_rsvps": 10,
    "attended_count": 8,
    "no_show_count": 2,
    "attendance_rate": 0.8
  },
  "attendance_records": [
    {
      "user": {
        "username": "player1",
        "display_name": "Sarah Wilson"
      },
      "attended": true,
      "attendance_time": "2025-09-20T18:05:00Z"
    }
  ]
}
```

## Error Response Format

All APIs use consistent error response format:

```json
{
  "error": {
    "code": "ACTIVITY_CAPACITY_EXCEEDED",
    "message": "This activity has reached its maximum capacity",
    "details": {
      "activity_id": "uuid",
      "current_capacity": 10,
      "waitlist_position": 3
    }
  },
  "request_id": "uuid"
}
```

### Activity Service Error Codes
- `ACTIVITY_NOT_FOUND`: Activity doesn't exist
- `UNAUTHORIZED_HOST`: User is not the activity host
- `INVALID_ACTIVITY_TIME`: Activity time is in the past or invalid
- `ACTIVITY_CANCELLED`: Activity has been cancelled
- `IMAGE_UPLOAD_FAILED`: Image upload failed
- `LOCATION_VALIDATION_FAILED`: Location coordinates are invalid

### RSVP Service Error Codes
- `ACTIVITY_CAPACITY_EXCEEDED`: Activity is at full capacity
- `DUPLICATE_RSVP`: User already has RSVP for this activity
- `RSVP_NOT_FOUND`: RSVP doesn't exist
- `ACTIVITY_NOT_ACCEPTING_RSVPS`: Activity is not accepting new RSVPs
- `PAYMENT_REQUIRED`: Payment required for this activity

---

**API Contracts Status**: ✅ Complete
**Next Steps**: Define integration points with other epics
