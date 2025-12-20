# Update Laravel Cloud Environment Variables

## SSL Setup Complete ✅

All SSL certificates are installed and valid until **March 20, 2026**.

---

## Update Your Laravel Cloud .env

Go to **Laravel Cloud Dashboard** → **Environment Variables** and update:

### MinIO S3 Storage Configuration

```env
# Filesystem
FILESYSTEM_DISK=s3

# MinIO S3 Credentials
AWS_ACCESS_KEY_ID=funlynk
AWS_SECRET_ACCESS_KEY=funlynk_minio_password_2025
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=funlynk-production

# MinIO Endpoints (HTTPS with SSL)
AWS_ENDPOINT=https://storage.funlynk.com
AWS_URL=https://storage.funlynk.com

# S3 Configuration
AWS_USE_PATH_STYLE_ENDPOINT=true
```

---

## What Changed

### Before (HTTP - Mixed Content Error)
```env
AWS_ENDPOINT=http://178.156.193.56:9000
```

### After (HTTPS - Secure)
```env
AWS_ENDPOINT=https://storage.funlynk.com
```

---

## Access Points

### MinIO Console (Admin)
```
https://console.funlynk.com
```
- Login: `funlynk` / `funlynk_minio_password_2025`
- For managing buckets, viewing logs, etc.

### MinIO Storage API (S3)
```
https://storage.funlynk.com
```
- Used by Laravel for file uploads
- No port needed (standard HTTPS port 443)

---

## How It Works

```
Laravel App (HTTPS)
    ↓
AWS_ENDPOINT=https://storage.funlynk.com
    ↓
CloudFlare DNS (translates domain to IP)
    ↓
Hetzner Server (178.156.193.56)
    ↓
Nginx (SSL termination, port 443)
    ↓
MinIO API (HTTP, port 9000, localhost only)
```

---

## Next Steps

1. **Update .env** in Laravel Cloud dashboard
2. **Redeploy** your application
3. **Test profile picture upload** - should work without mixed content errors
4. **Verify** in browser DevTools (no HTTPS warnings)

---

## SSL Certificate Details

| Domain | Expiry | Auto-Renewal |
|--------|--------|--------------|
| console.funlynk.com | 2026-03-20 | ✅ Enabled |
| storage.funlynk.com | 2026-03-20 | ✅ Enabled |
| services.funlynk.com | 2026-03-20 | ✅ Enabled |

All certificates auto-renew 30 days before expiry.

---

## Troubleshooting

### Profile picture upload still fails?
1. Clear browser cache
2. Hard refresh (Cmd+Shift+R or Ctrl+Shift+R)
3. Check DevTools Console for errors
4. Verify .env was updated correctly

### Can't access console.funlynk.com?
1. Wait 2-3 minutes for DNS propagation
2. Try: `nslookup console.funlynk.com`
3. Should return: `178.156.193.56`

### SSL certificate warning?
1. Make sure you're using the domain name, not IP
2. Clear browser cache
3. Try incognito/private window

---

**Ready to redeploy!** 🚀

