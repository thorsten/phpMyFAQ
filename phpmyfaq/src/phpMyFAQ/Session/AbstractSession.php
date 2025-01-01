<?php

/**
 * Abstract session class to wrap the Symfony session class.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-02-19
 */

namespace phpMyFAQ\Session;

use phpMyFAQ\Configuration;
use Symfony\Component\HttpFoundation\Session\Session;

class AbstractSession
{
    public function __construct(private readonly Configuration $configuration, private readonly Session $session)
    {
    }

    public function get(string $key): mixed
    {
        return $this->session->get($key);
    }

    public function set(string $key, mixed $value): void
    {
        $this->session->set($key, $value);
    }
}
