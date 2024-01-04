#!/bin/bash
set -o errexit -o nounset -o pipefail

source /lib.sh

entrypoint_log "==> Create the storage tree if needed"
as_www_user cp --recursive storage.skel/* storage/

entrypoint_log "==> Ensure storage is linked"
as_www_user php artisan storage:link

entrypoint_log "==> Ensure permissions are correct"
chown --recursive www-data:www-data storage/ bootstrap/
