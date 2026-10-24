# syntax=docker/dockerfile:1
# See https://hub.docker.com/r/docker/dockerfile

#######################################################
# Configuration
#######################################################

# See: https://nginx.org/
#
# renovate: datasource=docker depName=nginx versioning=semver
ARG NGINX_VERSION="1.29.4"

# See: https://github.com/ddollar/forego
#
# renovate: datasource=docker depName=nginxproxy/forego
ARG FOREGO_VERSION="0.18.8"

# See: https://github.com/hairyhenderson/gomplate
#
# renovate: datasource=github-releases depName=hairyhenderson/gomplate
ARG GOMPLATE_VERSION="5.2.0"

# See: https://github.com/jippi/dottie
#
# renovate: datasource=docker depName=ghcr.io/jippi/dottie
ARG DOTTIE_VERSION="v1.3.0"

# See: https://github.com/composer/composer
#
# renovate: datasource=github-releases depName=composer/composer
ARG COMPOSER_VERSION="2.9.2"

# See: https://github.com/jippi/dottie
#
# renovate: datasource=docker depName=ghcr.io/jippi/dottie
ARG MARIADB_CLIENT_VERSION="11.8"

# See: https://github.com/jippi/dottie
#
# renovate: datasource=docker depName=ghcr.io/jippi/dottie
ARG POSTGRESQL_CLIENT_VERSION="17"

# Go toolchain for building forego/dottie/gomplate (see the go-tools stage). Keep on the latest patch
# release: rebuilding with it is what fixes the stdlib CVEs in those binaries.
#
# renovate: datasource=docker depName=golang
ARG GOLANG_VERSION="1.27.3"

# Static ffmpeg/ffprobe (replaces Debian's ffmpeg package, whose libavdevice/libplacebo dependencies
# pull in ~360MB of audio, X11/GPU and Vulkan libraries a server never uses).
# Security fixes come from bumping this tag, not from apt.
#
# See: https://github.com/wader/static-ffmpeg
#
# renovate: datasource=docker depName=mwader/static-ffmpeg
ARG FFMPEG_VERSION="9.0.2-1"

ARG RUNTIME_UID=33 # often called 'www-data'
ARG RUNTIME_GID=33 # often called 'www-data'

###
# PHP base configuration
###

# See: https://hub.docker.com/_/php/tags
#
# renovate: datasource=docker depName=php versioning=semver-coerced
ARG PHP_VERSION="8.5"

# See: https://github.com/docker-library/docs/blob/master/php/README.md#image-variants
ARG PHP_BASE_TYPE="fpm"
ARG PHP_DEBIAN_RELEASE="trixie"

# APT extra packages
ARG APT_PACKAGES_EXTRA=

# PHP extensions installed via [pecl install]
ARG PHP_PECL_EXTENSIONS=""
ARG PHP_PECL_EXTENSIONS_EXTRA=

# PHP extensions installed via https://deb.sury.org/
# ! NOTE: Must not include the "php8.x-" prefix for packages
# !       so [php8.4-bcmath] should just be [bcmath] here
ARG PHP_EXTENSIONS="bcmath curl gd imagick intl mbstring redis vips xml yaml zip ffi"
ARG PHP_EXTENSIONS_EXTRA=""
ARG PHP_EXTENSIONS_DATABASE="pgsql mysql sqlite3"
#ARG PHP_EXTENSIONS_DATABASE="pgsql mysql sqlite"

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
# GLITCH MODIFIED: Docker Hub pull-through mirror, avoids ECR Public rate limits (upstream: nginx)
FROM mirror.gcr.io/library/nginx:${NGINX_VERSION} AS nginx-image

# Go helper binaries, built from their pinned release tags (see install/go-tools.sh):
#   forego   - Procfile runner, PID 1 for the nginx runtime   https://github.com/nginx-proxy/forego
#   dottie   - .env validation                                  https://github.com/jippi/dottie
#   gomplate - config templating in the entrypoint             https://github.com/hairyhenderson/gomplate
# Built with a current Go and patched golang.org/x/* (etc.) modules, because the upstream release
# binaries lag behind Go security releases. Cross-compiled on the build platform.
FROM --platform=${BUILDPLATFORM} docker.io/library/golang:${GOLANG_VERSION}-${PHP_DEBIAN_RELEASE} AS go-tools

ARG FOREGO_VERSION
ARG DOTTIE_VERSION
ARG GOMPLATE_VERSION
ARG TARGETOS
ARG TARGETARCH

ENV GOOS=${TARGETOS}
ENV GOARCH=${TARGETARCH}

COPY docker/rootfs/shared/docker/install/go-tools.sh /go-tools.sh

RUN --mount=type=cache,id=pixelfed-go-mod,target=/go/pkg/mod \
    --mount=type=cache,id=pixelfed-go-build-${TARGETARCH},target=/root/.cache/go-build \
    FOREGO_VERSION=${FOREGO_VERSION} DOTTIE_VERSION=${DOTTIE_VERSION} GOMPLATE_VERSION=${GOMPLATE_VERSION} \
    /go-tools.sh /out

# Static ffmpeg + ffprobe binaries
#
# NOTE: Docker will *not* pull this image unless it's referenced (via build target)
FROM docker.io/mwader/static-ffmpeg:${FFMPEG_VERSION} AS ffmpeg-image

#######################################################
# Base image
#######################################################

# -slim: same Debian, without docs/man pages/translations (and dpkg skips them for later installs too)
FROM docker.io/library/debian:${PHP_DEBIAN_RELEASE}-slim AS base

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

ARG MARIADB_CLIENT_VERSION
ARG POSTGRESQL_CLIENT_VERSION

# Copy the base layer installer into the image
COPY docker/rootfs/shared/docker/install/base.sh /docker/install/base.sh

RUN --mount=type=cache,id=pixelfed-apt-${PHP_VERSION}-${PHP_DEBIAN_RELEASE}-${TARGETPLATFORM},target=/var/lib/apt \
    --mount=type=cache,id=pixelfed-apt-cache-${PHP_VERSION}-${PHP_DEBIAN_RELEASE}-${TARGETPLATFORM},target=/var/cache/apt \
    /docker/install/base.sh

#######################################################
# PHP: extensions
#######################################################

ARG COMPOSER_VERSION
ARG PHP_VERSION
ARG PHP_EXTENSIONS
ARG PHP_EXTENSIONS_DATABASE
ARG PHP_EXTENSIONS_EXTRA
ARG PHP_PECL_EXTENSIONS
ARG PHP_PECL_EXTENSIONS_EXTRA
ARG PHP_BASE_TYPE

# Copy the PHP layer installer into the image
COPY docker/rootfs/shared/docker/install/php.sh /docker/install/php.sh

RUN --mount=type=cache,id=pixelfed-pear-${PHP_VERSION}-${PHP_DEBIAN_RELEASE}-${TARGETPLATFORM},target=/tmp/pear \
    --mount=type=cache,id=pixelfed-apt-${PHP_VERSION}-${PHP_DEBIAN_RELEASE}-${TARGETPLATFORM},target=/var/lib/apt \
    --mount=type=cache,id=pixelfed-apt-cache-${PHP_VERSION}-${PHP_DEBIAN_RELEASE}-${TARGETPLATFORM},target=/var/cache/apt \
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

# Disable interactivity from composer
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

# Install composer dependencies (production only: no test/lint tooling in the image)
# NOTE: we skip the autoloader generation here since we don't have all files available (yet)
# NOTE: --no-scripts because composer.json hooks call `php artisan`, which does not exist until the
#       source is copied in below; package discovery still runs at `composer dump-autoload` later
RUN --mount=type=cache,id=pixelfed-composer-${PHP_VERSION},uid=${RUNTIME_UID},gid=${RUNTIME_GID},sharing=locked,target=/cache/composer \
    --mount=type=bind,source=composer.json,target=/var/www/composer.json \
    --mount=type=bind,source=composer.lock,target=/var/www/composer.lock \
    set -ex \
    && composer install --prefer-dist --no-autoloader --no-scripts --no-dev --ignore-platform-reqs

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

# Image driver fallback for config/image.php when IMAGE_DRIVER is not set. Not IMAGE_DRIVER itself:
# a real env var would override IMAGE_DRIVER from .env. libvips + FFI ship in this image.
ENV DOCKER_IMAGE_DRIVER=vips

USER root

COPY --link --from=go-tools /out/forego /out/dottie /out/gomplate /usr/local/bin/
COPY --link --from=ffmpeg-image /ffmpeg /ffprobe /usr/local/bin/
RUN set -ex \
    && gomplate --version \
    && ffmpeg -hide_banner -version | head -n1

# Apply pending Debian security/bug-fix updates on every build (see install/patch.sh).
# The package install layer in [base] is cached for as long as base.sh is unchanged, so its own
# `apt-get upgrade` can be months old. Pass a changing APT_UPGRADE_CACHEBUST (CI uses the run id)
# to force this layer to re-run; it sits before the app files so app-only changes don't re-run it.
ARG PHP_VERSION
ARG PHP_DEBIAN_RELEASE
ARG TARGETPLATFORM
ARG APT_UPGRADE_CACHEBUST=""
COPY docker/rootfs/shared/docker/install/patch.sh /docker/install/patch.sh
RUN --mount=type=cache,id=pixelfed-apt-${PHP_VERSION}-${PHP_DEBIAN_RELEASE}-${TARGETPLATFORM},target=/var/lib/apt \
    --mount=type=cache,id=pixelfed-apt-cache-${PHP_VERSION}-${PHP_DEBIAN_RELEASE}-${TARGETPLATFORM},target=/var/cache/apt \
    echo "apt upgrade cachebust: ${APT_UPGRADE_CACHEBUST}" \
    && /docker/install/patch.sh

# Keep Laravel's package/service manifests inside the image instead of bootstrap/cache/.
# Deployments often mount a volume over bootstrap/cache; a packages.php written there by another image
# version then registers providers this image does not have (e.g. dev-only Telescope -> fatal error).
ENV APP_PACKAGES_CACHE=/var/www/bootstrap/image-cache/packages.php
ENV APP_SERVICES_CACHE=/var/www/bootstrap/image-cache/services.php

#! Changing user to runtime user
USER ${RUNTIME_UID}:${RUNTIME_GID}

# vendor/ and the app source are separate layers: vendor only changes with composer.lock, so a
# code-only change re-pushes/re-pulls the small source layer instead of vendor + source together
COPY --link --from=composer-and-src --chown=${RUNTIME_UID}:${RUNTIME_GID} /var/www/vendor /var/www/vendor
COPY --link --chown=${RUNTIME_UID}:${RUNTIME_GID} . /var/www/
COPY --link --from=frontend-build --chown=${RUNTIME_UID}:${RUNTIME_GID} /var/www/public /var/www/public

# Generate optimized autoloader now that we have all files around
# (its post-autoload-dump hook runs package:discover, writing $APP_PACKAGES_CACHE)
RUN set -ex \
    && mkdir -p /var/www/bootstrap/image-cache \
    && ENABLE_CONFIG_CACHE=false composer dump-autoload --optimize --no-dev \
    && test -s "$APP_PACKAGES_CACHE"

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

# Install apache webserver
RUN --mount=type=cache,id=pixelfed-apt-${PHP_VERSION}-${PHP_DEBIAN_RELEASE}-${TARGETPLATFORM},target=/var/lib/apt \
    --mount=type=cache,id=pixelfed-apt-cache-${PHP_VERSION}-${PHP_DEBIAN_RELEASE}-${TARGETPLATFORM},target=/var/cache/apt \
    set -ex \
    && apt-get update \
    && apt-get install -y --no-install-recommends apache2

COPY docker/rootfs/apache /

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
RUN --mount=type=cache,id=pixelfed-apt-${PHP_VERSION}-${PHP_DEBIAN_RELEASE}-${TARGETPLATFORM},target=/var/lib/apt \
    --mount=type=cache,id=pixelfed-apt-cache-${PHP_VERSION}-${PHP_DEBIAN_RELEASE}-${TARGETPLATFORM},target=/var/cache/apt \
    set -ex -o pipefail \
    && apt-get update \
    && apt-get install -y --no-install-recommends wget gnupg ca-certificates \
    && wget -qO- https://nginx.org/keys/nginx_signing.key | gpg --dearmor > "${NGINX_GPGKEY_PATH}" \
    && echo "deb [signed-by=${NGINX_GPGKEY_PATH}] https://nginx.org/packages/mainline/debian/ ${PHP_DEBIAN_RELEASE} nginx" > /etc/apt/sources.list.d/nginx.list \
    && apt-get update \
    && apt-get install -y --no-install-recommends nginx=${NGINX_VERSION}* 

# copy docker entrypoints from the *real* nginx image directly
COPY --link --from=nginx-image /docker-entrypoint.d /docker/entrypoint.d/

COPY docker/rootfs/nginx /

STOPSIGNAL SIGQUIT

CMD ["forego", "start", "-r"]
