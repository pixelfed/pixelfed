#!/bin/bash
source /docker/helpers.sh

entrypoint-set-script-name "$0"

# Ensure the two Docker volumes and dot-env files are owned by the runtime user as other scripts
# will be writing to these
run-as-current-user chown --verbose ${RUNTIME_UID}:${RUNTIME_GID} "./.env"
run-as-current-user chown --verbose ${RUNTIME_UID}:${RUNTIME_GID} "./bootstrap/cache"
run-as-current-user chown --verbose ${RUNTIME_UID}:${RUNTIME_GID} "./storage"

# Optionally fix ownership of configured paths
: ${ENTRYPOINT_ENSURE_OWNERSHIP_PATHS:=""}

declare -a ensure_ownership_paths=()
IFS=' ' read -a ensure_ownership_paths <<<"${ENTRYPOINT_ENSURE_OWNERSHIP_PATHS}"

if [[ ${#ensure_ownership_paths[@]} == 0 ]]; then
    log-info "No paths has been configured for ownership fixes via [\$ENTRYPOINT_ENSURE_OWNERSHIP_PATHS]."

    exit 0
fi

for path in "${ensure_ownership_paths[@]}"; do
    log-info "Ensure ownership of [${path}] is correct"
    stream-prefix-command-output run-as-current-user chown --recursive ${RUNTIME_UID}:${RUNTIME_GID} "${path}"
done