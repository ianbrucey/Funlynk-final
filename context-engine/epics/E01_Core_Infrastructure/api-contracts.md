# E01 Core Infrastructure - API Contracts

## Overview

This document defines the API contracts that Core Infrastructure services expose to other epics. These contracts establish the interface boundaries and ensure consistent integration patterns across the platform.

## Authentication Service API

### Base Configuration
- **Base URL**: Handled by Supabase Auth
- **Authentication**: JWT tokens in Authorization header
- **Content Type**: application/json

### Core Authentication Endpoints

#### POST /auth/signup
**Purpose**: Register a new user account

**Request**:
```json
{
  "email": "user@example.com",
  "password": "securePassword123",
  "metadata": {
    "username": "johndoe",
    "display_name": "John Doe"
  }
}
```

**Response (201)**:
```json
{
  "user": {
    "id": "uuid",
    "email": "user@example.com",
    "email_confirmed_at": null,
    "user_metadata": {
      "username": "johndoe",
      "display_name": "John Doe"
    }
  },
  "session": {
    "access_token": "jwt_token",
    "refresh_token": "refresh_token",
    "expires_in": 3600
  }
}
```

#### POST /auth/signin
**Purpose**: Authenticate existing user

**Request**:
```json
{
  "email": "user@example.com",
  "password": "securePassword123"
}
```

**Response (200)**: Same as signup response

#### POST /auth/signout
**Purpose**: End user session

**Headers**: `Authorization: Bearer {jwt_token}`

**Response (204)**: No content

#### POST /auth/refresh
**Purpose**: Refresh expired access token

**Request**:
```json
{
  "refresh_token": "refresh_token"
}
```

**Response (200)**:
```json
{
  "access_token": "new_jwt_token",
  "refresh_token": "new_refresh_token",
  "expires_in": 3600
}
```

### Social Authentication

#### POST /auth/oauth/{provider}
**Purpose**: Initiate OAuth flow (Google, Apple)

**Parameters**:
- `provider`: google | apple

**Response (200)**:
```json
{
  "url": "oauth_authorization_url",
  "state": "csrf_protection_state"
}
```

## Geolocation Service API

### Base Configuration
- **Base URL**: `/api/v1/geolocation`
- **Authentication**: Required (JWT token)
- **Rate Limiting**: 100 requests per minute per user

### Spatial Query Endpoints

#### GET /api/v1/geolocation/activities/nearby
**Purpose**: Find activities within specified radius

**Query Parameters**:
- `lat` (required): Latitude (-90 to 90)
- `lng` (required): Longitude (-180 to 180)
- `radius` (required): Search radius in kilometers (max 50)
- `limit` (optional): Max results (default 20, max 100)
- `activity_type` (optional): Filter by activity type
- `start_time_after` (optional): ISO timestamp filter

**Response (200)**:
```json
{
  "activities": [
    {
      "id": "uuid",
      "title": "Pickup Basketball",
      "location_name": "Central Park",
      "location_coordinates": {
        "lat": 40.7829,
        "lng": -73.9654
      },
      "distance_km": 1.2,
      "start_time": "2025-09-18T18:00:00Z",
      "host": {
        "id": "uuid",
        "username": "basketballpro",
        "display_name": "Mike Johnson"
      }
    }
  ],
  "total_count": 15,
  "search_center": {
    "lat": 40.7831,
    "lng": -73.9712
  },
  "search_radius_km": 5
}
```

#### GET /api/v1/geolocation/users/nearby
**Purpose**: Find users within specified radius

**Query Parameters**:
- `lat` (required): Latitude
- `lng` (required): Longitude  
- `radius` (required): Search radius in kilometers (max 10)
- `limit` (optional): Max results (default 10, max 50)

**Response (200)**:
```json
{
  "users": [
    {
      "id": "uuid",
      "username": "runner123",
      "display_name": "Sarah Wilson",
      "profile_image_url": "https://...",
      "distance_km": 0.8,
      "location_name": "Brooklyn, NY"
    }
  ],
  "total_count": 8
}
```

#### POST /api/v1/geolocation/distance
**Purpose**: Calculate distance between two points

**Request**:
```json
{
  "point1": {
    "lat": 40.7831,
    "lng": -73.9712
  },
  "point2": {
    "lat": 40.7589,
    "lng": -73.9851
  }
}
```

**Response (200)**:
```json
{
  "distance_km": 2.34,
  "distance_miles": 1.45
}
```

## Notification Service API

### Base Configuration
- **Base URL**: `/api/v1/notifications`
- **Authentication**: Required (JWT token)
- **Rate Limiting**: 50 requests per minute per user

### Notification Management Endpoints

#### GET /api/v1/notifications
**Purpose**: Get user's notifications

**Query Parameters**:
- `limit` (optional): Max results (default 20, max 100)
- `offset` (optional): Pagination offset
- `unread_only` (optional): Filter to unread notifications

**Response (200)**:
```json
{
  "notifications": [
    {
      "id": "uuid",
      "type": "new_follower",
      "title": "New Follower",
      "message": "Sarah Wilson started following you",
      "data": {
        "follower_id": "uuid",
        "follower_username": "sarahw"
      },
      "is_read": false,
      "created_at": "2025-09-17T14:30:00Z"
    }
  ],
  "total_count": 45,
  "unread_count": 12
}
```

#### PATCH /api/v1/notifications/{id}/read
**Purpose**: Mark notification as read

**Response (200)**:
```json
{
  "id": "uuid",
  "is_read": true,
  "read_at": "2025-09-17T15:45:00Z"
}
```

#### POST /api/v1/notifications/send
**Purpose**: Send notification (internal service use)

**Request**:
```json
{
  "user_id": "uuid",
  "type": "activity_reminder",
  "title": "Activity Reminder",
  "message": "Your basketball game starts in 1 hour",
  "data": {
    "activity_id": "uuid",
    "activity_title": "Pickup Basketball"
  },
  "delivery_methods": ["push", "in_app"]
}
```

**Response (201)**:
```json
{
  "notification_id": "uuid",
  "delivery_status": {
    "push": "sent",
    "in_app": "created"
  }
}
```

### Notification Preferences

#### GET /api/v1/notifications/preferences
**Purpose**: Get user's notification preferences

**Response (200)**:
```json
{
  "push_notifications": {
    "new_followers": true,
    "activity_updates": true,
    "activity_reminders": true,
    "comments": false
  },
  "email_notifications": {
    "weekly_digest": true,
    "activity_invites": true,
    "security_alerts": true
  }
}
```

#### PUT /api/v1/notifications/preferences
**Purpose**: Update notification preferences

**Request**:
```json
{
  "push_notifications": {
    "new_followers": true,
    "activity_updates": false
  }
}
```

**Response (200)**: Updated preferences object

## Error Response Format

All APIs use consistent error response format:

```json
{
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Invalid request parameters",
    "details": [
      {
        "field": "lat",
        "message": "Latitude must be between -90 and 90"
      }
    ]
  },
  "request_id": "uuid"
}
```

### Common Error Codes
- `AUTHENTICATION_REQUIRED`: Missing or invalid JWT token
- `AUTHORIZATION_FAILED`: User lacks permission for resource
- `VALIDATION_ERROR`: Invalid request parameters
- `RESOURCE_NOT_FOUND`: Requested resource doesn't exist
- `RATE_LIMIT_EXCEEDED`: Too many requests
- `INTERNAL_ERROR`: Server error

## Data Types and Schemas

### Common Types
```typescript
interface Coordinates {
  lat: number; // -90 to 90
  lng: number; // -180 to 180
}

interface User {
  id: string;
  username: string;
  display_name: string;
  profile_image_url?: string;
}

interface Activity {
  id: string;
  title: string;
  location_name: string;
  location_coordinates: Coordinates;
  start_time: string; // ISO 8601
  host: User;
}

interface Notification {
  id: string;
  type: string;
  title: string;
  message: string;
  data?: Record<string, any>;
  is_read: boolean;
  created_at: string; // ISO 8601
  read_at?: string; // ISO 8601
}
```

## Integration Guidelines

### Authentication Integration
1. Include JWT token in Authorization header for all requests
2. Handle token refresh automatically when tokens expire
3. Redirect to login on authentication failures

### Error Handling
1. Always check response status codes
2. Parse error responses for user-friendly messages
3. Implement retry logic for transient failures

### Rate Limiting
1. Respect rate limit headers in responses
2. Implement exponential backoff for rate limit errors
3. Cache responses when appropriate to reduce API calls

---

**API Contracts Status**: ✅ Complete
**Next Steps**: Define integration points with other epics
