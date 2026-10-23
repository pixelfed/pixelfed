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
    # no locales-all (~230MB): PHP runs in the C/POSIX locale; translations come from lang/ and ICU
    lsb-release
    moreutils
    nano
    procps
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

# Video Processing: static ffmpeg/ffprobe are copied in from mwader/static-ffmpeg (see docker/Dockerfile)

# Database
packages+=(
    mariadb-client
    "postgresql-client-${POSTGRESQL_CLIENT_VERSION}"
)

readarray -d ' ' -t -O "${#packages[@]}" packages < <(echo -n "${APT_PACKAGES_EXTRA:-}")

apt-get update
apt-get install -y --no-install-recommends curl ca-certificates apt-transport-https

# Install MariaDB version that doesn't break on MariaDB dumps AND that still alias to mysql
# shellcheck disable=SC2154
curl -LsS https://downloads.mariadb.com/MariaDB/mariadb_repo_setup | bash -s -- --mariadb-server-version "mariadb-${MARIADB_CLIENT_VERSION}"

# following https://www.postgresql.org/download/linux/debian/
# Import the repository signing key:
install -d /usr/share/postgresql-common/pgdg
curl -o /usr/share/postgresql-common/pgdg/apt.postgresql.org.asc --fail https://www.postgresql.org/media/keys/ACCC4CF8.asc

# Create the repository configuration file:
sh -c 'echo "deb [signed-by=/usr/share/postgresql-common/pgdg/apt.postgresql.org.asc] https://apt.postgresql.org/pub/repos/apt ${PHP_DEBIAN_RELEASE}-pgdg main" > /etc/apt/sources.list.d/pgdg.list'

apt-get update
apt-get upgrade -y
apt-get install -y --no-install-recommends "${packages[@]}"

locale-gen
update-locale

# Set www-data to be RUNTIME_UID/RUNTIME_GID
groupmod --gid "${RUNTIME_GID?:missing}" www-data
usermod --uid "${RUNTIME_UID?:missing}" --gid "${RUNTIME_GID}" www-data

mkdir -pv /var/www/
chown -R "${RUNTIME_UID}:${RUNTIME_GID}" /var/www
