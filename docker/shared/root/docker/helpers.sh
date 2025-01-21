#!/bin/bash
set -e -o errexit -o nounset -o pipefail

[[ ${DOCKER_APP_ENTRYPOINT_DEBUG:=0} == 1 ]] && set -x

: "${RUNTIME_UID:="33"}"
: "${RUNTIME_GID:="33"}"

# Some splash of color for important messages
declare -g error_message_color="\033[1;31m"
declare -g warn_message_color="\033[1;33m"
declare -g notice_message_color="\033[1;34m"
declare -g success_message_color="\033[1;32m"
# shellcheck disable=SC2034
declare -g section_message_color="\033[1;35m"
declare -g color_clear="\033[1;0m"

# Current and previous log prefix
declare -g script_name=
declare -g script_name_previous=
declare -g log_prefix=

declare -Ag lock_fds=()

# dot-env files to source when reading config
declare -a dot_env_files=(
    /var/www/.env
)

declare -g docker_state_path
docker_state_path="$(readlink -f ./storage/docker)"

declare -g docker_locks_path="${docker_state_path}/lock"
declare -g docker_once_path="${docker_state_path}/once"

declare -g runtime_username
runtime_username=$(id -un "${RUNTIME_UID}")

# We should already be in /var/www, but just to be explicit
cd /var/www || log-error-and-exit "could not change to /var/www"

# @description Restore the log prefix to the previous value that was captured in [entrypoint-set-script-name ]
# @arg $1 string The name (or path) of the entrypoint script being run
function entrypoint-set-script-name()
{
    script_name_previous="${script_name}"
    script_name="${1}"

    log_prefix="[entrypoint / $(get-entrypoint-script-name "$1")] - "
}

# @description Restore the log prefix to the previous value that was captured in [entrypoint-set-script-name ]
function entrypoint-restore-script-name()
{
    entrypoint-set-script-name "${script_name_previous}"
}

# @description Run a command as the [runtime user]
# @arg $@ string The command to run
# @exitcode 0 if the command succeeeds
# @exitcode 1 if the command fails
function run-as-runtime-user()
{
    run-command-as "${runtime_username}" "${@}"
}

# @description Run a command as the [runtime user]
# @arg $@ string The command to run
# @exitcode 0 if the command succeeeds
# @exitcode 1 if the command fails
function run-as-current-user()
{
    run-command-as "$(id -un)" "${@}"
}

# @description Run a command as the a named user
# @arg $1 string The user to run the command as
# @arg $@ string The command to run
# @exitcode 0 If the command succeeeds
# @exitcode 1 If the command fails
function run-command-as()
{
    local -i exit_code
    local target_user

    target_user=${1}
    shift

    log-info-stderr "${notice_message_color}👷 Running [${*}] as [${target_user}]${color_clear}"

    # disable error on exit behavior temporarily while we run the command
    set +e

    if [[ ${target_user} != "root" ]]; then
        stream-prefix-command-output su --preserve-environment "${target_user}" --shell /bin/bash --command "${*}"
    else
        stream-prefix-command-output "${@}"
    fi

    # capture exit code
    exit_code=$?

    # re-enable exit code handling
    set -e

    if [[ $exit_code != 0 ]]; then
        log-error "${error_message_color}❌ Error!${color_clear}"

        return "$exit_code"
    fi

    log-info-stderr "${success_message_color}✅ OK!${color_clear}"

    return "$exit_code"
}

# @description Streams stdout from the command and echo it
# with log prefixing.
# @see stream-prefix-command-output
function stream-stdout-handler()
{
    while read -r line; do
        log-info "(stdout) ${line}"
    done
}

# @description Streams stderr from the command and echo it
# with a bit of color and log prefixing.
# @see stream-prefix-command-output
function stream-stderr-handler()
{
    while read -r line; do
        log-info-stderr "(${error_message_color}stderr${color_clear}) ${line}"
    done
}

# @description Steam stdout and stderr from a command with log prefix
# and stdout/stderr prefix. If stdout or stderr is being piped/redirected
# it will automatically fall back to non-prefixed output.
# @arg $@ string The command to run
function stream-prefix-command-output()
{
    local stdout=stream-stdout-handler
    local stderr=stream-stderr-handler

    # if stdout is being piped, print it like normal with echo
    if [ ! -t 1 ]; then
        # shellcheck disable=SC1007
        stdout= echo >&1 -ne
    fi

    # if stderr is being piped, print it like normal with echo
    if [ ! -t 2 ]; then
        # shellcheck disable=SC1007
        stderr= echo >&2 -ne
    fi

    "$@" > >($stdout) 2> >($stderr)
}

# @description Print the given error message to stderr
# @arg $message string A error message.
# @stderr The error message provided with log prefix
function log-error()
{
    local msg

    if [[ $# -gt 0 ]]; then
        msg="$*"
    elif [[ ! -t 0 ]]; then
        read -r msg || log-error-and-exit "[${FUNCNAME[0]}] could not read from stdin"
    else
        log-error-and-exit "[${FUNCNAME[0]}] did not receive any input arguments and STDIN is empty"
    fi

    echo -e "${error_message_color}${log_prefix}ERROR -${color_clear} ${msg}" >/dev/stderr
}

# @description Print the given error message to stderr and exit 1
# @arg $@ string A error message.
# @stderr The error message provided with log prefix
# @exitcode 1
function log-error-and-exit()
{
    log-error "$@"

    show-call-stack

    exit 1
}

# @description Print the given warning message to stderr
# @arg $@ string A warning message.
# @stderr The warning message provided with log prefix
function log-warning()
{
    local msg

    if [[ $# -gt 0 ]]; then
        msg="$*"
    elif [[ ! -t 0 ]]; then
        read -r msg || log-error-and-exit "[${FUNCNAME[0]}] could not read from stdin"
    else
        log-error-and-exit "[${FUNCNAME[0]}] did not receive any input arguments and STDIN is empty"
    fi

    echo -e "${warn_message_color}${log_prefix}WARNING -${color_clear} ${msg}" >/dev/stderr
}

# @description Print the given message to stdout unless [ENTRYPOINT_QUIET_LOGS] is set
# @arg $@ string A info message.
# @stdout The info message provided with log prefix unless $ENTRYPOINT_QUIET_LOGS
function log-info()
{
    local msg

    if [[ $# -gt 0 ]]; then
        msg="$*"
    elif [[ ! -t 0 ]]; then
        read -r msg || log-error-and-exit "[${FUNCNAME[0]}] could not read from stdin"
    else
        log-error-and-exit "[${FUNCNAME[0]}] did not receive any input arguments and STDIN is empty"
    fi

    if [ -z "${ENTRYPOINT_QUIET_LOGS:-}" ]; then
        echo -e "${notice_message_color}${log_prefix}${color_clear}${msg}"
    fi
}

# @description Print the given message to stderr unless [ENTRYPOINT_QUIET_LOGS] is set
# @arg $@ string A info message.
# @stderr The info message provided with log prefix unless $ENTRYPOINT_QUIET_LOGS
function log-info-stderr()
{
    local msg

    if [[ $# -gt 0 ]]; then
        msg="$*"
    elif [[ ! -t 0 ]]; then
        read -r msg || log-error-and-exit "[${FUNCNAME[0]}] could not read from stdin"
    else
        log-error-and-exit "[${FUNCNAME[0]}] did not receive any input arguments and STDIN is empty"
    fi

    if [ -z "${ENTRYPOINT_QUIET_LOGS:-}" ]; then
        echo -e "${notice_message_color}${log_prefix}${color_clear}${msg}" >/dev/stderr
    fi
}

# @description Loads the dot-env files used by Docker
function load-config-files() {
    local export_vars=0
    load-config-files-impl "$export_vars"
}

# @description Loads the dot-env files used by Docker and exports the variables to subshells
function load-and-export-config-files() {
    local export_vars=1
    load-config-files-impl "$export_vars"
}

# @description Implementation of the [load-config-files] and [load-and-export-config-files] functions. Loads th
# @arg $1 int Whether to export the variables or just have them available in the current shell
function load-config-files-impl()
{
    local export_vars=${1:-0}
    for file in "${dot_env_files[@]}"; do
        if ! file-exists "${file}"; then
            log-warning "Could not source file [${file}]: does not exists"
            continue
        fi

        log-info "Sourcing ${file}"
        if ((export_vars)); then set -o allexport; fi
        # shellcheck disable=SC1090
        source "${file}"
        if ((export_vars)); then set +o allexport; fi
    done
}

# @description Checks if $needle exists in $haystack
# @arg $1 string The needle (value) to search for
# @arg $2 array  The haystack (array) to search in
# @exitcode 0 If $needle was found in $haystack
# @exitcode 1 If $needle was *NOT* found in $haystack
function in-array()
{
    local -r needle="\<${1}\>"
    local -nr haystack=$2

    [[ ${haystack[*]} =~ $needle ]]
}

# @description Checks if $1 has executable bit set or not
# @arg $1 string The path to check
# @exitcode 0 If $1 has executable bit
# @exitcode 1 If $1 does *NOT* have executable bit
function is-executable()
{
    [[ -x "$1" ]]
}

# @description Checks if $1 is writable or not
# @arg $1 string The path to check
# @exitcode 0 If $1 is writable
# @exitcode 1 If $1 is *NOT* writable
function is-writable()
{
    [[ -w "$1" ]]
}

# @description Checks if $1 exists (directory or file)
# @arg $1 string The path to check
# @exitcode 0 If $1 exists
# @exitcode 1 If $1 does *NOT* exists
function path-exists()
{
    [[ -e "$1" ]]
}

# @description Checks if $1 exists (file only)
# @arg $1 string The path to check
# @exitcode 0 If $1 exists
# @exitcode 1 If $1 does *NOT* exists
function file-exists()
{
    [[ -f "$1" ]]
}

# @description Checks if $1 contains any files or not
# @arg $1 string The path to check
# @exitcode 0 If $1 contains files
# @exitcode 1 If $1 does *NOT* contain files
function directory-is-empty()
{
    ! path-exists "${1}" || [[ -z "$(ls -A "${1}")" ]]
}

# @description Ensures a directory exists (via mkdir)
# @arg $1 string The path to create
# @exitcode 0 If $1 If the path exists *or* was created
# @exitcode 1 If $1 If the path does *NOT* exists and could *NOT* be created
function ensure-directory-exists()
{
    stream-prefix-command-output mkdir -pv "$@"
}

# @description Find the relative path for a entrypoint script by removing the ENTRYPOINT_D_ROOT prefix
# @arg $1 string The path to manipulate
# @stdout The relative path to the entrypoint script
function get-entrypoint-script-name()
{
    echo "${1#"$ENTRYPOINT_D_ROOT"}"
}

# @description Ensure a command is only run once (via a 'lock' file) in the storage directory.
#   The 'lock' is only written if the passed in command ($2) successfully ran.
# @arg $1 string The name of the lock file
# @arg $@ string The command to run
function only-once()
{
    local name="${1:-$script_name}"
    local file="${docker_once_path}/${name}"
    shift

    if [[ -e "${file}" ]]; then
        log-info "Command [${*}] has already run once before (remove file [${file}] to run it again)"

        return 0
    fi

    ensure-directory-exists "$(dirname "${file}")"

    if ! "$@"; then
        return 1
    fi

    stream-prefix-command-output touch "${file}"
    return 0
}

# @description Best effort file lock to ensure *something* is not running in multiple containers.
#   The script uses "trap" to clean up after itself if the script crashes
# @arg $1 string The lock identifier
function acquire-lock()
{
    local name="${1:-$script_name}"
    local file="${docker_locks_path}/${name}"
    local lock_fd

    ensure-directory-exists "$(dirname "${file}")"

    exec {lock_fd}>"$file"

    log-info "🔑 Trying to acquire lock: ${file}: "
    while ! ([[ -v lock_fds[$name] ]] || flock -n -x "$lock_fd"); do
        log-info "🔒 Waiting on lock ${file}"

        staggered-sleep
    done

    [[ -v lock_fds[$name] ]] || lock_fds[$name]=$lock_fd

    log-info "🔐 Lock acquired [${file}]"

    on-trap "release-lock ${name}" EXIT INT QUIT TERM
}

# @description Release a lock aquired by [acquire-lock]
# @arg $1 string The lock identifier
function release-lock()
{
    local name="${1:-$script_name}"
    local file="${docker_locks_path}/${name}"

    log-info "🔓 Releasing lock [${file}]"

    [[ -v lock_fds[$name] ]] || return

    # shellcheck disable=SC1083,SC2086
    flock --unlock ${lock_fds[$name]}
    unset 'lock_fds[$name]'
}

# @description Helper function to append multiple actions onto
#   the bash [trap] logic
# @arg $1 string The command to run
# @arg $@ string The list of trap signals to register
function on-trap()
{
    local trap_add_cmd=$1
    shift || log-error-and-exit "${FUNCNAME[0]} usage error"

    for trap_add_name in "$@"; do
        trap -- "$(
            # helper fn to get existing trap command from output
            # of trap -p
            #
            # shellcheck disable=SC2317
            extract_trap_cmd()
            {
                printf '%s\n' "${3:-}"
            }
            # print existing trap command with newline
            eval "extract_trap_cmd $(trap -p "${trap_add_name}")"
            # print the new trap command
            printf '%s\n' "${trap_add_cmd}"
        )" "${trap_add_name}" \
            || log-error-and-exit "unable to add to trap ${trap_add_name}"
    done
}

# Set the trace attribute for the above function.
#
# This is required to modify DEBUG or RETURN traps because functions don't
# inherit them unless the trace attribute is set
declare -f -t on-trap

# @description Waits for the database to be healthy and responsive
function await-database-ready()
{
    log-info "❓ Waiting for database to be ready"

    load-config-files

    case "${DB_CONNECTION:-}" in
        mysql)
            # shellcheck disable=SC2154
            while ! echo "SELECT 1" | mysql --user="${DB_USERNAME}" --password="${DB_PASSWORD}" --host="${DB_HOST}" --port="${DOCKER_DB_HOST_PORT}" "${DB_DATABASE}" --silent >/dev/null; do
                staggered-sleep
            done
            ;;

        pgsql)
            # shellcheck disable=SC2154
            while ! echo "SELECT 1" | PGPASSWORD="${DB_PASSWORD}" psql --user="${DB_USERNAME}" --host="${DB_HOST}" --port="${DOCKER_DB_HOST_PORT}" "${DB_DATABASE}" >/dev/null; do
                staggered-sleep
            done
            ;;

        sqlsrv)
            log-warning "Don't know how to check if SQLServer is *truely* ready or not - so will just check if we're able to connect to it"

            # shellcheck disable=SC2154
            while ! timeout 1 bash -c "cat < /dev/null > /dev/tcp/${DB_HOST}/${DB_PORT}"; do
                staggered-sleep
            done
            ;;

        sqlite)
            log-info "${success_message_color}sqlite is always ready${color_clear}"
            ;;

        *)
            log-error-and-exit "Unknown database type: [${DB_CONNECTION:-}]"
            ;;
    esac

    log-info "${success_message_color}✅ Successfully connected to database${color_clear}"
}

# @description sleeps between 1 and 3 seconds to ensure a bit of randomness
#   in multiple scripts/containers doing work almost at the same time.
function staggered-sleep()
{
    sleep "$(get-random-number-between 1 3)"
}

# @description Helper function to get a random number between $1 and $2
# @arg $1 int Minimum number in the range (inclusive)
# @arg $2 int Maximum number in the range (inclusive)
function get-random-number-between()
{
    local -i from=${1:-1}
    local -i to="${2:-10}"

    shuf -i "${from}-${to}" -n 1
}

# @description Helper function to show the bask call stack when something
#   goes wrong. Is super useful when needing to debug an issue
function show-call-stack()
{
    local stack_size=${#FUNCNAME[@]}
    local func
    local lineno
    local src

    # to avoid noise we start with 1 to skip the get_stack function
    for ((i = 1; i < stack_size; i++)); do
        func="${FUNCNAME[$i]}"
        [ -z "$func" ] && func="MAIN"

        lineno="${BASH_LINENO[$((i - 1))]}"
        src="${BASH_SOURCE[$i]}"
        [ -z "$src" ] && src="non_file_source"

        log-error "  at: ${func} ${src}:${lineno}"
    done
}

# @description Helper function see if $1 could be considered truthy
#   returns [0] if input is truthy, otherwise [1]
# @arg $1 string The string to evaluate
# @see as-boolean
function is-true()
{
    as-boolean "${1:-}" && return 0

    return 1
}

# @description Helper function see if $1 could be considered falsey
#   returns [0] if input is falsey, otherwise [1]
# @arg $1 string The string to evaluate
# @see as-boolean
function is-false()
{
    as-boolean "${1:-}" && return 1

    return 0
}

# @description Helper function see if $1 could be truethy or falsey.
#   since this is a bash context, returning 0 is true and 1 is false
#   so it works with [if is-false $input; then .... fi]
#
#   This is a bit confusing, *especially* in a PHP world where [1] would be truthy and
#   [0] would be falsely as return values
# @arg $1 string The string to evaluate
function as-boolean()
{
    local input="${1:-}"
    local var="${input,,}" # convert input to lower-case

    case "$var" in
        1 | true)
            return 0
            ;;

        0 | false)
            return 1
            ;;

        *)
            log-warning "[as-boolean] variable [${var}] could not be detected as true or false, returning [1] (false) as default"

            return 1
            ;;

    esac
}
