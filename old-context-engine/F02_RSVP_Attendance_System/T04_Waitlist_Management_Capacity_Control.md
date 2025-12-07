# T04 Waitlist Management & Capacity Control

## Problem Definition

### Task Overview
Implement comprehensive waitlist management system with intelligent capacity control, automatic promotion algorithms, and fair queuing mechanisms. This includes both backend logic for waitlist operations and frontend interfaces for waitlist status tracking and management.

### Problem Statement
The platform needs sophisticated waitlist management to:
- **Handle oversubscribed activities**: Manage demand that exceeds activity capacity
- **Ensure fairness**: Implement fair queuing with transparent position tracking
- **Automate promotions**: Promote participants automatically when spots become available
- **Provide transparency**: Give clear status updates and estimated wait times
- **Support host control**: Allow hosts to manage waitlists and make manual adjustments
- **Handle edge cases**: Manage capacity changes, cancellations, and special circumstances

### Scope
**In Scope:**
- Waitlist enrollment and position tracking system
- Automatic promotion algorithms with fairness guarantees
- Capacity adjustment handling with waitlist impact
- Waitlist analytics and estimated wait time calculations
- Host waitlist management tools and manual promotion capabilities
- Waitlist notification system for status changes
- Integration with RSVP system for seamless transitions

**Out of Scope:**
- Advanced predictive analytics (handled by E07)
- Payment processing for waitlisted participants (handled by E06)
- Complex priority systems (basic FIFO for MVP)
- Social waitlist features (handled by E05)

### Success Criteria
- [ ] Waitlist promotions happen within 5 seconds of spot availability
- [ ] Position tracking is accurate and updates in real-time
- [ ] Estimated wait times are within 20% accuracy
- [ ] Fairness algorithm prevents queue jumping
- [ ] Host management tools achieve 90%+ satisfaction
- [ ] System handles 1000+ person waitlists efficiently

### Dependencies
- **Requires**: T02 RSVP backend APIs for integration
- **Requires**: T03 RSVP frontend components for waitlist UI
- **Requires**: E01.F04 Notification service for waitlist alerts
- **Blocks**: Complete RSVP system functionality
- **Informs**: T05 Attendance tracking (waitlist impact on check-in)

### Acceptance Criteria

#### Waitlist Enrollment System
- [ ] Automatic waitlist enrollment when activity reaches capacity
- [ ] Position assignment with fair queuing (FIFO)
- [ ] Guest count consideration in waitlist positioning
- [ ] Waitlist enrollment confirmation with position information
- [ ] Waitlist cancellation with position adjustment

#### Automatic Promotion Algorithm
- [ ] Immediate promotion when spots become available
- [ ] Fair promotion order based on enrollment time
- [ ] Guest count matching for optimal spot utilization
- [ ] Promotion timeout handling for non-responsive participants
- [ ] Bulk promotion for multiple simultaneous cancellations

#### Capacity Management Integration
- [ ] Dynamic capacity adjustment with waitlist impact analysis
- [ ] Capacity increase handling with automatic promotions
- [ ] Capacity decrease handling with confirmed participant protection
- [ ] Host capacity override capabilities with waitlist notifications
- [ ] Emergency capacity management for special circumstances

#### Position Tracking & Analytics
- [ ] Real-time position updates for all waitlisted participants
- [ ] Estimated wait time calculations based on historical data
- [ ] Waitlist movement analytics and pattern recognition
- [ ] Position change notifications and status updates
- [ ] Waitlist abandonment tracking and analysis

#### Host Management Tools
- [ ] Waitlist overview with participant details
- [ ] Manual promotion capabilities with reason tracking
- [ ] Waitlist communication tools for updates
- [ ] Capacity adjustment tools with waitlist impact preview
- [ ] Waitlist analytics dashboard for hosts

### Estimated Effort
**3-4 hours** for experienced full-stack developer

### Task Breakdown
1. **Waitlist Core Logic & Algorithms** (90 minutes)
   - Implement waitlist enrollment and position management
   - Build automatic promotion algorithms with fairness guarantees
   - Create capacity adjustment handling with waitlist integration
   - Add waitlist analytics and estimated wait time calculations

2. **Frontend Waitlist Interface** (120 minutes)
   - Build waitlist status tracking components
   - Create position visualization and progress indicators
   - Implement promotion notification system
   - Add host waitlist management interface

3. **Integration & Optimization** (60 minutes)
   - Integrate waitlist system with RSVP operations
   - Add comprehensive notification system
   - Optimize performance for large waitlists
   - Create testing and validation systems

### Deliverables
- [ ] Waitlist enrollment and position tracking system
- [ ] Automatic promotion algorithms with fairness guarantees
- [ ] Capacity management integration with waitlist handling
- [ ] Waitlist status tracking and visualization components
- [ ] Host waitlist management tools and interfaces
- [ ] Waitlist notification system for status changes
- [ ] Estimated wait time calculation and display
- [ ] Performance optimization for large waitlists
- [ ] Comprehensive testing and edge case handling

### Technical Specifications

#### Waitlist Data Model
```sql
-- Enhanced waitlist queue table
CREATE TABLE waitlist_queue (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  activity_id UUID REFERENCES activities(id) ON DELETE CASCADE,
  user_id UUID REFERENCES users(id) ON DELETE CASCADE,
  position INTEGER NOT NULL,
  spots_requested INTEGER DEFAULT 1,
  enrolled_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
  promotion_deadline TIMESTAMP WITH TIME ZONE,
  status VARCHAR(20) DEFAULT 'waiting' CHECK (status IN ('waiting', 'promoted', 'expired', 'cancelled')),
  metadata JSONB,
  created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
  updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
  UNIQUE(activity_id, user_id),
  UNIQUE(activity_id, position)
);

-- Waitlist promotions tracking
CREATE TABLE waitlist_promotions (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  waitlist_id UUID REFERENCES waitlist_queue(id) ON DELETE CASCADE,
  promoted_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
  promotion_type VARCHAR(20) NOT NULL, -- 'automatic', 'manual', 'bulk'
  promoted_by UUID REFERENCES users(id) ON DELETE SET NULL,
  response_deadline TIMESTAMP WITH TIME ZONE,
  response_status VARCHAR(20) DEFAULT 'pending' CHECK (response_status IN ('pending', 'accepted', 'declined', 'expired')),
  responded_at TIMESTAMP WITH TIME ZONE,
  reason TEXT
);

-- Waitlist analytics
CREATE TABLE waitlist_analytics (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  activity_id UUID REFERENCES activities(id) ON DELETE CASCADE,
  date DATE NOT NULL,
  max_waitlist_size INTEGER DEFAULT 0,
  total_enrollments INTEGER DEFAULT 0,
  total_promotions INTEGER DEFAULT 0,
  total_cancellations INTEGER DEFAULT 0,
  average_wait_time_minutes INTEGER,
  promotion_success_rate DECIMAL(5,4),
  created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
  UNIQUE(activity_id, date)
);
```

#### Waitlist Management Service
```typescript
class WaitlistManager {
  async enrollInWaitlist(
    activityId: string,
    userId: string,
    spotsRequested: number = 1
  ): Promise<WaitlistEntry> {
    return await this.withTransaction(async (client) => {
      // Get next position in waitlist
      const positionResult = await client.query(`
        SELECT COALESCE(MAX(position), 0) + 1 as next_position
        FROM waitlist_queue
        WHERE activity_id = $1
      `, [activityId]);
      
      const nextPosition = positionResult.rows[0].next_position;
      
      // Create waitlist entry
      const waitlistEntry = await client.query(`
        INSERT INTO waitlist_queue (activity_id, user_id, position, spots_requested)
        VALUES ($1, $2, $3, $4)
        RETURNING *
      `, [activityId, userId, nextPosition, spotsRequested]);
      
      // Update activity waitlist count
      await client.query(`
        UPDATE activities
        SET waitlisted_participants = waitlisted_participants + $1
        WHERE id = $2
      `, [spotsRequested, activityId]);
      
      // Calculate estimated wait time
      const estimatedWaitTime = await this.calculateEstimatedWaitTime(
        activityId,
        nextPosition,
        spotsRequested
      );
      
      const entry = waitlistEntry.rows[0];
      return {
        ...entry,
        estimated_wait_time_minutes: estimatedWaitTime,
      };
    });
  }
  
  async promoteFromWaitlist(
    activityId: string,
    availableSpots: number
  ): Promise<WaitlistPromotion[]> {
    return await this.withTransaction(async (client) => {
      // Get eligible waitlist entries in order
      const eligibleEntries = await client.query(`
        SELECT * FROM waitlist_queue
        WHERE activity_id = $1 AND status = 'waiting'
        ORDER BY position ASC
      `, [activityId]);
      
      const promotions: WaitlistPromotion[] = [];
      let spotsToFill = availableSpots;
      
      for (const entry of eligibleEntries.rows) {
        if (spotsToFill >= entry.spots_requested) {
          // Promote this entry
          const promotion = await this.createPromotion(client, entry.id, 'automatic');
          promotions.push(promotion);
          spotsToFill -= entry.spots_requested;
          
          if (spotsToFill === 0) break;
        }
      }
      
      // Send promotion notifications
      for (const promotion of promotions) {
        await this.sendPromotionNotification(promotion);
      }
      
      return promotions;
    });
  }
  
  async handleCapacityIncrease(
    activityId: string,
    newCapacity: number,
    oldCapacity: number
  ): Promise<void> {
    const additionalSpots = newCapacity - oldCapacity;
    
    if (additionalSpots > 0) {
      // Get current confirmed participants
      const activity = await this.getActivity(activityId);
      const availableSpots = Math.min(
        additionalSpots,
        newCapacity - activity.confirmed_participants
      );
      
      if (availableSpots > 0) {
        await this.promoteFromWaitlist(activityId, availableSpots);
      }
    }
  }
  
  async calculateEstimatedWaitTime(
    activityId: string,
    position: number,
    spotsRequested: number
  ): Promise<number> {
    // Get historical data for similar activities
    const historicalData = await this.getHistoricalWaitTimes(activityId);
    
    if (historicalData.length === 0) {
      // Default estimate based on position
      return position * 30; // 30 minutes per position
    }
    
    // Calculate average promotion rate
    const avgPromotionRate = historicalData.reduce((sum, data) => 
      sum + (data.promotions_per_hour || 0), 0
    ) / historicalData.length;
    
    if (avgPromotionRate === 0) {
      return position * 60; // 1 hour per position if no historical data
    }
    
    // Estimate based on position and historical promotion rate
    const estimatedHours = (position * spotsRequested) / avgPromotionRate;
    return Math.round(estimatedHours * 60); // Convert to minutes
  }
  
  private async createPromotion(
    client: any,
    waitlistId: string,
    promotionType: string
  ): Promise<WaitlistPromotion> {
    // Set promotion deadline (e.g., 15 minutes to respond)
    const deadline = new Date(Date.now() + 15 * 60 * 1000);
    
    const promotion = await client.query(`
      INSERT INTO waitlist_promotions (waitlist_id, promotion_type, response_deadline)
      VALUES ($1, $2, $3)
      RETURNING *
    `, [waitlistId, promotionType, deadline]);
    
    // Update waitlist entry status
    await client.query(`
      UPDATE waitlist_queue
      SET status = 'promoted', promotion_deadline = $2
      WHERE id = $1
    `, [waitlistId, deadline]);
    
    return promotion.rows[0];
  }
}
```

#### Waitlist Frontend Components
```typescript
interface WaitlistStatusProps {
  activityId: string;
  userWaitlistEntry?: WaitlistEntry;
  onPromotionResponse: (accepted: boolean) => void;
}

const WaitlistStatus: React.FC<WaitlistStatusProps> = ({
  activityId,
  userWaitlistEntry,
  onPromotionResponse,
}) => {
  const [showPromotionModal, setShowPromotionModal] = useState(false);
  const { data: waitlistStats } = useWaitlistStats(activityId);
  
  useEffect(() => {
    if (userWaitlistEntry?.status === 'promoted') {
      setShowPromotionModal(true);
    }
  }, [userWaitlistEntry?.status]);
  
  if (!userWaitlistEntry) return null;
  
  const calculateProgress = (): number => {
    if (!waitlistStats || !userWaitlistEntry.position) return 0;
    return Math.max(0, 1 - (userWaitlistEntry.position / waitlistStats.totalWaitlisted));
  };
  
  const formatEstimatedTime = (minutes: number): string => {
    if (minutes < 60) return `${minutes} minutes`;
    const hours = Math.round(minutes / 60);
    return `${hours} hour${hours > 1 ? 's' : ''}`;
  };
  
  return (
    <View style={styles.waitlistContainer}>
      <View style={styles.header}>
        <Icon name="clock" size={20} color={colors.orange.primary} />
        <Text style={styles.title}>You're on the waitlist</Text>
      </View>
      
      <View style={styles.positionInfo}>
        <Text style={styles.positionText}>
          Position {userWaitlistEntry.position} of {waitlistStats?.totalWaitlisted}
        </Text>
        
        <ProgressBar
          progress={calculateProgress()}
          color={colors.cyan.primary}
          backgroundColor={colors.gray[200]}
          style={styles.progressBar}
        />
        
        {userWaitlistEntry.estimated_wait_time_minutes && (
          <Text style={styles.estimateText}>
            Estimated wait: {formatEstimatedTime(userWaitlistEntry.estimated_wait_time_minutes)}
          </Text>
        )}
      </View>
      
      <View style={styles.actions}>
        <Button
          title="Leave Waitlist"
          variant="secondary"
          onPress={() => handleLeaveWaitlist()}
          size="small"
        />
      </View>
      
      <PromotionModal
        visible={showPromotionModal}
        onAccept={() => {
          onPromotionResponse(true);
          setShowPromotionModal(false);
        }}
        onDecline={() => {
          onPromotionResponse(false);
          setShowPromotionModal(false);
        }}
        deadline={userWaitlistEntry.promotion_deadline}
      />
    </View>
  );
};
```

#### Host Waitlist Management
```typescript
interface HostWaitlistManagerProps {
  activityId: string;
  onCapacityChange: (newCapacity: number) => void;
}

const HostWaitlistManager: React.FC<HostWaitlistManagerProps> = ({
  activityId,
  onCapacityChange,
}) => {
  const { data: waitlistEntries } = useWaitlistEntries(activityId);
  const { data: activity } = useActivity(activityId);
  const [selectedEntries, setSelectedEntries] = useState<string[]>([]);
  
  const handleManualPromotion = async (entryIds: string[]) => {
    try {
      await waitlistService.promoteManually(activityId, entryIds);
      showNotification({
        type: 'success',
        title: 'Participants Promoted',
        message: `${entryIds.length} participants promoted from waitlist`,
      });
    } catch (error) {
      showNotification({
        type: 'error',
        title: 'Promotion Failed',
        message: error.message,
      });
    }
  };
  
  const handleCapacityIncrease = async (increase: number) => {
    const newCapacity = activity.capacity + increase;
    await onCapacityChange(newCapacity);
    
    // Show impact preview
    const promotableCount = Math.min(
      increase,
      waitlistEntries?.filter(e => e.status === 'waiting').length || 0
    );
    
    if (promotableCount > 0) {
      showNotification({
        type: 'info',
        title: 'Capacity Increased',
        message: `${promotableCount} participants will be promoted from waitlist`,
      });
    }
  };
  
  return (
    <View style={styles.container}>
      <View style={styles.header}>
        <Text style={styles.title}>Waitlist Management</Text>
        <Text style={styles.subtitle}>
          {waitlistEntries?.length || 0} people waiting
        </Text>
      </View>
      
      <View style={styles.capacityControls}>
        <Text style={styles.capacityLabel}>
          Current Capacity: {activity?.capacity}
        </Text>
        <View style={styles.capacityActions}>
          <Button
            title="+5 Spots"
            onPress={() => handleCapacityIncrease(5)}
            size="small"
          />
          <Button
            title="+10 Spots"
            onPress={() => handleCapacityIncrease(10)}
            size="small"
          />
        </View>
      </View>
      
      <WaitlistEntryList
        entries={waitlistEntries || []}
        selectedEntries={selectedEntries}
        onSelectionChange={setSelectedEntries}
      />
      
      {selectedEntries.length > 0 && (
        <View style={styles.bulkActions}>
          <Button
            title={`Promote ${selectedEntries.length} Selected`}
            onPress={() => handleManualPromotion(selectedEntries)}
            variant="primary"
          />
        </View>
      )}
    </View>
  );
};
```

### Quality Checklist
- [ ] Waitlist promotions are fair and transparent
- [ ] Position tracking is accurate and updates in real-time
- [ ] Estimated wait times provide reasonable accuracy
- [ ] Host management tools are intuitive and powerful
- [ ] Performance optimized for large waitlists
- [ ] Notification system keeps participants informed
- [ ] Edge cases handled gracefully (capacity changes, cancellations)
- [ ] Integration with RSVP system is seamless

---

**Status**: 🔄 Ready for Problem Definition Phase  
**Assignee**: Full-Stack Developer  
**Epic**: E03 Activity Management  
**Feature**: F02 RSVP & Attendance System  
**Dependencies**: T02 RSVP Backend APIs, T03 RSVP Frontend, Notification Service  
**Blocks**: Complete RSVP System Functionality
