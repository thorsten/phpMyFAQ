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
 * @copyright 2009-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2009-03-01
 */

namespace phpMyFAQ\Auth;

use phpMyFAQ\Auth;
use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Enums\AuthenticationSourceType;
use phpMyFAQ\User;
use SensitiveParameter;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class AuthHttp
 *
 * @package phpMyFAQ\Auth
 */
class AuthHttp extends Auth implements AuthDriverInterface
{
    private readonly Request $request;

    /**
     * @inheritDoc
     */
    public function __construct(Configuration $configuration)
    {
        parent::__construct($configuration);

        $this->request = Request::createFromGlobals();
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function create(string $login, #[SensitiveParameter] string $password, string $domain = ''): bool
    {
        $user = new User($this->configuration);
        $user->createUser($login);

        $user->setStatus('active');
        $user->setAuthSource(AuthenticationSourceType::AUTH_HTTP->value);
        $user->setUserData(['display_name' => $login]);

        return true;
    }

    /**
     * @inheritDoc
     */
    public function update(string $login, #[SensitiveParameter] string $password): bool
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
    public function checkCredentials(string $login, #[SensitiveParameter] $password, ?array $optionalData = null): bool
    {
        if ($this->request->server->get('PHP_AUTH_USER') === null && $this->request->server->get('PHP_AUTH_PW')) {
            return false;
        }

        return (
            $this->request->server->get('PHP_AUTH_USER') === $login &&
            $this->request->server->get('PHP_AUTH_PW') === $password
        );
    }

    /**
     * @inheritDoc
     */
    public function isValidLogin($login, ?array $optionalData = null): int
    {
        return $this->request->server->get('PHP_AUTH_USER') !== null ? 1 : 0;
    }
}
