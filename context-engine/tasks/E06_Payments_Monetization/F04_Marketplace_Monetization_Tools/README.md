# F04 Marketplace & Monetization Tools

## Feature Overview

Enable advanced pricing strategies and promotional tools using Laravel 12 and Filament v4. This feature provides dynamic pricing, coupon codes, early bird discounts, group pricing, and sponsored activity placement to maximize host revenue and platform growth.

**Key Architecture**: Pricing rules cascade (base price → early bird → coupon → group discount). Sponsored listings boost activity visibility in discovery feeds using bid-based ranking. All pricing calculations happen server-side for security.

## Feature Scope

### In Scope
- **Dynamic pricing**: Time-based and demand-based pricing
- **Coupon system**: Percentage and fixed-amount discounts
- **Early bird pricing**: Time-limited discounts for early RSVPs
- **Group discounts**: Discounts for multiple RSVPs
- **Sponsored listings**: Paid activity promotion in feeds
- **Pricing analytics**: Revenue optimization insights

### Out of Scope
- **Auction pricing**: Fixed pricing only
- **Dynamic currency conversion**: USD only Phase 1

## Tasks Breakdown

### T01: Coupon & Promotion Database Schema
**Estimated Time**: 3-4 hours
**Dependencies**: E06/F01 complete
**Artisan Commands**:
```bash
php artisan make:migration create_coupons_table --no-interaction
php artisan make:migration create_coupon_usages_table --no-interaction
php artisan make:migration create_sponsored_listings_table --no-interaction
php artisan make:migration add_pricing_fields_to_activities_table --no-interaction
```

**Description**: Create tables for coupons, usage tracking, sponsored listings, and add pricing rule fields to activities.

**Key Implementation Details**:
- `coupons`: `id`, `code`, `discount_type` (percentage/fixed), `discount_value`, `min_purchase`, `max_uses`, `uses_count`, `expires_at`
- `coupon_usages`: `id`, `coupon_id`, `user_id`, `activity_id`, `rsvp_id`, `discount_amount`, `created_at`
- `sponsored_listings`: `id`, `activity_id`, `bid_amount`, `budget`, `spent`, `impressions`, `clicks`, `start_date`, `end_date`, `status`
- Add to `activities`: `early_bird_price`, `early_bird_ends_at`, `group_discount_threshold`, `group_discount_percentage`

**Deliverables**:
- [ ] Coupon tables created
- [ ] Sponsored listings table created
- [ ] Pricing fields added to activities
- [ ] Schema tests

---

### T02: Coupon & Pricing Models
**Estimated Time**: 3-4 hours
**Dependencies**: T01
**Artisan Commands**:
```bash
php artisan make:model Coupon --no-interaction
php artisan make:model CouponUsage --no-interaction
php artisan make:model SponsoredListing --no-interaction
php artisan make:factory CouponFactory --model=Coupon --no-interaction
```

**Description**: Create models with relationships and implement `casts()` for enums and dates.

**Key Implementation Details**:
- Use `casts()` method (Laravel 12)
- Cast `discount_type` and `status` as enums
- Cast dates: `expires_at`, `early_bird_ends_at`, `start_date`, `end_date`
- Relationships: `Coupon hasMany CouponUsages`, `SponsoredListing belongsTo Activity`
- Helper methods: `isValid()`, `isExpired()`, `hasUsesRemaining()`, `canUse($user)`

**Deliverables**:
- [ ] Coupon, CouponUsage, SponsoredListing models
- [ ] Updated Activity model with pricing fields
- [ ] Validation helpers
- [ ] Factories

---

### T03: PricingService with Calculation Logic
**Estimated Time**: 5-6 hours
**Dependencies**: T02
**Artisan Commands**:
```bash
php artisan make:class Services/PricingService --no-interaction
php artisan make:test --pest Feature/PricingServiceTest --no-interaction
```

**Description**: Build service class calculating final prices with all discounts applied in correct order.

**Key Implementation Details**:
- `calculatePrice($activity, $user, $quantity = 1, $couponCode = null)`: Calculate final price
- Pricing cascade: base → early bird → coupon → group discount
- Early bird: if `now() < early_bird_ends_at`, use `early_bird_price`
- Group discount: if `$quantity >= group_discount_threshold`, apply percentage discount
- Coupon: validate and apply code
- Return breakdown: base, early_bird_savings, coupon_savings, group_savings, final_price

**Deliverables**:
- [ ] PricingService with cascade logic
- [ ] Price breakdown calculation
- [ ] Coupon validation
- [ ] Tests for all pricing scenarios

---

### T04: CouponService with Validation
**Estimated Time**: 4-5 hours
**Dependencies**: T02, T03
**Artisan Commands**:
```bash
php artisan make:class Services/CouponService --no-interaction
php artisan make:test --pest Feature/CouponServiceTest --no-interaction
```

**Description**: Build service class handling coupon validation, redemption, and usage tracking.

**Key Implementation Details**:
- `validateCoupon($code, $user, $activity)`: Check if coupon is valid
- `applyCoupon($coupon, $rsvp)`: Record usage, update uses_count
- `generateCode()`: Create unique coupon codes
- Validation rules: not expired, has uses remaining, meets min_purchase, user hasn't used (if single-use)
- Track redemption rate per coupon

**Deliverables**:
- [ ] CouponService with validation
- [ ] Usage tracking
- [ ] Code generation
- [ ] Tests for validation rules

---

### T05: Filament Coupon Management
**Estimated Time**: 4-5 hours
**Dependencies**: T02
**Artisan Commands**:
```bash
php artisan make:filament-resource Coupon --generate --no-interaction
php artisan make:filament-resource SponsoredListing --generate --no-interaction
php artisan make:filament-widget CouponMetrics --no-interaction
```

**Description**: Create Filament admin resources for managing coupons and sponsored listings.

**Key Implementation Details**:
- Use Filament v4 `->components([])` for forms
- CouponResource: create/edit coupons, view usage stats, deactivate
- SponsoredListingResource: manage sponsored activities, view performance
- CouponMetrics widget: redemption rate, revenue impact, top coupons
- Add filters: by status (active/expired), by discount type

**Deliverables**:
- [ ] Coupon resource with usage tracking
- [ ] SponsoredListing resource with analytics
- [ ] CouponMetrics widget
- [ ] Admin tests

---

### T06: Livewire Pricing & Coupon Components
**Estimated Time**: 5-6 hours
**Dependencies**: T03, T04
**Artisan Commands**:
```bash
php artisan make:livewire Pricing/PriceBreakdown --no-interaction
php artisan make:livewire Pricing/CouponInput --no-interaction
php artisan make:livewire Pricing/GroupDiscountIndicator --no-interaction
```

**Description**: Build user-facing components showing price breakdowns, coupon application, and discount indicators.

**Key Implementation Details**:
- `PriceBreakdown`: Display price with savings breakdown
- `CouponInput`: Input field with real-time validation
- `GroupDiscountIndicator`: Show "Bring X friends, save Y%"
- Display early bird countdown timer
- Show applied discounts in checkout
- Use DaisyUI and galaxy theme styling

**Deliverables**:
- [ ] PriceBreakdown component
- [ ] CouponInput with validation
- [ ] GroupDiscountIndicator
- [ ] Tests for all components

---

### T07: Pricing & Coupon Tests
**Estimated Time**: 4-5 hours
**Dependencies**: T01-T06
**Artisan Commands**:
```bash
php artisan make:test --pest Feature/PricingCalculationTest --no-interaction
php artisan make:test --pest Feature/CouponRedemptionTest --no-interaction
php artisan test --filter=Pricing
```

**Description**: Comprehensive testing of pricing calculations, coupon validation, and discount stacking.

**Key Implementation Details**:
- Test pricing cascade (all discounts applied correctly)
- Test coupon validation (expired, max uses, min purchase)
- Test group discount thresholds
- Test early bird expiration
- Test coupon redemption tracking
- Test edge cases (negative prices, 100% discounts)

**Deliverables**:
- [ ] Pricing calculation tests
- [ ] Coupon validation tests
- [ ] Discount stacking tests
- [ ] All tests passing with >80% coverage

---

## Success Criteria

### Functional Requirements
- [ ] Coupons can be created and redeemed
- [ ] Early bird pricing works with time limits
- [ ] Group discounts apply at threshold
- [ ] Price breakdowns display correctly
- [ ] Sponsored listings boost visibility
- [ ] Usage tracking accurate

### Technical Requirements
- [ ] Pricing calculations accurate
- [ ] Coupon validation prevents fraud
- [ ] Discount stacking follows rules
- [ ] Usage limits enforced
- [ ] Sponsored listing bids tracked

### User Experience Requirements
- [ ] Price breakdowns clear
- [ ] Coupon input intuitive
- [ ] Savings highlighted prominently
- [ ] Galaxy theme applied

### Performance Requirements
- [ ] Pricing calculations fast
- [ ] Coupon lookups optimized
- [ ] Sponsored listing queries efficient

## Dependencies

### External Dependencies
- **E06/F01**: Payment infrastructure
- **E04 Discovery**: Sponsored listings affect ranking

## Technical Notes

### Laravel 12 Conventions
- Use `casts()` method in models
- Validate pricing server-side only

### Pricing Calculation
```php
// Calculate final price with all discounts
$basePrice = $activity->price;
$price = $basePrice;

// Early bird
if (now() < $activity->early_bird_ends_at) {
    $price = $activity->early_bird_price;
}

// Coupon
if ($coupon && $coupon->isValid()) {
    $price -= $coupon->discount_type === 'percentage' 
        ? ($price * $coupon->discount_value / 100)
        : $coupon->discount_value;
}

// Group discount
if ($quantity >= $activity->group_discount_threshold) {
    $price -= ($price * $activity->group_discount_percentage / 100);
}

return max($price, 0); // Never negative
```

### Testing Considerations
- Test all discount combinations
- Test expiration edge cases
- Run tests with: `php artisan test --filter=Pricing`

---

**Feature Status**: 🔄 Ready for Implementation
**Priority**: P2
**Epic**: E06 Payments & Monetization
**Estimated Total Time**: 29-36 hours
**Dependencies**: E06/F01 complete
