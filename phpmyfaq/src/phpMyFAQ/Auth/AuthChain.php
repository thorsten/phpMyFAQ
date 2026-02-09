<?php

/**
 * Authentication chain for API requests.
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
 * @since     2026-02-09
 */

declare(strict_types=1);

namespace phpMyFAQ\Auth;

use phpMyFAQ\User\CurrentUser;
use Symfony\Component\HttpFoundation\Request;

final class AuthChain
{
    private ?int $authenticatedUserIdStorage = null;

    private ?string $authSourceStorage = null;

    public ?int $authenticatedUserId {
        get => $this->authenticatedUserIdStorage;
    }

    public ?string $authSource {
        get => $this->authSourceStorage;
    }

    /** @var callable(Request): ?int|null */
    private $oauth2Authenticator = null;

    public function __construct(
        private readonly CurrentUser $currentUser,
        private readonly ApiKeyAuthenticator $apiKeyAuthenticator,
    ) {
    }

    /**
     * @param callable(Request): ?int $authenticator
     */
    public function setOAuth2Authenticator(callable $authenticator): void
    {
        $this->oauth2Authenticator = $authenticator;
    }

    /**
     * @param string[] $requiredScopes
     */
    public function authenticate(Request $request, array $requiredScopes = []): bool
    {
        $this->authenticatedUserIdStorage = null;
        $this->authSourceStorage = null;

        if ($this->currentUser->isLoggedIn()) {
            $this->authenticatedUserIdStorage = $this->currentUser->getUserId();
            $this->authSourceStorage = 'session';
            return true;
        }

        if ($this->apiKeyAuthenticator->authenticate($request, $requiredScopes)) {
            $this->authenticatedUserIdStorage = $this->apiKeyAuthenticator->getAuthenticatedUserId();
            $this->authSourceStorage = 'api_key';
            return $this->authenticatedUserIdStorage !== null;
        }

        if (is_callable($this->oauth2Authenticator)) {
            $userId = ($this->oauth2Authenticator)($request);
            if ($userId !== null) {
                $this->authenticatedUserIdStorage = $userId;
                $this->authSourceStorage = 'oauth2';
                return true;
            }
        }

        return false;
    }

    public function getAuthenticatedUserId(): ?int
    {
        return $this->authenticatedUserId;
    }

    public function getAuthSource(): ?string
    {
        return $this->authSource;
    }
}
