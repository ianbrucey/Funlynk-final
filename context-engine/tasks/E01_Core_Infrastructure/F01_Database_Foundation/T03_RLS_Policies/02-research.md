# T03: Row Level Security (RLS) Policies - Research

## Research Objectives

1. Understand Supabase RLS implementation and best practices
2. Design comprehensive security model for all data access patterns
3. Research performance implications of RLS policies
4. Identify compliance requirements for data protection regulations
5. Plan policy structure for maintainability and scalability

## Supabase RLS Overview

### How RLS Works in Supabase
- **PostgreSQL Foundation**: Built on PostgreSQL's native RLS feature
- **Policy-Based Access**: Each table can have multiple policies for different operations
- **User Context**: Policies use `auth.uid()` to identify current user
- **Real-time Integration**: RLS automatically filters real-time subscriptions
- **Performance**: Policies are evaluated at the database level for efficiency

### Policy Types
```sql
-- Enable RLS on a table
ALTER TABLE table_name ENABLE ROW LEVEL SECURITY;

-- Policy for SELECT operations
CREATE POLICY "policy_name" ON table_name
    FOR SELECT USING (condition);

-- Policy for INSERT operations  
CREATE POLICY "policy_name" ON table_name
    FOR INSERT WITH CHECK (condition);

-- Policy for UPDATE operations
CREATE POLICY "policy_name" ON table_name
    FOR UPDATE USING (condition) WITH CHECK (condition);

-- Policy for DELETE operations
CREATE POLICY "policy_name" ON table_name
    FOR DELETE USING (condition);
```

### Supabase Auth Integration
```sql
-- Get current user ID
auth.uid()

-- Get user role
auth.role()

-- Get JWT claims
auth.jwt() ->> 'claim_name'

-- Check if user is authenticated
auth.uid() IS NOT NULL
```

## Security Model Design

### User Authentication Levels
1. **Anonymous (anon)**: Public read access only
2. **Authenticated (authenticated)**: Standard user access
3. **Service Role**: Full access for server-side operations
4. **Admin**: Enhanced access for platform management

### Data Access Patterns

#### 1. User Profile Data
```sql
-- Users can read their own profile
CREATE POLICY "users_select_own" ON users
    FOR SELECT USING (auth.uid() = id);

-- Users can update their own profile
CREATE POLICY "users_update_own" ON users
    FOR UPDATE USING (auth.uid() = id);

-- Public profiles visible to authenticated users
CREATE POLICY "users_select_public" ON users
    FOR SELECT USING (
        auth.role() = 'authenticated' AND 
        (privacy_level = 'public' OR auth.uid() = id)
    );
```

#### 2. Activity Management
```sql
-- Activity hosts can manage their activities
CREATE POLICY "activities_host_access" ON activities
    FOR ALL USING (auth.uid() = host_id);

-- Public activities visible to all authenticated users
CREATE POLICY "activities_public_read" ON activities
    FOR SELECT USING (
        auth.role() = 'authenticated' AND 
        visibility = 'public'
    );

-- Private activities only visible to invited users
CREATE POLICY "activities_private_read" ON activities
    FOR SELECT USING (
        auth.uid() = host_id OR 
        EXISTS (
            SELECT 1 FROM rsvps 
            WHERE activity_id = activities.id 
            AND user_id = auth.uid()
        )
    );
```

#### 3. Financial Data (Enhanced Security)
```sql
-- Users can only access their own payment methods
CREATE POLICY "payment_methods_own_access" ON payment_methods
    FOR ALL USING (auth.uid() = user_id);

-- Transactions visible to involved parties only
CREATE POLICY "transactions_participant_access" ON transactions
    FOR SELECT USING (
        auth.uid() = payer_id OR 
        auth.uid() = recipient_id OR
        auth.jwt() ->> 'role' = 'admin'
    );
```

### Admin Access Patterns
```sql
-- Admin users can access user data for moderation
CREATE POLICY "users_admin_access" ON users
    FOR SELECT USING (
        auth.jwt() ->> 'role' = 'admin' OR
        auth.jwt() ->> 'role' = 'moderator'
    );

-- Audit logs readable by security team
CREATE POLICY "audit_logs_security_read" ON audit_logs
    FOR SELECT USING (
        auth.jwt() ->> 'role' IN ('admin', 'security')
    );
```

## Performance Considerations

### RLS Performance Impact
- **Query Planning**: RLS conditions are included in query execution plans
- **Index Usage**: Proper indexes on RLS condition columns are critical
- **Policy Complexity**: Simple policies perform better than complex joins
- **Caching**: PostgreSQL caches policy evaluation results when possible

### Optimization Strategies
```sql
-- Index on user_id for user-specific policies
CREATE INDEX idx_table_user_id ON table_name(user_id);

-- Partial index for public data
CREATE INDEX idx_activities_public ON activities(created_at) 
    WHERE visibility = 'public';

-- Composite index for complex conditions
CREATE INDEX idx_rsvps_activity_user ON rsvps(activity_id, user_id);
```

### Performance Testing Approach
- Benchmark queries before and after RLS implementation
- Test with realistic data volumes (1M+ users, 10M+ activities)
- Monitor query execution plans for policy overhead
- Load test real-time subscriptions with RLS filtering

## Compliance Requirements

### GDPR Compliance
- **Data Minimization**: Users only access necessary data
- **Purpose Limitation**: Data access aligned with platform purposes
- **Right to Access**: Users can access all their personal data
- **Right to Deletion**: Proper handling of data deletion requests
- **Data Portability**: Structured access to user's own data

### CCPA Compliance
- **Consumer Rights**: Access, deletion, and opt-out rights
- **Data Categories**: Clear classification of personal information
- **Business Purposes**: Documented reasons for data access
- **Third-party Sharing**: Controlled access to shared data

### SOC 2 Type II
- **Access Controls**: Documented and tested access restrictions
- **Monitoring**: Audit trails for all data access
- **Change Management**: Controlled updates to security policies
- **Incident Response**: Procedures for security violations

## Policy Implementation Strategy

### Phase 1: Core User Data (1 hour)
- Enable RLS on user tables
- Implement basic user-own-data policies
- Test user profile access patterns

### Phase 2: Activity and Social Data (1 hour)
- Implement activity visibility policies
- Add social interaction access controls
- Test public/private data separation

### Phase 3: Financial and Sensitive Data (1 hour)
- Implement enhanced financial data policies
- Add audit logging for sensitive access
- Test payment and transaction security

### Phase 4: Administrative Access (30 minutes)
- Implement admin and moderator policies
- Add role-based access controls
- Test administrative data access

### Phase 5: Testing and Validation (30 minutes)
- Comprehensive access testing
- Performance validation
- Security penetration testing

## Technical Decisions

### Decision 1: Policy Granularity
**Choice**: Table-level policies with condition-based filtering
**Rationale**: Balances security with performance, easier to maintain than column-level policies
**Alternatives Considered**: Column-level RLS (too complex), application-level security (less secure)

### Decision 2: Admin Access Model
**Choice**: JWT claims-based role checking
**Rationale**: Integrates with Supabase Auth, supports multiple admin roles
**Alternatives Considered**: Separate admin tables (more complex), hardcoded admin IDs (inflexible)

### Decision 3: Real-time Filtering
**Choice**: Rely on RLS for real-time subscription filtering
**Rationale**: Automatic security, consistent with database access patterns
**Alternatives Considered**: Application-level filtering (error-prone), separate real-time policies (complex)

### Decision 4: Performance vs Security Trade-offs
**Choice**: Prioritize security with performance monitoring
**Rationale**: Security is non-negotiable, performance can be optimized with proper indexing
**Alternatives Considered**: Relaxed security for performance (unacceptable risk)

## Testing Strategy

### Unit Testing
- Test each policy type individually
- Verify correct access for different user roles
- Test edge cases and boundary conditions
- Validate policy interactions

### Integration Testing
- Test complete user workflows with RLS
- Verify real-time subscription filtering
- Test admin access scenarios
- Validate cross-table policy interactions

### Security Testing
- Attempt unauthorized data access
- Test privilege escalation scenarios
- Verify policy bypass attempts fail
- Test with malicious input patterns

### Performance Testing
- Benchmark query performance with RLS
- Test with large datasets
- Monitor real-time subscription performance
- Validate index effectiveness

## Risk Mitigation

### Policy Gap Prevention
- Comprehensive policy coverage checklist
- Regular security audits and reviews
- Automated policy testing in CI/CD
- Security team review of all policies

### Performance Monitoring
- Query performance dashboards
- RLS overhead tracking
- Real-time performance alerts
- Regular performance optimization reviews

### Compliance Validation
- Regular compliance audits
- Policy documentation maintenance
- Legal team review of access patterns
- External security assessments

## Implementation Tools

### Development Tools
```sql
-- Test policy as specific user
SET LOCAL ROLE authenticated;
SET LOCAL "request.jwt.claims" TO '{"sub":"user-id","role":"authenticated"}';

-- Reset to default role
RESET ROLE;
```

### Monitoring Queries
```sql
-- Check RLS status on all tables
SELECT schemaname, tablename, rowsecurity 
FROM pg_tables 
WHERE schemaname = 'public';

-- View all policies
SELECT schemaname, tablename, policyname, permissive, roles, cmd, qual, with_check
FROM pg_policies 
WHERE schemaname = 'public';
```

## Next Steps

1. **Proceed to Planning Phase**: Create detailed implementation plan
2. **Policy Design**: Design specific policies for each table
3. **Testing Framework**: Set up comprehensive testing approach
4. **Performance Baseline**: Establish performance benchmarks
5. **Documentation**: Create policy documentation and maintenance procedures

---

**Research Status**: ✅ Complete
**Key Decisions**: JWT-based admin access, table-level policies, security-first approach
**Next Phase**: Planning (03-plan-enhanced.md)
**Estimated Implementation Time**: 3-4 hours total
