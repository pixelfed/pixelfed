#!/bin/bash
set -o errexit -o nounset -o pipefail

source /lib.sh

entrypoint_log "==> route:cache"
as_runtime_user php artisan route:cache

entrypoint_log "==> view:cache"
as_runtime_user php artisan view:cache

entrypoint_log "==> config:cache"
as_runtime_user php artisan config:cache
