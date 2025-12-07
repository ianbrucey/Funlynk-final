# F01 Profile Creation & Management

## Feature Overview

Enable rich user profiles with images, interests, and location, built with Laravel 12, Postgres + PostGIS, and Filament v4. Builds on E01 tables (`users`, `follows`) and the `User` model and `UserResource`.

**Key Architecture**: Profiles power discovery for both Posts (E04) and Events (E03).

## Feature Scope

### In Scope
- **User profile data**: Bio, interests (JSON), location (PostGIS), profile image
- **Filament admin**: Enhance `UserResource` for profile management
- **Profile completion**: Service + Livewire indicators
- **Interest management**: JSON list editor with suggestions
- **Location picker**: Save to `users.location_coordinates`

### Out of Scope
- **Auth**: E01 handles authentication
- **Posts/Events CRUD**: E03/E04 handle content features
- **Payments**: E06 handles monetization

## Tasks Breakdown

### T01: Enhance Filament UserResource for Profile Fields
**Estimated Time**: 3-4 hours
**Dependencies**: None
**Artisan Commands**:
```bash
php artisan make:filament-resource User --generate --no-interaction # if missing
```
**Description**: Add/organize fields for bio, interests (JSON), `location_name`, `location_coordinates`, and `profile_image_url` using Filament v4 components and relationship helpers.
**Key Implementation Details**:
- Use `->components([])` in forms (Filament v4)
- Use `FileUpload` for profile image (Laravel filesystem)
- Cast spatial columns via `casts()` in `User`
- Leverage E01 `UserResource` if present; extend rather than duplicate
**Deliverables**:
- [ ] Updated `UserResource` form/table
- [ ] Image upload working with storage disk
- [ ] Validation rules aligned with DB
- [ ] Basic tests for resource screens

---

### T02: Profile Image Upload via Filesystem
**Estimated Time**: 4-5 hours
**Dependencies**: T01
**Artisan Commands**:
```bash
php artisan storage:link --no-interaction
php artisan make:test --pest Feature/ProfileImageUploadTest --no-interaction
```
**Description**: Implement secure upload, storage, and retrieval of profile images using Laravel filesystem and public URLs. No Supabase.
**Key Implementation Details**:
- Configure `config/filesystems.php` disk
- Sanitize/validate images; optional resizing
- Store path in `users.profile_image_url`
- Show preview in Filament and Livewire UI
**Deliverables**:
- [ ] Upload field and working URLs
- [ ] Validation and size/type limits
- [ ] Passing feature test

---

### T03: Profile Completion Tracking
**Estimated Time**: 3-4 hours
**Dependencies**: T01
**Artisan Commands**:
```bash
php artisan make:class Services/ProfileCompletionService --no-interaction
php artisan make:livewire Profile/ProfileCompletion --no-interaction
```
**Description**: Compute completion score based on filled fields; show progress indicators.
**Key Implementation Details**:
- Service returns percentage and missing steps
- Livewire shows progress; cache per user
- Hook into profile updates to refresh
**Deliverables**:
- [ ] Service class with tests
- [ ] Livewire component visible on profile page
- [ ] Cached computation for performance

---

### T04: Interest Management Component
**Estimated Time**: 4-5 hours
**Dependencies**: T01
**Artisan Commands**:
```bash
php artisan make:livewire Profile/InterestEditor --no-interaction
php artisan make:test --pest Feature/InterestEditorTest --no-interaction
```
**Description**: Build tag-like editor for `users.interests` JSON with suggestions and validation.
**Key Implementation Details**:
- Validate max count and length; normalize case/slug
- Persist as JSON array on `users`
- Use DaisyUI chips for UI consistency
**Deliverables**:
- [ ] Livewire editor with add/remove
- [ ] JSON persisted and validated
- [ ] Tests for validation and persistence

---

### T05: Location Picker with PostGIS
**Estimated Time**: 5-6 hours
**Dependencies**: T01
**Artisan Commands**:
```bash
php artisan make:test --pest Feature/ProfileLocationTest --no-interaction
```
**Description**: Save geocoded location to `users.location_coordinates` (GEOGRAPHY Point, 4326) and `location_name`.
**Key Implementation Details**:
- Use `matanyadaev/laravel-eloquent-spatial`
- `User::casts()` => `location_coordinates` to `Point::class`
- Provide simple lat/lng inputs or map picker
**Deliverables**:
- [ ] Location UI and validation
- [ ] Spatial cast + save works
- [ ] Passing feature test

---

### T06: Policies & Privacy Hooks
**Estimated Time**: 3-4 hours
**Dependencies**: T01
**Artisan Commands**:
```bash
php artisan make:policy ProfilePolicy --model=User --no-interaction
```
**Description**: Basic visibility rules to prepare for E02/F02 privacy. Apply authorization to profile views/edits.
**Key Implementation Details**:
- Gate checks in controllers/Livewire actions
- Respect blocked users (if available)
**Deliverables**:
- [ ] Policy with rules
- [ ] Authorization integrated
- [ ] Tests covering allowed/denied

---

### T07: UI Polishing & Tests
**Estimated Time**: 3-4 hours
**Dependencies**: T01–T06
**Artisan Commands**:
```bash
php artisan make:test --pest Feature/ProfileUiTest --no-interaction
```
**Description**: Polish forms, validation, and states; ensure DaisyUI styling and glass morphism patterns.
**Deliverables**:
- [ ] Consistent styling per UI standards
- [ ] Edge cases tested (empty, long text)
- [ ] Pint formatting clean

## Success Criteria

### Database & Models
- [ ] Spatial `Point` cast via `casts()`
- [ ] Interests JSON validated and persisted
- [ ] Image URL stored and retrievable

### Filament Resources
- [ ] UserResource shows all profile fields
- [ ] Forms use v4 `->components([])`
- [ ] Tables expose useful filters

### Business Logic & Services
- [ ] Completion service accurate and tested
- [ ] Image handling secure and validated

### User Experience
- [ ] Smooth editing with Livewire feedback
- [ ] Clear completion progress
- [ ] Mobile-friendly forms

### Integration
- [ ] Compatible with E04 discovery inputs
- [ ] Respects E02 privacy (F02)

## Dependencies

### Blocks
- **E04 Discovery**: Needs interests/location for feeds
- **E03 Activities**: Host credibility from profiles

### External Dependencies
- **E01 Core**: `users` table, User model, UserResource
- **matanyadaev/laravel-eloquent-spatial**: spatial types

## Technical Notes

### Laravel 12 Conventions
- Use `casts()` in models (not `$casts`)
- Configure middleware/exceptions in `bootstrap/app.php`
- Use `php artisan make:` with `--no-interaction`

### Filament v4 Conventions
- Use `->components([])` for forms
- Use `relationship()` for selects

### Testing
- Pest v4; use `RefreshDatabase`
- Run focused tests: `php artisan test --filter=Profile`

---

**Feature Status**: 🔄 Ready for Implementation
**Priority**: P1
**Epic**: E02 User & Profile Management
**Estimated Total Time**: 27-32 hours
**Dependencies**: E01 foundation ready
