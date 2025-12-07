# Deployment & Infrastructure

**Generated:** 2025-12-02

## Development Environment

### Local Setup

**Requirements:**
- PHP 8.2+
- PostgreSQL with PostGIS extension
- Redis (for queues and cache)
- Node.js 18+ (for Vite)
- Composer
- NPM/Yarn

**Environment File (`.env`):**
```env
APP_NAME=FunLynk
APP_ENV=local
APP_KEY=base64:...
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=funlynk
DB_USERNAME=postgres
DB_PASSWORD=

BROADCAST_DRIVER=reverb
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=database

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MEILISEARCH_HOST=http://127.0.0.1:7700
MEILISEARCH_KEY=

STRIPE_KEY=pk_test_...
STRIPE_SECRET=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...

REVERB_APP_ID=...
REVERB_APP_KEY=...
REVERB_APP_SECRET=...
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http
```

### Development Commands

**Initial Setup:**
```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
npm run build
```

**Start Development Servers:**
```bash
# All services (concurrent)
composer dev

# Individual services
php artisan serve              # Laravel server (port 8000)
php artisan queue:listen       # Queue worker
php artisan reverb:start       # WebSocket server (port 8080)
npm run dev                    # Vite dev server
php artisan pail               # Log viewer
```

## Database Management

### Migrations

**Run migrations:**
```bash
php artisan migrate
```

**Rollback:**
```bash
php artisan migrate:rollback
php artisan migrate:rollback --step=1
```

**Fresh migration (destructive):**
```bash
php artisan migrate:fresh
php artisan migrate:fresh --seed
```

**Check migration status:**
```bash
php artisan migrate:status
```

### PostGIS Setup

PostGIS extension must be enabled in PostgreSQL:

```sql
CREATE EXTENSION IF NOT EXISTS postgis;
```

Verify PostGIS is working:
```bash
php artisan test --filter=PostGISSetupTest
```

### Seeders

**Run all seeders:**
```bash
php artisan db:seed
```

**Run specific seeder:**
```bash
php artisan db:seed --class=UserSeeder
```

## Queue System

### Queue Configuration

**Driver:** Redis (production) / Database (development)

**Queue Names:**
- `default` - General background jobs
- `notifications` - Notification processing
- `emails` - Email sending

### Running Queue Workers

**Development:**
```bash
php artisan queue:listen
```

**Production:**
```bash
php artisan queue:work --tries=3 --timeout=90
```

**Supervisor Configuration (Production):**
```ini
[program:funlynk-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/path/to/storage/logs/worker.log
stopwaitsecs=3600
```

## Search Infrastructure

### Meilisearch

**Installation:**
```bash
# macOS
brew install meilisearch

# Linux
curl -L https://install.meilisearch.com | sh
```

**Start Meilisearch:**
```bash
meilisearch --master-key="YOUR_MASTER_KEY"
```

**Index Models:**
```bash
php artisan scout:import "App\Models\Post"
php artisan scout:import "App\Models\Activity"
php artisan scout:import "App\Models\User"
```

**Flush Indexes:**
```bash
php artisan scout:flush "App\Models\Post"
```

## WebSocket Server (Reverb)

### Configuration

**File:** `config/reverb.php`

```php
'apps' => [
    [
        'id' => env('REVERB_APP_ID'),
        'key' => env('REVERB_APP_KEY'),
        'secret' => env('REVERB_APP_SECRET'),
        'options' => [
            'host' => env('REVERB_HOST', '0.0.0.0'),
            'port' => env('REVERB_PORT', 8080),
            'scheme' => env('REVERB_SCHEME', 'http'),
        ],
    ],
],
```

### Running Reverb

**Development:**
```bash
php artisan reverb:start
```

**Production (with Supervisor):**
```ini
[program:funlynk-reverb]
command=php /path/to/artisan reverb:start
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/path/to/storage/logs/reverb.log
```

## Asset Compilation

### Vite Configuration

**File:** `vite.config.js`

```javascript
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
});
```

### Build Commands

**Development:**
```bash
npm run dev
```

**Production:**
```bash
npm run build
```

## Production Deployment

### Server Requirements

- PHP 8.2+ with extensions: pdo_pgsql, redis, gd, mbstring, xml, curl
- PostgreSQL 14+ with PostGIS 3.3+
- Redis 6+
- Nginx or Apache
- Supervisor (for queue workers and Reverb)
- SSL certificate (Let's Encrypt recommended)

### Deployment Steps

1. **Clone repository:**
```bash
git clone https://github.com/your-repo/funlynk.git
cd funlynk
```

2. **Install dependencies:**
```bash
composer install --optimize-autoloader --no-dev
npm install
npm run build
```

3. **Configure environment:**
```bash
cp .env.example .env
php artisan key:generate
# Edit .env with production values
```

4. **Run migrations:**
```bash
php artisan migrate --force
```

5. **Optimize:**
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

6. **Set permissions:**
```bash
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

7. **Start services:**
```bash
supervisorctl start funlynk-worker:*
supervisorctl start funlynk-reverb
```

### Nginx Configuration

```nginx
server {
    listen 80;
    server_name funlynk.com;
    root /var/www/funlynk/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

## Monitoring & Logging

### Log Files

**Location:** `storage/logs/`

**View logs:**
```bash
php artisan pail
tail -f storage/logs/laravel.log
```

### Error Tracking

Consider integrating:
- Sentry
- Bugsnag
- Flare

### Performance Monitoring

Consider integrating:
- Laravel Telescope (development)
- New Relic
- Datadog

## Backup Strategy

### Database Backups

```bash
# Backup
pg_dump -U postgres funlynk > backup.sql

# Restore
psql -U postgres funlynk < backup.sql
```

### File Storage Backups

```bash
# Backup storage directory
tar -czf storage-backup.tar.gz storage/app/public
```

