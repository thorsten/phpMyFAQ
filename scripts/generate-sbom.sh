#!/bin/sh

#
# Generate CycloneDX Software Bill of Materials (SBOM) files for the
# PHP (Composer) and JS/TS (pnpm) dependency graphs.
#
# Usage:
#   ./scripts/generate-sbom.sh [source-dir] [output-dir]
#
# Both arguments are optional:
#   source-dir  defaults to the repository root
#   output-dir  defaults to <repo-root>/build/release/<version>
#
# The release version is resolved from scripts/get-version.php unless
# the VERSION environment variable is set.
#
# The resulting files follow the CycloneDX 1.5 JSON schema and are named
# after the release version:
#   <output-dir>/phpMyFAQ-<version>-php.sbom.json      (Composer only)
#   <output-dir>/phpMyFAQ-<version>-js.sbom.json       (pnpm only)
#   <output-dir>/phpMyFAQ-<version>.sbom.json          (both ecosystems)
#
# Requires pnpm (used to run @cyclonedx/cdxgen via `pnpm dlx`) and php
# (used to resolve the project version).
#
# This Source Code Form is subject to the terms of the Mozilla Public License,
# v. 2.0. If a copy of the MPL was not distributed with this file, You can
# obtain one at https://mozilla.org/MPL/2.0/.
#
# @package   phpMyFAQ
# @author    Thorsten Rinne <thorsten@phpmyfaq.de>
# @copyright 2008-2026 phpMyFAQ Team
# @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
# @link      https://www.phpmyfaq.de
# @version   2026-04-10
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

if [ "$#" -gt 2 ]; then
    printf 'Usage: %s [source-dir] [output-dir]\n' "$0" >&2
    exit 1
fi

SOURCE_DIR="${1:-${REPO_ROOT}}"

# shellcheck disable=SC1007
SOURCE_DIR=$(CDPATH= cd -- "${SOURCE_DIR}" && pwd)

: "${PHP_BIN:=php}"
: "${VERSION:=$("${PHP_BIN}" "${REPO_ROOT}/scripts/get-version.php")}"
: "${CDXGEN_VERSION:=latest}"
: "${CDXGEN_SPEC_VERSION:=1.5}"

OUTPUT_DIR="${2:-${REPO_ROOT}/build/release/${VERSION}}"

SBOM_PHP="${OUTPUT_DIR}/phpMyFAQ-${VERSION}.php.sbom.json"
SBOM_JS="${OUTPUT_DIR}/phpMyFAQ-${VERSION}.js.sbom.json"
SBOM_COMBINED="${OUTPUT_DIR}/phpMyFAQ-${VERSION}.sbom.json"

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

check_prerequisites() {
    require_command pnpm

    if [ ! -f "${SOURCE_DIR}/composer.lock" ]; then
        fail "composer.lock not found in ${SOURCE_DIR}"
    fi
    if [ ! -f "${SOURCE_DIR}/pnpm-lock.yaml" ]; then
        fail "pnpm-lock.yaml not found in ${SOURCE_DIR}"
    fi
}

run_cdxgen() {
    # Usage: run_cdxgen <project-type> <output-file>
    # <project-type> may be a single type or a comma-separated list,
    # which cdxgen interprets as a combined multi-ecosystem scan.
    _type="$1"
    _out="$2"
    pnpm dlx "@cyclonedx/cdxgen@${CDXGEN_VERSION}" \
        --type "${_type}" \
        --spec-version "${CDXGEN_SPEC_VERSION}" \
        --output "${_out}" \
        --project-name "phpMyFAQ-${VERSION}" \
        --project-version "${VERSION}" \
        --required-only \
        --no-recurse \
        "${SOURCE_DIR}"
}

generate_php_sbom() {
    log "Generating PHP SBOM from ${SOURCE_DIR}/composer.lock"
    run_cdxgen composer "${SBOM_PHP}"
}

generate_js_sbom() {
    log "Generating JS/TS SBOM from ${SOURCE_DIR}/pnpm-lock.yaml"
    run_cdxgen pnpm "${SBOM_JS}"
}

generate_combined_sbom() {
    log "Generating combined PHP + JS/TS SBOM"
    run_cdxgen composer,pnpm "${SBOM_COMBINED}"
}

main() {
    check_prerequisites
    mkdir -p "${OUTPUT_DIR}"
    generate_php_sbom
    generate_js_sbom
    generate_combined_sbom

    log "SBOMs written to:"
    printf ' - %s\n' "${SBOM_PHP}" "${SBOM_JS}" "${SBOM_COMBINED}"
}

main
