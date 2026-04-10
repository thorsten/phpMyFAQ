#!/bin/sh

#
# For creating a release artifact run:
#
#   ./scripts/prepare-release-artifacts.sh x.y.z
#
# The script will download the source code from branch and
# it will create the 2 packages plus their MD5 hashes.
#
# This Source Code Form is subject to the terms of the Mozilla Public License,
# v. 2.0. If a copy of the MPL was not distributed with this file, You can
# obtain one at https://mozilla.org/MPL/2.0/.
#
# @package   phpMyFAQ
# @author    Matteo Scaramuccia <matteo@scaramuccia.com>
# @author    Thorsten Rinne <thorsten@phpmyfaq.de>
# @author    Rene Treffer <treffer+phpmyfaq@measite.de>
# @author    David Soria Parra <dsp@php.net>
# @author    Florian Anderiasch <florian@phpmyfaq.de>
# @copyright 2008-2026 phpMyFAQ Team
# @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
# @link      https://www.phpmyfaq.de
# @version   2008-09-10
#

set -eu

# shellcheck disable=SC3040
if (set -o pipefail 2>/dev/null); then
    set -o pipefail
fi
IFS=$(printf ' \t\n')

# shellcheck disable=SC1007
SCRIPT_DIR=$(CDPATH= cd -- "$(dirname "$0")" && pwd)
# shellcheck disable=SC1007
REPO_ROOT=$(CDPATH= cd -- "${SCRIPT_DIR}/.." && pwd)
ORIGINAL_DIR=$(pwd)
cd "${REPO_ROOT}" || exit 1

if [ "$#" -gt 1 ]; then
    printf 'Usage: %s [version]\n' "$0" >&2
    exit 1
fi

: "${PHP_BIN:=php}"
: "${VERSION:=${1:-$(php "${REPO_ROOT}/scripts/get-version.php")}}"
: "${PMF_PACKAGE_FOLDER:=phpmyfaq-${VERSION}}"

if command -v md5sum >/dev/null 2>&1; then
    : "${MD5BIN:=md5sum}"
elif command -v md5 >/dev/null 2>&1; then
    : "${MD5BIN:=md5 -r}"
else
    : "${MD5BIN:=}"
fi

BUILD_DIR="${REPO_ROOT}/build"
CHECKOUT_DIR="${BUILD_DIR}/checkout/${PMF_PACKAGE_FOLDER}"
PACKAGE_DIR="${BUILD_DIR}/package/${PMF_PACKAGE_FOLDER}"
RELEASE_DIR="${BUILD_DIR}/release/${VERSION}"
ARTIFACT_TAR="${RELEASE_DIR}/phpMyFAQ-${VERSION}.tar.gz"
ARTIFACT_ZIP="${RELEASE_DIR}/phpMyFAQ-${VERSION}.zip"
HASH_MANIFEST="${RELEASE_DIR}/hashes-${VERSION}.json"
ARTIFACT_MANIFEST="${RELEASE_DIR}/ARTIFACTS.txt"
TCPDF_PATH="${CHECKOUT_DIR}/phpmyfaq/src/libs/tecnickcom/tcpdf"

log() {
    printf '\n[%s] %s\n' "$(date '+%H:%M:%S')" "$*"
}

warn() {
    printf '\n[WARN] %s\n' "$*" >&2
}

fail() {
    printf '\n[ERROR] %s\n' "$*" >&2
    exit 1
}

cleanup() {
    cd "${ORIGINAL_DIR}" || true
    if [ "${KEEP_BUILD:-0}" = "1" ]; then
        warn "KEEP_BUILD=1 set; leaving ${BUILD_DIR} in place"
        return
    fi
    if [ -d "${BUILD_DIR}/checkout" ] || [ -d "${BUILD_DIR}/package" ]; then
        rm -rf "${BUILD_DIR}/checkout" "${BUILD_DIR}/package"
        log "Removed intermediate build directories"
    fi
}

trap cleanup EXIT

require_command() {
    command -v "$1" >/dev/null 2>&1 || fail "Required command '$1' not found in PATH"
}

check_prerequisites() {
    for cmd in git composer pnpm tar zip "${PHP_BIN}"; do
        require_command "$cmd"
    done

    if ! git rev-parse --is-inside-work-tree >/dev/null 2>&1; then
        fail "This script must be run inside a git repository"
    fi
}

prepare_directories() {
    log "Preparing release workspace under ${BUILD_DIR}"
    rm -rf "${CHECKOUT_DIR}" "${PACKAGE_DIR}" "${RELEASE_DIR}"
    mkdir -p "${CHECKOUT_DIR}" "${PACKAGE_DIR}" "${RELEASE_DIR}"
}

checkout_sources() {
    log "Checking out current git index into ${CHECKOUT_DIR}"
    git checkout-index -f -a --prefix="${CHECKOUT_DIR}/"
}

install_php_dependencies() {
    log "Installing PHP dependencies"
    (
        cd "${CHECKOUT_DIR}" &&
            COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --prefer-dist --no-progress --no-interaction
    )
}

install_js_dependencies() {
    log "Installing Node.js dependencies"
    (cd "${CHECKOUT_DIR}" && pnpm install --frozen-lockfile)
}

build_frontend() {
    log "Building frontend assets"
    (cd "${CHECKOUT_DIR}" && pnpm build:prod)
}

strip_tcpdf_assets() {
    if [ -d "${TCPDF_PATH}" ]; then
        log "Removing TCPDF fonts and examples"
        rm -rf "${TCPDF_PATH}/fonts" "${TCPDF_PATH}/examples"
    else
        warn "TCPDF path ${TCPDF_PATH} not found; skipping cleanup"
    fi
}

generate_hash_manifest() {
    log "Generating hash manifest"
    (cd "${CHECKOUT_DIR}" && "${PHP_BIN}" scripts/createHashes.php > "${HASH_MANIFEST}")
}

stage_for_packaging() {
    log "Staging phpMyFAQ payload"
    rm -rf "${PACKAGE_DIR}/phpmyfaq"
    mv "${CHECKOUT_DIR}/phpmyfaq" "${PACKAGE_DIR}/phpmyfaq"
}

create_packages() {
    log "Creating release archives in ${RELEASE_DIR}"
    tar czf "${ARTIFACT_TAR}" -C "${PACKAGE_DIR}" phpmyfaq
    (cd "${PACKAGE_DIR}" && zip -rq "${ARTIFACT_ZIP}" phpmyfaq)
}

write_checksums() {
    log "Creating checksum files"
    ${MD5BIN} "${ARTIFACT_TAR}" > "${ARTIFACT_TAR}.md5"
    ${MD5BIN} "${ARTIFACT_ZIP}" > "${ARTIFACT_ZIP}.md5"

    if command -v sha256sum >/dev/null 2>&1; then
        sha256sum "${ARTIFACT_TAR}" > "${ARTIFACT_TAR}.sha256"
        sha256sum "${ARTIFACT_ZIP}" > "${ARTIFACT_ZIP}.sha256"
    elif command -v shasum >/dev/null 2>&1; then
        shasum -a 256 "${ARTIFACT_TAR}" > "${ARTIFACT_TAR}.sha256"
        shasum -a 256 "${ARTIFACT_ZIP}" > "${ARTIFACT_ZIP}.sha256"
    else
        warn "No SHA256 tool found; skipping .sha256 files"
    fi
}

write_manifest() {
    cat > "${ARTIFACT_MANIFEST}" <<EOF
Release artifact layout for phpMyFAQ ${VERSION}

Directory:
${RELEASE_DIR}

Artifacts:
- $(basename "${ARTIFACT_ZIP}")
- $(basename "${ARTIFACT_TAR}")
- $(basename "${HASH_MANIFEST}")

Reserved for signing phase:
- SHA256SUMS
- SHA256SUMS.asc
- $(basename "${ARTIFACT_ZIP}").asc
- $(basename "${ARTIFACT_TAR}").asc
EOF
}

main() {
    check_prerequisites
    prepare_directories
    checkout_sources
    install_php_dependencies
    install_js_dependencies
    build_frontend
    strip_tcpdf_assets
    generate_hash_manifest
    stage_for_packaging
    create_packages
    write_checksums
    write_manifest

    log "Prepared release artifacts:"
    printf ' - %s\n' "${ARTIFACT_TAR}" "${ARTIFACT_ZIP}" "${HASH_MANIFEST}" "${ARTIFACT_MANIFEST}"
}

main
