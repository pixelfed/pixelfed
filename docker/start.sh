#!/bin/bash

cp -r storage.skel/* storage/
php artisan migrate --force

exec apache2-foreground
