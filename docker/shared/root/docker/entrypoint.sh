#!/bin/bash
# short curcuit the entrypoint if $ENTRYPOINT_SKIP isn't set to 0
if [[ ${ENTRYPOINT_SKIP:=0} != 0 ]]; then
    exec "$@"
fi

: "${ENTRYPOINT_ROOT:="/docker"}"
export ENTRYPOINT_ROOT

# Directory where entrypoint scripts lives
: "${ENTRYPOINT_D_ROOT:="${ENTRYPOINT_ROOT}/entrypoint.d/"}"
export ENTRYPOINT_D_ROOT

: "${DOCKER_APP_HOST_OVERRIDES_PATH:="${ENTRYPOINT_ROOT}/overrides"}"
export DOCKER_APP_HOST_OVERRIDES_PATH

# Space separated list of scripts the entrypoint runner should skip
: "${ENTRYPOINT_SKIP_SCRIPTS:=""}"

# Load helper scripts
#
# shellcheck source=SCRIPTDIR/helpers.sh
source "${ENTRYPOINT_ROOT}/helpers.sh"

# Set the entrypoint name for logging
entrypoint-set-script-name "entrypoint.sh"

# Convert ENTRYPOINT_SKIP_SCRIPTS into a native bash array for easier lookup
declare -a skip_scripts
# shellcheck disable=SC2034
IFS=' ' read -r -a skip_scripts <<< "$ENTRYPOINT_SKIP_SCRIPTS"

# Ensure the entrypoint root folder exists
mkdir -p "${ENTRYPOINT_D_ROOT}"

# If ENTRYPOINT_D_ROOT directory is empty, warn and run the regular command
if directory-is-empty "${ENTRYPOINT_D_ROOT}"; then
    log-warning "No files found in ${ENTRYPOINT_D_ROOT}, skipping configuration"

    exec "$@"
fi

# If the overridess directory exists, then copy all files into the container
if ! directory-is-empty "${DOCKER_APP_HOST_OVERRIDES_PATH}"; then
    log-info "Overrides directory is not empty, copying files"
    run-as-current-user cp --verbose --recursive "${DOCKER_APP_HOST_OVERRIDES_PATH}/." /
fi

acquire-lock "entrypoint.sh"

# Start scanning for entrypoint.d files to source or run
log-info "looking for shell scripts in [${ENTRYPOINT_D_ROOT}]"

find "${ENTRYPOINT_D_ROOT}" -follow -type f -print | sort -V | while read -r file; do
    # Skip the script if it's in the skip-script list
    if in-array "$(get-entrypoint-script-name "${file}")" skip_scripts; then
        log-warning "Skipping script [${file}] since it's in the skip list (\$ENTRYPOINT_SKIP_SCRIPTS)"

        continue
    fi

    # Inspect the file extension of the file we're processing
    case "${file}" in
        *.envsh)
            if ! is-executable "${file}"; then
                # warn on shell scripts without exec bit
                log-error-and-exit "File [${file}] is not executable (please 'chmod +x' it)"
            fi

            log-info "${section_message_color}============================================================${color_clear}"
            log-info "${section_message_color}Sourcing [${file}]${color_clear}"
            log-info "${section_message_color}============================================================${color_clear}"

            # shellcheck disable=SC1090
            source "${file}"

            # the sourced file will (should) than the log prefix, so this restores our own
            # "global" log prefix once the file is done being sourced
            entrypoint-restore-script-name
            ;;

        *.sh)
            if ! is-executable "${file}"; then
                # warn on shell scripts without exec bit
                log-error-and-exit "File [${file}] is not executable (please 'chmod +x' it)"
            fi

            log-info "${section_message_color}============================================================${color_clear}"
            log-info "${section_message_color}Executing [${file}]${color_clear}"
            log-info "${section_message_color}============================================================${color_clear}"

            "${file}"
            ;;

        *)
            log-warning "Ignoring unrecognized file [${file}]"
            ;;
    esac
done

release-lock "entrypoint.sh"

log-info "Configuration complete; ready for start up"

exec "$@"
