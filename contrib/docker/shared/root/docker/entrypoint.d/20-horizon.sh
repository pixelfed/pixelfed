#!/bin/bash
source /docker/helpers.sh

entrypoint-set-name "$0"

run-as-runtime-user php artisan horizon:publish
