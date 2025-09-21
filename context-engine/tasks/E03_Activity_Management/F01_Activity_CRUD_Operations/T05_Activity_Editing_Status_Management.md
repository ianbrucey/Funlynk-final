# T05 Activity Editing & Status Management

## Problem Definition

### Task Overview
Implement comprehensive activity editing capabilities and status management system. This includes building interfaces for hosts to modify their activities, manage activity lifecycle states, handle participant notifications, and ensure data consistency throughout the editing process.

### Problem Statement
Activity hosts need robust editing capabilities to:
- **Update activity details**: Modify information while preserving participant commitments
- **Manage activity status**: Control publication, cancellation, and completion states
- **Handle participant impact**: Notify participants of significant changes appropriately
- **Maintain data integrity**: Ensure edits don't break existing RSVPs or system state
- **Provide flexibility**: Allow edits while respecting platform policies and participant rights

The system must balance host flexibility with participant protection and platform stability.

### Scope
**In Scope:**
- Activity editing interface with change tracking
- Status management system (draft, published, cancelled, completed)
- Participant notification system for activity changes
- Edit validation and conflict resolution
- Activity history and audit logging
- Bulk editing capabilities for hosts with multiple activities
- Cancellation workflows with participant handling

**Out of Scope:**
- Advanced scheduling features (recurring events handled separately)
- Payment refund processing (handled by E06)
- Complex approval workflows (handled by E07)
- Social features integration (handled by E05)

### Success Criteria
- [ ] Activity editing maintains 99%+ data consistency
- [ ] Status changes trigger appropriate notifications within 30 seconds
- [ ] Edit validation prevents 95%+ of invalid state changes
- [ ] Participant notification system achieves 98%+ delivery rate
- [ ] Editing interface achieves 90%+ user satisfaction
- [ ] System handles concurrent edits without data corruption

### Dependencies
- **Requires**: T02 Activity management APIs for edit operations
- **Requires**: T03 Activity creation components for form reuse
- **Requires**: T04 Image management for photo editing
- **Requires**: E01.F04 Notification service for participant alerts
- **Requires**: F02 RSVP system for participant impact analysis
- **Blocks**: Complete activity management workflow
- **Informs**: E07 Administration (audit logging and moderation)

### Acceptance Criteria

#### Activity Editing Interface
- [ ] Intuitive editing form pre-populated with current activity data
- [ ] Change tracking with clear indication of modified fields
- [ ] Real-time validation with impact warnings for significant changes
- [ ] Draft mode for preparing changes before publication
- [ ] Bulk editing interface for hosts managing multiple activities

#### Status Management System
- [ ] Clear status transitions with business rule enforcement
- [ ] Status change confirmation with impact explanation
- [ ] Automatic status updates based on activity lifecycle
- [ ] Status history tracking with timestamps and reasons
- [ ] Emergency cancellation capabilities with immediate notifications

#### Participant Impact Management
- [ ] Change impact analysis showing affected participants
- [ ] Notification system for significant activity changes
- [ ] Participant consent for major changes (time, location, capacity)
- [ ] Automatic RSVP handling for cancelled activities
- [ ] Waitlist promotion when capacity increases

#### Data Integrity & Validation
- [ ] Edit validation preventing invalid activity states
- [ ] Concurrent edit detection and conflict resolution
- [ ] Data rollback capabilities for failed edits
- [ ] Audit logging for all activity modifications
- [ ] Change approval workflow for high-impact edits

#### Performance & Reliability
- [ ] Edit operations complete within 2 seconds
- [ ] Real-time updates propagate to all connected clients
- [ ] Offline edit support with sync when online
- [ ] Error recovery and partial edit preservation
- [ ] Performance optimization for large activity datasets

### Estimated Effort
**3-4 hours** for experienced full-stack developer

### Task Breakdown
1. **Backend Edit APIs & Validation** (90 minutes)
   - Implement activity update APIs with validation
   - Add status management and transition logic
   - Create change impact analysis system
   - Build audit logging and history tracking

2. **Frontend Editing Interface** (120 minutes)
   - Build activity editing form with change tracking
   - Implement status management controls
   - Create participant impact visualization
   - Add bulk editing capabilities

3. **Integration & Notifications** (60 minutes)
   - Integrate with notification system for participant alerts
   - Add real-time updates for activity changes
   - Implement conflict resolution and error handling
   - Create comprehensive testing and validation

### Deliverables
- [ ] Activity editing interface with change tracking
- [ ] Status management system with transition controls
- [ ] Participant notification system for activity changes
- [ ] Edit validation and conflict resolution
- [ ] Activity history and audit logging
- [ ] Bulk editing capabilities for multiple activities
- [ ] Real-time updates and synchronization
- [ ] Comprehensive testing and error handling
- [ ] Performance optimization and monitoring

### Technical Specifications

#### Status Management System
```typescript
enum ActivityStatus {
  DRAFT = 'draft',
  PUBLISHED = 'published',
  CANCELLED = 'cancelled',
  COMPLETED = 'completed',
}

interface StatusTransition {
  from: ActivityStatus;
  to: ActivityStatus;
  conditions: string[];
  notifications: NotificationType[];
  reversible: boolean;
}

const STATUS_TRANSITIONS: StatusTransition[] = [
  {
    from: ActivityStatus.DRAFT,
    to: ActivityStatus.PUBLISHED,
    conditions: ['has_required_fields', 'future_start_time'],
    notifications: ['followers', 'interested_users'],
    reversible: true,
  },
  {
    from: ActivityStatus.PUBLISHED,
    to: ActivityStatus.CANCELLED,
    conditions: ['host_permission'],
    notifications: ['participants', 'waitlist'],
    reversible: false,
  },
  {
    from: ActivityStatus.PUBLISHED,
    to: ActivityStatus.COMPLETED,
    conditions: ['past_end_time'],
    notifications: ['participants'],
    reversible: false,
  },
];

class ActivityStatusManager {
  async changeStatus(
    activityId: string,
    newStatus: ActivityStatus,
    reason?: string
  ): Promise<void> {
    const activity = await this.getActivity(activityId);
    const transition = this.validateTransition(activity.status, newStatus);
    
    if (!transition) {
      throw new Error(`Invalid status transition from ${activity.status} to ${newStatus}`);
    }
    
    // Check transition conditions
    await this.validateConditions(activity, transition.conditions);
    
    // Update status
    await this.updateActivityStatus(activityId, newStatus, reason);
    
    // Send notifications
    await this.sendStatusChangeNotifications(activity, transition.notifications);
    
    // Log status change
    await this.logStatusChange(activityId, activity.status, newStatus, reason);
  }
}
```

#### Change Impact Analysis
```typescript
interface ChangeImpact {
  field: string;
  oldValue: any;
  newValue: any;
  impactLevel: 'low' | 'medium' | 'high';
  affectedParticipants: number;
  requiresNotification: boolean;
  requiresConsent: boolean;
}

class ChangeImpactAnalyzer {
  analyzeChanges(
    originalActivity: Activity,
    updatedActivity: Activity
  ): ChangeImpact[] {
    const impacts: ChangeImpact[] = [];
    
    // Analyze each field for impact
    const fields = ['title', 'description', 'start_time', 'end_time', 'location', 'capacity'];
    
    for (const field of fields) {
      if (originalActivity[field] !== updatedActivity[field]) {
        impacts.push(this.analyzeFieldChange(field, originalActivity, updatedActivity));
      }
    }
    
    return impacts;
  }
  
  private analyzeFieldChange(
    field: string,
    original: Activity,
    updated: Activity
  ): ChangeImpact {
    const impactRules = {
      title: { level: 'low', notification: true, consent: false },
      description: { level: 'low', notification: true, consent: false },
      start_time: { level: 'high', notification: true, consent: true },
      end_time: { level: 'medium', notification: true, consent: false },
      location: { level: 'high', notification: true, consent: true },
      capacity: { level: 'medium', notification: true, consent: false },
    };
    
    const rule = impactRules[field];
    
    return {
      field,
      oldValue: original[field],
      newValue: updated[field],
      impactLevel: rule.level,
      affectedParticipants: original.current_participants,
      requiresNotification: rule.notification,
      requiresConsent: rule.consent,
    };
  }
}
```

#### Editing Interface Components
```typescript
interface ActivityEditProps {
  activityId: string;
  onSave: (activity: Activity) => void;
  onCancel: () => void;
}

const ActivityEdit: React.FC<ActivityEditProps> = ({ activityId, onSave, onCancel }) => {
  const [activity, setActivity] = useState<Activity>();
  const [originalActivity, setOriginalActivity] = useState<Activity>();
  const [changes, setChanges] = useState<ChangeImpact[]>([]);
  const [isAnalyzing, setIsAnalyzing] = useState(false);
  const [showImpactWarning, setShowImpactWarning] = useState(false);
  
  const analyzeChanges = useMemo(
    () => debounce(async (updated: Activity) => {
      if (!originalActivity) return;
      
      setIsAnalyzing(true);
      const impacts = await changeImpactAnalyzer.analyzeChanges(originalActivity, updated);
      setChanges(impacts);
      setShowImpactWarning(impacts.some(i => i.impactLevel === 'high'));
      setIsAnalyzing(false);
    }, 500),
    [originalActivity]
  );
  
  const handleFieldChange = (field: string, value: any) => {
    const updated = { ...activity, [field]: value };
    setActivity(updated);
    analyzeChanges(updated);
  };
  
  const handleSave = async () => {
    if (!activity) return;
    
    // Show impact confirmation if needed
    if (showImpactWarning) {
      const confirmed = await showChangeConfirmation(changes);
      if (!confirmed) return;
    }
    
    try {
      const savedActivity = await activityService.updateActivity(activity);
      onSave(savedActivity);
    } catch (error) {
      // Handle save errors
    }
  };
  
  return (
    <ScrollView>
      <ActivityEditForm
        activity={activity}
        onChange={handleFieldChange}
        changes={changes}
      />
      
      {showImpactWarning && (
        <ChangeImpactWarning
          impacts={changes.filter(c => c.impactLevel === 'high')}
          onDismiss={() => setShowImpactWarning(false)}
        />
      )}
      
      <StatusManagementControls
        currentStatus={activity?.status}
        onStatusChange={(status, reason) => handleStatusChange(status, reason)}
      />
      
      <View style={styles.actions}>
        <Button title="Cancel" onPress={onCancel} variant="secondary" />
        <Button
          title="Save Changes"
          onPress={handleSave}
          disabled={changes.length === 0 || isAnalyzing}
        />
      </View>
    </ScrollView>
  );
};
```

#### Audit Logging System
```typescript
interface ActivityAuditLog {
  id: string;
  activity_id: string;
  user_id: string;
  action: 'created' | 'updated' | 'status_changed' | 'deleted';
  changes: Record<string, { old: any; new: any }>;
  reason?: string;
  ip_address?: string;
  user_agent?: string;
  created_at: Date;
}

class ActivityAuditLogger {
  async logActivityChange(
    activityId: string,
    userId: string,
    action: string,
    changes: Record<string, any>,
    reason?: string
  ): Promise<void> {
    const auditEntry: Omit<ActivityAuditLog, 'id' | 'created_at'> = {
      activity_id: activityId,
      user_id: userId,
      action: action as any,
      changes,
      reason,
      ip_address: await this.getClientIP(),
      user_agent: await this.getUserAgent(),
    };
    
    await supabase
      .from('activity_audit_logs')
      .insert(auditEntry);
  }
  
  async getActivityHistory(activityId: string): Promise<ActivityAuditLog[]> {
    const { data, error } = await supabase
      .from('activity_audit_logs')
      .select('*')
      .eq('activity_id', activityId)
      .order('created_at', { ascending: false });
    
    if (error) throw error;
    return data;
  }
}
```

### Quality Checklist
- [ ] Edit validation prevents invalid activity states
- [ ] Status transitions follow business rules correctly
- [ ] Participant notifications are timely and accurate
- [ ] Change impact analysis provides helpful warnings
- [ ] Concurrent edit handling prevents data corruption
- [ ] Audit logging captures all significant changes
- [ ] Performance optimized for large datasets
- [ ] Error handling provides clear recovery paths

---

**Status**: 🔄 Ready for Problem Definition Phase  
**Assignee**: Full-Stack Developer  
**Epic**: E03 Activity Management  
**Feature**: F01 Activity CRUD Operations  
**Dependencies**: T02 Activity APIs, T03 Creation Components, T04 Image Management, Notification Service  
**Blocks**: Complete Activity Management Workflow
