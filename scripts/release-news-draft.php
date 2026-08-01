#!/usr/bin/env php
<?php

/**
 * Generates a www.phpmyfaq.de news entry draft for a given version.
 * Usage: php scripts/release-news-draft.php <version> [date] [codename] [news-file]
 * With a news-file argument the draft is inserted into the file in place;
 * otherwise it is printed to stdout.
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

if ($argc < 2 || $argc > 5) {
    fwrite(stream: STDERR, data: "Usage: php scripts/release-news-draft.php <version> [date] [codename] [news-file]\n");
    exit(1);
}

$version = $argv[1];
$date = $argv[2] ?? date('Y-m-d');
$codename = $argv[3] ?? 'TBD';
$newsFile = $argv[4] ?? null;

try {
    $changelog = (string) file_get_contents(dirname(__DIR__) . '/CHANGELOG.md');
    $section = ReleaseTools::extractChangelogSection($changelog, $version);
    $draft = ReleaseTools::newsDraft($version, $date, $section, $codename);

    if ($newsFile === null) {
        echo $draft;
        exit(0);
    }

    $content = file_get_contents($newsFile);
    if ($content === false) {
        fwrite(stream: STDERR, data: sprintf("Cannot read news file: %s\n", $newsFile));
        exit(1);
    }

    file_put_contents($newsFile, ReleaseTools::insertNewsEntry($content, $draft));
    fwrite(stream: STDOUT, data: sprintf("News draft inserted into %s\n", $newsFile));
} catch (InvalidArgumentException $exception) {
    fwrite(stream: STDERR, data: $exception->getMessage() . "\n");
    exit(1);
}
