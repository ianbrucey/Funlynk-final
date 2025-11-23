# F04 System Monitoring & Operations

## Feature Overview

Implement comprehensive platform health monitoring and operational tools using Laravel 12, Filament v4, and `spatie/laravel-health`. This feature provides system health dashboards, performance monitoring, error tracking, automated alerting, and security monitoring to ensure platform reliability and uptime.

**Key Architecture**: Health checks run periodically to monitor database, cache, queue, disk space. Performance metrics track response times and resource usage. Automated alerts notify admins of critical issues. Incident management tracks outages and resolutions.

## Feature Scope

### In Scope
- **System health dashboards**: Database, cache, queue, disk status
- **Performance monitoring**: Response times, memory usage, query performance
- **Error tracking**: Exception logging and analysis
- **Automated alerting**: Email/Slack notifications for critical issues
- **Security monitoring**: Failed logins, suspicious activity
- **Incident management**: Track outages, root cause analysis

### Out of Scope
- **APM tools**: New Relic/DataDog integration (Phase 2)
- **Log aggregation**: ELK stack (Phase 2)
- **Infrastructure monitoring**: Server-level metrics (use external tools)

## Tasks Breakdown

### T01: Monitoring Database Schema
**Estimated Time**: 2-3 hours
**Dependencies**: None
**Artisan Commands**:
```bash
php artisan make:migration create_system_metrics_table --no-interaction
php artisan make:migration create_incidents_table --no-interaction
```

**Description**: Create tables for storing system metrics and incident tracking.

**Key Implementation Details**:
- `system_metrics`: `id`, `metric_name`, `value`, `unit`, `metadata` (JSON), `recorded_at`
- `incidents`: `id`, `title`, `severity` (low/medium/high/critical), `status` (investigating/identified/monitoring/resolved), `description`, `resolved_at`, `created_at`
- Metrics tracked: response_time, memory_usage, cpu_usage, queue_size, error_rate

**Deliverables**:
- [ ] Metrics and incidents tables created
- [ ] Schema tests

---

### T02: SystemMetric & Incident Models
**Estimated Time**: 3-4 hours
**Dependencies**: T01
**Artisan Commands**:
```bash
composer require spatie/laravel-health
php artisan vendor:publish --tag=health-config
php artisan make:model SystemMetric --no-interaction
php artisan make:model Incident --no-interaction
```

**Description**: Install Spatie Health, create models with `casts()` for JSON and enums.

**Key Implementation Details**:
- Use `casts()` method (Laravel 12)
- Cast `metadata` as array, `severity` and `status` as enums
- Install `spatie/laravel-health` for built-in health checks
- Configure health checks in `config/health.php`

**Deliverables**:
- [ ] Spatie Health installed and configured
- [ ] SystemMetric and Incident models
- [ ] Factories for testing

---

### T03: MonitoringService with Health Checks
**Estimated Time**: 5-6 hours
**Dependencies**: T02
**Artisan Commands**:
```bash
php artisan make:class Services/MonitoringService --no-interaction
php artisan make:job RecordMetricsJob --no-interaction
php artisan make:test --pest Feature/MonitoringServiceTest --no-interaction
```

**Description**: Build service class implementing health checks and metrics collection.

**Key Implementation Details**:
- `runHealthChecks()`: Execute all registered health checks (database, cache, queue, disk)
- `recordMetric($name, $value, $unit)`: Store system metrics
- `getMetrics($name, $startDate, $endDate)`: Query historical metrics
- `detectAnomalies()`: Identify performance issues
- Use Spatie Health for: Database, Cache, Queue, Disk Space, Schedule
- Custom checks: API response time, external service availability

**Deliverables**:
- [ ] MonitoringService with health checks
- [ ] RecordMetricsJob for scheduled collection
- [ ] Anomaly detection logic
- [ ] Tests for all checks

---

### T04: Filament System Dashboard
**Estimated Time**: 6-7 hours
**Dependencies**: T02, T03
**Artisan Commands**:
```bash
php artisan make:filament-resource Incident --generate --no-interaction
php artisan make:filament-widget SystemHealthOverview --no-interaction
php artisan make:filament-widget PerformanceChart --no-interaction
```

**Description**: Create Filament admin dashboards displaying system health and performance metrics.

**Key Implementation Details**:
- Use Filament v4 widgets
- `SystemHealthOverview`: Status cards for all health checks (green/yellow/red)
- `PerformanceChart`: Line charts showing response times, memory usage over time
- IncidentResource: Create/manage incidents, track resolution
- Display recent errors from Laravel logs
- Show queue status (pending jobs, failed jobs)

**Deliverables**:
- [ ] SystemHealthOverview widget
- [ ] PerformanceChart widget
- [ ] Incident resource
- [ ] Widget tests

---

### T05: Automated Alerting System
**Estimated Time**: 5-6 hours
**Dependencies**: T03
**Artisan Commands**:
```bash
php artisan make:notification CriticalAlertNotification --no-interaction
php artisan make:job CheckSystemHealthJob --no-interaction
composer require laravel/slack-notification-channel
```

**Description**: Implement automated alerting via email and Slack for critical system issues.

**Key Implementation Details**:
- Configure alert thresholds in `config/monitoring.php`
- Alert on: health check failures, high error rate (>5%), slow response time (>2s), high memory (>80%), disk space low (<10%)
- Send to: email (admin list), Slack channel
- Implement alert cooldown (don't spam, max 1/hour per issue)
- CheckSystemHealthJob runs every 5 minutes

**Deliverables**:
- [ ] CriticalAlertNotification
- [ ] CheckSystemHealthJob with alerting logic
- [ ] Slack integration
- [ ] Alert cooldown mechanism
- [ ] Tests for alerting

---

### T06: Performance Optimization Tools
**Estimated Time**: 5-6 hours
**Dependencies**: T03
**Artisan Commands**:
```bash
php artisan make:command AnalyzePerformance --no-interaction
php artisan make:command OptimizeCache --no-interaction
```

**Description**: Build tools for analyzing and optimizing platform performance.

**Key Implementation Details**:
- `php artisan performance:analyze`: Generate performance report
- `php artisan cache:optimize-all`: Warm all caches (views, routes, config, icons)
- Identify slow queries (log queries >1s)
- Database optimization suggestions (missing indexes, etc.)
- Memory leak detection
- Recommend caching opportunities

**Deliverables**:
- [ ] AnalyzePerformance command
- [ ] OptimizeCache command
- [ ] Slow query logging
- [ ] Performance recommendations
- [ ] Tests for commands

---

### T07: Monitoring Tests
**Estimated Time**: 4-5 hours
**Dependencies**: T01-T06
**Artisan Commands**:
```bash
php artisan make:test --pest Feature/HealthCheckTest --no-interaction
php artisan make:test --pest Feature/AlertingTest --no-interaction
php artisan test --filter=Monitoring
```

**Description**: Comprehensive testing of health checks, alerting, and performance monitoring.

**Key Implementation Details**:
- Test all health checks pass/fail correctly
- Test metric recording and retrieval
- Test alert triggering on thresholds
- Test alert cooldown prevents spam
- Test incident creation and tracking
- Mock external services in tests

**Deliverables**:
- [ ] Health check tests
- [ ] Alerting tests
- [ ] Performance monitoring tests
- [ ] All tests passing with >80% coverage

---

## Success Criteria

### Functional Requirements
- [ ] System health dashboard displays all checks
- [ ] Performance metrics tracked and visualized
- [ ] Automated alerts sent for critical issues
- [ ] Incidents can be created and tracked
- [ ] Error logging functional
- [ ] Security events monitored

### Technical Requirements
- [ ] Health checks run every 5 minutes
- [ ] Metrics recorded every minute
- [ ] Alerts trigger on threshold breaches
- [ ] Alert cooldown prevents spam
- [ ] Logs stored for 30 days

### User Experience Requirements
- [ ] Dashboard loads quickly
- [ ] Health status clear at a glance
- [ ] Alerts actionable and informative
- [ ] Incident tracking organized

### Performance Requirements
- [ ] Health checks complete <5 seconds
- [ ] Metrics queries optimized
- [ ] Dashboard widgets cached
- [ ] Minimal monitoring overhead

## Dependencies

### External Dependencies
- **spatie/laravel-health**: Health check framework
- **laravel/slack-notification-channel**: Slack alerts

## Technical Notes

### Laravel 12 Conventions
- Use `casts()` method in models
- Schedule health checks with Task Scheduler

### Spatie Health Configuration
```php
// config/health.php
return [
    'checks' => [
        Checks\DatabaseCheck::new(),
        Checks\CacheCheck::new(),
        Checks\QueueCheck::new(),
        Checks\UsedDiskSpaceCheck::new()->failWhenUsedSpaceIsAbovePercentage(90),
        Checks\ScheduleCheck::new(),
    ],
];
```

### Alert Threshold Example
```php
// config/monitoring.php
return [
    'thresholds' => [
        'error_rate' => 5, // %
        'response_time' => 2000, // ms
        'memory_usage' => 80, // %
        'disk_space' => 10, // % remaining
    ],
    'alert_cooldown' => 3600, // 1 hour
];
```

### Health Check Scheduling
```php
// In routes/console.php or App\Console\Kernel
Schedule::command('health:check')->everyFiveMinutes();
Schedule::job(new RecordMetricsJob)->everyMinute();
```

### Testing Considerations
- Mock external dependencies
- Test all alert scenarios
- Run tests with: `php artisan test --filter=Monitoring`

---

**Feature Status**: 🔄 Ready for Implementation
**Priority**: P0
**Epic**: E07 Administration
**Estimated Total Time**: 30-37 hours
**Dependencies**: None (foundational feature)
