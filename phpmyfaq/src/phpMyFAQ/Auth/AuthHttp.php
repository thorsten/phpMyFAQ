<?php

/**
 * Manages user authentication with Apache's HTTP authentication.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Alberto Cabello <alberto@unex.es>
 * @copyright 2009-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2009-03-01
 */

namespace phpMyFAQ\Auth;

use phpMyFAQ\Auth;
use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\User;

/**
 * Class AuthHttp
 *
 * @package phpMyFAQ\Auth
 */
class AuthHttp extends Auth implements AuthDriverInterface
{
    /**
     * @inheritDoc
     */
    public function __construct(Configuration $config)
    {
        parent::__construct($config);
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function create(string $login, string $password, string $domain = ''): bool
    {
        $user = new User($this->config);
        $result = $user->createUser($login);

        $user->setStatus('active');
        $user->setUserData(['display_name' => $login]);

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function update(string $login, string $password): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function delete($login): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function checkCredentials($login, $password, array $optionalData = null): bool
    {
        if (!isset($_SERVER['PHP_AUTH_USER']) && $_SERVER['PHP_AUTH_PW']) {
            return false;
        } else {
            return ($_SERVER['PHP_AUTH_USER'] === $login && $_SERVER['PHP_AUTH_PW'] === $password);
        }
    }

    /**
     * @inheritDoc
     */
    public function isValidLogin($login, array $optionalData = null): int
    {
        return isset($_SERVER['PHP_AUTH_USER']) ? 1 : 0;
    }
}
