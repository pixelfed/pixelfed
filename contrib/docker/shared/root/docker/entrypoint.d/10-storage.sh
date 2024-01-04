#!/bin/bash
source /docker/helpers.sh

set_identity "$0"

log "Create the storage tree if needed"
as_runtime_user cp --recursive storage.skel/* storage/

log "Ensure storage is linked"
as_runtime_user php artisan storage:link

log "Ensure permissions are correct"
chown --recursive ${RUNTIME_UID}:${RUNTIME_GID} storage/ bootstrap/
