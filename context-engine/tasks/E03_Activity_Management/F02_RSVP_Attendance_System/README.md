# F02 RSVP & Attendance System

## Feature Overview

Manage RSVPs, capacity, waitlists, payment tracking fields, and attendance verification for activities (events). Built with Laravel 12 services, jobs, policies, and Filament v4. Uses E01 `rsvps`, `activities` tables and `RsvpResource`.

## Feature Scope

### In Scope
- **RSVP CRUD** with status/payment fields
- **Capacity & waitlist** management
- **Attendance check-in** (QR/location/manual)
- **Notifications** via E01 system
- **Livewire UI** for RSVP and host tools

### Out of Scope
- Payment processing (E06)
- Discovery/ranking (E04)

## Tasks Breakdown

### T01: Enhance RsvpResource
**Estimated Time**: 3-4 hours
**Dependencies**: None
**Artisan Commands**:
```bash
php artisan make:filament-resource Rsvp --generate --no-interaction # if missing
php artisan make:test --pest Feature/RsvpResourceTest --no-interaction
```
**Description**: Ensure status, payment_status, payment_amount, attended fields; filters and actions.
**Deliverables**:
- [ ] Complete forms/tables with filters
- [ ] Tests for basic CRUD

---

### T02: CapacityService & Waitlist
**Estimated Time**: 4-5 hours
**Dependencies**: T01
**Artisan Commands**:
```bash
php artisan make:class Services/CapacityService --no-interaction
php artisan make:test --pest Feature/CapacityManagementTest --no-interaction
```
**Description**: Enforce activity capacity; manage waitlist when full and automatic promotion on spots opening.
**Key Implementation Details**:
- Optimistic locking or DB transactions to avoid race conditions
- Track counts via efficient queries
**Deliverables**:
- [ ] Service with `canRsvp`, `reserve`, `promoteFromWaitlist`
- [ ] Tests for race and edge cases

---

### T03: RsvpService & Policies
**Estimated Time**: 4-5 hours
**Dependencies**: T01
**Artisan Commands**:
```bash
php artisan make:class Services/RsvpService --no-interaction
php artisan make:policy RsvpPolicy --model=Rsvp --no-interaction
php artisan make:test --pest Feature/RsvpPolicyTest --no-interaction
```
**Description**: Centralize create/update/cancel logic; enforce who can RSVP and manage others.
**Deliverables**:
- [ ] Service + policy
- [ ] Tests for permissions and flows

---

### T04: Attendance Check-in
**Estimated Time**: 5-6 hours
**Dependencies**: T01
**Artisan Commands**:
```bash
php artisan make:livewire Rsvp/AttendanceCheckIn --no-interaction
php artisan make:test --pest Feature/AttendanceCheckInTest --no-interaction
```
**Description**: Check-in via QR code, GPS match, or manual host action; mark `attended=true`.
**Deliverables**:
- [ ] Livewire check-in component
- [ ] Mark attendance and audit

---

### T05: RSVP Notifications
**Estimated Time**: 3-4 hours
**Dependencies**: T02, T03
**Artisan Commands**:
```bash
php artisan make:job SendRsvpNotifications --no-interaction
```
**Description**: Notify hosts/participants on status changes and promotions.
**Deliverables**:
- [ ] Job wired into service events
- [ ] Tests asserting notifications dispatched

---

### T06: RSVP UI Components
**Estimated Time**: 5-6 hours
**Dependencies**: T02, T03
**Artisan Commands**:
```bash
php artisan make:livewire Rsvp/RsvpButton --no-interaction
php artisan make:test --pest Feature/RsvpUiTest --no-interaction
```
**Description**: Button + stateful UI for joining/leaving/waitlist with DaisyUI loading/disabled states.
**Deliverables**:
- [ ] Component with optimistic UI
- [ ] Tests for state transitions

---

### T07: Analytics & Reporting (Basic)
**Estimated Time**: 3-4 hours
**Dependencies**: T01–T06
**Artisan Commands**:
```bash
php artisan make:test --pest Feature/RsvpAnalyticsTest --no-interaction
```
**Description**: Basic counts and rates for hosts; expose in Filament widgets.
**Deliverables**:
- [ ] Simple metrics and widgets
- [ ] Tests for metrics

## Success Criteria

### Database & Models
- [ ] RSVP status/payment fields respected
- [ ] Attendance set correctly

### Filament Resources
- [ ] RsvpResource fully functional
- [ ] Host widgets show key metrics

### Business Logic & Services
- [ ] Capacity/waitlist rules correct under load
- [ ] Notifications dispatched on changes

### User Experience
- [ ] Check-in reliable; responsive UI
- [ ] Clear errors and disabled states

### Integration
- [ ] Activity capacity synced with RSVPs
- [ ] E01 notifications consumed

## Dependencies

### Blocks
- **E06 Payments**: Future payment capture

### External Dependencies
- **E01 Core**: Activities, RSVPs tables, notifications

## Technical Notes

### Laravel 12
- Transactions for multi-step RSVP flows
- Configure in `bootstrap/app.php`

### Filament v4
- `->components([])` and relationship managers

### Testing
- Pest v4; `RefreshDatabase`

---

**Feature Status**: 🔄 Ready for Implementation
**Priority**: P1
**Epic**: E03 Activity Management
**Estimated Total Time**: 30-38 hours
**Dependencies**: E01 foundation
