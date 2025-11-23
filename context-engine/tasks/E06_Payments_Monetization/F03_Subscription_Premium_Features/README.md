# F03 Subscription & Premium Features

## Feature Overview

Enable tiered subscriptions (Free, Pro, Premium) for users and hosts using Laravel 12, Filament v4, and Laravel Cashier (Stripe). This feature provides premium features like unlimited activity creation, advanced analytics, and priority support. Implements feature gating based on subscription tier.

**Key Architecture**: Uses Laravel Cashier for Stripe subscription billing. Feature gates control access to premium functionality. Trial periods allow users to test premium features before commitment.

## Feature Scope

### In Scope
- **Subscription plans**: Free, Pro ($9/month), Premium ($29/month)
- **Subscription billing**: Stripe recurring billing via Cashier
- **Feature gating**: Middleware and gates limiting features by tier
- **Trial periods**: 14-day trial for Pro/Premium
- **Upgrades/downgrades**: Seamless plan switching
- **Subscription analytics**: MRR, churn, LTV tracking

### Out of Scope
- **Annual billing**: Monthly only in Phase 1
- **Team subscriptions**: Individual only
- **Add-ons**: Single plan tiers only

## Tasks Breakdown

### T01: Subscription Database Schema
**Estimated Time**: 3-4 hours
**Dependencies**: None
**Artisan Commands**:
```bash
composer require laravel/cashier-stripe
php artisan vendor:publish --tag=cashier-migrations
php artisan make:migration create_subscription_plans_table --no-interaction
php artisan make:migration add_subscription_fields_to_users_table --no-interaction
```

**Description**: Install Cashier, publish migrations, create subscription_plans table, add fields to users table.

**Key Implementation Details**:
- Cashier migrations: `subscriptions`, `subscription_items`
- `subscription_plans`: `id`, `name`, `slug`, `stripe_price_id`, `price`, `features` (JSON), `is_active`
- Add to `users`: `stripe_id`, `pm_type`, `pm_last_four`, `trial_ends_at`
- Define plans: Free (0), Pro (9), Premium (29)

**Deliverables**:
- [ ] Cashier migrations run
- [ ] subscription_plans table created
- [ ] User table updated with Cashier fields
- [ ] Seed subscription plans

---

### T02: Subscription & Plan Models
**Estimated Time**: 3-4 hours
**Dependencies**: T01
**Artisan Commands**:
```bash
php artisan make:model SubscriptionPlan --no-interaction
```

**Description**: Configure User model as Billable, create SubscriptionPlan model, implement `casts()` for JSON features.

**Key Implementation Details**:
- Add `Billable` trait to User model
- Use `casts()` method (Laravel 12)
- Cast `features` as array on SubscriptionPlan
- Helper methods: `hasFeature($feature)`, `isOnTrial()`, `onPlan($plan)`

**Deliverables**:
- [ ] User model implements Billable trait
- [ ] SubscriptionPlan model created
- [ ] Feature checking helpers
- [ ] Factory for plans

---

### T03: SubscriptionService with Billing Logic
**Estimated Time**: 6-7 hours
**Dependencies**: T02
**Artisan Commands**:
```bash
php artisan make:class Services/SubscriptionService --no-interaction
php artisan make:test --pest Feature/SubscriptionServiceTest --no-interaction
```

**Description**: Build service class handling subscription creation, upgrades, downgrades, and cancellations using Cashier.

**Key Implementation Details**:
- `subscribe($user, $plan)`: Create Stripe subscription via Cashier
- `upgrade($user, $newPlan)`: Upgrade to higher tier (prorate)
- `downgrade($user, $newPlan)`: Downgrade at period end
- `cancel($user, $immediately = false)`: Cancel subscription
- `resume($user)`: Resume canceled subscription
- Trial period: 14 days for Pro/Premium

**Deliverables**:
- [ ] SubscriptionService with Cashier integration
- [ ] Upgrade/downgrade logic with proration
- [ ] Trial period handling
- [ ] Tests for all subscription workflows

---

### T04: Feature Gate Middleware
**Estimated Time**: 3-4 hours
**Dependencies**: T02
**Artisan Commands**:
```bash
php artisan make:middleware CheckSubscription --no-interaction
php artisan make:middleware RequiresPremium --no-interaction
```

**Description**: Implement middleware and Laravel Gates controlling access to premium features based on subscription tier.

**Key Implementation Details**:
- `CheckSubscription`: Verify active subscription
- `RequiresPremium`: Require Premium tier
- Define Gates: `create-unlimited-activities`, `access-analytics`, `priority-support`
- Free tier limits: 3 activities/month, basic analytics
- Pro tier: unlimited activities, advanced analytics
- Premium tier: all features + priority support

**Deliverables**:
- [ ] Subscription middleware
- [ ] Feature gates defined
- [ ] Integration with routes
- [ ] Tests for feature gating

---

### T05: Filament Subscription Management
**Estimated Time**: 4-5 hours
**Dependencies**: T02
**Artisan Commands**:
```bash
php artisan make:filament-resource SubscriptionPlan --generate --no-interaction
php artisan make:filament-widget SubscriptionMetrics --no-interaction
```

**Description**: Create Filament admin resources for managing subscription plans and viewing subscription analytics.

**Key Implementation Details**:
- Use Filament v4 `->components([])` for forms
- SubscriptionPlanResource: manage plans, features, pricing
- SubscriptionMetrics widget: MRR, active subscribers, churn rate
- View user subscriptions in UserResource
- Add filters: by plan, by status (active/canceled/trial)

**Deliverables**:
- [ ] SubscriptionPlan resource
- [ ] SubscriptionMetrics widget
- [ ] User subscription display
- [ ] Admin tests

---

### T06: Livewire Subscription Components
**Estimated Time**: 5-6 hours
**Dependencies**: T03
**Artisan Commands**:
```bash
php artisan make:livewire Subscriptions/PlanSelector --no-interaction
php artisan make:livewire Subscriptions/CheckoutModal --no-interaction
php artisan make:livewire Subscriptions/ManageSubscription --no-interaction
```

**Description**: Build user-facing components for selecting plans, checkout, and managing subscriptions.

**Key Implementation Details**:
- `PlanSelector`: Display plan cards with features, pricing, CTAs
- `CheckoutModal`: Stripe Checkout or Elements for payment collection
- `ManageSubscription`: View current plan, upgrade/downgrade, cancel
- Show trial remaining days
- Display next billing date
- Use DaisyUI and galaxy theme styling

**Deliverables**:
- [ ] PlanSelector with plan comparison
- [ ] CheckoutModal with Stripe integration
- [ ] ManageSubscription dashboard
- [ ] Tests for all components

---

### T07: Subscription Tests
**Estimated Time**: 4-5 hours
**Dependencies**: T01-T06
**Artisan Commands**:
```bash
php artisan make:test --pest Feature/SubscriptionWorkflowTest --no-interaction
php artisan test --filter=Subscription
```

**Description**: Comprehensive testing of subscription workflows including trials, upgrades, and feature gating.

**Key Implementation Details**:
- Test subscription creation with trial
- Test upgrade with proration
- Test downgrade at period end
- Test cancellation and resume
- Test feature gate enforcement
- Mock Stripe API calls

**Deliverables**:
- [ ] Subscription workflow tests
- [ ] Feature gating tests
- [ ] Cashier integration tests
- [ ] All tests passing with >80% coverage

---

## Success Criteria

### Functional Requirements
- [ ] Users can subscribe to Pro/Premium plans
- [ ] 14-day trial periods work correctly
- [ ] Upgrades and downgrades process smoothly
- [ ] Feature gates enforce tier restrictions
- [ ] Users can cancel subscriptions
- [ ] Subscription analytics track MRR

### Technical Requirements
- [ ] Laravel Cashier integrated with Stripe
- [ ] Feature gates implemented
- [ ] Trial periods configured
- [ ] Proration calculated correctly
- [ ] Webhook events handled

### User Experience Requirements
- [ ] Plan selection clear and compelling
- [ ] Checkout process smooth
- [ ] Subscription management intuitive
- [ ] Galaxy theme applied

### Performance Requirements
- [ ] Subscription queries optimized
- [ ] Feature gate checks cached
- [ ] Stripe API calls handled efficiently

## Dependencies

### External Dependencies
- **laravel/cashier-stripe**: Subscription billing
- **Stripe**: Payment processing

## Technical Notes

### Laravel 12 Conventions
- Use `casts()` method in models
- Use Cashier's `Billable` trait on User

### Cashier Subscription Creation
```php
// Subscribe with trial
$user->newSubscription('default', 'price_pro_monthly')
    ->trialDays(14)
    ->create($paymentMethod);
```

### Feature Gate Definition
```php
// In AuthServiceProvider
Gate::define('create-unlimited-activities', function ($user) {
    return $user->subscribed('default') && 
           in_array($user->subscription('default')->stripe_price, ['price_pro', 'price_premium']);
});
```

### Testing Considerations
- Mock Stripe with Cashier's testing helpers
- Use `$user->createAsStripeCustomer()` in tests
- Run tests with: `php artisan test --filter=Subscription`

---

**Feature Status**: 🔄 Ready for Implementation
**Priority**: P1
**Epic**: E06 Payments & Monetization
**Estimated Total Time**: 29-36 hours
**Dependencies**: Stripe account configured
