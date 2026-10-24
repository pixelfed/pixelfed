#!/bin/bash
# Security patching for the runtime image.
#
# Runs late in the build (after the cached package layer from base.sh) and is re-run on every CI build via
# the APT_UPGRADE_CACHEBUST build arg, so published Debian fixes land even when base.sh is unchanged.
#
# Non-apt components are patched by bumping their pinned versions instead; check them with
# `trivy image <image>` (OS packages are expected to show 0 fixable after this script):
#   - forego, dottie, gomplate, static ffmpeg: *_VERSION args in docker/Dockerfile
#   - PHP dependencies: composer.lock (`composer update <package>`)
set -ex -o errexit -o nounset -o pipefail

export DEBIAN_FRONTEND=noninteractive

apt-get update
apt-get upgrade -y --no-install-recommends
apt-get autoremove -y

# Fail the build if anything is still upgradable (e.g. held back because it needs new dependencies)
pending=$(apt list --upgradable 2>/dev/null | tail -n +2 || true)
if [[ -n "${pending}" ]]; then
    echo "Packages still upgradable after patching:"
    echo "${pending}"
    apt-get dist-upgrade -y --no-install-recommends
fi
