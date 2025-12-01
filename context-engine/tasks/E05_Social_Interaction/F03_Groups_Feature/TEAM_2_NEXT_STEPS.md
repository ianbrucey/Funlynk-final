# Team 2 (Backend) - Next Steps

## ⚠️ Your proposal NEEDS REVISION before implementation

You must fix **4 issues** and resubmit your proposal.

---

## 📋 What You Need To Do

### **Step 1: Read the Review**
Read your section in: `PROPOSAL_REVIEW_RESULTS.md`

It contains detailed explanations and code examples for all required fixes.

### **Step 2: Fix All Issues**

#### **Issue #1: Routing Strategy (CRITICAL)**
Add BOTH web routes and API routes to your proposal.

**Copy this into your revised proposal**:
```php
// routes/web.php - For Livewire page loads
Route::middleware('auth')->group(function () {
    Route::get('/groups', GroupsIndex::class)->name('groups.index');
    Route::get('/groups/create', CreateGroup::class)->name('groups.create');
    Route::get('/groups/{group:slug}', GroupShow::class)->name('groups.show');
    Route::get('/groups/{group:slug}/settings', GroupSettings::class)->name('groups.settings');
    Route::get('/dashboard', UserDashboard::class)->name('dashboard');
});

// routes/api.php - For AJAX operations
Route::middleware('auth')->prefix('api')->group(function () {
    Route::post('/groups', [GroupController::class, 'store']);
    Route::put('/groups/{group}', [GroupController::class, 'update']);
    Route::delete('/groups/{group}', [GroupController::class, 'destroy']);
    Route::post('/groups/{group}/join', [GroupController::class, 'join']);
    Route::post('/groups/{group}/leave', [GroupController::class, 'leave']);
    Route::get('/groups/search', [GroupController::class, 'search']);
    Route::get('/groups/{group}/members', [GroupController::class, 'members']);
    Route::post('/groups/{group}/members/{user}/remove', [GroupController::class, 'removeMember']);
    Route::post('/groups/{group}/join-requests/{request}/approve', [GroupController::class, 'approveRequest']);
    Route::post('/groups/{group}/join-requests/{request}/deny', [GroupController::class, 'denyRequest']);
    Route::post('/groups/{group}/posts', [GroupPostController::class, 'store']);
    Route::post('/groups/{group}/events', [GroupEventController::class, 'store']);
    Route::get('/groups/{group}/timeline', [GroupController::class, 'timeline']);
    Route::get('/groups/{group}/chat', [GroupChatController::class, 'show']);
    Route::post('/groups/{group}/chat/messages', [GroupChatController::class, 'sendMessage']);
    Route::get('/groups/{group}/chat/messages', [GroupChatController::class, 'messages']);
});
```

#### **Issue #2: Property Naming**
Replace ALL occurrences of `is_private` with `privacy`.

**Find and replace**:
- `'is_private' => 'boolean'` → `'privacy' => 'required|in:public,private'`
- Update API contracts in your proposal
- Update service method examples

#### **Issue #3: Validation Rules**
Add the complete validation rules from `PROPOSAL_REVIEW_RESULTS.md` to your proposal.

**Copy these into your revised proposal**:
- CreateGroupRequest validation rules
- UpdateGroupRequest validation rules
- CreateGroupPostRequest validation rules
- CreateGroupEventRequest validation rules

(Full rules are in PROPOSAL_REVIEW_RESULTS.md)

#### **Issue #4: Event Names**
Standardize event names:
- ✅ `GroupCreated` (already correct)
- ✅ `GroupMemberJoined` (already correct)
- ✅ `GroupMemberRemoved` (already correct)
- ❌ `NewGroupPost` → ✅ `GroupPostCreated`
- ❌ `NewGroupEvent` → ✅ `GroupEventCreated`
- ✅ `GroupJoinRequestReceived` (already correct)
- ✅ `GroupJoinRequestApproved` (already correct)

### **Step 3: Update Integration Contracts**
Update `INTEGRATION_CONTRACTS.md` with:
- Corrected API endpoints (with `/api` prefix)
- Corrected validation rules
- Corrected event names
- `privacy` instead of `is_private`

### **Step 4: Resubmit Proposal**
Create a new file: `BACKEND_PROPOSAL_v2.md`

Include all fixes from above.

### **Step 5: Report Back**
When complete, update `PROPOSAL_REVIEW_RESULTS.md` at the bottom:

```markdown
## Team Progress Updates

### Team 2 (Backend) - [DATE/TIME]
✅ Revised proposal submitted as BACKEND_PROPOSAL_v2.md
- Fixed routing strategy (web + API routes)
- Changed is_private to privacy
- Added complete validation rules
- Standardized event names
- Updated INTEGRATION_CONTRACTS.md
```

---

## 🚫 DO NOT START IMPLEMENTATION

You are **BLOCKED** until your revised proposal is approved.

**Timeline**: Submit revised proposal within 1 hour.

**Questions?** Ask the Architect Agent (me) in `PROPOSAL_REVIEW_RESULTS.md`

---

## ✅ Checklist Before Resubmitting

- [ ] Added web routes for Livewire pages
- [ ] Added API routes for AJAX operations
- [ ] Replaced all `is_private` with `privacy`
- [ ] Added complete validation rules for all Form Requests
- [ ] Standardized event names to `GroupPostCreated`, `GroupEventCreated`
- [ ] Updated `INTEGRATION_CONTRACTS.md`
- [ ] Created `BACKEND_PROPOSAL_v2.md`
- [ ] Reported completion in `PROPOSAL_REVIEW_RESULTS.md`

