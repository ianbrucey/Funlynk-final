# E06 Payments & Monetization - API Contracts

## Overview

This document defines the API contracts for Payment Processing Service, Revenue Sharing & Payouts Service, Subscription Management Service, and Marketplace Monetization Service. These APIs enable comprehensive financial operations, revenue generation, and premium features.

## Payment Processing Service API

### Base Configuration
- **Base URL**: `/api/v1/payments`
- **Authentication**: Required (JWT token)
- **Rate Limiting**: 100 requests per minute per user
- **Security**: PCI DSS compliant, all financial data encrypted

### Payment Processing Endpoints

#### POST /api/v1/payments/activity
**Purpose**: Process payment for activity RSVP

**Request**:
```json
{
  "activity_id": "uuid",
  "payment_method_id": "uuid",
  "discount_code": "EARLY20", // Optional
  "save_payment_method": true,
  "billing_address": {
    "line1": "123 Main St",
    "city": "San Francisco",
    "state": "CA",
    "postal_code": "94105",
    "country": "US"
  }
}
```

**Response (201)**:
```json
{
  "payment_result": {
    "transaction_id": "uuid",
    "payment_status": "succeeded", // succeeded, processing, requires_action
    "amount_charged_cents": 7500,
    "currency": "usd",
    "platform_fee_cents": 750,
    "host_earnings_cents": 6750,
    "discount_applied_cents": 1500,
    "payment_method": {
      "id": "uuid",
      "type": "card",
      "card_brand": "visa",
      "card_last_four": "4242"
    },
    "receipt_url": "https://pay.stripe.com/receipts/...",
    "created_at": "2025-09-18T20:00:00Z"
  },
  "rsvp_status": "confirmed",
  "next_action": null // For 3D Secure or other authentication
}
```

#### POST /api/v1/payments/methods
**Purpose**: Add a new payment method

**Request**:
```json
{
  "stripe_payment_method_id": "pm_1234567890",
  "set_as_default": true,
  "billing_address": {
    "line1": "123 Main St",
    "city": "San Francisco",
    "state": "CA",
    "postal_code": "94105",
    "country": "US"
  }
}
```

**Response (201)**:
```json
{
  "payment_method": {
    "id": "uuid",
    "type": "card",
    "card_brand": "visa",
    "card_last_four": "4242",
    "card_exp_month": 12,
    "card_exp_year": 2027,
    "is_default": true,
    "is_verified": true,
    "created_at": "2025-09-18T20:05:00Z"
  }
}
```

#### GET /api/v1/payments/methods
**Purpose**: Get user's payment methods

**Response (200)**:
```json
{
  "payment_methods": [
    {
      "id": "uuid",
      "type": "card",
      "card_brand": "visa",
      "card_last_four": "4242",
      "card_exp_month": 12,
      "card_exp_year": 2027,
      "is_default": true,
      "is_verified": true,
      "created_at": "2025-09-18T20:05:00Z"
    }
  ]
}
```

#### POST /api/v1/payments/refunds
**Purpose**: Request a refund for a transaction

**Request**:
```json
{
  "transaction_id": "uuid",
  "refund_amount_cents": 7500, // Optional, defaults to full amount
  "reason": "customer_request", // customer_request, host_cancellation, fraudulent
  "description": "Customer requested refund due to schedule conflict"
}
```

**Response (201)**:
```json
{
  "refund": {
    "id": "uuid",
    "original_transaction_id": "uuid",
    "refund_amount_cents": 7500,
    "refund_status": "processing", // processing, succeeded, failed
    "reason": "customer_request",
    "platform_fee_refunded_cents": 750,
    "host_fee_refunded_cents": 6750,
    "estimated_arrival": "2025-09-23T20:00:00Z",
    "created_at": "2025-09-18T20:30:00Z"
  }
}
```

#### GET /api/v1/payments/transactions
**Purpose**: Get user's transaction history

**Query Parameters**:
- `type` (optional): activity_payment, subscription, refund
- `status` (optional): succeeded, failed, pending, refunded
- `limit` (optional): Max results (default: 20, max: 100)
- `offset` (optional): Pagination offset

**Response (200)**:
```json
{
  "transactions": [
    {
      "id": "uuid",
      "transaction_type": "activity_payment",
      "amount_cents": 7500,
      "currency": "usd",
      "payment_status": "succeeded",
      "description": "Payment for Sunset Yoga Session",
      "activity": {
        "id": "uuid",
        "title": "Sunset Yoga Session",
        "start_time": "2025-09-20T18:00:00Z"
      },
      "payment_method": {
        "type": "card",
        "card_last_four": "4242"
      },
      "receipt_url": "https://pay.stripe.com/receipts/...",
      "created_at": "2025-09-18T20:00:00Z"
    }
  ],
  "total_count": 45,
  "has_more": true
}
```

## Revenue Sharing & Payouts Service API

### Base Configuration
- **Base URL**: `/api/v1/revenue`
- **Authentication**: Required (JWT token)
- **Rate Limiting**: 50 requests per minute per user

### Host Earnings Endpoints

#### GET /api/v1/revenue/earnings
**Purpose**: Get host earnings summary

**Query Parameters**:
- `timeframe` (optional): 7d, 30d, 90d, 1y, all (default: 30d)
- `group_by` (optional): day, week, month

**Response (200)**:
```json
{
  "earnings_summary": {
    "total_gross_earnings_cents": 125000,
    "total_platform_fees_cents": 12500,
    "total_net_earnings_cents": 112500,
    "transaction_count": 47,
    "average_earnings_cents": 2394,
    "growth_rate": 0.23, // 23% growth vs previous period
    "timeframe": "30d"
  },
  "earnings_by_period": [
    {
      "period": "2025-09-01",
      "gross_earnings_cents": 15000,
      "platform_fees_cents": 1500,
      "net_earnings_cents": 13500,
      "transaction_count": 6
    }
  ],
  "top_earning_activities": [
    {
      "activity_id": "uuid",
      "activity_title": "Wine Tasting Experience",
      "earnings_cents": 25000,
      "transaction_count": 8
    }
  ]
}
```

#### GET /api/v1/revenue/earnings/breakdown
**Purpose**: Get detailed earnings breakdown

**Query Parameters**:
- `start_date` (required): Start date (YYYY-MM-DD)
- `end_date` (required): End date (YYYY-MM-DD)

**Response (200)**:
```json
{
  "earnings_breakdown": {
    "period": {
      "start_date": "2025-09-01",
      "end_date": "2025-09-18"
    },
    "totals": {
      "gross_earnings_cents": 85000,
      "platform_fees_cents": 8500,
      "net_earnings_cents": 76500
    },
    "by_activity": [
      {
        "activity_id": "uuid",
        "activity_title": "Photography Workshop",
        "gross_earnings_cents": 30000,
        "platform_fees_cents": 3000,
        "net_earnings_cents": 27000,
        "participant_count": 8,
        "average_per_participant_cents": 3375
      }
    ],
    "fee_breakdown": {
      "base_platform_fee_rate": 0.10,
      "subscription_discount": 0.02,
      "effective_fee_rate": 0.08,
      "volume_bonus_applied": true
    }
  }
}
```

### Payout Management Endpoints

#### GET /api/v1/revenue/payouts
**Purpose**: Get host payout history

**Query Parameters**:
- `status` (optional): pending, processing, paid, failed
- `limit` (optional): Max results (default: 20, max: 50)

**Response (200)**:
```json
{
  "payouts": [
    {
      "id": "uuid",
      "payout_amount_cents": 76500,
      "currency": "usd",
      "payout_status": "paid",
      "payout_method": "bank_transfer",
      "period": {
        "start_date": "2025-09-01",
        "end_date": "2025-09-07"
      },
      "earnings_count": 12,
      "bank_account": {
        "bank_name": "Chase Bank",
        "account_last_four": "1234"
      },
      "initiated_at": "2025-09-08T09:00:00Z",
      "processed_at": "2025-09-08T14:30:00Z",
      "estimated_arrival": "2025-09-10T00:00:00Z"
    }
  ],
  "pending_earnings": {
    "amount_cents": 15000,
    "next_payout_date": "2025-09-22T09:00:00Z"
  }
}
```

#### POST /api/v1/revenue/payouts/request
**Purpose**: Request an immediate payout (for eligible hosts)

**Request**:
```json
{
  "amount_cents": 50000, // Optional, defaults to all available earnings
  "payout_method": "bank_transfer" // bank_transfer, debit_card
}
```

**Response (201)**:
```json
{
  "payout": {
    "id": "uuid",
    "payout_amount_cents": 50000,
    "payout_status": "processing",
    "estimated_arrival": "2025-09-20T00:00:00Z",
    "fee_cents": 0, // Instant payout fees if applicable
    "initiated_at": "2025-09-18T21:00:00Z"
  }
}
```

## Subscription Management Service API

### Base Configuration
- **Base URL**: `/api/v1/subscriptions`
- **Authentication**: Required (JWT token)
- **Rate Limiting**: 30 requests per minute per user

### Subscription Management Endpoints

#### GET /api/v1/subscriptions/plans
**Purpose**: Get available subscription plans

**Response (200)**:
```json
{
  "plans": [
    {
      "id": "uuid",
      "plan_name": "Pro",
      "plan_tier": "pro",
      "price_cents": 999,
      "currency": "usd",
      "billing_interval": "month",
      "trial_period_days": 14,
      "features": {
        "unlimited_activities": true,
        "advanced_analytics": true,
        "priority_support": true,
        "reduced_fees": true
      },
      "feature_limits": {
        "platform_fee_reduction": 0.02,
        "premium_listings": 5
      },
      "description": "Perfect for active hosts who want to grow their business",
      "popular": true
    }
  ]
}
```

#### POST /api/v1/subscriptions
**Purpose**: Create a new subscription

**Request**:
```json
{
  "plan_id": "uuid",
  "payment_method_id": "uuid",
  "start_trial": true,
  "promotional_code": "WELCOME20" // Optional
}
```

**Response (201)**:
```json
{
  "subscription": {
    "id": "uuid",
    "plan": {
      "plan_name": "Pro",
      "plan_tier": "pro",
      "price_cents": 999
    },
    "subscription_status": "trialing",
    "current_period_start": "2025-09-18T21:30:00Z",
    "current_period_end": "2025-10-18T21:30:00Z",
    "trial_start": "2025-09-18T21:30:00Z",
    "trial_end": "2025-10-02T21:30:00Z",
    "cancel_at_period_end": false,
    "features_enabled": {
      "unlimited_activities": true,
      "advanced_analytics": true,
      "priority_support": true
    },
    "created_at": "2025-09-18T21:30:00Z"
  }
}
```

#### GET /api/v1/subscriptions/current
**Purpose**: Get user's current subscription

**Response (200)**:
```json
{
  "subscription": {
    "id": "uuid",
    "plan": {
      "plan_name": "Pro",
      "plan_tier": "pro",
      "price_cents": 999
    },
    "subscription_status": "active",
    "current_period_start": "2025-09-18T21:30:00Z",
    "current_period_end": "2025-10-18T21:30:00Z",
    "cancel_at_period_end": false,
    "usage": {
      "activities_created": 8,
      "premium_listings_used": 3,
      "analytics_views": 45
    },
    "next_billing_date": "2025-10-18T21:30:00Z",
    "next_billing_amount_cents": 999
  }
}
```

#### PUT /api/v1/subscriptions/{subscriptionId}
**Purpose**: Update subscription (upgrade/downgrade)

**Request**:
```json
{
  "new_plan_id": "uuid",
  "proration_behavior": "create_prorations" // create_prorations, none
}
```

**Response (200)**:
```json
{
  "subscription": {
    "id": "uuid",
    "plan": {
      "plan_name": "Premium",
      "plan_tier": "premium",
      "price_cents": 2999
    },
    "subscription_status": "active",
    "proration_amount_cents": 1500,
    "upgrade_effective_immediately": true,
    "next_billing_amount_cents": 2999
  }
}
```

#### DELETE /api/v1/subscriptions/{subscriptionId}
**Purpose**: Cancel subscription

**Request**:
```json
{
  "cancel_immediately": false, // If true, cancels immediately; if false, cancels at period end
  "cancellation_reason": "cost_too_high", // cost_too_high, not_using, found_alternative, other
  "feedback": "Great service, but need to reduce expenses right now"
}
```

**Response (200)**:
```json
{
  "subscription": {
    "id": "uuid",
    "subscription_status": "active",
    "cancel_at_period_end": true,
    "canceled_at": "2025-09-18T22:00:00Z",
    "cancellation_reason": "cost_too_high",
    "access_until": "2025-10-18T21:30:00Z"
  },
  "retention_offer": {
    "discount_percentage": 50,
    "discount_duration_months": 3,
    "offer_expires_at": "2025-09-25T22:00:00Z"
  }
}
```

## Marketplace Monetization Service API

### Base Configuration
- **Base URL**: `/api/v1/monetization`
- **Authentication**: Required (JWT token)
- **Rate Limiting**: 100 requests per minute per user

### Pricing Strategy Endpoints

#### POST /api/v1/monetization/pricing/{activityId}
**Purpose**: Create or update pricing strategy for an activity

**Request**:
```json
{
  "strategy_type": "dynamic",
  "base_price_cents": 5000,
  "dynamic_pricing_enabled": true,
  "demand_multiplier_max": 1.5,
  "early_bird_price_cents": 4000,
  "early_bird_deadline": "2025-09-15T23:59:59Z",
  "group_discount_enabled": true,
  "group_discount_threshold": 4,
  "group_discount_percentage": 0.15
}
```

**Response (201)**:
```json
{
  "pricing_strategy": {
    "id": "uuid",
    "activity_id": "uuid",
    "strategy_type": "dynamic",
    "base_price_cents": 5000,
    "current_price_cents": 5250, // Current dynamic price
    "dynamic_pricing_enabled": true,
    "pricing_factors": {
      "demand_multiplier": 1.05,
      "time_factor": 1.0,
      "capacity_factor": 1.0
    },
    "early_bird_active": true,
    "early_bird_price_cents": 4000,
    "early_bird_deadline": "2025-09-15T23:59:59Z"
  }
}
```

#### GET /api/v1/monetization/pricing/{activityId}/optimal
**Purpose**: Get optimal pricing recommendation

**Response (200)**:
```json
{
  "optimal_pricing": {
    "recommended_price_cents": 5750,
    "confidence_score": 0.87,
    "expected_conversion_rate": 0.23,
    "revenue_projection_cents": 46000,
    "pricing_factors": {
      "demand_score": 0.85,
      "competitive_position": "above_average",
      "time_urgency": 0.3,
      "capacity_pressure": 0.6
    },
    "recommendations": [
      "Consider increasing price by 10% due to high demand",
      "Early bird pricing is driving strong conversions",
      "Similar activities in your area are priced 15% higher"
    ]
  }
}
```

### Discount and Promotion Endpoints

#### POST /api/v1/monetization/discounts
**Purpose**: Create a discount code

**Request**:
```json
{
  "code": "SUMMER25",
  "discount_type": "percentage",
  "discount_value": 25,
  "usage_limit": 100,
  "per_user_limit": 1,
  "valid_from": "2025-09-20T00:00:00Z",
  "valid_until": "2025-09-30T23:59:59Z",
  "applicable_to": "specific_activities",
  "applicable_activity_ids": ["uuid1", "uuid2"],
  "minimum_purchase_cents": 2000
}
```

**Response (201)**:
```json
{
  "discount_code": {
    "id": "uuid",
    "code": "SUMMER25",
    "discount_type": "percentage",
    "discount_value": 25,
    "usage_count": 0,
    "usage_limit": 100,
    "is_active": true,
    "valid_from": "2025-09-20T00:00:00Z",
    "valid_until": "2025-09-30T23:59:59Z",
    "share_url": "https://funlynk.com/promo/SUMMER25"
  }
}
```

#### GET /api/v1/monetization/analytics
**Purpose**: Get monetization analytics for host

**Query Parameters**:
- `timeframe` (optional): 7d, 30d, 90d (default: 30d)
- `metric` (optional): revenue, conversion, pricing

**Response (200)**:
```json
{
  "monetization_analytics": {
    "timeframe": "30d",
    "revenue_metrics": {
      "total_revenue_cents": 125000,
      "average_price_cents": 5200,
      "price_optimization_impact_cents": 8500,
      "discount_usage_impact_cents": -3200
    },
    "conversion_metrics": {
      "overall_conversion_rate": 0.18,
      "price_point_analysis": [
        {
          "price_range": "4000-5000",
          "conversion_rate": 0.25,
          "volume": 45
        }
      ]
    },
    "optimization_opportunities": [
      {
        "type": "dynamic_pricing",
        "potential_revenue_increase_cents": 12000,
        "confidence": 0.82,
        "description": "Enable dynamic pricing for high-demand activities"
      }
    ]
  }
}
```

## Error Response Format

All APIs use consistent error response format:

```json
{
  "error": {
    "code": "PAYMENT_METHOD_DECLINED",
    "message": "Your payment method was declined. Please try a different payment method.",
    "details": {
      "decline_code": "insufficient_funds",
      "payment_method_id": "uuid",
      "suggested_actions": [
        "Try a different payment method",
        "Contact your bank",
        "Use a different card"
      ]
    }
  },
  "request_id": "uuid"
}
```

### Payment & Monetization Error Codes
- `PAYMENT_METHOD_DECLINED`: Payment method was declined by the bank
- `INSUFFICIENT_FUNDS`: Not enough funds for the transaction
- `SUBSCRIPTION_ALREADY_EXISTS`: User already has an active subscription
- `PAYOUT_MINIMUM_NOT_MET`: Earnings below minimum payout threshold
- `DISCOUNT_CODE_EXPIRED`: Discount code is no longer valid
- `PRICING_STRATEGY_CONFLICT`: Conflicting pricing strategies for activity
- `FEATURE_ACCESS_DENIED`: User doesn't have access to premium feature

---

**API Contracts Status**: ✅ Complete
**Next Steps**: Define integration points with other epics
