# Project Identity

**Generated:** 2025-12-02  
**Project:** FunLynk  
**Repository:** `/Users/ianbruce/Herd/funlynk`

## Overview

FunLynk is a Laravel-based web application for spontaneous, niche activity discovery. The platform enables users to discover and participate in local activities through two distinct content types: ephemeral "Posts" (24-48h lifespan) that can evolve into structured "Events" based on community engagement.

## Business Domain

**Primary Use Case:** Location-based social activity discovery and coordination  
**Target Users:** People seeking spontaneous local activities and event organizers  
**Core Value Proposition:** Bridge between casual interest (Posts) and committed participation (Events)

## Tech Stack

### Backend Framework
- **Laravel 12** - PHP web application framework
- **PHP 8.2+** - Required PHP version

### Frontend Stack
- **Livewire v3** - Full-stack framework for dynamic interfaces
- **DaisyUI** - Tailwind CSS component library
- **Vite** - Frontend build tool
- **Alpine.js** (via Livewire) - Lightweight JavaScript framework

### Database & Spatial
- **PostgreSQL** - Primary database
- **PostGIS** - Spatial database extension for location queries
- **matanyadaev/laravel-eloquent-spatial** - Laravel PostGIS integration

### Admin Panel
- **Filament v4** - Admin panel and resource management

### Search
- **Laravel Scout** - Search abstraction layer
- **Meilisearch** - Primary search engine
- **PostgreSQL Full-Text Search** - Fallback search implementation

### Real-Time Features
- **Laravel Reverb** - WebSocket server for real-time updates
- **Pusher PHP Server** - Broadcasting driver

### Payments
- **Stripe PHP SDK** - Payment processing
- **Stripe Connect** - Host payout system

### Testing
- **Pest v4** - PHP testing framework
- **PHPUnit** (via Pest) - Underlying test runner

### Development Tools
- **Laravel Pint** - Code style fixer
- **Laravel Sail** - Docker development environment
- **Laravel Pail** - Log viewer
- **Faker** - Test data generation

## Project Structure

```
funlynk/
├── app/
│   ├── Broadcasting/       # WebSocket channel authorization
│   ├── Events/            # Domain events (PostCreated, GroupJoined, etc.)
│   ├── Filament/          # Admin panel resources
│   ├── Http/              # Controllers, middleware, requests
│   ├── Jobs/              # Queued background jobs
│   ├── Listeners/         # Event listeners
│   ├── Livewire/          # User-facing UI components
│   ├── Models/            # Eloquent models
│   ├── Notifications/     # Notification classes
│   ├── Policies/          # Authorization policies
│   ├── Providers/         # Service providers
│   └── Services/          # Business logic layer
├── config/                # Application configuration
├── context-engine/        # Project documentation system
│   ├── domain-contexts/   # Cross-cutting technical standards
│   ├── epics/            # Feature module documentation
│   └── tasks/            # Implementation task breakdowns
├── database/
│   ├── factories/        # Model factories for testing
│   ├── migrations/       # Database schema migrations
│   └── seeders/          # Database seeders
├── resources/
│   ├── css/              # Stylesheets
│   ├── js/               # JavaScript assets
│   └── views/            # Blade templates
├── routes/               # Route definitions
├── tests/
│   ├── Feature/          # Feature tests
│   └── Unit/             # Unit tests
└── spawn_sub_agent.py    # Multi-agent workflow orchestration
```

## Core Architectural Concepts

### 1. Posts vs Events Dual Model
The platform's defining architectural pattern:

- **Posts**: Ephemeral content (24-48h TTL), 5-10km radius, casual engagement
- **Events**: Persistent content, 25-50km radius, formal RSVPs, payments
- **Conversion Flow**: Posts with sufficient engagement (5+ reactions) can convert to Events

### 2. Location-First Design
All content is spatially indexed using PostGIS geography columns:
- Posts use 5-10km discovery radius
- Events use 25-50km discovery radius
- Spatial queries use `whereDistance()` for efficient location-based filtering

### 3. Real-Time Social Features
- WebSocket-based notifications via Laravel Reverb
- Live chat for Posts, Events, Groups, and Direct Messages
- Real-time reaction updates and engagement tracking

### 4. Multi-Tenant Chat Architecture
Unified chat system supporting multiple contexts:
- Post discussions
- Event coordination
- Group conversations
- Direct messages (1-on-1)
- Message requests for non-mutual follows

### 5. Monetization via Stripe Connect
- Hosts can charge for Events
- Platform uses Stripe Connect for payouts
- Transaction tracking and fee management

## Key Dependencies

```json
{
  "filament/filament": "^4.0",
  "laravel/framework": "^12.0",
  "laravel/reverb": "^1.6",
  "laravel/scout": "^10.22",
  "matanyadaev/laravel-eloquent-spatial": "^4.5",
  "meilisearch/meilisearch-php": "^1.16",
  "pusher/pusher-php-server": "^7.2",
  "stripe/stripe-php": "^19.0"
}
```

## Entry Points

- **Web Routes:** `routes/web.php` - All user-facing routes
- **API Routes:** `routes/api.php` - API endpoints (minimal, mostly Livewire)
- **Admin Panel:** `/admin` - Filament admin interface
- **Main Landing:** `/` - Welcome page (routes to `resources/views/welcome.blade.php`)

## Development Commands

```bash
# Start development servers (concurrent)
composer dev

# Run tests
php artisan test

# Code formatting
vendor/bin/pint --dirty

# Database migrations
php artisan migrate

# Queue worker
php artisan queue:listen
```

