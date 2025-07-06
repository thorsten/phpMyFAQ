<?php
/**
 * FrankenPHP Worker for phpMyFAQ
 * 
 * This worker script enables FrankenPHP's worker mode for better performance.
 * It preloads phpMyFAQ's bootstrap and handles requests in a long-running process.
 * 
 * @package phpMyFAQ
 * @author  phpMyFAQ Team
 * @since   2025-01-01
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