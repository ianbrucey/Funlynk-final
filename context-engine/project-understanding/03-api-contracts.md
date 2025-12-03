# API Contracts

**Generated:** 2025-12-02

## Overview

FunLynk is primarily a **Livewire-based application** with minimal traditional REST API endpoints. Most interactions happen through Livewire components that communicate via AJAX.

## Route Structure

### Web Routes (`routes/web.php`)

All user-facing routes are defined in `routes/web.php` and render Livewire components.

#### Authentication Routes (Guest Only)
```
GET  /register              → App\Livewire\Auth\Register
GET  /login                 → App\Livewire\Auth\Login
```

#### Onboarding Routes (Authenticated)
```
GET  /onboarding            → App\Livewire\Onboarding\OnboardingWizard
```

#### Profile Routes (Authenticated + Onboarded)
```
GET  /profile               → App\Livewire\Profile\ShowProfile (own profile)
GET  /profile/edit          → App\Livewire\Profile\EditProfile
GET  /u/{username}          → App\Livewire\Profile\ShowProfile (other user)
```

#### Post Routes (Authenticated + Onboarded)
```
GET  /posts/create          → App\Livewire\Posts\CreatePost
GET  /posts/{post}          → App\Livewire\Posts\PostDetail
GET  /posts/{post}/chat     → App\Livewire\Posts\PostChat
```

#### Activity Routes (Authenticated + Onboarded)
```
GET  /activities            → Activities Index (placeholder)
GET  /activities/create     → App\Livewire\Activities\CreateActivity
GET  /activities/{activity} → App\Livewire\Activities\ActivityDetail
GET  /activities/{activity}/edit → App\Livewire\Activities\EditActivity
GET  /activities/{activity}/checkout → App\Livewire\Payments\CheckoutForm
```

#### Discovery Routes (Authenticated + Onboarded)
```
GET  /feed/nearby           → App\Livewire\Discovery\NearbyFeed
GET  /feed/for-you          → App\Livewire\Discovery\ForYouFeed
GET  /map                   → App\Livewire\Discovery\MapView
GET  /search                → Redirects to /feed/nearby?q={query}
GET  /search/users          → App\Livewire\Search\SearchUsers
```

#### Messaging Routes (Authenticated + Onboarded)
```
GET  /messages              → App\Livewire\DirectMessages\MessagesPage
GET  /messages/requests     → App\Livewire\DirectMessages\MessagesPage (requests tab)
GET  /messages/{conversation} → App\Livewire\DirectMessages\MessagesPage
```

#### Group Routes (Authenticated + Onboarded)
```
GET  /groups                → App\Livewire\Groups\GroupsIndex
GET  /groups/create         → App\Livewire\Groups\CreateGroup
GET  /groups/{group:slug}   → App\Livewire\Groups\GroupShow
GET  /groups/{group:slug}/members → App\Livewire\Groups\GroupMembers
GET  /groups/{group:slug}/timeline → App\Livewire\Groups\GroupTimeline
GET  /groups/{group:slug}/settings → App\Livewire\Groups\GroupSettings
```

#### Notification Routes (Authenticated + Onboarded)
```
GET  /notifications         → App\Livewire\Notifications\NotificationList
```

#### Settings Routes (Authenticated + Onboarded)
```
GET  /settings/notifications → App\Livewire\Settings\NotificationPreferences
```

#### Payment Routes (Authenticated + Onboarded)
```
GET  /host/stripe-onboarding → App\Livewire\Payments\StripeOnboarding
GET  /host/stripe-return     → App\Livewire\Payments\StripeOnboarding (return URL)
GET  /host/stripe-refresh    → App\Livewire\Payments\StripeOnboarding (refresh URL)
```

#### Social OAuth Routes
```
GET  /auth/{provider}/redirect → SocialLoginController@redirect
GET  /auth/{provider}/callback → SocialLoginController@callback
```
Supported providers: `google`, `facebook`

### API Routes (`routes/api.php`)

Minimal REST API endpoints for specific use cases.

#### Username Validation
```
POST /api/check-username
```

**Request:**
```json
{
  "username": "string"
}
```

**Response:**
```json
{
  "available": true|false,
  "message": "Username is available" | "Username is already taken"
}
```

**Rate Limit:** 60 requests per minute

## Livewire Component Contracts

Livewire components expose public methods and properties that act as implicit API contracts.

### Common Livewire Patterns

#### 1. Feed Components (NearbyFeed, ForYouFeed)

**Public Properties:**
- `$posts` - Collection of Post models
- `$page` - Current page number for pagination
- `$hasMore` - Boolean indicating more results

**Public Methods:**
- `loadMore()` - Load next page of results
- `react($postId, $type)` - React to a post
- `unreact($postId)` - Remove reaction

#### 2. Post Components (CreatePost, PostDetail)

**Public Properties:**
- `$post` - Post model instance
- `$title` - Post title
- `$description` - Post description
- `$locationName` - Location name
- `$latitude` - Latitude coordinate
- `$longitude` - Longitude coordinate

**Public Methods:**
- `save()` - Create/update post
- `delete()` - Delete post
- `convertToEvent()` - Convert post to activity

#### 3. Activity Components (CreateActivity, ActivityDetail)

**Public Properties:**
- `$activity` - Activity model instance
- `$title` - Activity title
- `$description` - Activity description
- `$startTime` - Start timestamp
- `$capacity` - Max attendees
- `$price` - Ticket price

**Public Methods:**
- `save()` - Create/update activity
- `rsvp()` - RSVP to activity
- `cancelRsvp()` - Cancel RSVP

#### 4. Chat Components (PostChat, DirectMessages)

**Public Properties:**
- `$conversation` - Conversation model
- `$messages` - Collection of Message models
- `$newMessage` - Message input text

**Public Methods:**
- `sendMessage()` - Send new message
- `loadOlderMessages()` - Paginate message history

## WebSocket Events (Laravel Reverb)

Real-time events broadcast via WebSocket channels.

### Private Channels

#### User Channel: `private-user.{userId}`
Events:
- `MessageSent` - New direct message received
- `MessageRequestReceived` - New message request
- `PostReacted` - Someone reacted to user's post
- `PostConversionSuggested` - Post eligible for conversion
- `ActivityInvitationSent` - Invited to activity
- `GroupNotification` - Group-related updates

#### Conversation Channel: `private-conversation.{conversationId}`
Events:
- `MessageSent` - New message in conversation

#### Post Channel: `private-post.{postId}`
Events:
- `PostReacted` - New reaction on post
- `PostConvertedToEvent` - Post converted to activity

#### Activity Channel: `private-activity.{activityId}`
Events:
- `RsvpCreated` - New RSVP
- `RsvpCancelled` - RSVP cancelled

#### Group Channel: `private-group.{groupId}`
Events:
- `GroupMemberJoined` - New member joined
- `GroupPostCreated` - New post in group
- `GroupEventCreated` - New event in group

## External API Integrations

### Stripe API

#### Create Payment Intent
```php
StripeConnectService::createPaymentIntent($activity, $user)
```

#### Create Connect Account
```php
StripeConnectService::createConnectAccount($user)
```

#### Generate Onboarding Link
```php
StripeConnectService::createAccountLink($stripeAccountId)
```

### Meilisearch API

#### Index Posts
```php
Post::search($query)->get()
```

#### Index Activities
```php
Activity::search($query)->get()
```

#### Index Users
```php
User::search($query)->get()
```

## Response Formats

### Livewire Component Responses

Livewire automatically handles JSON responses for AJAX requests. Components return:

**Success:**
```json
{
  "effects": {
    "html": "<updated-component-html>",
    "dirty": ["propertyName"],
    "dispatches": []
  }
}
```

**Validation Error:**
```json
{
  "errors": {
    "fieldName": ["Error message"]
  }
}
```

### Flash Messages

Components use Laravel's session flash for user feedback:
```php
session()->flash('success', 'Post created successfully!');
session()->flash('error', 'Failed to create post.');
```

## Authentication

### Session-Based Authentication
- Uses Laravel's built-in session authentication
- Session cookie: `funlynk_session`
- CSRF token required for all POST/PUT/DELETE requests

### Middleware Stack
```
web → auth → onboarding.complete
```

- `web`: Session, CSRF, cookie encryption
- `auth`: Requires authenticated user
- `onboarding.complete`: Requires completed onboarding

