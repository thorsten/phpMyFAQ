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
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;

final class OidcSession extends AbstractSession
{
    private const string KEY_PREFIX = 'pmf-oidc-';

    /** Seconds the visitor has to finish the login at the identity provider. */
    private const int FALLBACK_COOKIE_LIFETIME = 1800;

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
     * Returns the pending authorization state. The session cookie is SameSite=Strict, so the
     * browser withholds it on the redirect back from an identity provider on another site and
     * the session arrives empty. Only then the state is read from the fallback cookie of the
     * given request; a session that holds a state always wins.
     *
     * @return array{state: string, nonce: string, verifier: string}
     */
    public function getAuthorizationState(?Request $request = null): array
    {
        $authorizationState = [
            'state' => (string) $this->get($this->key('state')),
            'nonce' => (string) $this->get($this->key('nonce')),
            'verifier' => (string) $this->get($this->key('pkce-code')),
        ];

        if ($authorizationState['state'] !== '' || !$request instanceof Request) {
            return $authorizationState;
        }

        return $this->readFallbackCookie($request) ?? $authorizationState;
    }

    public function clearAuthorizationState(): void
    {
        $this->set($this->key('state'), '');
        $this->set($this->key('nonce'), '');
        $this->set($this->key('pkce-code'), '');
    }

    /**
     * Builds the cookie that carries the authorization state across the redirect back from an
     * identity provider on another site: SameSite=Lax so it accompanies that top-level
     * navigation, HttpOnly, Secure, host-only and short-lived. It holds the same three values
     * as the session and is checked the same way.
     */
    public function createFallbackCookie(string $state, string $nonce, string $verifier, string $defaultUrl): Cookie
    {
        $payload = json_encode(['state' => $state, 'nonce' => $nonce, 'verifier' => $verifier], JSON_THROW_ON_ERROR);

        return Cookie::create(
            name: $this->fallbackCookieName(),
            value: rtrim(strtr(base64_encode($payload), from: '+/', to: '-_'), characters: '='),
            expire: time() + self::FALLBACK_COOKIE_LIFETIME,
            path: $this->cookiePath($defaultUrl),
            domain: null,
            secure: true,
            httpOnly: true,
            raw: false,
            sameSite: Cookie::SAMESITE_LAX,
        );
    }

    /**
     * Removes the fallback cookie from the browser; it must not outlive the callback.
     */
    public function expireFallbackCookie(Response $response, string $defaultUrl): void
    {
        $response->headers->clearCookie(
            name: $this->fallbackCookieName(),
            path: $this->cookiePath($defaultUrl),
            domain: null,
            secure: true,
            httpOnly: true,
            sameSite: Cookie::SAMESITE_LAX,
        );
    }

    /**
     * @return array{state: string, nonce: string, verifier: string}|null
     */
    private function readFallbackCookie(Request $request): ?array
    {
        $cookie = $request->cookies->get($this->fallbackCookieName());
        if (!is_string($cookie) || $cookie === '') {
            return null;
        }

        $payload = base64_decode(strtr($cookie, from: '-_', to: '+/'), strict: true);
        if ($payload === false) {
            return null;
        }

        $values = json_decode($payload, associative: true);
        if (!is_array($values)) {
            return null;
        }

        $state = $values['state'] ?? null;
        $nonce = $values['nonce'] ?? null;
        $verifier = $values['verifier'] ?? null;
        if (!is_string($state) || !is_string($nonce) || !is_string($verifier)) {
            return null;
        }

        return ['state' => $state, 'nonce' => $nonce, 'verifier' => $verifier];
    }

    private function fallbackCookieName(): string
    {
        return self::KEY_PREFIX . $this->provider . '-authorization';
    }

    private function cookiePath(string $defaultUrl): string
    {
        $path = parse_url($defaultUrl, PHP_URL_PATH);

        return is_string($path) && $path !== '' ? $path : '/';
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
