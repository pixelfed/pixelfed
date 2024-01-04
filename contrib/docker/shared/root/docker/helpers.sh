#!/bin/bash
set -e -o errexit -o nounset -o pipefail

declare -g error_message_color="\033[1;31m"
declare -g warn_message_color="\033[1;34m"
declare -g color_clear="\033[1;0m"
declare -g log_prefix=
declare -g old_log_prefix=
declare -ra dot_env_files=(
    /var/www/.env.docker
    /var/www/.env
)
declare -a seen_dot_env_variables=()

function set_identity() {
    old_log_prefix="${log_prefix}"
    log_prefix="ENTRYPOINT - [${1}] - "
}

function resetore_identity() {
    log_prefix="${old_log_prefix}"
}

function as_runtime_user() {
    su --preserve-environment $(id -un ${RUNTIME_UID}) --shell /bin/bash --command "${*}"
}

# @description Display the given error message with its line number on stderr and exit with error.
# @arg $message string A error message.
function log_error() {
    echo -e "${error_message_color}${log_prefix}ERROR - ${1}${color_clear}" >/dev/stderr
}

# @description Display the given error message with its line number on stderr and exit with error.
# @arg $message string A error message.
# @exitcode 1
function log_error_and_exit() {
    log_error "$1"

    exit 1
}

# @description Display the given warning message with its line number on stderr.
# @arg $message string A warning message.
function log_warning() {
    echo -e "${warn_message_color}${log_prefix}WARNING - ${1}${color_clear}" >/dev/stderr
}

function log() {
    if [ -z "${ENTRYPOINT_QUIET_LOGS:-}" ]; then
        echo "${log_prefix}$@"
    fi
}

function load-config-files() {
    # Associative array (aka map/disctrionary) holding the unique keys found in dot-env files
    local -A _tmp_dot_env_keys

    for f in "${dot_env_files[@]}"; do
        if [ ! -e "$f" ]; then
            log_warning "Could not source file [${f}]: does not exists"
            continue
        fi

        log "Sourcing ${f}"
        source "${f}"

        # find all keys in the dot-env file and store them in our temp associative array
        for k in "$(grep -v '^#' "${f}" | sed -E 's/(.*)=.*/\1/' | xargs)"; do
            _tmp_dot_env_keys[$k]=1
        done
    done

    seen_dot_env_variables=(${!_tmp_dot_env_keys[@]})
}
