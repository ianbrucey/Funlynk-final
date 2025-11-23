# F02 Privacy Settings

## Feature Overview

Provide granular privacy controls for profile visibility, discovery, and notifications using Laravel 12 policies, global scopes, and Filament v4 forms. Builds on E01 `users` + `follows` tables and E02/F01 profile foundation.

## Feature Scope

### In Scope
- **Privacy columns** on `users` (e.g., `is_profile_public`, `show_location_level`, `dm_permissions`)
- **Policies & scopes** enforcing visibility at query level
- **Filament settings UI** for admins and user-facing Livewire form
- **Blocked users management** and enforcement hooks
- **Notification preferences** (respect E01 notifications)

### Out of Scope
- Comments/messaging (E05)
- Payments (E06)
- Feed ranking (E04)

## Tasks Breakdown

### T01: Migration for Privacy Columns
**Estimated Time**: 2-3 hours
**Dependencies**: None
**Artisan Commands**:
```bash
php artisan make:migration add_privacy_columns_to_users_table --no-interaction
php artisan make:test --pest Feature/PrivacyMigrationTest --no-interaction
```
**Description**: Add fields like `is_profile_public` (bool, default true), `show_location_level` (enum: exact, city, hidden), `dm_permissions` (enum), `notification_prefs` (json).
**Key Implementation Details**:
- Include all attributes when modifying tables (Laravel 12)
- Backfill sensible defaults
- Add indexes where appropriate
**Deliverables**:
- [ ] Migration created and ran
- [ ] Tests covering defaults and schema

---

### T02: ProfilePolicy & Authorization
**Estimated Time**: 3-4 hours
**Dependencies**: T01
**Artisan Commands**:
```bash
php artisan make:policy ProfilePolicy --model=User --no-interaction
php artisan make:test --pest Feature/ProfilePolicyTest --no-interaction
```
**Description**: Enforce privacy in `view`, `update`, and related abilities.
**Key Implementation Details**:
- Respect blocks and mutual follows
- Deny precise location when `hidden` or `city` only
- Centralize checks to avoid duplication
**Deliverables**:
- [ ] Policy with comprehensive rules
- [ ] Passing policy tests

---

### T03: Global Scopes & Query Filters
**Estimated Time**: 3-4 hours
**Dependencies**: T01
**Artisan Commands**:
```bash
php artisan make:class Services/PrivacyService --no-interaction
```
**Description**: Apply visibility at query layer to avoid leaking private data.
**Key Implementation Details**:
- Add local scopes on `User` (e.g., `publicProfiles()`)
- Apply conditional select/hiding of sensitive fields
- Integrate with discovery queries (E02/F03, E04 feeds)
**Deliverables**:
- [ ] Scopes/services wired
- [ ] Unit tests for scope behavior

---

### T04: Filament Admin Privacy Settings
**Estimated Time**: 3-4 hours
**Dependencies**: T01
**Artisan Commands**:
```bash
php artisan make:filament-resource PrivacySetting --generate --no-interaction
```
**Description**: Provide admin-level management of privacy defaults and overrides.
**Key Implementation Details**:
- Use `->components([])` with Toggles/Selects
- Audit changes (simple activity log)
**Deliverables**:
- [ ] Resource for reviewing privacy fields
- [ ] Filters/actions for moderation

---

### T05: User-Facing Livewire Privacy Form
**Estimated Time**: 3-4 hours
**Dependencies**: T01, T02
**Artisan Commands**:
```bash
php artisan make:livewire Profile/PrivacySettings --no-interaction
php artisan make:test --pest Feature/PrivacySettingsUiTest --no-interaction
```
**Description**: Users configure privacy and notification preferences.
**Key Implementation Details**:
- Validate preferences; persist JSON fields
- Show location granularity options
- DaisyUI toggles for consistent design
**Deliverables**:
- [ ] Livewire form with validation
- [ ] Tests for updates and enforcement

---

### T06: Blocked Users Management
**Estimated Time**: 4-5 hours
**Dependencies**: T02, T03
**Artisan Commands**:
```bash
php artisan make:model BlockedUser --no-interaction
php artisan make:migration create_blocked_users_table --no-interaction
php artisan make:test --pest Feature/BlockedUsersTest --no-interaction
```
**Description**: Pivot table and enforcement hooks preventing access/interactions.
**Key Implementation Details**:
- Unique constraint on blocker/blockee
- Policy checks integrated into queries
**Deliverables**:
- [ ] Table, model, relations
- [ ] Enforcement in policies/scopes
- [ ] Tests for blocked behavior

---

### T07: Notifications Preferences Service
**Estimated Time**: 3-4 hours
**Dependencies**: T01
**Artisan Commands**:
```bash
php artisan make:class Services/NotificationPreferencesService --no-interaction
```
**Description**: Centralize logic for opt-in/out and channels.
**Deliverables**:
- [ ] Service with tests
- [ ] Integration points for E01 notifications

## Success Criteria

### Database & Models
- [ ] Privacy fields added with defaults
- [ ] Blocked users pivot enforced

### Filament Resources
- [ ] Admin resource with filters/actions
- [ ] Forms use v4 patterns

### Business Logic & Services
- [ ] Policies/scopes enforce privacy reliably
- [ ] Notification prefs respected

### User Experience
- [ ] Clear privacy choices and help text
- [ ] Changes immediately reflected

### Integration
- [ ] Discovery (E02/F03, E04) respects privacy
- [ ] Activities (E03) respect blocked users

## Dependencies

### Blocks
- **E04 Discovery**: Needs privacy-aware queries
- **E03 Activity**: RSVP visibility depends on profile privacy

### External Dependencies
- **E01 Core**: Users, notifications

## Technical Notes

### Laravel 12
- Use `casts()` for JSON
- Configure in `bootstrap/app.php`

### Filament v4
- `->components([])` and `relationship()` fields

### Testing
- Pest v4 + `RefreshDatabase`

---

**Feature Status**: 🔄 Ready for Implementation
**Priority**: P1
**Epic**: E02 User & Profile Management
**Estimated Total Time**: 26-34 hours
**Dependencies**: E01 foundation
