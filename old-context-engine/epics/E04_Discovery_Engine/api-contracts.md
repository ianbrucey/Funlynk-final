# E04 Discovery Engine - API Contracts

## Overview

This document defines the API contracts for Search Service, Recommendation Engine, and Feed Generation Service. These APIs enable intelligent discovery of activities and users through search, personalized recommendations, and curated feeds.

## Search Service API

### Base Configuration
- **Base URL**: `/api/v1/search`
- **Authentication**: Optional (enhanced results for authenticated users)
- **Rate Limiting**: 200 requests per minute per user

### Activity Search Endpoints

#### GET /api/v1/search/activities
**Purpose**: Search activities with advanced filtering and ranking

**Query Parameters**:
- `q` (optional): Search query text
- `lat` (optional): Latitude for location-based search
- `lng` (optional): Longitude for location-based search
- `radius` (optional): Search radius in km (default 25, max 100)
- `categories` (optional): Comma-separated category names
- `tags` (optional): Comma-separated tag names
- `price_min` (optional): Minimum price in cents
- `price_max` (optional): Maximum price in cents
- `start_time_after` (optional): ISO timestamp filter
- `start_time_before` (optional): ISO timestamp filter
- `skill_level` (optional): beginner, intermediate, advanced, expert
- `has_capacity` (optional): Filter activities with available spots
- `sort` (optional): relevance, date, price, popularity, distance
- `limit` (optional): Max results (default 20, max 100)
- `offset` (optional): Pagination offset

**Response (200)**:
```json
{
  "results": [
    {
      "activity": {
        "id": "uuid",
        "title": "Pickup Basketball Game",
        "description": "Casual basketball game for all skill levels...",
        "location_name": "Central Park Basketball Courts",
        "location_coordinates": {
          "lat": 40.7829,
          "lng": -73.9654
        },
        "start_time": "2025-09-20T18:00:00Z",
        "end_time": "2025-09-20T20:00:00Z",
        "capacity": 10,
        "rsvp_count": 7,
        "price_cents": 0,
        "tags": ["basketball", "sports", "pickup"],
        "host": {
          "username": "basketballpro",
          "display_name": "Mike Johnson",
          "is_verified": false
        }
      },
      "relevance_score": 0.89,
      "distance_km": 1.2,
      "personalization_factors": {
        "interest_match": 0.8,
        "location_preference": 0.9,
        "social_signals": 0.3
      },
      "match_reasons": [
        "Matches your interest in basketball",
        "Close to your usual activity area",
        "2 people you follow are attending"
      ]
    }
  ],
  "total_count": 156,
  "facets": {
    "categories": [
      { "name": "Sports", "count": 89 },
      { "name": "Social", "count": 34 }
    ],
    "price_ranges": [
      { "range": "free", "count": 120 },
      { "range": "1-20", "count": 25 }
    ],
    "skill_levels": [
      { "level": "beginner", "count": 67 },
      { "level": "intermediate", "count": 45 }
    ]
  },
  "suggestions": [
    "Try searching for 'tennis' instead",
    "Expand your search radius to find more activities"
  ],
  "search_id": "uuid"
}
```

#### GET /api/v1/search/suggestions
**Purpose**: Get search suggestions and autocomplete

**Query Parameters**:
- `q` (required): Search prefix
- `type` (optional): activity, tag, location, user
- `limit` (optional): Max suggestions (default 10, max 20)

**Response (200)**:
```json
{
  "suggestions": [
    {
      "text": "basketball",
      "type": "tag",
      "popularity": 1247,
      "completion": "basketball pickup games"
    },
    {
      "text": "Basketball in Central Park",
      "type": "activity",
      "activity_count": 15,
      "completion": "Basketball in Central Park this weekend"
    },
    {
      "text": "Central Park",
      "type": "location",
      "activity_count": 89,
      "completion": "Central Park activities"
    }
  ]
}
```

### User Search Endpoints

#### GET /api/v1/search/users
**Purpose**: Search users with social context

**Query Parameters**:
- `q` (required): Search query
- `limit` (optional): Max results (default 20, max 50)
- `include_mutual` (optional): Include mutual connections info

**Response (200)**:
```json
{
  "users": [
    {
      "id": "uuid",
      "username": "basketballpro",
      "display_name": "Mike Johnson",
      "profile_image_url": "https://...",
      "bio": "Basketball enthusiast and weekend warrior",
      "follower_count": 156,
      "is_verified": false,
      "mutual_connections": 5,
      "mutual_connection_previews": [
        {
          "username": "friend1",
          "display_name": "Sarah Wilson"
        }
      ],
      "shared_interests": ["basketball", "sports"],
      "relevance_score": 0.85
    }
  ],
  "total_count": 23
}
```

## Recommendation Engine API

### Base Configuration
- **Base URL**: `/api/v1/recommendations`
- **Authentication**: Required (JWT token)
- **Rate Limiting**: 100 requests per minute per user

### Personal Recommendations

#### GET /api/v1/recommendations/activities
**Purpose**: Get personalized activity recommendations

**Query Parameters**:
- `type` (optional): interest, social, location, trending, similar
- `limit` (optional): Max results (default 10, max 50)
- `diversity` (optional): low, medium, high (default medium)
- `exclude_rsvped` (optional): Exclude activities user already RSVP'd to

**Response (200)**:
```json
{
  "recommendations": [
    {
      "activity": {
        "id": "uuid",
        "title": "Tennis Doubles Match",
        "description": "Looking for two more players for doubles...",
        "location_name": "Riverside Tennis Courts",
        "start_time": "2025-09-21T10:00:00Z",
        "capacity": 4,
        "rsvp_count": 2,
        "price_cents": 1500,
        "tags": ["tennis", "sports", "doubles"],
        "host": {
          "username": "tennispro",
          "display_name": "Alex Chen",
          "is_verified": true
        }
      },
      "recommendation_score": 0.92,
      "confidence": 0.87,
      "reasoning": {
        "primary_reason": "Matches your interest in racquet sports",
        "supporting_factors": [
          "Similar to activities you've enjoyed before",
          "Good match for your skill level",
          "Convenient location for you"
        ],
        "algorithm_breakdown": {
          "interest_based": 0.85,
          "collaborative_filtering": 0.78,
          "location_based": 0.95,
          "social_signals": 0.45
        }
      },
      "recommendation_id": "uuid",
      "generated_at": "2025-09-18T12:00:00Z"
    }
  ],
  "refresh_available": true,
  "last_updated": "2025-09-18T12:00:00Z"
}
```

#### POST /api/v1/recommendations/feedback
**Purpose**: Provide feedback on recommendations

**Request**:
```json
{
  "recommendation_id": "uuid",
  "feedback_type": "positive", // positive, negative, not_interested, irrelevant
  "reason": "great_match", // great_match, wrong_location, wrong_time, not_interested, etc.
  "additional_context": "Perfect activity for my skill level"
}
```

**Response (200)**:
```json
{
  "success": true,
  "message": "Feedback recorded successfully",
  "impact": "Future recommendations will be adjusted based on your feedback"
}
```

### Contextual Recommendations

#### GET /api/v1/recommendations/similar/{activityId}
**Purpose**: Get activities similar to a specific activity

**Query Parameters**:
- `limit` (optional): Max results (default 5, max 20)

**Response (200)**:
```json
{
  "similar_activities": [
    {
      "activity": {
        "id": "uuid",
        "title": "Basketball Scrimmage",
        "similarity_score": 0.78,
        "similarity_factors": [
          "Same sport category",
          "Similar skill level",
          "Nearby location"
        ]
      }
    }
  ]
}
```

#### GET /api/v1/recommendations/trending
**Purpose**: Get trending activities with personalization

**Query Parameters**:
- `timeframe` (optional): 1h, 6h, 24h, 7d (default 24h)
- `category` (optional): Filter by category
- `limit` (optional): Max results (default 20, max 50)

**Response (200)**:
```json
{
  "trending_activities": [
    {
      "activity": {
        "id": "uuid",
        "title": "Weekend Hiking Adventure",
        "trend_score": 0.95,
        "trend_factors": {
          "rsvp_velocity": 0.9,
          "view_count_growth": 0.85,
          "social_shares": 0.8
        },
        "personalization_score": 0.67
      }
    }
  ],
  "timeframe": "24h",
  "updated_at": "2025-09-18T12:00:00Z"
}
```

## Feed Generation Service API

### Base Configuration
- **Base URL**: `/api/v1/feeds`
- **Authentication**: Required (JWT token)
- **Rate Limiting**: 150 requests per minute per user

### Feed Endpoints

#### GET /api/v1/feeds/home
**Purpose**: Get personalized home feed

**Query Parameters**:
- `limit` (optional): Max items (default 20, max 100)
- `offset` (optional): Pagination offset
- `refresh` (optional): Force feed refresh

**Response (200)**:
```json
{
  "feed_items": [
    {
      "id": "uuid",
      "type": "activity",
      "activity": {
        "id": "uuid",
        "title": "Morning Yoga Session",
        "description": "Start your day with peaceful yoga...",
        "start_time": "2025-09-19T07:00:00Z",
        "location_name": "Sunset Park",
        "host": {
          "username": "yogainstructor",
          "display_name": "Maria Santos"
        }
      },
      "feed_position": 0,
      "source_type": "social", // social, recommended, trending, location
      "insertion_reason": "Sarah Wilson, who you follow, is attending",
      "engagement_score": 0.78,
      "created_at": "2025-09-18T12:00:00Z"
    },
    {
      "id": "uuid",
      "type": "recommendation",
      "activity": {
        "id": "uuid",
        "title": "Photography Walk",
        "description": "Explore the city through your lens..."
      },
      "feed_position": 1,
      "source_type": "recommended",
      "insertion_reason": "Based on your interest in photography",
      "recommendation_score": 0.85
    }
  ],
  "has_more": true,
  "last_updated": "2025-09-18T12:00:00Z",
  "next_refresh_available": "2025-09-18T12:15:00Z"
}
```

#### GET /api/v1/feeds/social
**Purpose**: Get feed from followed users

**Query Parameters**:
- `limit` (optional): Max items (default 20, max 100)
- `offset` (optional): Pagination offset

**Response (200)**:
```json
{
  "feed_items": [
    {
      "id": "uuid",
      "type": "activity",
      "activity": {
        "id": "uuid",
        "title": "Beach Volleyball Tournament"
      },
      "source_user": {
        "username": "volleyballstar",
        "display_name": "Jessica Kim",
        "relationship": "following"
      },
      "action_type": "created", // created, rsvped, shared
      "action_time": "2025-09-18T11:30:00Z"
    }
  ]
}
```

#### GET /api/v1/feeds/category/{category}
**Purpose**: Get category-specific feed

**Parameters**:
- `category` (path): Category name (sports, social, learning, etc.)

**Query Parameters**:
- `limit` (optional): Max items (default 20, max 100)
- `sort` (optional): recent, popular, trending

**Response (200)**:
```json
{
  "category": "sports",
  "feed_items": [
    {
      "activity": {
        "id": "uuid",
        "title": "Soccer Practice Session"
      },
      "category_relevance": 0.95,
      "popularity_score": 0.82
    }
  ]
}
```

### Feed Management

#### POST /api/v1/feeds/refresh
**Purpose**: Refresh user's feeds

**Request**:
```json
{
  "feed_types": ["home", "social"], // Optional, defaults to all
  "force_refresh": false
}
```

**Response (200)**:
```json
{
  "refreshed_feeds": ["home", "social"],
  "refresh_time": "2025-09-18T12:05:00Z",
  "next_refresh_available": "2025-09-18T12:20:00Z"
}
```

#### POST /api/v1/feeds/interactions
**Purpose**: Record feed interactions for optimization

**Request**:
```json
{
  "feed_item_id": "uuid",
  "interaction_type": "click", // view, click, rsvp, share, hide, not_interested
  "interaction_time": "2025-09-18T12:05:00Z",
  "context": {
    "feed_position": 3,
    "feed_type": "home"
  }
}
```

**Response (200)**:
```json
{
  "success": true,
  "message": "Interaction recorded"
}
```

## Error Response Format

All APIs use consistent error response format:

```json
{
  "error": {
    "code": "SEARCH_QUERY_TOO_BROAD",
    "message": "Search query returned too many results. Please add more specific filters.",
    "details": {
      "result_count": 50000,
      "suggested_filters": ["location", "category", "time_range"]
    }
  },
  "request_id": "uuid"
}
```

### Discovery Service Error Codes
- `SEARCH_QUERY_TOO_BROAD`: Search query needs more specificity
- `SEARCH_NO_RESULTS`: No activities match the search criteria
- `RECOMMENDATIONS_UNAVAILABLE`: Recommendation service temporarily unavailable
- `FEED_GENERATION_FAILED`: Feed generation encountered an error
- `INSUFFICIENT_USER_DATA`: Not enough user data for personalization
- `RATE_LIMIT_EXCEEDED`: Too many requests in time window

---

**API Contracts Status**: ✅ Complete
**Next Steps**: Define integration points with other epics
