#!/bin/bash
set -e -o errexit -o nounset -o pipefail

: ${ENTRYPOINT_SKIP:=0}
: ${ENTRYPOINT_SKIP_SCRIPTS:=""}
: ${ENTRYPOINT_DEBUG:=0}
: ${ENTRYPOINT_ROOT:="/docker/entrypoint.d/"}

export ENTRYPOINT_ROOT

if [[ ${ENTRYPOINT_SKIP} == 0 ]]; then
    [[ ${ENTRYPOINT_DEBUG} == 1 ]] && set -x

    source /docker/helpers.sh

    declare -a skip_scripts=()
    IFS=' ' read -a skip_scripts <<<"$ENTRYPOINT_SKIP_SCRIPTS"

    declare script_name

    # ensure the entrypoint folder exists
    mkdir -p "${ENTRYPOINT_ROOT}"

    if /usr/bin/find "${ENTRYPOINT_ROOT}" -mindepth 1 -maxdepth 1 -type f -print -quit 2>/dev/null | read v; then
        log "looking for shell scripts in /docker/entrypoint.d/"

        find "${ENTRYPOINT_ROOT}" -follow -type f -print | sort -V | while read -r f; do
            script_name="$(get_script_name $f)"
            if array_value_exists skip_scripts "${script_name}"; then
                log_warning "Skipping script [${script_name}] since it's in the skip list (\$ENTRYPOINT_SKIP_SCRIPTS)"

                continue
            fi

            case "$f" in
            *.envsh)
                if [ -x "$f" ]; then
                    log "Sourcing $f"

                    source "$f"

                    resetore_identity
                else
                    # warn on shell scripts without exec bit
                    log_error_and_exit "File [$f] is not executable (please 'chmod +x' it)"
                fi
                ;;

            *.sh)
                if [ -x "$f" ]; then
                    log "Launching $f"
                    "$f"
                else
                    # warn on shell scripts without exec bit
                    log_error_and_exit "File [$f] is not executable (please 'chmod +x' it)"
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
fi

exec "$@"
