<?php

/**
 * OIDC session helper.
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
 * @since     2026-04-18
 */

declare(strict_types=1);

namespace phpMyFAQ\Auth\Oidc;

use InvalidArgumentException;
use phpMyFAQ\Session\AbstractSession;
use Symfony\Component\HttpFoundation\Session\Session;

final class OidcSession extends AbstractSession
{
    private const string KEY_PREFIX = 'pmf-oidc-';

    private readonly string $provider;

    public function __construct(Session $session, string $provider)
    {
        $provider = trim($provider);
        if ($provider === '') {
            throw new InvalidArgumentException('OidcSession needs a non-empty provider name to scope its keys.');
        }

        parent::__construct($session);
        $this->provider = $provider;
    }

    public function setAuthorizationState(string $state, string $nonce, string $pkceVerifier): void
    {
        $this->set($this->key('state'), $state);
        $this->set($this->key('nonce'), $nonce);
        $this->set($this->key('pkce-code'), $pkceVerifier);
    }

    /**
     * @return array{state: string, nonce: string, verifier: string}
     */
    public function getAuthorizationState(): array
    {
        return [
            'state' => (string) $this->get($this->key('state')),
            'nonce' => (string) $this->get($this->key('nonce')),
            'verifier' => (string) $this->get($this->key('pkce-code')),
        ];
    }

    public function clearAuthorizationState(): void
    {
        $this->set($this->key('state'), '');
        $this->set($this->key('nonce'), '');
        $this->set($this->key('pkce-code'), '');
    }

    public function setIdToken(#[\SensitiveParameter] string $idToken): void
    {
        $this->set($this->key('id-assertion'), $idToken);
    }

    public function getIdToken(): string
    {
        return (string) $this->get($this->key('id-assertion'));
    }

    public function clearIdToken(): void
    {
        $this->set($this->key('id-assertion'), '');
    }

    private function key(string $suffix): string
    {
        return self::KEY_PREFIX . $this->provider . '-' . $suffix;
    }
}
