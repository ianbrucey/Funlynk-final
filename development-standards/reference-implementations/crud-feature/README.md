# Complete CRUD Feature Reference Implementation

**Purpose:** Copy-paste template for implementing a full CRUD feature.

## Overview

This reference implementation shows how to build a complete CRUD feature for a "Task" resource. Copy this pattern and replace "Task" with your resource name.

## File Structure

```
app/
├── Models/
│   └── Task.php                           # Model
├── Services/
│   └── TaskService.php                    # Business logic
├── Livewire/
│   └── Tasks/
│       ├── TaskList.php                   # Index (list)
│       ├── CreateTask.php                 # Create
│       ├── EditTask.php                   # Edit
│       └── ShowTask.php                   # Show (detail)
├── Policies/
│   └── TaskPolicy.php                     # Authorization
└── Events/
    └── TaskCreated.php                    # Event

database/
├── migrations/
│   └── create_tasks_table.php             # Migration
└── factories/
    └── TaskFactory.php                    # Factory

resources/views/livewire/tasks/
├── task-list.blade.php                    # Index view
├── create-task.blade.php                  # Create view
├── edit-task.blade.php                    # Edit view
└── show-task.blade.php                    # Show view

routes/
└── web.php                                # Routes

tests/Feature/
├── Services/
│   └── TaskServiceTest.php                # Service tests
└── Livewire/
    └── Tasks/
        ├── TaskListTest.php               # List tests
        ├── CreateTaskTest.php             # Create tests
        ├── EditTaskTest.php               # Edit tests
        └── ShowTaskTest.php               # Show tests
```

## Implementation Steps

### Step 1: Create Migration

```bash
php artisan make:migration create_tasks_table --no-interaction
```

See: `01-migration.php`

### Step 2: Create Model

```bash
php artisan make:model Task --no-interaction
```

See: `02-model.php`

### Step 3: Create Factory

```bash
php artisan make:factory TaskFactory --no-interaction
```

See: `03-factory.php`

### Step 4: Create Service Class

```bash
mkdir -p app/Services
touch app/Services/TaskService.php
```

See: `04-service.php`

### Step 5: Create Policy

```bash
php artisan make:policy TaskPolicy --model=Task --no-interaction
```

See: `05-policy.php`

### Step 6: Create Event

```bash
php artisan make:event TaskCreated --no-interaction
```

See: `06-event.php`

### Step 7: Create Livewire Components

```bash
php artisan make:livewire Tasks/TaskList --no-interaction
php artisan make:livewire Tasks/CreateTask --no-interaction
php artisan make:livewire Tasks/EditTask --no-interaction
php artisan make:livewire Tasks/ShowTask --no-interaction
```

See: `07-livewire-components/`

### Step 8: Create Routes

Add to `routes/web.php`:

```php
Route::middleware(['auth'])->group(function () {
    Route::get('/tasks', \App\Livewire\Tasks\TaskList::class)->name('tasks.index');
    Route::get('/tasks/create', \App\Livewire\Tasks\CreateTask::class)->name('tasks.create');
    Route::get('/tasks/{task}', \App\Livewire\Tasks\ShowTask::class)->name('tasks.show');
    Route::get('/tasks/{task}/edit', \App\Livewire\Tasks\EditTask::class)->name('tasks.edit');
});
```

### Step 9: Create Tests

```bash
php artisan make:test --pest Services/TaskServiceTest --no-interaction
php artisan make:test --pest Livewire/Tasks/TaskListTest --no-interaction
php artisan make:test --pest Livewire/Tasks/CreateTaskTest --no-interaction
php artisan make:test --pest Livewire/Tasks/EditTaskTest --no-interaction
php artisan make:test --pest Livewire/Tasks/ShowTaskTest --no-interaction
```

See: `08-tests/`

### Step 10: Run Migration and Tests

```bash
php artisan migrate
php artisan test --filter=Task
```

## Customization Checklist

When adapting this template for your resource:

- [ ] Replace "Task" with your resource name (PascalCase)
- [ ] Replace "task" with your resource name (snake_case)
- [ ] Replace "tasks" with your resource plural (snake_case)
- [ ] Update table columns in migration
- [ ] Update model relationships
- [ ] Update validation rules in Livewire components
- [ ] Update service class methods for your business logic
- [ ] Update policy rules for your authorization logic
- [ ] Update factory attributes
- [ ] Update test cases for your specific scenarios
- [ ] Update routes with correct names
- [ ] Update view titles and descriptions

## Time Estimate

**Total:** 2-3 hours for a basic CRUD feature

**Breakdown:**
- Step 1-3 (Database): 20 minutes
- Step 4 (Service): 30 minutes
- Step 5-6 (Policy/Event): 20 minutes
- Step 7 (Livewire): 60 minutes
- Step 8 (Routes): 5 minutes
- Step 9 (Tests): 45 minutes
- Step 10 (Testing): 10 minutes

## Next Steps

After implementing basic CRUD:

1. **Add Search** - See `../search-feature/`
2. **Add Real-time Updates** - See `../real-time-feature/`
3. **Add File Uploads** - See `../file-upload-feature/`
4. **Add Pagination** - Already included in TaskList component
5. **Add Filters** - Add filter properties to TaskList component

## Common Enhancements

### Add Soft Deletes

In migration:
```php
$table->softDeletes();
```

In model:
```php
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use SoftDeletes;
}
```

### Add Search

In TaskList component:
```php
public $search = '';

public function render()
{
    $tasks = Task::query()
        ->when($this->search, fn($q) => $q->where('title', 'like', "%{$this->search}%"))
        ->latest()
        ->paginate(20);
    
    return view('livewire.tasks.task-list', ['tasks' => $tasks]);
}
```

### Add Filters

In TaskList component:
```php
public $status = 'all';

public function render()
{
    $tasks = Task::query()
        ->when($this->status !== 'all', fn($q) => $q->where('status', $this->status))
        ->latest()
        ->paginate(20);
    
    return view('livewire.tasks.task-list', ['tasks' => $tasks]);
}
```

## Files in This Directory

```
crud-feature/
├── README.md                              # This file
├── 01-migration.php                       # Database migration
├── 02-model.php                           # Eloquent model
├── 03-factory.php                         # Model factory
├── 04-service.php                         # Service class
├── 05-policy.php                          # Authorization policy
├── 06-event.php                           # Event class
├── 07-livewire-components/
│   ├── TaskList.php                       # Index component
│   ├── CreateTask.php                     # Create component
│   ├── EditTask.php                       # Edit component
│   └── ShowTask.php                       # Show component
├── 07-livewire-views/
│   ├── task-list.blade.php                # Index view
│   ├── create-task.blade.php              # Create view
│   ├── edit-task.blade.php                # Edit view
│   └── show-task.blade.php                # Show view
└── 08-tests/
    ├── TaskServiceTest.php                # Service tests
    ├── TaskListTest.php                   # List tests
    ├── CreateTaskTest.php                 # Create tests
    ├── EditTaskTest.php                   # Edit tests
    └── ShowTaskTest.php                   # Show tests
```

