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

# Rewrites `const <NAME> = '<value>';` in a PHP file. Fails when the constant
# is not found exactly once.
patch_php_constant() {
    if [ "${DRY_RUN}" -eq 1 ]; then
        printf "[dry-run] patch %s = '%s' in %s\n" "$1" "$2" "$3"
        return 0
    fi

    # shellcheck disable=SC2016
    "${PHP_BIN}" -r '
        [$name, $value, $file] = [$argv[1], $argv[2], $argv[3]];
        $source = file_get_contents($file);
        $patched = preg_replace(
            sprintf("/const %s = \x27[^\x27]*\x27;/", preg_quote($name, "/")),
            sprintf("const %s = \x27%s\x27;", $name, $value),
            $source,
            1,
            $count,
        );
        if ($count !== 1) {
            fwrite(STDERR, sprintf("Could not patch constant %s in %s\n", $name, $file));
            exit(1);
        }
        file_put_contents($file, $patched);
    ' "$1" "$2" "$3"
}

usage() {
    sed -n '3,12p' "$0" | sed 's/^# \{0,1\}//'
}

# Single-quotes a string for safe reuse as one shell word (escapes embedded
# single quotes using the standard '\'' trick).
shell_quote() {
    printf "'%s'" "$(printf '%s' "$1" | sed "s/'/'\\\\''/g")"
}

VERSION=''
FROM_STAGE='preflight'
DRY_RUN=0
TYPE=''
CODENAME='TBD'
PRINT_TYPE=0
# Reproduces the flags the user actually passed, so a resume hint can be
# replayed verbatim (see print_resume_hint). --from is deliberately excluded:
# the hint always supplies its own --from <failed-stage>.
RESUME_FLAGS=''

while [ "$#" -gt 0 ]; do
    case "$1" in
        --from)       [ "$#" -ge 2 ] || fail 'Option --from requires an argument.'; FROM_STAGE=$2; shift 2 ;;
        --dry-run)    DRY_RUN=1; RESUME_FLAGS="${RESUME_FLAGS} --dry-run"; shift ;;
        --type)       [ "$#" -ge 2 ] || fail 'Option --type requires an argument.'; TYPE=$2; RESUME_FLAGS="${RESUME_FLAGS} --type $(shell_quote "$2")"; shift 2 ;;
        --codename)   [ "$#" -ge 2 ] || fail 'Option --codename requires an argument.'; CODENAME=$2; RESUME_FLAGS="${RESUME_FLAGS} --codename $(shell_quote "$2")"; shift 2 ;;
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
    if [ -f "${RELEASE_DIR}/SHA256SUMS" ]; then
        log "build: signed artifacts already present in ${RELEASE_DIR} — skipping"
        return 0
    fi

    run "${REPO_ROOT}/scripts/prepare-release-artifacts.sh" "${VERSION}"
    run "${REPO_ROOT}/scripts/sign-release-artifacts.sh" "${VERSION}"
}

stage_publish_packages() {
    [ "${DRY_RUN}" -eq 1 ] || [ -d "${RELEASE_DIR}" ] \
        || fail "publish-packages: ${RELEASE_DIR} does not exist — run the build stage first."

    run rsync -av --checksum \
        --exclude "hashes-${VERSION}.json" \
        --exclude 'ARTIFACTS.txt' \
        "${RELEASE_DIR}/" "${DOWNLOAD_SSH_TARGET}/"

    if [ "${DRY_RUN}" -eq 1 ]; then
        log 'publish-packages: dry-run — skipping download.phpmyfaq.de verification'
        return 0
    fi

    remote_info=$(curl -fsS "https://download.phpmyfaq.de/info/${VERSION}") \
        || fail "publish-packages: https://download.phpmyfaq.de/info/${VERSION} is not reachable after upload."

    printf '%s' "${remote_info}" | grep -q "\"version\": *\"${VERSION}\"" \
        || fail "publish-packages: info endpoint does not report version ${VERSION} — got: ${remote_info}"

    local_md5=$(cut -d' ' -f1 "${RELEASE_DIR}/phpMyFAQ-${VERSION}.zip.md5")
    printf '%s' "${remote_info}" | grep -q "${local_md5}" \
        || fail "publish-packages: remote zip md5 does not match local ${local_md5} — upload may be corrupt."

    log "publish-packages: download.phpmyfaq.de serves ${VERSION} with matching checksum"
}

stage_update_api() {
    [ "${DRY_RUN}" -eq 1 ] || [ -d "${RELEASE_DIR}" ] \
        || fail "update-api: ${RELEASE_DIR} does not exist — run the build stage first."

    release_date=$(date '+%Y-%m-%d')

    if ! cmp -s "${RELEASE_DIR}/hashes-${VERSION}.json" "${API_REPO_DIR}/json/hashes-${VERSION}.json" 2>/dev/null; then
        run cp "${RELEASE_DIR}/hashes-${VERSION}.json" "${API_REPO_DIR}/json/"
    fi

    if [ "${RELEASE_TYPE}" = 'stable' ]; then
        patch_php_constant PHPMYFAQ_STABLE_VERSION "${VERSION}" "${API_REPO_DIR}/phpmyfaq.php"
        patch_php_constant PHPMYFAQ_STABLE_RELEASE "${release_date}" "${API_REPO_DIR}/phpmyfaq.php"
    else
        patch_php_constant PHPMYFAQ_DEV_VERSION "${VERSION}" "${API_REPO_DIR}/phpmyfaq.php"
        patch_php_constant PHPMYFAQ_DEV_RELEASE "${release_date}" "${API_REPO_DIR}/phpmyfaq.php"
    fi

    (cd "${API_REPO_DIR}" && run composer install --quiet && run composer test)

    if [ -n "$(git -C "${API_REPO_DIR}" status --porcelain)" ]; then
        run git -C "${API_REPO_DIR}" add "json/hashes-${VERSION}.json" phpmyfaq.php
        run git -C "${API_REPO_DIR}" commit -m "${VERSION}"
    else
        log 'update-api: no changes to commit — already up to date'
    fi
    run git -C "${API_REPO_DIR}" push

    # Ship production dependencies, then restore dev dependencies locally.
    (cd "${API_REPO_DIR}" && run composer install --no-dev --quiet)
    # WARNING: --delete removes anything on the server that is not in this
    # tree. Any server-only files added directly on API_SSH_TARGET (outside
    # this repo) WILL be deleted — verify the server directory before first use.
    run rsync -av --delete \
        --exclude '.git' --exclude 'tests' --exclude 'phpunit.xml.dist' \
        "${API_REPO_DIR}/" "${API_SSH_TARGET}/"
    (cd "${API_REPO_DIR}" && run composer install --quiet)

    if [ "${DRY_RUN}" -eq 1 ]; then
        log 'update-api: dry-run — skipping api.phpmyfaq.de verification'
        return 0
    fi

    remote_versions=$(curl -fsS 'https://api.phpmyfaq.de/versions') \
        || fail 'update-api: https://api.phpmyfaq.de/versions is not reachable after deploy.'
    printf '%s' "${remote_versions}" | grep -q "\"${VERSION}\"" \
        || fail "update-api: /versions does not report ${VERSION} — got: ${remote_versions}"

    curl -fsS "https://api.phpmyfaq.de/verify/${VERSION}" >/dev/null \
        || fail "update-api: /verify/${VERSION} is not serving the hash manifest."

    log "update-api: api.phpmyfaq.de serves ${VERSION}"
}

stage_update_www() {
    (cd "${WWW_REPO_DIR}" && run pnpm install --frozen-lockfile && run pnpm update:data)

    news_year=$(date '+%Y')
    news_date=$(date '+%Y-%m-%d')
    news_file="${WWW_REPO_DIR}/content/news/${news_year}.md"
    [ "${DRY_RUN}" -eq 1 ] || [ -f "${news_file}" ] \
        || fail "update-www: ${news_file} does not exist — create the year file with its frontmatter first."

    if [ "${DRY_RUN}" -eq 0 ] && grep -q "phpMyFAQ ${VERSION}](/download)" "${news_file}"; then
        log "update-www: news entry for ${VERSION} already present — skipping draft"
    else
        run "${PHP_BIN}" "${REPO_ROOT}/scripts/release-news-draft.php" \
            "${VERSION}" "${news_date}" "${CODENAME}" "${news_file}"
    fi

    if [ "${DRY_RUN}" -eq 0 ]; then
        printf '\nReview and edit the news entry now:\n  %s\nPress Enter when done to build and deploy...' "${news_file}"
        read -r _
    fi

    (cd "${WWW_REPO_DIR}" && run pnpm test:ci && run pnpm build)

    if [ -n "$(git -C "${WWW_REPO_DIR}" status --porcelain)" ]; then
        run git -C "${WWW_REPO_DIR}" add data content/news public/api/news
        run git -C "${WWW_REPO_DIR}" commit -m "${VERSION}"
    else
        log 'update-www: no changes to commit — already up to date'
    fi
    run git -C "${WWW_REPO_DIR}" push

    # WARNING: --delete removes anything on the server that is not in the
    # built output. Any server-only files added directly on WWW_SSH_TARGET
    # (outside this repo's build) WILL be deleted — verify the server
    # directory before first use.
    run rsync -av --delete "${WWW_REPO_DIR}/out/" "${WWW_SSH_TARGET}/"

    if [ "${DRY_RUN}" -eq 1 ]; then
        log 'update-www: dry-run — skipping www.phpmyfaq.de verification'
        return 0
    fi

    curl -fsS 'https://www.phpmyfaq.de/' | grep -q "${VERSION}" \
        || fail "update-www: deployed homepage does not mention ${VERSION} — check the deploy."

    log "update-www: www.phpmyfaq.de shows ${VERSION}"
}

stage_github_release() {
    release_notes=$("${PHP_BIN}" "${REPO_ROOT}/scripts/release-changelog.php" "${VERSION}")
    release_notes=$(printf '%s\n\n## Verification\n\nSee the [verification instructions](https://github.com/thorsten/phpMyFAQ/blob/main/docs/release.md#139-verification). The release public key is published at [docs/keys/phpmyfaq-release-public-key.asc](https://github.com/thorsten/phpMyFAQ/blob/main/docs/keys/phpmyfaq-release-public-key.asc).\n' "${release_notes}")

    if [ "${RELEASE_TYPE}" = 'development' ]; then
        set -- --prerelease
    else
        set --
    fi

    if ! gh release view "${VERSION}" --repo thorsten/phpMyFAQ >/dev/null 2>&1; then
        run gh release create "${VERSION}" \
            --repo thorsten/phpMyFAQ \
            --title "phpMyFAQ ${VERSION}" \
            --notes "${release_notes}" \
            "$@" \
            "${RELEASE_DIR}/phpMyFAQ-${VERSION}.zip" \
            "${RELEASE_DIR}/phpMyFAQ-${VERSION}.tar.gz" \
            "${RELEASE_DIR}/SHA256SUMS" \
            "${RELEASE_DIR}/SHA256SUMS.asc" \
            "${RELEASE_DIR}/phpMyFAQ-${VERSION}.zip.asc" \
            "${RELEASE_DIR}/phpMyFAQ-${VERSION}.tar.gz.asc" \
            "${RELEASE_DIR}/phpMyFAQ-${VERSION}.php.sbom.cdx.json" \
            "${RELEASE_DIR}/phpMyFAQ-${VERSION}.js.sbom.cdx.json" \
            "${RELEASE_DIR}/phpMyFAQ-${VERSION}.sbom.cdx.json"

        if [ "${DRY_RUN}" -eq 1 ]; then
            log 'github-release: dry-run — skipped release creation'
        else
            log "github-release: created release ${VERSION}"
        fi
        return 0
    fi

    # The release already exists. `gh release create` creates the release
    # and THEN uploads assets, so a failure mid-upload leaves a live release
    # missing artifacts — "exists" must never be treated as "complete".
    # Diff the expected asset list against what's actually attached and
    # upload whatever is missing instead of skipping outright.
    existing_assets=$(gh release view "${VERSION}" --repo thorsten/phpMyFAQ --json assets --jq '.assets[].name')

    missing_found=0
    for asset_name in \
        "phpMyFAQ-${VERSION}.zip" \
        "phpMyFAQ-${VERSION}.tar.gz" \
        'SHA256SUMS' \
        'SHA256SUMS.asc' \
        "phpMyFAQ-${VERSION}.zip.asc" \
        "phpMyFAQ-${VERSION}.tar.gz.asc" \
        "phpMyFAQ-${VERSION}.php.sbom.cdx.json" \
        "phpMyFAQ-${VERSION}.js.sbom.cdx.json" \
        "phpMyFAQ-${VERSION}.sbom.cdx.json"
    do
        if printf '%s\n' "${existing_assets}" | grep -Fxq "${asset_name}"; then
            continue
        fi
        missing_found=1
        log "github-release: asset ${asset_name} missing from release ${VERSION} — uploading"
        run gh release upload "${VERSION}" --repo thorsten/phpMyFAQ "${RELEASE_DIR}/${asset_name}"
    done

    if [ "${missing_found}" -eq 0 ]; then
        log "github-release: release ${VERSION} already complete — skipping"
    fi
}

# --- Stage dispatch --------------------------------------------------------

CURRENT_STAGE=''

print_resume_hint() {
    exit_status=$?
    if [ "${exit_status}" -ne 0 ] && [ -n "${CURRENT_STAGE}" ]; then
        printf '\n[FAIL] Stage %s failed — fix the issue and resume with: ./scripts/release.sh %s --from %s%s\n' \
            "${CURRENT_STAGE}" "${VERSION}" "${CURRENT_STAGE}" "${RESUME_FLAGS}" >&2
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
