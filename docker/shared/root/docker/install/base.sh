#!/bin/bash
set -ex -o errexit -o nounset -o pipefail

# Ensure we keep apt cache around in a Docker environment
rm -f /etc/apt/apt.conf.d/docker-clean
echo 'Binary::apt::APT::Keep-Downloaded-Packages "true";' >/etc/apt/apt.conf.d/keep-cache

# Don't install recommended packages by default
echo 'APT::Install-Recommends "false";' >>/etc/apt/apt.conf

# Don't install suggested packages by default
echo 'APT::Install-Suggests "false";' >>/etc/apt/apt.conf

# Standard packages
declare -ra standardPackages=(
    apt-utils
    ca-certificates
    curl
    git
    gnupg1
    gosu
    libcurl4-openssl-dev
    libzip-dev
    locales
    locales-all
    moreutils
    nano
    procps
    software-properties-common
    unzip
    wget
    zip
)

# Image Optimization
declare -ra imageOptimization=(
    gifsicle
    jpegoptim
    optipng
    pngquant
)

# Image Processing
declare -ra imageProcessing=(
    libjpeg62-turbo-dev
    libmagickwand-dev
    libpng-dev
)

# Required for GD
declare -ra gdDependencies=(
    libwebp-dev
    libwebp6
    libxpm-dev
    libxpm4
)

# Video Processing
declare -ra videoProcessing=(
    ffmpeg
)

# Database
declare -ra databaseDependencies=(
    libpq-dev
    libsqlite3-dev
    mariadb-client
    postgresql-client
)

apt-get update

apt-get upgrade -y

apt-get install -y \
    ${standardPackages[*]} \
    ${imageOptimization[*]} \
    ${imageProcessing[*]} \
    ${gdDependencies[*]} \
    ${videoProcessing[*]} \
    ${databaseDependencies[*]} \
    ${APT_PACKAGES_EXTRA}

locale-gen
update-locale

# Install dotenv linter (https://github.com/dotenv-linter/dotenv-linter)
curl -sSfL https://raw.githubusercontent.com/dotenv-linter/dotenv-linter/master/install.sh | sh -s -- -b /usr/local/bin ${DOTENV_LINTER_VERSION}
