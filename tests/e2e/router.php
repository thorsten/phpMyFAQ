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

$root = dirname(__DIR__, 2) . '/phpmyfaq';
$path = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$candidate = realpath($root . $path);

// Serve existing static assets (css, js, images, fonts, ...) straight from disk.
if (
    $candidate !== false
    && is_file($candidate)
    && str_starts_with($candidate, $root)
    && !str_ends_with($candidate, '.php')
) {
    return false;
}

$dispatch = static function (string $frontController) use ($root): void {
    $_SERVER['SCRIPT_NAME'] = $frontController;
    $_SERVER['PHP_SELF'] = $frontController;
    $_SERVER['SCRIPT_FILENAME'] = $root . $frontController;
    require $root . $frontController;
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
