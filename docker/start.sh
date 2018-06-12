#!/bin/sh

mkdir -p storage/app/public/avatars
mkdir -p storage/{debugbar,logs}
mkdir -p storage/framework/{cache,sessions,views,testing}
php artisan migrate

exec apache2-foreground
