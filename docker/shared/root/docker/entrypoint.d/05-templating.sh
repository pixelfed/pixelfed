#!/bin/bash
: "${ENTRYPOINT_ROOT:="/docker"}"

# shellcheck source=SCRIPTDIR/../helpers.sh
source "${ENTRYPOINT_ROOT}/helpers.sh"

entrypoint-set-script-name "$0"

# Show [git diff] of templates being rendered (will help verify output)
: "${ENTRYPOINT_SHOW_TEMPLATE_DIFF:=1}"
# Directory where templates can be found
: "${ENTRYPOINT_TEMPLATE_DIR:=/docker/templates/}"
# Root path to write template template_files to (default is '', meaning it will be written to /<path>)
: "${ENTRYPOINT_TEMPLATE_OUTPUT_PREFIX:=}"

declare template_file relative_template_file_path output_file_dir

# load all dot-env config files
load-and-export-config-files


find "${ENTRYPOINT_TEMPLATE_DIR}" -follow -type f -print | while read -r template_file; do
    # Example: template_file=/docker/templates/usr/local/etc/php/php.ini

    # The file path without the template dir prefix ($ENTRYPOINT_TEMPLATE_DIR)
    #
    # Example: /usr/local/etc/php/php.ini
    relative_template_file_path="${template_file#"${ENTRYPOINT_TEMPLATE_DIR}"}"

    # Adds optional prefix to the output file path
    #
    # Example: /usr/local/etc/php/php.ini
    output_file_path="${ENTRYPOINT_TEMPLATE_OUTPUT_PREFIX}/${relative_template_file_path}"

    # Remove the file from the path
    #
    # Example: /usr/local/etc/php
    output_file_dir=$(dirname "${output_file_path}")

    # Ensure the output directory is writable
    if ! is-writable "${output_file_dir}"; then
        log-error-and-exit "${output_file_dir} is not writable"
    fi

    # Create the output directory if it doesn't exists
    ensure-directory-exists "${output_file_dir}"

    # Render the template
    log-info "Running [gomplate] on [${template_file}] --> [${output_file_path}]"
    gomplate <"${template_file}" >"${output_file_path}"

    # Show the diff from the envsubst command
    if is-true "${ENTRYPOINT_SHOW_TEMPLATE_DIFF}"; then
        git --no-pager diff --color=always "${template_file}" "${output_file_path}" || : # ignore diff exit code
    fi
done
