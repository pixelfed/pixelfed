#!/bin/bash
source /docker/helpers.sh

entrypoint-set-name "$0"

run-as-runtime-user cp --recursive storage.skel/* storage/
run-as-runtime-user php artisan storage:link

log-info "Ensure permissions are correct"
chown --recursive ${RUNTIME_UID}:${RUNTIME_GID} storage/ bootstrap/
