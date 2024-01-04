#!/bin/bash
source /docker/helpers.sh

set_identity "$0"

as_runtime_user cp --recursive storage.skel/* storage/
as_runtime_user php artisan storage:link

log "Ensure permissions are correct"
chown --recursive ${RUNTIME_UID}:${RUNTIME_GID} storage/ bootstrap/
