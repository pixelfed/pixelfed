#!/bin/bash
set -e -o errexit -o nounset -o pipefail

[[ ${ENTRYPOINT_DEBUG:=0} == 1 ]] && set -x

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

# @description Restore the log prefix to the previous value that was captured in [entrypoint-set-script-name ]
# @arg $1 string The name (or path) of the entrypoint script being run
function entrypoint-set-script-name() {
    log_prefix_previous="${log_prefix}"
    log_prefix="ENTRYPOINT - [$(get-entrypoint-script-name $1)] - "
}

# @description Restore the log prefix to the previous value that was captured in [entrypoint-set-script-name ]
function entrypoint-restore-script-name() {
    log_prefix="${log_prefix_previous}"
}

# @description Run a command as the [runtime user]
# @arg $@ string The command to run
# @exitcode 0 if the command succeeeds
# @exitcode 1 if the command fails
function run-as-runtime-user() {
    run-command-as "$(id -un ${RUNTIME_UID})" "${@}"
}

# @description Run a command as the [runtime user]
# @arg $@ string The command to run
# @exitcode 0 if the command succeeeds
# @exitcode 1 if the command fails
function run-as-current-user() {
    run-command-as "$(id -un)" "${@}"
}

# @description Run a command as the a named user
# @arg $1 string The user to run the command as
# @arg $@ string The command to run
# @exitcode 0 If the command succeeeds
# @exitcode 1 If the command fails
function run-command-as() {
    local -i exit_code
    local target_user

    target_user=${1}
    shift

    log-info-stderr "👷 Running [${*}] as [${target_user}]"

    if [[ ${target_user} != "root" ]]; then
        su --preserve-environment "${target_user}" --shell /bin/bash --command "${*}"
    else
        "${@}"
    fi

    exit_code=$?

    if [[ $exit_code != 0 ]]; then
        log-error "❌ Error!"
        return $exit_code
    fi

    log-info-stderr "✅ OK!"
    return $exit_code
}

# @description Print the given error message to stderr
# @arg $message string A error message.
# @stderr The error message provided with log prefix
function log-error() {
    echo -e "${error_message_color}${log_prefix}ERROR - ${*}${color_clear}" >/dev/stderr
}

# @description Print the given error message to stderr and exit 1
# @arg $@ string A error message.
# @stderr The error message provided with log prefix
# @exitcode 1
function log-error-and-exit() {
    log-error "$@"

    exit 1
}

# @description Print the given warning message to stderr
# @arg $@ string A warning message.
# @stderr The warning message provided with log prefix
function log-warning() {
    echo -e "${warn_message_color}${log_prefix}WARNING - ${*}${color_clear}" >/dev/stderr
}

# @description Print the given message to stdout unless [ENTRYPOINT_QUIET_LOGS] is set
# @arg $@ string A info message.
# @stdout The info message provided with log prefix unless $ENTRYPOINT_QUIET_LOGS
function log-info() {
    if [ -z "${ENTRYPOINT_QUIET_LOGS:-}" ]; then
        echo "${log_prefix}$*"
    fi
}

# @description Print the given message to stderr unless [ENTRYPOINT_QUIET_LOGS] is set
# @arg $@ string A info message.
# @stderr The info message provided with log prefix unless $ENTRYPOINT_QUIET_LOGS
function log-info-stderr() {
    if [ -z "${ENTRYPOINT_QUIET_LOGS:-}" ]; then
        echo "${log_prefix}$*"
    fi
}

# @description Loads the dot-env files used by Docker and track the keys present in the configuration.
# @sets seen_dot_env_variables array List of config keys discovered during loading
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

# @description Checks if $needle exists in $haystack
# @arg $1 string The needle (value) to search for
# @arg $2 array  The haystack (array) to search in
# @exitcode 0 If $needle was found in $haystack
# @exitcode 1 If $needle was *NOT* found in $haystack
function in-array() {
    local -r needle="\<${1}\>"
    local -nr haystack=$2

    [[ ${haystack[*]} =~ $needle ]]
}

# @description Checks if $1 has executable bit set or not
# @arg $1 string The path to check
# @exitcode 0 If $1 has executable bit
# @exitcode 1 If $1 does *NOT* have executable bit
function is-executable() {
    [[ -x "$1" ]]
}

# @description Checks if $1 is writable or not
# @arg $1 string The path to check
# @exitcode 0 If $1 is writable
# @exitcode 1 If $1 is *NOT* writable
function is-writable() {
    [[ -w "$1" ]]
}

# @description Checks if $1 contains any files or not
# @arg $1 string The path to check
# @exitcode 0 If $1 contains files
# @exitcode 1 If $1 does *NOT* contain files
function is-directory-empty() {
    ! find "${1}" -mindepth 1 -maxdepth 1 -type f -print -quit 2>/dev/null | read v
}

# @description Ensures a directory exists (via mkdir)
# @arg $1 string The path to create
# @exitcode 0 If $1 If the path exists *or* was created
# @exitcode 1 If $1 If the path does *NOT* exists and could *NOT* be created
function ensure-directory-exists() {
    mkdir -pv "$@"
}

# @description Find the relative path for a entrypoint script by removing the ENTRYPOINT_ROOT prefix
# @arg $1 string The path to manipulate
# @stdout The relative path to the entrypoint script
function get-entrypoint-script-name() {
    echo "${1#"$ENTRYPOINT_ROOT"}"
}
