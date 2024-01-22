#!/bin/bash
: "${ENTRYPOINT_ROOT:="/docker"}"

# shellcheck source=SCRIPTDIR/../helpers.sh
source "${ENTRYPOINT_ROOT}/helpers.sh"

entrypoint-set-script-name "$0"

load-config-files

# Allow automatic applying of outstanding/new migrations on startup
: "${DOCKER_APP_RUN_ONE_TIME_SETUP_TASKS:=1}"

if is-false "${DOCKER_APP_RUN_ONE_TIME_SETUP_TASKS}"; then
    log-warning "Automatic run of the 'One-time setup tasks' is disabled."
    log-warning "Please set [DOCKER_APP_RUN_ONE_TIME_SETUP_TASKS=1] in your [.env] file to enable this."

    exit 0
fi

await-database-ready

# Following https://docs.pixelfed.org/running-pixelfed/installation/#one-time-setup-tasks
#
# NOTE: Caches happens in [30-cache.sh]

only-once "key:generate" run-as-runtime-user php artisan key:generate
only-once "storage:link" run-as-runtime-user php artisan storage:link
only-once "initial:migrate" run-as-runtime-user php artisan migrate --force
only-once "import:cities" run-as-runtime-user php artisan import:cities

if is-true "${ACTIVITY_PUB:-false}"; then
    only-once "instance:actor" run-as-runtime-user php artisan instance:actor
fi

if is-true "${OAUTH_ENABLED:-false}"; then
    only-once "passport:keys" run-as-runtime-user php artisan passport:keys
fi
