# E07 Administration - API Contracts

## Overview

This document defines the API contracts for Platform Analytics & Business Intelligence Service, Content Moderation & Safety Service, User & Community Management Service, and System Monitoring & Operations Service. These APIs enable comprehensive platform administration, monitoring, and optimization.

## Platform Analytics & Business Intelligence Service API

### Base Configuration
- **Base URL**: `/api/v1/admin/analytics`
- **Authentication**: Required (Admin JWT token with analytics permissions)
- **Rate Limiting**: 1000 requests per hour per admin user
- **Security**: Role-based access with audit logging

### Analytics Dashboard Endpoints

#### GET /api/v1/admin/analytics/dashboard
**Purpose**: Get comprehensive platform analytics dashboard

**Query Parameters**:
- `timeframe` (required): 1h, 24h, 7d, 30d, 90d, 1y
- `metrics` (optional): Comma-separated list of specific metrics
- `breakdown` (optional): user_type, geography, device_type

**Response (200)**:
```json
{
  "dashboard": {
    "timeframe": "30d",
    "generated_at": "2025-09-18T22:00:00Z",
    "platform_overview": {
      "total_users": 125000,
      "active_users": 45000,
      "new_users": 8500,
      "user_growth_rate": 0.15,
      "total_activities": 12500,
      "active_activities": 3200,
      "total_revenue_cents": 2500000,
      "revenue_growth_rate": 0.23
    },
    "user_metrics": {
      "daily_active_users": 15000,
      "weekly_active_users": 35000,
      "monthly_active_users": 45000,
      "user_retention_rate": 0.68,
      "average_session_duration_minutes": 24,
      "user_engagement_score": 7.2
    },
    "activity_metrics": {
      "activities_created": 850,
      "activities_completed": 720,
      "average_attendance_rate": 0.82,
      "host_satisfaction_score": 4.3,
      "participant_satisfaction_score": 4.5
    },
    "revenue_metrics": {
      "gross_revenue_cents": 2500000,
      "platform_revenue_cents": 250000,
      "host_earnings_cents": 2250000,
      "average_transaction_value_cents": 5500,
      "conversion_rate": 0.18
    },
    "geographic_breakdown": [
      {
        "country": "US",
        "users": 75000,
        "revenue_cents": 1800000,
        "growth_rate": 0.12
      }
    ]
  }
}
```

#### GET /api/v1/admin/analytics/cohorts
**Purpose**: Get user cohort analysis

**Query Parameters**:
- `cohort_type` (required): registration, first_activity, first_payment
- `period` (required): daily, weekly, monthly
- `start_date` (required): Start date (YYYY-MM-DD)
- `end_date` (required): End date (YYYY-MM-DD)

**Response (200)**:
```json
{
  "cohort_analysis": {
    "cohort_type": "registration",
    "period": "weekly",
    "cohorts": [
      {
        "cohort_period": "2025-09-01",
        "cohort_size": 1250,
        "retention_periods": [
          {
            "period": 0,
            "users_active": 1250,
            "retention_rate": 1.0
          },
          {
            "period": 1,
            "users_active": 850,
            "retention_rate": 0.68
          },
          {
            "period": 2,
            "users_active": 625,
            "retention_rate": 0.50
          }
        ]
      }
    ],
    "average_retention": {
      "week_1": 0.68,
      "week_2": 0.50,
      "week_4": 0.35,
      "week_8": 0.25
    }
  }
}
```

#### POST /api/v1/admin/analytics/experiments
**Purpose**: Create A/B testing experiment

**Request**:
```json
{
  "experiment_name": "New Onboarding Flow",
  "experiment_description": "Testing simplified onboarding process",
  "experiment_type": "feature_test",
  "target_audience": {
    "user_type": "new_users",
    "geographic_regions": ["US", "CA"],
    "device_types": ["mobile", "desktop"]
  },
  "variants": [
    {
      "name": "control",
      "description": "Current onboarding flow",
      "traffic_allocation": 0.5
    },
    {
      "name": "simplified",
      "description": "Simplified 3-step onboarding",
      "traffic_allocation": 0.5
    }
  ],
  "success_metrics": [
    {
      "metric_name": "onboarding_completion_rate",
      "target_improvement": 0.15
    },
    {
      "metric_name": "time_to_first_activity",
      "target_improvement": -0.20
    }
  ],
  "start_date": "2025-09-20T00:00:00Z",
  "end_date": "2025-10-20T00:00:00Z"
}
```

**Response (201)**:
```json
{
  "experiment": {
    "id": "uuid",
    "experiment_name": "New Onboarding Flow",
    "status": "draft",
    "variants": [
      {
        "name": "control",
        "traffic_allocation": 0.5,
        "current_users": 0
      },
      {
        "name": "simplified",
        "traffic_allocation": 0.5,
        "current_users": 0
      }
    ],
    "success_metrics": [
      {
        "metric_name": "onboarding_completion_rate",
        "baseline_value": 0.65,
        "target_improvement": 0.15
      }
    ],
    "estimated_sample_size": 2400,
    "estimated_duration_days": 14,
    "created_at": "2025-09-18T22:15:00Z"
  }
}
```

#### GET /api/v1/admin/analytics/experiments/{experimentId}/results
**Purpose**: Get A/B experiment results

**Response (200)**:
```json
{
  "experiment_results": {
    "experiment_id": "uuid",
    "experiment_name": "New Onboarding Flow",
    "status": "running",
    "duration_days": 7,
    "total_participants": 1250,
    "statistical_significance": 0.89,
    "confidence_level": 0.95,
    "variants": [
      {
        "name": "control",
        "participants": 625,
        "metrics": {
          "onboarding_completion_rate": {
            "value": 0.65,
            "confidence_interval": [0.61, 0.69]
          },
          "time_to_first_activity_hours": {
            "value": 48.5,
            "confidence_interval": [45.2, 51.8]
          }
        }
      },
      {
        "name": "simplified",
        "participants": 625,
        "metrics": {
          "onboarding_completion_rate": {
            "value": 0.78,
            "confidence_interval": [0.74, 0.82]
          },
          "time_to_first_activity_hours": {
            "value": 36.2,
            "confidence_interval": [33.1, 39.3]
          }
        }
      }
    ],
    "winner": {
      "variant_name": "simplified",
      "improvement": 0.20,
      "statistical_significance": 0.99
    },
    "recommendations": [
      "Simplified variant shows significant improvement in completion rate",
      "Consider rolling out to 100% of users",
      "Monitor long-term retention impact"
    ]
  }
}
```

## Content Moderation & Safety Service API

### Base Configuration
- **Base URL**: `/api/v1/admin/moderation`
- **Authentication**: Required (Admin JWT token with moderation permissions)
- **Rate Limiting**: 500 requests per hour per moderator

### Content Moderation Endpoints

#### GET /api/v1/admin/moderation/queue
**Purpose**: Get content moderation queue

**Query Parameters**:
- `status` (optional): pending, in_review, resolved
- `priority` (optional): 1, 2, 3, 4, 5
- `content_type` (optional): activity, comment, profile, image
- `assigned_to` (optional): moderator user ID
- `limit` (optional): Max results (default: 20, max: 100)

**Response (200)**:
```json
{
  "moderation_queue": [
    {
      "id": "uuid",
      "content_type": "activity",
      "content_id": "uuid",
      "content_preview": {
        "title": "Sunset Photography Workshop",
        "description": "Join us for an amazing photography session...",
        "images": ["https://example.com/image1.jpg"]
      },
      "reported_by": {
        "user_id": "uuid",
        "username": "reporter123"
      },
      "report_reason": "inappropriate_content",
      "report_description": "Contains inappropriate language in description",
      "automated_flags": [
        {
          "flag_type": "language_detection",
          "confidence": 0.85,
          "details": "Potentially offensive language detected"
        }
      ],
      "risk_score": 0.75,
      "priority_level": 3,
      "status": "pending",
      "created_at": "2025-09-18T21:30:00Z",
      "estimated_review_time_minutes": 15
    }
  ],
  "queue_summary": {
    "total_pending": 45,
    "high_priority_pending": 8,
    "average_wait_time_minutes": 120,
    "your_assigned_items": 5
  }
}
```

#### POST /api/v1/admin/moderation/queue/{itemId}/resolve
**Purpose**: Resolve a moderation queue item

**Request**:
```json
{
  "resolution": "approve", // approve, remove_content, warn_user, suspend_user
  "resolution_notes": "Content reviewed and found to be appropriate",
  "policy_violations": [], // Array of policy IDs if violations found
  "action_details": {
    "warning_message": "Please ensure future content follows community guidelines",
    "suspension_duration_hours": 72,
    "content_removal_reason": "Violates community guidelines section 3.2"
  },
  "appeal_eligible": true
}
```

**Response (200)**:
```json
{
  "resolution": {
    "item_id": "uuid",
    "resolution": "approve",
    "resolved_by": "uuid",
    "resolved_at": "2025-09-18T22:30:00Z",
    "actions_taken": [
      {
        "action_type": "content_approved",
        "target_id": "uuid",
        "details": "Content approved after manual review"
      }
    ],
    "appeal_deadline": null,
    "next_review_date": null
  }
}
```

#### POST /api/v1/admin/moderation/policies
**Purpose**: Create or update moderation policy

**Request**:
```json
{
  "policy_name": "Inappropriate Language Policy",
  "policy_category": "content_standards",
  "policy_description": "Guidelines for appropriate language in user-generated content",
  "policy_rules": [
    {
      "rule_type": "language_filter",
      "severity": 3,
      "automated_action": "flag_for_review",
      "keywords": ["inappropriate", "offensive"],
      "context_exceptions": ["educational", "artistic"]
    }
  ],
  "severity_level": 3,
  "automated_actions": [
    {
      "trigger_condition": "confidence > 0.9",
      "action": "auto_remove",
      "requires_human_review": false
    },
    {
      "trigger_condition": "confidence > 0.7",
      "action": "flag_for_review",
      "requires_human_review": true
    }
  ],
  "manual_review_required": true
}
```

**Response (201)**:
```json
{
  "policy": {
    "id": "uuid",
    "policy_name": "Inappropriate Language Policy",
    "policy_category": "content_standards",
    "severity_level": 3,
    "is_active": true,
    "effectiveness_score": null,
    "created_by": "uuid",
    "created_at": "2025-09-18T22:45:00Z"
  }
}
```

## User & Community Management Service API

### Base Configuration
- **Base URL**: `/api/v1/admin/users`
- **Authentication**: Required (Admin JWT token with user management permissions)
- **Rate Limiting**: 200 requests per hour per admin user

### User Management Endpoints

#### GET /api/v1/admin/users/{userId}
**Purpose**: Get comprehensive user details for administration

**Response (200)**:
```json
{
  "user_details": {
    "user_id": "uuid",
    "username": "john_doe",
    "email": "john@example.com",
    "profile": {
      "full_name": "John Doe",
      "bio": "Photography enthusiast and outdoor adventurer",
      "location": "San Francisco, CA",
      "profile_image_url": "https://example.com/profile.jpg"
    },
    "account_status": {
      "status": "active",
      "verification_level": "verified",
      "trust_score": 8.5,
      "account_age_days": 245,
      "last_active_at": "2025-09-18T20:00:00Z"
    },
    "activity_summary": {
      "activities_hosted": 15,
      "activities_attended": 32,
      "total_earnings_cents": 125000,
      "total_spent_cents": 85000,
      "average_rating_as_host": 4.7,
      "average_rating_as_participant": 4.8
    },
    "moderation_history": {
      "warnings_received": 0,
      "content_removed": 0,
      "suspensions": 0,
      "last_violation_date": null,
      "current_restrictions": []
    },
    "support_history": {
      "tickets_created": 2,
      "tickets_resolved": 2,
      "average_resolution_time_hours": 18,
      "satisfaction_rating": 4.5
    }
  }
}
```

#### PUT /api/v1/admin/users/{userId}/status
**Purpose**: Update user account status

**Request**:
```json
{
  "status": "suspended", // active, suspended, banned, under_review
  "reason": "Multiple policy violations",
  "duration_hours": 168, // 7 days
  "internal_notes": "User has violated community guidelines multiple times",
  "notify_user": true,
  "appeal_eligible": true
}
```

**Response (200)**:
```json
{
  "status_update": {
    "user_id": "uuid",
    "previous_status": "active",
    "new_status": "suspended",
    "effective_immediately": true,
    "expires_at": "2025-09-25T22:00:00Z",
    "appeal_deadline": "2025-09-21T22:00:00Z",
    "notification_sent": true,
    "updated_by": "uuid",
    "updated_at": "2025-09-18T22:00:00Z"
  }
}
```

#### GET /api/v1/admin/support/tickets
**Purpose**: Get support ticket queue

**Query Parameters**:
- `status` (optional): open, in_progress, waiting, resolved, closed
- `priority` (optional): low, medium, high, urgent
- `assigned_to` (optional): admin user ID
- `category` (optional): technical, billing, account, safety
- `limit` (optional): Max results (default: 20, max: 100)

**Response (200)**:
```json
{
  "support_tickets": [
    {
      "id": "uuid",
      "ticket_number": "TK-2025-001234",
      "user": {
        "user_id": "uuid",
        "username": "user123",
        "email": "user@example.com"
      },
      "ticket_type": "technical",
      "priority": "medium",
      "status": "open",
      "subject": "Unable to upload activity images",
      "description": "I'm having trouble uploading images for my photography workshop...",
      "category": "technical",
      "subcategory": "image_upload",
      "assigned_to": null,
      "created_at": "2025-09-18T20:30:00Z",
      "last_updated_at": "2025-09-18T20:30:00Z",
      "sla_deadline": "2025-09-19T20:30:00Z",
      "tags": ["image_upload", "workshop"]
    }
  ],
  "queue_summary": {
    "total_open": 25,
    "high_priority": 3,
    "overdue": 2,
    "your_assigned": 8,
    "average_response_time_hours": 4.2
  }
}
```

## System Monitoring & Operations Service API

### Base Configuration
- **Base URL**: `/api/v1/admin/monitoring`
- **Authentication**: Required (Admin JWT token with monitoring permissions)
- **Rate Limiting**: 2000 requests per hour per admin user

### System Health Endpoints

#### GET /api/v1/admin/monitoring/health
**Purpose**: Get overall system health status

**Response (200)**:
```json
{
  "system_health": {
    "overall_status": "healthy", // healthy, degraded, critical
    "health_score": 0.95,
    "last_updated": "2025-09-18T22:00:00Z",
    "services": [
      {
        "service_name": "user_service",
        "status": "healthy",
        "response_time_ms": 45,
        "error_rate": 0.001,
        "uptime_percentage": 99.98,
        "last_incident": null
      },
      {
        "service_name": "activity_service",
        "status": "healthy",
        "response_time_ms": 52,
        "error_rate": 0.002,
        "uptime_percentage": 99.95,
        "last_incident": "2025-09-15T14:30:00Z"
      }
    ],
    "infrastructure": {
      "database_status": "healthy",
      "database_connections": 45,
      "database_response_time_ms": 12,
      "cache_status": "healthy",
      "cache_hit_rate": 0.94,
      "storage_usage_percentage": 0.68
    },
    "performance_metrics": {
      "average_response_time_ms": 48,
      "requests_per_second": 1250,
      "error_rate": 0.0015,
      "active_users": 15000
    }
  }
}
```

#### GET /api/v1/admin/monitoring/alerts
**Purpose**: Get active system alerts

**Query Parameters**:
- `severity` (optional): info, warning, error, critical
- `status` (optional): active, acknowledged, resolved
- `service` (optional): service name filter
- `limit` (optional): Max results (default: 50, max: 200)

**Response (200)**:
```json
{
  "alerts": [
    {
      "id": "uuid",
      "alert_type": "performance",
      "severity": "warning",
      "title": "High Response Time Detected",
      "description": "Activity service response time exceeded threshold",
      "service_affected": "activity_service",
      "metric_name": "response_time_ms",
      "threshold_value": 100,
      "current_value": 125,
      "status": "active",
      "created_at": "2025-09-18T21:45:00Z",
      "acknowledged_by": null,
      "estimated_impact": "Minor performance degradation for activity searches"
    }
  ],
  "alert_summary": {
    "total_active": 3,
    "critical": 0,
    "warnings": 2,
    "info": 1,
    "unacknowledged": 2
  }
}
```

#### POST /api/v1/admin/monitoring/alerts/{alertId}/acknowledge
**Purpose**: Acknowledge a system alert

**Request**:
```json
{
  "acknowledgment_notes": "Investigating high response times, scaling additional instances",
  "estimated_resolution_time": "2025-09-18T23:30:00Z"
}
```

**Response (200)**:
```json
{
  "acknowledgment": {
    "alert_id": "uuid",
    "acknowledged_by": "uuid",
    "acknowledged_at": "2025-09-18T22:15:00Z",
    "acknowledgment_notes": "Investigating high response times, scaling additional instances",
    "estimated_resolution_time": "2025-09-18T23:30:00Z"
  }
}
```

## Error Response Format

All administrative APIs use consistent error response format:

```json
{
  "error": {
    "code": "INSUFFICIENT_PERMISSIONS",
    "message": "You do not have permission to perform this action.",
    "details": {
      "required_permission": "user_management",
      "current_permissions": ["analytics_read", "moderation_read"],
      "required_role": "admin",
      "current_role": "moderator"
    }
  },
  "request_id": "uuid",
  "timestamp": "2025-09-18T22:30:00Z"
}
```

### Administrative Error Codes
- `INSUFFICIENT_PERMISSIONS`: User lacks required permissions for action
- `INVALID_ADMIN_SESSION`: Administrative session expired or invalid
- `RESOURCE_NOT_FOUND`: Requested administrative resource not found
- `MODERATION_QUEUE_EMPTY`: No items available in moderation queue
- `EXPERIMENT_ALREADY_RUNNING`: A/B experiment with same name already active
- `SYSTEM_MAINTENANCE_MODE`: System in maintenance mode, limited functionality
- `AUDIT_LOG_REQUIRED`: Action requires audit logging but logging failed

---

**API Contracts Status**: ✅ Complete
**Next Steps**: Define integration points with all platform services
