# F03 User & Community Management

## Feature Overview

Build comprehensive user administration tools using Laravel 12 and Filament v4. This feature provides admin dashboards for user management, support ticket systems, identity verification, account actions, and appeals processing. Enables efficient customer support and community health monitoring.

**Key Architecture**: Filament resources provide admin interfaces for user CRUD. Support tickets track user issues. Verification workflows confirm user identities. Account actions log administrative decisions. Appeals allow users to contest moderation.

## Feature Scope

### In Scope
- **User management dashboard**: Search, filter, view, edit user accounts
- **Support ticket system**: User-submitted tickets with admin responses
- **User verification**: Identity confirmation workflows
- **Account actions**: Administrative user management (verify, suspend, delete)
- **Appeals process**: Users can appeal suspensions/bans
- **Community health metrics**: User satisfaction, resolution times

### Out of Scope
- **Automated support**: AI chatbots (Phase 2)
- **Live chat support**: Email-based only initially

## Tasks Breakdown

### T01: User Management Database Schema
**Estimated Time**: 3-4 hours
**Dependencies**: E01 `users` table exists
**Artisan Commands**:
```bash
php artisan make:migration create_support_tickets_table --no-interaction
php artisan make:migration create_user_actions_table --no-interaction
php artisan make:migration create_appeals_table --no-interaction
php artisan make:migration add_verification_fields_to_users_table --no-interaction
```

**Description**: Create tables for support tickets, user actions, appeals, and add verification fields to users.

**Key Implementation Details**:
- `support_tickets`: `id`, `user_id`, `subject`, `description`, `status` (open/in_progress/resolved/closed), `priority`, `assigned_to`, `created_at`
- `user_actions`: `id`, `user_id`, `action_type` (verify/suspend/delete/restore), `reason`, `performed_by`, `created_at`
- `appeals`: `id`, `user_id`, `moderation_action_id`, `reason`, `status` (pending/approved/denied), `reviewed_by`, `created_at`
- Add to `users`: `is_verified`, `verified_at`, `verification_notes`

**Deliverables**:
- [ ] Support, actions, appeals tables created
- [ ] Verification fields added to users
- [ ] Schema tests

---

### T02: SupportTicket & Appeal Models
**Estimated Time**: 3-4 hours
**Dependencies**: T01
**Artisan Commands**:
```bash
php artisan make:model SupportTicket --no-interaction
php artisan make:model UserAction --no-interaction
php artisan make:model Appeal --no-interaction
php artisan make:factory SupportTicketFactory --model=SupportTicket --no-interaction
```

**Description**: Create models with relationships and implement `casts()` for enums.

**Key Implementation Details**:
- Use `casts()` method (Laravel 12)
- Cast `status`, `priority`, `action_type` as enums
- Relationships: `SupportTicket belongsTo User`, `Appeal belongsTo User/ModerationAction`
- Helper methods: `isOpen()`, `isPending()`, `canAppeal()`

**Deliverables**:
- [ ] Support Ticket, UserAction, Appeal models
- [ ] Relationships configured
- [ ] Factories for testing

---

### T03: UserManagementService
**Estimated Time**: 5-6 hours
**Dependencies**: T02
**Artisan Commands**:
```bash
php artisan make:class Services/UserManagementService --no-interaction
php artisan make:test --pest Feature/UserManagementServiceTest --no-interaction
```

**Description**: Build service class handling user administrative actions and verification workflows.

**Key Implementation Details**:
- `verifyUser($user, $notes)`: Mark user as verified
- `logAction($user, $actionType, $reason, $adminId)`: Record administrative actions
- `searchUsers($query, $filters)`: Advanced user search with filters
- `getUserStats($user)`: Generate user activity summary
- `exportUsers($filters)`: Export user data to CSV
- Track all actions in `user_actions` table

**Deliverables**:
- [ ] UserManagementService with admin actions
- [ ] User search and filtering
- [ ] Action logging
- [ ] Tests for all methods

---

### T04: Filament User Management Dashboard
**Estimated Time**: 6-7 hours
**Dependencies**: T02
**Artisan Commands**:
```bash
php artisan make:filament-resource SupportTicket --generate --no-interaction
php artisan make:filament-resource UserAction --generate --no-interaction
php artisan make:filament-resource Appeal --generate --no-interaction
```

**Description**: Create Filament admin resources for managing users, tickets, actions, and appeals.

**Key Implementation Details**:
- Use Filament v4 `->components([])` for forms
- Enhanced UserResource: view full profile, action buttons (verify, suspend, delete), activity timeline
- SupportTicketResource: view tickets, assign to admin, add responses, change status
- AppealResource: view appeals, approve/deny, add notes
- Add filters: by verification status, by account status, by ticket status
- Bulk actions: verify users, close tickets, process appeals

**Deliverables**:
- [ ] Enhanced UserResource with admin actions
- [ ] SupportTicket resource with assignment
- [ ] Appeal resource with approval workflow
- [ ] Admin tests

---

### T05: Support Ticket System
**Estimated Time**: 5-6 hours
**Dependencies**: T03, T04
**Artisan Commands**:
```bash
php artisan make:livewire Support/CreateTicket --no-interaction
php artisan make:livewire Support/TicketThread --no-interaction
php artisan make:notification TicketResponseNotification --no-interaction
```

**Description**: Build complete support ticket system with user and admin interfaces.

**Key Implementation Details**:
- `CreateTicket`: User-facing form for submitting support tickets
- `TicketThread`: Display ticket history, add responses
- Auto-assign tickets based on admin workload
- Email notifications on ticket updates
- Track response time and resolution metrics
- Categorize tickets: account, billing, technical, general

**Deliverables**:
- [ ] CreateTicket component
- [ ] TicketThread for responses
- [ ] Auto-assignment logic
- [ ] Notification system
- [ ] Tests for ticket workflows

---

### T06: Appeals Workflow
**Estimated Time**: 4-5 hours
**Dependencies**: T03, T04
**Artisan Commands**:
```bash
php artisan make:livewire Appeals/SubmitAppeal --no-interaction
php artisan make:livewire Appeals/AppealStatus --no-interaction
php artisan make:notification AppealDecisionNotification --no-interaction
```

**Description**: Implement appeals process allowing users to contest moderation actions.

**Key Implementation Details**:
- `SubmitAppeal`: Form for users to appeal suspensions/bans
- `AppealStatus`: Track appeal review progress
- Admins review appeals in Filament
- Approval reverses moderation action
- Denial maintains action, records reason
- Users can only appeal once per action

**Deliverables**:
- [ ] SubmitAppeal component
- [ ] AppealStatus tracking
- [ ] Admin approval workflow
- [ ] Notifications for decisions
- [ ] Tests for appeal process

---

### T07: User Management Tests
**Estimated Time**: 4-5 hours
**Dependencies**: T01-T06
**Artisan Commands**:
```bash
php artisan make:test --pest Feature/UserAdministrationTest --no-interaction
php artisan make:test --pest Feature/SupportTicketTest --no-interaction
php artisan make:test --pest Feature/AppealWorkflowTest --no-interaction
php artisan test --filter=UserManagement
```

**Description**: Comprehensive testing of user administration, support tickets, and appeals.

**Key Implementation Details**:
- Test user verification workflow
- Test support ticket creation and resolution
- Test appeals submission and approval
- Test admin action logging
- Test user search and filtering
- Test bulk actions

**Deliverables**:
- [ ] User administration tests
- [ ] Support ticket tests
- [ ] Appeal workflow tests
- [ ] All tests passing with >80% coverage

---

## Success Criteria

### Functional Requirements
- [ ] Admins can search, view, and manage users
- [ ] Support tickets can be created and resolved
- [ ] Users can submit appeals
- [ ] User verification workflow functional
- [ ] All administrative actions logged
- [ ] Notifications sent for ticket/appeal updates

### Technical Requirements
- [ ] User search optimized with indexes
- [ ] Ticket assignment automated
- [ ] Appeal approval reverses actions correctly
- [ ] Action logging comprehensive
- [ ] Email notifications reliable

### User Experience Requirements
- [ ] Support ticket submission easy
- [ ] Appeal process clear
- [ ] Admin dashboard efficient
- [ ] User profiles comprehensive

### Performance Requirements
- [ ] User searches fast (<1 second)
- [ ] Ticket listing paginated
- [ ] Dashboard loads quickly
- [ ] Bulk actions process efficiently

## Dependencies

### External Dependencies
- **E01 Core Infrastructure**: `users` table
- **E07/F02 Moderation**: Appeals reference moderation_actions

## Technical Notes

### Laravel 12 Conventions
- Use `casts()` method in models
- Queue email notifications

### User Search Example
```php
User::query()
    ->when($query, fn($q) => $q->where('email', 'like', "%{$query}%")
        ->orWhere('display_name', 'like', "%{$query}%"))
    ->when($verified, fn($q) => $q->where('is_verified', $verified))
    ->when($suspended, fn($q) => $q->where('is_suspended', $suspended))
    ->paginate(50);
```

### Testing Considerations
- Test permission boundaries (only admins can access)
- Test notification delivery
- Run tests with: `php artisan test --filter=UserManagement`

---

**Feature Status**: 🔄 Ready for Implementation
**Priority**: P1
**Epic**: E07 Administration
**Estimated Total Time**: 30-37 hours
**Dependencies**: E01 users table, E07/F02 for appeals
