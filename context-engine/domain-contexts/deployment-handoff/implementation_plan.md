# Single-Server Docker Deployment Plan

## Goal Description
Deploy the FunLynk Laravel 12 application and all its dependencies to a single Hetzner server using Docker and Docker Compose. This ensures a reproducible, isolated environment that maximizes the server's resources without needing external paid services (except for the existing S3/MinIO compatible object storage).

## Services Identified from `.env`
1.  **Web Application (app):** Laravel Octane via RoadRunner (`OCTANE_SERVER=roadrunner`).
2.  **Database (pgsql):** PostgreSQL with PostGIS extension for spatial queries.
3.  **Cache/Queue/Session (redis):** Redis instance.
4.  **Search (meilisearch):** Meilisearch instance.
5.  **WebSockets (reverb):** Laravel Reverb server.
6.  **Background Workers (horizon):** Dedicated container running Laravel Horizon.
7.  **Cron/Scheduler (scheduler):** Dedicated container running Laravel Scheduler.
8.  **Reverse Proxy (caddy):** To easily manage HTTPS/SSL certificates and route traffic to Octane and Reverb securely.

*(Note: Object Storage is outsourced to `storage.funlynk.com`, so MinIO is omitted from the local compose stack).*

## Proposed Changes

### [Docker Infrastructure]

#### [NEW] `Dockerfile`
A lean, production-ready multi-stage build:
1.  **Base:** PHP 8.3/8.4 CLI Alpine image.
2.  **Extensions:** Install PostGIS client libs, `pdo_pgsql`, `redis`, `pcntl`, `sockets`, `opcache`.
3.  **Build:** Copy code, run `composer install --no-dev --optimize-autoloader`. *(We'll assume frontend assets are either built here using Node or pre-compiled before deployment).*
4.  **Runtime:** Install RoadRunner. Set the entrypoint to handle Octane, Reverb, Horizon, or Scheduler based on an environment variable or CMD override.

#### [NEW] `docker-compose.production.yml`
The orchestration file tying everything together:
-   **Persistent Volumes:** Configured for `pgsql_data`, `redis_data`, and `meilisearch_data` to ensure data survives container restarts.
-   **Networking:** An internal Docker network for service communication.
-   **Service Definitions:**
    -   `app`: Exposes Octane to the proxy.
    -   `reverb`: Exposes WebSocket port to the proxy.
    -   `horizon` & `scheduler`: Run in the background (no exposed ports).
    -   `caddy`: Binds to `80/443` on the host, handling Let's Encrypt SSL automatically and routing `ws.funlynk.com` to Reverb and `funlynk.com` to Octane.

#### [NEW] `deploy.sh`
A robust deployment script defining the workflow for rolling out new code:
1.  **Pull:** Fetch latest code via Git.
2.  **Build/Up:** Run `docker compose -f docker-compose.production.yml up -d --build`. This starts new containers.
3.  **Post-Deployment Hooks:** Run commands *inside* the newly running `app` container:
    -   `php artisan migrate --force`
    -   `php artisan config:cache`, `route:cache`, `view:cache`
    -   `php artisan horizon:terminate` (instructs Horizon to gracefully restart its supervised workers)
    -   Octane reload (to ensure memory limits are refreshed if needed).

## Verification Plan

### Automated/Review Verification
-   **Review:** Inspect `docker-compose.production.yml` to ensure no database ports are publicly exposed without need (only Caddy should touch the outside world).
-   **Review:** Verify `Dockerfile` includes all necessary PHP extensions for Laravel 12 + PostGIS + Redis + Reverb.

### Manual Verification on Hetzner
1.  **Provision:** Run `deploy.sh` for the first time on the Hetzner server.
2.  **Migration Check:** Verify `php artisan migrate` runs cleanly and PostGIS extension is installed on the `pgsql` database.
3.  **App Check:** Visit the web app via its domain; check if Caddy secured it with HTTPS.
4.  **WebSocket Check:** Test real-time features to ensure Reverb is accepting connections on `ws.funlynk.com` (or the respective Reverb configuration).
5.  **Queue Check:** Open the Horizon dashboard (`/horizon`) and verify the worker status is "Active".
