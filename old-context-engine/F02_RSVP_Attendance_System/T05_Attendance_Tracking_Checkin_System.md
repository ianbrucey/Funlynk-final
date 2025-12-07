# T05 Attendance Tracking & Check-in System

## Problem Definition

### Task Overview
Implement comprehensive attendance tracking and check-in system that enables hosts to verify participant attendance through multiple methods (QR codes, manual check-in, location verification) while providing participants with seamless check-in experiences and attendance confirmation.

### Problem Statement
Activities need reliable attendance tracking to:
- **Verify participation**: Confirm who actually attended activities for analytics and reputation
- **Enable host management**: Give hosts tools to track attendance and manage no-shows
- **Support participant experience**: Provide easy check-in methods and attendance confirmation
- **Generate insights**: Collect attendance data for activity success metrics and recommendations
- **Handle edge cases**: Manage late arrivals, early departures, and special circumstances

The system must balance ease of use with accuracy and fraud prevention.

### Scope
**In Scope:**
- Multiple check-in methods (QR codes, manual, location-based)
- Real-time attendance tracking and status updates
- No-show detection and management
- Attendance analytics and reporting
- Late arrival and early departure handling
- Host attendance management tools
- Participant attendance history and verification

**Out of Scope:**
- Advanced biometric verification (future enhancement)
- Complex attendance policies and penalties (basic no-show tracking only)
- Integration with external attendance systems (handled separately)
- Payment-related attendance features (handled by E06)

### Success Criteria
- [ ] Check-in process completes in under 30 seconds
- [ ] QR code check-in achieves 98%+ success rate
- [ ] Attendance tracking accuracy of 95%+
- [ ] No-show detection within 15 minutes of activity start
- [ ] Host attendance tools achieve 90%+ satisfaction
- [ ] System handles 500+ simultaneous check-ins

### Dependencies
- **Requires**: T02 RSVP backend APIs for participant data
- **Requires**: T03 RSVP frontend components for integration
- **Requires**: E01.F03 Geolocation service for location verification
- **Requires**: QR code generation and scanning libraries
- **Blocks**: Complete activity lifecycle management
- **Informs**: E07 Analytics (attendance data for insights)

### Acceptance Criteria

#### Check-in Methods
- [ ] QR code generation and scanning for quick check-in
- [ ] Manual check-in interface for hosts
- [ ] Location-based check-in with proximity verification
- [ ] Bulk check-in capabilities for group activities
- [ ] Offline check-in with sync when online

#### Attendance Status Management
- [ ] Real-time attendance status updates
- [ ] Check-in time tracking with timestamps
- [ ] Late arrival detection and handling
- [ ] Early departure tracking (optional)
- [ ] No-show identification and marking

#### Host Management Tools
- [ ] Live attendance dashboard during activities
- [ ] Participant check-in list with search and filters
- [ ] Manual attendance override capabilities
- [ ] Attendance summary and reporting
- [ ] No-show management and communication tools

#### Participant Experience
- [ ] Easy check-in process with clear instructions
- [ ] Check-in confirmation with activity access
- [ ] Attendance history and verification
- [ ] Late check-in capabilities with host approval
- [ ] Check-in troubleshooting and support

#### Analytics & Reporting
- [ ] Attendance rate tracking and trends
- [ ] No-show pattern analysis
- [ ] Check-in method effectiveness metrics
- [ ] Host attendance management analytics
- [ ] Participant attendance history and reputation

### Estimated Effort
**3-4 hours** for experienced full-stack developer

### Task Breakdown
1. **Check-in System Backend** (90 minutes)
   - Implement attendance tracking data model
   - Build check-in APIs with multiple verification methods
   - Create no-show detection and management logic
   - Add attendance analytics and reporting

2. **Check-in Frontend Components** (120 minutes)
   - Build QR code generation and scanning components
   - Create host attendance management interface
   - Implement participant check-in flow
   - Add attendance status tracking and history

3. **Integration & Optimization** (60 minutes)
   - Integrate with RSVP system for participant data
   - Add real-time attendance updates
   - Optimize performance for large activities
   - Create comprehensive testing and validation

### Deliverables
- [ ] Multi-method check-in system (QR, manual, location)
- [ ] Real-time attendance tracking and status updates
- [ ] Host attendance management dashboard
- [ ] Participant check-in interface and history
- [ ] No-show detection and management system
- [ ] Attendance analytics and reporting
- [ ] Offline check-in capabilities with synchronization
- [ ] Performance optimization for large activities
- [ ] Comprehensive testing and edge case handling

### Technical Specifications

#### Attendance Data Model
```sql
-- Attendance tracking table
CREATE TABLE attendance_records (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  activity_id UUID REFERENCES activities(id) ON DELETE CASCADE,
  user_id UUID REFERENCES users(id) ON DELETE CASCADE,
  rsvp_id UUID REFERENCES rsvps(id) ON DELETE CASCADE,
  check_in_method VARCHAR(20) NOT NULL CHECK (check_in_method IN ('qr_code', 'manual', 'location', 'bulk')),
  checked_in_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
  checked_in_by UUID REFERENCES users(id) ON DELETE SET NULL, -- Host who checked them in
  location_verified BOOLEAN DEFAULT false,
  check_in_location GEOGRAPHY(POINT),
  status VARCHAR(20) DEFAULT 'present' CHECK (status IN ('present', 'late', 'left_early', 'no_show')),
  notes TEXT,
  metadata JSONB,
  created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
  updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
  UNIQUE(activity_id, user_id)
);

-- QR codes for check-in
CREATE TABLE activity_checkin_codes (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  activity_id UUID REFERENCES activities(id) ON DELETE CASCADE,
  code_data TEXT NOT NULL UNIQUE,
  expires_at TIMESTAMP WITH TIME ZONE NOT NULL,
  usage_count INTEGER DEFAULT 0,
  max_usage INTEGER,
  is_active BOOLEAN DEFAULT true,
  created_by UUID REFERENCES users(id),
  created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- No-show tracking
CREATE TABLE no_show_records (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  activity_id UUID REFERENCES activities(id) ON DELETE CASCADE,
  user_id UUID REFERENCES users(id) ON DELETE CASCADE,
  rsvp_id UUID REFERENCES rsvps(id) ON DELETE CASCADE,
  marked_no_show_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
  grace_period_minutes INTEGER DEFAULT 15,
  reason VARCHAR(50), -- 'auto_detected', 'manual', 'host_marked'
  notified BOOLEAN DEFAULT false,
  created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);
```

#### Attendance Service
```typescript
class AttendanceService {
  async generateCheckInCode(
    activityId: string,
    hostId: string,
    expirationMinutes: number = 60
  ): Promise<CheckInCode> {
    const codeData = this.generateSecureCode();
    const expiresAt = new Date(Date.now() + expirationMinutes * 60 * 1000);
    
    const { data, error } = await supabase
      .from('activity_checkin_codes')
      .insert({
        activity_id: activityId,
        code_data: codeData,
        expires_at: expiresAt,
        created_by: hostId,
      })
      .select()
      .single();
    
    if (error) throw error;
    return data;
  }
  
  async checkInParticipant(
    activityId: string,
    userId: string,
    method: CheckInMethod,
    options: CheckInOptions = {}
  ): Promise<AttendanceRecord> {
    return await this.withTransaction(async (client) => {
      // Verify participant has valid RSVP
      const rsvp = await client.query(`
        SELECT * FROM rsvps
        WHERE activity_id = $1 AND user_id = $2 AND status = 'confirmed'
      `, [activityId, userId]);
      
      if (rsvp.rows.length === 0) {
        throw new Error('No valid RSVP found for this participant');
      }
      
      // Check if already checked in
      const existingRecord = await client.query(`
        SELECT * FROM attendance_records
        WHERE activity_id = $1 AND user_id = $2
      `, [activityId, userId]);
      
      if (existingRecord.rows.length > 0) {
        throw new Error('Participant already checked in');
      }
      
      // Determine attendance status based on timing
      const activity = await this.getActivity(activityId);
      const status = this.determineAttendanceStatus(
        activity.start_time,
        new Date(),
        options.gracePeriodMinutes || 15
      );
      
      // Create attendance record
      const attendanceRecord = await client.query(`
        INSERT INTO attendance_records (
          activity_id, user_id, rsvp_id, check_in_method,
          checked_in_by, location_verified, check_in_location,
          status, notes
        ) VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9)
        RETURNING *
      `, [
        activityId,
        userId,
        rsvp.rows[0].id,
        method,
        options.checkedInBy,
        options.locationVerified || false,
        options.location,
        status,
        options.notes,
      ]);
      
      // Update RSVP check-in status
      await client.query(`
        UPDATE rsvps
        SET checked_in = true, check_in_time = NOW()
        WHERE id = $1
      `, [rsvp.rows[0].id]);
      
      // Update activity checked-in count
      await client.query(`
        UPDATE activities
        SET checked_in_participants = checked_in_participants + 1
        WHERE id = $1
      `, [activityId]);
      
      return attendanceRecord.rows[0];
    });
  }
  
  async detectNoShows(activityId: string): Promise<NoShowRecord[]> {
    const activity = await this.getActivity(activityId);
    const gracePeriod = 15; // minutes
    const cutoffTime = new Date(
      activity.start_time.getTime() + gracePeriod * 60 * 1000
    );
    
    if (new Date() < cutoffTime) {
      return []; // Too early to detect no-shows
    }
    
    // Find confirmed RSVPs without attendance records
    const noShows = await supabase
      .from('rsvps')
      .select(`
        *,
        users(id, name, email),
        attendance_records(id)
      `)
      .eq('activity_id', activityId)
      .eq('status', 'confirmed')
      .is('attendance_records.id', null);
    
    const noShowRecords: NoShowRecord[] = [];
    
    for (const rsvp of noShows.data || []) {
      // Create no-show record
      const noShowRecord = await supabase
        .from('no_show_records')
        .insert({
          activity_id: activityId,
          user_id: rsvp.user_id,
          rsvp_id: rsvp.id,
          reason: 'auto_detected',
          grace_period_minutes: gracePeriod,
        })
        .select()
        .single();
      
      if (noShowRecord.data) {
        noShowRecords.push(noShowRecord.data);
      }
    }
    
    return noShowRecords;
  }
  
  private determineAttendanceStatus(
    activityStart: Date,
    checkInTime: Date,
    gracePeriodMinutes: number
  ): AttendanceStatus {
    const timeDiff = checkInTime.getTime() - activityStart.getTime();
    const gracePeriodMs = gracePeriodMinutes * 60 * 1000;
    
    if (timeDiff <= 0) {
      return 'present'; // On time or early
    } else if (timeDiff <= gracePeriodMs) {
      return 'present'; // Within grace period
    } else {
      return 'late'; // Late arrival
    }
  }
  
  private generateSecureCode(): string {
    // Generate cryptographically secure random code
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    let result = '';
    for (let i = 0; i < 8; i++) {
      result += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    return result;
  }
}
```

#### QR Code Check-in Components
```typescript
interface QRCheckInProps {
  activityId: string;
  isHost: boolean;
  onCheckInComplete: (result: CheckInResult) => void;
}

const QRCheckIn: React.FC<QRCheckInProps> = ({
  activityId,
  isHost,
  onCheckInComplete,
}) => {
  const [showScanner, setShowScanner] = useState(false);
  const [checkInCode, setCheckInCode] = useState<CheckInCode>();
  
  // Generate QR code for hosts
  const generateQRCode = async () => {
    try {
      const code = await attendanceService.generateCheckInCode(activityId);
      setCheckInCode(code);
    } catch (error) {
      showNotification({
        type: 'error',
        title: 'Failed to Generate Code',
        message: error.message,
      });
    }
  };
  
  // Handle QR code scan
  const handleQRScan = async (data: string) => {
    try {
      const result = await attendanceService.checkInWithQR(activityId, data);
      onCheckInComplete(result);
      setShowScanner(false);
      
      showNotification({
        type: 'success',
        title: 'Check-in Successful',
        message: `${result.participantName} checked in successfully`,
      });
    } catch (error) {
      showNotification({
        type: 'error',
        title: 'Check-in Failed',
        message: error.message,
      });
    }
  };
  
  if (isHost) {
    return (
      <View style={styles.hostContainer}>
        <Text style={styles.title}>Check-in QR Code</Text>
        
        {checkInCode ? (
          <View style={styles.qrContainer}>
            <QRCode
              value={checkInCode.code_data}
              size={200}
              backgroundColor="white"
              color="black"
            />
            <Text style={styles.codeText}>
              Code: {checkInCode.code_data}
            </Text>
            <Text style={styles.expiryText}>
              Expires: {formatTime(checkInCode.expires_at)}
            </Text>
          </View>
        ) : (
          <Button
            title="Generate Check-in Code"
            onPress={generateQRCode}
            variant="primary"
          />
        )}
        
        <Button
          title="Scan Participant Code"
          onPress={() => setShowScanner(true)}
          variant="secondary"
          style={styles.scanButton}
        />
        
        <QRScanner
          visible={showScanner}
          onScan={handleQRScan}
          onClose={() => setShowScanner(false)}
        />
      </View>
    );
  }
  
  // Participant view
  return (
    <View style={styles.participantContainer}>
      <Text style={styles.title}>Check-in</Text>
      <Text style={styles.instructions}>
        Scan the host's QR code or show this code to the host
      </Text>
      
      <Button
        title="Scan Host's Code"
        onPress={() => setShowScanner(true)}
        variant="primary"
      />
      
      <QRScanner
        visible={showScanner}
        onScan={handleQRScan}
        onClose={() => setShowScanner(false)}
      />
    </View>
  );
};
```

#### Host Attendance Dashboard
```typescript
interface AttendanceDashboardProps {
  activityId: string;
}

const AttendanceDashboard: React.FC<AttendanceDashboardProps> = ({ activityId }) => {
  const { data: participants } = useActivityParticipants(activityId);
  const { data: attendanceStats } = useAttendanceStats(activityId);
  const [selectedParticipants, setSelectedParticipants] = useState<string[]>([]);
  const [filterStatus, setFilterStatus] = useState<AttendanceFilter>('all');
  
  const handleBulkCheckIn = async () => {
    try {
      await attendanceService.bulkCheckIn(activityId, selectedParticipants);
      showNotification({
        type: 'success',
        title: 'Bulk Check-in Complete',
        message: `${selectedParticipants.length} participants checked in`,
      });
      setSelectedParticipants([]);
    } catch (error) {
      showNotification({
        type: 'error',
        title: 'Bulk Check-in Failed',
        message: error.message,
      });
    }
  };
  
  const handleMarkNoShow = async (participantId: string) => {
    try {
      await attendanceService.markNoShow(activityId, participantId);
      showNotification({
        type: 'info',
        title: 'Marked as No-show',
        message: 'Participant marked as no-show',
      });
    } catch (error) {
      showNotification({
        type: 'error',
        title: 'Failed to Mark No-show',
        message: error.message,
      });
    }
  };
  
  const filteredParticipants = participants?.filter(p => {
    switch (filterStatus) {
      case 'checked_in': return p.attendance_record;
      case 'not_checked_in': return !p.attendance_record;
      case 'no_show': return p.no_show_record;
      default: return true;
    }
  });
  
  return (
    <View style={styles.container}>
      <View style={styles.header}>
        <Text style={styles.title}>Attendance</Text>
        <AttendanceStats stats={attendanceStats} />
      </View>
      
      <View style={styles.filters}>
        <FilterTabs
          options={[
            { key: 'all', label: 'All' },
            { key: 'checked_in', label: 'Checked In' },
            { key: 'not_checked_in', label: 'Not Checked In' },
            { key: 'no_show', label: 'No Shows' },
          ]}
          selected={filterStatus}
          onSelect={setFilterStatus}
        />
      </View>
      
      <ParticipantList
        participants={filteredParticipants || []}
        selectedParticipants={selectedParticipants}
        onSelectionChange={setSelectedParticipants}
        onCheckIn={(id) => attendanceService.manualCheckIn(activityId, id)}
        onMarkNoShow={handleMarkNoShow}
      />
      
      {selectedParticipants.length > 0 && (
        <View style={styles.bulkActions}>
          <Button
            title={`Check In ${selectedParticipants.length} Selected`}
            onPress={handleBulkCheckIn}
            variant="primary"
          />
        </View>
      )}
    </View>
  );
};
```

### Quality Checklist
- [ ] Check-in process is fast and reliable
- [ ] QR code system works consistently across devices
- [ ] Attendance tracking is accurate and tamper-resistant
- [ ] Host management tools are intuitive and efficient
- [ ] No-show detection is fair and accurate
- [ ] Performance optimized for large activities
- [ ] Offline capabilities work reliably
- [ ] Error handling provides clear guidance

---

**Status**: 🔄 Ready for Problem Definition Phase  
**Assignee**: Full-Stack Developer  
**Epic**: E03 Activity Management  
**Feature**: F02 RSVP & Attendance System  
**Dependencies**: T02 RSVP APIs, T03 RSVP Frontend, Geolocation Service, QR Code Libraries  
**Blocks**: Complete Activity Lifecycle Management
