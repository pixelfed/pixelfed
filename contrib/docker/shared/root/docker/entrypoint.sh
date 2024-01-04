#!/bin/bash
set -e -o errexit -o nounset -o pipefail

[[ -n ${ENTRYPOINT_DEBUG:-} ]] && set -x

declare -g ME="$0"
declare -gr ENTRYPOINT_ROOT=/docker/entrypoint.d/

source /docker/helpers.sh

# ensure the entrypoint folder exists
mkdir -p "${ENTRYPOINT_ROOT}"

if /usr/bin/find "${ENTRYPOINT_ROOT}" -mindepth 1 -maxdepth 1 -type f -print -quit 2>/dev/null | read v; then
    log "looking for shell scripts in /docker/entrypoint.d/"
    find "${ENTRYPOINT_ROOT}" -follow -type f -print | sort -V | while read -r f; do
        case "$f" in
        *.envsh)
            if [ -x "$f" ]; then
                log "Sourcing $f"
                source "$f"
                resetore_identity
            else
                # warn on shell scripts without exec bit
                log_warning "Ignoring $f, not executable"
            fi
            ;;

        *.sh)
            if [ -x "$f" ]; then
                log "Launching $f"
                "$f"
            else
                # warn on shell scripts without exec bit
                log_warning "Ignoring $f, not executable"
            fi
            ;;

        *)
            log_warning "Ignoring $f"
            ;;
        esac
    done

    log "Configuration complete; ready for start up"
else
    log_warning "No files found in ${ENTRYPOINT_ROOT}, skipping configuration"
fi

exec "$@"
