# Event Edit Protection - Database Schema

## New Tables

### 1. `activity_edit_logs` - Audit Trail
Stores every edit made to an activity for transparency.

```sql
CREATE TABLE activity_edit_logs (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    activity_id UUID NOT NULL REFERENCES activities(id) ON DELETE CASCADE,
    editor_id UUID NOT NULL REFERENCES users(id),
    
    -- What changed
    field_name VARCHAR(50) NOT NULL,           -- 'title', 'start_time', 'location_name', etc.
    old_value TEXT,                             -- JSON-encoded for complex fields
    new_value TEXT,                             -- JSON-encoded for complex fields
    
    -- Classification
    change_category VARCHAR(20) NOT NULL,      -- 'cosmetic', 'minor', 'significant', 'blocked'
    
    -- Context
    reason TEXT,                                -- Optional host-provided reason
    triggered_refund_window BOOLEAN DEFAULT FALSE,
    
    -- Metadata
    paid_attendee_count INTEGER DEFAULT 0,     -- Snapshot at time of edit
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE INDEX idx_activity_edit_logs_activity ON activity_edit_logs(activity_id);
CREATE INDEX idx_activity_edit_logs_created ON activity_edit_logs(created_at);
```

### 2. `activity_refund_windows` - Active Refund Windows
Tracks open refund windows triggered by significant changes.

```sql
CREATE TABLE activity_refund_windows (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    activity_id UUID NOT NULL REFERENCES activities(id) ON DELETE CASCADE,
    
    -- Window timing
    triggered_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    expires_at TIMESTAMP WITH TIME ZONE NOT NULL,  -- triggered_at + 72 hours
    
    -- What triggered it
    trigger_edit_log_id UUID REFERENCES activity_edit_logs(id),
    changes_summary JSONB NOT NULL,            -- [{field: 'start_time', old: '...', new: '...'}]
    
    -- Status
    status VARCHAR(20) DEFAULT 'active',       -- 'active', 'expired', 'cancelled'
    
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE INDEX idx_refund_windows_activity ON activity_refund_windows(activity_id);
CREATE INDEX idx_refund_windows_status ON activity_refund_windows(status, expires_at);
```

### 3. `rsvp_change_responses` - Attendee Responses to Changes
Tracks how each attendee responded to a refund window.

```sql
CREATE TABLE rsvp_change_responses (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    rsvp_id UUID NOT NULL REFERENCES rsvps(id) ON DELETE CASCADE,
    refund_window_id UUID NOT NULL REFERENCES activity_refund_windows(id) ON DELETE CASCADE,
    
    -- Response
    response VARCHAR(20),                      -- 'accepted', 'refunded', NULL (pending/expired)
    responded_at TIMESTAMP WITH TIME ZONE,
    
    -- If refunded
    refund_id UUID REFERENCES refunds(id),     -- Link to actual refund record
    
    -- Notification tracking
    notified_at TIMESTAMP WITH TIME ZONE,
    notification_id UUID,                       -- Link to notification record
    
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    
    UNIQUE(rsvp_id, refund_window_id)
);

CREATE INDEX idx_change_responses_window ON rsvp_change_responses(refund_window_id);
CREATE INDEX idx_change_responses_pending ON rsvp_change_responses(response) WHERE response IS NULL;
```

## Modified Tables

### `activities` - Add Edit Metadata
```sql
ALTER TABLE activities ADD COLUMN IF NOT EXISTS 
    edit_locked_at TIMESTAMP WITH TIME ZONE;  -- Set when first paid RSVP occurs

ALTER TABLE activities ADD COLUMN IF NOT EXISTS
    original_values JSONB;                     -- Snapshot of key fields at lock time
    -- {title: '...', start_time: '...', location_name: '...', price: 100}
```

## Relationships

```
Activity
  ├── has_many: activity_edit_logs
  ├── has_many: activity_refund_windows
  └── original_values (JSONB snapshot)

ActivityRefundWindow
  ├── belongs_to: activity
  ├── belongs_to: trigger_edit_log
  └── has_many: rsvp_change_responses

RsvpChangeResponse
  ├── belongs_to: rsvp
  ├── belongs_to: refund_window
  └── belongs_to: refund (optional)
```

## Data Flow Example

```
1. Event created (price: $50)
2. First paid RSVP arrives
   → activities.edit_locked_at = NOW()
   → activities.original_values = {title, start_time, location_name, price}
3. Host changes start_time by 3 hours (significant)
   → INSERT activity_edit_logs (field='start_time', category='significant')
   → INSERT activity_refund_windows (expires = NOW() + 72hrs)
   → For each paid RSVP: INSERT rsvp_change_responses (response=NULL)
   → Send notifications
4. Attendee clicks "Request Refund"
   → UPDATE rsvp_change_responses SET response='refunded'
   → Trigger RefundService
5. Window expires
   → UPDATE activity_refund_windows SET status='expired'
   → All NULL responses become implicitly 'accepted'
```

