# F02 Revenue Sharing & Payouts

## Feature Overview

Implement automated revenue sharing and payout management for activity hosts using Laravel 12, Filament v4, and Stripe Transfers. This feature calculates host earnings after platform fees, schedules automated payouts, and provides earnings dashboards. Builds on E06/F01's payment infrastructure.

**Key Architecture**: Platform collects 10% fee from each transaction (configurable). Host earnings (90%) accumulate in their balance. Automated payouts transfer earnings to host's bank accounts via Stripe. Tax reporting generates 1099 forms for US hosts earning >$600/year.

## Feature Scope

### In Scope
- **Revenue split calculation**: Platform fee vs host earnings per transaction
- **Earnings tracking**: Real-time balance updates for hosts
- **Automated payouts**: Scheduled transfers to host bank accounts
- **Payout history**: Track all payouts with statuses
- **Earnings dashboard**: Host-facing earnings and analytics
- **Tax reporting**: 1099 form generation for US hosts

### Out of Scope
- **Manual payouts**: All payouts are automated
- **International tax compliance**: Focus on US 1099s only in Phase 1
- **Currency conversion**: USD only in Phase 1

## Tasks Breakdown

### T01: Payout Database Schema
**Estimated Time**: 2-3 hours
**Dependencies**: E06/F01 complete
**Artisan Commands**:
```bash
php artisan make:migration create_payouts_table --no-interaction
php artisan make:migration create_earnings_table --no-interaction
php artisan make:migration add_balance_to_stripe_accounts_table --no-interaction
```

**Description**: Create tables for payouts, earnings tracking, and add balance fields to stripe_accounts.

**Key Implementation Details**:
- `payouts` table: `id`, `stripe_account_id`, `amount`, `stripe_payout_id`, `status` (pending/paid/failed), `arrival_date`, `created_at`
- `earnings` table: `id`, `stripe_account_id`, `transaction_id`, `amount`, `platform_fee`, `created_at`
- Add to `stripe_accounts`: `available_balance`, `pending_balance`, `last_payout_at`

**Deliverables**:
- [ ] Payout and earnings tables created
- [ ] Balance fields added to stripe_accounts
- [ ] Indexes on status and dates
- [ ] Schema tests

---

### T02: Payout & Earnings Models
**Estimated Time**: 3-4 hours
**Dependencies**: T01
**Artisan Commands**:
```bash
php artisan make:model Payout --no-interaction
php artisan make:model Earning --no-interaction
php artisan make:factory PayoutFactory --model=Payout --no-interaction
```

**Description**: Create models with relationships to StripeAccount and Transaction. Implement `casts()` for status enums.

**Key Implementation Details**:
- Use `casts()` method (Laravel 12)
- Cast `status` as enum
- Relationships: `Payout belongsTo StripeAccount`, `Earning belongsTo StripeAccount/Transaction`
- Helper methods: `isPaid()`, `isFailed()`, `canRetry()`

**Deliverables**:
- [ ] Payout and Earning models with relationships
- [ ] Status casting and helpers
- [ ] Factories for testing

---

### T03: RevenueService with Split Logic
**Estimated Time**: 5-6 hours
**Dependencies**: T02
**Artisan Commands**:
```bash
php artisan make:class Services/RevenueService --no-interaction
php artisan make:test --pest Feature/RevenueServiceTest --no-interaction
```

**Description**: Build service class calculating revenue splits, tracking earnings, and updating balances.

**Key Implementation Details**:
- `calculateSplit($amount)`: Returns platform_fee (10%) and host_earnings (90%)
- `recordEarning($transaction)`: Create Earning record, update stripe_account.available_balance
- `getHostBalance($stripeAccountId)`: Query available + pending balance
- `getEarningsReport($stripeAccountId, $startDate, $endDate)`: Generate earnings breakdown
- Platform fee configurable via `config('payments.platform_fee_percentage', 10)`

**Deliverables**:
- [ ] RevenueService with split calculations
- [ ] Earning recording and balance updates
- [ ] Earnings reporting methods
- [ ] Tests for all calculations

---

### T04: PayoutService with Stripe Transfers
**Estimated Time**: 5-6 hours
**Dependencies**: T02, T03
**Artisan Commands**:
```bash
php artisan make:class Services/PayoutService --no-interaction
php artisan make:job ProcessPayoutJob --no-interaction
php artisan make:test --pest Feature/PayoutServiceTest --no-interaction
```

**Description**: Build service class handling automated payouts via Stripe Transfers API.

**Key Implementation Details**:
- `createPayout($stripeAccountId, $amount)`: Create Stripe payout/transfer
- `processPendingPayouts()`: Process all eligible accounts (min $25 balance, weekly schedule)
- `retryFailedPayout($payoutId)`: Retry failed payouts
- `getPayoutStatus($stripePayoutId)`: Check payout status via Stripe API
- Schedule weekly payouts via Laravel scheduler (every Monday)
- Handle webhook: `payout.paid`, `payout.failed`

**Deliverables**:
- [ ] PayoutService with Stripe Transfer integration
- [ ] ProcessPayoutJob for async processing
- [ ] Scheduled command for weekly payouts
- [ ] Tests for payout workflows

---

### T05: Filament Earnings Dashboard
**Estimated Time**: 5-6 hours
**Dependencies**: T02
**Artisan Commands**:
```bash
php artisan make:filament-resource Payout --generate --no-interaction
php artisan make:filament-resource Earning --generate --no-interaction
php artisan make:filament-widget EarningsOverview --no-interaction
```

**Description**: Create Filament admin resources for viewing payouts and earnings with analytics widgets.

**Key Implementation Details**:
- Use Filament v4 `->components([])` for forms
- PayoutResource: display amount, status, arrival_date, Stripe link
- EarningResource: show transaction breakdown, fees, host earnings
- EarningsOverview widget: total revenue, platform fees, host earnings
- Add filters: by status, by date range, by host
- Charts: revenue over time, top earning hosts

**Deliverables**:
- [ ] Payout and Earning resources
- [ ] EarningsOverview widget
- [ ] Revenue analytics charts
- [ ] Admin tests

---

### T06: Livewire Host Earnings Components
**Estimated Time**: 5-6 hours
**Dependencies**: T03
**Artisan Commands**:
```bash
php artisan make:livewire Earnings/EarningsDashboard --no-interaction
php artisan make:livewire Earnings/PayoutHistory --no-interaction
php artisan make:livewire Earnings/WithdrawFunds --no-interaction
```

**Description**: Build host-facing components showing earnings, payout history, and withdrawal options.

**Key Implementation Details**:
- `EarningsDashboard`: Display available balance, pending balance, earnings chart
- `PayoutHistory`: List all payouts with statuses and arrival dates
- `WithdrawFunds`: Allow hosts to trigger manual payout (if enabled)
- Show next scheduled payout date
- Display earnings breakdown by activity
- Use DaisyUI and galaxy theme styling

**Deliverables**:
- [ ] EarningsDashboard showing balances
- [ ] PayoutHistory with filtering
- [ ] WithdrawFunds component
- [ ] Tests for all components

---

### T07: Payout Tests
**Estimated Time**: 4-5 hours
**Dependencies**: T01-T06
**Artisan Commands**:
```bash
php artisan make:test --pest Feature/PayoutWorkflowTest --no-interaction
php artisan test --filter=Payout
```

**Description**: Comprehensive testing of revenue splits, earnings tracking, and payout processing.

**Key Implementation Details**:
- Test revenue split calculation (10% platform, 90% host)
- Test earning recording updates balance correctly
- Test payout creation via Stripe API
- Test weekly payout scheduling
- Test failed payout retry logic
- Mock Stripe Transfer API in tests

**Deliverables**:
- [ ] Revenue split tests
- [ ] Payout workflow tests
- [ ] Webhook tests (payout.paid, payout.failed)
- [ ] All tests passing with >80% coverage

---

## Success Criteria

### Functional Requirements
- [ ] Revenue splits calculated correctly (10% platform, 90% host)
- [ ] Host balances update in real-time with new earnings
- [ ] Automated payouts process weekly
- [ ] Hosts can view earnings dashboard
- [ ] Payout history accessible
- [ ] Failed payouts can be retried

### Technical Requirements
- [ ] Stripe Transfers API integrated
- [ ] Weekly payout schedule automated
- [ ] Balance calculations accurate
- [ ] Webhook events update payout statuses
- [ ] Tax reporting data collected

### User Experience Requirements
- [ ] Earnings dashboard clear and informative
- [ ] Payout history easy to navigate
- [ ] Balance updates reflected immediately
- [ ] Galaxy theme applied

### Performance Requirements
- [ ] Balance queries optimized
- [ ] Payout processing asynchronous
- [ ] Earnings reports generate quickly

## Dependencies

### Blocks
- **E07/F01 Analytics**: Revenue analytics depend on this data

### External Dependencies
- **E06/F01**: Payment infrastructure and transactions
- **Stripe Transfers**: Payout processing

## Technical Notes

### Laravel 12 Conventions
- Use `casts()` method in models
- Schedule payouts with Laravel Task Scheduler

### Stripe Transfer Creation
```php
// Create payout to host's bank account
$payout = \Stripe\Payout::create([
    'amount' => $amount * 100, // cents
    'currency' => 'usd',
], [
    'stripe_account' => $stripeAccountId, // Connected account
]);
```

### Revenue Split Calculation
```php
$platformFee = $amount * (config('payments.platform_fee_percentage') / 100);
$hostEarnings = $amount - $platformFee;
```

### Testing Considerations
- Mock Stripe Transfers API
- Test payout scheduling with Carbon::setTestNow()
- Run tests with: `php artisan test --filter=Payout`

---

**Feature Status**: 🔄 Ready for Implementation
**Priority**: P1
**Epic**: E06 Payments & Monetization
**Estimated Total Time**: 30-37 hours
**Dependencies**: E06/F01 complete
