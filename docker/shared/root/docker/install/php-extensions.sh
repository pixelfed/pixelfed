#!/bin/bash
set -ex -o errexit -o nounset -o pipefail

declare -a pecl_extensions=()

readarray -d ' ' -t pecl_extensions < <(echo -n "${PHP_PECL_EXTENSIONS:-}")
readarray -d ' ' -t -O "${#pecl_extensions[@]}" pecl_extensions < <(echo -n "${PHP_PECL_EXTENSIONS_EXTRA:-}")

declare -a php_extensions=()
readarray -d ' ' -t php_extensions < <(echo -n "${PHP_EXTENSIONS:-}")
readarray -d ' ' -t -O "${#php_extensions[@]}" php_extensions < <(echo -n "${PHP_EXTENSIONS_EXTRA:-}")
readarray -d ' ' -t -O "${#php_extensions[@]}" php_extensions < <(echo -n "${PHP_EXTENSIONS_DATABASE:-}")

# Optional script folks can copy into their image to do any [docker-php-ext-configure] work before the [docker-php-ext-install]
# this can also overwirte the [gd] configure above by simply running it again
declare -r custom_pre_configure_script=""
if [[ -e "${custom_pre_configure_script}" ]]; then
    if [ ! -x "${custom_pre_configure_script}" ]; then
        echo >&2 "ERROR: found ${custom_pre_configure_script} but its not executable - please [chmod +x] the file!"
        exit 1
    fi

    "${custom_pre_configure_script}"
fi

# PECL + PHP extensions
IPE_KEEP_SYSPKG_CACHE=1 install-php-extensions "${pecl_extensions[@]}" "${php_extensions[@]}"
