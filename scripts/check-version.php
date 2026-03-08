#!/usr/bin/env php
<?php

/**
 * Checks that the version in package.json matches the canonical version
 * defined in System.php. Exits with code 1 on mismatch.
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
 * @since     2026-03-08
 */

require dirname(__DIR__) . '/phpmyfaq/src/libs/autoload.php';

$phpVersion = phpMyFAQ\System::getVersion();

$packageJsonPath = dirname(__DIR__) . '/package.json';
$packageJson = json_decode(file_get_contents($packageJsonPath), true);
$packageVersion = $packageJson['version'] ?? '(not set)';

if ($phpVersion !== $packageVersion) {
    fprintf(
        STDERR,
        "Version mismatch!\n  System.php:   %s\n  package.json: %s\nRun 'composer version:sync' to fix.\n",
        $phpVersion,
        $packageVersion
    );
    exit(1);
}

echo sprintf("Version OK: %s\n", $phpVersion);
