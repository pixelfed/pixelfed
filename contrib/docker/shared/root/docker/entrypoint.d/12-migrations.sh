#!/bin/bash
source /docker/helpers.sh

entrypoint-set-script-name "$0"

await-database-ready

declare new_migrations=0
run-as-runtime-user php artisan migrate:status | grep No && migrations=yes || migrations=no
