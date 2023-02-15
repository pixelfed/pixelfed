#!/bin/bash

# We should already be in /var/www, but just to be explicit
cd /var/www || exit

if [[ ! -e storage/.docker.init ]]; then
    echo "Database is not initialized yet, exiting..."
    sleep 5
    exit 1
fi

# Check for migrations
gosu www-data php artisan migrate:status | grep No && migrations=yes || migrations=no

if [ "$migrations" = "yes" ]; then
    echo "Found outstanding migrations, exiting..."
    sleep 5
    exit 1
fi

# Finally run Apache
gosu www-data php artisan horizon
