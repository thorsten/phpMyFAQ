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
use phpMyFAQ\Configuration;

class Token
{
    public const PMF_SESSION_NAME = 'pmf-csrf-token';
    private const PMF_SESSION_EXPIRY = 1800;

    private string $page;

    private int $expiry = 0;

    private ?string $sessionToken = null;

    private ?string $cookieToken = null;

    private static ?Token $instance = null;

    /**
     * Constructor.
     */
    final private function __construct(private Configuration $config)
    {
    }

    /**
     * @return string
     */
    public function getPage(): string
    {
        return $this->page;
    }

    /**
     * @param string $page
     * @return Token
     */
    public function setPage(string $page): Token
    {
        $this->page = $page;
        return $this;
    }

    /**
     * @return int
     */
    public function getExpiry(): int
    {
        return $this->expiry;
    }

    /**
     * @param int $expiry
     * @return Token
     */
    public function setExpiry(int $expiry): Token
    {
        $this->expiry = $expiry;
        return $this;
    }

    /**
     * @return string
     */
    public function getSessionToken(): string
    {
        return $this->sessionToken;
    }

    /**
     * @param string $sessionToken
     * @return Token
     */
    public function setSessionToken(string $sessionToken): Token
    {
        $this->sessionToken = $sessionToken;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getCookieToken(): ?string
    {
        return $this->cookieToken;
    }

    /**
     * @param string $cookieToken
     * @return Token
     */
    public function setCookieToken(string $cookieToken): Token
    {
        $this->cookieToken = $cookieToken;
        return $this;
    }


    /**
     * @param Configuration $config
     * @return Token
     */
    public static function getInstance(Configuration $config): Token
    {
        if (!(self::$instance instanceof Token)) {
            self::$instance = new self($config);
        }

        return self::$instance;
    }

    /**
     * @param string $page
     * @param int    $expiry
     * @return string
     * @throws Exception
     */
    public function getTokenInput(string $page, int $expiry = self::PMF_SESSION_EXPIRY): string
    {
        if (!$this->sessionHasStarted()) {
            throw new Exception('CSRF Input can not be created.');
        }

        $token = $this->getSession($page) ?? $this->setSession($page, $expiry);

        return sprintf(
            '<input type="hidden" id="%s" name="%s" value="%s">',
            self::PMF_SESSION_NAME,
            self::PMF_SESSION_NAME,
            $token->sessionToken
        );
    }

    /**
     * @param string $page
     * @param int    $expiry
     * @return string
     * @throws Exception
     */
    public function getTokenString(string $page, int $expiry = self::PMF_SESSION_EXPIRY): string
    {
        if (!$this->sessionHasStarted()) {
            throw new Exception('CSRF Input can not be created.');
        }

        $token = $this->getSession($page) ?? $this->setSession($page, $expiry);

        return $token->sessionToken;
    }

    /**
     * @param string      $page
     * @param string|null $requestToken
     * @param bool        $removeToken
     * @return bool
     * @throws Exception
     */
    public function verifyToken(string $page, string $requestToken = null, bool $removeToken = false): bool
    {
        if (!$this->sessionHasStarted()) {
            throw new Exception('Token can not be verified.');
        }

        // if the request token has not been passed, check POST
        $requestToken = ($requestToken ?? $_POST[self::PMF_SESSION_NAME] ?? null);
        if (is_null($requestToken)) {
            throw new Exception('Token is missing.');
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

    /**
     * @param string $page
     * @return bool
     * @throws Exception
     */
    public function removeToken(string $page): bool
    {
        if (!$this->sessionHasStarted()) {
            throw new Exception('Token can not be removed.');
        }

        unset($_COOKIE[$this->getCookie($page)], $_SESSION[self::PMF_SESSION_NAME][$page]);

        return true;
    }

    /**
     * @param string $page
     * @return Token|null
     */
    private function getSession(string $page): ?Token
    {
        return !empty($_SESSION[self::PMF_SESSION_NAME][$page]) ? $_SESSION[self::PMF_SESSION_NAME][$page] : null;
    }

    private function getCookie(string $page): string
    {
        return !empty($_COOKIE[$this->getCookieName($page)]) ? $_COOKIE[$this->getCookieName($page)] : '';
    }

    /**
     * @param string $page
     * @param int    $expiry
     * @return Token
     * @throws Exception
     */
    private function setSession(string $page, int $expiry): Token
    {
        $this
            ->setPage($page)
            ->setExpiry(time() + $expiry)
            ->setSessionToken(base64_encode(random_bytes(32)))
            ->setCookieToken(md5(base64_encode(random_bytes(32))));

        setcookie($this->getCookieName($page), $this->getCookieToken(), $this->getExpiry());

        return $_SESSION[self::PMF_SESSION_NAME][$page] = $this;
    }

    /**
     * @param string $page
     * @return string
     */
    private function getCookieName(string $page): string
    {
        return sprintf('%s-%s', self::PMF_SESSION_NAME, substr(md5($page), 0, 10));
    }

    /**
     * @throws Exception
     */
    private function sessionHasStarted(): bool
    {
        if (!isset($_SESSION)) {
            throw new Exception('Session has not been started.');
        }

        return true;
    }
}
