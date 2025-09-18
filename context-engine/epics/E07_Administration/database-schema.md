# E07 Administration - Database Schema

## Overview

This document defines the database schema additions for the Administration epic. These tables support platform analytics, content moderation, user management, and system monitoring capabilities.

## Analytics & Business Intelligence Tables

### analytics_events
**Purpose**: Stores all platform events for analytics processing
```sql
CREATE TABLE analytics_events (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    event_type VARCHAR(100) NOT NULL,
    event_category VARCHAR(50) NOT NULL,
    user_id UUID REFERENCES users(id) ON DELETE SET NULL,
    session_id VARCHAR(255),
    activity_id UUID REFERENCES activities(id) ON DELETE SET NULL,
    event_data JSONB NOT NULL DEFAULT '{}',
    event_timestamp TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    ip_address INET,
    user_agent TEXT,
    referrer TEXT,
    utm_source VARCHAR(100),
    utm_medium VARCHAR(100),
    utm_campaign VARCHAR(100),
    device_type VARCHAR(50),
    browser VARCHAR(100),
    operating_system VARCHAR(100),
    country_code CHAR(2),
    region VARCHAR(100),
    city VARCHAR(100),
    processed_at TIMESTAMPTZ,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_analytics_events_type_timestamp ON analytics_events(event_type, event_timestamp);
CREATE INDEX idx_analytics_events_user_timestamp ON analytics_events(user_id, event_timestamp);
CREATE INDEX idx_analytics_events_activity_timestamp ON analytics_events(activity_id, event_timestamp);
CREATE INDEX idx_analytics_events_session ON analytics_events(session_id);
CREATE INDEX idx_analytics_events_timestamp ON analytics_events(event_timestamp);
```

### analytics_metrics
**Purpose**: Stores aggregated metrics for fast dashboard queries
```sql
CREATE TABLE analytics_metrics (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    metric_name VARCHAR(100) NOT NULL,
    metric_category VARCHAR(50) NOT NULL,
    time_period VARCHAR(20) NOT NULL, -- hour, day, week, month
    period_start TIMESTAMPTZ NOT NULL,
    period_end TIMESTAMPTZ NOT NULL,
    metric_value DECIMAL(15,4) NOT NULL,
    metric_count INTEGER DEFAULT 0,
    dimensions JSONB DEFAULT '{}',
    calculated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    
    UNIQUE(metric_name, metric_category, time_period, period_start, dimensions)
);

CREATE INDEX idx_analytics_metrics_name_period ON analytics_metrics(metric_name, time_period, period_start);
CREATE INDEX idx_analytics_metrics_category_period ON analytics_metrics(metric_category, time_period, period_start);
```

### user_cohorts
**Purpose**: Tracks user cohorts for retention and behavior analysis
```sql
CREATE TABLE user_cohorts (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    cohort_name VARCHAR(100) NOT NULL,
    cohort_type VARCHAR(50) NOT NULL, -- registration, first_activity, first_payment
    cohort_period DATE NOT NULL,
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    cohort_data JSONB DEFAULT '{}',
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    
    UNIQUE(cohort_name, cohort_type, cohort_period, user_id)
);

CREATE INDEX idx_user_cohorts_name_period ON user_cohorts(cohort_name, cohort_period);
CREATE INDEX idx_user_cohorts_user ON user_cohorts(user_id);
```

### ab_experiments
**Purpose**: Manages A/B testing experiments and results
```sql
CREATE TABLE ab_experiments (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    experiment_name VARCHAR(100) NOT NULL UNIQUE,
    experiment_description TEXT,
    experiment_type VARCHAR(50) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'draft', -- draft, active, paused, completed
    start_date TIMESTAMPTZ,
    end_date TIMESTAMPTZ,
    target_audience JSONB DEFAULT '{}',
    variants JSONB NOT NULL DEFAULT '[]',
    success_metrics JSONB DEFAULT '[]',
    traffic_allocation DECIMAL(3,2) DEFAULT 1.0,
    created_by UUID NOT NULL REFERENCES users(id),
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_ab_experiments_status ON ab_experiments(status);
CREATE INDEX idx_ab_experiments_dates ON ab_experiments(start_date, end_date);
```

### ab_experiment_assignments
**Purpose**: Tracks user assignments to A/B test variants
```sql
CREATE TABLE ab_experiment_assignments (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    experiment_id UUID NOT NULL REFERENCES ab_experiments(id) ON DELETE CASCADE,
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    variant_name VARCHAR(50) NOT NULL,
    assigned_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    
    UNIQUE(experiment_id, user_id)
);

CREATE INDEX idx_ab_assignments_experiment ON ab_experiment_assignments(experiment_id);
CREATE INDEX idx_ab_assignments_user ON ab_experiment_assignments(user_id);
```

## Content Moderation & Safety Tables

### moderation_policies
**Purpose**: Defines platform policies and rules
```sql
CREATE TABLE moderation_policies (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    policy_name VARCHAR(100) NOT NULL UNIQUE,
    policy_category VARCHAR(50) NOT NULL,
    policy_description TEXT NOT NULL,
    policy_rules JSONB NOT NULL DEFAULT '[]',
    severity_level INTEGER NOT NULL DEFAULT 1, -- 1-5 scale
    automated_actions JSONB DEFAULT '[]',
    manual_review_required BOOLEAN DEFAULT false,
    is_active BOOLEAN DEFAULT true,
    created_by UUID NOT NULL REFERENCES users(id),
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_moderation_policies_category ON moderation_policies(policy_category);
CREATE INDEX idx_moderation_policies_active ON moderation_policies(is_active);
```

### content_moderation_queue
**Purpose**: Manages content requiring moderation review
```sql
CREATE TABLE content_moderation_queue (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    content_type VARCHAR(50) NOT NULL, -- activity, comment, profile, image
    content_id UUID NOT NULL,
    content_data JSONB NOT NULL DEFAULT '{}',
    reported_by UUID REFERENCES users(id) ON DELETE SET NULL,
    report_reason VARCHAR(100),
    report_description TEXT,
    automated_flags JSONB DEFAULT '[]',
    risk_score DECIMAL(3,2) DEFAULT 0.0,
    priority_level INTEGER DEFAULT 1, -- 1-5 scale
    status VARCHAR(20) NOT NULL DEFAULT 'pending', -- pending, in_review, resolved, escalated
    assigned_to UUID REFERENCES users(id) ON DELETE SET NULL,
    assigned_at TIMESTAMPTZ,
    reviewed_at TIMESTAMPTZ,
    resolution VARCHAR(50),
    resolution_notes TEXT,
    policy_violations JSONB DEFAULT '[]',
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_moderation_queue_status ON content_moderation_queue(status);
CREATE INDEX idx_moderation_queue_priority ON content_moderation_queue(priority_level, created_at);
CREATE INDEX idx_moderation_queue_assigned ON content_moderation_queue(assigned_to);
CREATE INDEX idx_moderation_queue_content ON content_moderation_queue(content_type, content_id);
```

### moderation_actions
**Purpose**: Records all moderation actions taken
```sql
CREATE TABLE moderation_actions (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    action_type VARCHAR(50) NOT NULL, -- warning, content_removal, suspension, ban
    target_type VARCHAR(50) NOT NULL, -- user, activity, comment, community
    target_id UUID NOT NULL,
    target_user_id UUID REFERENCES users(id) ON DELETE SET NULL,
    policy_violation_id UUID REFERENCES moderation_policies(id),
    action_reason TEXT NOT NULL,
    action_details JSONB DEFAULT '{}',
    severity_level INTEGER NOT NULL,
    duration_hours INTEGER, -- for temporary actions
    expires_at TIMESTAMPTZ,
    performed_by UUID NOT NULL REFERENCES users(id),
    automated_action BOOLEAN DEFAULT false,
    appeal_eligible BOOLEAN DEFAULT true,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_moderation_actions_target ON moderation_actions(target_type, target_id);
CREATE INDEX idx_moderation_actions_user ON moderation_actions(target_user_id);
CREATE INDEX idx_moderation_actions_performed_by ON moderation_actions(performed_by);
CREATE INDEX idx_moderation_actions_expires ON moderation_actions(expires_at) WHERE expires_at IS NOT NULL;
```

### safety_reports
**Purpose**: Tracks safety incidents and reports
```sql
CREATE TABLE safety_reports (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    report_type VARCHAR(50) NOT NULL, -- harassment, inappropriate_content, safety_concern
    reported_content_type VARCHAR(50),
    reported_content_id UUID,
    reported_user_id UUID REFERENCES users(id) ON DELETE SET NULL,
    reporter_user_id UUID REFERENCES users(id) ON DELETE SET NULL,
    report_description TEXT NOT NULL,
    evidence_urls TEXT[],
    incident_date TIMESTAMPTZ,
    severity_assessment INTEGER DEFAULT 1, -- 1-5 scale
    status VARCHAR(20) NOT NULL DEFAULT 'open', -- open, investigating, resolved, closed
    investigation_notes TEXT,
    resolution_summary TEXT,
    assigned_investigator UUID REFERENCES users(id) ON DELETE SET NULL,
    resolved_at TIMESTAMPTZ,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_safety_reports_status ON safety_reports(status);
CREATE INDEX idx_safety_reports_reported_user ON safety_reports(reported_user_id);
CREATE INDEX idx_safety_reports_reporter ON safety_reports(reporter_user_id);
CREATE INDEX idx_safety_reports_severity ON safety_reports(severity_assessment, created_at);
```

## User & Community Management Tables

### admin_users
**Purpose**: Manages administrative users and their permissions
```sql
CREATE TABLE admin_users (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    admin_role VARCHAR(50) NOT NULL, -- super_admin, moderator, support, analyst
    permissions JSONB NOT NULL DEFAULT '[]',
    access_level INTEGER NOT NULL DEFAULT 1, -- 1-5 scale
    department VARCHAR(50),
    manager_id UUID REFERENCES admin_users(id),
    is_active BOOLEAN DEFAULT true,
    last_login_at TIMESTAMPTZ,
    created_by UUID REFERENCES admin_users(id),
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    
    UNIQUE(user_id)
);

CREATE INDEX idx_admin_users_role ON admin_users(admin_role);
CREATE INDEX idx_admin_users_active ON admin_users(is_active);
```

### support_tickets
**Purpose**: Manages customer support tickets and resolution
```sql
CREATE TABLE support_tickets (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    ticket_number VARCHAR(20) NOT NULL UNIQUE,
    user_id UUID REFERENCES users(id) ON DELETE SET NULL,
    ticket_type VARCHAR(50) NOT NULL, -- technical, billing, account, safety
    priority VARCHAR(20) NOT NULL DEFAULT 'medium', -- low, medium, high, urgent
    status VARCHAR(20) NOT NULL DEFAULT 'open', -- open, in_progress, waiting, resolved, closed
    subject VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    category VARCHAR(50),
    subcategory VARCHAR(50),
    assigned_to UUID REFERENCES admin_users(id) ON DELETE SET NULL,
    assigned_at TIMESTAMPTZ,
    first_response_at TIMESTAMPTZ,
    resolved_at TIMESTAMPTZ,
    closed_at TIMESTAMPTZ,
    satisfaction_rating INTEGER, -- 1-5 scale
    satisfaction_feedback TEXT,
    tags TEXT[],
    metadata JSONB DEFAULT '{}',
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_support_tickets_status ON support_tickets(status);
CREATE INDEX idx_support_tickets_assigned ON support_tickets(assigned_to);
CREATE INDEX idx_support_tickets_user ON support_tickets(user_id);
CREATE INDEX idx_support_tickets_priority ON support_tickets(priority, created_at);
```

### support_ticket_messages
**Purpose**: Stores messages and communication within support tickets
```sql
CREATE TABLE support_ticket_messages (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    ticket_id UUID NOT NULL REFERENCES support_tickets(id) ON DELETE CASCADE,
    sender_type VARCHAR(20) NOT NULL, -- user, admin, system
    sender_id UUID REFERENCES users(id) ON DELETE SET NULL,
    message_content TEXT NOT NULL,
    message_type VARCHAR(20) DEFAULT 'text', -- text, attachment, system_note
    attachments JSONB DEFAULT '[]',
    is_internal BOOLEAN DEFAULT false,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_support_messages_ticket ON support_ticket_messages(ticket_id, created_at);
CREATE INDEX idx_support_messages_sender ON support_ticket_messages(sender_id);
```

### user_verification_requests
**Purpose**: Manages user identity verification requests
```sql
CREATE TABLE user_verification_requests (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    verification_type VARCHAR(50) NOT NULL, -- identity, host, business
    status VARCHAR(20) NOT NULL DEFAULT 'pending', -- pending, approved, rejected, expired
    submitted_documents JSONB DEFAULT '[]',
    verification_data JSONB DEFAULT '{}',
    reviewed_by UUID REFERENCES admin_users(id) ON DELETE SET NULL,
    reviewed_at TIMESTAMPTZ,
    review_notes TEXT,
    rejection_reason VARCHAR(100),
    expires_at TIMESTAMPTZ,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_verification_requests_user ON user_verification_requests(user_id);
CREATE INDEX idx_verification_requests_status ON user_verification_requests(status);
CREATE INDEX idx_verification_requests_type ON user_verification_requests(verification_type);
```

## System Monitoring & Operations Tables

### system_health_metrics
**Purpose**: Stores system performance and health metrics
```sql
CREATE TABLE system_health_metrics (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    metric_name VARCHAR(100) NOT NULL,
    metric_category VARCHAR(50) NOT NULL, -- performance, availability, security, business
    service_name VARCHAR(100),
    metric_value DECIMAL(15,4) NOT NULL,
    metric_unit VARCHAR(20),
    threshold_warning DECIMAL(15,4),
    threshold_critical DECIMAL(15,4),
    status VARCHAR(20) DEFAULT 'normal', -- normal, warning, critical
    tags JSONB DEFAULT '{}',
    recorded_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_health_metrics_name_time ON system_health_metrics(metric_name, recorded_at);
CREATE INDEX idx_health_metrics_service_time ON system_health_metrics(service_name, recorded_at);
CREATE INDEX idx_health_metrics_status ON system_health_metrics(status, recorded_at);
```

### system_alerts
**Purpose**: Manages system alerts and notifications
```sql
CREATE TABLE system_alerts (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    alert_type VARCHAR(50) NOT NULL, -- performance, security, business, operational
    severity VARCHAR(20) NOT NULL, -- info, warning, error, critical
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    service_affected VARCHAR(100),
    metric_name VARCHAR(100),
    threshold_value DECIMAL(15,4),
    current_value DECIMAL(15,4),
    status VARCHAR(20) NOT NULL DEFAULT 'active', -- active, acknowledged, resolved
    acknowledged_by UUID REFERENCES admin_users(id) ON DELETE SET NULL,
    acknowledged_at TIMESTAMPTZ,
    resolved_at TIMESTAMPTZ,
    resolution_notes TEXT,
    alert_data JSONB DEFAULT '{}',
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_system_alerts_status ON system_alerts(status);
CREATE INDEX idx_system_alerts_severity ON system_alerts(severity, created_at);
CREATE INDEX idx_system_alerts_service ON system_alerts(service_affected);
```

### audit_logs
**Purpose**: Comprehensive audit trail for all administrative actions
```sql
CREATE TABLE audit_logs (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    action_type VARCHAR(100) NOT NULL,
    action_category VARCHAR(50) NOT NULL, -- user_management, content_moderation, system_admin
    performed_by UUID REFERENCES users(id) ON DELETE SET NULL,
    target_type VARCHAR(50),
    target_id UUID,
    action_description TEXT NOT NULL,
    before_state JSONB,
    after_state JSONB,
    ip_address INET,
    user_agent TEXT,
    session_id VARCHAR(255),
    success BOOLEAN DEFAULT true,
    error_message TEXT,
    metadata JSONB DEFAULT '{}',
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_audit_logs_performed_by ON audit_logs(performed_by, created_at);
CREATE INDEX idx_audit_logs_action_type ON audit_logs(action_type, created_at);
CREATE INDEX idx_audit_logs_target ON audit_logs(target_type, target_id);
CREATE INDEX idx_audit_logs_category ON audit_logs(action_category, created_at);
```

## Data Retention and Archival

### data_retention_policies
**Purpose**: Manages data retention and archival policies
```sql
CREATE TABLE data_retention_policies (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    table_name VARCHAR(100) NOT NULL,
    retention_period_days INTEGER NOT NULL,
    archival_enabled BOOLEAN DEFAULT false,
    archival_storage_location VARCHAR(200),
    deletion_enabled BOOLEAN DEFAULT true,
    policy_description TEXT,
    last_cleanup_at TIMESTAMPTZ,
    next_cleanup_at TIMESTAMPTZ,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    
    UNIQUE(table_name)
);

CREATE INDEX idx_retention_policies_cleanup ON data_retention_policies(next_cleanup_at) WHERE is_active = true;
```

---

**Database Schema Status**: ✅ Complete
**Next Steps**: Design service architecture for administration components
