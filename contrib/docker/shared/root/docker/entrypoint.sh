#!/bin/bash
if [[ ${ENTRYPOINT_SKIP:=0} != 0 ]]; then
    exec "$@"
fi

: ${ENTRYPOINT_ROOT:="/docker/entrypoint.d/"}
: ${ENTRYPOINT_SKIP_SCRIPTS:=""}

export ENTRYPOINT_ROOT

source /docker/helpers.sh

entrypoint-set-name "entrypoint.sh"

declare -a skip_scripts
IFS=' ' read -a skip_scripts <<<"$ENTRYPOINT_SKIP_SCRIPTS"

declare script_name

# ensure the entrypoint folder exists
mkdir -p "${ENTRYPOINT_ROOT}"

if /usr/bin/find "${ENTRYPOINT_ROOT}" -mindepth 1 -maxdepth 1 -type f -print -quit 2>/dev/null | read v; then
    log-info "looking for shell scripts in /docker/entrypoint.d/"

    find "${ENTRYPOINT_ROOT}" -follow -type f -print | sort -V | while read -r file; do
        script_name="$(get-entrypoint-script-name $file)"

        if in-array "${script_name}" skip_scripts; then
            log-warning "Skipping script [${script_name}] since it's in the skip list (\$ENTRYPOINT_SKIP_SCRIPTS)"

            continue
        fi

        case "${file}" in
        *.envsh)
            if ! is-executable "${file}"; then
                # warn on shell scripts without exec bit
                log-error-and-exit "File [${file}] is not executable (please 'chmod +x' it)"
            fi

            log-info "Sourcing [${file}]"

            source "${file}"

            # the sourced file will (should) than the log prefix, so this restores our own
            # "global" log prefix once the file is done being sourced
            entrypoint-restore-name
            ;;

        *.sh)
            if ! is-executable "${file}"; then
                # warn on shell scripts without exec bit
                log-error-and-exit "File [${file}] is not executable (please 'chmod +x' it)"
            fi

            log-info "Running [${file}]"
            "${file}"
            ;;

        *)
            log-warning "Ignoring unrecognized file [${file}]"
            ;;
        esac
    done

    log-info "Configuration complete; ready for start up"
else
    log-warning "No files found in ${ENTRYPOINT_ROOT}, skipping configuration"
fi

exec "$@"
