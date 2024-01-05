#!/bin/bash
source /docker/helpers.sh

entrypoint-set-script-name "$0"

run-as-runtime-user php artisan route:cache
run-as-runtime-user php artisan view:cache
run-as-runtime-user php artisan config:cache
