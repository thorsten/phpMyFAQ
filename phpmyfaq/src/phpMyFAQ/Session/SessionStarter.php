<?php

/**
 * Starts the native PHP session and recovers from session data that cannot be decoded.
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
 * @since     2026-10-05
 */

declare(strict_types=1);

namespace phpMyFAQ\Session;

use Throwable;

final class SessionStarter
{
    /**
     * Starts the PHP session with the given options.
     *
     * When the stored session payload cannot be decoded, PHP raises a warning, which phpMyFAQ's
     * error handler turns into an ErrorException, or an exception is thrown from the
     * __unserialize() method of a stored object. This happens e.g. when the payload was truncated
     * because a previous request failed to serialize it, or when it was written by another
     * phpMyFAQ release whose stored objects can no longer be unserialized. Instead of failing the
     * whole request, the broken session data is discarded and a fresh session is started.
     *
     * @param array<string, mixed> $options
     */
    public static function start(array $options): void
    {
        try {
            session_start($options);
        } catch (Throwable $throwable) {
            self::discardBrokenSession($throwable);
            session_start($options);
        }
    }

    private static function discardBrokenSession(Throwable $throwable): void
    {
        if (ini_get('log_errors')) {
            error_log(sprintf('phpMyFAQ: discarded undecodable session data: %s', $throwable->getMessage()));
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            session_destroy();
        }
    }
}
