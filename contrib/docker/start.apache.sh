#!/bin/bash

info() { echo >&2 "$*" ; }

# Create the storage tree if needed and fix permissions
cp -r storage.skel/* storage/
chown -R www-data:www-data storage/ bootstrap/

# Do initial setup if it hasn't been done
# These are the one-time setup tasks from:
# https://docs.pixelfed.org/running-pixelfed/installation/#one-time-setup-tasks
if [ -z "$APP_KEY" ]; then
	info "creating app key"
	php artisan key:generate || exit 1
fi

if [ ! -r storage/.migrated ]; then
	for i in `seq 1 10`; do
		info "******* WAITING FOR DB TO COME UP ********"
		if echo 'exit' | mysql \
			-u $DB_USERNAME \
			-p$DB_PASSWORD \
			-h $DB_HOST \
			-P $DB_PORT \
			$DB_DATABASE \
		; then
			break
		fi
		sleep 10
	done

	info "Creating database scheme; this might take a while"
	php artisan migrate --force || exit 1
	touch storage/.migrated
fi

if [ ! -r storage/.instance ]; then
	info "Creating ActivityPub federation id"
	php artisan instance:actor || exit 1
	touch storage/.instance
fi

if [ ! -r storage/.passport ]; then
	info "Creating OAuth keys"
	php artisan passport:keys || exit 1
	touch storage/.passport
fi


# Refresh the environment
php artisan storage:link
php artisan horizon:publish
php artisan route:cache
php artisan view:cache
php artisan config:cache

# Finally run Apache
exec apache2-foreground
