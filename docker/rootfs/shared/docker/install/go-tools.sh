#!/bin/bash
# Builds the Go helper binaries (forego, dottie, gomplate) from their pinned release tags with the current
# Go toolchain of the builder image, after bumping security-relevant modules they already depend on.
#
# Upstream release binaries lag behind Go and golang.org/x/* security releases; rebuilding fixes the
# stdlib CVEs, and the module bumps fix the rest. Only modules already in each tool's go.mod are bumped
# (patch/minor within the same major), so behaviour stays that of the pinned release.
#
# Usage: go-tools.sh <output dir>   (needs FOREGO_VERSION, DOTTIE_VERSION, GOMPLATE_VERSION)
# shellcheck disable=SC2154 # FOREGO_VERSION, DOTTIE_VERSION, GOMPLATE_VERSION come from the build env
set -ex -o errexit -o nounset -o pipefail

out="${1:?output dir}"
mkdir -p "${out}"

# Modules to bump when present (security fixes keep landing here)
patch_modules=(
    golang.org/x/crypto
    golang.org/x/net
    golang.org/x/text
    golang.org/x/sys
    github.com/klauspost/compress
    github.com/go-git/go-git/v5
    google.golang.org/grpc
    go.opentelemetry.io/otel/sdk
)

export CGO_ENABLED=0 GOFLAGS=-mod=mod GOTOOLCHAIN=local

build() {
    local repo="$1" tag="$2" pkg="$3" name="$4" ldflags="${5:-}"
    local src="/tmp/src/${name}"

    rm -rf "${src}"
    git clone --quiet --depth 1 --branch "${tag}" "https://github.com/${repo}.git" "${src}"
    pushd "${src}" >/dev/null

    for m in "${patch_modules[@]}"; do
        if go list -m "${m}" >/dev/null 2>&1; then
            go get "${m}@latest"
        fi
    done
    go mod tidy

    go build -trimpath -ldflags "-s -w ${ldflags}" -o "${out}/${name}" "${pkg}"
    popd >/dev/null
    # cross-compiled binaries may not run on the build platform
    "${out}/${name}" --version 2>/dev/null || true
}

build nginx-proxy/forego "v${FOREGO_VERSION}" . forego "-X main.Version=${FOREGO_VERSION}"
build jippi/dottie "${DOTTIE_VERSION}" . dottie
build hairyhenderson/gomplate "v${GOMPLATE_VERSION}" ./cmd/gomplate gomplate "-X github.com/hairyhenderson/gomplate/v5/version.Version=${GOMPLATE_VERSION}"
