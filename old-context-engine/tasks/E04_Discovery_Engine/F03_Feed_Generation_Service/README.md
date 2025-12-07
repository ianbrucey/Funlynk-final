# F03 Social Resonance & Post Evolution (formerly "Feed Generation")

## Feature Overview

Track post engagement ("I'm down", "Join me", "Interested") and trigger post-to-event conversion when posts gain traction. E04 detects conversion, E03 creates the activity and links via `originated_from_post_id`. Uses E01 `post_reactions`, `post_conversions`, `posts`, `activities`.

## Feature Scope

### In Scope
- **Reaction tracking** and counters
- **Conversion detection** thresholds and scoring
- **Conversion trigger** calling E03 `ActivityConversionService`
- **Conversion analytics** persisted to `post_conversions`
- **Notifications** to creators and engaged users

### Out of Scope
- Event CRUD after conversion (E03)
- Feed ranking (F01/F02)

## Tasks Breakdown

### T01: SocialResonanceService
**Estimated Time**: 4-5 hours
**Dependencies**: None
**Artisan Commands**:
```bash
php artisan make:class Services/SocialResonanceService --no-interaction
php artisan make:test --pest Unit/SocialResonanceServiceTest --no-interaction
```
**Description**: Track reactions per post; update counters; expose engagement metrics.
**Deliverables**:
- [ ] Service with methods to record reactions
- [ ] Counter updates and tests

---

### T02: ConversionDetectionService
**Estimated Time**: 5-6 hours
**Dependencies**: T01
**Artisan Commands**:
```bash
php artisan make:class Services/ConversionDetectionService --no-interaction
php artisan make:job DetectPostConversions --no-interaction
php artisan make:test --pest Feature/ConversionDetectionTest --no-interaction
```
**Description**: Evaluate thresholds (e.g., 5+ "I'm down" in 2h suggest; 10+ in 4h auto-prompt). Compute `conversion_score`.
**Deliverables**:
- [ ] Service + job with schedule
- [ ] Tests for thresholds

---

### T03: Post-to-Event Conversion Trigger
**Estimated Time**: 4-5 hours
**Dependencies**: T01, T02
**Artisan Commands**:
```bash
php artisan make:test --pest Feature/PostConversionTriggerTest --no-interaction
```
**Description**: Call `app(ActivityConversionService::class)->createFromPost($post)`; persist `PostConversion` with score and trigger reason; notify creator.
**Deliverables**:
- [ ] Trigger wired with retries/idempotency
- [ ] PostConversion record created

---

### T04: Reaction Livewire Components
**Estimated Time**: 5-6 hours
**Dependencies**: T01
**Artisan Commands**:
```bash
php artisan make:livewire Post/PostReactions --no-interaction
php artisan make:test --pest Feature/PostReactionsUiTest --no-interaction
```
**Description**: Buttons for reactions with loading/disabled states and optimistic UI.
**Deliverables**:
- [ ] Component with counts and state
- [ ] Tests for reaction flows

---

### T05: Conversion Notifications
**Estimated Time**: 3-4 hours
**Dependencies**: T03
**Artisan Commands**:
```bash
php artisan make:job DetectConversionOpportunities --no-interaction
php artisan make:test --pest Feature/ConversionNotificationsTest --no-interaction
```
**Description**: Notify post creator and engaged users when conversion suggested/created.
**Deliverables**:
- [ ] Jobs integrated; notifications dispatched
- [ ] Tests verifying notifications

---

### T06: Analytics & Dashboards
**Estimated Time**: 4-5 hours
**Dependencies**: T01–T05
**Artisan Commands**:
```bash
php artisan make:test --pest Feature/ConversionAnalyticsTest --no-interaction
```
**Description**: Basic analytics on conversion rates and time-to-conversion; Filament widgets.
**Deliverables**:
- [ ] Metrics and cached summaries

---

### T07: Policies & Guardrails
**Estimated Time**: 3-4 hours
**Dependencies**: T01–T04
**Artisan Commands**:
```bash
php artisan make:policy PostPolicy --model=Post --no-interaction
```
**Description**: Ensure only authorized users can trigger or approve conversion prompts.
**Deliverables**:
- [ ] Policy rules; tests

## Success Criteria

### Database & Models
- [ ] Reactions tracked; counters consistent
- [ ] PostConversion records created with scores

### Filament Resources
- [ ] Admin can view conversion analytics widgets

### Business Logic & Services
- [ ] Detection thresholds fire reliably
- [ ] Conversion trigger calls E03 and persists linkage

### User Experience
- [ ] Reaction UI responsive and clear
- [ ] Helpful prompts for conversion

### Integration
- [ ] E03 activity created with `originated_from_post_id`
- [ ] Notifications via E01

## Dependencies

### Blocks
- **E03 Activity Management**: Creates the activity

### External Dependencies
- **E01 Core**: Posts, PostReactions, PostConversions, Activities

## Technical Notes

### Laravel 12
- Use `casts()`; configure in `bootstrap/app.php`

### Filament v4
- Use `->components([])`; widgets for analytics

### PostGIS
- Use radii logic consistent with feeds (F01)

### Testing
- Pest v4 + `RefreshDatabase`

---

**Feature Status**: 🔄 Ready for Implementation
**Priority**: P1
**Epic**: E04 Discovery Engine
**Estimated Total Time**: 30-40 hours
**Dependencies**: E01/E03 ready
