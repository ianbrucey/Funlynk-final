# Laravel Task Documentation Template

## Purpose
This template provides the exact structure and format for rebuilding task-level documentation for E02, E03, and E04 to align with Laravel 12 + Filament v4 architecture.

## Reference Example
See `context-engine/tasks/E01_Core_Infrastructure/F01_Database_Foundation/README.md` for a complete example.

---

# [Feature ID] [Feature Name]

## Feature Overview

[Brief description of the feature's purpose - 2-3 sentences]

This feature [implements/provides/enables] [core functionality] using Laravel 12, Filament v4, and [relevant packages]. It [builds on/integrates with] E01's completed database foundation, specifically the [table names] tables and [Model names] models.

**Key Architecture**: [If relevant, mention Posts vs Events dual model context]

## Feature Scope

### In Scope
- **[Component 1]**: [Description using Laravel/Filament terminology]
- **[Component 2]**: [Description using Laravel/Filament terminology]
- **[Component 3]**: [Description using Laravel/Filament terminology]
- **[Component 4]**: [Description using Laravel/Filament terminology]

### Out of Scope
- **[Feature]**: [Reason - handled by which epic]
- **[Feature]**: [Reason - handled by which epic]
- **[Feature]**: [Reason - Phase 2 or future enhancement]

## Tasks Breakdown

### T01: [Task Name]
**Estimated Time**: [X-Y] hours
**Dependencies**: [None or list task IDs]
**Artisan Commands**:
```bash
# [Specific Laravel/Filament commands]
php artisan make:filament-resource [ResourceName] --generate --no-interaction
php artisan make:model [ModelName] --no-interaction
php artisan make:policy [PolicyName] --no-interaction
php artisan make:livewire [ComponentName] --no-interaction
```

**Description**: [Detailed description of what needs to be built - 3-5 sentences. Be specific about Laravel components, Filament resources, Livewire components, service classes, etc.]

**Key Implementation Details**:
- [Specific Laravel 12 pattern or convention]
- [Specific Filament v4 pattern or convention]
- [Integration with E01 components]
- [Database tables/models involved]

**Deliverables**:
- [ ] [Specific file or component created]
- [ ] [Specific functionality implemented]
- [ ] [Specific integration completed]
- [ ] [Tests written and passing]

---

### T02: [Task Name]
**Estimated Time**: [X-Y] hours
**Dependencies**: [List task IDs]
**Artisan Commands**:
```bash
# [Specific commands]
```

**Description**: [Detailed description]

**Deliverables**:
- [ ] [Deliverable 1]
- [ ] [Deliverable 2]

---

[Repeat T03-T07 following same structure]

---

## Success Criteria

### Database & Models
- [ ] [Specific criterion related to database/models]
- [ ] [Specific criterion related to relationships]
- [ ] [Specific criterion related to data integrity]

### Filament Resources
- [ ] [Specific criterion for Filament resources]
- [ ] [Specific criterion for forms/tables]
- [ ] [Specific criterion for actions/filters]

### Business Logic & Services
- [ ] [Specific criterion for service classes]
- [ ] [Specific criterion for business logic]
- [ ] [Specific criterion for validation]

### User Experience
- [ ] [Specific criterion for UX]
- [ ] [Specific criterion for performance]
- [ ] [Specific criterion for accessibility]

### Integration
- [ ] [Specific criterion for E01 integration]
- [ ] [Specific criterion for other epic integration]
- [ ] [Specific criterion for third-party integration]

## Dependencies

### Blocks
- **[Epic/Feature]**: [Description of what depends on this feature]
- **[Epic/Feature]**: [Description of what depends on this feature]

### External Dependencies
- **E01 Core Infrastructure**: [Specific tables, models, services required]
- **[Package Name]**: [Purpose and usage]
- **[External Service]**: [Purpose and integration point]

## Technical Notes

### Laravel 12 Conventions
- Use `casts()` method instead of `$casts` property in models
- Use `php artisan make:` commands with `--no-interaction` flag
- Middleware and exception handling configured in `bootstrap/app.php`
- Commands auto-register from `app/Console/Commands/`

### Filament v4 Conventions
- Use `->components([])` instead of `->schema([])` in form methods
- Use `relationship()` method for relationship fields
- File visibility defaults to `private`
- `deferFilters` is default (use `deferFilters(false)` to disable)

### PostGIS Integration (if applicable)
- Use `matanyadaev/laravel-eloquent-spatial` for spatial data types
- Define columns as `$table->geography('location_coordinates', 'point', 4326)`
- Cast spatial columns to `Point::class` in models
- Use spatial query scopes: `whereDistance('location_coordinates', $point, '<=', $radius)`

### Testing Considerations
- Use Pest v4 for all tests
- Use `RefreshDatabase` trait in feature tests
- Create factories for all models
- Test all happy paths, failure paths, and edge cases
- Run tests with: `php artisan test --filter=[TestName]`

### Performance Optimization
- Eager load relationships to prevent N+1 queries
- Use query builder for complex aggregations
- Limit eager loading: `$query->latest()->limit(10)`
- Use database transactions for multi-step operations
- Cache frequently accessed data (Redis)

---

**Feature Status**: 🔄 Ready for Implementation
**Priority**: [P0/P1/P2]
**Epic**: [E0X Epic Name]
**Estimated Total Time**: [XX-YY] hours
**Dependencies**: [List epic dependencies]

---

## CRITICAL RULES FOR DOCUMENTATION

1. **NO React Native references**: No mentions of React Native, Expo, React Navigation, TypeScript interfaces
2. **NO Supabase references**: No mentions of Supabase RPC, Supabase Storage, Supabase Auth
3. **Laravel 12 focus**: All code examples and commands must be Laravel 12
4. **Filament v4 focus**: All admin UI references must be Filament v4
5. **E01 references**: Always reference E01's completed implementation (tables, models, resources)
6. **Realistic estimates**: Tasks should be 2-8 hours each, features should be 25-40 hours total
7. **Specific commands**: Always include exact Artisan commands with flags
8. **Clear deliverables**: Each task must have measurable, specific deliverables

## TASK NAMING CONVENTIONS

- T01: Usually database/model setup or core foundation
- T02: Usually service classes or business logic
- T03: Usually Filament resources or admin UI
- T04: Usually Livewire components or frontend
- T05: Usually policies, permissions, or security
- T06: Usually testing, optimization, or analytics
- T07: Usually integration or advanced features (optional)

## TIME ESTIMATE GUIDELINES

- Simple Filament resource enhancement: 2-3 hours
- Service class with business logic: 3-4 hours
- Complex Livewire component: 4-5 hours
- Policy implementation with tests: 2-3 hours
- Integration with external service: 4-6 hours
- Performance optimization: 3-4 hours
- Comprehensive testing: 2-3 hours

Total per feature: 25-40 hours (5-7 tasks)

