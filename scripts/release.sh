#!/bin/sh

#
# One-command phpMyFAQ release orchestrator.
#
#   ./scripts/release.sh <version> [--from <stage>] [--dry-run]
#                        [--type stable|development] [--codename <name>]
#                        [--print-type]
#
# Stages: preflight build publish-packages update-api update-www github-release
# Configuration: ~/.config/phpmyfaq/release.conf (see scripts/release.conf.example)
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
# @version   2026-08-01
#

set -eu

# shellcheck disable=SC3040
if (set -o pipefail 2>/dev/null); then
    set -o pipefail
fi

# shellcheck disable=SC1007
SCRIPT_DIR=$(CDPATH= cd -- "$(dirname "$0")" && pwd)
# shellcheck disable=SC1007
REPO_ROOT=$(CDPATH= cd -- "${SCRIPT_DIR}/.." && pwd)

: "${PHP_BIN:=php}"

STAGES='preflight build publish-packages update-api update-www github-release'

log() {
    printf '\n[%s] %s\n' "$(date '+%H:%M:%S')" "$*"
}

fail() {
    printf '\n[FAIL] %s\n' "$*" >&2
    exit 1
}

# Executes its arguments, or prints them when --dry-run is active.
run() {
    if [ "${DRY_RUN}" -eq 1 ]; then
        printf '[dry-run] %s\n' "$*"
    else
        "$@"
    fi
}

release_type() {
    case "$1" in
        *-*) printf 'development' ;;
        *)   printf 'stable' ;;
    esac
}

usage() {
    sed -n '3,12p' "$0" | sed 's/^# \{0,1\}//'
}

VERSION=''
FROM_STAGE='preflight'
DRY_RUN=0
TYPE=''
CODENAME='TBD'
PRINT_TYPE=0

# shellcheck disable=SC2034
while [ "$#" -gt 0 ]; do
    case "$1" in
        --from)       [ "$#" -ge 2 ] || fail 'Option --from requires an argument.'; FROM_STAGE=$2; shift 2 ;;
        --dry-run)    DRY_RUN=1; shift ;;
        --type)       [ "$#" -ge 2 ] || fail 'Option --type requires an argument.'; TYPE=$2; shift 2 ;;
        --codename)   [ "$#" -ge 2 ] || fail 'Option --codename requires an argument.'; CODENAME=$2; shift 2 ;;
        --print-type) PRINT_TYPE=1; shift ;;
        -h|--help)    usage; exit 0 ;;
        -*)           fail "Unknown option: $1 — run with --help for usage." ;;
        *)            VERSION=$1; shift ;;
    esac
done

[ -n "${VERSION}" ] || fail 'Missing <version> argument — run with --help for usage.'

RELEASE_TYPE=${TYPE:-$(release_type "${VERSION}")}
case "${RELEASE_TYPE}" in
    stable|development) ;;
    *) fail "Invalid --type '${RELEASE_TYPE}' — use 'stable' or 'development'." ;;
esac

if [ "${PRINT_TYPE}" -eq 1 ]; then
    printf '%s\n' "${RELEASE_TYPE}"
    exit 0
fi

case " ${STAGES} " in
    *" ${FROM_STAGE} "*) ;;
    *) fail "Unknown stage '${FROM_STAGE}' — valid stages: ${STAGES}" ;;
esac

# shellcheck disable=SC2034
RELEASE_DIR="${REPO_ROOT}/build/release/${VERSION}"

CONFIG_FILE="${PMF_RELEASE_CONF:-${HOME}/.config/phpmyfaq/release.conf}"
[ -f "${CONFIG_FILE}" ] || fail "Config file not found: ${CONFIG_FILE} — copy scripts/release.conf.example there and fill it in."
# shellcheck disable=SC1090
. "${CONFIG_FILE}"

for required_var in API_REPO_DIR WWW_REPO_DIR DOWNLOAD_SSH_TARGET API_SSH_TARGET WWW_SSH_TARGET; do
    eval "required_value=\${${required_var}:-}"
    [ -n "${required_value}" ] || fail "${CONFIG_FILE} is missing ${required_var} — see scripts/release.conf.example."
done

# --- Stages (implemented in later tasks) -----------------------------------

stage_preflight() {
    [ -z "$(git -C "${REPO_ROOT}" status --porcelain)" ] \
        || fail 'preflight: main repo working tree is not clean — commit or stash first.'

    actual_version=$("${PHP_BIN}" "${REPO_ROOT}/scripts/get-version.php")
    [ "${actual_version}" = "${VERSION}" ] \
        || fail "preflight: System.php reports version ${actual_version}, expected ${VERSION} — bump the version constants first."

    "${PHP_BIN}" "${REPO_ROOT}/scripts/release-changelog.php" "${VERSION}" >/dev/null \
        || fail "preflight: CHANGELOG.md has no section for ${VERSION} — add the release notes first."

    for site_repo in "${API_REPO_DIR}" "${WWW_REPO_DIR}"; do
        [ -d "${site_repo}/.git" ] \
            || fail "preflight: ${site_repo} is not a git checkout — check your release.conf."
        [ -z "$(git -C "${site_repo}" status --porcelain)" ] \
            || fail "preflight: ${site_repo} has uncommitted changes — commit or stash first."
        run git -C "${site_repo}" pull --ff-only || fail "preflight: ${site_repo}: git pull --ff-only failed — resolve manually (diverged branch or network issue)."
    done

    for ssh_target in "${DOWNLOAD_SSH_TARGET}" "${API_SSH_TARGET}" "${WWW_SSH_TARGET}"; do
        ssh_host=${ssh_target%%:*}
        ssh -o BatchMode=yes "${ssh_host}" true \
            || fail "preflight: cannot reach ${ssh_host} via non-interactive SSH — check keys/agent."
    done

    gh auth status >/dev/null 2>&1 \
        || fail "preflight: gh CLI is not authenticated — run 'gh auth login'."

    if [ "${SKIP_GPG:-0}" != '1' ]; then
        gpg --list-secret-keys >/dev/null 2>&1 \
            || fail 'preflight: no GPG secret key available — set SKIP_GPG=1 to release unsigned.'
    fi

    log 'preflight: all checks passed'
}

stage_build() {
    log 'build: not implemented yet'
}

stage_publish_packages() {
    log 'publish-packages: not implemented yet'
}

stage_update_api() {
    log 'update-api: not implemented yet'
}

stage_update_www() {
    log 'update-www: not implemented yet'
}

stage_github_release() {
    log 'github-release: not implemented yet'
}

# --- Stage dispatch --------------------------------------------------------

CURRENT_STAGE=''

print_resume_hint() {
    exit_status=$?
    if [ "${exit_status}" -ne 0 ] && [ -n "${CURRENT_STAGE}" ]; then
        printf '\n[FAIL] Stage %s failed — fix the issue and resume with: ./scripts/release.sh %s --from %s\n' \
            "${CURRENT_STAGE}" "${VERSION}" "${CURRENT_STAGE}" >&2
    fi
}
trap print_resume_hint EXIT

started=0
for stage in ${STAGES}; do
    [ "${stage}" = "${FROM_STAGE}" ] && started=1
    if [ "${started}" -eq 1 ]; then
        CURRENT_STAGE=${stage}
        log "=== Stage: ${stage} (release ${VERSION}, ${RELEASE_TYPE}) ==="
        "stage_$(printf '%s' "${stage}" | tr '-' '_')"
        CURRENT_STAGE=''
    fi
done

log "Release ${VERSION} finished."
