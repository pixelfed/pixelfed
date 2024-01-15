#!/bin/bash
set -ex -o errexit -o nounset -o pipefail

: "${PHP_PECL_EXTENSIONS:=""}"
: "${PHP_PECL_EXTENSIONS_EXTRA:=""}"
: "${PHP_EXTENSIONS:=""}"
: "${PHP_EXTENSIONS_EXTRA:=""}"
: "${PHP_EXTENSIONS_DATABASE:=""}"

# Grab the PHP source code so we can compile against it
docker-php-source extract

# PHP GD extensions
docker-php-ext-configure gd \
    --with-freetype \
    --with-jpeg \
    --with-webp \
    --with-xpm

# Optional script folks can copy into their image to do any [docker-php-ext-configure] work before the [docker-php-ext-install]
# this can also overwirte the [gd] configure above by simply running it again
if [[ -f /install/php-extension-configure.sh ]]; then
    if [ ! -x "/install/php-extension-configure.sh" ]; then
        echo >&2 "ERROR: found /install/php-extension-configure.sh but its not executable - please [chmod +x] the file!"
        exit 1
    fi

    /install/php-extension-configure.sh
fi

# Install pecl extensions
pecl install "${PHP_PECL_EXTENSIONS}" "${PHP_PECL_EXTENSIONS_EXTRA}"

# PHP extensions (dependencies)
#
# shellcheck disable=SC2086
docker-php-ext-install \
    -j "$(nproc)" \
    ${PHP_EXTENSIONS} \
    ${PHP_EXTENSIONS_EXTRA} \
    ${PHP_EXTENSIONS_DATABASE}

# Enable all extensions
#
# shellcheck disable=SC2086
docker-php-ext-enable \
    ${PHP_PECL_EXTENSIONS} \
    ${PHP_PECL_EXTENSIONS_EXTRA} \
    ${PHP_EXTENSIONS} \
    ${PHP_EXTENSIONS_EXTRA} \
    ${PHP_EXTENSIONS_DATABASE}
