<?php

/**
 * phpMyFAQ main error class.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2020-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2020-11-13
 */

declare(strict_types=1);

namespace phpMyFAQ\Core;

use ErrorException;
use phpMyFAQ\Controller\Frontend\ErrorController;
use phpMyFAQ\Environment;
use phpMyFAQ\Strings;

/**
 * Class Error
 *
 * @package phpMyFAQ
 */
class Error
{
    /**
     * Error handler to convert all errors to PHP exceptions by
     * throwing a PHP ErrorException. Deprecation notices are only
     * logged, so they never crash the application.
     *
     * @throws ErrorException
     */
    public static function errorHandler(int $level, string $message, string $filename, int $line): void
    {
        if (error_reporting() === 0) {
            return;
        }

        if ($level === E_DEPRECATED || $level === E_USER_DEPRECATED) {
            if (ini_get('log_errors')) {
                error_log(sprintf('phpMyFAQ Deprecation: %s in %s on line %d', $message, $filename, $line));
            }

            return;
        }

        $filename = Environment::isDebugMode() ? $filename : basename($filename);
        throw new ErrorException($message, 0, $level, $filename, $line);
    }

    /**
     * Exception handler. The exception details (class, message, stack trace,
     * absolute file path) are only rendered in debug mode; in production they
     * are written to the error log and the client gets a generic error page.
     */
    public static function exceptionHandler(\Throwable $exception): void
    {
        $code = $exception->getCode();
        if ($code !== 404) {
            $code = 500;
        }

        http_response_code($code);

        if (ini_get('log_errors')) {
            error_log(sprintf(
                "phpMyFAQ %s: %s in %s on line %d\nStack trace:\n%s",
                $exception::class,
                $exception->getMessage(),
                $exception->getFile(),
                $exception->getLine(),
                $exception->getTraceAsString(),
            ));
        }

        echo Environment::isDebugMode() ? self::renderDebugOutput($exception) : self::renderGenericOutput($code);
    }

    private static function renderDebugOutput(\Throwable $exception): string
    {
        return (
            '<h1>phpMyFAQ Fatal error</h1>'
            . "<p>Uncaught exception: '"
            . $exception::class
            . "'</p>"
            . "<p>Message: '"
            . Strings::htmlentities($exception->getMessage())
            . "'</p>"
            . '<p>Stack trace:<pre>'
            . Strings::htmlentities($exception->getTraceAsString())
            . '</pre></p>'
            . "<p>Thrown in '"
            . Strings::htmlentities($exception->getFile())
            . "' on line "
            . $exception->getLine()
            . '</p>'
        );
    }

    /**
     * Renders the generic error page without any exception details. The
     * styled template is reused for server errors; if rendering it fails
     * (e.g. the exception was thrown before the template engine is usable)
     * a minimal fixed HTML message is returned instead.
     */
    private static function renderGenericOutput(int $code): string
    {
        if ($code === 500) {
            try {
                return (string) ErrorController::renderBootstrapError()->getContent();
            } catch (\Throwable) {
                /* @mago-expect lint:no-empty-catch-clause - the template engine may itself be the cause of the
                 * fatal error; the fixed minimal message below is the fallback */
            }
        }

        $message = $code === 404 ? 'The requested page could not be found.' : 'An internal error occurred.';

        return (
            '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><title>phpMyFAQ</title></head>'
            . '<body><h1>phpMyFAQ</h1><p>'
            . $message
            . '</p></body></html>'
        );
    }
}
