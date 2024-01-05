#!/bin/bash
source /docker/helpers.sh

entrypoint-set-script-name "$0"

# Copy the [storage/] skeleton files over the "real" [storage/] directory so assets are updated between versions
cp --recursive storage.skel/. storage/

# Ensure storage linkk are correctly configured
php artisan storage:link
