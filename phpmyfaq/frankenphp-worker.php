<?php

/**
 * FrankenPHP worker script (experimental).
 *
 * One PHP process serves many requests: FrankenPHP calls the handler below for
 * every request instead of starting a new PHP process. The handler resets the
 * request-scoped singletons, picks the front controller the request path maps
 * to (the rewrite rules of the Caddyfile are mirrored in FrontControllerResolver)
 * and runs it. Enable it with FRANKENPHP_WORKER_MODE=true in the Docker images or
 * with `php_server { worker /var/www/html/frankenphp-worker.php }` in a Caddyfile.
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

use phpMyFAQ\Bootstrap\FrontControllerResolver;
use phpMyFAQ\Bootstrap\MultisiteInstances;
use phpMyFAQ\Bootstrap\WorkerState;

// Requested directly (e.g. through Apache, or via Caddy outside worker mode): nothing to serve.
if (PHP_SAPI !== 'frankenphp' || !function_exists('frankenphp_handle_request')) {
    http_response_code(404);
    exit();
}

require __DIR__ . '/src/constants.php';
require __DIR__ . '/src/libs/autoload.php';

// Worker mode is not supported with multisite: the instance of a request is selected through constants,
// which keep the value of the first request for the lifetime of this process.
$servesMultisiteInstances = new MultisiteInstances(__DIR__ . '/multisite')->exist();
if ($servesMultisiteInstances) {
    error_log(
        'phpMyFAQ: FrankenPHP worker mode is not supported with multisite instances. Disable worker '
        . 'mode (FRANKENPHP_WORKER_MODE=false) or remove the instance directories from phpmyfaq/multisite.',
    );
}

$handler = static function () use ($servesMultisiteInstances): void {
    if ($servesMultisiteInstances) {
        http_response_code(503);
        return;
    }

    WorkerState::reset();

    try {
        $frontController = FrontControllerResolver::resolve((string) ($_SERVER['REQUEST_URI'] ?? '/'));
    } catch (InvalidArgumentException) {
        http_response_code(403);
        return;
    }

    $script = __DIR__ . '/' . $frontController;
    if (!is_file($script)) {
        http_response_code(404);
        return;
    }

    // The front controllers and Symfony's Request derive the base path from these variables.
    $_SERVER['SCRIPT_FILENAME'] = $script;
    $_SERVER['SCRIPT_NAME'] = '/' . $frontController;
    $_SERVER['PHP_SELF'] = '/' . $frontController;

    require $script;
};

// Restart the worker after a fixed number of requests to limit memory growth; 0 means never.
$maxRequests = (int) ($_SERVER['MAX_REQUESTS'] ?? 0);
$handled = 0;

while ($maxRequests === 0 || $handled < $maxRequests) {
    $keepRunning = \frankenphp_handle_request($handler);
    $handled++;
    gc_collect_cycles();

    if (!$keepRunning) {
        break;
    }
}
