#!/bin/bash
: "${ENTRYPOINT_ROOT:="/docker"}"

# shellcheck source=SCRIPTDIR/../helpers.sh
source "${ENTRYPOINT_ROOT}/helpers.sh"

entrypoint-set-script-name "$0"

# Validating dot-env files for any issues
for file in "${dot_env_files[@]}"; do
    if ! file-exists "${file}"; then
        log-warning "Could not source file [${file}]: does not exists"
        continue
    fi

    # We ignore 'dir' + 'file' rules since they are validate *host* paths
    # which do not (and should not) exists inside the container
    #
    # We disable fixer since its not interactive anyway
    run-as-current-user dottie validate --file "${file}" --ignore-rule dir,file --exclude-prefix APP_KEY --no-fix
done
