#!/usr/bin/env php
<?php

/**
 * Enforces a per-class method-coverage floor from a Clover coverage report.
 *
 * Usage: php scripts/check-class-coverage.php <clover.xml> [minimumPercent] [allowlist]
 *   --report   list every class below the floor and exit 0 (no gating)
 *
 * Exits 1 when the gate fails, 2 on input errors.
 *
 * Project-wide line coverage hides classes with untested methods, because a
 * well-covered long class offsets a barely-touched short one. This gate works
 * per class instead: every class must cover at least <minimumPercent> of its
 * own methods.
 *
 * Classes listed in the allowlist may sit below the floor. The file is a
 * ratchet: an entry that starts passing is reported as stale and fails the
 * build, so the list can only ever shrink. Delete it once it is empty.
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
 * @since     2026-08-21
 */

$arguments = array_values(array_filter(
    array_slice($argv, 1),
    static fn(string $argument): bool => !str_starts_with($argument, '--'),
));
$reportOnly = in_array('--report', array_slice($argv, 1), true);

$cloverFile = $arguments[0] ?? '';
$minimumCoverage = (float) ($arguments[1] ?? '80');
$allowlistFile = $arguments[2] ?? __DIR__ . '/../tests/coverage-allowlist.txt';

if ($cloverFile === '' || !is_file($cloverFile)) {
    fwrite(STDERR, sprintf("Clover coverage report not found: %s\n", $cloverFile === '' ? '(no path given)' : $cloverFile));
    exit(2);
}

$xml = @simplexml_load_file($cloverFile);
if ($xml === false || !isset($xml->project)) {
    fwrite(STDERR, sprintf("Could not read <project> from Clover report: %s\n", $cloverFile));
    exit(2);
}

/**
 * Collects per-class method counts from every <file> in the report.
 *
 * Clover records one <metrics> element per class plus one per file. Only the
 * class-level element carries the method counts we need, so the file element
 * is skipped.
 *
 * @return array<string, array{name: string, covered: int, total: int}>
 */
function collectClasses(SimpleXMLElement $project): array
{
    $classes = [];

    foreach ($project->xpath('//file') ?: [] as $file) {
        $path = (string) $file['name'];

        foreach ($file->class as $class) {
            $metrics = $class->metrics;
            if ($metrics === null) {
                continue;
            }

            $total = (int) $metrics['methods'];
            // Interfaces, marker traits and pure exception subclasses have no
            // executable methods; they can never fail a percentage gate.
            if ($total === 0) {
                continue;
            }

            $namespace = (string) $class['namespace'];
            $name = (string) $class['name'];
            // php-code-coverage already writes @name fully qualified; older
            // Clover writers emit a bare name plus a separate @namespace.
            $fqcn = str_contains($name, '\\') || $namespace === '' || $namespace === 'global'
                ? $name
                : $namespace . '\\' . $name;

            $classes[$fqcn] = [
                'name' => $fqcn,
                'path' => $path,
                'covered' => (int) $metrics['coveredmethods'],
                'total' => $total,
            ];
        }
    }

    ksort($classes);

    return $classes;
}

/**
 * Reads allowlisted class names, ignoring blank lines and # comments.
 *
 * @return array<int, string>
 */
function readAllowlist(string $file): array
{
    if (!is_file($file)) {
        return [];
    }

    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];

    return array_values(array_filter(array_map(
        static fn(string $line): string => trim($line),
        $lines,
    ), static fn(string $line): bool => $line !== '' && !str_starts_with($line, '#')));
}

$classes = collectClasses($xml->project);

if ($classes === []) {
    fwrite(STDERR, "No classes with methods found in the coverage report; nothing to measure.\n");
    exit(2);
}

$allowed = readAllowlist($allowlistFile);
$allowedIndex = array_fill_keys($allowed, true);

$below = [];
$totalMethods = 0;
$totalCovered = 0;

foreach ($classes as $class) {
    $totalMethods += $class['total'];
    $totalCovered += $class['covered'];

    $percent = $class['covered'] / $class['total'] * 100;
    if ($percent < $minimumCoverage - 0.005) {
        $class['percent'] = $percent;
        $class['gain'] = (int) ceil($minimumCoverage / 100 * $class['total']) - $class['covered'];
        $below[] = $class;
    }
}

usort($below, static fn(array $a, array $b): int => $b['gain'] <=> $a['gain'] ?: strcmp($a['name'], $b['name']));

printf(
    "Method coverage: %.2f%% (%d/%d methods across %d classes). Per-class floor: %.2f%%.\n",
    $totalMethods > 0 ? $totalCovered / $totalMethods * 100 : 0.0,
    $totalCovered,
    $totalMethods,
    count($classes),
    $minimumCoverage,
);

$notAllowed = array_values(array_filter(
    $below,
    static fn(array $class): bool => !isset($allowedIndex[$class['name']]),
));

$belowIndex = array_fill_keys(array_column($below, 'name'), true);
$stale = array_values(array_filter(
    $allowed,
    static fn(string $name): bool => !isset($belowIndex[$name]),
));

if ($reportOnly) {
    printf("\n%d classes below the floor (need +%d covered methods):\n\n", count($below), array_sum(array_column($below, 'gain')));
    foreach ($below as $class) {
        printf("  %-6s %6.2f%%  %3d/%-3d  +%-3d  %s\n", isset($allowedIndex[$class['name']]) ? 'allow' : 'FAIL', $class['percent'], $class['covered'], $class['total'], $class['gain'], $class['name']);
    }
    exit(0);
}

printf("%d classes below the floor, %d of them allowlisted.\n", count($below), count($below) - count($notAllowed));

if ($notAllowed !== []) {
    fwrite(STDERR, sprintf("\nClass coverage gate FAILED: %d class(es) below %.2f%% and not allowlisted:\n", count($notAllowed), $minimumCoverage));
    foreach ($notAllowed as $class) {
        fwrite(STDERR, sprintf("  %6.2f%%  %d/%d methods  (+%d needed)  %s\n", $class['percent'], $class['covered'], $class['total'], $class['gain'], $class['name']));
    }
    fwrite(STDERR, "\nAdd tests for these classes. Do not add them to the allowlist.\n");
}

if ($stale !== []) {
    fwrite(STDERR, sprintf("\nStale allowlist entries: %d class(es) now meet the floor and must be removed from %s:\n", count($stale), $allowlistFile));
    foreach ($stale as $name) {
        fwrite(STDERR, sprintf("  %s\n", $name));
    }
}

if ($notAllowed !== [] || $stale !== []) {
    exit(1);
}

echo "Class coverage gate passed.\n";
exit(0);
