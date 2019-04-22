#!/bin/bash

# Create the storage tree if needed and fix permissions
cp -r storage.skel/* storage/
chown -R www-data:www-data storage/ bootstrap/
php artisan storage:link

# Migrate database if the app was upgraded
# gosu www-data:www-data php artisan migrate --force

# Run other specific migratins if required
# gosu www-data:www-data php artisan update

# Finally run Apache
exec apache2-foreground
