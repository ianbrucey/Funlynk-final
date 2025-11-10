<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

## Foundational Context
This application is a Laravel web application. Main packages & versions:

- php - 8.4.13
- filament/filament (FILAMENT) - v4
- laravel/framework (LARAVEL) - v12
- livewire/livewire (LIVEWIRE) - v3
- pestphp/pest (PEST) - v4
- laravel/pint (PINT) - v1

## Frontend Framework
- **DaisyUI** is our CSS framework for all UI components (buttons, forms, modals, etc.)
- Use DaisyUI classes and components for consistent styling
- Refer to DaisyUI documentation when implementing UI elements

## UI Design System
- **Design Standards**: All UI components MUST follow the design system documented in `context-engine/domain-contexts/ui-design-standards.md`
- **Galaxy Theme**: Use the galaxy background with aurora borealis effects (green, blue, purple layers) and animated stars
- **Glass Morphism**: Apply glass card styling with backdrop blur and blue borders to all major content containers
- **Consistency**: The galaxy/aurora theme with glass morphism is the standard design language for ALL pages (landing, auth, dashboard, etc.)
- **CRITICAL**: Always consult the UI design standards document before building any UI components to ensure visual consistency

## Development Strategy
- **Primary Focus**: Building Laravel web application with Filament for CRUD operations
- **Filament First**: Use Filament framework for all CRUD operations
- **Custom Views**: Only create custom views for specialized functionality that Filament cannot handle
- **Future Plans**: React Native mobile app after Laravel completion
- **Current Priority**: 100% focused on Laravel development

## Documentation & Research
- **CRITICAL**: If you don't know how to implement a feature based on Laravel, Filament, DaisyUI, or other standards:
  1. Use `web-fetch` tool to get official documentation first
  2. Use `search-docs` tool for Laravel ecosystem packages
  3. Never guess or hallucinate implementation details
- **Testing Frontend**: Use Chrome DevTools to test and verify front-end features you build

## Conventions
- Follow existing code conventions - check sibling files for structure, approach, naming
- Use descriptive names: `isRegisteredForDiscounts`, not `discount()`
- Reuse existing components before creating new ones
- Stick to existing directory structure
- Do not change dependencies without approval

## Frontend Bundling
- If frontend changes aren't reflected, user may need to run `npm run build` or `npm run dev`

## Documentation Files
- Only create documentation files if explicitly requested


=== boost rules ===

## Laravel Boost Tools
- Use `list-artisan-commands` to check Artisan command parameters
- Use `tinker` for debugging PHP/Eloquent queries
- Use `database-query` for read-only database operations
- Use `browser-logs` to read browser errors and exceptions
- Use `get-absolute-url` when sharing project URLs

## Documentation Search (CRITICAL)
- Use `search-docs` tool for Laravel ecosystem packages (Laravel, Livewire, Filament, Pest, etc.)
- Search documentation BEFORE making code changes
- Use multiple, broad, topic-based queries: `['rate limiting', 'routing']`
- Don't add package names to queries: use `test resource table`, not `filament 4 test resource table`

## Parallel Task Execution with Subagents (CRITICAL)

### Purpose
Execute **multiple independent tasks in parallel** using `spawn_sub_agent.py`. **ALWAYS evaluate** whether a task can be parallelized BEFORE starting sequential work.

### How It Works
- **Script**: `python spawn_sub_agent.py gemini "YOUR COMPLETE PROMPT HERE"`
- **Returns**: `job_id` (e.g., `20250109_143022_a3f8b1`)
- **Output**: `./subagent_runs/{job_id}/` with status, logs, and results
- **Agent**: Gemini 2.5 Flash model

**CRITICAL: Subagents are STATELESS**
- No memory of previous conversations or context
- Instructions must be COMPLETE and SELF-CONTAINED
- Provide ALL necessary context in the prompt or reference specific files to read
- Specify exact file paths for inputs and outputs
- Include step-by-step procedures, not just high-level goals

### Decision-Making Checklist

**BEFORE starting any multi-step task, ask yourself:**

1. ✅ **Are there multiple independent subtasks?**
2. ✅ **Do the subtasks have NO dependencies on each other?**
3. ✅ **Would parallel execution significantly reduce total time?**
4. ✅ **Is each subtask well-defined enough to be delegated?**

**If YES to all 4 questions → USE SUBAGENT SPAWNING**

### Best Practices for Writing Subagent Prompts

**✅ GOOD Prompt Structure**:
```
1. Context: "You are updating Epic E03 Activity Management documentation."
2. Input: "Read the file context-engine/epics/E03_Activity_Management/epic-overview.md"
3. Task: "Add a new section called 'Post-to-Event Conversion' after the 'Component Breakdown' section."
4. Details: "Include these subsections: [list specific subsections]"
5. Output: "Save the updated file to the same location."
6. Validation: "Ensure the file is valid Markdown and all existing content is preserved."
```

**❌ BAD Prompt Examples**:
- "Update the documentation we discussed earlier" (no context, no file path)
- "Fix the issues in the epic files" (not specific, no clear output)
- "Make the changes like we did for E04" (assumes shared memory)
- "Analyze the codebase and suggest improvements" (too open-ended, no output path)

**Key Principles**:
1. **Be Explicit**: Specify exact file paths, section names, formats
2. **Be Complete**: Include all context needed (no references to "earlier" or "previous")
3. **Be Specific**: Define exact outputs, formats, validation criteria
4. **Be Sequential**: Break down the task into numbered steps
5. **Be Verifiable**: Make success/failure easy to determine

### Example Use Cases

**Example 1: Parallel Documentation Updates**
```bash
# Task: Update 5 epic documentation files to add "Posts vs Events" clarification

# Spawn 5 subagents in parallel:
python spawn_sub_agent.py gemini "Read context-engine/epics/E01_Core_Infrastructure/epic-overview.md. Add a new section at line 50 titled 'Posts vs Events Architecture' with this content: [full content]. Save to the same file path."

python spawn_sub_agent.py gemini "Read context-engine/epics/E02_User_Profile_Management/epic-overview.md. Add a new section at line 60 titled 'Posts vs Events Architecture' with this content: [full content]. Save to the same file path."

# ... spawn 3 more for E05, E06, E07
```

**Example 2: Parallel Test File Creation**
```bash
# Task: Create 4 new test files for different features

python spawn_sub_agent.py gemini "Create a new Pest test file at tests/Feature/PostCreationTest.php. Test the post creation functionality with these test cases: [list cases]. Use the existing test structure from tests/Feature/ActivityCreationTest.php as a reference. Include setup, teardown, and all assertions."

python spawn_sub_agent.py gemini "Create a new Pest test file at tests/Feature/PostReactionTest.php. Test the post reaction functionality with these test cases: [list cases]. Use the existing test structure from tests/Feature/ActivityCreationTest.php as a reference. Include setup, teardown, and all assertions."

# ... spawn 2 more for PostConversionTest and PostExpirationTest
```

**Example 3: Parallel Code Refactoring**
```bash
# Task: Refactor 3 similar service classes to use a new base class

# First, create the base class (sequential, required first)
# ... create BaseDiscoveryService ...

# Then spawn 3 subagents to refactor each service:
python spawn_sub_agent.py gemini "Read app/Services/NearbyFeedService.php. Refactor it to extend BaseDiscoveryService (located at app/Services/BaseDiscoveryService.php). Move common methods to the base class: [list methods]. Preserve all existing functionality. Save to the same file path."

python spawn_sub_agent.py gemini "Read app/Services/ForYouFeedService.php. Refactor it to extend BaseDiscoveryService (located at app/Services/BaseDiscoveryService.php). Move common methods to the base class: [list methods]. Preserve all existing functionality. Save to the same file path."

# ... spawn 1 more for MapViewService
```

### When NOT to Use Subagents

**❌ DO NOT use subagents for:**

1. **Sequential Dependencies**: Task B needs Task A's output
   - Example: "Create migration, then create model, then create controller"
   - Solution: Do sequentially or spawn only the independent parts

2. **Shared State Modifications**: Multiple tasks editing the same file
   - Example: "Add 3 different sections to the same file"
   - Solution: Do sequentially or combine into one task

3. **Complex Coordination**: Tasks require back-and-forth communication
   - Example: "Refactor code and update tests to match"
   - Solution: Do as a single coordinated task

4. **Simple/Quick Tasks**: Overhead exceeds benefit
   - Example: "Update 2 lines in a file"
   - Solution: Just do it directly

5. **Exploratory Work**: Task requires discovery/analysis first
   - Example: "Find all files that need updating and update them"
   - Solution: Do discovery first, then spawn updates

6. **User Interaction Required**: Task needs user input/approval
   - Example: "Propose changes and wait for approval"
   - Solution: Get approval first, then spawn implementation

### Integration with Task Management

**Recommended Approach**:
1. **Create parent task**: "Update all epic documentation files"
2. **Mark as IN_PROGRESS**: Before spawning subagents
3. **Spawn subagents**: For each independent subtask
4. **Monitor completion**: Check `status.json` files
5. **Verify results**: Review `report.md` files
6. **Mark as COMPLETE**: After all subagents succeed

**Do NOT create separate task list entries for each subagent** - they are implementation details of the parent task.

### Monitoring and Verification

**After spawning subagents**:
1. **Wait briefly**: Give subagents time to complete (30-60 seconds for simple tasks)
2. **Check status**: `cat subagent_runs/{job_id}/status.json`
3. **Review output**: `cat subagent_runs/{job_id}/report.md`
4. **Verify files**: Check that output files were created/modified correctly
5. **Handle failures**: If a subagent fails, review `run.log` and retry with improved prompt

**Status Values**:
- `"running"`: Subagent is still executing
- `"completed"`: Subagent finished successfully (exit_code: 0)
- `"failed"`: Subagent encountered an error (exit_code: 1)

### Performance Guidelines

**Optimal Parallelization**:
- **2-10 subagents**: Sweet spot for most tasks
- **10+ subagents**: Possible but monitor system resources
- **1 subagent**: Only use if you need async execution (rare)

**Time Estimates**:
- Simple file updates: 30-60 seconds per subagent
- Code generation: 60-120 seconds per subagent
- Complex refactoring: 120-300 seconds per subagent

**Always consider**: Is the parallelization worth the coordination overhead?


=== php rules ===

## PHP Standards
- Always use curly braces for control structures
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`
- Always use explicit return type declarations
- Use appropriate type hints for parameters
- Prefer PHPDoc blocks over inline comments
- Enum keys should be TitleCase: `FavoritePerson`, `Monthly`


=== filament/core rules ===

## Filament
- Filament is a Server-Driven UI (SDUI) framework for Laravel built on Livewire, Alpine.js, and Tailwind CSS
- Use `search-docs` tool for Filament documentation when needed
- Use Filament Artisan commands to create files: check with `list-artisan-commands`
- Always pass `--no-interaction` to Artisan commands

### Core Features
- **Resources**: CRUD interfaces for Eloquent models in `app/Filament/Resources`
- **Forms**: Dynamic forms in resources, actions, filters
- **Tables**: Interactive tables with filtering, sorting, pagination
- **Actions**: Buttons/links with modals for one-time operations
- **Schemas**: Define UI structure (forms, tables, lists)

### Relationships
- Use `relationship()` method on form components for selects, checkboxes, repeaters


## Testing
- Test Filament functionality with `livewire()` or `Livewire::test()`
- Ensure authentication in tests
- Use `fillForm()`, `assertCanSeeTableRecords()`, `callAction()` for Filament tests


=== filament/v4 rules ===

## Filament 4

### Key v4 Changes
- File visibility defaults to `private`
- `deferFilters` is default (use `deferFilters(false)` to disable)
- Layout components don't span all columns by default
- All actions extend `Filament\Actions\Action`
- Form/Infolist layouts moved to `Filament\Schemas\Components`
- Use `Schema` type instead of `Form` in Livewire components with `InteractsWithForms`
- Use `->components([])` instead of `->schema([])` in form methods

### Component Organization
- Schema components: `Schemas/Components/`
- Table columns: `Tables/Columns/`
- Table filters: `Tables/Filters/`
- Actions: `Actions/`


=== laravel/core rules ===

## Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (migrations, controllers, models, etc.)
- Pass `--no-interaction` to all Artisan commands

### Database
- Use Eloquent relationships with return type hints
- Prefer `Model::query()` over `DB::`
- Prevent N+1 queries with eager loading
- Use query builder for complex operations

### Model Creation
- Create factories and seeders with new models

### Controllers & Validation
- Create Form Request classes for validation (not inline)
- Check sibling Form Requests for array vs string validation rules

### Configuration
- Use `config('app.name')` not `env('APP_NAME')` outside config files

### URL Generation
- Prefer named routes: `route('dashboard')`

### Testing
- Use model factories in tests
- Create feature tests with `php artisan make:test --pest <name>`
- Use `--unit` flag for unit tests

### Vite Error
- Run `npm run build` if Vite manifest error occurs


=== laravel/v12 rules ===

## Laravel 12

### Structure Changes
- No `app/Http/Middleware/` files
- Use `bootstrap/app.php` for middleware, exceptions, routing
- No `app/Console/Kernel.php` - commands auto-register from `app/Console/Commands/`

### Database
- Include all column attributes when modifying (or they'll be dropped)
- Limit eager loading: `$query->latest()->limit(10)`

### Models
- Use `casts()` method instead of `$casts` property (follow existing conventions)


=== livewire/core rules ===

## Livewire Core
- Use the `search-docs` tool to find exact version specific documentation for how to write Livewire & Livewire tests.
- Use the `php artisan make:livewire [Posts\\CreatePost]` artisan command to create new components
- State should live on the server, with the UI reflecting it.
- All Livewire requests hit the Laravel backend, they're like regular HTTP requests. Always validate form data, and run authorization checks in Livewire actions.

## Livewire Best Practices
- Livewire components require a single root element.
- Use `wire:loading` and `wire:dirty` for delightful loading states.
- Add `wire:key` in loops:

<code-snippet name="Lifecycle hook examples" lang="php">
    @foreach ($items as $item)
        <div wire:key="item-{{ $item->id }}">
            {{ $item->name }}
        </div>
    @endforeach
</code-snippet>

- Prefer lifecycle hooks like `mount()`, `updatedFoo()` for initialization and reactive side effects:

<code-snippet name="Lifecycle hook examples" lang="php">
    public function mount(User $user) { $this->user = $user; }
    public function updatedSearch() { $this->resetPage(); }
</code-snippet>


## Testing Livewire

<code-snippet name="Example Livewire component test" lang="php">
    Livewire::test(Counter::class)
        ->assertSet('count', 0)
        ->call('increment')
        ->assertSet('count', 1)
        ->assertSee(1)
        ->assertStatus(200);
</code-snippet>


    <code-snippet name="Testing a Livewire component exists within a page" lang="php">
        $this->get('/posts/create')
        ->assertSeeLivewire(CreatePost::class);
    </code-snippet>


=== livewire/v3 rules ===

## Livewire 3

### Key Changes From Livewire 2
- These things changed in Livewire 2, but may not have been updated in this application. Verify this application's setup to ensure you conform with application conventions.
    - Use `wire:model.live` for real-time updates, `wire:model` is now deferred by default.
    - Components now use the `App\Livewire` namespace (not `App\Http\Livewire`).
    - Use `$this->dispatch()` to dispatch events (not `emit` or `dispatchBrowserEvent`).
    - Use the `components.layouts.app` view as the typical layout path (not `layouts.app`).

### New Directives
- `wire:show`, `wire:transition`, `wire:cloak`, `wire:offline`, `wire:target` are available for use. Use the documentation to find usage examples.

### Alpine
- Alpine is now included with Livewire, don't manually include Alpine.js.
- Plugins included with Alpine: persist, intersect, collapse, and focus.

### Lifecycle Hooks
- You can listen for `livewire:init` to hook into Livewire initialization, and `fail.status === 419` for the page expiring:

<code-snippet name="livewire:load example" lang="js">
document.addEventListener('livewire:init', function () {
    Livewire.hook('request', ({ fail }) => {
        if (fail && fail.status === 419) {
            alert('Your session expired');
        }
    });

    Livewire.hook('message.failed', (message, component) => {
        console.error(message);
    });
});
</code-snippet>


=== pint/core rules ===

## Laravel Pint Code Formatter

- You must run `vendor/bin/pint --dirty` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test`, simply run `vendor/bin/pint` to fix any formatting issues.


=== pest/core rules ===

## Pest

### Testing
- If you need to verify a feature is working, write or update a Unit / Feature test.

### Pest Tests
- All tests must be written using Pest. Use `php artisan make:test --pest <name>`.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files - these are core to the application.
- Tests should test all of the happy paths, failure paths, and weird paths.
- Tests live in the `tests/Feature` and `tests/Unit` directories.
- Pest tests look and behave like this:
<code-snippet name="Basic Pest Test Example" lang="php">
it('is true', function () {
    expect(true)->toBeTrue();
});
</code-snippet>

### Running Tests
- Run the minimal number of tests using an appropriate filter before finalizing code edits.
- To run all tests: `php artisan test`.
- To run all tests in a file: `php artisan test tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `php artisan test --filter=testName` (recommended after making a change to a related file).
- When the tests relating to your changes are passing, ask the user if they would like to run the entire test suite to ensure everything is still passing.

### Pest Assertions
- When asserting status codes on a response, use the specific method like `assertForbidden` and `assertNotFound` instead of using `assertStatus(403)` or similar, e.g.:
<code-snippet name="Pest Example Asserting postJson Response" lang="php">
it('returns all', function () {
    $response = $this->postJson('/api/docs', []);

    $response->assertSuccessful();
});
</code-snippet>

### Mocking
- Mocking can be very helpful when appropriate.
- When mocking, you can use the `Pest\Laravel\mock` Pest function, but always import it via `use function Pest\Laravel\mock;` before using it. Alternatively, you can use `$this->mock()` if existing tests do.
- You can also create partial mocks using the same import or self method.

### Datasets
- Use datasets in Pest to simplify tests which have a lot of duplicated data. This is often the case when testing validation rules, so consider going with this solution when writing tests for validation rules.

<code-snippet name="Pest Dataset Example" lang="php">
it('has emails', function (string $email) {
    expect($email)->not->toBeEmpty();
})->with([
    'james' => 'james@laravel.com',
    'taylor' => 'taylor@laravel.com',
]);
</code-snippet>


=== pest/v4 rules ===

## Pest 4

- Pest v4 is a huge upgrade to Pest and offers: browser testing, smoke testing, visual regression testing, test sharding, and faster type coverage.
- Browser testing is incredibly powerful and useful for this project.
- Browser tests should live in `tests/Browser/`.
- Use the `search-docs` tool for detailed guidance on utilizing these features.

### Browser Testing
- You can use Laravel features like `Event::fake()`, `assertAuthenticated()`, and model factories within Pest v4 browser tests, as well as `RefreshDatabase` (when needed) to ensure a clean state for each test.
- Interact with the page (click, type, scroll, select, submit, drag-and-drop, touch gestures, etc.) when appropriate to complete the test.
- If requested, test on multiple browsers (Chrome, Firefox, Safari).
- If requested, test on different devices and viewports (like iPhone 14 Pro, tablets, or custom breakpoints).
- Switch color schemes (light/dark mode) when appropriate.
- Take screenshots or pause tests for debugging when appropriate.

### Example Tests

<code-snippet name="Pest Browser Test Example" lang="php">
it('may reset the password', function () {
    Notification::fake();

    $this->actingAs(User::factory()->create());

    $page = visit('/sign-in'); // Visit on a real browser...

    $page->assertSee('Sign In')
        ->assertNoJavascriptErrors() // or ->assertNoConsoleLogs()
        ->click('Forgot Password?')
        ->fill('email', 'nuno@laravel.com')
        ->click('Send Reset Link')
        ->assertSee('We have emailed your password reset link!')

    Notification::assertSent(ResetPassword::class);
});
</code-snippet>

<code-snippet name="Pest Smoke Testing Example" lang="php">
$pages = visit(['/', '/about', '/contact']);

$pages->assertNoJavascriptErrors()->assertNoConsoleLogs();
</code-snippet>
</laravel-boost-guidelines>
