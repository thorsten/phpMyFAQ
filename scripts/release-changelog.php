#!/usr/bin/env php
<?php

/**
 * Prints the CHANGELOG.md section for a given version.
 * Usage: php scripts/release-changelog.php <version>
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-08-01
 */

declare(strict_types=1);

require __DIR__ . '/lib/ReleaseTools.php';

use phpMyFAQ\Release\ReleaseTools;

if ($argc !== 2) {
    fwrite(stream: STDERR, data: "Usage: php scripts/release-changelog.php <version>\n");
    exit(1);
}

$changelogFile = dirname(__DIR__) . '/CHANGELOG.md';
$changelog = file_get_contents($changelogFile);
if ($changelog === false) {
    fwrite(stream: STDERR, data: sprintf("Cannot read CHANGELOG.md: %s\n", $changelogFile));
    exit(1);
}

try {
    echo ReleaseTools::extractChangelogSection($changelog, $argv[1]) . "\n";
} catch (InvalidArgumentException $exception) {
    fwrite(stream: STDERR, data: $exception->getMessage() . "\n");
    exit(1);
}
