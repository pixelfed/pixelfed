#!/bin/bash

set -o allexport
source .env
set +o allexport

cp -r storage.skel/* storage/
chown -R www-data:www-data storage/
php artisan migrate --force
php artisan storage:link

php artisan horizon &
exec apache2-foreground
