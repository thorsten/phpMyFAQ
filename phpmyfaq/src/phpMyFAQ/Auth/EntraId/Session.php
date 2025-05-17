<?php

/**
 * Session class for Entra ID.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2024-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-11-02
 */

namespace phpMyFAQ\Auth\EntraId;

use Exception;
use phpMyFAQ\Configuration;
use phpMyFAQ\Session\AbstractSession;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session as SymfonySession;
use Symfony\Component\Uid\Uuid;

class Session extends AbstractSession
{
    /** @var string EntraID session key */
    final public const string ENTRA_ID_SESSION_KEY = 'pmf-entra-id-session-key';

    /** @var string */
    final public const string ENTRA_ID_OAUTH_VERIFIER = 'pmf-entra-id-oauth-verifier';

    /** @var string */
    final public const string ENTRA_ID_JWT = 'pmf-entra-id-jwt';

    private string $currentSessionKey;

    public function __construct(private readonly Configuration $configuration, private readonly SymfonySession $session)
    {
        parent::__construct($session);

        $this->createCurrentSessionKey();
    }

    /**
     * Creates the current UUID session key
     */
    public function createCurrentSessionKey(): void
    {
        $this->currentSessionKey = Uuid::v4();
    }

    /**
     * Returns the current UUID session key
     */
    public function getCurrentSessionKey(): ?string
    {
        return $this->currentSessionKey ?? $this->session->get(self::ENTRA_ID_SESSION_KEY);
    }

    /**
     * Sets the current UUID session key
     *
     * @throws Exception
     */
    public function setCurrentSessionKey(): Session
    {
        if (!isset($this->currentSessionKey)) {
            $this->createCurrentSessionKey();
        }

        $this->session->set(self::ENTRA_ID_SESSION_KEY, $this->currentSessionKey);

        return $this;
    }

    /**
     * Store the Session ID into a persistent cookie expiring
     * 3600 seconds after the page request.
     *
     * @param string          $name Cookie name
     * @param int|string|null $sessionId Session ID
     * @param int             $timeout Cookie timeout
     */
    public function setCookie(string $name, int|string|null $sessionId, int $timeout = 3600, bool $strict = true): void
    {
        $request = Request::createFromGlobals();

        Cookie::create($name)
            ->withValue($sessionId ?? '')
            ->withExpires($request->server->get('REQUEST_TIME') + $timeout)
            ->withPath(dirname($request->server->get('SCRIPT_NAME')))
            ->withDomain(parse_url($this->configuration->getDefaultUrl(), PHP_URL_HOST))
            ->withSameSite($strict ? 'strict' : '')
            ->withSecure($request->isSecure())
            ->withHttpOnly();
    }

    /**
     * Returns the value of a cookie.
     *
     * @param string $name Cookie name
     */
    public function getCookie(string $name): string
    {
        $request = Request::createFromGlobals();
        return $request->cookies->get($name, '');
    }
}
