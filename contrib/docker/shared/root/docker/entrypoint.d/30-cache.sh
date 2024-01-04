#!/bin/bash
source /docker/helpers.sh

set_identity "$0"

as_runtime_user php artisan route:cache
as_runtime_user php artisan view:cache
as_runtime_user php artisan config:cache
