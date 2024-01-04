# syntax=docker/dockerfile:1
# See https://hub.docker.com/r/docker/dockerfile

#######################################################
# Configuration
#######################################################

ARG COMPOSER_VERSION="2.6"
ARG NGINX_VERSION=1.25.3
ARG FOREGO_VERSION=0.17.2
ARG PECL_EXTENSIONS_EXTRA=""
ARG PECL_EXTENSIONS="imagick redis"
ARG PHP_BASE_TYPE="apache"
ARG PHP_DATABASE_EXTENSIONS="pdo_pgsql pdo_mysql pdo_sqlite"
ARG PHP_DEBIAN_RELEASE="bullseye"
ARG PHP_EXTENSIONS_EXTRA=""
ARG PHP_EXTENSIONS="intl bcmath zip pcntl exif curl gd"
ARG PHP_VERSION="8.1"
ARG APT_PACKAGES_EXTRA=""

# GPG key for nginx apt repository
ARG NGINX_GPGKEY=573BFD6B3D8FBC641079A6ABABF5BD827BD9BF62

# GPP key path for nginx apt repository
ARG NGINX_GPGKEY_PATH=/usr/share/keyrings/nginx-archive-keyring.gpg

#######################################################
# Docker "copy from" images
#######################################################

# Composer docker image from Docker Hub
#
# NOTE: Docker will *not* pull this image unless it's referenced (via build target)
FROM composer:${COMPOSER_VERSION} AS composer-image

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

#######################################################
# Base image
#######################################################

FROM php:${PHP_VERSION}-${PHP_BASE_TYPE}-${PHP_DEBIAN_RELEASE} AS base

ARG PHP_VERSION
ARG PHP_DEBIAN_RELEASE
ARG APT_PACKAGES_EXTRA

ARG TARGETPLATFORM
ARG BUILDKIT_SBOM_SCAN_STAGE=true

ENV DEBIAN_FRONTEND=noninteractive

# Ensure we run all scripts through 'bash' rather than 'sh'
SHELL ["/bin/bash", "-c"]

RUN set -ex \
	&& mkdir -pv /var/www/ \
	&& chown -R 33:33 /var/www

WORKDIR /var/www/

# Install package dependencies
RUN --mount=type=cache,id=pixelfed-apt-${PHP_VERSION}-${PHP_DEBIAN_RELEASE}-${TARGETPLATFORM},sharing=locked,target=/var/lib/apt \
	--mount=type=cache,id=pixelfed-apt-cache-${PHP_VERSION}-${PHP_DEBIAN_RELEASE}-${TARGETPLATFORM},sharing=locked,target=/var/cache/apt \
<<-SCRIPT
	#!/bin/bash
	set -ex -o errexit -o nounset -o pipefail

	# ensure we keep apt cache around in a Docker environment
	rm -f /etc/apt/apt.conf.d/docker-clean
	echo 'Binary::apt::APT::Keep-Downloaded-Packages "true";' > /etc/apt/apt.conf.d/keep-cache

	# Standard packages
	standardPackages=(
		apt-utils
		ca-certificates
		gettext-base
		git
		gnupg1
		gosu
		libcurl4-openssl-dev
		libzip-dev
		locales
		locales-all
		nano
		procps
		unzip
		zip
	)

	# Image Optimization
	imageOptimization=(
		gifsicle
		jpegoptim
		optipng
		pngquant
	)

	# Image Processing
	imageProcessing=(
		libjpeg62-turbo-dev
		libmagickwand-dev
		libpng-dev
	)

	# Required for GD
	gdDependencies=(
		libwebp-dev
		libwebp6
		libxpm-dev
		libxpm4
	)

	# Video Processing
	videoProcessing=(
		ffmpeg
	)

	# Database
	databaseDependencies=(
		libpq-dev
		libsqlite3-dev
	)

	apt-get update

	apt-get upgrade -y

	apt-get install -y --no-install-recommends \
		${standardPackages[*]} \
		${imageOptimization[*]} \
		${imageProcessing[*]} \
		${gdDependencies[*]} \
		${videoProcessing[*]} \
		${databaseDependencies[*]} \
		${APT_PACKAGES_EXTRA}
SCRIPT

# update locales
RUN set -ex \
	&& locale-gen \
	&& update-locale

#######################################################
# PHP: extensions
#######################################################

FROM base AS php-extensions

ARG PECL_EXTENSIONS
ARG PECL_EXTENSIONS_EXTRA
ARG PHP_DATABASE_EXTENSIONS
ARG PHP_DEBIAN_RELEASE
ARG PHP_EXTENSIONS
ARG PHP_EXTENSIONS_EXTRA
ARG PHP_VERSION
ARG TARGETPLATFORM

RUN --mount=type=cache,id=pixelfed-php-${PHP_VERSION}-${PHP_DEBIAN_RELEASE}-${TARGETPLATFORM},sharing=locked,target=/usr/src/php/ \
	set -ex \
	# Grab the PHP source code so we can compile against it
	&& docker-php-source extract \
	# Install pecl extensions
	&& pecl install ${PECL_EXTENSIONS} ${PECL_EXTENSIONS_EXTRA} \
	# PHP GD extensions
	&& docker-php-ext-configure gd \
		--with-freetype \
		--with-jpeg \
		--with-webp \
		--with-xpm \
	# PHP extensions (dependencies)
	&& docker-php-ext-install -j$(nproc) ${PHP_EXTENSIONS} ${PHP_EXTENSIONS_EXTRA} ${PHP_DATABASE_EXTENSIONS} \
	# Enable all extensions
	&& docker-php-ext-enable ${PECL_EXTENSIONS} ${PECL_EXTENSIONS_EXTRA} ${PHP_EXTENSIONS} ${PHP_EXTENSIONS_EXTRA} ${PHP_DATABASE_EXTENSIONS}

#######################################################
# PHP: composer and source code
#######################################################

FROM base AS composer-and-src

ARG PHP_VERSION
ARG PHP_DEBIAN_RELEASE
ARG TARGETPLATFORM

# Make sure composer cache is targeting our cache mount later
ENV COMPOSER_CACHE_DIR=/cache/composer

# Don't enforce any memory limits for composer
ENV COMPOSER_MEMORY_LIMIT=-1

# Disable interactvitity from composer
ENV COMPOSER_NO_INTERACTION=1

# Copy composer from https://hub.docker.com/_/composer
COPY --link --from=composer-image /usr/bin/composer /usr/bin/composer

#! Changing user to 33
USER 33:33

# Copy over only composer related files so docker layer cache isn't invalidated on PHP file changes
COPY --link --chown=33:33 composer.json composer.lock /var/www/

# Install composer dependencies
# NOTE: we skip the autoloader generation here since we don't have all files avaliable (yet)
RUN --mount=type=cache,id=pixelfed-composer-${PHP_VERSION}-${PHP_DEBIAN_RELEASE}-${TARGETPLATFORM},sharing=locked,target=/cache/composer \
	set -ex \
	&& composer install --prefer-dist --no-autoloader --ignore-platform-reqs

# Copy all other files over
COPY --link --chown=33:33 . /var/www/

# Generate optimized autoloader now that we have all files around
RUN set -ex \
	&& composer dump-autoload --optimize

#! Changing back to root
USER root:root

#######################################################
# Runtime: base
#######################################################

FROM base AS shared-runtime

COPY --link --from=php-extensions /usr/local/lib/php/extensions /usr/local/lib/php/extensions
COPY --link --from=php-extensions /usr/local/etc/php /usr/local/etc/php
COPY --link --from=composer-and-src --chown=33:33 /var/www /var/www
COPY --link --from=forego-image /usr/local/bin/forego /usr/local/bin/forego
COPY --link contrib/docker/php.production.ini "$PHP_INI_DIR/php.ini"

# for detail why storage is copied this way, pls refer to https://github.com/pixelfed/pixelfed/pull/2137#discussion_r434468862
RUN set -ex \
	&& cp --recursive --link --preserve=all storage storage.skel \
	&& rm -rf html && ln -s public html

COPY --link contrib/docker/docker-entrypoint.sh /docker-entrypoint.sh
COPY --link contrib/docker/shared/lib.sh /lib.sh
COPY --link contrib/docker/shared/docker-entrypoint.d /docker-entrypoint.d/

ENTRYPOINT ["/docker-entrypoint.sh"]

VOLUME /var/www/storage /var/www/bootstrap

#######################################################
# Runtime: apache
#######################################################

FROM shared-runtime AS apache-runtime

COPY --link contrib/docker/apache/conf-available/remoteip.conf /etc/apache2/conf-available/remoteip.conf
COPY --link contrib/docker/apache/docker-entrypoint.d /docker-entrypoint.d/

RUN set -ex \
	&& a2enmod rewrite remoteip proxy proxy_http \
	&& a2enconf remoteip

CMD ["apache2-foreground"]

EXPOSE 80

#######################################################
# Runtime: fpm
#######################################################

FROM shared-runtime AS fpm-runtime

COPY --link contrib/docker/fpm/docker-entrypoint.d /docker-entrypoint.d/

CMD ["php-fpm"]

EXPOSE 9000

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
	&& apt-get install -y --no-install-recommends \
		nginx=${NGINX_VERSION}*

# copy docker entrypoints from the *real* nginx image directly
COPY --link --from=nginx-image /docker-entrypoint.d /docker-entrypoint.d/
COPY --link contrib/docker/nginx/docker-entrypoint.d /docker-entrypoint.d/
COPY --link contrib/docker/nginx/default-http.conf /etc/nginx/templates/default.conf.template
COPY --link contrib/docker/nginx/Procfile .

EXPOSE 80

STOPSIGNAL SIGQUIT

CMD ["forego", "start", "-r"]
