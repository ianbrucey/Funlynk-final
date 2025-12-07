# Integration Contracts: Groups Feature

This document outlines the API endpoints, validation rules, and real-time events for the Groups feature. Team 3 (Frontend) should use this as the source of truth for all backend interactions.

**Last Updated**: 2025-12-01

---

## 1. API Endpoints

All endpoints are prefixed with `/api` and require authentication.

### Groups

-   **[POST] `/api/groups`**
    -   **Description**: Create a new group.
    -   **Request Body**: `CreateGroupRequest` (see Validation Rules)
    -   **Response**: `Group` object (JSON)
-   **[PUT] `/api/groups/{group}`**
    -   **Description**: Update an existing group.
    -   **Request Body**: `UpdateGroupRequest` (see Validation Rules)
    -   **Response**: `Group` object (JSON)
-   **[DELETE] `/api/groups/{group}`**
    -   **Description**: Delete a group.
    -   **Response**: `204 No Content`
-   **[POST] `/api/groups/{group}/join`**
    -   **Description**: Request to join a group (or directly join if public).
    -   **Response**: `GroupMember` or `GroupJoinRequest` object (JSON)
-   **[POST] `/api/groups/{group}/leave`**
    -   **Description**: Leave a group.
    -   **Response**: `204 No Content`
-   **[GET] `/api/groups/search`**
    -   **Description**: Search for public groups.
    -   **Query Params**: `query` (string), `tag_ids[]` (array of int)
    -   **Response**: `Collection` of `Group` objects (JSON)
-   **[GET] `/api/groups/{group}/members`**
    -   **Description**: Get group members.
    -   **Response**: `Collection` of `GroupMember` objects (JSON)
-   **[POST] `/api/groups/{group}/members/{user}/remove`**
    -   **Description**: Remove a member from a group.
    -   **Response**: `204 No Content`
-   **[POST] `/api/groups/{group}/join-requests/{request}/approve`**
    -   **Description**: Approve a join request.
    -   **Response**: `204 No Content`
-   **[POST] `/api/groups/{group}/join-requests/{request}/deny`**
    -   **Description**: Deny a join request.
    -   **Response**: `204 No Content`

### Group Content (Posts & Events)

-   **[POST] `/api/groups/{group}/posts`**
    -   **Description**: Create a new post within a group.
    -   **Request Body**: `CreateGroupPostRequest` (see Validation Rules)
    -   **Response**: `Post` object (JSON)
-   **[POST] `/api/groups/{group}/events`**
    -   **Description**: Create a new event within a group.
    -   **Request Body**: `CreateGroupEventRequest` (see Validation Rules)
    -   **Response**: `Activity` object (JSON)
-   **[GET] `/api/groups/{group}/timeline`**
    -   **Description**: Get a paginated timeline of posts and events within a group.
    -   **Query Params**: `page` (int), `per_page` (int)
    -   **Response**: `Collection` of `Post` and `Activity` objects (JSON)

### Group Chat

-   **[GET] `/api/groups/{group}/chat`**
    -   **Description**: Get or create the chat conversation for a group.
    -   **Response**: `Conversation` object (JSON)
-   **[POST] `/api/groups/{group}/chat/messages`**
    -   **Description**: Send a message to the group chat.
    -   **Request Body**: `JSON: { "message": "string" }`
    -   **Response**: `Message` object (JSON)
-   **[GET] `/api/groups/{group}/chat/messages`**
    -   **Description**: Get recent messages from the group chat.
    -   **Query Params**: `limit` (int)
    -   **Response**: `Collection` of `Message` objects (JSON)

---

## 2. Validation Rules

### CreateGroupRequest
```php
'name' => 'required|string|max:100|unique:groups,name',
'description' => 'nullable|string|max:1000',
'avatar_url' => 'nullable|url|max:255',
'cover_image_url' => 'nullable|url|max:255',
'privacy' => 'required|in:public,private',
'tags' => 'nullable|array',
'tags.*' => 'exists:tags,id',
```

### UpdateGroupRequest
```php
'name' => 'sometimes|string|max:100|unique:groups,name,' . $this->group->id,
'description' => 'nullable|string|max:1000',
'avatar_url' => 'nullable|url|max:255',
'cover_image_url' => 'nullable|url|max:255',
'privacy' => 'sometimes|in:public,private',
'tags' => 'nullable|array',
'tags.*' => 'exists:tags,id',
```

### CreateGroupPostRequest
```php
'title' => 'required|string|max:255',
'description' => 'required|string',
'location_name' => 'nullable|string|max:255',
'location_coordinates' => 'nullable', // PostGIS point
'expires_at' => 'nullable|date|after:now',
'tags' => 'nullable|array',
'tags.*' => 'exists:tags,id',
```

### CreateGroupEventRequest
```php
'title' => 'required|string|max:255',
'description' => 'required|string',
'location_name' => 'required|string|max:255',
'location_coordinates' => 'required', // PostGIS point
'start_time' => 'required|date|after:now',
'end_time' => 'required|date|after:start_time',
'max_attendees' => 'nullable|integer|min:1',
'tags' => 'nullable|array',
'tags.*' => 'exists:tags,id',
```

---

## 3. Real-Time Events (Laravel Reverb)

Listen for these events on the specified private channels.

### Channels

-   **Group Channel**: `group.{group.id}`
-   **User Channel**: `user.{user.id}`

### Events

-   **`GroupPostCreated`**
    -   **Channel**: `group.{group.id}`
    -   **Payload**: `Post` object
-   **`GroupEventCreated`**
    -   **Channel**: `group.{group.id}`
    -   **Payload**: `Activity` object
-   **`GroupMemberJoined`**
    -   **Channel**: `group.{group.id}`
    -   **Payload**: `User` object
-   **`GroupMemberRemoved`**
    -   **Channel**: `group.{group.id}`
    -   **Payload**: `User` object
-   **`GroupJoinRequestReceived`**
    -   **Channel**: `user.{admin.id}` (for each group admin)
    -   **Payload**: `GroupJoinRequest` object
-   **`GroupJoinRequestApproved`**
    -   **Channel**: `user.{user.id}` (for the user who requested to join)
    -   **Payload**: `Group` object