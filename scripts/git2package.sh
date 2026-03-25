#!/bin/sh

#
# Compatibility wrapper for the historical phpMyFAQ packaging entry point.
# The canonical release preparation flow now lives in prepare-release-artifacts.sh.
#

set -eu

SCRIPT_DIR=$(CDPATH= cd -- "$(dirname "$0")" && pwd)

printf '[DEPRECATED] git2package.sh is kept as a compatibility wrapper.\n' >&2
printf '[DEPRECATED] Use %s/prepare-release-artifacts.sh instead.\n' "${SCRIPT_DIR}" >&2

exec "${SCRIPT_DIR}/prepare-release-artifacts.sh" "$@"
