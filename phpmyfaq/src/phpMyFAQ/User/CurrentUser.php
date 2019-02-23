<?php

namespace phpMyFAQ\User;

/**
 * Manages authentication process using PHP sessions.
 *
 * The CurrentUser class is an extension of the User class. It provides methods
 * manage user authentication using multiple database accesses. There are three
 * ways of making a new current user object, using the login(), getFromSession(),
 * getFromCookie() or manually. login(), getFromSession() and getFromCookie() may
 * be combined.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Lars Tiedemann <php@larstiedemann.de>
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2005-2019 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2005-09-28
 */

use phpMyFAQ\Configuration;
use phpMyFAQ\Db;
use phpMyFAQ\Session;
use phpMyFAQ\User;

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/* user defined constants */
define('SESSION_CURRENT_USER', 'CURRENT_USER');
define('SESSION_ID_TIMESTAMP', 'SESSION_TIMESTAMP');

/**
 * Class CurrentUser
 *
 * @package phpMyFAQ
 * @author Lars Tiedemann <php@larstiedemann.de>
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2005-2019 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2005-09-28
 */
class CurrentUser extends User
{
    /**
     * true if CurrentUser is logged in, otherwise false.
     * @var bool
     */
    private $loggedIn = false;

    /**
     * Specifies the timeout for the session in minutes. If the session ID was
     * not updated for the last $this->_sessionTimeout minutes, the CurrentUser
     * will be logged out automatically if no cookie was set.
     * @var int
     */
    private $sessionTimeout = PMF_AUTH_TIMEOUT;

    /**
     * The Session class object
     * @var Session
     */
    private $session;

    /**
     * Specifies the timeout for the session-ID in minutes. If the session ID
     * was not updated for the last $this->_sessionIdTimeout minutes, it will
     * be updated. If set to 0, the session ID will be updated on every click.
     * The session ID timeout must not be greater than Session timeout.
     * @var int
     */
    private $sessionIdTimeout = 1;

    /**
     * LDAP configuration if available.
     * @var array
     */
    private $ldapConfig = [];

    /**
     * Remember me activated or deactivated.
     * @var bool
     */
    private $rememberMe = false;

    /**
     * Login successful or auth failure:
     * 1 -> success
     * 0 -> failure.
     * @var int
     */
    private $loginState = 1;

    /**
     * Number of failed login attempts
     * @var int
     */
    private $loginAttempts = 0;

    /**
     * Lockout time in seconds
     * @var integer
     */
    private $lockoutTime = 600;

    /**
     * Constructor.
     * @param Configuration $config
     */
    public function __construct(Configuration $config)
    {
        parent::__construct($config);
        $this->ldapConfig = $config->getLdapConfig();
        $this->session = new Session($config);
    }

    /**
     * Checks the given login and password in all auth-objects. Returns true
     * on success, otherwise false. Raises errors that can be checked using
     * the error() method. On success, the CurrentUser instance will be
     * labeled as logged in. The name of the successful auth container will
     * be stored in the user table. A new auth object may be added by using
     * addAuth() method. The given password must not be encrypted, since the
     * auth object takes care about the encryption method.
     *
     * @param string $login    Login name
     * @param string $password Password
     *
     * @return bool
     */
    public function login(string $login, string $password): bool
    {
        $optData = [];
        $loginError = $passwordError = $count = 0;

        // First check for brute force attack
        $this->getUserByLogin($login);
        if ($this->isFailedLastLoginAttempt()) {
            $this->errors[] = parent::ERROR_USER_TOO_MANY_FAILED_LOGINS;
            return false;
        }

        // Additional code for LDAP: user\\domain
        if ($this->config->get('ldap.ldapSupport') && $this->config->get('ldap.ldap_use_domain_prefix') &&
            '' !== $password) {
            // If LDAP configuration and ldap_use_domain_prefix is true
            // and LDAP credentials are provided (password is not empty)
            if (($pos = strpos($login, '\\')) !== false) {
                if ($pos !== 0) {
                    $optData['domain'] = substr($login, 0, $pos);
                }

                $login = substr($login, $pos + 1);
            }
        }

        // Additional code for SSO
        if ($this->config->get('security.ssoSupport') && isset($_SERVER['REMOTE_USER']) && '' === $password) {
            // if SSO configuration is enabled, REMOTE_USER is provided and we try to login using SSO (no password)
            if (($pos = strpos($login, '@')) !== false) {
                if ($pos !== 0) {
                    $login = substr($login, 0, $pos);
                }
            }
            if (($pos = strpos($login, '\\')) !== false) {
                if ($pos !== 0) {
                    $login = substr($login, $pos + 1);
                }
            }
        }

        // authenticate user by login and password
        foreach ($this->authContainer as $name => $auth) {
            ++$count;

            // $auth is an invalid Auth object, so continue
            if (!$this->checkAuth($auth)) {
                --$count;
                continue;
            }

            // $login does not exist, so continue
            if (!$auth->checkLogin($login, $optData)) {
                ++$loginError;
                continue;
            }

            // $login exists, but $pass is incorrect, so stop!
            if (!$auth->checkPassword($login, $password, $optData)) {
                ++$passwordError;
                // Don't stop, as other auth method could work:
                continue;
            }

            // but hey, this must be a valid match, so get user object
            $this->getUserByLogin($login);
            $this->loggedIn = true;
            $this->updateSessionId(true);
            $this->saveToSession();
            $this->saveCrsfTokenToSession();

            // save remember me cookie if set
            if (true === $this->rememberMe) {
                $rememberMe = sha1(session_id());
                $this->setRememberMe($rememberMe);
                $this->session->setCookie(
                    Session::PMF_COOKIE_NAME_REMEMBERME,
                    $rememberMe,
                    $_SERVER['REQUEST_TIME'] + PMF_REMEMBERME_EXPIRED_TIME
                );
            }

            // remember the auth container for administration
            $update = sprintf("
                UPDATE
                    %sfaquser
                SET
                    auth_source = '%s'
                WHERE
                    user_id = %d",
                Db::getTablePrefix(),
                $this->config->getDb()->escape($name),
                $this->getUserId()
            );
            $result = $this->config->getDb()->query($update);
            if (!$result) {
                $this->setSuccess(false);
                return false;
            }

            // Login successful
            $this->setSuccess(true);
            return true;
        }

        // raise errors and return false
        if ($loginError === $count) {
            $this->setSuccess(false);
            $this->errors[] = parent::ERROR_USER_INCORRECT_LOGIN;
        }
        if ($passwordError > 0) {
            $this->getUserByLogin($login);
            $this->setLoginAttempt();
            $this->errors[] = parent::ERROR_USER_INCORRECT_PASSWORD;
        }

        return false;
    }

    /**
     * Returns true if CurrentUser is logged in, otherwise false.
     * @return bool
     */
    public function isLoggedIn(): bool
    {
        return $this->loggedIn;
    }

    /**
     * Returns false if the CurrentUser object stored in the
     * session is valid and not timed out. There are two
     * parameters for session timeouts: $this->_sessionTimeout
     * and $this->_sessionIdTimeout.
     * @return bool
     */
    public function sessionIsTimedOut(): bool
    {
        if ($this->sessionTimeout <= $this->sessionAge()) {
            return true;
        }
        return false;
    }

    /**
     * Returns false if the session-ID is not timed out.
     *
     * @return bool
     */
    public function sessionIdIsTimedOut(): bool
    {
        if ($this->sessionIdTimeout <= $this->sessionAge()) {
            return true;
        }
        return false;
    }

    /**
     * Returns the age of the current session-ID in minutes.
     *
     * @return float
     */
    public function sessionAge(): float
    {
        if (!isset($_SESSION[SESSION_ID_TIMESTAMP])) {
            return 0;
        }
        return ($_SERVER['REQUEST_TIME'] - $_SESSION[SESSION_ID_TIMESTAMP])/60;
    }

    /**
     * Returns an associative array with session information stored
     * in the user table. The array has the following keys:
     * session_id, session_timestamp and ip.
     * @return array
     */
    public function getSessionInfo(): array
    {
        $select = sprintf('
            SELECT
                session_id,
                session_timestamp,
                ip,
                success
            FROM
                %sfaquser
            WHERE
                user_id = %d',
            Db::getTablePrefix(),
            $this->getUserId()
        );

        $res = $this->config->getDb()->query($select);
        if (!$res or $this->config->getDb()->numRows($res) != 1) {
            return [];
        }

        return $this->config->getDb()->fetchArray($res);
    }

    /**
     * Updates the session-ID, does not care about time outs.
     * Stores session information in the user table: session_id,
     * session_timestamp and ip.
     * Optionally it should update the 'last login' time.
     * Returns true on success, otherwise false.
     *
     * @param bool $updateLastLogin Update the last login time?
     *
     * @return bool
     */
    public function updateSessionId(bool $updateLastLogin = false): bool
    {
        // renew the session-ID
        $oldSessionId = session_id();
        if (session_regenerate_id(true)) {
            $sessionPath = session_save_path();
            if (strpos($sessionPath, ';') !== false) {
                $sessionPath = substr($sessionPath, strpos($sessionPath, ';') + 1);
            }
            $sessionFilename = $sessionPath.'/sess_'.$oldSessionId;
            if (@file_exists($sessionFilename)) {
                @unlink($sessionFilename);
            }
        }
        // store session-ID age
        $_SESSION[SESSION_ID_TIMESTAMP] = $_SERVER['REQUEST_TIME'];
        // save session information in user table
        $update = sprintf("
            UPDATE
                %sfaquser
            SET
                session_id = '%s',
                session_timestamp = %d,
                %s
                ip = '%s'
            WHERE
                user_id = %d",
            Db::getTablePrefix(),
            session_id(),
            $_SERVER['REQUEST_TIME'],
            $updateLastLogin ? "last_login = '".date('YmdHis', $_SERVER['REQUEST_TIME'])."'," : '',
            $_SERVER['REMOTE_ADDR'],
            $this->getUserId()
        );

        $res = $this->config->getDb()->query($update);
        if (!$res) {
            $this->errors[] = $this->config->getDb()->error();

            return false;
        }

        return true;
    }

    /**
     * Saves the CurrentUser into the session. This method
     * may be called after a successful login.
     * @return void
     */
    public function saveToSession()
    {
        $_SESSION[SESSION_CURRENT_USER] = $this->getUserId();
    }

    /**
     * Deletes the CurrentUser from the session. The user
     * will be logged out. Return true on success, otherwise false.
     * @param bool $deleteCookie
     * @return bool
     */
    public function deleteFromSession(bool $deleteCookie = false): bool
    {
        // delete CSRF Token
        $this->deleteCsrfTokenFromSession();

        // delete CurrentUser object from session
        $_SESSION[SESSION_CURRENT_USER] = null;
        unset($_SESSION[SESSION_CURRENT_USER]);

        // log CurrentUser out
        $this->loggedIn = false;

        // delete session-ID
        $update = sprintf('
            UPDATE
                %sfaquser
            SET
                session_id = NULL
                %s
            WHERE
                user_id = %d',
                Db::getTablePrefix(),
                $deleteCookie ? ', remember_me = NULL' : '',
                $this->getUserId()
        );

        $res = $this->config->getDb()->query($update);

        if (!$res) {
            $this->errors[] = $this->config->getDb()->error();

            return false;
        }

        if ($deleteCookie) {
            $this->session->setCookie(Session::PMF_COOKIE_NAME_REMEMBERME);
        }

        session_destroy();

        return true;
    }

    /**
     * This static method returns a valid CurrentUser object if there is one
     * in the session that is not timed out. The session-ID is updated if
     * necessary. The CurrentUser will be removed from the session, if it is
     * timed out. If there is no valid CurrentUser in the session or the
     * session is timed out, null will be returned. If the session data is
     * correct, but there is no user found in the user table, false will be
     * returned. On success, a valid CurrentUser object is returned.
     * @static
     * @param Configuration $config
     * @return null|CurrentUser
     */
    public static function getFromSession(Configuration $config)
    {
        // there is no valid user object in session
        if (!isset($_SESSION[SESSION_CURRENT_USER]) || !isset($_SESSION[SESSION_ID_TIMESTAMP])) {
            return null;
        }

        // create a new CurrentUser object
        $user = new self($config);
        $user->getUserById($_SESSION[SESSION_CURRENT_USER]);

        // user object is timed out
        if ($user->sessionIsTimedOut()) {
            $user->deleteFromSession();
            $user->errors[] = 'Session timed out.';

            return null;
        }
        // session-id not found in user table
        $session_info = $user->getSessionInfo();
        $session_id = (isset($session_info['session_id']) ? $session_info['session_id'] : '');
        if ($session_id == '' || $session_id != session_id()) {
            return null;
        }
        // check ip
        if ($config->get('security.ipCheck') &&
            $session_info['ip'] != $_SERVER['REMOTE_ADDR']) {
            return null;
        }
        // session-id needs to be updated
        if ($user->sessionIdIsTimedOut()) {
            $user->updateSessionId();
        }
        // user is now logged in
        $user->loggedIn = true;
        // save current user to session and return the instance
        $user->saveToSession();

        return $user;
    }

    /**
     * This static method returns a valid CurrentUser object if there is one
     * in the cookie that is not timed out. The session-ID is updated then.
     * The CurrentUser will be removed from the session, if it is
     * timed out. If there is no valid CurrentUser in the cookie or the
     * cookie is timed out, null will be returned. If the cookie is correct,
     * but there is no user found in the user table, false will be returned.
     * On success, a valid CurrentUser object is returned.
     *
     * @static
     *
     * @param Configuration $config
     *
     * @return null|CurrentUser
     */
    public static function getFromCookie(Configuration $config)
    {
        if (!isset($_COOKIE[Session::PMF_COOKIE_NAME_REMEMBERME])) {
            return null;
        }

        // create a new CurrentUser object
        $user = new self($config);
        $user->getUserByCookie($_COOKIE[Session::PMF_COOKIE_NAME_REMEMBERME]);

        if (-1 === $user->getUserId()) {
            return null;
        }

        // sessionId needs to be updated
        $user->updateSessionId(true);
        // user is now logged in
        $user->loggedIn = true;
        // save current user to session and return the instance
        $user->saveToSession();
        // add CSRF token to session
        $user->saveCrsfTokenToSession();

        return $user;
    }

    /**
     * Sets the number of minutes when the current user stored in
     * the session gets invalid.
     *
     * @param float $timeout Timeout
     */
    public function setSessionTimeout($timeout)
    {
        $this->sessionTimeout = abs($timeout);
    }

    /**
     * Sets the number of minutes when the session-ID needs to be
     * updated. By setting the session-ID timeout to zero, the
     * session-ID will be updated on each click.
     *
     * @param float $timeout Timeout
     */
    public function setSessionIdTimeout($timeout)
    {
        $this->sessionIdTimeout = abs($timeout);
    }

    /**
     * Enables the remember me decision.
     */
    public function enableRememberMe()
    {
        $this->rememberMe = true;
    }

    /**
     * Saves remember me token in the database.
     *
     * @param string $rememberMe
     *
     * @return bool
     */
    protected function setRememberMe($rememberMe)
    {
        $update = sprintf("
            UPDATE
                %sfaquser
            SET
                remember_me = '%s'
            WHERE
                user_id = %d",
            Db::getTablePrefix(),
            $this->config->getDb()->escape($rememberMe),
            $this->getUserId()
        );

        return $this->config->getDb()->query($update);
    }

    /**
     * Sets login success/failure.
     *
     * @param bool $success
     *
     * @return bool
     */
    protected function setSuccess($success)
    {
        $this->loginState = (int)$success;
        $this->loginAttempts = 0;

        $update = sprintf('
            UPDATE
                %sfaquser
            SET
                success = %d,
                login_attempts = %d
            WHERE
                user_id = %d',
            Db::getTablePrefix(),
            $this->loginState,
            $this->loginAttempts,
            $this->getUserId()
        );

        return $this->config->getDb()->query($update);
    }

    /**
     * Sets IP and session timestamp plus lockout time, success flag to
     * false.
     *
     * @return mixed
     */
    protected function setLoginAttempt()
    {
        $this->loginAttempts++;

        $update = sprintf("
            UPDATE
                %sfaquser
            SET
                session_timestamp ='%s',
                ip = '%s',
                success = 0,
                login_attempts = login_attempts + 1
            WHERE
                user_id = %d",
            Db::getTablePrefix(),
            $_SERVER['REQUEST_TIME'],
            $_SERVER['REMOTE_ADDR'],
            $this->getUserId()
        );

        return $this->config->getDb()->query($update);
    }

    /**
     * Checks if the last login attempt from current user failed.
     *
     * @return bool
     */
    protected function isFailedLastLoginAttempt()
    {
        $select = sprintf("
            SELECT
                session_timestamp,
                ip,
                success,
                login_attempts
            FROM
                %sfaquser
            WHERE
                user_id = %d
            AND
                ('%d' - session_timestamp) <= %d
            AND
                ip = '%s'
            AND
                success = 0
            AND
                login_attempts > 5",
            Db::getTablePrefix(),
            $this->getUserId(),
            $_SERVER['REQUEST_TIME'],
            $this->lockoutTime,
            $_SERVER['REMOTE_ADDR']
        );

        $result = $this->config->getDb()->query($select);
        if ($this->config->getDb()->numRows($result) !== 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Returns the CSRF token from session.
     *
     * @return string
     */
    public function getCsrfTokenFromSession()
    {
        return $_SESSION['phpmyfaq_csrf_token'];
    }

    /**
     * Save CSRF token to session.
     */
    public function saveCrsfTokenToSession()
    {
        if (!isset($_SESSION['phpmyfaq_csrf_token'])) {
            $_SESSION['phpmyfaq_csrf_token'] = $this->createCsrfToken();
        }
    }

    /**
     * Deletes CSRF token from session.
     */
    protected function deleteCsrfTokenFromSession()
    {
        unset($_SESSION['phpmyfaq_csrf_token']);
    }

    /**
     * Creates a CSRF token.
     *
     * @return string
     */
    private function createCsrfToken()
    {
        return sha1(microtime().$this->getLogin());
    }
}
