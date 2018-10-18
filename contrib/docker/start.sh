#!/bin/bash

# Create the storage tree if needed and fix permissions
cp -r storage.skel/* storage/
chown -R www-data:www-data storage/
php artisan storage:link

# Migrate database if the app was upgraded
php artisan migrate --force

# Run other specific migratins if required
php artisan update

# Run a worker if it is set as embedded
if [ $HORIZON_EMBED = true ]; then
	php artisan horizon &
fi

# Finally run Apache
exec apache2-foreground
