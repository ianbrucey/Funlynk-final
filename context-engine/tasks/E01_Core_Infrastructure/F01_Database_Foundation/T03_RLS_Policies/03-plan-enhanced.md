# T03: Row Level Security (RLS) Policies - Enhanced Planning

## Planning Overview

This document provides detailed specifications for implementing comprehensive Row Level Security policies across UX, Backend, Frontend, and Third-party service domains. The implementation will secure all database access and ensure users can only access authorized data.

## UX Specification

### User Experience Considerations

#### End User Experience
- **Transparent Security**: Users should not notice RLS policies in normal operation
- **Clear Error Messages**: When access is denied, users get helpful feedback
- **Consistent Behavior**: Security policies work consistently across all features
- **Privacy Confidence**: Users trust their data is properly protected

#### Developer Experience
- **Clear Policy Logic**: Policies are easy to understand and debug
- **Testing Tools**: Easy to test policies with different user contexts
- **Documentation**: Comprehensive policy documentation with examples
- **Debugging Support**: Clear error messages for policy violations

### UX Requirements
- [ ] Security policies are transparent to normal user operations
- [ ] Access denied scenarios provide helpful user feedback
- [ ] Policy violations don't cause confusing application behavior
- [ ] Developers can easily test and debug policy behavior
- [ ] Security documentation is clear and comprehensive

### UX Success Metrics
- Zero user complaints about unexpected access restrictions
- Policy violation errors are resolved quickly by developers
- Security testing coverage > 95% of all access scenarios
- Developer onboarding includes successful policy testing

## Backend Specification

### RLS Policy Implementation

#### User Data Access Policies
```sql
-- Users can read their own profile data
CREATE POLICY "users_select_own" ON users
    FOR SELECT USING (auth.uid() = id);

-- Users can update their own profile data
CREATE POLICY "users_update_own" ON users
    FOR UPDATE USING (auth.uid() = id);

-- Public profile information visible to authenticated users
CREATE POLICY "users_select_public" ON users
    FOR SELECT USING (
        auth.role() = 'authenticated' AND 
        (id IN (
            SELECT user_id FROM user_profiles 
            WHERE privacy_level = 'public'
        ) OR auth.uid() = id)
    );

-- User profiles access control
CREATE POLICY "user_profiles_own_access" ON user_profiles
    FOR ALL USING (auth.uid() = user_id);

-- Public profiles visible based on privacy settings
CREATE POLICY "user_profiles_public_read" ON user_profiles
    FOR SELECT USING (
        auth.role() = 'authenticated' AND 
        (privacy_level = 'public' OR auth.uid() = user_id)
    );
```

#### Activity Access Policies
```sql
-- Activity hosts can manage their activities
CREATE POLICY "activities_host_full_access" ON activities
    FOR ALL USING (auth.uid() = host_id);

-- Public activities visible to authenticated users
CREATE POLICY "activities_public_read" ON activities
    FOR SELECT USING (
        auth.role() = 'authenticated' AND 
        visibility = 'public' AND 
        status = 'active'
    );

-- Private activities only visible to invited users
CREATE POLICY "activities_private_read" ON activities
    FOR SELECT USING (
        auth.uid() = host_id OR 
        (visibility = 'private' AND EXISTS (
            SELECT 1 FROM rsvps 
            WHERE activity_id = activities.id 
            AND user_id = auth.uid()
        ))
    );

-- Invite-only activities for invited users
CREATE POLICY "activities_invite_only_read" ON activities
    FOR SELECT USING (
        auth.uid() = host_id OR 
        (visibility = 'invite_only' AND EXISTS (
            SELECT 1 FROM rsvps 
            WHERE activity_id = activities.id 
            AND user_id = auth.uid()
            AND status IN ('confirmed', 'pending')
        ))
    );
```

#### RSVP Access Policies
```sql
-- Users can manage their own RSVPs
CREATE POLICY "rsvps_user_own_access" ON rsvps
    FOR ALL USING (auth.uid() = user_id);

-- Activity hosts can view RSVPs for their activities
CREATE POLICY "rsvps_host_read" ON rsvps
    FOR SELECT USING (
        EXISTS (
            SELECT 1 FROM activities 
            WHERE id = rsvps.activity_id 
            AND host_id = auth.uid()
        )
    );

-- Users can create RSVPs for visible activities
CREATE POLICY "rsvps_create_for_visible_activities" ON rsvps
    FOR INSERT WITH CHECK (
        auth.uid() = user_id AND
        EXISTS (
            SELECT 1 FROM activities 
            WHERE id = activity_id 
            AND (
                visibility = 'public' OR 
                host_id = auth.uid() OR
                (visibility IN ('private', 'invite_only') AND EXISTS (
                    SELECT 1 FROM rsvps r2 
                    WHERE r2.activity_id = activity_id 
                    AND r2.user_id = auth.uid()
                ))
            )
        )
    );
```

#### Social Interaction Policies
```sql
-- Users can manage their own social connections
CREATE POLICY "follow_relationships_own_access" ON follow_relationships
    FOR ALL USING (
        auth.uid() = follower_id OR 
        auth.uid() = following_id
    );

-- Comments visible based on activity visibility
CREATE POLICY "comments_activity_visibility" ON comments
    FOR SELECT USING (
        EXISTS (
            SELECT 1 FROM activities 
            WHERE id = comments.activity_id 
            AND (
                visibility = 'public' OR 
                host_id = auth.uid() OR
                EXISTS (
                    SELECT 1 FROM rsvps 
                    WHERE activity_id = activities.id 
                    AND user_id = auth.uid()
                )
            )
        )
    );

-- Users can create comments on activities they can see
CREATE POLICY "comments_create_on_visible_activities" ON comments
    FOR INSERT WITH CHECK (
        auth.uid() = user_id AND
        EXISTS (
            SELECT 1 FROM activities 
            WHERE id = activity_id 
            AND (
                visibility = 'public' OR 
                host_id = auth.uid() OR
                EXISTS (
                    SELECT 1 FROM rsvps 
                    WHERE activity_id = activities.id 
                    AND user_id = auth.uid()
                )
            )
        )
    );

-- Users can update/delete their own comments
CREATE POLICY "comments_own_modify" ON comments
    FOR UPDATE USING (auth.uid() = user_id);

CREATE POLICY "comments_own_delete" ON comments
    FOR DELETE USING (auth.uid() = user_id);
```

#### Financial Data Policies
```sql
-- Users can only access their own payment methods
CREATE POLICY "payment_methods_own_access" ON payment_methods
    FOR ALL USING (auth.uid() = user_id);

-- Transaction access for involved parties
CREATE POLICY "transactions_participant_access" ON transactions
    FOR SELECT USING (
        auth.uid() = payer_id OR 
        auth.uid() = recipient_id OR
        auth.jwt() ->> 'role' = 'admin'
    );

-- Users can create transactions as payer
CREATE POLICY "transactions_create_as_payer" ON transactions
    FOR INSERT WITH CHECK (auth.uid() = payer_id);

-- Only system can update transaction status
CREATE POLICY "transactions_system_update" ON transactions
    FOR UPDATE USING (auth.jwt() ->> 'role' = 'service_role');
```

#### Administrative Access Policies
```sql
-- Admin users can access user data for moderation
CREATE POLICY "users_admin_access" ON users
    FOR SELECT USING (
        auth.jwt() ->> 'role' IN ('admin', 'moderator')
    );

-- Admin users can access activities for moderation
CREATE POLICY "activities_admin_access" ON activities
    FOR ALL USING (
        auth.jwt() ->> 'role' IN ('admin', 'moderator')
    );

-- Audit logs readable by security team
CREATE POLICY "audit_logs_security_read" ON audit_logs
    FOR SELECT USING (
        auth.jwt() ->> 'role' IN ('admin', 'security') OR
        auth.uid() = user_id
    );

-- Only system can write audit logs
CREATE POLICY "audit_logs_system_write" ON audit_logs
    FOR INSERT WITH CHECK (auth.jwt() ->> 'role' = 'service_role');
```

### Policy Testing Framework
```sql
-- Test policy as specific user
CREATE OR REPLACE FUNCTION test_policy_as_user(user_uuid UUID)
RETURNS VOID AS $$
BEGIN
    -- Set user context for testing
    PERFORM set_config('request.jwt.claims', 
        json_build_object('sub', user_uuid, 'role', 'authenticated')::text, 
        true);
END;
$$ LANGUAGE plpgsql;

-- Reset to default context
CREATE OR REPLACE FUNCTION reset_policy_context()
RETURNS VOID AS $$
BEGIN
    PERFORM set_config('request.jwt.claims', '{}', true);
END;
$$ LANGUAGE plpgsql;
```

## Frontend Specification

### Frontend RLS Integration

#### Authentication Context
```typescript
// RLS-aware query context
interface AuthenticatedQuery {
  userId: string;
  userRole: 'authenticated' | 'admin' | 'moderator';
  isAuthenticated: boolean;
}

// Supabase client with RLS context
const createAuthenticatedClient = (session: Session) => {
  return createClient(
    process.env.NEXT_PUBLIC_SUPABASE_URL!,
    process.env.NEXT_PUBLIC_SUPABASE_ANON_KEY!,
    {
      global: {
        headers: {
          Authorization: `Bearer ${session.access_token}`
        }
      }
    }
  );
};
```

#### RLS-Aware Query Patterns
```typescript
// Activity queries respect RLS automatically
const fetchUserActivities = async (userId: string) => {
  const { data, error } = await supabase
    .from('activities')
    .select('*')
    .eq('host_id', userId); // RLS ensures user can only see their own
  
  return { data, error };
};

// Public activities automatically filtered by RLS
const fetchPublicActivities = async () => {
  const { data, error } = await supabase
    .from('activities')
    .select('*')
    .order('start_date', { ascending: true }); // RLS filters to public only
  
  return { data, error };
};

// User profile with privacy respect
const fetchUserProfile = async (userId: string) => {
  const { data, error } = await supabase
    .from('user_profiles')
    .select('*')
    .eq('user_id', userId)
    .single(); // RLS ensures privacy settings are respected
  
  return { data, error };
};
```

#### Error Handling for RLS
```typescript
// Handle RLS policy violations gracefully
const handleRLSError = (error: PostgrestError) => {
  if (error.code === 'PGRST116') {
    // RLS policy violation
    return {
      type: 'access_denied',
      message: 'You do not have permission to access this resource',
      userMessage: 'This content is not available to you'
    };
  }
  
  return {
    type: 'unknown_error',
    message: error.message,
    userMessage: 'An unexpected error occurred'
  };
};
```

### Real-time Subscriptions with RLS
```typescript
// RLS automatically filters real-time subscriptions
const subscribeToActivityComments = (activityId: string) => {
  return supabase
    .channel(`activity-${activityId}-comments`)
    .on('postgres_changes', {
      event: '*',
      schema: 'public',
      table: 'comments',
      filter: `activity_id=eq.${activityId}`
    }, (payload) => {
      // Only comments user can see will be received
      handleCommentUpdate(payload);
    })
    .subscribe();
};
```

## Third-Party Services Specification

### Supabase RLS Configuration

#### Enable RLS on All Tables
```sql
-- Enable RLS on all application tables
ALTER TABLE users ENABLE ROW LEVEL SECURITY;
ALTER TABLE user_profiles ENABLE ROW LEVEL SECURITY;
ALTER TABLE activities ENABLE ROW LEVEL SECURITY;
ALTER TABLE activity_categories ENABLE ROW LEVEL SECURITY;
ALTER TABLE rsvps ENABLE ROW LEVEL SECURITY;
ALTER TABLE comments ENABLE ROW LEVEL SECURITY;
ALTER TABLE follow_relationships ENABLE ROW LEVEL SECURITY;
ALTER TABLE payment_methods ENABLE ROW LEVEL SECURITY;
ALTER TABLE transactions ENABLE ROW LEVEL SECURITY;
ALTER TABLE audit_logs ENABLE ROW LEVEL SECURITY;
```

#### Service Role Bypass
```sql
-- Service role can bypass RLS for system operations
GRANT USAGE ON SCHEMA public TO service_role;
GRANT ALL ON ALL TABLES IN SCHEMA public TO service_role;
GRANT ALL ON ALL SEQUENCES IN SCHEMA public TO service_role;
```

### External Service Integration

#### Admin Dashboard Integration
- Admin users get elevated JWT claims for moderation access
- Separate admin authentication flow with enhanced permissions
- Audit logging for all administrative actions

#### Analytics Service Integration
- Analytics queries use service role for unrestricted access
- Aggregated data respects user privacy settings
- No individual user data exposed in analytics

## Implementation Sequence

### Phase 1: Core User Policies (1 hour)
1. **User Data Policies** (30 min)
   - Implement user and user_profile policies
   - Test user data access and privacy controls

2. **Social Graph Policies** (30 min)
   - Implement follow_relationships policies
   - Test social connection privacy

### Phase 2: Activity and RSVP Policies (1 hour)
1. **Activity Access Control** (30 min)
   - Implement activity visibility policies
   - Test public, private, and invite-only access

2. **RSVP Management** (30 min)
   - Implement RSVP access policies
   - Test RSVP creation and host access

### Phase 3: Social and Financial Policies (1 hour)
1. **Social Interaction Security** (30 min)
   - Implement comment and reaction policies
   - Test social feature access control

2. **Financial Data Protection** (30 min)
   - Implement payment and transaction policies
   - Test financial data security

### Phase 4: Testing and Validation (30 minutes)
1. **Comprehensive Testing** (20 min)
   - Test all policy scenarios
   - Validate security requirements

2. **Performance Validation** (10 min)
   - Ensure policies don't impact performance
   - Optimize policy queries if needed

## Quality Assurance

### Security Testing Checklist
- [ ] Users cannot access other users' private data
- [ ] Activity visibility rules are enforced correctly
- [ ] Financial data is properly protected
- [ ] Admin access is properly restricted
- [ ] Real-time subscriptions respect RLS policies
- [ ] Policy violations return appropriate errors

### Performance Testing
- [ ] RLS policies add < 10% query overhead
- [ ] Complex policies are optimized with proper indexes
- [ ] Real-time subscriptions perform well with RLS
- [ ] Policy evaluation doesn't cause query timeouts

### Compliance Validation
- [ ] GDPR privacy requirements are met
- [ ] Financial data protection standards are met
- [ ] Audit logging captures all required events
- [ ] Data access patterns match privacy policies

---

**Planning Status**: ✅ Complete
**Implementation Ready**: Yes
**Estimated Total Time**: 3-4 hours
**Next Phase**: Implementation (04-implementation-enhanced.md)
