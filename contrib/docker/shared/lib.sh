#!/bin/bash

set -e

function entrypoint_log() {
    if [ -z "${ENTRYPOINT_QUIET_LOGS:-}" ]; then
        echo "/docker-entrypoint.sh: $@"
    fi
}

function as_www_user() {
	su --preserve-environment www-data --shell /bin/bash --command "${*}"
}
