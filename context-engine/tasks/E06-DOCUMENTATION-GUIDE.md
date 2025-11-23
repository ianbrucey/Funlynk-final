# E06 Payments & Monetization - Laravel Documentation Guide

## Epic Context
**Purpose**: Enable payment processing, revenue sharing, subscriptions, and monetization for paid activities.
**Reference**: `context-engine/epics/E06_Payments_Monetization/epic-overview.md`

**CRITICAL**: Only EVENTS (activities) can be paid, NOT Posts. Posts are always free and ephemeral.

## Features to Document (4 total)

### F01: Payment Processing System
**Purpose**: Stripe Connect integration for activity payments and RSVPs
**Key Components**:
- Stripe Connect Express accounts for hosts
- Payment processing for paid activities
- RSVP payment capture
- Refund handling
- Payment webhooks

**E01 Integration**:
- Uses `rsvps` table with payment fields (payment_status, payment_amount, stripe_payment_intent_id)
- May need `stripe_accounts` table (user_id, stripe_account_id, onboarding_complete)
- May need `transactions` table for payment history

**Suggested Tasks (6-7 tasks, 35-45 hours)**:
- T01: Stripe Connect Database Schema (3-4h)
- T02: StripeAccount & Transaction Models (3-4h)
- T03: StripeConnectService (6-7h)
- T04: PaymentService for RSVP Payments (5-6h)
- T05: Filament Payment Management (4-5h)
- T06: Livewire Payment Components (6-7h)
- T07: Stripe Webhook Handling (5-6h)
- T08: Payment Tests (4-5h)

**Key Packages**:
- `stripe/stripe-php` for Stripe API
- `laravel/cashier` (optional, for subscriptions)

---

### F02: Revenue Sharing & Payouts
**Purpose**: Calculate host earnings, platform fees, automated payouts
**Key Components**:
- Revenue split calculation (host vs platform)
- Earnings dashboard for hosts
- Automated payout scheduling
- Payout history and tracking
- Tax reporting (1099 generation)

**E01 Integration**:
- Uses `transactions` table for earnings tracking
- Uses `stripe_accounts` table for payout destinations
- May need `payouts` table (user_id, amount, status, stripe_payout_id)

**Suggested Tasks (5-7 tasks, 30-40 hours)**:
- T01: Payout Database Schema (2-3h)
- T02: Payout & Earnings Models (3-4h)
- T03: RevenueService with Split Logic (5-6h)
- T04: PayoutService with Stripe Transfers (5-6h)
- T05: Filament Earnings Dashboard (5-6h)
- T06: Livewire Host Earnings Components (5-6h)
- T07: Payout Tests (4-5h)

**Key Packages**:
- `stripe/stripe-php` for Stripe Transfers
- `barryvdh/laravel-dompdf` for PDF invoices

---

### F03: Subscription & Premium Features
**Purpose**: Tiered subscriptions for users and hosts with premium features
**Key Components**:
- Subscription plans (Free, Pro, Premium)
- Subscription billing and management
- Feature gating based on subscription
- Trial periods and upgrades
- Subscription analytics

**E01 Integration**:
- May need `subscriptions` table (user_id, plan, status, stripe_subscription_id)
- May need `subscription_plans` table (name, price, features)
- Uses `users` table with subscription_tier field

**Suggested Tasks (6-7 tasks, 30-40 hours)**:
- T01: Subscription Database Schema (3-4h)
- T02: Subscription & Plan Models (3-4h)
- T03: SubscriptionService with Stripe Billing (6-7h)
- T04: Feature Gate Middleware (3-4h)
- T05: Filament Subscription Management (4-5h)
- T06: Livewire Subscription Components (5-6h)
- T07: Subscription Tests (4-5h)

**Key Packages**:
- `laravel/cashier-stripe` for subscription billing
- Laravel Gates for feature access control

---

### F04: Marketplace & Monetization Tools
**Purpose**: Advanced pricing, promotions, discounts, sponsored listings
**Key Components**:
- Dynamic pricing for activities
- Coupon codes and discounts
- Early bird pricing
- Group discounts
- Sponsored activity placement
- Premium listing boosts

**E01 Integration**:
- May need `coupons` table (code, discount_type, discount_value, expires_at)
- May need `coupon_usages` table (coupon_id, user_id, activity_id)
- May need `sponsored_listings` table (activity_id, boost_level, expires_at)
- Uses `activities` table with pricing fields

**Suggested Tasks (6-7 tasks, 30-40 hours)**:
- T01: Coupon & Promotion Database Schema (3-4h)
- T02: Coupon & Promotion Models (3-4h)
- T03: PricingService with Dynamic Pricing (5-6h)
- T04: CouponService with Validation (4-5h)
- T05: Filament Coupon Management (4-5h)
- T06: Livewire Pricing & Coupon Components (5-6h)
- T07: Pricing & Coupon Tests (4-5h)

**Key Packages**:
- Custom pricing logic (no specific package needed)

---

## Common Patterns Across All Features

### Database Migrations
```bash
php artisan make:migration create_stripe_accounts_table --no-interaction
php artisan make:migration create_transactions_table --no-interaction
php artisan make:migration create_subscriptions_table --no-interaction
php artisan make:migration create_coupons_table --no-interaction
```

### Models
```bash
php artisan make:model StripeAccount --no-interaction
php artisan make:model Transaction --no-interaction
php artisan make:model Subscription --no-interaction
php artisan make:model Coupon --no-interaction
```

### Service Classes
```bash
php artisan make:class Services/StripeConnectService --no-interaction
php artisan make:class Services/PaymentService --no-interaction
php artisan make:class Services/RevenueService --no-interaction
php artisan make:class Services/SubscriptionService --no-interaction
php artisan make:class Services/PricingService --no-interaction
```

### Filament Resources
```bash
php artisan make:filament-resource Transaction --generate --no-interaction
php artisan make:filament-resource Subscription --generate --no-interaction
php artisan make:filament-resource Coupon --generate --no-interaction
```

### Livewire Components
```bash
php artisan make:livewire Payments/CheckoutForm --no-interaction
php artisan make:livewire Payments/EarningsDashboard --no-interaction
php artisan make:livewire Subscriptions/PlanSelector --no-interaction
php artisan make:livewire Pricing/CouponInput --no-interaction
```

### Jobs (Async Processing)
```bash
php artisan make:job ProcessPayoutJob --no-interaction
php artisan make:job ProcessRefundJob --no-interaction
php artisan make:job SyncStripeWebhookJob --no-interaction
```

### Tests
```bash
php artisan make:test --pest Feature/StripeConnectTest --no-interaction
php artisan make:test --pest Feature/PaymentProcessingTest --no-interaction
php artisan make:test --pest Feature/SubscriptionTest --no-interaction
php artisan make:test --pest Feature/CouponTest --no-interaction
```

---

## Testing Checklist

### F01: Payment Processing System
- [ ] Can onboard hosts to Stripe Connect
- [ ] Can process RSVP payments for paid activities
- [ ] Can handle refunds correctly
- [ ] Stripe webhooks processed successfully
- [ ] Payment failures handled gracefully

### F02: Revenue Sharing & Payouts
- [ ] Revenue split calculated correctly
- [ ] Payouts scheduled and processed
- [ ] Earnings dashboard displays accurate data
- [ ] Tax documents generated

### F03: Subscription & Premium Features
- [ ] Can subscribe to plans
- [ ] Feature gates work correctly
- [ ] Trial periods enforced
- [ ] Upgrades/downgrades handled
- [ ] Subscription webhooks processed

### F04: Marketplace & Monetization Tools
- [ ] Coupon codes validate and apply correctly
- [ ] Dynamic pricing calculates accurately
- [ ] Group discounts work
- [ ] Sponsored listings boost visibility

