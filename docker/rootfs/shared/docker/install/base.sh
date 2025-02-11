#!/usr/bin/env bash
set -ex -o errexit -o nounset -o pipefail

# Ensure we keep apt cache around in a Docker environment
rm -f /etc/apt/apt.conf.d/docker-clean
echo 'Binary::apt::APT::Keep-Downloaded-Packages "true";' >/etc/apt/apt.conf.d/keep-cache

# Don't install recommended packages by default
echo 'APT::Install-Recommends "false";' >>/etc/apt/apt.conf

# Don't install suggested packages by default
echo 'APT::Install-Suggests "false";' >>/etc/apt/apt.conf

declare -a packages=()

# Standard packages
packages+=(
    apt-utils
    bzip2
    ca-certificates
    curl
    git
    gnupg1
    gosu
    locales
    locales-all
    lsb-release
    moreutils
    nano
    procps
    software-properties-common
    unzip
    wget
    tzdata
    zip
)

# Image Optimization
packages+=(
    gifsicle
    jpegoptim
    optipng
    pngquant
)

# Video Processing
packages+=(
    ffmpeg
)

# Database
packages+=(
    mariadb-client
    postgresql-client
)

readarray -d ' ' -t -O "${#packages[@]}" packages < <(echo -n "${APT_PACKAGES_EXTRA:-}")

# Install MariaDB version that doesn't break on MariaDB dumps AND that still alias to mysql
apt update
apt install -y --no-install-recommends curl ca-certificates apt-transport-https
curl -LsS https://downloads.mariadb.com/MariaDB/mariadb_repo_setup | bash -s -- --mariadb-server-version mariadb-10.11

apt update
apt upgrade -y
apt install -y --no-install-recommends "${packages[@]}"

locale-gen
update-locale
