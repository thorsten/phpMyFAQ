<?php

declare(strict_types=1);

/**
 * Session wrapper to use Symfony Session instead of direct $_SESSION access.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2025-08-04
 */

namespace phpMyFAQ\Session;

use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\PhpBridgeSessionStorage;

class SessionWrapper
{
    private Session $session;

    public function __construct(?Session $session = null)
    {
        if (!$session instanceof Session) {
            // If no session is provided, create one with PhpBridgeSessionStorage
            // This connects to the existing PHP session
            $this->session = new Session(new PhpBridgeSessionStorage());
            if (!$this->session->isStarted()) {
                $this->session->start();
            }
        } else {
            $this->session = $session;
        }
    }

    /**
     * Get a value from the session
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->session->get($key, $default);
    }

    /**
     * Set a value in the session
     */
    public function set(string $key, mixed $value): void
    {
        $this->session->set($key, $value);
    }

    /**
     * Check if a key exists in the session
     */
    public function has(string $key): bool
    {
        return $this->session->has($key);
    }

    /**
     * Remove a key from the session
     */
    public function remove(string $key): mixed
    {
        return $this->session->remove($key);
    }

    /**
     * Get the underlying Symfony Session instance
     */
    public function getSession(): Session
    {
        return $this->session;
    }
}
