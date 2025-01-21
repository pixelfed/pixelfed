#!/bin/bash
: "${ENTRYPOINT_ROOT:="/docker"}"

# shellcheck source=SCRIPTDIR/../helpers.sh
source "${ENTRYPOINT_ROOT}/helpers.sh"

entrypoint-set-script-name "$0"

run-as-runtime-user php artisan config:cache
run-as-runtime-user php artisan route:cache
run-as-runtime-user php artisan view:cache
