# T06 Participant Management & Host Tools

## Problem Definition

### Task Overview
Implement comprehensive participant management tools for activity hosts, including participant overview, communication capabilities, RSVP management, attendance tracking, and post-activity follow-up. This provides hosts with everything they need to successfully manage their activities and participants.

### Problem Statement
Activity hosts need powerful management tools to:
- **Oversee participants**: View and manage all RSVPs, waitlists, and attendance in one place
- **Communicate effectively**: Send updates, reminders, and announcements to participants
- **Handle changes**: Manage capacity adjustments, cancellations, and special circumstances
- **Track success**: Monitor attendance, engagement, and activity performance
- **Build relationships**: Follow up with participants and encourage repeat attendance

The host experience must be intuitive and efficient while providing comprehensive control over activity management.

### Scope
**In Scope:**
- Comprehensive participant dashboard with all RSVP and attendance data
- Communication tools for participant messaging and updates
- Capacity and waitlist management with impact preview
- Attendance management and check-in tools
- Participant analytics and activity performance metrics
- Post-activity follow-up and feedback collection
- Bulk operations for efficient participant management

**Out of Scope:**
- Advanced CRM features (handled separately)
- Payment management for paid activities (handled by E06)
- Complex approval workflows (handled by E07)
- Social media integration (handled by E05)

### Success Criteria
- [ ] Host dashboard provides complete activity overview in under 3 seconds
- [ ] Participant communication achieves 95%+ delivery rate
- [ ] Bulk operations handle 500+ participants efficiently
- [ ] Host satisfaction with management tools exceeds 90%
- [ ] Activity performance insights drive 20%+ improvement in repeat hosting
- [ ] Post-activity follow-up increases participant retention by 15%

### Dependencies
- **Requires**: T02 RSVP backend APIs for participant data
- **Requires**: T04 Waitlist management for waitlist tools
- **Requires**: T05 Attendance tracking for attendance management
- **Requires**: E01.F04 Notification service for participant communication
- **Blocks**: Complete host experience and activity management workflow
- **Informs**: E07 Analytics (host behavior and activity success metrics)

### Acceptance Criteria

#### Participant Dashboard
- [ ] Complete participant overview with RSVP, waitlist, and attendance status
- [ ] Real-time participant count and capacity tracking
- [ ] Participant search, filtering, and sorting capabilities
- [ ] Individual participant profiles with history and notes
- [ ] Quick actions for common participant management tasks

#### Communication Tools
- [ ] Broadcast messaging to all participants or filtered groups
- [ ] Automated reminder system for upcoming activities
- [ ] Activity update notifications with change impact
- [ ] Individual participant messaging capabilities
- [ ] Message templates for common communications

#### Capacity & Waitlist Management
- [ ] Visual capacity management with drag-and-drop adjustments
- [ ] Waitlist promotion tools with batch operations
- [ ] Impact preview for capacity and timing changes
- [ ] Emergency capacity management for special circumstances
- [ ] Automated waitlist notifications and promotions

#### Attendance Management
- [ ] Live attendance tracking during activities
- [ ] Multiple check-in methods with host controls
- [ ] No-show management and follow-up tools
- [ ] Attendance analytics and patterns
- [ ] Post-activity attendance confirmation

#### Analytics & Insights
- [ ] Activity performance metrics and trends
- [ ] Participant engagement and retention analytics
- [ ] RSVP conversion and attendance rate tracking
- [ ] Host performance benchmarks and recommendations
- [ ] Comparative analytics across host's activities

### Estimated Effort
**3-4 hours** for experienced full-stack developer

### Task Breakdown
1. **Host Dashboard & Participant Overview** (120 minutes)
   - Build comprehensive participant dashboard
   - Create participant search, filtering, and management
   - Implement real-time status tracking and updates
   - Add individual participant profile management

2. **Communication & Management Tools** (90 minutes)
   - Build participant communication system
   - Create capacity and waitlist management tools
   - Implement bulk operations and batch processing
   - Add automated notification and reminder systems

3. **Analytics & Post-Activity Tools** (60 minutes)
   - Create host analytics dashboard
   - Build post-activity follow-up tools
   - Add performance insights and recommendations
   - Implement feedback collection and analysis

### Deliverables
- [ ] Comprehensive host participant dashboard
- [ ] Participant communication and messaging system
- [ ] Capacity and waitlist management tools
- [ ] Attendance management and check-in interface
- [ ] Host analytics and performance insights
- [ ] Post-activity follow-up and feedback tools
- [ ] Bulk operations for efficient participant management
- [ ] Real-time updates and notification system
- [ ] Mobile-optimized host management interface

### Technical Specifications

#### Host Dashboard Data Model
```sql
-- Host activity overview
CREATE VIEW host_activity_overview AS
SELECT 
  a.*,
  COUNT(DISTINCT r.id) FILTER (WHERE r.status = 'confirmed') as confirmed_count,
  COUNT(DISTINCT r.id) FILTER (WHERE r.status = 'waitlisted') as waitlisted_count,
  COUNT(DISTINCT ar.id) as checked_in_count,
  COUNT(DISTINCT ns.id) as no_show_count,
  AVG(CASE WHEN ar.id IS NOT NULL THEN 1 ELSE 0 END) as attendance_rate
FROM activities a
LEFT JOIN rsvps r ON a.id = r.activity_id
LEFT JOIN attendance_records ar ON r.id = ar.rsvp_id
LEFT JOIN no_show_records ns ON r.id = ns.rsvp_id
GROUP BY a.id;

-- Participant communication log
CREATE TABLE participant_communications (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  activity_id UUID REFERENCES activities(id) ON DELETE CASCADE,
  host_id UUID REFERENCES users(id) ON DELETE CASCADE,
  recipient_type VARCHAR(20) NOT NULL, -- 'all', 'confirmed', 'waitlisted', 'individual'
  recipient_ids UUID[],
  message_type VARCHAR(20) NOT NULL, -- 'update', 'reminder', 'announcement', 'followup'
  subject VARCHAR(200),
  message TEXT NOT NULL,
  delivery_method VARCHAR(20) DEFAULT 'push', -- 'push', 'email', 'sms'
  sent_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
  delivery_stats JSONB,
  created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Host activity analytics
CREATE TABLE host_activity_analytics (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  host_id UUID REFERENCES users(id) ON DELETE CASCADE,
  activity_id UUID REFERENCES activities(id) ON DELETE CASCADE,
  date DATE NOT NULL,
  rsvp_conversion_rate DECIMAL(5,4),
  attendance_rate DECIMAL(5,4),
  waitlist_promotion_rate DECIMAL(5,4),
  participant_satisfaction DECIMAL(3,2),
  repeat_participant_rate DECIMAL(5,4),
  created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
  UNIQUE(host_id, activity_id, date)
);
```

#### Host Dashboard Service
```typescript
class HostDashboardService {
  async getActivityOverview(activityId: string, hostId: string): Promise<ActivityOverview> {
    // Verify host ownership
    await this.verifyHostAccess(activityId, hostId);
    
    const { data, error } = await supabase
      .from('host_activity_overview')
      .select('*')
      .eq('id', activityId)
      .single();
    
    if (error) throw error;
    
    // Get recent participant activity
    const recentActivity = await this.getRecentParticipantActivity(activityId);
    
    return {
      ...data,
      recent_activity: recentActivity,
    };
  }
  
  async getParticipantList(
    activityId: string,
    hostId: string,
    filters: ParticipantFilters = {}
  ): Promise<ParticipantListItem[]> {
    await this.verifyHostAccess(activityId, hostId);
    
    let query = supabase
      .from('rsvps')
      .select(`
        *,
        users(id, name, email, profile_image_url),
        attendance_records(id, checked_in_at, status),
        no_show_records(id, marked_no_show_at)
      `)
      .eq('activity_id', activityId);
    
    // Apply filters
    if (filters.status) {
      query = query.eq('status', filters.status);
    }
    
    if (filters.attendance) {
      if (filters.attendance === 'checked_in') {
        query = query.not('attendance_records', 'is', null);
      } else if (filters.attendance === 'not_checked_in') {
        query = query.is('attendance_records', null);
      }
    }
    
    if (filters.search) {
      query = query.ilike('users.name', `%${filters.search}%`);
    }
    
    const { data, error } = await query.order('created_at', { ascending: true });
    
    if (error) throw error;
    return data;
  }
  
  async sendParticipantMessage(
    activityId: string,
    hostId: string,
    message: ParticipantMessage
  ): Promise<CommunicationResult> {
    await this.verifyHostAccess(activityId, hostId);
    
    // Get recipient list based on type
    const recipients = await this.getMessageRecipients(activityId, message.recipient_type, message.recipient_ids);
    
    // Create communication record
    const { data: communication } = await supabase
      .from('participant_communications')
      .insert({
        activity_id: activityId,
        host_id: hostId,
        recipient_type: message.recipient_type,
        recipient_ids: message.recipient_ids,
        message_type: message.message_type,
        subject: message.subject,
        message: message.message,
        delivery_method: message.delivery_method,
      })
      .select()
      .single();
    
    // Send notifications
    const deliveryResults = await this.deliverMessages(recipients, message);
    
    // Update delivery stats
    await supabase
      .from('participant_communications')
      .update({
        delivery_stats: {
          total_recipients: recipients.length,
          successful_deliveries: deliveryResults.successful,
          failed_deliveries: deliveryResults.failed,
          delivery_rate: deliveryResults.successful / recipients.length,
        },
      })
      .eq('id', communication.id);
    
    return {
      communication_id: communication.id,
      recipients_count: recipients.length,
      delivery_results: deliveryResults,
    };
  }
  
  async bulkUpdateParticipants(
    activityId: string,
    hostId: string,
    participantIds: string[],
    action: BulkAction
  ): Promise<BulkUpdateResult> {
    await this.verifyHostAccess(activityId, hostId);
    
    const results: BulkUpdateResult = {
      successful: 0,
      failed: 0,
      errors: [],
    };
    
    for (const participantId of participantIds) {
      try {
        switch (action.type) {
          case 'check_in':
            await attendanceService.manualCheckIn(activityId, participantId, hostId);
            break;
          case 'mark_no_show':
            await attendanceService.markNoShow(activityId, participantId);
            break;
          case 'promote_from_waitlist':
            await waitlistService.promoteParticipant(activityId, participantId);
            break;
          case 'send_message':
            await this.sendIndividualMessage(activityId, participantId, action.message);
            break;
        }
        results.successful++;
      } catch (error) {
        results.failed++;
        results.errors.push({
          participant_id: participantId,
          error: error.message,
        });
      }
    }
    
    return results;
  }
}
```

#### Host Dashboard Components
```typescript
interface HostDashboardProps {
  activityId: string;
}

const HostDashboard: React.FC<HostDashboardProps> = ({ activityId }) => {
  const { data: overview } = useActivityOverview(activityId);
  const { data: participants } = useParticipantList(activityId);
  const [selectedTab, setSelectedTab] = useState<DashboardTab>('overview');
  const [selectedParticipants, setSelectedParticipants] = useState<string[]>([]);
  
  const handleBulkAction = async (action: BulkAction) => {
    try {
      const result = await hostService.bulkUpdateParticipants(
        activityId,
        selectedParticipants,
        action
      );
      
      showNotification({
        type: 'success',
        title: 'Bulk Action Complete',
        message: `${result.successful} participants updated successfully`,
      });
      
      if (result.failed > 0) {
        showNotification({
          type: 'warning',
          title: 'Some Actions Failed',
          message: `${result.failed} actions failed. Check details for more info.`,
        });
      }
      
      setSelectedParticipants([]);
    } catch (error) {
      showNotification({
        type: 'error',
        title: 'Bulk Action Failed',
        message: error.message,
      });
    }
  };
  
  return (
    <View style={styles.container}>
      <ActivityOverviewHeader overview={overview} />
      
      <TabNavigation
        tabs={[
          { key: 'overview', label: 'Overview', icon: 'dashboard' },
          { key: 'participants', label: 'Participants', icon: 'people' },
          { key: 'communication', label: 'Messages', icon: 'message' },
          { key: 'analytics', label: 'Analytics', icon: 'chart' },
        ]}
        selected={selectedTab}
        onSelect={setSelectedTab}
      />
      
      {selectedTab === 'overview' && (
        <OverviewTab
          overview={overview}
          onQuickAction={(action) => handleQuickAction(action)}
        />
      )}
      
      {selectedTab === 'participants' && (
        <ParticipantsTab
          participants={participants}
          selectedParticipants={selectedParticipants}
          onSelectionChange={setSelectedParticipants}
          onBulkAction={handleBulkAction}
        />
      )}
      
      {selectedTab === 'communication' && (
        <CommunicationTab
          activityId={activityId}
          participants={participants}
        />
      )}
      
      {selectedTab === 'analytics' && (
        <AnalyticsTab activityId={activityId} />
      )}
    </View>
  );
};
```

#### Participant Communication Interface
```typescript
interface CommunicationTabProps {
  activityId: string;
  participants: ParticipantListItem[];
}

const CommunicationTab: React.FC<CommunicationTabProps> = ({
  activityId,
  participants,
}) => {
  const [messageType, setMessageType] = useState<MessageType>('update');
  const [recipientType, setRecipientType] = useState<RecipientType>('all');
  const [selectedRecipients, setSelectedRecipients] = useState<string[]>([]);
  const [message, setMessage] = useState('');
  const [subject, setSubject] = useState('');
  
  const { data: messageTemplates } = useMessageTemplates();
  const { data: communicationHistory } = useCommunicationHistory(activityId);
  
  const handleSendMessage = async () => {
    try {
      const result = await hostService.sendParticipantMessage(activityId, {
        recipient_type: recipientType,
        recipient_ids: recipientType === 'individual' ? selectedRecipients : undefined,
        message_type: messageType,
        subject,
        message,
        delivery_method: 'push',
      });
      
      showNotification({
        type: 'success',
        title: 'Message Sent',
        message: `Message delivered to ${result.recipients_count} participants`,
      });
      
      // Reset form
      setMessage('');
      setSubject('');
      setSelectedRecipients([]);
    } catch (error) {
      showNotification({
        type: 'error',
        title: 'Message Failed',
        message: error.message,
      });
    }
  };
  
  const getRecipientCount = (): number => {
    switch (recipientType) {
      case 'all':
        return participants.length;
      case 'confirmed':
        return participants.filter(p => p.status === 'confirmed').length;
      case 'waitlisted':
        return participants.filter(p => p.status === 'waitlisted').length;
      case 'individual':
        return selectedRecipients.length;
      default:
        return 0;
    }
  };
  
  return (
    <ScrollView style={styles.container}>
      <View style={styles.messageComposer}>
        <Text style={styles.sectionTitle}>Send Message</Text>
        
        <MessageTypeSelector
          value={messageType}
          onChange={setMessageType}
          options={[
            { key: 'update', label: 'Activity Update' },
            { key: 'reminder', label: 'Reminder' },
            { key: 'announcement', label: 'Announcement' },
            { key: 'followup', label: 'Follow-up' },
          ]}
        />
        
        <RecipientSelector
          value={recipientType}
          onChange={setRecipientType}
          participants={participants}
          selectedRecipients={selectedRecipients}
          onRecipientSelectionChange={setSelectedRecipients}
        />
        
        <Text style={styles.recipientCount}>
          {getRecipientCount()} recipients selected
        </Text>
        
        <TextInput
          style={styles.subjectInput}
          placeholder="Subject"
          value={subject}
          onChangeText={setSubject}
        />
        
        <TextInput
          style={styles.messageInput}
          placeholder="Message"
          value={message}
          onChangeText={setMessage}
          multiline
          numberOfLines={6}
        />
        
        <MessageTemplateSelector
          templates={messageTemplates}
          onSelect={(template) => {
            setSubject(template.subject);
            setMessage(template.message);
          }}
        />
        
        <Button
          title="Send Message"
          onPress={handleSendMessage}
          disabled={!message.trim() || getRecipientCount() === 0}
          variant="primary"
        />
      </View>
      
      <View style={styles.communicationHistory}>
        <Text style={styles.sectionTitle}>Message History</Text>
        <CommunicationHistoryList history={communicationHistory} />
      </View>
    </ScrollView>
  );
};
```

#### Host Analytics Dashboard
```typescript
interface AnalyticsTabProps {
  activityId: string;
}

const AnalyticsTab: React.FC<AnalyticsTabProps> = ({ activityId }) => {
  const { data: analytics } = useHostAnalytics(activityId);
  const { data: benchmarks } = useHostBenchmarks();
  
  return (
    <ScrollView style={styles.container}>
      <View style={styles.metricsGrid}>
        <MetricCard
          title="RSVP Conversion"
          value={`${(analytics?.rsvp_conversion_rate * 100).toFixed(1)}%`}
          trend={analytics?.rsvp_trend}
          benchmark={benchmarks?.rsvp_conversion_rate}
        />
        
        <MetricCard
          title="Attendance Rate"
          value={`${(analytics?.attendance_rate * 100).toFixed(1)}%`}
          trend={analytics?.attendance_trend}
          benchmark={benchmarks?.attendance_rate}
        />
        
        <MetricCard
          title="Repeat Participants"
          value={`${(analytics?.repeat_participant_rate * 100).toFixed(1)}%`}
          trend={analytics?.repeat_trend}
          benchmark={benchmarks?.repeat_participant_rate}
        />
        
        <MetricCard
          title="Satisfaction Score"
          value={analytics?.participant_satisfaction?.toFixed(1)}
          trend={analytics?.satisfaction_trend}
          benchmark={benchmarks?.participant_satisfaction}
        />
      </View>
      
      <View style={styles.charts}>
        <RSVPTrendChart data={analytics?.rsvp_timeline} />
        <AttendancePatternChart data={analytics?.attendance_patterns} />
        <ParticipantDemographicsChart data={analytics?.participant_demographics} />
      </View>
      
      <View style={styles.insights}>
        <Text style={styles.sectionTitle}>Insights & Recommendations</Text>
        <InsightsList insights={analytics?.insights} />
      </View>
    </ScrollView>
  );
};
```

### Quality Checklist
- [ ] Dashboard provides comprehensive activity overview
- [ ] Participant management tools are intuitive and efficient
- [ ] Communication system achieves high delivery rates
- [ ] Bulk operations handle large participant lists smoothly
- [ ] Analytics provide actionable insights for hosts
- [ ] Real-time updates keep information current
- [ ] Mobile interface is optimized for host usage
- [ ] Performance optimized for activities with 500+ participants

---

**Status**: 🔄 Ready for Problem Definition Phase  
**Assignee**: Full-Stack Developer  
**Epic**: E03 Activity Management  
**Feature**: F02 RSVP & Attendance System  
**Dependencies**: T02 RSVP APIs, T04 Waitlist Management, T05 Attendance Tracking, Notification Service  
**Blocks**: Complete Host Experience and Activity Management Workflow
