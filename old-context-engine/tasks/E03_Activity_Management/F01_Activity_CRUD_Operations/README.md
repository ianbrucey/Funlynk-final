# F01 Activity CRUD Operations

## Feature Overview

Provide comprehensive CRUD for activities (events) with image upload, templates, status workflow, and post-to-event conversion. Built with Laravel 12, PostGIS, and Filament v4. Builds on E01 `activities` table and `ActivityResource`.

**Key Architecture**: Post-to-Event conversion: E04 detects traction and calls E03 to create an activity, tracked via `originated_from_post_id`.

## Feature Scope

### In Scope
- **Activity creation/editing** with all fields
- **Image upload** via Laravel filesystem
- **Templates** for common activity types
- **Status workflow** (draft → published → active → completed → cancelled)
- **Post-origin link**: `originated_from_post_id`

### Out of Scope
- Discovery ranking (E04)
- Payments (E06)

## Tasks Breakdown

### T01: Enhance ActivityResource Form & Table
**Estimated Time**: 4-5 hours
**Dependencies**: None
**Artisan Commands**:
```bash
php artisan make:filament-resource Activity --generate --no-interaction # if missing
```
**Description**: Add fields for title, description, images, time window, capacity, pricing, location (PostGIS), and status. Configure filters/actions.
**Key Implementation Details**:
- Forms use `->components([])` (Filament v4)
- Spatial cast via `casts()` to `Point::class`
- Relationship managers for RSVPs/Tags
**Deliverables**:
- [ ] Updated form/table with filters
- [ ] Validation rules aligned

---

### T02: Activity Image Upload
**Estimated Time**: 3-4 hours
**Dependencies**: T01
**Artisan Commands**:
```bash
php artisan storage:link --no-interaction
php artisan make:test --pest Feature/ActivityImageUploadTest --no-interaction
```
**Description**: Implement storage for activity images with previews.
**Deliverables**:
- [ ] FileUpload fields configured
- [ ] Passing upload test

---

### T03: Post-to-Event Conversion Service
**Estimated Time**: 5-6 hours
**Dependencies**: T01
**Artisan Commands**:
```bash
php artisan make:class Services/ActivityConversionService --no-interaction
php artisan make:job ProcessActivityConversion --no-interaction
php artisan make:test --pest Feature/PostToEventConversionTest --no-interaction
```
**Description**: Create activity from a `Post`, set `originated_from_post_id`, copy location/time hints, and notify host to finalize.
**Key Implementation Details**:
- Called by E04 conversion flow
- Idempotency when called multiple times
- Record linkage in `post_conversions`
**Deliverables**:
- [ ] Service + job
- [ ] Tests covering conversion flow

---

### T04: Activity Templates
**Estimated Time**: 4-5 hours
**Dependencies**: T01
**Artisan Commands**:
```bash
php artisan make:livewire Activity/ActivityTemplates --no-interaction
php artisan make:test --pest Feature/ActivityTemplatesTest --no-interaction
```
**Description**: Quick-create templates (e.g., Pickup Basketball, Jam Session) with sensible defaults.
**Deliverables**:
- [ ] Livewire UI to select template
- [ ] Template presets persisted

---

### T05: Status Workflow & Policies
**Estimated Time**: 4-5 hours
**Dependencies**: T01
**Artisan Commands**:
```bash
php artisan make:policy ActivityPolicy --model=Activity --no-interaction
php artisan make:test --pest Feature/ActivityStatusWorkflowTest --no-interaction
```
**Description**: Transitions: draft → published → active → completed/cancelled.
**Deliverables**:
- [ ] Policy rules for who can publish/cancel
- [ ] Transition methods and guards

---

### T06: Editing Components
**Estimated Time**: 6-7 hours
**Dependencies**: T01
**Artisan Commands**:
```bash
php artisan make:livewire Activity/EditActivity --no-interaction
```
**Description**: Livewire UI for rich editing states and autosave.
**Deliverables**:
- [ ] Component with validation and feedback

---

### T07: Tests & Indexes
**Estimated Time**: 3-4 hours
**Dependencies**: T01–T06
**Artisan Commands**:
```bash
php artisan make:test --pest Feature/ActivityCrudTest --no-interaction
```
**Description**: Ensure indexes for common queries; cover happy/failure paths.
**Deliverables**:
- [ ] Passing feature tests
- [ ] Index review complete

## Success Criteria

### Database & Models
- [ ] `originated_from_post_id` tracked
- [ ] Spatial cast configured

### Filament Resources
- [ ] Complete forms/tables with filters/actions

### Business Logic & Services
- [ ] Conversion service robust and idempotent
- [ ] Status transitions validated

### User Experience
- [ ] Creation < 2 minutes; edits instant

### Integration
- [ ] E04 can trigger conversion
- [ ] E01/E02 models wired

## Dependencies

### Blocks
- **E04 Discovery**: Triggers conversion and links back to posts

### External Dependencies
- **E01 Core**: Activities, Posts, PostConversions tables

## Technical Notes

### Laravel 12
- Use `casts()`; configure in `bootstrap/app.php`

### Filament v4
- Use `->components([])` and relationship managers

### Testing
- Pest v4; run focused tests

---

**Feature Status**: 🔄 Ready for Implementation
**Priority**: P1
**Epic**: E03 Activity Management
**Estimated Total Time**: 35-42 hours
**Dependencies**: E01/E04 ready
