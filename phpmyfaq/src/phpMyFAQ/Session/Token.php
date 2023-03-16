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
 * @copyright 2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-02-19
 */

namespace phpMyFAQ\Session;

use Exception;

class Token
{
    final public const PMF_SESSION_NAME = 'pmf-csrf-token';
    private const PMF_SESSION_EXPIRY = 1800;

    private string $page;

    private int $expiry = 0;

    private ?string $sessionToken = null;

    private ?string $cookieToken = null;

    private static ?Token $instance = null;

    /**
     * Constructor.
     */
    final private function __construct()
    {
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
        return $this->sessionToken;
    }

    public function setSessionToken(string $sessionToken): Token
    {
        $this->sessionToken = $sessionToken;
        return $this;
    }

    public function getCookieToken(): ?string
    {
        return $this->cookieToken;
    }

    public function setCookieToken(string $cookieToken): Token
    {
        $this->cookieToken = $cookieToken;
        return $this;
    }


    public static function getInstance(): Token
    {
        if (!(self::$instance instanceof Token)) {
            self::$instance = new self();
        }

        return self::$instance;
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
            $token->sessionToken
        );
    }

    /**
     * @throws Exception
     */
    public function getTokenString(string $page, int $expiry = self::PMF_SESSION_EXPIRY): string
    {
        $token = $this->getSession($page) ?? $this->setSession($page, $expiry);

        return $token->sessionToken;
    }

    /**
     * @param string|null $requestToken
     */
    public function verifyToken(string $page, string $requestToken = null, bool $removeToken = false): bool
    {
        // if the request token has not been passed, check POST
        $requestToken ??= $_POST[self::PMF_SESSION_NAME] ?? null;

        if (is_null($requestToken)) {
            return false;
        }

        $token = $this->getSession($page);

        // if the time is greater than the expiry form submission window
        if (empty($token) || time() > $token->getExpiry()) {
            $this->removeToken($page);
            return false;
        }

        // check the hash matches the Session / Cookie
        $sessionConfirm = hash_equals($token->getSessionToken(), $requestToken);
        $cookieConfirm  = hash_equals($token->getCookieToken(), $this->getCookie($page));

        // remove the token
        if ($removeToken) {
            $this->removeToken($page);
        }

        // both session and cookie match
        if ($sessionConfirm && $cookieConfirm) {
            return true;
        }

        return false;
    }

    public function removeToken(string $page): bool
    {
        unset($_COOKIE[$this->getCookie($page)], $_SESSION[self::PMF_SESSION_NAME][$page]);

        return true;
    }

    private function getSession(string $page): ?Token
    {
        return !empty($_SESSION[self::PMF_SESSION_NAME][$page]) ? $_SESSION[self::PMF_SESSION_NAME][$page] : null;
    }

    private function getCookie(string $page): string
    {
        return !empty($_COOKIE[$this->getCookieName($page)]) ? $_COOKIE[$this->getCookieName($page)] : '';
    }

    /**
     * @throws Exception
     */
    private function setSession(string $page, int $expiry): Token
    {
        $token = new self();
        $token
            ->setPage($page)
            ->setExpiry(time() + $expiry)
            ->setSessionToken(md5(base64_encode(random_bytes(32))))
            ->setCookieToken(md5(base64_encode(random_bytes(32))));

        setcookie($token->getCookieName($page), $token->getCookieToken(), ['expires' => $token->getExpiry()]);

        return $_SESSION[self::PMF_SESSION_NAME][$page] = $token;
    }

    private function getCookieName(string $page): string
    {
        return sprintf('%s-%s', self::PMF_SESSION_NAME, substr(md5($page), 0, 10));
    }
}
