#!/bin/bash
source /docker/helpers.sh

entrypoint-set-script-name "$0"

load-config-files
await-database-ready

only-once "storage:link" run-as-runtime-user php artisan storage:link
only-once "key:generate" run-as-runtime-user php artisan key:generate
only-once "initial:migrate" run-as-runtime-user php artisan migrate --force
only-once "import:cities" run-as-runtime-user php artisan import:cities

# if [ ! -e "./storage/docker-instance-actor-has-run" ]; then
#     run-as-runtime-user php artisan instance:actor
#     touch "./storage/docker-instance-actor-has-run"
# fi

# if [ ! -e "./storage/docker-passport-keys-has-run" ]; then
#     run-as-runtime-user php artisan instance:actor
#     touch "./storage/docker-passport-keys-has-run"
# fi
