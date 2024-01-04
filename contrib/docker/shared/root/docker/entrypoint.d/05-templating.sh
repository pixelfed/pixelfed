#!/bin/bash
source /docker/helpers.sh

set_identity "$0"

auto_envsubst() {
    local template_dir="${ENVSUBST_TEMPLATE_DIR:-/docker/templates}"
    local output_dir="${ENVSUBST_OUTPUT_DIR:-}"
    local filter="${ENVSUBST_FILTER:-}"
    local template defined_envs relative_path output_path output_dir subdir

    # load all dot-env files
    load-config-files

    # export all dot-env variables so they are available in templating
    export ${seen_dot_env_variables[@]}

    defined_envs=$(printf '${%s} ' $(awk "END { for (name in ENVIRON) { print ( name ~ /${filter}/ ) ? name : \"\" } }" </dev/null))

    find "$template_dir" -follow -type f -print | while read -r template; do
        relative_path="${template#"$template_dir/"}"
        subdir=$(dirname "$relative_path")
        output_path="$output_dir/${relative_path}"
        output_dir=$(dirname "$output_path")

        if [ ! -w "$output_dir" ]; then
            log_error_and_exit "ERROR: $template_dir exists, but $output_dir is not writable"
        fi

        # create a subdirectory where the template file exists
        mkdir -p "$output_dir/$subdir"

        log "Running envsubst on $template to $output_path"
        envsubst "$defined_envs" <"$template" >"$output_path"
    done
}

auto_envsubst

exit 0
