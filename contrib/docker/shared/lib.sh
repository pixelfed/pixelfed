#!/bin/bash

set -e

function entrypoint_log() {
    if [ -z "${ENTRYPOINT_QUIET_LOGS:-}" ]; then
        echo "/docker-entrypoint.sh: $@"
    fi
}

function as_runtime_user() {
    su --preserve-environment ${RUNTIME_UID} --shell /bin/bash --command "${*}"
}
