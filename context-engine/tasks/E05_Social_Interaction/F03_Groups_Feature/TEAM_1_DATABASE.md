# Team 1: Database & Schema Design

## 🎯 Your Mission

Design and implement the database schema for the Groups feature. Your work is **BLOCKING** - Teams 2 and 3 cannot proceed until you complete and get approval.

---

## 📋 Required Reading

**MUST READ FIRST**:
1. `context-engine/tasks/E05_Social_Interaction/F03_Groups_Feature/REQUIREMENTS.md`
2. `context-engine/tasks/E05_Social_Interaction/F03_Groups_Feature/ARCHITECTURE.md`
3. `context-engine/domain-contexts/database-context.md` (PostGIS patterns)
4. `context-engine/epics/E01_Core_Infrastructure/database-schema.md` (existing tables)

---

## 🎯 Your Responsibilities

### **1. Schema Design**
Design the following tables with proper columns, indexes, and constraints:

#### **New Tables**
- `groups` - Group details
- `group_members` - Membership and roles
- `group_join_requests` - Pending join requests
- `group_tag` - Pivot table linking groups to tags (for categorization)

#### **Table Modifications**
- `posts` - Add `group_id` column
- `activities` - Add `group_id` column
- `conversations` - Add `group_id` column and update `type` enum

### **2. Relationships**
Define all foreign keys, indexes, and constraints to ensure:
- Data integrity
- Query performance
- Proper cascading deletes

### **3. Migrations**
Create Laravel migration files using proper naming conventions:
- `YYYY_MM_DD_HHMMSS_create_groups_table.php`
- `YYYY_MM_DD_HHMMSS_create_group_members_table.php`
- `YYYY_MM_DD_HHMMSS_create_group_join_requests_table.php`
- `YYYY_MM_DD_HHMMSS_create_group_tag_table.php`
- `YYYY_MM_DD_HHMMSS_add_group_id_to_posts_table.php`
- `YYYY_MM_DD_HHMMSS_add_group_id_to_activities_table.php`
- `YYYY_MM_DD_HHMMSS_add_group_id_to_conversations_table.php`

### **4. Models**
Create Eloquent models with:
- Proper relationships (hasMany, belongsTo, belongsToMany)
- Casts (using `casts()` method, not `$casts` property - Laravel 12)
- Fillable/guarded attributes
- Scopes for common queries
- Factories for testing

### **5. Seeders**
Create seeders for development/testing:
- Sample groups (public and private)
- Group memberships
- Group posts and events

---

## 📐 Schema Specifications

### **`groups` Table**

**Columns**:
```
- id: uuid, primary key
- name: string(100), required, indexed
- slug: string(120), unique, indexed (for URLs)
- description: text, nullable
- avatar_url: string, nullable
- cover_image_url: string, nullable
- privacy: enum('public', 'private'), default 'public', indexed
- auto_approve_members: boolean, default false
- member_count: integer, default 0
- created_by: uuid, foreign key to users.id
- created_at: timestamp
- updated_at: timestamp
- deleted_at: timestamp, nullable (soft deletes)
```

**Indexes**:
- Primary key on `id`
- Unique index on `slug`
- Index on `name` for search
- Index on `privacy` for filtering
- Index on `created_by` for "my created groups"

**Constraints**:
- Foreign key `created_by` references `users.id` ON DELETE CASCADE

---

### **`group_members` Table**

**Columns**:
```
- id: uuid, primary key
- group_id: uuid, foreign key to groups.id
- user_id: uuid, foreign key to users.id
- role: enum('admin', 'member'), default 'member'
- joined_at: timestamp
- created_at: timestamp
- updated_at: timestamp
```

**Indexes**:
- Primary key on `id`
- **UNIQUE** composite index on `(group_id, user_id)` - prevent duplicate memberships
- Index on `user_id` for "my groups" queries
- Index on `group_id` for member lists

**Constraints**:
- Foreign key `group_id` references `groups.id` ON DELETE CASCADE
- Foreign key `user_id` references `users.id` ON DELETE CASCADE

---

### **`group_join_requests` Table**

**Columns**:
```
- id: uuid, primary key
- group_id: uuid, foreign key to groups.id
- user_id: uuid, foreign key to users.id
- status: enum('pending', 'approved', 'denied'), default 'pending'
- requested_at: timestamp
- responded_at: timestamp, nullable
- responded_by: uuid, foreign key to users.id, nullable
- created_at: timestamp
- updated_at: timestamp
```

**Indexes**:
- Primary key on `id`
- **UNIQUE** partial index on `(group_id, user_id)` WHERE `status = 'pending'` - prevent duplicate pending requests
- Index on `group_id` for admin approval queries
- Index on `user_id` for "my requests" queries
- Index on `status` for filtering

**Constraints**:
- Foreign key `group_id` references `groups.id` ON DELETE CASCADE
- Foreign key `user_id` references `users.id` ON DELETE CASCADE
- Foreign key `responded_by` references `users.id` ON DELETE SET NULL

---

### **Modifications to Existing Tables**

#### **`posts` Table**
```sql
ADD COLUMN group_id uuid NULLABLE
ADD FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE CASCADE
ADD INDEX idx_posts_group_id (group_id)
```

#### **`activities` Table**
```sql
ADD COLUMN group_id uuid NULLABLE
ADD FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE CASCADE
ADD INDEX idx_activities_group_id (group_id)
```

#### **`conversations` Table**
```sql
ADD COLUMN group_id uuid NULLABLE
ALTER COLUMN type TYPE VARCHAR(20) -- extend enum to include 'group'
ADD FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE CASCADE
ADD INDEX idx_conversations_group_id (group_id)
```

**Note**: For the `type` column, you may need to:
1. Check existing enum definition
2. Add 'group' to the enum values
3. Use `DB::statement()` for PostgreSQL enum modification

---

### **`group_tag` Pivot Table**

**Columns**:
```
- group_id: uuid, foreign key to groups.id
- tag_id: uuid, foreign key to tags.id
- created_at: timestamp
```

**Indexes**:
- **PRIMARY KEY** on `(group_id, tag_id)` - composite primary key
- Index on `tag_id` for "groups by tag" queries

**Constraints**:
- Foreign key `group_id` references `groups.id` ON DELETE CASCADE
- Foreign key `tag_id` references `tags.id` ON DELETE CASCADE

**Note**: This reuses the existing `tags` table from E01 Core Infrastructure. No modifications to the `tags` table are needed.

---

## 🔍 Key Considerations

### **1. Slug Generation**
- Auto-generate from group name (e.g., "Brooklyn Runners" → "brooklyn-runners")
- Ensure uniqueness (append number if needed: "brooklyn-runners-2")
- Use `Str::slug()` helper

### **2. Member Count**
- Denormalized for performance (avoid COUNT queries)
- Update via database triggers OR model events
- Consider using model observers

### **3. Soft Deletes**
- Groups should use soft deletes
- Deleted groups should hide content but preserve data
- Consider cascade behavior for related records

### **4. Privacy Enforcement**
- Database-level constraints won't enforce privacy
- Privacy is enforced at application layer (policies)
- Ensure indexes support privacy filtering

### **5. Performance**
- Index all foreign keys
- Index columns used in WHERE clauses
- Consider composite indexes for common query patterns

---

## 📦 Deliverables

### **Phase 1: Schema Proposal** (Submit for Review)
Create a document: `SCHEMA_PROPOSAL.md` with:
1. Complete table definitions (SQL DDL)
2. Relationship diagram (text or Mermaid)
3. Index strategy explanation
4. Migration order and dependencies
5. Any deviations from ARCHITECTURE.md (with justification)

**Submit this for approval before proceeding to Phase 2**

### **Phase 2: Implementation** (After Approval)
1. Migration files (7 files)
2. Model files:
   - `app/Models/Group.php`
   - `app/Models/GroupMember.php`
   - `app/Models/GroupJoinRequest.php`
3. Updated models:
   - `app/Models/Post.php` (add group relationship)
   - `app/Models/Activity.php` (add group relationship)
   - `app/Models/Conversation.php` (add group relationship)
   - `app/Models/User.php` (add group relationships)
   - `app/Models/Tag.php` (add groups relationship)
4. Factory files:
   - `database/factories/GroupFactory.php`
   - `database/factories/GroupMemberFactory.php`
   - `database/factories/GroupJoinRequestFactory.php`
5. Seeder file:
   - `database/seeders/GroupSeeder.php` (include tag associations)
6. Test the migrations:
   - Run `php artisan migrate:fresh --seed`
   - Verify all tables created correctly
   - Verify relationships work (including group-tag pivot)

---

## ⚠️ Critical Rules

1. **Use UUIDs** - All primary keys must be UUIDs (existing FunLynk standard)
2. **Laravel 12 Syntax** - Use `casts()` method, not `$casts` property
3. **Naming Conventions** - Follow Laravel conventions (snake_case for columns, PascalCase for models)
4. **Foreign Keys** - Always define foreign key constraints
5. **Indexes** - Index all foreign keys and frequently queried columns
6. **No Breaking Changes** - Don't modify existing table structures beyond adding columns
7. **Reversible Migrations** - All migrations must have proper `down()` methods

---

## 🧪 Testing Checklist

Before submitting Phase 2:
- [ ] All migrations run successfully
- [ ] All migrations can be rolled back
- [ ] All models load without errors
- [ ] All relationships work (test in Tinker)
- [ ] Factories generate valid data
- [ ] Seeders populate database correctly
- [ ] No N+1 query issues (use eager loading)

---

## 📞 Communication

**Report to**: Architect Agent (me)
**Blockers**: If you encounter issues with existing schema, report immediately
**Questions**: Ask before making assumptions

**When complete**: Submit `SCHEMA_PROPOSAL.md` for review, then wait for approval before implementing.

---

**Team Lead**: Database Engineer Agent
**Priority**: P0 (BLOCKING)
**Estimated Time**: 4-6 hours
**Status**: READY TO START

