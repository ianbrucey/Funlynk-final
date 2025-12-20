# Deployment Fixes - December 20, 2025

## Issues Fixed

### 1. Google Maps Async Loading Warning ✅

**Problem**: Google Maps JavaScript API was loaded without `loading=async` parameter, causing performance warnings.

**Solution**: Added `&loading=async` parameter to all Google Maps script URLs across 10 files:

- `resources/views/livewire/discovery/map-view.blade.php`
- `resources/views/livewire/activities/activity-detail.blade.php`
- `resources/views/livewire/components/location-autocomplete.blade.php`
- `resources/views/livewire/posts/create-post.blade.php`
- `resources/views/livewire/activities/create-activity.blade.php`
- `resources/views/livewire/activities/edit-activity.blade.php`
- `resources/views/livewire/profile/edit-profile.blade.php`
- `resources/views/livewire/groups/create-group.blade.php`
- `resources/views/livewire/auth/register.blade.php`
- `resources/views/livewire/onboarding/onboarding-wizard.blade.php`

**Example Change**:
```javascript
// Before
script.src = `https://maps.googleapis.com/maps/api/js?key=KEY&libraries=places`;

// After
script.src = `https://maps.googleapis.com/maps/api/js?key=KEY&libraries=places&loading=async`;
```

---

### 2. Profile Picture Upload 404 Error ✅

**Problem**: File uploads were failing with 404 errors because:
1. Livewire temporary uploads were not configured for S3
2. Final storage was using `'public'` disk instead of `'s3'`

**Solution**: 

#### A. Created Custom Livewire Config
**File**: `config/livewire.php`

Key change:
```php
'temporary_file_upload' => [
    'disk' => env('LIVEWIRE_TMP_DISK', 's3'),  // Use S3 for temporary uploads
    'directory' => 'livewire-tmp',
    // ... other settings
],
```

#### B. Updated All File Upload Components to Use S3

**Files Changed**:
1. `app/Livewire/Onboarding/OnboardingWizard.php`
   - Line 131: `->store('profile-images', 's3')`

2. `app/Livewire/Profile/EditProfile.php`
   - Line 194: `Storage::disk('s3')->delete()`
   - Line 199: `->store('profiles', 's3')`
   - Line 224: `Storage::disk('s3')->delete()`

3. `app/Livewire/Activities/CreateActivity.php`
   - Line 119: `->store('activities', 's3')`

4. `app/Livewire/Activities/EditActivity.php`
   - Line 165: `->store('activities', 's3')`

5. `app/Livewire/Modals/ConvertPostModal.php`
   - Line 165: `->store('activities', 's3')`

---

## Deployment Steps

### Step 1: Commit and Push Changes

```bash
git add .
git commit -m "Fix: Google Maps async loading + S3 file uploads"
git push origin main
```

### Step 2: Deploy to Laravel Cloud

Laravel Cloud will automatically deploy when you push to `main` branch.

### Step 3: Verify Environment Variables

Ensure these are set in **Laravel Cloud Dashboard** → **Environment Variables**:

```env
# S3/MinIO Configuration
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=funlynk
AWS_SECRET_ACCESS_KEY=funlynk_minio_password_2025
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=funlynk-production
AWS_ENDPOINT=https://storage.funlynk.com
AWS_URL=https://storage.funlynk.com
AWS_USE_PATH_STYLE_ENDPOINT=true

# Livewire Temporary Uploads (optional, defaults to s3)
LIVEWIRE_TMP_DISK=s3
```

### Step 4: Test Profile Picture Upload

1. Go to `https://funlynk-main-57y3j5h.laravel.cloud/onboarding`
2. Click "Upload Photo"
3. Select an image
4. Complete onboarding
5. Verify image appears in profile

---

## Expected Results

### ✅ Google Maps Warning Gone
Browser console should no longer show:
```
Google Maps JavaScript API has been loaded directly without loading=async
```

### ✅ Profile Picture Upload Works
- No more 404 errors on PUT requests
- Images successfully upload to MinIO S3
- Images display correctly using `https://storage.funlynk.com` URLs

### ✅ All File Uploads Use S3
- Profile pictures
- Activity images
- Post images
- Group images

---

## Troubleshooting

### If uploads still fail:

1. **Check MinIO bucket exists**:
   ```bash
   ssh -i ~/.ssh/hetzner_funlynk root@178.156.193.56
   mc ls myminio/funlynk-production
   ```

2. **Check MinIO bucket policy** (must be public-read):
   ```bash
   mc anonymous set download myminio/funlynk-production
   ```

3. **Check Laravel Cloud logs**:
   - Go to Laravel Cloud Dashboard
   - Click on your deployment
   - View logs for S3 connection errors

4. **Verify SSL certificates**:
   ```bash
   curl -I https://storage.funlynk.com
   # Should return 200 OK with valid SSL
   ```

---

## Files Modified Summary

**Total Files Changed**: 16

**Categories**:
- Google Maps Loading: 10 Blade files
- File Upload Logic: 5 Livewire components
- Configuration: 1 new config file

**Lines Changed**: ~50 lines across all files

---

**Status**: ✅ Ready for deployment
**Tested**: Locally verified, ready for production testing
**Next**: Deploy and test on Laravel Cloud

