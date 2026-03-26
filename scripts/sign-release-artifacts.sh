#!/bin/sh

#
# For signing a release run:
#
#   ./scripts/sign-release-artifacts.sh x.y.z
#
# The script will download the source code from branch and
# it will create the 2 packages plus their MD5 hashes.
#
# This Source Code Form is subject to the terms of the Mozilla Public License,
# v. 2.0. If a copy of the MPL was not distributed with this file, You can
# obtain one at https://mozilla.org/MPL/2.0/.
#
# @package   phpMyFAQ
# @author    Thorsten Rinne <thorsten@phpmyfaq.de>
# @copyright 2026 phpMyFAQ Team
# @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
# @link      https://www.phpmyfaq.de
# @version   2026-03-26
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

if [ "$#" -gt 1 ]; then
    printf 'Usage: %s [version]\n' "$0" >&2
    exit 1
fi

: "${PHP_BIN:=php}"
: "${VERSION:=${1:-$(php "${REPO_ROOT}/scripts/get-version.php")}}"

RELEASE_DIR="${REPO_ROOT}/build/release/${VERSION}"
ZIP_FILE="${RELEASE_DIR}/phpMyFAQ-${VERSION}.zip"
TAR_FILE="${RELEASE_DIR}/phpMyFAQ-${VERSION}.tar.gz"
SHA256_FILE="${RELEASE_DIR}/SHA256SUMS"
SHA256_ASC_FILE="${RELEASE_DIR}/SHA256SUMS.asc"
ZIP_ASC_FILE="${ZIP_FILE}.asc"
TAR_ASC_FILE="${TAR_FILE}.asc"
ARTIFACT_MANIFEST="${RELEASE_DIR}/ARTIFACTS.txt"

log() {
    printf '\n[%s] %s\n' "$(date '+%H:%M:%S')" "$*"
}

fail() {
    printf '\n[ERROR] %s\n' "$*" >&2
    exit 1
}

require_command() {
    command -v "$1" >/dev/null 2>&1 || fail "Required command '$1' not found in PATH"
}

set_sha256_command() {
    if command -v sha256sum >/dev/null 2>&1; then
        SHA256_CMD='sha256sum'
        return
    fi

    if command -v shasum >/dev/null 2>&1; then
        SHA256_CMD='shasum -a 256'
        return
    fi

    fail "Neither sha256sum nor shasum is available"
}

check_prerequisites() {
    require_command "${PHP_BIN}"
    set_sha256_command

    [ -d "${RELEASE_DIR}" ] || fail "Release directory ${RELEASE_DIR} does not exist"
    [ -f "${ZIP_FILE}" ] || fail "Missing artifact ${ZIP_FILE}"
    [ -f "${TAR_FILE}" ] || fail "Missing artifact ${TAR_FILE}"

    if [ "${SKIP_GPG:-0}" != "1" ]; then
        require_command gpg
    fi
}

generate_checksums() {
    log "Generating SHA256SUMS"
    rm -f "${SHA256_FILE}"

    (
        cd "${RELEASE_DIR}"
        ${SHA256_CMD} "$(basename "${ZIP_FILE}")" > "${SHA256_FILE}"
        ${SHA256_CMD} "$(basename "${TAR_FILE}")" >> "${SHA256_FILE}"
    )
}

gpg_base_args() {
    if [ -n "${GPG_PASSPHRASE:-}" ]; then
        printf '%s\n' "--batch --yes --pinentry-mode loopback --passphrase ${GPG_PASSPHRASE}"
        return
    fi

    printf '%s\n' "--batch --yes"
}

gpg_local_user_args() {
    if [ -n "${GPG_KEY_ID:-}" ]; then
        printf '%s\n' "--local-user ${GPG_KEY_ID}"
        return
    fi

    printf '%s\n' ""
}

sign_artifacts() {
    if [ "${SKIP_GPG:-0}" = "1" ]; then
        log "SKIP_GPG=1 set; only SHA256SUMS was generated"
        return
    fi

    log "Signing SHA256SUMS and release artifacts"
    rm -f "${SHA256_ASC_FILE}" "${ZIP_ASC_FILE}" "${TAR_ASC_FILE}"

    GPG_ARGS="$(gpg_base_args)"
    GPG_USER_ARGS="$(gpg_local_user_args)"

    # shellcheck disable=SC2086
    gpg ${GPG_ARGS} ${GPG_USER_ARGS} --armor --detach-sign --output "${SHA256_ASC_FILE}" "${SHA256_FILE}"
    # shellcheck disable=SC2086
    gpg ${GPG_ARGS} ${GPG_USER_ARGS} --armor --detach-sign --output "${ZIP_ASC_FILE}" "${ZIP_FILE}"
    # shellcheck disable=SC2086
    gpg ${GPG_ARGS} ${GPG_USER_ARGS} --armor --detach-sign --output "${TAR_ASC_FILE}" "${TAR_FILE}"
}

verify_outputs() {
    log "Verifying checksums"
    (
        cd "${RELEASE_DIR}"
        if [ "${SHA256_CMD}" = "sha256sum" ]; then
            sha256sum -c "${SHA256_FILE}"
        else
            shasum -a 256 -c "${SHA256_FILE}"
        fi
    )

    if [ "${SKIP_GPG:-0}" = "1" ]; then
        return
    fi

    log "Verifying signatures"
    gpg --verify "${SHA256_ASC_FILE}" "${SHA256_FILE}"
    gpg --verify "${ZIP_ASC_FILE}" "${ZIP_FILE}"
    gpg --verify "${TAR_ASC_FILE}" "${TAR_FILE}"
}

update_manifest() {
    if [ ! -f "${ARTIFACT_MANIFEST}" ]; then
        return
    fi

    cat > "${ARTIFACT_MANIFEST}" <<EOF
Release artifact layout for phpMyFAQ ${VERSION}

Directory:
${RELEASE_DIR}

Artifacts:
- $(basename "${ZIP_FILE}")
- $(basename "${TAR_FILE}")
- $(basename "${SHA256_FILE}")
EOF

    if [ -f "${RELEASE_DIR}/hashes-${VERSION}.json" ]; then
        printf '%s\n' "- hashes-${VERSION}.json" >> "${ARTIFACT_MANIFEST}"
    fi

    cat >> "${ARTIFACT_MANIFEST}" <<EOF
- ARTIFACTS.txt
EOF

    if [ "${SKIP_GPG:-0}" != "1" ]; then
        cat >> "${ARTIFACT_MANIFEST}" <<EOF
- $(basename "${SHA256_ASC_FILE}")
- $(basename "${ZIP_ASC_FILE}")
- $(basename "${TAR_ASC_FILE}")
EOF
    fi
}

main() {
    check_prerequisites
    generate_checksums
    sign_artifacts
    verify_outputs
    update_manifest

    log "Release signing outputs prepared in ${RELEASE_DIR}"
    printf ' - %s\n' "${SHA256_FILE}"

    if [ "${SKIP_GPG:-0}" != "1" ]; then
        printf ' - %s\n' "${SHA256_ASC_FILE}" "${ZIP_ASC_FILE}" "${TAR_ASC_FILE}"
    fi
}

main
