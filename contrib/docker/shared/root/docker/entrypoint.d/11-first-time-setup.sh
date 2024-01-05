#!/bin/bash
source /docker/helpers.sh

entrypoint-set-script-name "$0"

# if the script is running in another container, wait for it to complete
while [ -e "./storage/docker-first-time-is-running" ]; do
    sleep 1
done

# We got the lock!
touch "./storage/docker-first-time-is-running"

# Make sure to clean up on exit
trap "rm -f ./storage/docker-first-time-is-running" EXIT

if [ ! -e "./storage/docker-storage-link-has-run" ]; then
    run-as-runtime-user php artisan storage:link
    touch "./storage/docker-storage-link-has-run"
fi

if [ ! -e "./storage/docker-key-generate-has-run" ]; then
    run-as-runtime-user php artisan key:generate
    touch "./storage/docker-key-generate-has-run"
fi

if [ ! -e "./storage/docker-migrate-has-run" ]; then
    run-as-runtime-user php artisan migrate --force
    touch "./storage/docker-migrate-has-run"
fi

if [ ! -e "./storage/docker-import-cities-has-run" ]; then
    run-as-runtime-user php artisan import:cities
    touch "./storage/docker-import-cities-has-run"
fi

# if [ ! -e "./storage/docker-instance-actor-has-run" ]; then
#     run-as-runtime-user php artisan instance:actor
#     touch "./storage/docker-instance-actor-has-run"
# fi

# if [ ! -e "./storage/docker-passport-keys-has-run" ]; then
#     run-as-runtime-user php artisan instance:actor
#     touch "./storage/docker-passport-keys-has-run"
# fi
