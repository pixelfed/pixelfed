#!/bin/bash
source /docker/helpers.sh

set_identity "$0"

log "==> route:cache"
as_runtime_user php artisan route:cache

log "==> view:cache"
as_runtime_user php artisan view:cache

log "==> config:cache"
as_runtime_user php artisan config:cache
