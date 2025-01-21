#!/bin/bash
: "${ENTRYPOINT_ROOT:="/docker"}"

# shellcheck source=SCRIPTDIR/../helpers.sh
source "${ENTRYPOINT_ROOT}/helpers.sh"

entrypoint-set-script-name "$0"

# Copy the [storage/] skeleton files over the "real" [storage/] directory so assets are updated between versions
run-as-runtime-user cp --force --recursive storage.skel/. ./storage/

# Ensure storage linkk are correctly configured
run-as-runtime-user php artisan storage:link
