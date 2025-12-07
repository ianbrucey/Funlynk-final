# F01 Payment Processing System

## Feature Overview

Enable Stripe Connect payment processing for paid activities using Laravel 12, Filament v4, and `stripe/stripe-php`. This feature allows hosts to charge for activity attendance via RSVP payments, with platform fee collection and secure payment handling. **CRITICAL: Only EVENTS (activities) can be paid—Posts are always free and ephemeral.**

**Key Architecture**: Hosts onboard to Stripe Connect Express accounts. When users RSVP to paid activities, payment intents are created, funds are held, and platform fees are deducted. Webhooks handle payment confirmations, failures, and refunds. Builds on E01's `rsvps` table.

## Feature Scope

### In Scope
- **Stripe Connect onboarding**: Express accounts for activity hosts
- **RSVP payment processing**: Charge attendees for paid activities
- **Payment intent flow**: Authorize payments before finalizing RSVPs
- **Refund handling**: Process full/partial refunds for canceled activities
- **Webhook processing**: Handle Stripe events (payment_intent.succeeded, etc.)
- **Transaction history**: Track all payments and refunds

### Out of Scope
- **Post payments**: Posts cannot be monetized (always free)
- **Subscriptions**: Handled in E06/F03
- **Coupons**: Handled in E06/F04
- **Payouts**: Handled in E06/F02

## Tasks Breakdown

### T01: Stripe & Transaction Database Schema
**Estimated Time**: 3-4 hours
**Dependencies**: None
**Artisan Commands**:
```bash
php artisan make:migration create_stripe_accounts_table --no-interaction
php artisan make:migration create_transactions_table --no-interaction
php artisan make:migration add_payment_fields_to_rsvps_table --no-interaction
php artisan make:migration add_payment_fields_to_activities_table --no-interaction
```

**Description**: Create tables for Stripe accounts, transactions, and add payment fields to existing `rsvps` and `activities` tables.

**Key Implementation Details**:
- `stripe_accounts`: `id`, `user_id`, `stripe_account_id`, `onboarding_complete`, `charges_enabled`, `payouts_enabled`, `requirements` (JSON), `created_at`
- `transactions`: `id`, `user_id`, `activity_id`, `rsvp_id`, `stripe_payment_intent_id`, `amount`, `platform_fee`, `host_earnings`, `status`, `refunded_amount`, `created_at`
- Add to `rsvps`: `payment_status` (pending/paid/failed/refunded), `payment_amount`, `stripe_payment_intent_id`
- Add to `activities`: `is_paid`, `price`, `currency` (default USD)

**Deliverables**:
- [ ] Migration files for stripe_accounts and transactions
- [ ] Migration adding payment columns to rsvps and activities
- [ ] Indexes on stripe_payment_intent_id and status
- [ ] Schema tests

---

### T02: StripeAccount & Transaction Models
**Estimated Time**: 3-4 hours
**Dependencies**: T01
**Artisan Commands**:
```bash
php artisan make:model StripeAccount --no-interaction
php artisan make:model Transaction --no-interaction
php artisan make:factory TransactionFactory --model=Transaction --no-interaction
```

**Description**: Create models with relationships to User, Activity, Rsvp. Implement `casts()` for JSON and enum fields.

**Key Implementation Details**:
- Use `casts()` method (Laravel 12)
- Cast `payment_status` and `status` as enums
- Cast `requirements` as array
- Relationships: `StripeAccount belongsTo User`, `Transaction belongsTo User/Activity/Rsvp`
- Helper methods: `isOnboarded()`, `canAcceptPayments()`, `isPaid()`, `isRefunded()`

**Deliverables**:
- [ ] StripeAccount model with onboarding helpers
- [ ] Transaction model with status tracking
- [ ] Updated Rsvp and Activity models with payment fields
- [ ] Factories for testing

---

### T03: StripeConnectService
**Estimated Time**: 6-7 hours
**Dependencies**: T02
**Artisan Commands**:
```bash
composer require stripe/stripe-php
php artisan make:class Services/StripeConnectService --no-interaction
php artisan make:test --pest Feature/StripeConnectTest --no-interaction
```

**Description**: Build service class handling Stripe Connect onboarding, account verification, and account link generation for hosts.

**Key Implementation Details**:
- `createConnectAccount($user)`: Create Stripe Connect Express account
- `generateAccountLink($stripeAccountId)`: Create onboarding/return links
- `getAccountStatus($stripeAccountId)`: Check onboarding completion and requirements
- `updateAccountStatus($stripeAccountId)`: Sync Stripe account data to database
- Store Stripe API key in `.env`: `STRIPE_SECRET_KEY`, `STRIPE_PUBLISHABLE_KEY`
- Handle webhook: `account.updated` to sync account status

**Deliverables**:
- [ ] StripeConnectService with account management
- [ ] Onboarding flow (create account → generate link → verify)
- [ ] Account status synchronization
- [ ] Tests for all service methods

---

### T04: PaymentService with Payment Intent Flow
**Estimated Time**: 6-7 hours
**Dependencies**: T02, T03
**Artisan Commands**:
```bash
php artisan make:class Services/PaymentService --no-interaction
php artisan make:test --pest Feature/PaymentProcessingTest --no-interaction
```

**Description**: Build service class handling RSVP payments with Stripe Payment Intents, platform fee calculation, and transaction recording.

**Key Implementation Details**:
- `createPaymentIntent($activity, $user, $amount)`: Create Stripe Payment Intent with application_fee
- `confirmPayment($paymentIntentId)`: Confirm payment and update RSVP status
- `processRefund($transaction, $amount)`: Issue refund via Stripe and update transaction
- Calculate platform fee: 10% of activity price (configurable)
- Transfer funds to host's Connect account using `transfer_data` or `application_fee_amount`
- Create `Transaction` record on payment success
- Update `rsvps.payment_status` to `paid` on confirmation

**Deliverables**:
- [ ] PaymentService with Payment Intent flow
- [ ] Platform fee calculation
- [ ] Refund processing
- [ ] Transaction creation and tracking
- [ ] Tests for payment workflows

---

### T05: Filament Payment Management
**Estimated Time**: 4-5 hours
**Dependencies**: T02
**Artisan Commands**:
```bash
php artisan make:filament-resource Transaction --generate --no-interaction
php artisan make:filament-resource StripeAccount --generate --no-interaction
```

**Description**: Create Filament admin resources for viewing transactions, managing Stripe accounts, and processing refunds.

**Key Implementation Details**:
- Use Filament v4 `->components([])` for forms
- TransactionResource: display amount, fees, status, related activity/user
- StripeAccountResource: show onboarding status, account capabilities
- Add filters: by status, by date range, by activity
- Bulk actions: process refunds, export transactions
- Analytics widget: revenue, fees, refund rate

**Deliverables**:
- [ ] TransactionResource with refund actions
- [ ] StripeAccountResource with status monitoring
- [ ] Filters and bulk actions
- [ ] Revenue analytics widget

---

### T06: Livewire Payment Components
**Estimated Time**: 6-7 hours
**Dependencies**: T04
**Artisan Commands**:
```bash
php artisan make:livewire Payments/CheckoutForm --no-interaction
php artisan make:livewire Payments/StripeOnboarding --no-interaction
php artisan make:livewire Payments/TransactionHistory --no-interaction
php artisan make:test --pest Feature/PaymentComponentsTest --no-interaction
```

**Description**: Build user-facing components for RSVP checkout, Stripe Connect onboarding, and transaction history viewing.

**Key Implementation Details**:
- `CheckoutForm`: Stripe Elements integration for card input, handles Payment Intent confirmation
- `StripeOnboarding`: Displays onboarding progress, generates account links
- `TransactionHistory`: Lists user's payment history with filters
- Use Stripe.js for secure card tokenization (no card data touches server)
- Implement 3D Secure (SCA) compliance with Payment Intents
- Use DaisyUI and galaxy theme styling
- Display clear error messages for failed payments

**Deliverables**:
- [ ] CheckoutForm with Stripe Elements
- [ ] StripeOnboarding with account link generation
- [ ] TransactionHistory with filtering
- [ ] Tests for all Livewire interactions

---

### T07: Stripe Webhook Handling
**Estimated Time**: 5-6 hours
**Dependencies**: T03, T04
**Artisan Commands**:
```bash
php artisan make:controller WebhookController --no-interaction
php artisan make:job ProcessStripeWebhookJob --no-interaction
php artisan make:test --pest Feature/StripeWebhookTest --no-interaction
```

**Description**: Implement webhook endpoint for Stripe events with signature verification, event processing, and error handling.

**Key Implementation Details**:
- Create webhook route: `POST /stripe/webhook`
- Verify webhook signatures using `STRIPE_WEBHOOK_SECRET`
- Handle events: `payment_intent.succeeded`, `payment_intent.payment_failed`, `charge.refunded`, `account.updated`
- Queue webhook processing with `ProcessStripeWebhookJob`
- Update `rsvps.payment_status` and `transactions.status` based on events
- Log all webhook events for debugging
- Implement idempotency (prevent duplicate processing)

**Deliverables**:
- [ ] Webhook endpoint with signature verification
- [ ] Event handlers for payment events
- [ ] ProcessStripeWebhookJob for async processing
- [ ] Webhook logging and monitoring
- [ ] Tests mocking Stripe webhooks

---

### T08: Payment Tests & Edge Cases
**Estimated Time**: 4-5 hours
**Dependencies**: T01-T07
**Artisan Commands**:
```bash
php artisan make:test --pest Feature/PaymentEdgeCasesTest --no-interaction
php artisan test --filter=Payment
```

**Description**: Comprehensive testing of payment flows including happy paths, failures, refunds, and edge cases.

**Key Implementation Details**:
- Test successful payment flow (RSVP → Payment Intent → Confirmation → Transaction created)
- Test failed payments (declined cards, insufficient funds)
- Test refunds (full and partial)
- Test Stripe Connect onboarding flow
- Test webhook processing with various events
- Mock Stripe API calls in tests
- Test concurrent RSVP payments (race conditions)
- Test payment status edge cases (already paid, already refunded)

**Deliverables**:
- [ ] Payment flow tests (happy path and failures)
- [ ] Refund tests
- [ ] Webhook processing tests
- [ ] Edge case tests (concurrent, idempotency)
- [ ] All tests passing with >80% coverage

---

## Success Criteria

### Functional Requirements
- [ ] Hosts can onboard to Stripe Connect
- [ ] Users can pay for activity RSVPs via Stripe
- [ ] Platform fees are collected automatically
- [ ] Refunds can be processed for canceled activities
- [ ] Transaction history is accessible to users and admins
- [ ] Webhook events update payment statuses correctly

### Technical Requirements
- [ ] Stripe Connect Express accounts created for hosts
- [ ] Payment Intents handle 3D Secure (SCA compliance)
- [ ] Platform fee calculation accurate (10% default)
- [ ] Webhook signature verification prevents spoofing
- [ ] Idempotency prevents duplicate payments
- [ ] All Stripe API errors handled gracefully

### User Experience Requirements
- [ ] Checkout form secure and intuitive
- [ ] Onboarding process clear for hosts
- [ ] Payment errors display helpful messages
- [ ] Transaction history easy to read
- [ ] Galaxy theme applied to payment UI
- [ ] Mobile-friendly payment forms

### Performance Requirements
- [ ] Payment processing <5 seconds
- [ ] Webhook processing asynchronous
- [ ] Transaction queries optimized
- [ ] Stripe API calls cached when appropriate

## Dependencies

### Blocks
- **E06/F02 Revenue Sharing**: Depends on payment infrastructure

### External Dependencies
- **E01 Core Infrastructure**: `rsvps`, `activities`, `users` tables
- **stripe/stripe-php**: Stripe API integration
- **Stripe Connect**: Payment processing platform

## Technical Notes

### Laravel 12 Conventions
- Use `casts()` method instead of `$casts` property in models
- Queue webhook processing with Laravel Jobs

### Filament v4 Conventions
- Use `->components([])` for forms
- Use `->actions()` for refund operations

### Stripe Payment Intent Flow
```php
// Create Payment Intent with application fee
$paymentIntent = \Stripe\PaymentIntent::create([
    'amount' => $amount * 100, // cents
    'currency' => 'usd',
    'application_fee_amount' => ($amount * 0.10) * 100, // 10% platform fee
    'transfer_data' => [
        'destination' => $host->stripeAccount->stripe_account_id,
    ],
    'metadata' => [
        'activity_id' => $activity->id,
        'user_id' => $user->id,
    ],
]);
```

### Webhook Verification
```php
// Verify webhook signature
$signature = $request->header('Stripe-Signature');
$event = \Stripe\Webhook::constructEvent(
    $request->getContent(),
    $signature,
    config('services.stripe.webhook_secret')
);
```

### Testing Considerations
- Mock Stripe API with `\Stripe\Stripe::setApiKey('sk_test_...')`
- Use Stripe test card numbers: `4242424242424242` (success), `4000000000000002` (declined)
- Test webhook events by constructing fake event objects
- Run tests with: `php artisan test --filter=Payment`

### Performance Optimization
- Cache Stripe account status (15 minute TTL)
- Queue webhook processing asynchronously
- Eager load relationships: `Transaction::with('user', 'activity', 'rsvp')`
- Use database transactions for payment confirmation

---

**Feature Status**: 🔄 Ready for Implementation
**Priority**: P0
**Epic**: E06 Payments & Monetization
**Estimated Total Time**: 38-45 hours
**Dependencies**: E01 foundation complete, Stripe account required
