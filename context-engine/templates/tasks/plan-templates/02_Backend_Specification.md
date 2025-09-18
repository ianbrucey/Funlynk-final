# Backend Specification for {{feature_name}}

## Database Changes

### New Tables/Collections

#### {{table_name}}
```sql
CREATE TABLE {{table_name}} (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    {{column_name}} {{data_type}} {{constraints}},
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);
```

**Indexes**:
```sql
CREATE INDEX idx_{{table_name}}_{{column}} ON {{table_name}}({{column}});
CREATE UNIQUE INDEX idx_{{table_name}}_unique ON {{table_name}}({{column1}}, {{column2}});
```

**Constraints**:
- {{constraint_description}}

### Schema Modifications

#### {{existing_table}}
```sql
-- Add new columns
ALTER TABLE {{existing_table}} 
ADD COLUMN {{new_column}} {{data_type}} {{constraints}};

-- Modify existing columns  
ALTER TABLE {{existing_table}} 
ALTER COLUMN {{column}} TYPE {{new_type}};

-- Add constraints
ALTER TABLE {{existing_table}}
ADD CONSTRAINT {{constraint_name}} {{constraint_definition}};
```

### Migration Scripts

#### Migration: {{migration_name}}
```sql
-- Up migration
{{up_migration_sql}}

-- Down migration (rollback)
{{down_migration_sql}}
```

**Migration Notes**:
- **Data Impact**: {{how_existing_data_is_affected}}
- **Downtime**: {{expected_downtime}}
- **Rollback Plan**: {{how_to_rollback_safely}}

## API Endpoints

### {{HTTP_METHOD}} {{endpoint_path}}

**Purpose**: {{what_this_endpoint_does}}

**Authentication**: {{required_auth_level}}

**Request**:
```json
{
  "{{field}}": "{{type_and_description}}",
  "{{field}}": {{example_value}}
}
```

**Response (Success - 200/201)**:
```json
{
  "{{field}}": "{{type_and_description}}",
  "{{field}}": {{example_value}}
}
```

**Error Responses**:
- **400 Bad Request**: {{when_this_occurs}}
  ```json
  {
    "error": "{{error_code}}",
    "message": "{{user_friendly_message}}",
    "details": ["{{validation_error}}"]
  }
  ```
- **401 Unauthorized**: {{when_this_occurs}}
- **403 Forbidden**: {{when_this_occurs}}
- **404 Not Found**: {{when_this_occurs}}
- **409 Conflict**: {{when_this_occurs}}
- **500 Internal Server Error**: {{when_this_occurs}}

**Rate Limiting**: {{requests_per_minute_per_user}}

### {{HTTP_METHOD}} {{another_endpoint}}
[Repeat structure above for each endpoint]

## Business Logic

### Core Rules

#### {{business_rule_name}}
- **Description**: {{what_the_rule_enforces}}
- **Implementation**: {{where_and_how_its_implemented}}
- **Edge Cases**: {{special_scenarios_to_handle}}

#### {{another_business_rule}}
- **Description**: {{rule_description}}
- **Validation**: {{how_to_validate}}
- **Error Handling**: {{what_happens_on_violation}}

### Data Validation

#### Input Validation
- **{{field_name}}**: {{validation_rules}}
- **{{field_name}}**: {{validation_rules}}

#### Business Logic Validation
- **{{rule}}**: {{when_and_how_to_check}}
- **{{rule}}**: {{validation_logic}}

### Security Considerations

#### Authentication & Authorization
- **Who can access**: {{user_roles_permissions}}
- **Resource ownership**: {{how_ownership_is_determined}}
- **Permission checks**: {{where_permissions_are_verified}}

#### Data Protection
- **Sensitive data**: {{what_data_needs_protection}}
- **Encryption**: {{what_gets_encrypted}}
- **Audit logging**: {{what_actions_are_logged}}

## Background Jobs & Async Processing

### {{job_name}}
- **Trigger**: {{what_triggers_this_job}}
- **Frequency**: {{how_often_it_runs}}
- **Processing**: {{what_the_job_does}}
- **Error Handling**: {{what_happens_on_failure}}
- **Monitoring**: {{how_to_monitor_job_health}}

### Queue Management
- **Queue Name**: {{queue_identifier}}
- **Priority**: {{job_priority_level}}
- **Retry Policy**: {{retry_attempts_and_backoff}}
- **Dead Letter Queue**: {{failed_job_handling}}

## External Service Integration

### {{service_name}} Integration
- **Purpose**: {{why_we_need_this_service}}
- **API Calls**: {{specific_endpoints_used}}
- **Authentication**: {{how_we_authenticate}}
- **Error Handling**: {{fallback_strategies}}
- **Rate Limiting**: {{service_limits_to_respect}}

## Performance Considerations

### Database Performance
- **Query Optimization**: {{specific_optimizations_needed}}
- **Indexing Strategy**: {{which_indexes_are_critical}}
- **Connection Pooling**: {{pool_size_and_configuration}}

### Caching Strategy
- **What to Cache**: {{data_that_benefits_from_caching}}
- **Cache Duration**: {{ttl_for_different_data_types}}
- **Cache Invalidation**: {{when_and_how_to_invalidate}}

### Scalability
- **Bottlenecks**: {{potential_performance_bottlenecks}}
- **Horizontal Scaling**: {{how_to_scale_out}}
- **Resource Usage**: {{cpu_memory_disk_requirements}}

## Implementation Tasks

### Database Tasks
- [ ] **BE-DB-1**: {{specific_database_task}}
- [ ] **BE-DB-2**: {{specific_database_task}}

### API Development Tasks
- [ ] **BE-API-1**: {{specific_api_task}}
- [ ] **BE-API-2**: {{specific_api_task}}

### Business Logic Tasks
- [ ] **BE-BL-1**: {{specific_business_logic_task}}
- [ ] **BE-BL-2**: {{specific_business_logic_task}}

### Integration Tasks
- [ ] **BE-INT-1**: {{specific_integration_task}}
- [ ] **BE-INT-2**: {{specific_integration_task}}

## Testing Strategy

### Unit Tests
- **Models**: {{what_model_logic_to_test}}
- **Services**: {{what_service_logic_to_test}}
- **Utilities**: {{what_utility_functions_to_test}}

### Integration Tests
- **API Endpoints**: {{which_endpoints_need_integration_tests}}
- **Database Operations**: {{which_db_operations_to_test}}
- **External Services**: {{how_to_mock_external_calls}}

### Performance Tests
- **Load Testing**: {{expected_load_scenarios}}
- **Stress Testing**: {{breaking_point_scenarios}}
- **Benchmark Tests**: {{performance_benchmarks_to_maintain}}

## Monitoring & Observability

### Metrics to Track
- **Performance**: {{response_times_throughput}}
- **Errors**: {{error_rates_by_type}}
- **Business**: {{feature_usage_metrics}}

### Logging Strategy
- **What to Log**: {{important_events_to_log}}
- **Log Levels**: {{when_to_use_each_level}}
- **Structured Logging**: {{log_format_and_fields}}

### Alerts
- **Error Rate**: {{threshold_for_alerting}}
- **Response Time**: {{latency_thresholds}}
- **Resource Usage**: {{cpu_memory_disk_thresholds}}

---

**Related Documents**:
- [UX Specification](01_UX_Specification.md)
- [Frontend Specification](03_Frontend_Specification.md)
- [Third Party Services](04_Third_Party_Services.md)
