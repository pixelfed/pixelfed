# Pixelfed Docker Setup with serversideup/php

This setup uses `serversideup/php:8.4-fpm-nginx` as the base image and is designed to work behind a reverse proxy like Nginx Proxy Manager for HTTPS termination.

## Prerequisites

- Docker and Docker Compose installed
- A reverse proxy (e.g., Nginx Proxy Manager) for HTTPS
- Domain name configured

## Quick Start

1. **Copy the environment file:**
   ```bash
   cp .env.docker.example .env
   ```

2. **Update `.env` with your configuration:**
   - Set `APP_KEY` ( generate with https://laravel-encryption-key-generator.vercel.app/ )
   - Update `APP_URL`, `APP_DOMAIN`, `ADMIN_DOMAIN`, `SESSION_DOMAIN` with your domain
   - Set secure database passwords for `DB_PASSWORD` and `DB_ROOT_PASSWORD`
   - Configure mail settings

3. **Build container**
   ```bash
   docker compose build
   ```

4. **Build and start the containers:**
   ```bash
   docker compose up -d
   ```

5. **Generate application key (if not done in step 2):**
   ```bash
   docker compose exec pixelfed php artisan instance:actor
   docker compose exec pixelfed php artisan import:cities
   docker compose exec pixelfed php artisan passport:keys
   ```

6. **Create admin user:**
   ```bash
   docker compose exec pixelfed php artisan user:create
   ```

## Reverse Proxy Configuration

### Nginx Proxy Manager

1. Add a new Proxy Host in Nginx Proxy Manager
2. Set the following:
   - **Domain Names:** Your domain (e.g., `pixelfed.yourdomain.com`)
   - **Scheme:** `http`
   - **Forward Hostname/IP:** `pixelfed-app` (or the Docker host IP)
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
docker compose logs -f

# Run artisan commands
docker compose exec pixelfed php artisan [command]

# Access container shell
docker compose exec pixelfed bash

# Restart services
docker compose restart

# Stop services
docker compose down

# Stop and remove volumes (WARNING: deletes data)
docker compose down -v
```

## Environment Variables

Key environment variables configured in the Dockerfile/docker-compose:

- `PHP_POST_MAX_SIZE=500M` - Maximum POST data size
- `PHP_UPLOAD_MAX_FILE_SIZE=500M` - Maximum upload file size
- `PHP_OPCACHE_ENABLE=1` - Enable OPcache for performance
- `AUTORUN_ENABLED=true` - Enable Laravel auto-run features
- `AUTORUN_LARAVEL_MIGRATION=true` - Auto-run migrations on startup
- `AUTORUN_LARAVEL_STORAGE_LINK=true` - Auto-create storage symlink
- `AUTORUN_LARAVEL_EVENT_CACHE=true` - Auto-cache events
- `AUTORUN_LARAVEL_ROUTE_CACHE=true` - Auto-cache routes
- `AUTORUN_LARAVEL_VIEW_CACHE=true` - Auto-cache views
- `AUTORUN_LARAVEL_CONFIG_CACHE=true` - Auto-cache config

## Troubleshooting

### Permission Issues
```bash
docker compose exec pixelfed chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
```

### Clear Cache
```bash
docker compose exec pixelfed php artisan cache:clear
docker compose exec pixelfed php artisan config:clear
docker compose exec pixelfed php artisan route:clear
docker compose exec pixelfed php artisan view:clear
```

### Database Connection Issues
- Verify database credentials in `.env`
- Check if database container is running: `docker compose ps`
- View database logs: `docker compose logs -f db`
