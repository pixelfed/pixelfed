#!/bin/bash
set -e -o errexit -o nounset -o pipefail

# Some splash of color for important messages
declare -g error_message_color="\033[1;31m"
declare -g warn_message_color="\033[1;34m"
declare -g color_clear="\033[1;0m"

# Current and previous log prefix
declare -g log_prefix=
declare -g log_prefix_previous=

# dot-env files to source when reading config
declare -ra dot_env_files=(
    /var/www/.env.docker
    /var/www/.env
)

# environment keys seen when source dot files (so we can [export] them)
declare -ga seen_dot_env_variables=()

function entrypoint-set-name() {
    log_prefix_previous="${log_prefix}"
    log_prefix="ENTRYPOINT - [$(get-entrypoint-script-name $1)] - "
}

function entrypoint-restore-name() {
    log_prefix="${log_prefix_previous}"
}

function run-as-runtime-user() {
    local -i exit_code
    local target_user

    target_user=$(id -un ${RUNTIME_UID})

    log-info "👷 Running [${*}] as [${target_user}]"

    su --preserve-environment "${target_user}" --shell /bin/bash --command "${*}"
    exit_code=$?

    if [[ $exit_code != 0 ]]; then
        log-error "❌ Error!"
        return $exit_code
    fi

    log-info "✅ OK!"
    return $exit_code
}

# @description Print the given error message to stderr
# @arg $message string A error message.
function log-error() {
    echo -e "${error_message_color}${log_prefix}ERROR - ${*}${color_clear}" >/dev/stderr
}

# @description Print the given error message to stderr and exit 1
# @arg $@ string A error message.
# @exitcode 1
function log-error-and-exit() {
    log-error "$@"

    exit 1
}

# @description Print the given warning message to stderr
# @arg $@ string A warning message.
function log-warning() {
    echo -e "${warn_message_color}${log_prefix}WARNING - ${*}${color_clear}" >/dev/stderr
}

# @description Print the given message to stderr unless [ENTRYPOINT_QUIET_LOGS] is set
# @arg $@ string A warning message.
function log-info() {
    if [ -z "${ENTRYPOINT_QUIET_LOGS:-}" ]; then
        echo "${log_prefix}$*"
    fi
}

function load-config-files() {
    # Associative array (aka map/dictionary) holding the unique keys found in dot-env files
    local -A _tmp_dot_env_keys

    for f in "${dot_env_files[@]}"; do
        if [ ! -e "$f" ]; then
            log-warning "Could not source file [${f}]: does not exists"
            continue
        fi

        log-info "Sourcing ${f}"
        source "${f}"

        # find all keys in the dot-env file and store them in our temp associative array
        for k in "$(grep -v '^#' "${f}" | sed -E 's/(.*)=.*/\1/' | xargs)"; do
            _tmp_dot_env_keys[$k]=1
        done
    done

    seen_dot_env_variables=(${!_tmp_dot_env_keys[@]})
}

function in-array() {
    local -r needle="\<${1}\>"
    local -nr haystack=$2

    [[ ${haystack[*]} =~ $needle ]]
}

function is-executable() {
    [[ -x "$1" ]]
}

function get-entrypoint-script-name() {
    echo "${1#"$ENTRYPOINT_ROOT"}"
}
