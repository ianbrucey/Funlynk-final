# Gap Report Template

**Purpose:** Report when development standards are unclear or missing.

## When to Submit a Gap Report

Submit a gap report when:

1. ✅ You're implementing a feature and don't know which standard to follow
2. ✅ Multiple standards seem applicable and you're unsure which is correct
3. ✅ You've implemented something but it doesn't fit any existing pattern
4. ✅ You need clarification on an existing standard
5. ✅ You've discovered a better way to do something than the current standard

## Gap Report Template

```markdown
# Gap Report: [Feature/Pattern Name]

**Date:** YYYY-MM-DD  
**Submitted by:** [Your Name]  
**Status:** OPEN / RESOLVED

## Problem Statement

[Describe the situation where you needed a standard but couldn't find one]

**Example:**
"I'm implementing a feature that needs to send an email after a user completes payment. 
I'm unsure whether this should be:
- A synchronous operation in the service class
- A queued job
- An event listener"

## What I Tried

[Describe what approaches you considered and why they didn't work]

**Example:**
- Tried putting email logic in PaymentService.php - felt too heavy
- Checked decision-trees/async-vs-sync.md - doesn't cover email specifically
- Looked at existing email implementations - found 3 different approaches
- Checked backend-patterns/background-jobs.md - unclear when to use vs. synchronous

## Attempted Solution

[Describe what you implemented as a temporary solution]

**Example:**
```php
// app/Services/PaymentService.php
public function processPayment(array $data): Payment
{
    $payment = Payment::create($data);
    
    // TODO: STANDARD NEEDED - Should this be queued or synchronous?
    Mail::to($payment->user)->send(new PaymentConfirmation($payment));
    
    return $payment;
}
```

## Questions

[List specific questions that need answers]

**Example:**
1. Should all emails be queued or only long-running operations?
2. Should we use jobs or event listeners for email sending?
3. What's the performance threshold for deciding sync vs. async?
4. Should we have a standard email service class?

## Proposed Standard

[Optional: Suggest what the standard should be]

**Example:**
"I propose that all emails should be queued using jobs, with these exceptions:
- Transactional emails (password reset, email verification) should be synchronous
- Marketing/notification emails should be queued
- All jobs should use the 'emails' queue"

## Impact

[Describe how this gap affects development]

**Example:**
- Blocks implementation of payment confirmation feature
- Creates inconsistency if different developers implement email differently
- May cause performance issues if emails are sent synchronously

## References

[Link to related documentation or code]

**Example:**
- See: `backend-patterns/background-jobs.md`
- See: `decision-trees/async-vs-sync.md`
- Existing implementation: `app/Services/UserService.php` (line 45)
- Similar feature: Post creation with notifications

---

## Resolution

[To be filled by reviewer]

**Approved Standard:**
[The decision made]

**Implementation:**
[How to implement the standard]

**Updated Documentation:**
[Which documents were updated]

**Date Resolved:** YYYY-MM-DD
```

## Gap Report Examples

### Example 1: Email Sending Strategy

```markdown
# Gap Report: Email Sending Strategy

**Date:** 2025-12-02  
**Submitted by:** Agent Name  
**Status:** OPEN

## Problem Statement

Implementing payment confirmation emails. Unclear whether to queue or send synchronously.

## What I Tried

- Checked `backend-patterns/background-jobs.md` - doesn't mention emails
- Checked `decision-trees/async-vs-sync.md` - too generic
- Found 3 different approaches in existing code

## Attempted Solution

```php
// Implemented as queued job
SendPaymentConfirmation::dispatch($payment);
```

## Questions

1. Should ALL emails be queued?
2. What about transactional emails (password reset)?
3. Performance threshold for deciding?

## Proposed Standard

All emails should be queued except:
- Password reset (synchronous)
- Email verification (synchronous)
- All others use 'emails' queue

## Impact

- Blocks payment feature
- Creates inconsistency across codebase
```

### Example 2: Real-time Update Strategy

```markdown
# Gap Report: Real-time Update Strategy

**Date:** 2025-12-02  
**Submitted by:** Agent Name  
**Status:** OPEN

## Problem Statement

Implementing live reaction count updates on posts. Unclear whether to use:
- WebSocket events (Reverb)
- Polling
- Hybrid approach

## What I Tried

- Checked `decision-trees/websocket-vs-polling.md` - doesn't exist
- Checked `behavioral-standards/real-time-updates.md` - too vague
- Found WebSocket used for chat, polling used for notifications

## Attempted Solution

```php
// Implemented with WebSocket
event(new PostReactionAdded($post));
```

## Questions

1. When should we use WebSocket vs. polling?
2. What's the performance threshold?
3. Should we have a hybrid approach?

## Proposed Standard

- WebSocket for: Chat, live reactions, user presence
- Polling for: Notifications, background job status
- Hybrid for: High-frequency updates with fallback
```

## Submission Process

### Step 1: Write the Report

Use the template above. Be specific and include examples.

### Step 2: Add TODO Comment

Mark your implementation with a clear TODO:

```php
// TODO: STANDARD NEEDED - See gap report: [link or date]
// Temporary implementation: [brief description]
```

### Step 3: Submit for Review

1. Save gap report to `development-standards/gap-identification/reports/`
2. Name file: `YYYY-MM-DD-gap-[feature-name].md`
3. Notify primary agent for review

### Step 4: Wait for Resolution

Primary agent will:
1. Review the gap report
2. Make a decision on the standard
3. Update relevant documentation
4. Notify you of the resolution

### Step 5: Update Your Implementation

Once standard is approved:
1. Update your code to follow the standard
2. Remove TODO comment
3. Reference the standard in code comments

## Gap Report Status Lifecycle

```
OPEN
  ↓
UNDER_REVIEW (primary agent reviewing)
  ↓
DECISION_MADE (standard decided)
  ↓
DOCUMENTED (documentation updated)
  ↓
RESOLVED (implementation updated)
  ↓
CLOSED
```

## Common Gap Scenarios

### Scenario 1: Multiple Valid Approaches

**Gap:** "Should I use a service class or model method?"

**Resolution:** Check `decision-trees/logic-placement.md` for decision tree

### Scenario 2: No Existing Pattern

**Gap:** "How should I implement X? There's no standard."

**Resolution:** Submit gap report with proposed standard

### Scenario 3: Conflicting Standards

**Gap:** "Two different standards seem to apply here."

**Resolution:** Submit gap report explaining the conflict

### Scenario 4: Performance Concern

**Gap:** "The standard works but seems inefficient."

**Resolution:** Submit gap report with performance analysis and proposal

## Escalation Criteria

**Escalate immediately if:**
- Gap blocks critical feature development
- Gap affects multiple features
- Gap has security implications
- Gap requires architectural decision

**Can wait if:**
- Gap is for a nice-to-have feature
- Gap has a reasonable temporary solution
- Gap affects only one feature

## Tips for Good Gap Reports

✅ **Do:**
- Be specific with examples
- Show what you tried
- Propose a solution
- Include impact assessment
- Reference related documentation

❌ **Don't:**
- Be vague ("I don't know what to do")
- Complain without proposing
- Submit without trying alternatives
- Ignore existing standards
- Make architectural decisions alone

## Gap Report Archive

All resolved gap reports are kept in `gap-identification/reports/` for reference.

**To find similar gaps:**
```bash
grep -r "RESOLVED" development-standards/gap-identification/reports/
```

## Completion Checklist

When submitting a gap report, verify:

- [ ] Problem statement is clear and specific
- [ ] You've tried multiple approaches
- [ ] You've checked existing standards
- [ ] You've included code examples
- [ ] You've proposed a solution
- [ ] You've assessed the impact
- [ ] You've added TODO comment to code
- [ ] Report is saved with correct naming
- [ ] Primary agent has been notified

