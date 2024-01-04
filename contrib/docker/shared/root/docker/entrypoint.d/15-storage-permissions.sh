#!/bin/bash
source /docker/helpers.sh

entrypoint-set-name "$0"

# Optionally fix ownership of configured paths
: ${ENTRYPOINT_ENSURE_OWNERSHIP_PATHS:=""}

declare -a ensure_ownership_paths=()
IFS=' ' read -a ensure_ownership_paths <<<"$ENTRYPOINT_ENSURE_OWNERSHIP_PATHS"

if [[ ${#ensure_ownership_paths} == 0 ]]; then
    log-info "No paths has been configured for ownership fixes via [\$ENTRYPOINT_ENSURE_OWNERSHIP_PATHS]."

    exit 0
fi

for path in "${ensure_ownership_paths[@]}"; do
    log-info "Ensure ownership of [${path}] correct"
    chown --recursive ${RUNTIME_UID}:${RUNTIME_GID} "${path}"
done
