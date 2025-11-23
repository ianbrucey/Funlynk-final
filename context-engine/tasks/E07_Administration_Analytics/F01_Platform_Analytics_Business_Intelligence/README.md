# F01 Platform Analytics & Business Intelligence

## Feature Overview

Build comprehensive analytics dashboards for platform administrators using Laravel 12, Filament v4 widgets, and `maatwebsite/excel`. This feature provides real-time insights into user growth, activity engagement, revenue metrics, and platform health through interactive dashboards and custom report generation.

**Key Architecture**: Filament widgets display KPI cards and charts on admin dashboard. Analytics queries aggregate data from all tables. Report builder allows custom date ranges and export to Excel/CSV. Caching improves performance for expensive queries.

## Feature Scope

### In Scope
- **Executive dashboard**: Key metrics (MAU, revenue, growth rate)
- **User analytics**: Acquisition, engagement, retention cohorts
- **Activity analytics**: Post vs event performance, popular categories
- **Revenue analytics**: MRR, transaction volume, platform fees
- **Geographic insights**: Activity heatmaps, user distribution
- **Custom report builder**: Date ranges, filters, Excel export

### Out of Scope
- **Predictive analytics**: ML-based forecasting (Phase 2)
- **Real-time streaming**: Batch analytics only
- **External BI tools**: Standalone Filament dashboards only

## Tasks Breakdown

### T01: Analytics Database Schema
**Estimated Time**: 2-3 hours
**Dependencies**: None
**Artisan Commands**:
```bash
php artisan make:migration create_analytics_events_table --no-interaction
php artisan make:migration create_daily_metrics_table --no-interaction
```

**Description**: Create tables for tracking custom analytics events and aggregated daily metrics.

**Key Implementation Details**:
- `analytics_events`: `id`, `event_type`, `user_id`, `properties` (JSON), `created_at`
- `daily_metrics`: `id`, `date`, `metric_name`, `value`, `metadata` (JSON)
- Track events: page_view, activity_view, rsvp_created, payment_completed
- Aggregate daily: DAU, new_users, new_activities, revenue

**Deliverables**:
- [ ] Analytics tables created
- [ ] Event tracking schema
- [ ] Daily aggregation schema
- [ ] Schema tests

---

### T02: AnalyticsService with Metrics Calculation
**Estimated Time**: 6-7 hours
**Dependencies**: T01
**Artisan Commands**:
```bash
php artisan make:class Services/AnalyticsService --no-interaction
php artisan make:job CalculateDailyMetricsJob --no-interaction
php artisan make:test --pest Feature/AnalyticsServiceTest --no-interaction
```

**Description**: Build service class calculating KPIs, growth rates, retention cohorts, and trends.

**Key Implementation Details**:
- `getMAU()`: Monthly Active Users (distinct users in last 30 days)
- `getGrowthRate($metric, $period)`: Calculate MoM or WoW growth
- `getRetentionCohort($cohortMonth)`: User retention by signup cohort
- `getRevenueMetrics()`: MRR, average transaction value, LTV
- `getTopActivities($limit)`: Most popular activities by RSVPs
- Cache results for 1 hour (expensive queries)

**Deliverables**:
- [ ] AnalyticsService with KPI calculations
- [ ] CalculateDailyMetricsJob for aggregation
- [ ] Retention cohort analysis
- [ ] Tests for all metrics

---

### T03: Filament Dashboard Widgets
**Estimated Time**: 7-8 hours
**Dependencies**: T02
**Artisan Commands**:
```bash
php artisan make:filament-widget StatsOverview --no-interaction
php artisan make:filament-widget UserGrowthChart --no-interaction
php artisan make:filament-widget RevenueChart --no-interaction
php artisan make:filament-widget ActivityHeatmap --no-interaction
```

**Description**: Create Filament widgets displaying analytics on admin dashboard.

**Key Implementation Details**:
- Use Filament v4 widget classes
- `StatsOverview`: KPI cards (MAU, revenue, growth %)
- `UserGrowthChart`: Line chart showing user acquisition over time
- `RevenueChart`: Bar chart showing daily/weekly/monthly revenue
- `ActivityHeatmap`: Geographic distribution of activities
- Add date range filters to widgets
- Cache widget data (15 minute TTL)

**Deliverables**:
- [ ] Stats overview widget with KPIs
- [ ] User growth chart widget
- [ ] Revenue chart widget
- [ ] Activity heatmap widget
- [ ] Widget tests

---

### T04: Custom Report Builder
**Estimated Time**: 6-7 hours
**Dependencies**: T02
**Artisan Commands**:
```bash
php artisan make:filament-page CustomReports --no-interaction
composer require maatwebsite/excel
php artisan vendor:publish --provider="Maatwebsite\Excel\ExcelServiceProvider"
```

**Description**: Build custom report interface allowing admins to generate and export reports.

**Key Implementation Details**:
- Filament custom page with form: select metrics, date range, filters
- Available reports: User Acquisition, Activity Performance, Revenue Breakdown, Engagement
- Export formats: Excel, CSV
- Use `maatwebsite/excel` for export
- Queue large report generation
- Email download link when ready

**Deliverables**:
- [ ] CustomReports Filament page
- [ ] Report generation logic
- [ ] Excel/CSV export
- [ ] Queued report processing
- [ ] Tests for report generation

---

### T05: Analytics Export Functionality
**Estimated Time**: 4-5 hours
**Dependencies**: T04
**Artisan Commands**:
```bash
php artisan make:export UsersExport --model=User --no-interaction
php artisan make:export ActivitiesExport --model=Activity --no-interaction
php artisan make:job GenerateReportJob --no-interaction
```

**Description**: Implement data export functionality for all major entities with filtering.

**Key Implementation Details**:
- Create Export classes for Users, Activities, Transactions, RSVPs
- Support filtering by date range, status, type
- Chunk large exports to prevent memory issues
- Generate download URL stored temporarily
- Clean up old exports (7 day retention)

**Deliverables**:
- [ ] Export classes for all entities
- [ ] GenerateReportJob for async processing
- [ ] Download URL generation
- [ ] Cleanup job for old exports
- [ ] Export tests

---

### T06: Analytics Caching Strategy
**Estimated Time**: 4-5 hours
**Dependencies**: T02, T03
**Artisan Commands**:
```bash
php artisan make:command CacheAnalytics --no-interaction
```

**Description**: Implement intelligent caching for analytics queries to improve dashboard performance.

**Key Implementation Details**:
- Cache daily metrics: 24 hour TTL
- Cache widgets: 15 minute TTL
- Cache expensive queries (retention cohorts): 1 hour TTL
- Invalidate cache on new data (transactions, users, activities)
- Use Redis for caching
- Create `php artisan analytics:cache` command to warm cache

**Deliverables**:
- [ ] Cache strategy implemented
- [ ] Cache invalidation on updates
- [ ] CacheAnalytics command
- [ ] Performance benchmarks
- [ ] Cache tests

---

### T07: Analytics Tests
**Estimated Time**: 4-5 hours
**Dependencies**: T01-T06
**Artisan Commands**:
```bash
php artisan make:test --pest Feature/AnalyticsDashboardTest --no-interaction
php artisan make:test --pest Feature/ReportGenerationTest --no-interaction
php artisan test --filter=Analytics
```

**Description**: Comprehensive testing of analytics calculations, widget displays, and report generation.

**Key Implementation Details**:
- Test MAU calculation accuracy
- Test growth rate calculations
- Test retention cohort logic
- Test widget data rendering
- Test report export formats
- Test cache invalidation

**Deliverables**:
- [ ] Analytics calculation tests
- [ ] Widget rendering tests
- [ ] Report generation tests
- [ ] All tests passing with >80% coverage

---

## Success Criteria

### Functional Requirements
- [ ] Executive dashboard displays key metrics
- [ ] User growth tracked and visualized
- [ ] Revenue metrics accurate and current
- [ ] Custom reports can be generated and exported
- [ ] Geographic insights show activity distribution
- [ ] Analytics update in near real-time

### Technical Requirements
- [ ] Analytics queries optimized with caching
- [ ] Daily metrics aggregated via scheduled job
- [ ] Report generation queued for large exports
- [ ] Excel exports formatted correctly
- [ ] Cache invalidation works properly

### User Experience Requirements
- [ ] Dashboard loads quickly (<2 seconds)
- [ ] Charts and graphs clear and interactive
- [ ] Report builder intuitive
- [ ] Export downloads work reliably

### Performance Requirements
- [ ] Dashboard queries cached effectively
- [ ] Large exports don't timeout
- [ ] Daily aggregation completes in <5 minutes
- [ ] Widget updates don't block UI

## Dependencies

### External Dependencies
- **All epics**: Analytics aggregates data from entire platform
- **maatwebsite/excel**: Excel export functionality

## Technical Notes

### Laravel 12 Conventions
- Use `casts()` method in models
- Schedule daily aggregation with Task Scheduler

### Filament Widget Example
```php
use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Monthly Active Users', $this->analyticsService->getMAU())
                ->description('32% increase')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->chart([7, 2, 10, 3, 15, 4, 17])
                ->color('success'),
        ];
    }
}
```

### Report Export
```php
return Excel::download(new UsersExport($filters), 'users.xlsx');
```

### Testing Considerations
- Seed realistic data for accurate calculations
- Test edge cases (no data, single user, etc.)
- Run tests with: `php artisan test --filter=Analytics`

---

**Feature Status**: 🔄 Ready for Implementation
**Priority**: P1
**Epic**: E07 Administration
**Estimated Total Time**: 34-41 hours
**Dependencies**: E01-E06 data available for aggregation
