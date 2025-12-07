# Quick Start Guide: Post-to-Event Conversion

> **For**: Agent A and Agent B  
> **Purpose**: Get started quickly with parallel implementation  
> **Estimated Time**: 4-5 weeks

---

## 📋 Prerequisites

### Both Agents
- [ ] Read `post-to-event-conversion-design.md` (design decisions)
- [ ] Read `README.md` (task overview)
- [ ] Review `INTEGRATION_GUIDE.md` (how pieces connect)
- [ ] Review `TESTING_STRATEGY.md` (testing approach)

### Agent A (Backend)
- [ ] Familiar with Laravel 12 service pattern
- [ ] Understand PostGIS spatial queries
- [ ] Know how to write Pest tests
- [ ] Access to database migrations

### Agent B (Frontend)
- [ ] Familiar with Livewire v3
- [ ] Understand Filament v4 patterns
- [ ] Know DaisyUI classes
- [ ] Understand galaxy theme (ui-design-standards.md)

---

## 🚀 Getting Started

### Agent A: Start Here
```bash
# 1. Read your task file
cat context-engine/tasks/post-to-event-flow/AGENT_A_TASKS.md

# 2. Start with migrations (Day 1)
php artisan make:migration add_conversion_tracking_to_posts_table --no-interaction

# 3. Follow tasks A1 → A2 → A3 → A4 → A5 → A6

# 4. Run tests after each task
php artisan test --filter=Conversion
```

**Your Focus**: Database, services, events, API endpoints, notifications

### Agent B: Start Here
```bash
# 1. Read your task file
cat context-engine/tasks/post-to-event-flow/AGENT_B_TASKS.md

# 2. Start with profile tab (Day 1)
php artisan make:livewire Profile/InterestedTab --no-interaction

# 3. Follow tasks B1 → B2 → B3 → B4 → B5 → B6

# 4. Run tests after each task
php artisan test --filter=Livewire
```

**Your Focus**: Livewire components, Blade views, UI/UX, galaxy theme

---

## 📅 Daily Workflow

### Morning (15 min sync)
1. **Agent A**: Share any API/event changes
2. **Agent B**: Share any component structure changes
3. **Both**: Discuss integration points for the day
4. **Both**: Identify any blockers

### During Day
- Work on assigned tasks independently
- Test integration points as you go
- Communicate immediately if API/event contracts change
- Push code frequently to avoid conflicts

### End of Day (10 min)
1. **Both**: Commit and push code
2. **Both**: Update task status in README.md
3. **Both**: Note any issues for tomorrow's sync

---

## 🔗 Key Integration Points

### Week 1
**Agent A**: Complete migrations, models, services (A1-A3)
**Agent B**: Complete profile tab, post cards (B1-B2)
**Integration**: Test model helpers in Blade components

### Week 2
**Agent A**: Complete events, notifications, API (A4-A6)
**Agent B**: Complete conversion modal (B3-B5)
**Integration**: Test full conversion flow

### Week 3
**Both**: Integration testing, bug fixes, UI polish
**Integration**: Test all user flows end-to-end

### Week 4
**Both**: Analytics, monitoring, production prep
**Integration**: Performance testing, security audit

---

## 🧪 Testing Checklist

### After Each Task
- [ ] Unit tests passing
- [ ] No new linting errors
- [ ] Code formatted (vendor/bin/pint)
- [ ] Documentation updated if needed

### Before Integration
- [ ] All unit tests passing
- [ ] API contracts match between A and B
- [ ] Event names match between A and B
- [ ] Database schema matches expectations

### Before Production
- [ ] All integration tests passing
- [ ] E2E tests passing
- [ ] Performance benchmarks met
- [ ] Accessibility audit passed
- [ ] Security scan clean
- [ ] 95%+ test coverage

---

## 🆘 Troubleshooting

### "My tests are failing"
1. Check database is migrated: `php artisan migrate:fresh`
2. Check factories are seeded: `php artisan db:seed`
3. Check queue is running: `php artisan queue:work`
4. Check logs: `tail -f storage/logs/laravel.log`

### "Integration not working"
1. Review INTEGRATION_GUIDE.md
2. Check event names match exactly
3. Check API endpoint URLs match
4. Verify authentication middleware
5. Check browser console for errors

### "Galaxy theme not applied"
1. Review ui-design-standards.md
2. Check Tailwind classes are correct
3. Verify component extends galaxy-layout
4. Check CSS is compiled: `npm run build`

### "Can't find a file/class"
1. Check namespace matches directory structure
2. Run `composer dump-autoload`
3. Check file was created in correct location
4. Verify class name matches filename

---

## 📚 Reference Documents

### Design & Planning
- `post-to-event-conversion-design.md` - Full design document with rationale
- `README.md` - Task overview and success criteria
- `INTEGRATION_GUIDE.md` - How backend and frontend connect
- `TESTING_STRATEGY.md` - Comprehensive testing approach

### Implementation
- `AGENT_A_TASKS.md` - Backend tasks (A1-A6)
- `AGENT_B_TASKS.md` - Frontend tasks (B1-B6)

### Context
- `context-engine/global-context.md` - Project overview
- `context-engine/domain-contexts/ui-design-standards.md` - Galaxy theme
- `context-engine/domain-contexts/database-context.md` - PostGIS patterns
- `context-engine/domain-contexts/post-social-interactions.md` - Existing reactions

---

## ✅ Success Metrics

### Functional
- [ ] Users can view interested posts in profile tab
- [ ] Post owners receive prompts at 5 and 10 reactions
- [ ] Post owners can convert posts via modal
- [ ] Interested users receive invitations
- [ ] Converted posts show event links
- [ ] Privacy: Interested users not exposed by name

### Technical
- [ ] All database operations use transactions
- [ ] Idempotency checks prevent duplicates
- [ ] Notifications batched (max 10 per batch)
- [ ] Race conditions handled
- [ ] 95%+ test coverage
- [ ] < 200ms API response times

### UX
- [ ] Galaxy theme applied consistently
- [ ] Mobile-responsive layouts
- [ ] WCAG 2.1 AA compliant
- [ ] Smooth animations
- [ ] Clear error messages

---

## 🎯 First Steps (Right Now)

### Agent A
```bash
# 1. Create first migration
php artisan make:migration add_conversion_tracking_to_posts_table --no-interaction

# 2. Open AGENT_A_TASKS.md and follow A1 instructions

# 3. Write migration code

# 4. Test migration
php artisan migrate
php artisan migrate:rollback
php artisan migrate
```

### Agent B
```bash
# 1. Create first component
php artisan make:livewire Profile/InterestedTab --no-interaction

# 2. Open AGENT_B_TASKS.md and follow B1 instructions

# 3. Add method to User model

# 4. Test component
php artisan test --filter=InterestedTab
```

---

## 💬 Communication

### When to Sync
- **Daily**: 15-minute standup
- **Immediately**: API/event contract changes
- **As needed**: Blockers or questions
- **Weekly**: Milestone reviews

### What to Share
- Completed tasks
- Current blockers
- API/event changes
- Test failures
- Integration issues

---

**Ready? Let's build this! 🚀**

*Start with your respective AGENT_X_TASKS.md file and follow the step-by-step instructions.*

