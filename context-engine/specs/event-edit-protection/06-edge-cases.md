# Event Edit Protection - Edge Cases & Decisions

## Edge Cases

### EC1: Multiple Edits in Quick Succession
**Scenario:** Host makes 3 significant edits within 1 hour.

**Decision:** Each edit extends/resets the refund window.
- First edit: Window created (72hr)
- Second edit 30min later: Window extended to 72hr from new edit
- Attendees see consolidated changes in notification

**Rationale:** Prevents gaming where host makes changes right before window expires.

---

### EC2: Edit During Active Refund Window
**Scenario:** Refund window is active (48hrs remaining). Host makes another significant edit.

**Decision:** 
- New edit logged separately
- Window extended to 72hrs from new edit
- Attendees who already responded see new notification
- Previous "accepted" responses remain valid (they accepted knowing changes were happening)

---

### EC3: Attendee RSVPs During Refund Window
**Scenario:** Refund window is active. New person RSVPs and pays.

**Decision:**
- New RSVP is created normally
- New attendee does NOT get refund window for past changes
- They see current event details (post-change)
- Audit trail is visible to them

**Rationale:** They're buying the current version of the event.

---

### EC4: Free Event with Tips/Donations
**Scenario:** Event is free but accepts tips. Host changes date significantly.

**Decision:** 
- Tips are NOT refundable via this system
- No refund window triggered (price = 0)
- Attendees still notified of changes
- Future consideration: Optional tip refunds

---

### EC5: Partial Attendance (Qty > 1)
**Scenario:** User bought 4 tickets. Refund window opens. They want refund for 2.

**Decision (v1):** All or nothing.
- "Request Refund" refunds entire RSVP
- If they want partial, they must re-purchase after refund

**Future:** Consider partial refund option.

---

### EC6: Event Cancelled During Refund Window
**Scenario:** Host cancels event while refund window is active.

**Decision:**
- Cancellation supersedes refund window
- All attendees get automatic full refund (existing cancellation flow)
- Refund window marked as "cancelled"
- No duplicate refunds

---

### EC7: Price Decrease After Payment
**Scenario:** 50 people paid $100. Host decreases price to $50.

**Decision:** Allowed (not blocked).
- No automatic partial refund to existing attendees
- New attendees pay new price
- Host can manually issue partial refunds if desired

**Rationale:** Price decreases hurt host, not attendees. Not fraud.

---

### EC8: Timezone Considerations
**Scenario:** Event is in PST. Attendee is in EST. When does window expire?

**Decision:** 
- All times stored in UTC
- Displayed in user's local timezone
- "71 hours remaining" is universal

---

### EC9: Host Account Deleted/Banned
**Scenario:** Host makes significant edit, then account is banned.

**Decision:**
- Refund window remains active
- Attendees can still request refunds
- Refunds process normally (Stripe handles payouts)
- Event may be auto-cancelled by admin

---

### EC10: Stripe Refund Fails
**Scenario:** Attendee requests refund but Stripe call fails.

**Decision:**
- Response marked as "refund_failed"
- Attendee notified of failure
- Manual intervention required
- Consider retry queue for transient failures

---

## Business Rules Summary

| Rule | Decision |
|------|----------|
| Window duration | 72 hours |
| Multiple edits | Extend window |
| New RSVPs during window | No window for them |
| Free events | No refund window (notify only) |
| Partial qty refund | Not supported v1 |
| Price decrease | Allowed, no auto-refund |
| Price increase | Blocked |
| Paid → Free | Blocked |
| Free → Paid | Allowed |

---

## Open Questions (Decide Before Implementation)

### Q1: Should hosts see who requested refunds?
- Option A: Yes, full transparency
- Option B: No, privacy protection
- **Recommendation:** Show aggregate count only ("3 attendees requested refunds")

### Q2: Can host cancel refund window early?
- Option A: Yes, if they revert changes
- Option B: No, window always runs full duration
- **Recommendation:** Option A - reverting changes should close window

### Q3: What about end_time changes?
- Current plan: Always "minor" (notify only)
- Alternative: If end_time moves to next day = significant?
- **Recommendation:** Keep as minor - end time rarely affects attendance decision

### Q4: Minimum change threshold for title?
- If title changes by 1 character, should we even notify?
- **Recommendation:** <5% change = cosmetic (no notification)

### Q5: Should we track "who" made the edit?
- What if event has co-hosts in future?
- **Recommendation:** Yes, track editor_id (future-proofing)

