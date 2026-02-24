<?php

/**
 * PHP runtime configuration for phpMyFAQ bootstrap
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2012-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-02-08
 */

declare(strict_types=1);

namespace phpMyFAQ\Bootstrap;

use phpMyFAQ\Configuration;
use phpMyFAQ\Session\RedisSessionHandler;
use RuntimeException;

class PhpConfigurator
{
    /**
     * Ensures '.' is in the PHP include path.
     */
    public static function fixIncludePath(): void
    {
        $includePaths = explode(PATH_SEPARATOR, ini_get('include_path'));
        if (!in_array('.', $includePaths, strict: true)) {
            set_include_path('.' . PATH_SEPARATOR . get_include_path());
        }
    }

    /**
     * Increases PCRE limits to handle large content.
     */
    public static function configurePcre(): void
    {
        self::setIniOption('pcre.backtrack_limit', '100000000');
        self::setIniOption('pcre.recursion_limit', '100000000');
    }

    /**
     * Registers the phpMyFAQ error and exception handlers.
     */
    public static function registerErrorHandlers(): void
    {
        set_error_handler('\\phpMyFAQ\\Core\\Error::errorHandler');
        set_exception_handler('\\phpMyFAQ\\Core\\Error::exceptionHandler');
    }

    /**
     * Configures secure session settings if no session is active yet.
     */
    public static function configureSession(?Configuration $configuration = null): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            self::setIniOption('session.use_only_cookies', '1');
            self::setIniOption('session.use_trans_sid', '0');
            self::setIniOption('session.cookie_samesite', 'Strict');
            self::setIniOption('session.cookie_httponly', '1');
            self::setIniOption('session.cookie_secure', '1');

            if (defined('PMF_SESSION_SAVE_PATH') && PMF_SESSION_SAVE_PATH !== '') {
                self::setIniOption('session.save_path', PMF_SESSION_SAVE_PATH);
            }

            $sessionHandler = strtolower((string) ($configuration?->get('session.handler') ?? 'files'));
            $redisDsn = trim((string) ($configuration?->get('session.redisDsn') ?? ''));

            switch ($sessionHandler) {
                case 'files':
                    self::setIniOption('session.save_handler', 'files');
                    break;
                case 'redis':
                    RedisSessionHandler::configure($redisDsn);
                    break;
                default:
                    throw new RuntimeException(sprintf(
                        'Unsupported session handler "%s". Allowed values: files, redis.',
                        $sessionHandler,
                    ));
            }
        }
    }

    private static function setIniOption(string $option, string $value): void
    {
        $setter = 'ini_set';
        if (!function_exists($setter)) {
            return;
        }

        $setter($option, $value);
    }
}
