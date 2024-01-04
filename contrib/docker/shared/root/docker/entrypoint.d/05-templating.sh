#!/bin/bash
source /docker/helpers.sh

set_identity "$0"

declare template_dir="${ENVSUBST_TEMPLATE_DIR:-/docker/templates}"
declare output_dir="${ENVSUBST_OUTPUT_DIR:-}"
declare filter="${ENVSUBST_FILTER:-}"
declare template defined_envs relative_path output_path output_dir subdir

# load all dot-env files
load-config-files

: ${ENTRYPOINT_SHOW_TEMPLATE_DIFF:=1}

# export all dot-env variables so they are available in templating
export ${seen_dot_env_variables[@]}

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

    log "Running [gomplate] on [$template] --> [$output_path]"
    cat "$template" | gomplate >"$output_path"

    # Show the diff from the envsubst command
    if [[ ${ENTRYPOINT_SHOW_TEMPLATE_DIFF} = 1 ]]; then
        git --no-pager diff "$template" "${output_path}" || :
    fi
done
