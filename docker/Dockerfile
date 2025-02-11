# syntax=docker/dockerfile:1
# See https://hub.docker.com/r/docker/dockerfile

#######################################################
# Configuration
#######################################################

# See: https://nginx.org/
#
# renovate: datasource=docker depName=nginx versioning=semver
ARG NGINX_VERSION="1.27.3"

# See: https://github.com/ddollar/forego
#
# renovate: datasource=docker depName=nginxproxy/forego
ARG FOREGO_VERSION="0.18.2"

# See: https://github.com/hairyhenderson/gomplate
#
# renovate: datasource=github-releases depName=hairyhenderson/gomplate
ARG GOMPLATE_VERSION="4.3.0"

# See: https://github.com/jippi/dottie
#
# renovate: datasource=docker depName=ghcr.io/jippi/dottie
ARG DOTTIE_VERSION="v0.14.3"

# See: https://github.com/composer/composer
#
# renovate: datasource=github-releases depName=composer/composer
ARG COMPOSER_VERSION="2.8.4"

ARG RUNTIME_UID=33 # often called 'www-data'
ARG RUNTIME_GID=33 # often called 'www-data'

###
# PHP base configuration
###

# See: https://hub.docker.com/_/php/tags
#
# renovate: datasource=docker depName=php versioning=semver-coerced
ARG PHP_VERSION="8.4"

# See: https://github.com/docker-library/docs/blob/master/php/README.md#image-variants
ARG PHP_BASE_TYPE="fpm"
ARG PHP_DEBIAN_RELEASE="bookworm"

# APT extra packages
ARG APT_PACKAGES_EXTRA=

# PHP extensions installed via [pecl install]
ARG PHP_PECL_EXTENSIONS=""
ARG PHP_PECL_EXTENSIONS_EXTRA=

# PHP extensions installed via https://deb.sury.org/
# ! NOTE: Must not include the "php8.x-" prefix for packages
# !       so [php8.4-bcmath] should just be [bcmath] here
ARG PHP_EXTENSIONS="bcmath curl gd imagick intl mbstring redis xml yaml zip"
ARG PHP_EXTENSIONS_EXTRA=""
ARG PHP_EXTENSIONS_DATABASE="pgsql mysql sqlite"

# GPG key for nginx apt repository
ARG NGINX_GPGKEY="573BFD6B3D8FBC641079A6ABABF5BD827BD9BF62"

# GPP key path for nginx apt repository
ARG NGINX_GPGKEY_PATH="/usr/share/keyrings/nginx-archive-keyring.gpg"

#######################################################
# Docker "copy from" images
#######################################################

# nginx webserver from Docker Hub.
# Used to copy some docker-entrypoint files for [nginx-runtime]
#
# NOTE: Docker will *not* pull this image unless it's referenced (via build target)
FROM public.ecr.aws/nginx/nginx:${NGINX_VERSION} AS nginx-image

# Forego is a Procfile "runner" that makes it trival to run multiple
# processes under a simple init / PID 1 process.
#
# NOTE: Docker will *not* pull this image unless it's referenced (via build target)
#
# See: https://github.com/nginx-proxy/forego
FROM ghcr.io/nginx-proxy/forego:${FOREGO_VERSION}-debian AS forego-image

# Dottie makes working with .env files easier and safer
#
# NOTE: Docker will *not* pull this image unless it's referenced (via build target)
#
# See: https://github.com/jippi/dottie
FROM ghcr.io/jippi/dottie:${DOTTIE_VERSION} AS dottie-image

#######################################################
# Base image
#######################################################

FROM debian:${PHP_DEBIAN_RELEASE} AS base

ENV DEBIAN_FRONTEND="noninteractive"

ARG RUNTIME_GID
ARG RUNTIME_UID
ARG TARGETPLATFORM

# Ensure we run all scripts through 'bash' rather than 'sh'
SHELL ["/bin/bash", "-c"]

# Set www-data to be RUNTIME_UID/RUNTIME_GID
RUN set -ex \
    && groupmod --gid ${RUNTIME_GID} www-data \
    && usermod --uid ${RUNTIME_UID} --gid ${RUNTIME_GID} www-data

RUN set -ex \
    && mkdir -pv /var/www/ \
    && chown -R ${RUNTIME_UID}:${RUNTIME_GID} /var/www

WORKDIR /var/www/

ARG PHP_VERSION
ARG PHP_DEBIAN_RELEASE
ARG APT_PACKAGES_EXTRA
ENV APT_PACKAGES_EXTRA=${APT_PACKAGES_EXTRA}

# Copy the base layer installer into the image
COPY docker/rootfs/shared/docker/install/base.sh /docker/install/base.sh

RUN --mount=type=cache,id=pixelfed-apt-${PHP_VERSION}-${PHP_DEBIAN_RELEASE}-${TARGETPLATFORM},sharing=locked,target=/var/lib/apt \
    --mount=type=cache,id=pixelfed-apt-cache-${PHP_VERSION}-${PHP_DEBIAN_RELEASE}-${TARGETPLATFORM},sharing=locked,target=/var/cache/apt \
    /docker/install/base.sh

#######################################################
# PHP: extensions
#######################################################

ARG COMPOSER_VERSION
ARG PHP_EXTENSIONS
ARG PHP_EXTENSIONS_DATABASE
ARG PHP_EXTENSIONS_EXTRA
ARG PHP_PECL_EXTENSIONS
ARG PHP_PECL_EXTENSIONS_EXTRA
ARG PHP_BASE_TYPE

# Copy the PHP layer installer into the image
COPY docker/rootfs/shared/docker/install/php.sh /docker/install/php.sh

RUN --mount=type=cache,id=pixelfed-pear-${PHP_VERSION}-${PHP_DEBIAN_RELEASE}-${TARGETPLATFORM},sharing=locked,target=/tmp/pear \
    --mount=type=cache,id=pixelfed-apt-${PHP_VERSION}-${PHP_DEBIAN_RELEASE}-${TARGETPLATFORM},sharing=locked,target=/var/lib/apt \
    --mount=type=cache,id=pixelfed-apt-cache-${PHP_VERSION}-${PHP_DEBIAN_RELEASE}-${TARGETPLATFORM},sharing=locked,target=/var/cache/apt \
    IPE_KEEP_SYSPKG_CACHE=1 \
    COMPOSER_VERSION=${COMPOSER_VERSION} \
    PHP_EXTENSIONS_DATABASE=${PHP_EXTENSIONS_DATABASE} \
    PHP_EXTENSIONS_EXTRA=${PHP_EXTENSIONS_EXTRA} \
    PHP_EXTENSIONS=${PHP_EXTENSIONS} \
    PHP_PECL_EXTENSIONS_EXTRA=${PHP_PECL_EXTENSIONS_EXTRA} \
    PHP_PECL_EXTENSIONS=${PHP_PECL_EXTENSIONS} \
    PHP_BASE_TYPE=${PHP_BASE_TYPE} \
    /docker/install/php.sh

#######################################################
# PHP: composer
#######################################################

# Make sure composer cache is targeting our cache mount later
ENV COMPOSER_CACHE_DIR="/cache/composer"

# Don't enforce any memory limits for composer
ENV COMPOSER_MEMORY_LIMIT=-1

# Disable interactvitity from composer
ENV COMPOSER_NO_INTERACTION=1

#######################################################
# Baseline image
#######################################################

ENV RUNTIME_UID=${RUNTIME_UID}
ENV RUNTIME_GID=${RUNTIME_GID}

#! Changing user to runtime user
USER ${RUNTIME_UID}:${RUNTIME_GID}

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
COPY --chown=${RUNTIME_UID}:${RUNTIME_GID} . /var/www/

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

FROM base AS composer-and-src

ARG PHP_VERSION
ARG RUNTIME_UID
ARG RUNTIME_GID

# Install composer dependencies
# NOTE: we skip the autoloader generation here since we don't have all files available (yet)
RUN --mount=type=cache,id=pixelfed-composer-${PHP_VERSION},sharing=locked,target=/cache/composer \
    --mount=type=bind,source=composer.json,target=/var/www/composer.json \
    --mount=type=bind,source=composer.lock,target=/var/www/composer.lock \
    set -ex \
    && composer install --prefer-dist --no-autoloader --ignore-platform-reqs

# Copy all other files over
COPY --chown=${RUNTIME_UID}:${RUNTIME_GID} . /var/www/

#######################################################
# Runtime: base
#######################################################

FROM base AS shared-runtime

ARG RUNTIME_UID
ARG RUNTIME_GID

ENV RUNTIME_UID=${RUNTIME_UID}
ENV RUNTIME_GID=${RUNTIME_GID}

ARG PIXELFED_VERSION

ENV PIXELFED_VERSION=${PIXELFED_VERSION}

ARG TARGETARCH
ARG TARGETOS
ARG GOMPLATE_VERSION

USER root

COPY --link --from=forego-image /usr/local/bin/forego /usr/local/bin/forego
COPY --link --from=dottie-image /usr/local/bin/dottie /usr/local/bin/dottie

# Fetch gomplate
RUN set -ex \
    && wget \
        --quiet \
        -O /usr/local/bin/gomplate \
        https://github.com/hairyhenderson/gomplate/releases/download/v${GOMPLATE_VERSION}/gomplate_${TARGETOS}-${TARGETARCH} \
    && chmod +x /usr/local/bin/gomplate \
    && /usr/local/bin/gomplate --version

#! Changing user to runtime user
USER ${RUNTIME_UID}:${RUNTIME_GID}

COPY --link --from=composer-and-src --chown=${RUNTIME_UID}:${RUNTIME_GID} /var/www /var/www
COPY --link --from=frontend-build --chown=${RUNTIME_UID}:${RUNTIME_GID} /var/www/public /var/www/public

# Generate optimized autoloader now that we have all files around
RUN set -ex \
    && ENABLE_CONFIG_CACHE=false composer dump-autoload --optimize

# for detail why storage is copied this way, pls refer to https://github.com/pixelfed/pixelfed/pull/2137#discussion_r434468862
RUN set -ex \
    && cp --recursive --link --preserve=all storage storage.skel \
    && rm -rf html && ln -s public html

#! Changing to root user
USER root

COPY docker/rootfs/shared /

ENTRYPOINT ["/docker/entrypoint.sh"]

#######################################################
# Runtime: apache
#######################################################

FROM shared-runtime AS apache-runtime

ARG PHP_DEBIAN_RELEASE
ARG PHP_VERSION
ARG TARGETPLATFORM

COPY docker/rootfs/apache /

# Install apache webserver
RUN --mount=type=cache,id=pixelfed-apt-lists-${PHP_VERSION}-${PHP_DEBIAN_RELEASE}-${TARGETPLATFORM},sharing=locked,target=/var/lib/apt/lists \
    --mount=type=cache,id=pixelfed-apt-cache-${PHP_VERSION}-${PHP_DEBIAN_RELEASE}-${TARGETPLATFORM},sharing=locked,target=/var/cache/apt \
    set -ex \
    && apt update \
    && apt install -y --no-install-recommends apache2

RUN set -ex \
    && a2dismod mpm_event \
    && a2enmod php${PHP_VERSION} \
    && a2enmod rewrite remoteip proxy proxy_http \
    && a2enconf remoteip

CMD ["apache2-foreground"]

#######################################################
# Runtime: fpm
#######################################################

FROM shared-runtime AS fpm-runtime

ARG PHP_VERSION

ENV PHP_VERSION=${PHP_VERSION}

EXPOSE 9000

CMD ["sh", "-c", "php-fpm${PHP_VERSION}"]

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

ENV PHP_VERSION=${PHP_VERSION}

# Install nginx dependencies
RUN --mount=type=cache,id=pixelfed-apt-lists-${PHP_VERSION}-${PHP_DEBIAN_RELEASE}-${TARGETPLATFORM},sharing=locked,target=/var/lib/apt/lists \
    --mount=type=cache,id=pixelfed-apt-cache-${PHP_VERSION}-${PHP_DEBIAN_RELEASE}-${TARGETPLATFORM},sharing=locked,target=/var/cache/apt \
    set -ex \
    && gpg1 --keyserver "hkp://keyserver.ubuntu.com:80" --keyserver-options timeout=10 --recv-keys "${NGINX_GPGKEY}" \
    && gpg1 --export "$NGINX_GPGKEY" > "$NGINX_GPGKEY_PATH" \
    && echo "deb [signed-by=${NGINX_GPGKEY_PATH}] https://nginx.org/packages/mainline/debian/ ${PHP_DEBIAN_RELEASE} nginx" >> /etc/apt/sources.list.d/nginx.list \
    && apt update \
    && apt install -y --no-install-recommends nginx=${NGINX_VERSION}*

# copy docker entrypoints from the *real* nginx image directly
COPY --link --from=nginx-image /docker-entrypoint.d /docker/entrypoint.d/
COPY docker/rootfs/nginx /

STOPSIGNAL SIGQUIT

CMD ["forego", "start", "-r"]
