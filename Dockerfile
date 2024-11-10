# syntax=docker/dockerfile:1
# See https://hub.docker.com/r/docker/dockerfile

#######################################################
# Configuration
#######################################################

# See: https://github.com/mlocati/docker-php-extension-installer
ARG DOCKER_PHP_EXTENSION_INSTALLER_VERSION="2.1.80"

# See: https://github.com/composer/composer
ARG COMPOSER_VERSION="2.6"

# See: https://nginx.org/
ARG NGINX_VERSION="1.25.3"

# See: https://github.com/ddollar/forego
ARG FOREGO_VERSION="0.17.2"

# See: https://github.com/hairyhenderson/gomplate
ARG GOMPLATE_VERSION="v3.11.6"

# See: https://github.com/jippi/dottie
ARG DOTTIE_VERSION="v0.9.5"

###
# PHP base configuration
###

# See: https://hub.docker.com/_/php/tags
ARG PHP_VERSION="8.1"

# See: https://github.com/docker-library/docs/blob/master/php/README.md#image-variants
ARG PHP_BASE_TYPE="apache"
ARG PHP_DEBIAN_RELEASE="bullseye"

ARG RUNTIME_UID=33 # often called 'www-data'
ARG RUNTIME_GID=33 # often called 'www-data'

# APT extra packages
ARG APT_PACKAGES_EXTRA=

# Extensions installed via [pecl install]
# ! NOTE: imagick is installed from [master] branch on GitHub due to 8.3 bug on ARM that haven't
# ! been released yet (after +10 months)!
# ! See: https://github.com/Imagick/imagick/pull/641
ARG PHP_PECL_EXTENSIONS="redis https://codeload.github.com/Imagick/imagick/tar.gz/28f27044e435a2b203e32675e942eb8de620ee58"
ARG PHP_PECL_EXTENSIONS_EXTRA=

# Extensions installed via [docker-php-ext-install]
ARG PHP_EXTENSIONS="intl bcmath zip pcntl exif curl gd"
ARG PHP_EXTENSIONS_EXTRA=""
ARG PHP_EXTENSIONS_DATABASE="pdo_pgsql pdo_mysql pdo_sqlite"

# GPG key for nginx apt repository
ARG NGINX_GPGKEY="573BFD6B3D8FBC641079A6ABABF5BD827BD9BF62"

# GPP key path for nginx apt repository
ARG NGINX_GPGKEY_PATH="/usr/share/keyrings/nginx-archive-keyring.gpg"

#######################################################
# Docker "copy from" images
#######################################################

# Composer docker image from Docker Hub
#
# NOTE: Docker will *not* pull this image unless it's referenced (via build target)
FROM composer:${COMPOSER_VERSION} AS composer-image

# php-extension-installer image from Docker Hub
#
# NOTE: Docker will *not* pull this image unless it's referenced (via build target)
FROM mlocati/php-extension-installer:${DOCKER_PHP_EXTENSION_INSTALLER_VERSION} AS php-extension-installer

# nginx webserver from Docker Hub.
# Used to copy some docker-entrypoint files for [nginx-runtime]
#
# NOTE: Docker will *not* pull this image unless it's referenced (via build target)
FROM nginx:${NGINX_VERSION} AS nginx-image

# Forego is a Procfile "runner" that makes it trival to run multiple
# processes under a simple init / PID 1 process.
#
# NOTE: Docker will *not* pull this image unless it's referenced (via build target)
#
# See: https://github.com/nginx-proxy/forego
FROM nginxproxy/forego:${FOREGO_VERSION}-debian AS forego-image

# Dottie makes working with .env files easier and safer
#
# NOTE: Docker will *not* pull this image unless it's referenced (via build target)
#
# See: https://github.com/jippi/dottie
FROM ghcr.io/jippi/dottie:${DOTTIE_VERSION} AS dottie-image

# gomplate-image grabs the gomplate binary from GitHub releases
#
# It's in its own layer so it can be fetched in parallel with other build steps
FROM php:${PHP_VERSION}-${PHP_BASE_TYPE}-${PHP_DEBIAN_RELEASE} AS gomplate-image

ARG TARGETARCH
ARG TARGETOS
ARG GOMPLATE_VERSION

RUN set -ex \
    && curl \
        --silent \
        --show-error \
        --location \
        --output /usr/local/bin/gomplate \
        https://github.com/hairyhenderson/gomplate/releases/download/${GOMPLATE_VERSION}/gomplate_${TARGETOS}-${TARGETARCH} \
    && chmod +x /usr/local/bin/gomplate \
    && /usr/local/bin/gomplate --version

#######################################################
# Base image
#######################################################

FROM php:${PHP_VERSION}-${PHP_BASE_TYPE}-${PHP_DEBIAN_RELEASE} AS base

ARG BUILDKIT_SBOM_SCAN_STAGE="true"

ARG APT_PACKAGES_EXTRA
ARG PHP_DEBIAN_RELEASE
ARG PHP_VERSION
ARG RUNTIME_GID
ARG RUNTIME_UID
ARG TARGETPLATFORM

ENV DEBIAN_FRONTEND="noninteractive"

# Ensure we run all scripts through 'bash' rather than 'sh'
SHELL ["/bin/bash", "-c"]

# Set www-data to be RUNTIME_UID/RUNTIME_GID
RUN groupmod --gid ${RUNTIME_GID} www-data \
    && usermod --uid ${RUNTIME_UID} --gid ${RUNTIME_GID} www-data

RUN set -ex \
    && mkdir -pv /var/www/ \
    && chown -R ${RUNTIME_UID}:${RUNTIME_GID} /var/www

WORKDIR /var/www/

ENV APT_PACKAGES_EXTRA=${APT_PACKAGES_EXTRA}

# Install and configure base layer
COPY docker/shared/root/docker/install/base.sh /docker/install/base.sh

RUN --mount=type=cache,id=pixelfed-apt-${PHP_VERSION}-${PHP_DEBIAN_RELEASE}-${TARGETPLATFORM},sharing=locked,target=/var/lib/apt \
    --mount=type=cache,id=pixelfed-apt-cache-${PHP_VERSION}-${PHP_DEBIAN_RELEASE}-${TARGETPLATFORM},sharing=locked,target=/var/cache/apt \
    /docker/install/base.sh

#######################################################
# PHP: extensions
#######################################################

FROM base AS php-extensions

ARG PHP_DEBIAN_RELEASE
ARG PHP_EXTENSIONS
ARG PHP_EXTENSIONS_DATABASE
ARG PHP_EXTENSIONS_EXTRA
ARG PHP_PECL_EXTENSIONS
ARG PHP_PECL_EXTENSIONS_EXTRA
ARG PHP_VERSION
ARG TARGETPLATFORM

COPY --from=php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

COPY docker/shared/root/docker/install/php-extensions.sh /docker/install/php-extensions.sh

RUN --mount=type=cache,id=pixelfed-pear-${PHP_VERSION}-${PHP_DEBIAN_RELEASE}-${TARGETPLATFORM},sharing=locked,target=/tmp/pear  \
    --mount=type=cache,id=pixelfed-apt-${PHP_VERSION}-${PHP_DEBIAN_RELEASE}-${TARGETPLATFORM},sharing=locked,target=/var/lib/apt \
    --mount=type=cache,id=pixelfed-apt-cache-${PHP_VERSION}-${PHP_DEBIAN_RELEASE}-${TARGETPLATFORM},sharing=locked,target=/var/cache/apt \
    PHP_EXTENSIONS=${PHP_EXTENSIONS} \
    PHP_EXTENSIONS_DATABASE=${PHP_EXTENSIONS_DATABASE} \
    PHP_EXTENSIONS_EXTRA=${PHP_EXTENSIONS_EXTRA} \
    PHP_PECL_EXTENSIONS=${PHP_PECL_EXTENSIONS} \
    PHP_PECL_EXTENSIONS_EXTRA=${PHP_PECL_EXTENSIONS_EXTRA} \
    /docker/install/php-extensions.sh

#######################################################
# Node: Build frontend
#######################################################

# NOTE: Since the nodejs build is CPU architecture agnostic,
# we only want to build once and cache it for other architectures.
# We force the (CPU) [--platform] here to be architecture
# of the "builder"/"server" and not the *target* CPU architecture
# (e.g.) building the ARM version of Pixelfed on AMD64.
FROM --platform=${BUILDARCH} node:lts AS frontend-build

ARG BUILDARCH
ARG BUILD_FRONTEND=0
ARG RUNTIME_UID
ARG RUNTIME_GID

ARG NODE_ENV=production
ENV NODE_ENV=$NODE_ENV

WORKDIR /var/www/

SHELL [ "/usr/bin/bash", "-c" ]

# Install NPM dependencies
RUN --mount=type=cache,id=pixelfed-node-${BUILDARCH},sharing=locked,target=/tmp/cache \
    --mount=type=bind,source=package.json,target=/var/www/package.json \
    --mount=type=bind,source=package-lock.json,target=/var/www/package-lock.json \
<<EOF
    if [[ $BUILD_FRONTEND -eq 1 ]];
    then
        npm install --cache /tmp/cache --no-save --dev
    else
        echo "Skipping [npm install] as --build-arg [BUILD_FRONTEND] is not set to '1'"
    fi
EOF

# Copy the frontend source into the image before building
COPY --chown=${RUNTIME_UID}:${RUNTIME_GID} . /var/www

# Build the frontend with "mix" (See package.json)
RUN \
<<EOF
    if [[ $BUILD_FRONTEND -eq 1 ]];
    then
        npm run production
    else
        echo "Skipping [npm run production] as --build-arg [BUILD_FRONTEND] is not set to '1'"
    fi
EOF

#######################################################
# PHP: composer and source code
#######################################################

FROM php-extensions AS composer-and-src

ARG PHP_VERSION
ARG PHP_DEBIAN_RELEASE
ARG RUNTIME_UID
ARG RUNTIME_GID
ARG TARGETPLATFORM

# Make sure composer cache is targeting our cache mount later
ENV COMPOSER_CACHE_DIR="/cache/composer"

# Don't enforce any memory limits for composer
ENV COMPOSER_MEMORY_LIMIT=-1

# Disable interactvitity from composer
ENV COMPOSER_NO_INTERACTION=1

# Copy composer from https://hub.docker.com/_/composer
COPY --link --from=composer-image /usr/bin/composer /usr/bin/composer

#! Changing user to runtime user
USER ${RUNTIME_UID}:${RUNTIME_GID}


# Install composer dependencies
# NOTE: we skip the autoloader generation here since we don't have all files avaliable (yet)
RUN --mount=type=cache,id=pixelfed-composer-${PHP_VERSION},sharing=locked,uid=${RUNTIME_UID},gid=${RUNTIME_GID},target=/cache/composer \
    --mount=type=bind,source=composer.json,target=/var/www/composer.json \
    --mount=type=bind,source=composer.lock,target=/var/www/composer.lock \
    set -ex \
    && composer install --prefer-dist --no-autoloader --ignore-platform-reqs --no-scripts

# Copy all other files over
COPY --chown=${RUNTIME_UID}:${RUNTIME_GID} . /var/www/

# Generate optimized autoloader now that we have all files around
RUN set -ex \
    && ENABLE_CONFIG_CACHE=false composer dump-autoload --optimize

# Now we can run the post-install scripts
RUN set -ex \
    && composer run-script post-update-cmd

#######################################################
# Runtime: base
#######################################################

FROM php-extensions AS shared-runtime

ARG RUNTIME_GID
ARG RUNTIME_UID

ENV RUNTIME_UID=${RUNTIME_UID}
ENV RUNTIME_GID=${RUNTIME_GID}

COPY --link --from=forego-image /usr/local/bin/forego /usr/local/bin/forego
COPY --link --from=dottie-image /dottie /usr/local/bin/dottie
COPY --link --from=gomplate-image /usr/local/bin/gomplate /usr/local/bin/gomplate
COPY --link --from=composer-image /usr/bin/composer /usr/bin/composer
COPY --link --from=composer-and-src --chown=${RUNTIME_UID}:${RUNTIME_GID} /var/www /var/www
COPY --link --from=frontend-build --chown=${RUNTIME_UID}:${RUNTIME_GID} /var/www/public /var/www/public

USER root

# for detail why storage is copied this way, pls refer to https://github.com/pixelfed/pixelfed/pull/2137#discussion_r434468862
RUN set -ex \
    && cp --recursive --link --preserve=all storage storage.skel \
    && rm -rf html && ln -s public html

COPY docker/shared/root /

ENTRYPOINT ["/docker/entrypoint.sh"]

#######################################################
# Runtime: apache
#######################################################

FROM shared-runtime AS apache-runtime

COPY docker/apache/root /

RUN set -ex \
    && a2enmod rewrite remoteip proxy proxy_http \
    && a2enconf remoteip

CMD ["apache2-foreground"]

#######################################################
# Runtime: fpm
#######################################################

FROM shared-runtime AS fpm-runtime

COPY docker/fpm/root /

CMD ["php-fpm"]

#######################################################
# Runtime: nginx
#######################################################

FROM shared-runtime AS nginx-runtime

ARG NGINX_GPGKEY
ARG NGINX_GPGKEY_PATH
ARG NGINX_VERSION
ARG PHP_DEBIAN_RELEASE
ARG PHP_VERSION
ARG TARGETPLATFORM

# Install nginx dependencies
RUN --mount=type=cache,id=pixelfed-apt-lists-${PHP_VERSION}-${PHP_DEBIAN_RELEASE}-${TARGETPLATFORM},sharing=locked,target=/var/lib/apt/lists \
    --mount=type=cache,id=pixelfed-apt-cache-${PHP_VERSION}-${PHP_DEBIAN_RELEASE}-${TARGETPLATFORM},sharing=locked,target=/var/cache/apt \
    set -ex \
    && gpg1 --keyserver "hkp://keyserver.ubuntu.com:80" --keyserver-options timeout=10 --recv-keys "${NGINX_GPGKEY}" \
    && gpg1 --export "$NGINX_GPGKEY" > "$NGINX_GPGKEY_PATH" \
    && echo "deb [signed-by=${NGINX_GPGKEY_PATH}] https://nginx.org/packages/mainline/debian/ ${PHP_DEBIAN_RELEASE} nginx" >> /etc/apt/sources.list.d/nginx.list \
    && apt-get update \
    && apt-get install -y --no-install-recommends nginx=${NGINX_VERSION}*

# copy docker entrypoints from the *real* nginx image directly
COPY --link --from=nginx-image /docker-entrypoint.d /docker/entrypoint.d/
COPY docker/nginx/root /
COPY docker/nginx/Procfile .

STOPSIGNAL SIGQUIT

CMD ["forego", "start", "-r"]
