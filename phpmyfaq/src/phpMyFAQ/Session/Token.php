<?php

/**
 * Token class for CSRF (Cross Site Request Forgery) protection.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-02-19
 */

declare(strict_types=1);

namespace phpMyFAQ\Session;

use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class Token
{
    final public const string PMF_SESSION_NAME = 'pmf-csrf-token';

    private const int PMF_SESSION_EXPIRY = PMF_AUTH_TIMEOUT * 60;

    private string $page;

    private int $expiry = 0;

    private ?string $sessionToken = null;

    private ?string $cookieToken = null;

    private static ?Token $token = null;

    /**
     * Constructor.
     */
    final private function __construct(
        private readonly SessionInterface $session,
    ) {
    }

    /**
     * Only the token data is persisted in the session. The SessionInterface
     * reference is deliberately excluded: serialising it (e.g. when PHP
     * re-serialises $_SESSION during session_regenerate_id()) would drag in the
     * whole session object and could corrupt the stored token, dropping the
     * CSRF token. A persisted token never needs its own session back-reference.
     *
     * @return array<string, mixed>
     */
    public function __serialize(): array
    {
        return [
            'page' => $this->page ?? '',
            'expiry' => $this->expiry,
            'sessionToken' => $this->sessionToken,
            'cookieToken' => $this->cookieToken,
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public function __unserialize(array $data): void
    {
        $this->page = (string) ($data['page'] ?? '');
        $this->expiry = (int) ($data['expiry'] ?? 0);
        $sessionToken = $data['sessionToken'] ?? null;
        $this->sessionToken = is_string($sessionToken) ? $sessionToken : null;
        $cookieToken = $data['cookieToken'] ?? null;
        $this->cookieToken = is_string($cookieToken) ? $cookieToken : null;
    }

    public function getPage(): string
    {
        return $this->page;
    }

    public function setPage(string $page): Token
    {
        $this->page = $page;
        return $this;
    }

    public function getExpiry(): int
    {
        return $this->expiry;
    }

    public function setExpiry(int $expiry): Token
    {
        $this->expiry = $expiry;
        return $this;
    }

    public function getSessionToken(): string
    {
        return $this->sessionToken ?? '';
    }

    public function setSessionToken(#[\SensitiveParameter] string $sessionToken): Token
    {
        $this->sessionToken = $sessionToken;
        return $this;
    }

    public function getCookieToken(): ?string
    {
        return $this->cookieToken;
    }

    public function setCookieToken(#[\SensitiveParameter] string $cookieToken): Token
    {
        $this->cookieToken = $cookieToken;
        return $this;
    }

    /**
     * @throws Exception
     */
    public static function getInstance(SessionInterface $session): Token
    {
        if (!self::$token instanceof Token) {
            self::$token = new self($session);
        }

        return self::$token;
    }

    /**
     * @throws Exception
     */
    public function getTokenInput(string $page, int $expiry = self::PMF_SESSION_EXPIRY): string
    {
        $token = $this->getSession($page) ?? $this->setSession($page, $expiry);

        return sprintf(
            '<input type="hidden" id="%s" name="%s" value="%s">',
            self::PMF_SESSION_NAME,
            self::PMF_SESSION_NAME,
            $token->sessionToken,
        );
    }

    /**
     * @throws Exception
     */
    public function getTokenString(string $page, int $expiry = self::PMF_SESSION_EXPIRY): string
    {
        $token = $this->getSession($page) ?? $this->setSession($page, $expiry);

        return $token->sessionToken ?? '';
    }

    public function verifyToken(
        string $page,
        #[\SensitiveParameter]
        ?string $requestToken = null,
        #[\SensitiveParameter]
        bool $removeToken = false,
    ): bool {
        if ($requestToken === null) {
            $postedToken = Request::createFromGlobals()->request->get(self::PMF_SESSION_NAME);
            $requestToken = $postedToken === null ? null : (string) $postedToken;
        }

        if (is_null($requestToken)) {
            return false;
        }

        $token = $this->getSession($page);

        // if the time is greater than the expiry form submission window
        if (!$token instanceof Token || time() > $token->getExpiry()) {
            $this->removeToken($page);
            return false;
        }

        // check the hash matches the Session / Cookie
        $sessionConfirm = hash_equals($token->getSessionToken(), $requestToken);
        // A token without a cookie counterpart is malformed state - fail closed
        $storedCookieToken = $token->getCookieToken();
        $cookieConfirm = $storedCookieToken !== null && hash_equals($storedCookieToken, $this->getCookie($page));

        // remove the token
        if ($removeToken) {
            $this->removeToken($page);
        }

        // both session and cookie match
        return $sessionConfirm && $cookieConfirm;
    }

    public function removeToken(string $page): bool
    {
        Request::createFromGlobals()->cookies->remove($this->getCookieName($page));
        $this->session->remove(sprintf('%s.%s', self::PMF_SESSION_NAME, $page));

        return true;
    }

    private function getSession(string $page): ?Token
    {
        $token = $this->session->get(sprintf('%s.%s', self::PMF_SESSION_NAME, $page));

        // Treat a missing or corrupted (non-Token) value as absent so callers
        // regenerate a fresh token. A stale value that fails to deserialize
        // cleanly would otherwise be returned and never replaced, permanently
        // breaking CSRF verification for that page.
        if (!$token instanceof self) {
            return null;
        }

        // Treat an expired token as absent so callers regenerate a fresh one
        // instead of rendering a dead token that would fail verification.
        if (time() > $token->getExpiry()) {
            $this->removeToken($page);

            return null;
        }

        return $token;
    }

    private function getCookie(string $page): string
    {
        return Request::createFromGlobals()->cookies->get($this->getCookieName($page), '');
    }

    /**
     * @throws Exception
     */
    private function setSession(string $page, int $expiry): Token
    {
        $request = Request::createFromGlobals();
        $randomToken = bin2hex(random_bytes(32));
        $token = new self($this->session);
        $token
            ->setPage($page)
            ->setExpiry(time() + $expiry)
            ->setSessionToken($randomToken)
            ->setCookieToken($randomToken);

        setcookie($token->getCookieName($page), (string) $token->getCookieToken(), [
            'expires' => $token->getExpiry(),
            'path' => dirname((string) $request->server->get('SCRIPT_NAME')),
            'samesite' => 'strict',
            'secure' => $request->isSecure(),
            'httponly' => true,
        ]);

        $this->session->set(sprintf('%s.%s', self::PMF_SESSION_NAME, $page), $token);

        return $token;
    }

    private function getCookieName(string $page): string
    {
        return sprintf('%s-%s', self::PMF_SESSION_NAME, substr(string: md5($page), offset: 0, length: 10));
    }

    public static function resetInstanceForTests(): void
    {
        self::$token = null;
    }
}
