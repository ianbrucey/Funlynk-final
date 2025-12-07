# F03 Tagging & Category System

## Feature Overview

Provide comprehensive tagging and categorization for activities to enable filtering, discovery, and analytics. Built with Laravel 12, Livewire, and Filament v4. Uses E01 `tags` and `activity_tag` tables and `TagResource`.

## Feature Scope

### In Scope
- **Tag management** with usage analytics
- **Autocomplete** Livewire component
- **Category hierarchy** and moderation
- **Trending tags** with caching
- **Tag-based discovery** integration

### Out of Scope
- Feed ranking (E04)
- Comments/social features (E05)

## Tasks Breakdown

### T01: Enhance TagResource with Analytics
**Estimated Time**: 3-4 hours
**Dependencies**: None
**Artisan Commands**:
```bash
php artisan make:filament-resource Tag --generate --no-interaction # if missing
php artisan make:test --pest Feature/TagResourceTest --no-interaction
```
**Description**: Add columns for usage_count, category; filters, bulk actions.
**Deliverables**:
- [ ] Resource shows analytics and supports moderation
- [ ] Tests for CRUD and filters

---

### T02: Tag Autocomplete Component
**Estimated Time**: 4-5 hours
**Dependencies**: None
**Artisan Commands**:
```bash
php artisan make:livewire Tags/TagAutocomplete --no-interaction
php artisan make:test --pest Feature/TagAutocompleteTest --no-interaction
```
**Description**: Suggest tags as users type; create new with moderation rules.
**Deliverables**:
- [ ] Component with suggestions and selection chips
- [ ] Tests for suggestion relevance

---

### T03: Category Hierarchy
**Estimated Time**: 3-4 hours
**Dependencies**: None
**Artisan Commands**:
```bash
php artisan make:migration add_category_fields_to_tags_table --no-interaction
php artisan make:test --pest Feature/TagCategoryTest --no-interaction
```
**Description**: Support categories (e.g., sports/food/music) and optional parent-child hierarchy.
**Deliverables**:
- [ ] Migration and model casts
- [ ] Filters by category in resources

---

### T04: Trending Tags Analytics
**Estimated Time**: 4-5 hours
**Dependencies**: T01
**Artisan Commands**:
```bash
php artisan make:job UpdateTagUsageCount --no-interaction
php artisan make:test --pest Feature/TrendingTagsTest --no-interaction
```
**Description**: Update usage_count on activity create/edit; compute trending windowed counts.
**Deliverables**:
- [ ] Job hooked to events
- [ ] Cached trending list

---

### T05: Tag Management Livewire Tools
**Estimated Time**: 4-5 hours
**Dependencies**: T01
**Artisan Commands**:
```bash
php artisan make:livewire Tags/ManageTags --no-interaction
```
**Description**: Admin tools to merge/suppress synonyms; moderation actions.
**Deliverables**:
- [ ] Merge/suppress flows
- [ ] Audit trail

---

### T06: Policies & Tests
**Estimated Time**: 3-4 hours
**Dependencies**: T01
**Artisan Commands**:
```bash
php artisan make:policy TagPolicy --model=Tag --no-interaction
php artisan make:test --pest Feature/TagPolicyTest --no-interaction
```
**Description**: Authorization for tag creation/moderation.
**Deliverables**:
- [ ] Policy wired and enforced
- [ ] Passing tests

## Success Criteria

### Database & Models
- [ ] Tags have categories and usage tracking

### Filament Resources
- [ ] Resource shows analytics and filters by category

### Business Logic & Services
- [ ] Trending list updated and cached

### User Experience
- [ ] Autocomplete fast and relevant

### Integration
- [ ] E04 can filter by tags for discovery

## Dependencies

### Blocks
- **E04 Discovery**: Needs tag analytics for filters

### External Dependencies
- **E01 Core**: Tags and pivot tables

## Technical Notes

### Laravel 12
- Use `casts()` where appropriate
- Configure in `bootstrap/app.php`

### Filament v4
- `->components([])`, relationship managers

### Testing
- Pest v4; `RefreshDatabase`

---

**Feature Status**: 🔄 Ready for Implementation
**Priority**: P2
**Epic**: E03 Activity Management
**Estimated Total Time**: 25-33 hours
**Dependencies**: E01 foundation
