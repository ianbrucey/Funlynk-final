# FunLynk Deployment Checklist

## Pre-Deployment Verification ✅

### 1. Location Seeding System
- [x] Migration created: `2025_12_26_000001_create_locations_table.php`
- [x] CSV data generated: `database/seeders/data/locations.csv` (41,755 records)
- [x] Seeder created: `LocationSeeder.php`
- [x] Tested locally: ✅ 8.13s to seed all locations
- [x] Idempotency verified: ✅ Skips if already seeded
- [x] PostgreSQL sequence updated: ✅ Automatic

### 2. Deploy Script
- [x] Created: `deploy.sh`
- [x] Made executable: `chmod +x deploy.sh`
- [x] Correct order:
  1. Install dependencies
  2. Build assets
  3. Run migrations
  4. **Seed locations** (before indexing)
  5. Cache config/routes/views
  6. Create search indexes
  7. Sync index settings
  8. Import search data

### 3. Backward Compatibility
- [x] Old `SqlFileSeeder` redirects to `LocationSeeder`
- [x] Existing deploy scripts will continue to work

## Deployment Steps

### Option 1: Automated (Recommended)
```bash
./deploy.sh
```

### Option 2: Manual
```bash
# 1. Install dependencies
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
npm ci --audit false
npm run build

# 2. Database
php artisan migrate --force
php artisan db:seed --class=LocationSeeder

# 3. Cache
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 4. Search indexes
php artisan scout:index posts_index
php artisan scout:index activities_index
php artisan scout:index users_index
php artisan scout:index locations_index
php artisan scout:sync-index-settings

# 5. Import data
php artisan scout:import "App\Models\Post"
php artisan scout:import "App\Models\Activity"
php artisan scout:import "App\Models\User"
php artisan scout:import "App\Models\Location"
```

## Post-Deployment Verification

### 1. Check Location Count
```bash
php artisan tinker --execute="echo 'Locations: ' . App\Models\Location::count();"
```
**Expected**: 41755

### 2. Test Location Lookup
```bash
php artisan tinker --execute="print_r(App\Models\Location::findLocation(['zip' => '90210'])->toArray());"
```
**Expected**: Beverly Hills, CA

### 3. Verify Search Index
```bash
php artisan scout:status
```
**Expected**: All models indexed

### 4. Test Application
- [ ] Visit homepage
- [ ] Test location search
- [ ] Create a post with location
- [ ] Create an event with location
- [ ] Verify location autocomplete works

## Rollback Plan

If deployment fails:

```bash
# Rollback migrations
php artisan migrate:rollback

# Clear caches
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Restore previous version
git checkout <previous-commit>
composer install
npm ci && npm run build
```

## Performance Expectations

- **Location Seeding**: ~8-10 seconds
- **Total Deployment**: ~2-5 minutes (depending on server)
- **Database Size**: +5-10 MB for locations table

## Files to Commit

```bash
git add database/migrations/2025_12_26_000001_create_locations_table.php
git add database/seeders/LocationSeeder.php
git add database/seeders/SqlFileSeeder.php
git add database/seeders/data/locations.csv
git add database/seeders/data/README.md
git add scripts/convert_sql_to_csv.py
git add deploy.sh
git add LOCATION_SEEDING_GUIDE.md
git add DEPLOYMENT_CHECKLIST.md
git commit -m "feat: migrate location seeding from SQL to CSV-based system"
```

## Environment Variables

Ensure these are set in production:

```env
DB_CONNECTION=pgsql
DB_HOST=your-db-host
DB_PORT=5432
DB_DATABASE=funlynk
DB_USERNAME=your-username
DB_PASSWORD=your-password

SCOUT_DRIVER=meilisearch
MEILISEARCH_HOST=your-meilisearch-host
MEILISEARCH_KEY=your-meilisearch-key
```

## Support

- **Documentation**: See `LOCATION_SEEDING_GUIDE.md`
- **Data Details**: See `database/seeders/data/README.md`
- **Issues**: Check logs in `storage/logs/laravel.log`

## Success Criteria

- [x] All migrations run successfully
- [x] 41,755 locations seeded
- [x] Search indexes created
- [x] All models indexed in Meilisearch
- [ ] Application loads without errors
- [ ] Location features work correctly

---

**Status**: ✅ Ready for deployment
**Last Updated**: 2025-12-26

