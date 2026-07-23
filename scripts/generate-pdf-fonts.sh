#!/usr/bin/env bash
#
# Regenerates the JSON PDF font descriptors in phpmyfaq/src/fonts/ from the
# tecnickcom font sources. Requires: php, composer, network access to
# Packagist. Output families: core, dejavu, cid0.
#
# This Source Code Form is subject to the terms of the Mozilla Public License,
# v. 2.0. If a copy of the MPL was not distributed with this file, You can
# obtain one at https://mozilla.org/MPL/2.0/.

set -euo pipefail

REPO_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
FONTS_DIR="${REPO_ROOT}/phpmyfaq/src/fonts"
WORK_DIR="$(mktemp -d)"
trap 'rm -rf "${WORK_DIR}"' EXIT

echo ">> Installing tc-lib-pdf-font toolchain in ${WORK_DIR}"
(cd "${WORK_DIR}" && composer require --quiet --no-interaction tecnickcom/tcpdf:^7.0)

FONTLIB="${WORK_DIR}/vendor/tecnickcom/tc-lib-pdf-font"

# The importer expects to run from a repository checkout with its own
# vendor/autoload.php; shim it onto the project autoloader.
mkdir -p "${FONTLIB}/vendor"
printf '<?php require __DIR__ . "/../../../../vendor/autoload.php";\n' > "${FONTLIB}/vendor/autoload.php"

echo ">> Installing font sources (tc-font-mirror) and converting"
(cd "${FONTLIB}/util" && composer install --quiet --no-interaction && php bulk_convert.php)

echo ">> Copying core, dejavu and cid0 families to ${FONTS_DIR}"
for family in core dejavu cid0; do
    rm -rf "${FONTS_DIR:?}/${family}"
    cp -R "${FONTLIB}/target/fonts/${family}" "${FONTS_DIR}/${family}"
done

echo ">> Done. Families in ${FONTS_DIR}:"
ls -d "${FONTS_DIR}"/*/
