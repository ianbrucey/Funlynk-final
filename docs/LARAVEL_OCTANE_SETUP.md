# Laravel Octane Setup & Usage Guide

## Overview

Laravel Octane is installed and configured with **RoadRunner** as the application server. Octane provides significant performance improvements by keeping your Laravel application in memory and reusing it across requests.

**Key Benefits:**
- 2-8x faster request handling
- Reduced memory overhead
- Long-lived application state
- Built-in worker management

## Installation Status

✅ **Installed**: Laravel Octane v2.13 with RoadRunner v2025.1.6
✅ **Configuration**: Published to `config/octane.php`
✅ **Binary**: RoadRunner binary available at `./rr`

## Starting the Server

### Basic Usage

```bash
# Start with default settings (port 8000, 4 workers)
php artisan octane:start

# Start with custom port
php artisan octane:start --port=8001

# Start with custom worker count
php artisan octane:start --workers=8

# Start with file watching (auto-reload on code changes)
php artisan octane:start --watch
```

### Development Workflow

For local development with auto-reload:

```bash
php artisan octane:start --watch --workers=4
```

The `--watch` flag monitors these directories for changes:
- `app/`
- `bootstrap/`
- `config/`
- `database/`
- `public/`
- `resources/`
- `routes/`
- `.env`

## Configuration

### Main Config File: `config/octane.php`

**Server Selection** (line 41):
```php
'server' => env('OCTANE_SERVER', 'roadrunner'),
```

**Listeners** (lines 67-120):
- `RequestReceived`: Prepares app for each request
- `OperationTerminated`: Flushes temporary bindings
- `WorkerErrorOccurred`: Handles worker errors

**Garbage Collection** (line 209):
```php
'garbage' => 50,  // Run GC when memory reaches 50MB
```

**Max Execution Time** (line 222):
```php
'max_execution_time' => 30,  // 30 seconds per request
```

### RoadRunner Config: `.rr.yaml`

Currently empty. RoadRunner uses sensible defaults. To customize:

```yaml
http:
  address: 127.0.0.1:8000
  workers:
    num: 4
    max_jobs: 64
  pool:
    num_workers: 4
```

## Important Considerations

### State Management

⚠️ **Critical**: Application state persists across requests. Be careful with:

- **Static variables**: May retain values between requests
- **Global variables**: Can cause unexpected behavior
- **Database connections**: Automatically managed by Octane listeners
- **File handles**: Should be closed after each request

### Livewire Compatibility

✅ **Fully Compatible**: Livewire v3 works seamlessly with Octane.

### Broadcasting & Real-time

✅ **Works with Reverb**: Octane integrates with Laravel Reverb for WebSocket support.

## Monitoring & Debugging

### Health Check

```bash
curl http://localhost:8000/up
```

### Logs

Monitor Octane logs:
```bash
php artisan pail --filter=octane
```

### Performance Metrics

RoadRunner provides built-in metrics. Check worker status:
```bash
# View active workers
ps aux | grep rr
```

## Deployment

### Environment Variables

```env
OCTANE_SERVER=roadrunner
OCTANE_HTTPS=false
```

### Production Settings

```bash
# Start with more workers for production
php artisan octane:start --workers=16 --max-requests=500
```

The `--max-requests` flag reloads workers after N requests to prevent memory leaks.

## Troubleshooting

### Server Won't Start

```bash
# Check if port is in use
lsof -i :8000

# Kill existing process
pkill -f "octane:start"
```

### Memory Issues

Increase garbage collection threshold in `config/octane.php`:
```php
'garbage' => 100,  // Increase from 50MB
```

### Slow Requests

Check for:
- Database N+1 queries
- Unbounded loops
- Large file operations
- Missing indexes

## Next Steps

1. **Test locally**: `php artisan octane:start --watch`
2. **Monitor performance**: Compare with `php artisan serve`
3. **Configure for production**: Adjust workers and max-requests
4. **Deploy**: Use Octane in production for 2-8x performance gains

