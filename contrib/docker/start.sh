#!/bin/bash

# Create the storage tree if needed and fix permissions
cp -r storage.skel/* storage/
chown -R www-data:www-data storage/ bootstrap/

# Refresh the environment
php artisan storage:link
php artisan horizon:assets
php artisan route:cache
php artisan view:cache
php artisan config:cache

# Migrate database if the app was upgraded
# gosu www-data:www-data php artisan migrate --force

# Run other specific migratins if required
# gosu www-data:www-data php artisan update

# Run a worker if it is set as embedded
if [ "$HORIZON_EMBED" = "true" ]; then
  gosu www-data:www-data php artisan horizon &
fi

# Finally run Apache
exec apache2-foreground
