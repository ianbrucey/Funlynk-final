# Reverb WebSocket Server Setup Documentation

**Date**: December 20, 2025  
**Server**: Hetzner (178.156.193.56)  
**Purpose**: Real-time WebSocket server for FunLynk (chat, notifications, live updates)

---

## Why Reverb Instead of Pusher?

### Decision Rationale
- **Cost**: Reverb is free and unlimited; Pusher free tier has 200k messages/day limit
- **Control**: Self-hosted on our infrastructure, no vendor lock-in
- **Privacy**: All WebSocket data stays on our server
- **Performance**: Direct connection to our server (lower latency)
- **Scalability**: Can handle FunLynk's chat + notifications without hitting limits

### FunLynk Use Cases
- Direct messages (high volume)
- Group chats (multiple concurrent connections)
- Real-time notifications
- Live activity updates
- Post reactions

---

## Installation Steps

### 1. Prerequisites Installed
```bash
# PHP 8.3 + Extensions
apt-get install -y php8.3-cli php8.3-redis php8.3-mbstring php8.3-xml php8.3-curl

# Composer
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
```

### 2. Laravel + Reverb Installation
```bash
# Create minimal Laravel app
cd /opt
composer create-project laravel/laravel reverb

# Install Reverb package
cd /opt/reverb
composer require laravel/reverb
```

### 3. Configuration
**File**: `/opt/reverb/.env`
```env
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=funlynk
REVERB_APP_KEY=funlynk_reverb_key_2025
REVERB_APP_SECRET=funlynk_reverb_secret_2025
REVERB_HOST=178.156.193.56
REVERB_PORT=8080
REVERB_SCHEME=http

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=funlynk_redis_2025
REDIS_PORT=6379
```

### 4. Systemd Service
**File**: `/etc/systemd/system/reverb.service`
```ini
[Unit]
Description=Laravel Reverb WebSocket Server
After=network.target redis-server.service
Wants=redis-server.service

[Service]
Type=simple
User=root
WorkingDirectory=/opt/reverb
ExecStart=/usr/bin/php /opt/reverb/artisan reverb:start --host=0.0.0.0 --port=8080
Restart=always
RestartSec=5
StandardOutput=journal
StandardError=journal
SyslogIdentifier=reverb
LimitNOFILE=65536

[Install]
WantedBy=multi-user.target
```

**Enable and start**:
```bash
systemctl daemon-reload
systemctl enable reverb
systemctl start reverb
systemctl status reverb
```

---

## Server Architecture

```
┌─────────────────────────────────────────────────────────────┐
│ Laravel Cloud (funlynk-main-57y3j5h.laravel.cloud)         │
│ - Broadcasts events via Reverb client                       │
│ - Connects to: ws://178.156.193.56:8080                    │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│ Hetzner Server (178.156.193.56)                             │
│                                                              │
│  ┌────────────────────────────────────────────────────────┐ │
│  │ Reverb WebSocket Server (Port 8080)                    │ │
│  │ - Handles WebSocket connections                        │ │
│  │ - Broadcasts to connected clients                      │ │
│  │ - Uses Redis for scaling                               │ │
│  └────────────────────────────────────────────────────────┘ │
│                                                              │
│  ┌────────────────────────────────────────────────────────┐ │
│  │ Redis (Port 6379)                                      │ │
│  │ - Stores WebSocket connection state                    │ │
│  │ - Enables horizontal scaling                           │ │
│  └────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
                       ▲
                       │
┌──────────────────────┴──────────────────────────────────────┐
│ Browser Clients                                              │
│ - Connect via WebSocket: ws://178.156.193.56:8080          │
│ - Receive real-time updates                                 │
└─────────────────────────────────────────────────────────────┘
```

---

## Laravel Cloud Configuration

Add these to **Laravel Cloud Environment Variables**:

```env
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=funlynk
REVERB_APP_KEY=funlynk_reverb_key_2025
REVERB_APP_SECRET=funlynk_reverb_secret_2025
REVERB_HOST=178.156.193.56
REVERB_PORT=8080
REVERB_SCHEME=http

VITE_REVERB_APP_KEY=funlynk_reverb_key_2025
VITE_REVERB_HOST=178.156.193.56
VITE_REVERB_PORT=8080
VITE_REVERB_SCHEME=ws
```

---

## Next Steps (TODO)

1. **Set up Nginx reverse proxy** for WebSocket (wss:// instead of ws://)
2. **Configure SSL** via Let's Encrypt for `reverb.funlynk.com`
3. **Update REVERB_SCHEME** to `https` after SSL setup
4. **Test WebSocket connection** from Laravel Cloud

---

## Monitoring & Maintenance

### Check Service Status
```bash
systemctl status reverb
```

### View Logs
```bash
journalctl -u reverb -f
```

### Restart Service
```bash
systemctl restart reverb
```

### Check Port
```bash
netstat -tuln | grep 8080
```

---

## Credentials Summary

| Service | Credential | Value |
|---------|-----------|-------|
| Reverb App ID | `REVERB_APP_ID` | `funlynk` |
| Reverb App Key | `REVERB_APP_KEY` | `funlynk_reverb_key_2025` |
| Reverb App Secret | `REVERB_APP_SECRET` | `funlynk_reverb_secret_2025` |
| Redis Password | `REDIS_PASSWORD` | `funlynk_redis_2025` |
| Server IP | - | `178.156.193.56` |
| WebSocket Port | - | `8080` |

---

**Status**: ✅ Reverb running on port 8080  
**Next**: Configure Nginx + SSL for production use

