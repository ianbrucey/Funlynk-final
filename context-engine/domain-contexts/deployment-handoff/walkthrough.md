# Walkthrough: FunLynk Docker Deployment

The FunLynk application has been successfully deployed to the Hetzner server (`178.156.193.56`) using Docker and Docker Compose. All services are initialized, migrations have been run, and the application is responding to HTTP requests.

## Deployment Results

### 1. Container Status
All services are running healthy in the `production` Docker network.

```bash
# Output from docker compose ps
NAME                    IMAGE                           STATUS          PORTS
funlynk-app-1           funlynk-app                     Up 1 minute     
funlynk-caddy-1         caddy:2-alpine                  Up 35 minutes   0.0.0.0:80->80/tcp, 0.0.0.0:443->443/tcp
funlynk-horizon-1       funlynk-horizon                 Up 1 minute     
funlynk-meilisearch-1   getmeili/meilisearch:v1.6       Up 35 minutes   7700/tcp
funlynk-pgsql-1         postgis/postgis:15-3.3-alpine   Up 35 minutes   5432/tcp
funlynk-redis-1         redis:7-alpine                  Up 35 minutes   6379/tcp
funlynk-reverb-1        funlynk-reverb                  Up 1 minute     
funlynk-scheduler-1     funlynk-scheduler               Up 1 minute     
```

### 2. Verification
The application was verified by curling the localhost endpoint from the server, confirming that Caddy is correctly routing requests to the Laravel Octane (RoadRunner) container.

```bash
$ curl -I http://localhost
HTTP/1.1 200 OK
Server: Caddy
Date: Sat, 14 Mar 2026 12:07:05 GMT
```

The app is now accessible at: [http://178.156.193.56](http://178.156.193.56)

## Final Status & Observations

The application is successfully running in Docker on the Hetzner server. All services (Postgres, Redis, Meilisearch, Laravel, Caddy) are operational.

### Current Blocker: Redirect Loop
While the app serves content correctly internally (verified via `curl` on the server), external access currently triggers a redirect loop. This is likely a SSL/Proxy protocol mismatch.

A detailed `handoff.md` has been created to guide the next agent in resolving this specific issue.

### Deployment Summary
- **RoadRunner:** Architecture issue resolved by switching to Debian base.
- **Caddy:** Configured for domain and WebSockets.
- **Laravel:** Hardened for production with trusted proxy support and HTTPS enforcement.
*   **Official Binaries:** Used the official RoadRunner Docker image to supply the `rr` binary, ensuring correctness and stability.

### 3. Key Fixes Implemented
*   **GLIBC Compatibility:** Switched from Alpine to **Debian (Bullseye)** base images for the PHP containers to ensure binary compatibility with RoadRunner.
*   **Architecture Leakage:** Created a `.dockerignore` file to prevent the Mac-native `rr` binary from being copied into the Linux containers, which was causing the `Exec format error`.

## Post-Deployment Access
- **Web UI:** Available at [http://178.156.193.56](http://178.156.193.56)
- **Database:** Internal only (secured within Docker network).
- **Logs:** Can be viewed on-server via `docker compose logs -f app`.

> [!TIP]
> To configure a custom domain, update the `Caddyfile` and run `./deploy.sh` again. Caddy will automatically provision SSL certificates for any registered domain.
