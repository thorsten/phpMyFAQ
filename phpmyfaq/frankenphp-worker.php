<?php
/**
 * FrankenPHP Worker for phpMyFAQ
 * 
 * This worker script enables FrankenPHP's worker mode for better performance.
 * It preloads phpMyFAQ's bootstrap and handles requests in a long-running process.
 * 
 * @package phpMyFAQ
 *  @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 *  @copyright 2025 phpMyFAQ Team
 *  @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *  @link      https://www.phpmyfaq.de
 *  @since     2025-07-06
 */

// Include phpMyFAQ bootstrap (sets up error handling via Environment)
require_once __DIR__ . '/src/Bootstrap.php';

// FrankenPHP worker loop
while ($worker = frankenphp_handle_request()) {
    // The worker will handle each request here
    // the main application will handle phpMyFAQ's routing and processing
    // through the Bootstrap.php inclusion
}
