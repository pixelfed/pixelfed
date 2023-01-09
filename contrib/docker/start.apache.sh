#!/bin/bash

# We should already be in /var/www, but just to be explicit
cd /var/www || exit

# Create the storage tree if needed and fix permissions
cp -r storage.skel/* storage/
chown -R www-data:www-data storage/ bootstrap/

if [[ ! -e storage/.docker.init ]]; then
    echo "Fresh installation, initializing database..."
    chown www-data:www-data .env &&
        gosu www-data php artisan key:generate &&
        gosu www-data php artisan migrate:fresh --force &&
        gosu www-data php artisan passport:keys &&
        echo "done" >storage/.docker.init
fi

# Refresh the environment
gosu www-data php artisan storage:link
gosu www-data php artisan horizon:publish
gosu www-data php artisan config:cache
# gosu www-data php artisan cache:clear
gosu www-data php artisan route:cache
gosu www-data php artisan view:cache

# Check for migrations
gosu www-data php artisan migrate:status | grep No && migrations=yes || migrations=no

if [ "$migrations" = "yes" ]; then
    echo "Found outstanding migrations, running those..."
    gosu www-data php artisan migrate --force
fi

# Create instance actor
gosu www-data php artisan instance:actor

# Finally run Apache, passing along any parameters from `CMD`
dumb-init apache2-foreground "$@"
