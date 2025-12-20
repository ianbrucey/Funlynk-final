# FunLynk Hetzner Server Deployment Guide

## Overview

This document explains the complete Hetzner server setup for FunLynk's data services layer.

**Architecture**: Laravel Cloud (app) + Hetzner CPX42 (data services)

**Server Details**:
- **IP**: 178.156.193.56
- **Hostname**: ubuntu-16gb-ash-1
- **OS**: Ubuntu 24.04 LTS
- **Specs**: 4 vCPU, 16GB RAM, 320GB SSD
- **Cost**: €17.39/month (~$19)

---

## What's Running on Hetzner

### 1. PostgreSQL 16 with PostGIS
- **Port**: 5432
- **Database**: funlynk
- **User**: funlynk
- **Password**: funlynk_secure_password_2025
- **Extensions**: PostGIS, PostGIS Topology
- **Purpose**: Primary database with spatial queries

### 2. Redis 7
- **Port**: 6379
- **Password**: funlynk_redis_2025
- **Purpose**: Cache, sessions, queue backend

### 3. Meilisearch 1.30.1
- **Port**: 7700
- **Master Key**: funlynk_meili_master_key_2025
- **Purpose**: Fast search engine for posts/activities

### 4. MinIO (S3-Compatible Storage)
- **API Port**: 9000
- **Console Port**: 9001
- **Root User**: funlynk
- **Root Password**: funlynk_minio_password_2025
- **Purpose**: File storage (profile images, uploads)

### 5. Nginx
- **Port**: 80 (HTTP), 443 (HTTPS - to be configured)
- **Purpose**: Reverse proxy, SSL termination

---

## Laravel Cloud Environment Variables

Add these to your Laravel Cloud project's environment variables:

```env
# Database
DB_CONNECTION=pgsql
DB_HOST=178.156.193.56
DB_PORT=5432
DB_DATABASE=funlynk
DB_USERNAME=funlynk
DB_PASSWORD=funlynk_secure_password_2025

# Redis
REDIS_HOST=178.156.193.56
REDIS_PORT=6379
REDIS_PASSWORD=funlynk_redis_2025

# Meilisearch
SCOUT_DRIVER=meilisearch
MEILISEARCH_HOST=http://178.156.193.56:7700
MEILISEARCH_KEY=funlynk_meili_master_key_2025

# MinIO (S3-Compatible)
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=funlynk
AWS_SECRET_ACCESS_KEY=funlynk_minio_password_2025
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=funlynk-production
AWS_ENDPOINT=http://178.156.193.56:9000
AWS_USE_PATH_STYLE_ENDPOINT=true

# Queue (Redis)
QUEUE_CONNECTION=redis

# Session (Redis)
SESSION_DRIVER=redis

# Cache (Redis)
CACHE_DRIVER=redis
```

---

## How to Add .env Vars to Laravel Cloud

### Method 1: Via Dashboard (Recommended)

1. Go to https://cloud.laravel.com
2. Select your **FunLynk** project
3. Click **Environment** in the left sidebar
4. Click **Edit Environment Variables**
5. Paste the variables above
6. Click **Save**
7. Laravel Cloud will automatically redeploy with new variables

### Method 2: Via CLI

```bash
# Install Laravel Cloud CLI
composer global require laravel/cloud-cli

# Login
cloud login

# Set environment variables
cloud env:set DB_HOST=178.156.193.56
cloud env:set DB_PASSWORD=funlynk_secure_password_2025
# ... repeat for all variables

# Or bulk import from file
cloud env:import .env.production
```

---

## Initial Setup Tasks

### 1. Create MinIO Bucket

```bash
# SSH into server
ssh -i ~/.ssh/hetzner_funlynk root@178.156.193.56

# Install MinIO client
wget https://dl.min.io/client/mc/release/linux-amd64/mc
chmod +x mc
mv mc /usr/local/bin/

# Configure MinIO client
mc alias set funlynk http://localhost:9000 funlynk funlynk_minio_password_2025

# Create bucket
mc mb funlynk/funlynk-production

# Set public read policy (for profile images)
mc anonymous set download funlynk/funlynk-production

# Verify
mc ls funlynk
```

### 2. Run Laravel Migrations

```bash
# From your local machine (Laravel Cloud will run this)
php artisan migrate --force
```

### 3. Index Meilisearch

```bash
# From your local machine
php artisan scout:import "App\Models\Post"
php artisan scout:import "App\Models\Activity"
php artisan scout:import "App\Models\User"
```

---

## Connecting to Services

### PostgreSQL

```bash
# From local machine
psql -h 178.156.193.56 -U funlynk -d funlynk
# Password: funlynk_secure_password_2025

# Test PostGIS
SELECT PostGIS_Version();
```

### Redis

```bash
# From local machine
redis-cli -h 178.156.193.56 -a funlynk_redis_2025

# Test
PING
SET test "Hello FunLynk"
GET test
```

### Meilisearch

```bash
# From local machine
curl -H "Authorization: Bearer funlynk_meili_master_key_2025" \
  http://178.156.193.56:7700/health
```

### MinIO

```bash
# Access web console
http://178.156.193.56:9001

# Login:
# Username: funlynk
# Password: funlynk_minio_password_2025
```

---

## Service Management

### Check Service Status

```bash
ssh -i ~/.ssh/hetzner_funlynk root@178.156.193.56

# Check all services
systemctl status postgresql
systemctl status redis-server
systemctl status meilisearch
systemctl status minio
systemctl status nginx

# Quick check (active/inactive)
systemctl is-active postgresql redis-server meilisearch minio nginx
```

### Start/Stop/Restart Services

```bash
# Restart a service
systemctl restart postgresql
systemctl restart redis-server
systemctl restart meilisearch
systemctl restart minio
systemctl restart nginx

# Stop a service
systemctl stop meilisearch

# Start a service
systemctl start meilisearch

# Enable auto-start on boot (already configured)
systemctl enable postgresql
```

### View Service Logs

```bash
# PostgreSQL
journalctl -u postgresql -f

# Redis
journalctl -u redis-server -f

# Meilisearch
journalctl -u meilisearch -f

# MinIO
journalctl -u minio -f

# Nginx
tail -f /var/log/nginx/access.log
tail -f /var/log/nginx/error.log

# View last 100 lines
journalctl -u postgresql -n 100
```

---

## Monitoring & Health Checks

### Resource Usage

```bash
# CPU, RAM, Disk
htop

# Disk usage
df -h

# Memory usage
free -h

# Network connections
ss -tlnp
```

### Database Health

```bash
# PostgreSQL connections
psql -h localhost -U funlynk -d funlynk -c "SELECT count(*) FROM pg_stat_activity;"

# Database size
psql -h localhost -U funlynk -d funlynk -c "SELECT pg_size_pretty(pg_database_size('funlynk'));"

# Active queries
psql -h localhost -U funlynk -d funlynk -c "SELECT pid, usename, state, query FROM pg_stat_activity WHERE state != 'idle';"
```

### Redis Health

```bash
redis-cli -a funlynk_redis_2025 INFO stats
redis-cli -a funlynk_redis_2025 INFO memory
redis-cli -a funlynk_redis_2025 DBSIZE
```

### Meilisearch Health

```bash
curl -H "Authorization: Bearer funlynk_meili_master_key_2025" \
  http://localhost:7700/stats

curl -H "Authorization: Bearer funlynk_meili_master_key_2025" \
  http://localhost:7700/indexes
```

### MinIO Health

```bash
mc admin info funlynk
mc admin heal funlynk
```

---

## Security

### Firewall Rules (UFW)

```bash
# View current rules
ufw status verbose

# Current open ports:
# 22 (SSH)
# 80 (HTTP)
# 443 (HTTPS)
# 5432 (PostgreSQL)
# 6379 (Redis)
# 7700 (Meilisearch)
# 9000 (MinIO API)
# 9001 (MinIO Console)
```

### Credential Management

**⚠️ IMPORTANT**: Change default passwords in production!

```bash
# Change PostgreSQL password
psql -h localhost -U postgres
ALTER USER funlynk WITH PASSWORD 'new_secure_password';

# Change Redis password
# Edit /etc/redis/redis.conf
requirepass new_secure_password
systemctl restart redis-server

# Change Meilisearch master key
# Edit /etc/systemd/system/meilisearch.service
Environment="MEILI_MASTER_KEY=new_master_key"
systemctl daemon-reload
systemctl restart meilisearch

# Change MinIO credentials
# Edit /etc/systemd/system/minio.service
Environment="MINIO_ROOT_USER=new_user"
Environment="MINIO_ROOT_PASSWORD=new_password"
systemctl daemon-reload
systemctl restart minio
```

**After changing passwords, update Laravel Cloud .env variables!**

### SSH Security

```bash
# Disable password authentication (key-only)
nano /etc/ssh/sshd_config
# Set: PasswordAuthentication no
systemctl restart sshd

# Create non-root user (recommended)
adduser deploy
usermod -aG sudo deploy
mkdir -p /home/deploy/.ssh
cp ~/.ssh/authorized_keys /home/deploy/.ssh/
chown -R deploy:deploy /home/deploy/.ssh
chmod 700 /home/deploy/.ssh
chmod 600 /home/deploy/.ssh/authorized_keys
```

---

## Backup Strategy

### PostgreSQL Backups

```bash
# Manual backup
pg_dump -h localhost -U funlynk funlynk > funlynk_backup_$(date +%Y%m%d).sql

# Automated daily backups (cron)
crontab -e
# Add:
0 2 * * * pg_dump -h localhost -U funlynk funlynk > /backups/funlynk_$(date +\%Y\%m\%d).sql

# Restore from backup
psql -h localhost -U funlynk funlynk < funlynk_backup_20251220.sql
```

### Redis Backups

```bash
# Redis automatically saves to /var/lib/redis/dump.rdb
# Copy to backup location
cp /var/lib/redis/dump.rdb /backups/redis_$(date +%Y%m%d).rdb

# Restore
systemctl stop redis-server
cp /backups/redis_20251220.rdb /var/lib/redis/dump.rdb
systemctl start redis-server
```

### MinIO Backups

```bash
# Backup entire bucket
mc mirror funlynk/funlynk-production /backups/minio/

# Restore
mc mirror /backups/minio/ funlynk/funlynk-production
```

### Automated Backup Script

Create `/root/backup.sh`:

```bash
#!/bin/bash
BACKUP_DIR="/backups/$(date +%Y%m%d)"
mkdir -p $BACKUP_DIR

# PostgreSQL
pg_dump -h localhost -U funlynk funlynk > $BACKUP_DIR/postgres.sql

# Redis
cp /var/lib/redis/dump.rdb $BACKUP_DIR/redis.rdb

# MinIO
mc mirror funlynk/funlynk-production $BACKUP_DIR/minio/

# Compress
tar -czf $BACKUP_DIR.tar.gz $BACKUP_DIR
rm -rf $BACKUP_DIR

# Keep only last 7 days
find /backups -name "*.tar.gz" -mtime +7 -delete

echo "Backup complete: $BACKUP_DIR.tar.gz"
```

Make executable and schedule:

```bash
chmod +x /root/backup.sh
crontab -e
# Add: 0 3 * * * /root/backup.sh
```

---

## Troubleshooting

### Service Won't Start

```bash
# Check logs
journalctl -u <service-name> -n 50

# Check if port is already in use
ss -tlnp | grep <port>

# Kill process using port
kill -9 <pid>

# Restart service
systemctl restart <service-name>
```

### Can't Connect from Laravel Cloud

```bash
# Check firewall
ufw status

# Check service is listening on 0.0.0.0 (not 127.0.0.1)
ss -tlnp | grep <port>

# Test from server
curl http://localhost:<port>

# Check Laravel Cloud IP is not blocked
tail -f /var/log/nginx/access.log
```

### PostgreSQL Connection Issues

```bash
# Check pg_hba.conf allows remote connections
cat /etc/postgresql/16/main/pg_hba.conf | grep "0.0.0.0"

# Check postgresql.conf listens on all interfaces
cat /etc/postgresql/16/main/postgresql.conf | grep listen_addresses

# Restart PostgreSQL
systemctl restart postgresql
```

### Redis Connection Issues

```bash
# Check Redis is listening on 0.0.0.0
cat /etc/redis/redis.conf | grep bind

# Test connection
redis-cli -h 178.156.193.56 -a funlynk_redis_2025 PING

# Check password
cat /etc/redis/redis.conf | grep requirepass
```

### Meilisearch Not Responding

```bash
# Check service status
systemctl status meilisearch

# Check logs
journalctl -u meilisearch -n 50

# Restart
systemctl restart meilisearch

# Test
curl http://localhost:7700/health
```

### MinIO Access Denied

```bash
# Check credentials
cat /etc/systemd/system/minio.service | grep MINIO_ROOT

# Restart MinIO
systemctl restart minio

# Test
mc alias set test http://localhost:9000 funlynk funlynk_minio_password_2025
mc ls test
```

### Disk Space Full

```bash
# Check disk usage
df -h

# Find large files
du -sh /* | sort -h

# Clean up logs
journalctl --vacuum-time=7d

# Clean up old backups
find /backups -mtime +30 -delete
```

---

## Cost Breakdown

### Monthly Costs

| Service | Provider | Cost |
|---------|----------|------|
| Laravel App | Laravel Cloud | $50 |
| Data Services (CPX42) | Hetzner | $19 |
| **Total** | | **$69/month** |

### Savings vs All-Managed

| Service | Managed Cost | Hetzner Cost | Savings |
|---------|--------------|--------------|---------|
| PostgreSQL | $25 | $5 (shared) | $20 |
| Redis | $15 | $3 (shared) | $12 |
| Meilisearch | $50 | $5 (shared) | $45 |
| MinIO | $15 | $3 (shared) | $12 |
| Reverb | $50 | $3 (shared) | $47 |
| **Total** | **$155** | **$19** | **$136/month** |

**Annual Savings**: $1,632 🎉

---

## SSL Setup (Optional)

### Using Let's Encrypt (Free)

```bash
# Install Certbot
apt install -y certbot python3-certbot-nginx

# Get certificate (replace with your domain)
certbot --nginx -d data.funlynk.com

# Auto-renewal (already configured)
systemctl status certbot.timer
```

### Update Laravel Cloud .env

```env
# Change HTTP to HTTPS
MEILISEARCH_HOST=https://data.funlynk.com:7700
AWS_ENDPOINT=https://data.funlynk.com:9000
```

---

## Next Steps

1. ✅ **Server Setup Complete** - All services running
2. 🔲 **Add .env vars to Laravel Cloud** (see "How to Add .env Vars" section)
3. 🔲 **Create MinIO bucket** (see "Initial Setup Tasks")
4. 🔲 **Run migrations** from Laravel Cloud
5. 🔲 **Test connections** from your Laravel app
6. 🔲 **Change default passwords** (see "Security" section)
7. 🔲 **Set up automated backups** (see "Backup Strategy" section)
8. 🔲 **Configure SSL** (optional, see "SSL Setup" section)

---

## Quick Reference

### SSH Connection

```bash
ssh -i ~/.ssh/hetzner_funlynk root@178.156.193.56
```

### Service Ports

- PostgreSQL: 5432
- Redis: 6379
- Meilisearch: 7700
- MinIO API: 9000
- MinIO Console: 9001
- Nginx: 80, 443

### Credentials

See "Laravel Cloud Environment Variables" section above.

**⚠️ Change these in production!**

---

## Support

- **Hetzner Docs**: https://docs.hetzner.com
- **PostgreSQL Docs**: https://www.postgresql.org/docs/
- **Redis Docs**: https://redis.io/docs/
- **Meilisearch Docs**: https://www.meilisearch.com/docs
- **MinIO Docs**: https://min.io/docs/
- **Laravel Cloud Docs**: https://cloud.laravel.com/docs

---

**Last Updated**: December 20, 2025
**Server IP**: 178.156.193.56
**Server Hostname**: ubuntu-16gb-ash-1


