<?php

/**
 * Maps a request URI to the phpMyFAQ front controller that serves it.
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
 * @since     2026-10-25
 */

declare(strict_types=1);

namespace phpMyFAQ\Bootstrap;

use InvalidArgumentException;

/**
 * In FrankenPHP worker mode every PHP request reaches the single worker script, so the rewrite rules of the
 * Caddyfile (and of .htaccess and the nginx configuration) have to be applied in PHP: the request path decides
 * which front controller handles the request.
 */
final readonly class FrontControllerResolver
{
    public const string WORKER_SCRIPT = 'frankenphp-worker.php';

    /**
     * Returns the front controller path relative to the phpMyFAQ root directory, e.g. "admin/index.php".
     *
     * @throws InvalidArgumentException for paths that must never be executed
     */
    public static function resolve(string $requestUri): string
    {
        $path = (string) parse_url($requestUri, PHP_URL_PATH);
        $path = '/' . ltrim($path, characters: '/');

        if ($path === '/admin/api' || str_starts_with($path, '/admin/api/')) {
            return 'admin/api/index.php';
        }

        if ($path === '/admin' || str_starts_with($path, '/admin/')) {
            return 'admin/index.php';
        }

        if ($path === '/api' || str_starts_with($path, '/api/')) {
            return 'api/index.php';
        }

        if ($path === '/setup' || str_starts_with($path, '/setup/')) {
            return 'setup/index.php';
        }

        if (str_ends_with($path, '.php')) {
            return self::phpScript($path);
        }

        return 'index.php';
    }

    private static function phpScript(string $path): string
    {
        $script = ltrim($path, characters: '/');

        if (in_array('..', explode('/', $script), strict: true)) {
            throw new InvalidArgumentException(sprintf('Refusing to execute "%s": directory traversal.', $path));
        }

        if ($script === self::WORKER_SCRIPT) {
            throw new InvalidArgumentException('The worker script must not be requested directly.');
        }

        return $script;
    }
}
