# E07 Administration - Laravel Documentation Guide

## Epic Context
**Purpose**: Platform management, analytics, moderation, and system monitoring for administrators.
**Reference**: `context-engine/epics/E07_Administration/epic-overview.md`

**CRITICAL**: This is Filament-heavy. Most features are admin dashboards and management tools.

## Features to Document (4 total)

### F01: Platform Analytics & Business Intelligence
**Purpose**: Comprehensive analytics dashboards for platform metrics and KPIs
**Key Components**:
- Executive dashboard with key metrics
- User analytics (acquisition, engagement, retention)
- Activity analytics (posts vs events performance)
- Revenue analytics and financial reporting
- Geographic and demographic insights
- Custom report builder

**E01 Integration**:
- Queries all existing tables for analytics
- May need `analytics_events` table for tracking custom events
- Uses Filament widgets for dashboards

**Suggested Tasks (6-7 tasks, 35-45 hours)**:
- T01: Analytics Database Schema (2-3h)
- T02: AnalyticsService with Metrics Calculation (6-7h)
- T03: Filament Dashboard Widgets (7-8h)
- T04: Custom Report Builder (6-7h)
- T05: Analytics Export Functionality (4-5h)
- T06: Analytics Caching Strategy (4-5h)
- T07: Analytics Tests (4-5h)

**Key Packages**:
- `filament/filament` widgets for dashboards
- `spatie/laravel-analytics` for Google Analytics integration (optional)
- `maatwebsite/excel` for report exports

---

### F02: Content Moderation & Safety
**Purpose**: Automated and manual content moderation, policy enforcement, safety monitoring
**Key Components**:
- Automated content filtering (profanity, spam)
- Manual review queues
- Report management system
- Policy enforcement workflows
- User safety scoring
- Automated actions (warnings, suspensions, bans)

**E01 Integration**:
- Uses `reports` table (reportable_type, reportable_id, reason, status)
- May need `moderation_actions` table (user_id, action_type, reason, expires_at)
- May need `safety_scores` table (user_id, score, factors)
- Uses `Report` model and `ReportResource`

**Suggested Tasks (6-7 tasks, 30-40 hours)**:
- T01: Moderation Database Schema (3-4h)
- T02: ModerationAction & SafetyScore Models (3-4h)
- T03: ModerationService with Auto-filtering (6-7h)
- T04: Filament Moderation Dashboard (6-7h)
- T05: Report Review Workflow (5-6h)
- T06: Automated Action Jobs (4-5h)
- T07: Moderation Tests (4-5h)

**Key Packages**:
- `voku/anti-xss` for content sanitization
- `stevebauman/purify` for HTML purification
- Custom profanity filter or API integration

---

### F03: User & Community Management
**Purpose**: User administration, support tickets, verification, dispute resolution
**Key Components**:
- User management dashboard
- Support ticket system
- User verification and identity management
- Account actions (warnings, suspensions, bans)
- Appeals process
- Community health monitoring

**E01 Integration**:
- Uses `users` table with admin fields (is_verified, is_suspended, suspension_reason)
- May need `support_tickets` table (user_id, subject, status, priority)
- May need `user_actions` table (user_id, action_type, reason, performed_by)
- May need `appeals` table (user_id, action_id, reason, status)

**Suggested Tasks (6-7 tasks, 30-40 hours)**:
- T01: User Management Database Schema (3-4h)
- T02: SupportTicket & Appeal Models (3-4h)
- T03: UserManagementService (5-6h)
- T04: Filament User Management Dashboard (6-7h)
- T05: Support Ticket System (5-6h)
- T06: Appeals Workflow (4-5h)
- T07: User Management Tests (4-5h)

**Key Packages**:
- Filament's built-in user management features
- `spatie/laravel-permission` for admin roles (if not already using)

---

### F04: System Monitoring & Operations
**Purpose**: Platform health monitoring, performance optimization, incident management
**Key Components**:
- System health dashboards
- Performance monitoring
- Error tracking and logging
- Automated alerting
- Security monitoring
- Cost analysis and optimization

**E01 Integration**:
- May need `system_metrics` table (metric_name, value, recorded_at)
- May need `incidents` table (title, severity, status, resolved_at)
- Uses Laravel's logging system
- Uses queue monitoring

**Suggested Tasks (6-7 tasks, 30-40 hours)**:
- T01: Monitoring Database Schema (2-3h)
- T02: SystemMetric & Incident Models (3-4h)
- T03: MonitoringService with Health Checks (5-6h)
- T04: Filament System Dashboard (6-7h)
- T05: Automated Alerting System (5-6h)
- T06: Performance Optimization Tools (5-6h)
- T07: Monitoring Tests (4-5h)

**Key Packages**:
- `spatie/laravel-health` for health checks
- `spatie/laravel-backup` for backup monitoring
- `beyondcode/laravel-er-diagram-generator` for database visualization
- Laravel Horizon for queue monitoring
- Laravel Telescope for debugging (dev only)

---

## Common Patterns Across All Features

### Database Migrations
```bash
php artisan make:migration create_analytics_events_table --no-interaction
php artisan make:migration create_moderation_actions_table --no-interaction
php artisan make:migration create_support_tickets_table --no-interaction
php artisan make:migration create_system_metrics_table --no-interaction
```

### Models
```bash
php artisan make:model AnalyticsEvent --no-interaction
php artisan make:model ModerationAction --no-interaction
php artisan make:model SupportTicket --no-interaction
php artisan make:model SystemMetric --no-interaction
```

### Service Classes
```bash
php artisan make:class Services/AnalyticsService --no-interaction
php artisan make:class Services/ModerationService --no-interaction
php artisan make:class Services/UserManagementService --no-interaction
php artisan make:class Services/MonitoringService --no-interaction
```

### Filament Resources & Widgets
```bash
php artisan make:filament-resource SupportTicket --generate --no-interaction
php artisan make:filament-resource ModerationAction --generate --no-interaction
php artisan make:filament-widget StatsOverview --no-interaction
php artisan make:filament-widget UserGrowthChart --no-interaction
```

### Jobs (Scheduled Commands)
```bash
php artisan make:job CalculateDailyMetricsJob --no-interaction
php artisan make:job ProcessModerationQueueJob --no-interaction
php artisan make:job SendHealthCheckAlertsJob --no-interaction
```

### Tests
```bash
php artisan make:test --pest Feature/AnalyticsDashboardTest --no-interaction
php artisan make:test --pest Feature/ModerationWorkflowTest --no-interaction
php artisan make:test --pest Feature/SupportTicketTest --no-interaction
php artisan make:test --pest Feature/SystemMonitoringTest --no-interaction
```

---

## Testing Checklist

### F01: Platform Analytics & Business Intelligence
- [ ] Dashboard displays accurate metrics
- [ ] Custom reports generate correctly
- [ ] Analytics exports work
- [ ] Caching improves performance
- [ ] Date range filtering works

### F02: Content Moderation & Safety
- [ ] Automated filtering catches violations
- [ ] Manual review queue works
- [ ] Reports processed correctly
- [ ] Automated actions enforced
- [ ] Safety scores calculated accurately

### F03: User & Community Management
- [ ] Can manage user accounts
- [ ] Support tickets created and resolved
- [ ] User actions logged correctly
- [ ] Appeals process works
- [ ] Verification system functional

### F04: System Monitoring & Operations
- [ ] Health checks run successfully
- [ ] Performance metrics tracked
- [ ] Alerts sent for critical issues
- [ ] Incident management works
- [ ] Queue monitoring functional

