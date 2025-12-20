# Laravel Cloud Environment Setup

## Quick Start: Copy & Paste These Variables

Go to **Laravel Cloud Dashboard** → **Your Project** → **Environment** → **Edit Environment Variables**

Then paste these variables:

```env
# Database (PostgreSQL + PostGIS)
DB_CONNECTION=pgsql
DB_HOST=178.156.193.56
DB_PORT=5432
DB_DATABASE=funlynk
DB_USERNAME=funlynk
DB_PASSWORD=funlynk_secure_password_2025

# Redis (Cache + Queue + Sessions)
REDIS_HOST=178.156.193.56
REDIS_PORT=6379
REDIS_PASSWORD=funlynk_redis_2025

# Cache
CACHE_DRIVER=redis
CACHE_PREFIX=funlynk_cache

# Queue
QUEUE_CONNECTION=redis

# Session
SESSION_DRIVER=redis
SESSION_LIFETIME=120

# Meilisearch (Search Engine)
SCOUT_DRIVER=meilisearch
MEILISEARCH_HOST=http://178.156.193.56:7700
MEILISEARCH_KEY=funlynk_meili_master_key_2025

# MinIO (S3-Compatible Storage)
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=funlynk
AWS_SECRET_ACCESS_KEY=funlynk_minio_password_2025
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=funlynk-production
AWS_ENDPOINT=http://178.156.193.56:9000
AWS_USE_PATH_STYLE_ENDPOINT=true
```

---

## Step-by-Step Instructions

### 1. Access Laravel Cloud Dashboard

1. Go to https://cloud.laravel.com
2. Log in with your credentials
3. Select your **FunLynk** project

### 2. Navigate to Environment Variables

1. Click **Environment** in the left sidebar
2. Click **Edit Environment Variables** button

### 3. Add Variables

**Option A: Paste All at Once (Recommended)**

1. Copy the entire block above (from `DB_CONNECTION` to `AWS_USE_PATH_STYLE_ENDPOINT`)
2. Paste into the environment variables editor
3. Click **Save**

**Option B: Add One by One**

1. Click **Add Variable**
2. Enter **Key**: `DB_CONNECTION`
3. Enter **Value**: `pgsql`
4. Repeat for all variables

### 4. Deploy Changes

Laravel Cloud will automatically redeploy your application with the new environment variables.

**Wait 2-3 minutes** for deployment to complete.

### 5. Verify Connection

```bash
# SSH into your Laravel Cloud container (if available)
php artisan tinker

# Test database connection
DB::connection()->getPdo();

# Test Redis connection
Cache::put('test', 'Hello FunLynk', 60);
Cache::get('test');

# Test Meilisearch
use Laravel\Scout\Scout;
Scout::ping();
```

---

## Important Notes

### ⚠️ Security Warning

The passwords in this document are **DEMO PASSWORDS**. 

**Before going to production**, change all passwords:

1. PostgreSQL password
2. Redis password
3. Meilisearch master key
4. MinIO credentials

See `docs/HETZNER_DEPLOYMENT.md` → **Security** section for instructions.

### 📦 MinIO Bucket Setup

Before uploading files, create the MinIO bucket:

```bash
# SSH into Hetzner server
ssh -i ~/.ssh/hetzner_funlynk root@178.156.193.56

# Install MinIO client
wget https://dl.min.io/client/mc/release/linux-amd64/mc
chmod +x mc
mv mc /usr/local/bin/

# Configure
mc alias set funlynk http://localhost:9000 funlynk funlynk_minio_password_2025

# Create bucket
mc mb funlynk/funlynk-production

# Set public read policy (for profile images)
mc anonymous set download funlynk/funlynk-production
```

### 🗄️ Run Migrations

After adding environment variables:

```bash
# Laravel Cloud will run this automatically on deploy
# Or manually trigger:
php artisan migrate --force
```

### 🔍 Index Search Data

After migrations:

```bash
php artisan scout:import "App\Models\Post"
php artisan scout:import "App\Models\Activity"
php artisan scout:import "App\Models\User"
```

---

## Troubleshooting

### "Connection refused" Error

**Problem**: Laravel can't connect to Hetzner services

**Solutions**:

1. **Check firewall on Hetzner**:
   ```bash
   ssh -i ~/.ssh/hetzner_funlynk root@178.156.193.56
   ufw status
   ```

2. **Verify services are running**:
   ```bash
   systemctl status postgresql redis-server meilisearch minio
   ```

3. **Check services listen on 0.0.0.0** (not 127.0.0.1):
   ```bash
   ss -tlnp | grep -E ':(5432|6379|7700|9000)'
   ```

### "Authentication failed" Error

**Problem**: Wrong credentials

**Solutions**:

1. **Verify PostgreSQL password**:
   ```bash
   psql -h 178.156.193.56 -U funlynk -d funlynk
   # Enter password: funlynk_secure_password_2025
   ```

2. **Verify Redis password**:
   ```bash
   redis-cli -h 178.156.193.56 -a funlynk_redis_2025 PING
   ```

3. **Check .env variables match server credentials**

### "Bucket does not exist" Error

**Problem**: MinIO bucket not created

**Solution**: See "MinIO Bucket Setup" section above

### "Index not found" Error (Meilisearch)

**Problem**: Search indexes not created

**Solution**: Run `php artisan scout:import` commands above

---

## Testing Connections Locally

Before deploying to Laravel Cloud, test connections from your local machine:

### Test PostgreSQL

```bash
psql -h 178.156.193.56 -U funlynk -d funlynk
# Password: funlynk_secure_password_2025

# Run query
SELECT version();
SELECT PostGIS_Version();
```

### Test Redis

```bash
redis-cli -h 178.156.193.56 -a funlynk_redis_2025

# Test commands
PING
SET test "Hello"
GET test
```

### Test Meilisearch

```bash
curl -H "Authorization: Bearer funlynk_meili_master_key_2025" \
  http://178.156.193.56:7700/health
```

### Test MinIO

```bash
# Install AWS CLI
brew install awscli  # macOS
# or
apt install awscli   # Linux

# Configure
aws configure
# AWS Access Key ID: funlynk
# AWS Secret Access Key: funlynk_minio_password_2025
# Default region: us-east-1

# Test
aws --endpoint-url http://178.156.193.56:9000 s3 ls
```

---

## Next Steps

1. ✅ Add environment variables to Laravel Cloud
2. ✅ Wait for automatic deployment
3. 🔲 Create MinIO bucket
4. 🔲 Run migrations
5. 🔲 Index search data
6. 🔲 Test your application
7. 🔲 Change default passwords (production)

---

## Quick Reference

**Server IP**: 178.156.193.56

**Services**:
- PostgreSQL: 5432
- Redis: 6379
- Meilisearch: 7700
- MinIO: 9000

**Full Documentation**: See `docs/HETZNER_DEPLOYMENT.md`

---

**Last Updated**: December 20, 2025
