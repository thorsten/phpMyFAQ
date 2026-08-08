#!/bin/sh

#
# This is the shell script for building:
# 1. a TAR.GZ package;
# 2. a ZIP package
# of phpMyFAQ using what committed into Git.
#
# For creating a package simply run:
#
#   ./git2package.sh
#
# The script will download the source code from branch and
# it will create the 2 packages plus their MD5/SHA256 hashes and
# CycloneDX SBOM files for the PHP and JS/TS dependency graphs.
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

set -eu
if (set -o pipefail 2>/dev/null); then
    set -o pipefail
fi
IFS=$(printf ' \t\n')

SCRIPT_DIR=$(CDPATH= cd -- "$(dirname "$0")" && pwd)
REPO_ROOT=$(CDPATH= cd -- "${SCRIPT_DIR}/.." && pwd)
ORIGINAL_DIR=$(pwd)
cd "${REPO_ROOT}" || exit 1

. "${REPO_ROOT}/scripts/version.sh"
: "${PMF_PACKAGE_FOLDER:=phpmyfaq-${PMF_VERSION}}"
: "${PHP_BIN:=php}"

BUILD_DIR="${REPO_ROOT}/build"
CHECKOUT_DIR="${BUILD_DIR}/checkout/${PMF_PACKAGE_FOLDER}"
PACKAGE_DIR="${BUILD_DIR}/package/${PMF_PACKAGE_FOLDER}"
ARTIFACT_TAR="${REPO_ROOT}/${PMF_PACKAGE_FOLDER}.tar.gz"
ARTIFACT_ZIP="${REPO_ROOT}/${PMF_PACKAGE_FOLDER}.zip"
HASH_MANIFEST="${REPO_ROOT}/hashes-${PMF_VERSION}.json"
SBOM_PHP="${REPO_ROOT}/phpMyFAQ-${PMF_VERSION}.php.sbom.cdx.json"
SBOM_JS="${REPO_ROOT}/phpMyFAQ-${PMF_VERSION}.js.sbom.cdx.json"
SBOM_COMBINED="${REPO_ROOT}/phpMyFAQ-${PMF_VERSION}.sbom.cdx.json"
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
    if [ -d "${BUILD_DIR}" ]; then
        rm -rf "${BUILD_DIR}"
        log "Removed build directory ${BUILD_DIR}"
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

    if [ -z "${MD5BIN:-}" ]; then
        if command -v md5 >/dev/null 2>&1; then
            MD5BIN=$(command -v md5)
        elif command -v md5sum >/dev/null 2>&1; then
            MD5BIN=$(command -v md5sum)
        else
            fail "Neither md5 nor md5sum available; install one or set MD5BIN"
        fi
    fi

    if ! git rev-parse --is-inside-work-tree >/dev/null 2>&1; then
        fail "This script must be run inside a git repository"
    fi
}

prepare_directories() {
    log "Preparing workspace under ${BUILD_DIR}"
    rm -rf "${BUILD_DIR}"
    mkdir -p "${CHECKOUT_DIR}" "${PACKAGE_DIR}"
}

checkout_sources() {
    log "Checking out current index into ${CHECKOUT_DIR}"
    git checkout-index -f -a --prefix="${CHECKOUT_DIR}/"
}

install_php_dependencies() {
    log "Installing PHP dependencies"
    (cd "${CHECKOUT_DIR}" && COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --prefer-dist --no-progress --no-interaction)
}

install_js_dependencies() {
    log "Installing JS dependencies"
    (cd "${CHECKOUT_DIR}" && pnpm install --frozen-lockfile)
}

build_frontend() {
    log "Running pnpm build:prod"
    (cd "${CHECKOUT_DIR}" && pnpm build:prod)
}

strip_tcpdf_assets() {
    if [ -d "${TCPDF_PATH}" ]; then
        log "Removing TCPDF fonts and examples"
        rm -rf "${TCPDF_PATH}/fonts" "${TCPDF_PATH}/examples"
    else
        warn "TCPDF path ${TCPDF_PATH} not found; skipping font cleanup"
    fi
}

generate_hash_manifest() {
    log "Generating hash manifest ${HASH_MANIFEST}"
    (cd "${CHECKOUT_DIR}" && "${PHP_BIN}" scripts/createHashes.php > "${HASH_MANIFEST}")
}

stage_for_packaging() {
    log "Staging files for packaging"
    rm -rf "${PACKAGE_DIR}/phpmyfaq"
    mv "${CHECKOUT_DIR}/phpmyfaq" "${PACKAGE_DIR}/phpmyfaq"
}

create_packages() {
    log "Building ${ARTIFACT_TAR}"
    tar czf "${ARTIFACT_TAR}" -C "${PACKAGE_DIR}" phpmyfaq

    log "Building ${ARTIFACT_ZIP}"
    (cd "${PACKAGE_DIR}" && zip -rq "${ARTIFACT_ZIP}" phpmyfaq)
}

generate_sboms() {
    log "Generating CycloneDX SBOMs"
    VERSION="${PMF_VERSION}" "${SCRIPT_DIR}/generate-sbom.sh" "${CHECKOUT_DIR}" "${REPO_ROOT}"
}

write_checksums() {
    log "Creating checksum files"

    SHA256_CMD=""
    if command -v sha256sum >/dev/null 2>&1; then
        SHA256_CMD="sha256sum"
    elif command -v shasum >/dev/null 2>&1; then
        SHA256_CMD="shasum -a 256"
    else
        warn "No SHA256 tool found; skipping .sha256 files"
    fi

    # Hash by bare filename from inside the artifact's directory so the
    # checksum files stay verifiable via `sha256sum -c` next to the download
    # and don't leak the local build path.
    for artifact in "${ARTIFACT_TAR}" "${ARTIFACT_ZIP}" "${SBOM_PHP}" "${SBOM_JS}" "${SBOM_COMBINED}"; do
        artifact_dir=$(dirname "${artifact}")
        artifact_name=$(basename "${artifact}")
        (cd "${artifact_dir}" && ${MD5BIN} "${artifact_name}" > "${artifact_name}.md5")
        if [ -n "${SHA256_CMD}" ]; then
            (cd "${artifact_dir}" && ${SHA256_CMD} "${artifact_name}" > "${artifact_name}.sha256")
        fi
    done
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
    generate_sboms
    write_checksums

    log "Created release artifacts:"
    printf ' - %s\n' \
        "${ARTIFACT_TAR}" \
        "${ARTIFACT_ZIP}" \
        "${HASH_MANIFEST}" \
        "${SBOM_PHP}" \
        "${SBOM_JS}" \
        "${SBOM_COMBINED}"
}

main
