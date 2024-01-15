#!/bin/bash
source /docker/helpers.sh

entrypoint-set-script-name "$0"

# Allow automatic applying of outstanding/new migrations on startup
: ${DOCKER_APPLY_NEW_MIGRATIONS_AUTOMATICALLY:=0}

# Wait for the database to be ready
await-database-ready

# Detect if we have new migrations
declare -i new_migrations=0
run-as-runtime-user php artisan migrate:status | grep No && new_migrations=1

if is-true "${new_migrations}"; then
    log-info "No outstanding migrations detected"

    exit 0
fi

log-warning "New migrations available, will automatically apply them now"

if is-false "${DOCKER_APPLY_NEW_MIGRATIONS_AUTOMATICALLY}"; then
    log-info "Automatic applying of new database migrations is disabled"
    log-info "Please set [DOCKER_APPLY_NEW_MIGRATIONS_AUTOMATICALLY=1] in your [.env] file to enable this."

    exit 0
fi

run-as-runtime-user php artisan migrate --force
