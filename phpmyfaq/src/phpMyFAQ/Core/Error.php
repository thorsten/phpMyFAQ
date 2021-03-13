<?php

/**
 * phpMyFAQ main error class.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2020-2021 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2020-11-13
 */

namespace phpMyFAQ\Core;

use ErrorException;

/**
 * Class Error
 *
 * @package phpMyFAQ
 */
class Error
{
    /**
     * Error handler to convert all errors to PHP exceptions by
     * throwing a PHP ErrorException.
     *
     * @param int    $level
     * @param string $message
     * @param string $filename
     * @param int    $line
     * @throws ErrorException
     */
    public static function errorHandler(int $level, string $message, string $filename, int $line)
    {
        if (error_reporting() !== 0) {
            $filename = (DEBUG ? $filename : basename($filename));
            throw new ErrorException($message, 0, $level, $filename, $line);
        }
    }

    /**
     * Exception handler.
     *
     * @param $exception
     */
    public static function exceptionHandler($exception)
    {
        $code = $exception->getCode();
        if ($code !== 404) {
            $code = 500;
        }
        http_response_code($code);

        if (DEBUG) {
            echo "<h1>phpMyFAQ Fatal error</h1>";
            echo "<p>Uncaught exception: '" . get_class($exception) . "'</p>";
            echo "<p>Message: '" . $exception->getMessage() . "'</p>";
            echo "<p>Stack trace:<pre>" . $exception->getTraceAsString() . "</pre></p>";
            echo "<p>Thrown in '" . $exception->getFile() . "' on line " . $exception->getLine() . "</p>";
        }
        if (ini_get('log_errors')) {
            error_log(
                sprintf(
                    "phpMyFAQ %s: %s in %s on line %d\nStack trace:\n%s",
                    get_class($exception),
                    $exception->getMessage(),
                    $exception->getFile(),
                    $exception->getLine(),
                    $exception->getTraceAsString()
                )
            );
        }
    }
}
