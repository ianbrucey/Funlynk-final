# F02 Content Moderation & Safety

## Feature Overview

Implement automated and manual content moderation using Laravel 12 and Filament v4. This feature protects users through profanity filtering, spam detection, report management, and policy enforcement. Builds on E01's `reports` table to create comprehensive safety workflows.

**Key Architecture**: Automated filters scan content in real-time. Manual review queues allow admins to process reports. User safety scores track behavior. Automated actions (warnings, suspensions, bans) enforce community guidelines.

## Feature Scope

### In Scope
- **Automated content filtering**: Profanity, spam, malicious links
- **Manual review queues**: Process user reports efficiently
- **Report management**: Track and resolve reported content
- **Policy enforcement**: Automated warnings, suspensions, bans
- **User safety scoring**: Behavioral risk assessment
- **Moderation analytics**: Effectiveness metrics

### Out of Scope
- **AI content moderation**: Rule-based only in Phase 1
- **Image/video moderation**: Text only initially

## Tasks Breakdown

### T01: Moderation Database Schema
**Estimated Time**: 3-4 hours
**Dependencies**: E01 `reports` table exists
**Artisan Commands**:
```bash
php artisan make:migration create_moderation_actions_table --no-interaction
php artisan make:migration create_safety_scores_table --no-interaction
php artisan make:migration add_moderation_fields_to_users_table --no-interaction
```

**Description**: Create tables for moderation actions, safety scores, and add moderation fields to users table.

**Key Implementation Details**:
- `moderation_actions`: `id`, `user_id`, `action_type` (warning/suspension/ban), `reason`, `performed_by`, `expires_at`, `created_at`
- `safety_scores`: `id`, `user_id`, `score` (0-100), `factors` (JSON), `last_calculated_at`
- Add to `users`: `is_suspended`, `is_banned`, `suspension_reason`, `suspension_expires_at`
- E01 `reports` table already exists with: reportable_type, reportable_id, reason, status

**Deliverables**:
- [ ] Moderation tables created
- [ ] User moderation fields added
- [ ] Schema tests

---

### T02: ModerationAction & SafetyScore Models
**Estimated Time**: 3-4 hours
**Dependencies**: T01
**Artisan Commands**:
```bash
php artisan make:model ModerationAction --no-interaction
php artisan make:model SafetyScore --no-interaction
php artisan make:factory ModerationActionFactory --model=ModerationAction --no-interaction
```

**Description**: Create models with relationships and implement `casts()` for JSON and enums.

**Key Implementation Details**:
- Use `casts()` method (Laravel 12)
- Cast `action_type` as enum, `factors` as array
- Relationships: `ModerationAction belongsTo User`, `SafetyScore belongsTo User`
- Update E01 `Report` model (already exists)
- Helper methods: `isSuspended()`, `isBanned()`, `canPost()`

**Deliverables**:
- [ ] ModerationAction and SafetyScore models
- [ ] Updated User model with moderation helpers
- [ ] Factories for testing

---

### T03: ModerationService with Auto-filtering
**Estimated Time**: 6-7 hours
**Dependencies**: T02
**Artisan Commands**:
```bash
php artisan make:class Services/ModerationService --no-interaction
php artisan make:job ProcessReportJob --no-interaction
php artisan make:test --pest Feature/ModerationServiceTest --no-interaction
```

**Description**: Build service class handling automated content filtering and policy enforcement.

**Key Implementation Details**:
- `filterContent($text)`: Check profanity, spam patterns, malicious links
- `processReport($report)`: Review and take action on reports
- `warnUser($user, $reason)`: Issue warning, track in moderation_actions
- `suspendUser($user, $days, $reason)`: Temporary suspension
- `banUser($user, $reason)`: Permanent ban
- Profanity filter: blacklist-based (configurable in `config/moderation.php`)
- Spam detection: duplicate content, link spam, rapid posting

**Deliverables**:
- [ ] ModerationService with filtering logic
- [ ] Automated action enforcement
- [ ] ProcessReportJob for async processing
- [ ] Tests for all moderation actions

---

### T04: Filament Moderation Dashboard
**Estimated Time**: 6-7 hours
**Dependencies**: T02
**Artisan Commands**:
```bash
php artisan make:filament-resource Report --generate --no-interaction
php artisan make:filament-resource ModerationAction --generate --no-interaction
php artisan make:filament-widget ModerationQueue --no-interaction
```

**Description**: Create Filament admin resources for processing reports and managing moderation actions.

**Key Implementation Details**:
- Use Filament v4 `->components([])` for forms
- ReportResource: view reports, take actions (ignore, warn, suspend, ban), bulk processing
- ModerationActionResource: view moderation history, reverse actions
- ModerationQueue widget: pending reports count, avg response time
- Add filters: by status (pending/resolved), by type (spam/abuse/harassment)
- Custom actions: Quick Ban, Quick Warn, Resolve & Notify

**Deliverables**:
- [ ] Report resource with action buttons
- [ ] ModerationAction resource
- [ ] ModerationQueue widget
- [ ] Bulk action support
- [ ] Admin tests

---

### T05: Report Review Workflow
**Estimated Time**: 5-6 hours
**Dependencies**: T03, T04
**Artisan Commands**:
```bash
php artisan make:livewire Moderation/ReportDetails --no-interaction
php artisan make:notification ReportResolvedNotification --no-interaction
```

**Description**: Build complete workflow for reviewing and resolving reports with user notifications.

**Key Implementation Details**:
- Display full context: reported content, reporter, reported user
- Action buttons: Ignore, Warn, Suspend (1d/7d/30d), Ban
- Add notes to report resolution
- Notify reporter when action taken
- Notify reported user of action and reason
- Track resolution time metrics

**Deliverables**:
- [ ] ReportDetails component for review
- [ ] Action workflow with notifications
- [ ] Resolution tracking
- [ ] Tests for workflow

---

### T06: Automated Action Jobs
**Estimated Time**: 4-5 hours
**Dependencies**: T03
**Artisan Commands**:
```bash
php artisan make:job CalculateSafetyScoreJob --no-interaction
php artisan make:job ExpireSuspensionJob --no-interaction
php artisan make:command CheckModeration --no-interaction
```

**Description**: Implement background jobs for calculating safety scores, expiring suspensions, and proactive monitoring.

**Key Implementation Details**:
- `CalculateSafetyScoreJob`: Calculate user safety score based on reports, violations, engagement
- `ExpireSuspensionJob`: Automatically lift expired suspensions
- Factors: report count, violation severity, account age, activity level
- Score 0-100: <30 = high risk, 30-70 = medium, >70 = low risk
- Schedule jobs: safety scores daily, suspension checks hourly

**Deliverables**:
- [ ] CalculateSafetyScoreJob
- [ ] ExpireSuspensionJob
- [ ] Scheduled commands
- [ ] Tests for automation

---

### T07: Moderation Tests
**Estimated Time**: 4-5 hours
**Dependencies**: T01-T06
**Artisan Commands**:
```bash
php artisan make:test --pest Feature/ContentFilteringTest --no-interaction
php artisan make:test --pest Feature/ModerationWorkflowTest --no-interaction
php artisan test --filter=Moderation
```

**Description**: Comprehensive testing of content filtering, report workflows, and automated actions.

**Key Implementation Details**:
- Test profanity filter catches violations
- Test spam detection rules
- Test suspension expiration
- Test safety score calculation
- Test moderation action enforcement
- Test report resolution workflow

**Deliverables**:
- [ ] Content filtering tests
- [ ] Workflow tests
- [ ] Safety score tests
- [ ] All tests passing with >80% coverage

---

## Success Criteria

### Functional Requirements
- [ ] Automated filters catch profanity and spam
- [ ] Reports can be reviewed and resolved
- [ ] Moderation actions enforced (warnings, suspensions, bans)
- [ ] Safety scores calculated for all users
- [ ] Suspended users automatically reinstated when suspension expires
- [ ] Users notified of moderation actions

### Technical Requirements
- [ ] Content filtering happens in real-time
- [ ] Report processing queued
- [ ] Safety scores updated daily
- [ ] Suspension checks run hourly
- [ ] Moderation actions logged

### User Experience Requirements
- [ ] Report submission easy and clear
- [ ] Moderation dashboard efficient for admins
- [ ] Action notifications informative
- [ ] False positives minimal

### Performance Requirements
- [ ] Content filtering <100ms
- [ ] Report queue processing efficient
- [ ] Safety score calculation optimized
- [ ] Dashboard queries cached

## Dependencies

### External Dependencies
- **E01 Core Infrastructure**: `reports` table
- **voku/anti-xss**: Content sanitization (optional)
- **stevebauman/purify**: HTML purification (optional)

## Technical Notes

### Laravel 12 Conventions
- Use `casts()` method in models
- Queue heavy processing

### Content Filtering Example
```php
// Check for profanity
$blacklist = config('moderation.profanity_blacklist');
foreach ($blacklist as $word) {
    if (str_contains(strtolower($text), strtolower($word))) {
        return false;
    }
}
```

### Safety Score Calculation
```php
$score = 100;
$score -= $user->reports_received_count * 5; // -5 per report
$score -= $user->moderation_actions()->where('action_type', 'warning')->count() * 10;
$score -= $user->moderation_actions()->where('action_type', 'suspension')->count() * 20;
return max(0, min(100, $score));
```

### Testing Considerations
- Test edge cases (empty content, special characters)
- Mock notifications
- Run tests with: `php artisan test --filter=Moderation`

---

**Feature Status**: 🔄 Ready for Implementation
**Priority**: P0
**Epic**: E07 Administration
**Estimated Total Time**: 31-38 hours
**Dependencies**: E01 reports table available
