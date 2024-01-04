#!/bin/bash
source /docker/helpers.sh

set_identity "$0"

as_runtime_user php artisan horizon:publish
