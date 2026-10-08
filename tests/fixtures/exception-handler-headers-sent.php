<?php

/**
 * Fixture for ErrorTest: runs Error::exceptionHandler() in a fresh CLI process
 * after output has already been sent, i.e. the situation of an exception
 * thrown during session_write_close() at shutdown.
 */

declare(strict_types=1);

use phpMyFAQ\Core\Error;

require dirname(__DIR__, 2) . '/phpmyfaq/src/libs/autoload.php';

ini_set('display_errors', '1');
ini_set('log_errors', '0');
error_reporting(E_ALL);
set_error_handler('\\phpMyFAQ\\Core\\Error::errorHandler');

// In the CLI SAPI the headers are sent together with the first byte of output
echo "page output\n";

Error::exceptionHandler(new RuntimeException('late failure'));

echo "\nHANDLER RETURNED\n";
