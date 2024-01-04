#!/bin/bash
# short curcuit the entrypoint if $ENTRYPOINT_SKIP isn't set to 0
if [[ ${ENTRYPOINT_SKIP:=0} != 0 ]]; then
    exec "$@"
fi

# Directory where entrypoint scripts lives
: ${ENTRYPOINT_ROOT:="/docker/entrypoint.d/"}
export ENTRYPOINT_ROOT

# Space separated list of scripts the entrypoint runner should skip
: ${ENTRYPOINT_SKIP_SCRIPTS:=""}

# Load helper scripts
source /docker/helpers.sh

# Set the entrypoint name for logging
entrypoint-set-name "entrypoint.sh"

# Convert ENTRYPOINT_SKIP_SCRIPTS into a native bash array for easier lookup
declare -a skip_scripts
IFS=' ' read -a skip_scripts <<<"$ENTRYPOINT_SKIP_SCRIPTS"

# Ensure the entrypoint root folder exists
mkdir -p "${ENTRYPOINT_ROOT}"

# If ENTRYPOINT_ROOT directory is empty, warn and run the regular command
if is-directory-empty "${ENTRYPOINT_ROOT}"; then
    log-warning "No files found in ${ENTRYPOINT_ROOT}, skipping configuration"

    exec "$@"
fi

# Start scanning for entrypoint.d files to source or run
log-info "looking for shell scripts in [${ENTRYPOINT_ROOT}]"

find "${ENTRYPOINT_ROOT}" -follow -type f -print | sort -V | while read -r file; do
    # Skip the script if it's in the skip-script list
    if in-array $(get-entrypoint-script-name "${file}") skip_scripts; then
        log-warning "Skipping script [${script_name}] since it's in the skip list (\$ENTRYPOINT_SKIP_SCRIPTS)"

        continue
    fi

    # Inspect the file extension of the file we're processing
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

exec "$@"
