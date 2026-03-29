# FunLynk Deployment Handoff

## Project Overview
FunLynk (Laravel 12, Octane/RoadRunner, Filament v4) is deployed on a Hetzner server using Docker Compose.

- **IP Address:** `178.156.193.56`
- **Domain:** `funlynk.com`
- **Server OS:** Ubuntu
- **Stack:** PHP 8.4 (Debian base), Postgres + PostGIS, Redis, Meilisearch, Caddy (Reverse Proxy).

## SSH Access
The user has a local alias `funlynk` that connects to the server and enters the app directory:
```bash
alias funlynk='ssh -i ~/.ssh/funlynk_prod_03_2026 -t root@178.156.193.56 "cd /var/www/funlynk && bash --login"'
```

## Infrastructure Setup
The application is managed via `docker-compose.yml` in `/var/www/funlynk`.

### Key Files:
- `/var/www/funlynk/docker-compose.yml`: Defines all services.
- `/var/www/funlynk/Caddyfile`: Handles SSL (via Let's Encrypt) and proxies to the `app` (Octane) and `reverb` containers.
- `/var/www/funlynk/.env`: Production environment variables (using service names like `pgsql`, `redis`).
- `/var/www/funlynk/Dockerfile`: Uses Debian base and copies Roadmap binary from official image.

### Management Commands:
```bash
# Enter the server and app dir
funlynk

# Restart services
docker compose restart

# View logs
docker compose logs -f app
docker compose logs -f caddy
```

## Current Status & Issues
**Issue:** `ERR_TOO_MANY_REDIRECTS` on `https://funlynk.com`.

### What was done:
1. Fixed `Dockerfile` architecture mismatch (switched to Debian + official RoadRunner binary).
2. Configured `Caddyfile` for domain routing and WebSockets.
3. Updated `.env` to use Docker service names.
4. Enabled `trustProxies(at: '*')` in `bootstrap/app.php`.
5. Forced HTTPS unconditionally in `AppServiceProvider.php` to resolve mixed content.

### The Redirect Loop:
The loop is likely a conflict between Cloudflare (if active) and the origin server's HTTPS enforcement.
- Internal `curl` on the server shows the app is serving HTML correctly over the internal network.
- Browsers hit a loop, likely because Laravel is redirecting to HTTPS, but Caddy/Cloudflare might be terminated in a way that makes Laravel think it's still on HTTP.

### Recommended Next Steps for Successor:
1. **Check Cloudflare SSL Mode:** Ensure it is set to **Full (Strict)**. If set to "Flexible", Cloudflare will hit the server over HTTP, causing Laravel to redirect indefinitely.
2. **Verify Headers:** Confirm Caddy is passing `X-Forwarded-Proto: https` correctly.
3. **Debug Octane Headers:** RoadRunner/Octane might need specific configuration to recognize the forwarded protocol if `trustProxies` isn't enough.
4. **Revert Unconditional HTTPS:** Once the proxy trust is fixed, the unconditional `forceScheme('https')` in `AppServiceProvider.php` should be made conditional again.

---
*Drafted by Antigravity*
