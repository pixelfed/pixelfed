# Pixelfed VinylHub owner-runtime Docker Compose setup

This is the canonical local VinylHub T2 entrypoint. It builds the checked-out
Pixelfed source with the repository `Dockerfile`, uses the admitted MySQL and
Redis image digests, and shares one task-owned named storage volume across the
web, Horizon, and scheduler services. Native GitHub PHP/SQLite tests use a
separate T1 workflow.

## Prerequisites

- Docker and Docker Compose installed
- Docker Desktop with Linux containers
- A clean task-owned checkout at the source SHA under validation

## Quick Start

1. **Prepare the environment file:**
    ```bash
    cp .env.example .env
    # Set a disposable APP_KEY and passwords in .env before starting.
    ```

    PowerShell equivalent:

    ```powershell
    Copy-Item .env.example .env
    ```

2. **Render and build the exact current source:**
   ```bash
   docker compose -f docker-compose.yml config
   docker compose -f docker-compose.yml build pixelfed
   ```

    #### Container Build Troubleshooting ####
   
    `open /home/username/pixelfed/storage/app/public/m/_v2/xxxxxxxxxxxxxxxxxx/xxxxxxxxxxx-xxxxxxxxxx/xxxxxxxxxxxx: permission denied` or similar might require fixing local permissions.
    ```bash
    sudo find storage/ -type d -exec chmod 755 {} \; # set all directories to rwx by user/group
    sudo find storage/ -type f -exec chmod 644 {} \; # set all files to rw by user/group
    ```

3. **Start the owner runtime:**
   ```bash
   docker compose -f docker-compose.yml up -d --no-build --wait db redis pixelfed horizon scheduler
   ```

4. **Run bounded readiness checks:**
   ```bash
   docker compose -f docker-compose.yml ps
   docker compose -f docker-compose.yml exec pixelfed php artisan migrate:status
   docker compose -f docker-compose.yml exec horizon php artisan horizon:status
   ```

5. **Bootstrap Passport only when authenticated API evidence is required:**
   ```bash
   docker compose -f docker-compose.yml exec pixelfed php artisan passport:keys
   docker compose -f docker-compose.yml exec pixelfed php artisan passport:client --personal
   ```

## Reverse Proxy Configuration

### Cloudflare Tunnel

1. Doco coming soon

### Nginx Proxy Manager

1. Add a new Proxy Host in Nginx Proxy Manager
2. Set the following:
   - **Domain Names:** Your domain (e.g., `pixelfed.yourdomain.com`)
   - **Scheme:** `http`
   - **Forward Hostname/IP:** the Docker host
   - **Forward Port:** `8080`
   - **Enable:** Websockets Support, Block Common Exploits
3. Configure SSL certificate (Let's Encrypt recommended)
4. Add custom Nginx configuration if needed:
   ```nginx
   client_max_body_size 500M;
   proxy_read_timeout 300s;
   ```

### Manual Nginx Configuration

```nginx
server {
    listen 443 ssl http2;
    server_name yourdomain.com;

    ssl_certificate /path/to/cert.pem;
    ssl_certificate_key /path/to/key.pem;

    client_max_body_size 500M;

    location / {
        proxy_pass http://localhost:8080;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_read_timeout 300s;
    }
}
```

## Useful Commands

```bash
# View logs
docker compose -f docker-compose.yml logs -f

# Run artisan commands
docker compose -f docker-compose.yml exec pixelfed php artisan [command]

# Access container shell
docker compose -f docker-compose.yml exec pixelfed bash

# Restart services
docker compose -f docker-compose.yml restart

# Stop services
docker compose -f docker-compose.yml down

# Stop and remove task-owned database/cache volumes
docker compose -f docker-compose.yml down -v
```
