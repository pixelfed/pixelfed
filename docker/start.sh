#!/bin/sh

mkdir -p storage/framework/{cache,sessions,views,logs}
php artisan migrate

exec apache2-foreground
