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
        ini_set('pcre.backtrack_limit', value: '100000000');
        ini_set('pcre.recursion_limit', value: '100000000');
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
            ini_set('session.use_only_cookies', value: '1');
            ini_set('session.use_trans_sid', value: '0');
            ini_set('session.cookie_samesite', value: 'Strict');
            ini_set('session.cookie_httponly', value: '1');
            ini_set('session.cookie_secure', value: '1');

            if (defined('PMF_SESSION_SAVE_PATH') && PMF_SESSION_SAVE_PATH !== '') {
                ini_set('session.save_path', value: PMF_SESSION_SAVE_PATH);
            }

            $sessionHandler = strtolower((string) ($configuration?->get('session.handler') ?? 'files'));
            $redisDsn = trim((string) ($configuration?->get('session.redisDsn') ?? ''));

            switch ($sessionHandler) {
                case 'files':
                    ini_set('session.save_handler', value: 'files');
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
}
