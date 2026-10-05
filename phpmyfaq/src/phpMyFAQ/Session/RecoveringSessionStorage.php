<?php

/**
 * Native PHP session storage that recovers from session data that cannot be decoded.
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

use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage;
use Throwable;

final class RecoveringSessionStorage extends NativeSessionStorage
{
    /**
     * Starts the PHP session and discards stored session data that cannot be decoded.
     *
     * When the stored session payload cannot be decoded, PHP raises a warning, which phpMyFAQ's
     * error handler turns into an ErrorException, or an exception is thrown from the
     * __unserialize() method of a stored object. This happens e.g. when the payload was truncated
     * because a previous request failed to serialize it, or when it was written by another
     * phpMyFAQ release whose stored objects can no longer be unserialized. Instead of failing
     * every request until the visitor clears the cookie, the broken session data is discarded
     * and a fresh session is started.
     *
     * The states Symfony reports itself (storage already started, PHP session already active,
     * headers already sent) are not recoverable here and are left to the parent.
     */
    public function start(): bool
    {
        if ($this->isStarted() || session_status() === PHP_SESSION_ACTIVE || headers_sent()) {
            return parent::start();
        }

        try {
            return parent::start();
        } catch (Throwable $throwable) {
            $this->discardBrokenSession($throwable);

            return parent::start();
        }
    }

    /**
     * PHP aborts session_start() before the session becomes active when the payload cannot be
     * decoded, and the stored data stays on disk, so retrying under the cookie's id would read
     * the same broken payload again. The retry therefore runs under a fresh session id; the
     * browser receives the new cookie, and the broken data is left to the session garbage
     * collection. A session that did become active is destroyed, which also removes its data.
     */
    private function discardBrokenSession(Throwable $throwable): void
    {
        if (ini_get('log_errors')) {
            error_log(sprintf('phpMyFAQ: discarded undecodable session data: %s', $throwable->getMessage()));
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            session_destroy();
        }

        $freshId = session_create_id();
        if (is_string($freshId)) {
            session_id($freshId);
        }
    }
}
