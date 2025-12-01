--- /Users/ianbruce/Herd/funlynk/context-engine/tasks/E05_Social_Interaction/F03_Groups/README.md ---

# F03: Group Management

## Feature Overview
This feature enables users to create, manage, and interact with groups. Groups can be public or private, have members with different roles (admin, member), and facilitate shared activities and posts.

## Tasks

### T01: Database & Model Setup
- **Description**: Create `groups` table, `group_members` pivot table, `group_join_requests` table. Define Eloquent models and relationships.
- **Files**:
    - `database/migrations/*_create_groups_table.php`
    - `database/migrations/*_create_group_members_table.php`
    - `database/migrations/*_create_group_join_requests_table.php`
    - `app/Models/Group.php`
    - `app/Models/GroupMember.php`
    - `app/Models/GroupJoinRequest.php`
- **Artisan Commands**:
    ```bash
    php artisan make:model Group -m --no-interaction
    php artisan make:model GroupMember -m --no-interaction
    php artisan make:model GroupJoinRequest -m --no-interaction
    ```
- **Time Estimate**: 2 hours
- **Status**: ✅ COMPLETE (E01 Foundation)

### T02: Group Service & Business Logic
- **Description**: Implement core business logic for group creation, updates, deletion, member management, and join requests.
- **Files**:
    - `app/Services/GroupService.php`
    - `app/Events/GroupCreated.php`
    - `app/Events/GroupMemberJoined.php`
    - `app/Events/GroupMemberRemoved.php`
    - `app/Events/GroupJoinRequestApproved.php`
    - `app/Events/GroupJoinRequestReceived.php`
    - `app/Listeners/NotifyGroupCreator.php` (example)
- **Artisan Commands**:
    ```bash
    php artisan make:event GroupCreated --no-interaction
    php artisan make:event GroupMemberJoined --no-interaction
    php artisan make:event GroupMemberRemoved --no-interaction
    php artisan make:event GroupJoinRequestApproved --no-interaction
    php artisan make:event GroupJoinRequestReceived --no-interaction
    php artisan make:listener NotifyGroupCreator --event=GroupCreated --no-interaction
    ```
- **Time Estimate**: 4 hours
- **Status**: ✅ COMPLETE (E01 Foundation)

### T03: Filament Resources
- **Description**: Create Filament resources for `Group`, `GroupMember`, and `GroupJoinRequest` for admin management.
- **Files**:
    - `app/Filament/Resources/GroupResource.php`
    - `app/Filament/Resources/GroupMemberResource.php`
    - `app/Filament/Resources/GroupJoinRequestResource.php`
- **Artisan Commands**:
    ```bash
    php artisan make:filament-resource Group --generate --no-interaction
    php artisan make:filament-resource GroupMember --generate --no-interaction
    php artisan make:filament-resource GroupJoinRequest --generate --no-interaction
    ```
- **Time Estimate**: 3 hours
- **Status**: ✅ COMPLETE (E01 Foundation)

### T04: Create Group Livewire Component
- **Description**: Develop a user-facing Livewire component for creating new groups.
- **Files**:
    - `app/Livewire/Groups/CreateGroup.php`
    - `resources/views/livewire/groups/create-group.blade.php`
- **Artisan Commands**:
    ```bash
    php artisan make:livewire Groups/CreateGroup --no-interaction
    ```
- **Time Estimate**: 3 hours
- **Status**: ⏳ PENDING

### T05: Group Detail & Management Livewire Components
- **Description**: Create Livewire components for viewing group details, managing members, and handling join requests.
- **Files**:
    - `app/Livewire/Groups/ViewGroup.php`
    - `resources/views/livewire/groups/view-group.blade.php`
    - `app/Livewire/Groups/ManageMembers.php`
    - `resources/views/livewire/groups/manage-members.blade.php`
    - `app/Livewire/Groups/HandleJoinRequests.php`
    - `resources/views/livewire/groups/handle-join-requests.blade.php`
- **Artisan Commands**:
    ```bash
    php artisan make:livewire Groups/ViewGroup --no-interaction
    php artisan make:livewire Groups/ManageMembers --no-interaction
    php artisan make:livewire Groups/HandleJoinRequests --no-interaction
    ```
- **Time Estimate**: 6 hours
- **Status**: ⏳ PENDING

### T06: Group Search & Discovery Livewire Component
- **Description**: Implement a Livewire component for searching and discovering public groups, potentially with tag filtering.
- **Files**:
    - `app/Livewire/Groups/SearchGroups.php`
    - `resources/views/livewire/groups/search-groups.blade.php`
- **Artisan Commands**:
    ```bash
    php artisan make:livewire Groups/SearchGroups --no-interaction
    ```
- **Time Estimate**: 4 hours
- **Status**: ⏳ PENDING

### T07: Group Policies
- **Description**: Define authorization policies for group actions (create, view, update, delete, join, leave, manage members).
- **Files**:
    - `app/Policies/GroupPolicy.php`
    - `app/Policies/GroupMemberPolicy.php`
    - `app/Policies/GroupJoinRequestPolicy.php`
- **Artisan Commands**:
    ```bash
    php artisan make:policy GroupPolicy --model=Group --no-interaction
    php artisan make:policy GroupMemberPolicy --model=GroupMember --no-interaction
    php artisan make:policy GroupJoinRequestPolicy --model=GroupJoinRequest --no-interaction
    ```
- **Time Estimate**: 3 hours
- **Status**: ⏳ PENDING

### T08: Tests
- **Description**: Write Pest tests for all group-related features, including service logic, Livewire components, and policies.
- **Files**:
    - `tests/Feature/GroupsTest.php`
    - `tests/Unit/GroupServiceTest.php`
    - `tests/Unit/GroupPolicyTest.php`
- **Artisan Commands**:
    ```bash
    php artisan make:test Feature/GroupsTest --pest --no-interaction
    php artisan make:test Unit/GroupServiceTest --pest --no-interaction
    php artisan make:test Unit/GroupPolicyTest --pest --no-interaction
    ```
- **Time Estimate**: 8 hours
- **Status**: ⏳ PENDING

--- End of content ---
The context files provide a good understanding of the UI design standards, the `Group` model, and the `GroupService`. The `login.blade.php` file serves as an excellent example for applying the galaxy theme and glass morphism to a form.

Here's the plan:

**1. Create Livewire Component (`app/Livewire/Groups/CreateGroup.php`)**
   - Define properties: `$name`, `$description`, `$privacy`, `$selectedTags`, `$location`.
   - Implement `mount()` method to initialize properties if needed (e.g., fetch available tags).
   - Implement `rules()` method for validation.
   - Implement `createGroup()` method:
     - Validate input.
     - Use `GroupService` to create the group.
     - Redirect or emit an event on success.
   - Implement `addTag()` and `removeTag()` methods for managing selected tags.
   - Implement `updated()` method for real-time validation.

**2. Create Blade View (`resources/views/livewire/groups/create-group.blade.php`)**
   - Use the `<x-galaxy-layout>` (or similar, based on `login.blade.php`'s structure) for the overall page.
   - Wrap the form in a `glass-card` with `top-accent-center`.
   - Use DaisyUI classes combined with the custom galaxy theme styles for inputs, select, and buttons.
   - Implement form fields:
     - Name (text input)
     - Description (textarea)
     - Privacy (select dropdown: public/private)
     - Tags (multi-select, possibly with a search input and badges for selected tags)
     - Location (text input, will need to consider how geocoding will be integrated later, for now, just a text input)
   - Display validation errors.
   - Use the gradient submit button.

**Detailed breakdown for the component:**

**`app/Livewire/Groups/CreateGroup.php`**

```php
<?php

namespace App\Livewire\Groups;

use Livewire\Component;
use App\Services\GroupService;
use App\Models\Tag;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CreateGroup extends Component
{
    public string $name = '';
    public string $description = '';
    public string $privacy = 'public'; // Default to public
    public array $availableTags = [];
    public array $selectedTags = [];
    public string $location = ''; // Placeholder for location, geocoding to be integrated later

    protected GroupService $groupService;

    public function boot(GroupService $groupService)
    {
        $this->groupService = $groupService;
    }

    public function mount()
    {
        $this->availableTags = Tag::all()->pluck('name', 'id')->toArray();
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'description' => ['required', 'string', 'max:500'],
            'privacy' => ['required', 'string', Rule::in(['public', 'private'])],
            'selectedTags' => ['nullable', 'array'],
            'selectedTags.*' => ['exists:tags,id'],
            'location' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function messages(): array
    {
        return [
            'name.required' => 'A group name is required.',
            'name.max' => 'The group name cannot exceed 100 characters.',
            'description.required' => 'A description is required for the group.',
            'description.max' => 'The description cannot exceed 500 characters.',
            'privacy.required' => 'Group privacy setting is required.',
            'privacy.in' => 'Invalid privacy setting.',
            'selectedTags.*.exists' => 'One or more selected tags are invalid.',
        ];
    }

    public function updated($propertyName)
    {
        $this->validateOnly($propertyName);
    }

    public function createGroup()
    {
        $this->validate();

        try {
            $group = $this->groupService->createGroup(Auth::user(), [
                'name' => $this->name,
                'description' => $this->description,
                'privacy' => $this->privacy,
                'tags' => $this->selectedTags,
                // 'location' => $this->location, // Will be added when geocoding is integrated
            ]);

            session()->flash('message', 'Group created successfully!');
            return redirect()->to('/groups/' . $group->slug); // Redirect to group detail page
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to create group: ' . $e->getMessage());
        }
    }

    public function addTag($tagId)
    {
        if (!in_array($tagId, $this->selectedTags)) {
            $this->selectedTags[] = $tagId;
        }
    }

    public function removeTag($tagId)
    {
        $this->selectedTags = array_diff($this->selectedTags, [$tagId]);
    }

    public function render()
    {
        return view('livewire.groups.create-group');
    }
}

```

**`resources/views/livewire/groups/create-group.blade.php`**

```blade
<div class="min-h-screen flex items-center justify-center px-4 py-12">
    <div class="w-full max-w-2xl">
        <div class="text-center mb-8">
            <h2 class="text-3xl font-bold">
                <span class="text-yellow-400">Create</span> <span class="text-cyan-400">New Group</span>
            </h2>
            <p class="text-gray-400 mt-2">Start a new community around your interests</p>
        </div>

        <div class="relative p-8 glass-card">
            <div class="top-accent-center"></div>

            @if (session()->has('message'))
                <div class="mb-6 p-4 rounded-xl bg-green-500/20 border border-green-500/50 flex items-center gap-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2m4-4l-4 4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="text-green-200">{{ session('message') }}</span>
                </div>
            @endif

            @if (session()->has('error'))
                <div class="mb-6 p-4 rounded-xl bg-red-500/20 border border-red-500/50 flex items-center gap-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="text-red-200">{{ session('error') }}</span>
                </div>
            @endif

            <form wire:submit.prevent="createGroup">
                <div class="mb-4">
                    <label for="name" class="block text-gray-300 text-sm font-semibold mb-2">Group Name</label>
                    <input type="text" id="name" wire:model.live="name"
                           class="w-full px-4 py-3 bg-slate-800/50 border border-white/10 rounded-2xl focus:border-cyan-500/50 focus:outline-none transition text-white"
                           placeholder="e.g., Local Hiking Enthusiasts">
                    @error('name') <span class="text-red-400 text-xs mt-1">{{ $message }}</span> @enderror
                </div>

                <div class="mb-4">
                    <label for="description" class="block text-gray-300 text-sm font-semibold mb-2">Description</label>
                    <textarea id="description" wire:model.live="description"
                              class="w-full px-4 py-3 bg-slate-800/50 border border-white/10 rounded-2xl focus:border-cyan-500/50 focus:outline-none transition text-white"
                              rows="4" placeholder="Tell us what your group is about..."></textarea>
                    @error('description') <span class="text-red-400 text-xs mt-1">{{ $message }}</span> @enderror
                </div>

                <div class="mb-4">
                    <label for="privacy" class="block text-gray-300 text-sm font-semibold mb-2">Privacy</label>
                    <select id="privacy" wire:model="privacy"
                            class="w-full px-4 py-3 bg-slate-800/50 border border-white/10 rounded-2xl focus:border-cyan-500/50 focus:outline-none transition text-white">
                        <option value="public">Public (Anyone can see and join)</option>
                        <option value="private">Private (Only members can see content, join by invitation or approval)</option>
                    </select>
                    @error('privacy') <span class="text-red-400 text-xs mt-1">{{ $message }}</span> @enderror
                </div>

                <div class="mb-4">
                    <label for="tags" class="block text-gray-300 text-sm font-semibold mb-2">Tags (Optional)</label>
                    <div class="relative">
                        <select id="tags" wire:change="addTag($event.target.value)"
                                class="w-full px-4 py-3 bg-slate-800/50 border border-white/10 rounded-2xl focus:border-cyan-500/50 focus:outline-none transition text-white">
                            <option value="">Select tags...</option>
                            @foreach($availableTags as $id => $name)
                                <option value="{{ $id }}" @if(in_array($id, $selectedTags)) disabled @endif>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach($selectedTags as $tagId)
                            <span class="px-3 py-1 bg-purple-500/20 text-purple-300 rounded-full text-xs flex items-center gap-1">
                                {{ $availableTags[$tagId] ?? 'Unknown Tag' }}
                                <button type="button" wire:click="removeTag({{ $tagId }})" class="text-purple-300 hover:text-white transition">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </span>
                        @endforeach
                    </div>
                    @error('selectedTags') <span class="text-red-400 text-xs mt-1">{{ $message }}</span> @enderror
                </div>

                <div class="mb-6">
                    <label for="location" class="block text-gray-300 text-sm font-semibold mb-2">Location (Optional)</label>
                    <input type="text" id="location" wire:model.live="location"
                           class="w-full px-4 py-3 bg-slate-800/50 border border-white/10 rounded-2xl focus:border-cyan-500/50 focus:outline-none transition text-white"
                           placeholder="e.g., New York, NY">
                    @error('location') <span class="text-red-400 text-xs mt-1">{{ $message }}</span> @enderror
                </div>

                <div>
                    <button type="submit" class="w-full px-6 py-3 bg-gradient-to-r from-pink-500 to-purple-500 rounded-xl font-semibold hover:scale-105 transition-all">
                        Create Group
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

```

I will now create the Livewire component and its view.
I have completed the task of creating the Livewire v3 component for creating a new group.

The following files have been created and updated:
- `app/Livewire/Groups/CreateGroup.php`
- `resources/views/livewire/groups/create-group.blade.php`
- `dev-logs/2025-12-01-12.md`

The component includes form fields for name, description, privacy, tags, and location, with real-time validation and UI elements adhering to the galaxy theme and glass morphism standards. The code has been formatted using `pint`.