<?php

/**
 * FrankenPHP Worker for phpMyFAQ
 * This worker script enables FrankenPHP's worker mode for better performance.
 * It preloads phpMyFAQ's bootstrap and handles requests in a long-running process.
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2025-07-06
 */

// Set up error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include phpMyFAQ bootstrap
require_once __DIR__ . '/src/Bootstrap.php';

// FrankenPHP worker loop
while ($worker = \frankenphp_handle_request()) {
    // The worker will handle each request here
    // phpMyFAQ's routing and processing will be handled by the main application
    // through the Bootstrap.php inclusion
}
