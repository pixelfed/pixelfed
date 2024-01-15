#!/bin/bash
: "${ENTRYPOINT_ROOT:="/docker"}"

# shellcheck source=SCRIPTDIR/../helpers.sh
source "${ENTRYPOINT_ROOT}/helpers.sh"

entrypoint-set-script-name "$0"

# Allow automatic applying of outstanding/new migrations on startup
: "${DOCKER_RUN_ONE_TIME_SETUP_TASKS:=1}"

if is-false "${DOCKER_RUN_ONE_TIME_SETUP_TASKS}"; then
    log-warning "Automatic run of the 'One-time setup tasks' is disabled."
    log-warning "Please set [DOCKER_RUN_ONE_TIME_SETUP_TASKS=1] in your [.env] file to enable this."

    exit 0
fi

load-config-files
await-database-ready

only-once "storage:link" run-as-runtime-user php artisan storage:link
only-once "key:generate" run-as-runtime-user php artisan key:generate
only-once "initial:migrate" run-as-runtime-user php artisan migrate --force
only-once "import:cities" run-as-runtime-user php artisan import:cities
