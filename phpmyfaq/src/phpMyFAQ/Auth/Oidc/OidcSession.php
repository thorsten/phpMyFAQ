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

use phpMyFAQ\Session\AbstractSession;
use Symfony\Component\HttpFoundation\Session\Session;

final class OidcSession extends AbstractSession
{
    final public const string OIDC_STATE = 'pmf-oidc-state';
    final public const string OIDC_NONCE = 'pmf-oidc-nonce';
    final public const string OIDC_PKCE_CODE = 'pmf-oidc-pkce-code';
    final public const string OIDC_ID_ASSERTION = 'pmf-oidc-id-assertion';

    public function __construct(Session $session)
    {
        parent::__construct($session);
    }

    public function setAuthorizationState(string $state, string $nonce, string $pkceVerifier): void
    {
        $this->set(self::OIDC_STATE, $state);
        $this->set(self::OIDC_NONCE, $nonce);
        $this->set(self::OIDC_PKCE_CODE, $pkceVerifier);
    }

    /**
     * @return array{state: string, nonce: string, verifier: string}
     */
    public function getAuthorizationState(): array
    {
        return [
            'state' => (string) $this->get(self::OIDC_STATE),
            'nonce' => (string) $this->get(self::OIDC_NONCE),
            'verifier' => (string) $this->get(self::OIDC_PKCE_CODE),
        ];
    }

    public function clearAuthorizationState(): void
    {
        $this->set(self::OIDC_STATE, '');
        $this->set(self::OIDC_NONCE, '');
        $this->set(self::OIDC_PKCE_CODE, '');
    }
}
