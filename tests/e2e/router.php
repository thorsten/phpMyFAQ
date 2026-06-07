<?php

/**
 * Router script for the PHP built-in web server (`php -S`) used by the e2e suite.
 *
 * phpMyFAQ ships several front controllers (public, admin, API) that, under
 * Apache/nginx, live in sub-directories and rely on the request base path. This
 * router reproduces that layout for `php -S`: it serves real static files
 * directly and otherwise dispatches to the matching front controller, faking
 * SCRIPT_NAME so Symfony derives the correct base path (e.g. `/admin`).
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @copyright 2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 */

declare(strict_types=1);

$canonicalRoot = realpath(dirname(__DIR__, 2) . '/phpmyfaq');
if ($canonicalRoot === false) {
    // The document root must resolve; fail fast rather than fall back to a root
    // of "/" that would defeat the containment check below.
    http_response_code(500);
    exit("e2e router: document root could not be resolved\n");
}
// Trailing separator so a sibling directory sharing the prefix (e.g.
// ".../phpmyfaq-x") cannot pass the containment check below.
$rootWithSep = rtrim($canonicalRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
$path = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$candidate = realpath($canonicalRoot . $path);

// Serve existing static assets (css, js, images, fonts, ...) straight from disk.
if (
    $candidate !== false
    && is_file($candidate)
    && str_starts_with($candidate, $rootWithSep)
    && !str_ends_with($candidate, '.php')
) {
    return false;
}

$dispatch = static function (string $frontController) use ($canonicalRoot): void {
    $_SERVER['SCRIPT_NAME'] = $frontController;
    $_SERVER['PHP_SELF'] = $frontController;
    $_SERVER['SCRIPT_FILENAME'] = $canonicalRoot . $frontController;
    require $canonicalRoot . $frontController;
};

if (str_starts_with($path, '/admin/api')) {
    $dispatch('/admin/api/index.php');
} elseif (str_starts_with($path, '/admin')) {
    $dispatch('/admin/index.php');
} elseif (str_starts_with($path, '/api')) {
    $dispatch('/api/index.php');
} else {
    $dispatch('/index.php');
}
