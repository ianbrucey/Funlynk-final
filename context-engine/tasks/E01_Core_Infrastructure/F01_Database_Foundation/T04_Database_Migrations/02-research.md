# T04: Database Migrations and Version Control - Research

## Research Objectives

1. Understand Supabase CLI migration capabilities and best practices
2. Design migration workflow for team collaboration and deployment
3. Research migration safety patterns and rollback strategies
4. Plan environment synchronization and conflict resolution
5. Establish migration testing and validation procedures

## Supabase Migration System Analysis

### Supabase CLI Migration Features
- **Local Development**: `supabase start` creates local PostgreSQL instance
- **Migration Generation**: `supabase db diff` generates SQL migration files
- **Migration Application**: `supabase db push` applies migrations to remote
- **Schema Synchronization**: `supabase db pull` syncs remote schema to local
- **Reset Capability**: `supabase db reset` rebuilds database from migrations

### Migration File Structure
```
supabase/
├── config.toml                 # Project configuration
├── seed.sql                    # Initial data seeding
└── migrations/
    ├── 20240101000000_initial_schema.sql
    ├── 20240102000000_add_user_preferences.sql
    └── 20240103000000_create_activity_indexes.sql
```

### Migration Workflow Commands
```bash
# Initialize Supabase project
supabase init

# Start local development
supabase start

# Generate migration from schema changes
supabase db diff --file migration_name

# Apply migrations to remote
supabase db push

# Pull remote schema changes
supabase db pull

# Reset local database
supabase db reset
```

## Migration Strategy Design

### Development Workflow
1. **Feature Development**: Make schema changes locally
2. **Migration Generation**: Use `supabase db diff` to create migration
3. **Local Testing**: Test migration with `supabase db reset`
4. **Code Review**: Review migration in pull request
5. **Staging Deployment**: Apply migration to staging environment
6. **Production Deployment**: Apply migration to production

### Environment Management
- **Development**: Local Supabase instance for each developer
- **Staging**: Shared staging environment for integration testing
- **Production**: Live production environment with careful deployment

### Migration Naming Convention
```
YYYYMMDDHHMMSS_action_description.sql

Examples:
20240101120000_create_user_preferences_table.sql
20240102143000_add_activity_category_index.sql
20240103091500_update_user_privacy_policies.sql
```

## Migration Safety Patterns

### Safe Migration Practices
1. **Additive Changes**: Add new columns/tables without breaking existing code
2. **Backward Compatibility**: Ensure migrations don't break current application
3. **Data Preservation**: Never lose existing data during migrations
4. **Rollback Planning**: Design migrations with rollback procedures
5. **Testing**: Comprehensive testing before production deployment

### Migration Types and Safety

#### Schema Creation (Safe)
```sql
-- Adding new table
CREATE TABLE user_preferences (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID REFERENCES users(id) ON DELETE CASCADE,
    preference_key TEXT NOT NULL,
    preference_value JSONB,
    created_at TIMESTAMPTZ DEFAULT NOW()
);
```

#### Schema Modification (Requires Care)
```sql
-- Adding nullable column (safe)
ALTER TABLE activities ADD COLUMN max_participants INTEGER;

-- Adding non-null column (requires default or data migration)
ALTER TABLE activities ADD COLUMN status TEXT DEFAULT 'active' NOT NULL;

-- Renaming column (breaking change - requires application update)
ALTER TABLE activities RENAME COLUMN description TO activity_description;
```

#### Data Migration (High Risk)
```sql
-- Safe data migration with validation
DO $$
BEGIN
    -- Validate data before migration
    IF EXISTS (SELECT 1 FROM users WHERE email IS NULL) THEN
        RAISE EXCEPTION 'Cannot proceed: null emails exist';
    END IF;
    
    -- Perform migration
    UPDATE activities 
    SET max_participants = 50 
    WHERE max_participants IS NULL;
END $$;
```

## Rollback Strategy

### Rollback Approaches
1. **Forward-Only Migrations**: Fix issues with new migrations
2. **Reversible Migrations**: Include rollback SQL in migration files
3. **Backup and Restore**: Use database backups for major rollbacks
4. **Point-in-Time Recovery**: Use Supabase PITR for emergency rollback

### Rollback Planning
```sql
-- Migration with rollback comments
-- Migration: Add user preferences table
-- Rollback: DROP TABLE user_preferences;

CREATE TABLE user_preferences (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID REFERENCES users(id) ON DELETE CASCADE,
    preference_key TEXT NOT NULL,
    preference_value JSONB
);
```

## Team Collaboration Patterns

### Concurrent Development
- **Feature Branches**: Each feature gets its own migration files
- **Migration Conflicts**: Resolve by renaming and reordering migrations
- **Merge Strategy**: Squash related migrations before merging to main
- **Communication**: Team coordination for schema changes

### Conflict Resolution
```bash
# When migration conflicts occur
# 1. Rename conflicting migration with new timestamp
mv 20240101120000_add_column.sql 20240101130000_add_column.sql

# 2. Update migration references if needed
# 3. Test migration order
supabase db reset

# 4. Commit resolved migrations
git add supabase/migrations/
git commit -m "Resolve migration conflicts"
```

## Environment Synchronization

### Multi-Environment Setup
```toml
# supabase/config.toml
[db]
port = 54322
major_version = 15

[auth]
enabled = true
site_url = "http://localhost:3000"

[storage]
enabled = true
file_size_limit = "50MB"
```

### Environment-Specific Configurations
- **Development**: Full feature set, test data
- **Staging**: Production-like, sanitized data
- **Production**: Live data, performance optimized

### Synchronization Commands
```bash
# Link to specific environment
supabase link --project-ref staging-project-id

# Pull schema from environment
supabase db pull

# Push migrations to environment
supabase db push

# Switch between environments
supabase link --project-ref production-project-id
```

## Migration Testing Strategy

### Pre-Migration Testing
1. **Local Testing**: Test migration on local development database
2. **Staging Testing**: Apply migration to staging environment
3. **Performance Testing**: Measure migration execution time
4. **Rollback Testing**: Test rollback procedures
5. **Data Validation**: Verify data integrity after migration

### Automated Testing
```bash
#!/bin/bash
# Migration test script

# Reset to clean state
supabase db reset

# Apply all migrations
supabase db push

# Run data validation tests
psql -h localhost -p 54322 -U postgres -d postgres -f validate_schema.sql

# Test application functionality
npm test

echo "Migration testing complete"
```

### Validation Queries
```sql
-- Schema validation
SELECT table_name, column_name, data_type, is_nullable
FROM information_schema.columns
WHERE table_schema = 'public'
ORDER BY table_name, ordinal_position;

-- Constraint validation
SELECT constraint_name, table_name, constraint_type
FROM information_schema.table_constraints
WHERE table_schema = 'public';

-- Index validation
SELECT indexname, tablename, indexdef
FROM pg_indexes
WHERE schemaname = 'public';
```

## CI/CD Integration

### Automated Migration Pipeline
```yaml
# GitHub Actions example
name: Database Migration
on:
  push:
    branches: [main]
    paths: ['supabase/migrations/**']

jobs:
  migrate:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - uses: supabase/setup-cli@v1
      - name: Apply migrations to staging
        run: |
          supabase link --project-ref ${{ secrets.STAGING_PROJECT_REF }}
          supabase db push
      - name: Run tests
        run: npm test
```

### Deployment Safety
- **Staging First**: Always deploy to staging before production
- **Automated Testing**: Run full test suite after migration
- **Rollback Plan**: Have rollback procedure ready
- **Monitoring**: Monitor application health after deployment

## Technical Decisions

### Decision 1: Migration Tool Choice
**Choice**: Supabase CLI native migration system
**Rationale**: Tight integration with Supabase, handles RLS and extensions
**Alternatives**: Custom migration system, third-party tools

### Decision 2: Migration Strategy
**Choice**: Forward-only migrations with rollback documentation
**Rationale**: Simpler workflow, less error-prone than reversible migrations
**Alternatives**: Reversible migrations, manual rollback procedures

### Decision 3: Environment Synchronization
**Choice**: Separate Supabase projects for each environment
**Rationale**: Complete isolation, independent scaling and configuration
**Alternatives**: Single project with database separation

### Decision 4: Conflict Resolution
**Choice**: Timestamp-based ordering with manual conflict resolution
**Rationale**: Simple and predictable, works well with team coordination
**Alternatives**: Dependency-based migrations, automatic conflict resolution

## Performance Considerations

### Migration Performance
- **Large Table Migrations**: Plan for extended execution time
- **Index Creation**: Use CONCURRENTLY for production indexes
- **Data Migration**: Batch large data updates
- **Downtime Planning**: Minimize application downtime

### Optimization Strategies
```sql
-- Create index concurrently (no table lock)
CREATE INDEX CONCURRENTLY idx_activities_location ON activities USING GIST (location);

-- Batch data updates
UPDATE activities 
SET status = 'active' 
WHERE status IS NULL 
AND id IN (
    SELECT id FROM activities 
    WHERE status IS NULL 
    LIMIT 1000
);
```

## Risk Mitigation

### High-Risk Scenarios
- **Data Loss**: Incorrect migration deletes data
- **Application Breakage**: Migration breaks existing functionality
- **Performance Impact**: Migration causes performance degradation

### Mitigation Strategies
- **Backup Before Migration**: Automatic backup before major changes
- **Staging Testing**: Comprehensive testing in staging environment
- **Gradual Rollout**: Deploy to subset of users first
- **Monitoring**: Real-time monitoring during and after migration

## Next Steps

1. **Proceed to Planning Phase**: Create detailed implementation plan
2. **Environment Setup**: Configure development and staging environments
3. **Team Training**: Train team on migration procedures
4. **Documentation**: Create migration guidelines and troubleshooting

---

**Research Status**: ✅ Complete
**Key Decisions**: Supabase CLI, forward-only migrations, separate environments
**Next Phase**: Planning (03-plan-enhanced.md)
**Estimated Implementation Time**: 2-3 hours total
