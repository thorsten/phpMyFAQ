#!/usr/bin/env php
<?php

/**
 * Enforces a minimum line-coverage threshold from a Clover coverage report.
 *
 * Usage: php scripts/check-coverage.php <clover.xml> <minimumPercent>
 * Exits with code 1 when coverage is below the threshold, 2 on input errors.
 *
 * PHPUnit has no built-in coverage gate, so CI generates a Clover report
 * (--coverage-clover) and runs this script to fail the build on regressions.
 * The threshold is a floor to ratchet up as coverage improves, not a target.
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
 * @since     2026-06-29
 */

$cloverFile = $argv[1] ?? '';
$minimumCoverage = (float) ($argv[2] ?? '0');

if ($cloverFile === '' || !is_file($cloverFile)) {
    fwrite(STDERR, sprintf("Clover coverage report not found: %s\n", $cloverFile === '' ? '(no path given)' : $cloverFile));
    exit(2);
}

$xml = @simplexml_load_file($cloverFile);
if ($xml === false || !isset($xml->project->metrics)) {
    fwrite(STDERR, sprintf("Could not read <project><metrics> from Clover report: %s\n", $cloverFile));
    exit(2);
}

$metrics = $xml->project->metrics;
$statements = (int) $metrics['statements'];
$coveredStatements = (int) $metrics['coveredstatements'];

if ($statements === 0) {
    fwrite(STDERR, "No statements found in the coverage report; nothing to measure.\n");
    exit(2);
}

$coverage = $coveredStatements / $statements * 100;

printf(
    "Line coverage: %.2f%% (%d/%d statements). Minimum required: %.2f%%.\n",
    $coverage,
    $coveredStatements,
    $statements,
    $minimumCoverage,
);

// Small epsilon so a value printed as the threshold (e.g. 50.00) is not rejected by float noise.
if ($coverage < $minimumCoverage - 0.005) {
    fwrite(STDERR, sprintf("Coverage gate FAILED: %.2f%% is below the required %.2f%%.\n", $coverage, $minimumCoverage));
    exit(1);
}

echo "Coverage gate passed.\n";
exit(0);
