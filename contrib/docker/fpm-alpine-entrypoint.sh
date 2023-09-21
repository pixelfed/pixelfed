#!/bin/sh
set -e

# first arg is `-f` or `--some-option`
if [ "${1#-}" != "$1" ]; then
	set -- php-fpm "$@"
fi

if [ "$1" = 'php-fpm' ] || [ "$1" = 'php' ] || [ "$1" = 'artisan' ]; then
	# Create the storage tree if needed and fix permissions
	cp -r storage.skel/* storage/

	# Refresh the environment
	php artisan config:cache
    php artisan storage:link
    php artisan horizon:publish
    php artisan route:cache
    php artisan view:cache
fi

exec docker-php-entrypoint "$@"
