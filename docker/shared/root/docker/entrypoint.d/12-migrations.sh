#!/bin/bash
: "${ENTRYPOINT_ROOT:="/docker"}"

# shellcheck source=SCRIPTDIR/../helpers.sh
source "${ENTRYPOINT_ROOT}/helpers.sh"

entrypoint-set-script-name "$0"

# Allow automatic applying of outstanding/new migrations on startup
: "${DB_APPLY_NEW_MIGRATIONS_AUTOMATICALLY:=0}"

# Wait for the database to be ready
await-database-ready

# Run the migrate:status command and capture output
output=$(run-as-runtime-user php artisan migrate:status || :)

# By default we have no new migrations
declare -i new_migrations=0

# Detect if any new migrations are available by checking for "No" in the output
echo "$output" | grep No && new_migrations=1

if is-false "${new_migrations}"; then
    log-info "No new migrations detected"

    exit 0
fi

log-warning "New migrations available"

# Print the output
echo "$output"

if is-false "${DB_APPLY_NEW_MIGRATIONS_AUTOMATICALLY}"; then
    log-info "Automatic applying of new database migrations is disabled"
    log-info "Please set [DB_APPLY_NEW_MIGRATIONS_AUTOMATICALLY=1] in your [.env] file to enable this."

    exit 0
fi

run-as-runtime-user php artisan migrate --force
