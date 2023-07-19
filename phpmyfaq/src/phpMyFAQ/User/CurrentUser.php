<?php

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
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Lars Tiedemann <php@larstiedemann.de>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2005-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2005-09-28
 */

namespace phpMyFAQ\User;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Filter;
use phpMyFAQ\Permission\MediumPermission;
use phpMyFAQ\Session;
use phpMyFAQ\User;
use Symfony\Component\HttpFoundation\Request;

/* user defined constants */
define('SESSION_CURRENT_USER', 'CURRENT_USER');
define('SESSION_ID_TIMESTAMP', 'SESSION_TIMESTAMP');

/**
 * Class CurrentUser
 *
 * @package   phpMyFAQ
 * @author    Lars Tiedemann <php@larstiedemann.de>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2005-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2005-09-28
 */
class CurrentUser extends User
{
    private const PMF_REMEMBER_ME_EXPIRED_TIME = 1209600; // 2 weeks

    private bool $loggedIn = false;

    /**
     * Specifies the timeout for the session in minutes. If the session ID was
     * not updated for the last $this->_sessionTimeout minutes, the CurrentUser
     * will be logged out automatically if no cookie was set.
     */
    private int $sessionTimeout = PMF_AUTH_TIMEOUT;

    /**
     * The Session class object
     */
    private readonly Session $session;

    /**
     * Specifies the timeout for the session-ID in minutes. If the session ID
     * was not updated for the last $this->_sessionIdTimeout minutes, it will
     * be updated. If set to 0, the session ID will be updated on every click.
     * The session ID timeout must not be greater than Session timeout.
     */
    private int $sessionIdTimeout = 1;

    /**
     * Remember me activated or deactivated.
     */
    private bool $rememberMe = false;

    /**
     * Number of failed login attempts
     */
    private int $loginAttempts = 0;

    /**
     * Lockout time in seconds
     */
    private int $lockoutTime = 600;

    /**
     * Constructor.
     */
    public function __construct(Configuration $config)
    {
        parent::__construct($config);
        $this->session = new Session($config);
    }

    /**
     * Checks the given login and password in all auth-objects. Returns true
     * on success, otherwise false. Raises errors that can be checked using
     * the error() method. On success, the CurrentUser instance will be
     * labeled as logged in. The name of the successful auth container will
     * be stored in the user table. A new auth object may be added by using
     * addAuth() method. The given password must not be encrypted, since the
     * auth object takes care of the encryption method.
     *
     * @param string $login Login name
     * @param string $password Password
     */
    public function login(string $login, string $password): bool
    {
        $optData = [];
        $loginError = $passwordError = $count = 0;

        if ($this->config->get('security.loginWithEmailAddress') && Filter::filterVar($login, FILTER_VALIDATE_EMAIL)) {
            $userId = $this->getUserIdByEmail($login);
            $this->getUserById($userId);
            $login = $this->getLogin();
        }

        // First check for brute force attack
        $this->getUserByLogin($login);
        if ($this->isFailedLastLoginAttempt()) {
            $this->errors[] = parent::ERROR_USER_TOO_MANY_FAILED_LOGINS;
            $this->config->getLogger()->info(parent::ERROR_USER_TOO_MANY_FAILED_LOGINS);
            return false;
        }

        // Additional code for LDAP: user\\domain
        if (
            $this->config->isLdapActive() && $this->config->get('ldap.ldap_use_domain_prefix')
            && '' !== $password
        ) {
            // If LDAP configuration and ldap_use_domain_prefix are true,
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
            // if SSO configuration is enabled, REMOTE_USER is provided, and we try to log in using SSO (no password)
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
        foreach ($this->authContainer as $authSource => $auth) {
            ++$count;

            // $auth is an invalid Auth object, so continue
            if (!$this->checkAuth($auth)) {
                --$count;
                continue;
            }

            // $login does not exist, so continue
            if (!$auth->isValidLogin($login, $optData)) {
                ++$loginError;
                continue;
            }

            // $login exists, but $pass is incorrect, so stop!
            if (!$auth->checkCredentials($login, $password, $optData)) {
                ++$passwordError;
                // Don't stop, as another auth method could work
                continue;
            }

            // but hey, this must be a valid match, so get a user object
            $this->getUserByLogin($login);

            // only save this successful login to session when 2FA is not enabled
            if ($this->getUserData('twofactor_enabled') !== 1) {
                $this->setLoggedIn(true);
                $this->updateSessionId(true);
                $this->saveToSession();
            }

            // save remember me cookie if set
            if ($this->rememberMe) {
                $rememberMe = sha1(session_id());
                $this->setRememberMe($rememberMe);
                $this->session->setCookie(
                    Session::PMF_COOKIE_NAME_REMEMBERME,
                    $rememberMe,
                    $_SERVER['REQUEST_TIME'] + self::PMF_REMEMBER_ME_EXPIRED_TIME
                );
            }

            // Set auth source
            if (!$this->setAuthSource($authSource)) {
                $this->setSuccess(false);
                return false;
            }

            // Login successful if 2FA is not enabled
            if ($this->getUserData('twofactor_enabled') !== 1) {
                $this->setSuccess(true);
            }

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
     */
    public function isLoggedIn(): bool
    {
        return $this->loggedIn;
    }

    /**
     * Sets loggedIn to true if the 2FA-auth was successfully and saves the login to session.
     */
    public function twoFactorSuccess(): bool
    {
        $this->setLoggedIn(true);
        $this->updateSessionId(true);
        $this->saveToSession();
        $this->setSuccess(true);

        return true;
    }

    public function setLoggedIn(bool $loggedIn): void
    {
        $this->loggedIn = $loggedIn;
    }

    public function isLocalUser(): bool
    {
        $query = sprintf(
            "SELECT auth_source FROM %sfaquser WHERE auth_source = 'local' AND user_id = %d",
            Database::getTablePrefix(),
            $this->getUserId()
        );

        $result = $this->config->getDb()->query($query);

        return (bool) $this->config->getDb()->fetchRow($result);
    }

    /**
     * Returns false if the CurrentUser object stored in the
     * session is valid and not timed out. There are two
     * parameters for session timeouts: $this->_sessionTimeout
     * and $this->_sessionIdTimeout.
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
     */
    public function sessionAge(): float
    {
        if (!isset($_SESSION[SESSION_ID_TIMESTAMP])) {
            return 0;
        }
        return ($_SERVER['REQUEST_TIME'] - $_SESSION[SESSION_ID_TIMESTAMP]) / 60;
    }

    /**
     * Returns an associative array with session information stored
     * in the user table. The array has the following keys:
     * session_id, session_timestamp and ip.
     *
     * @return array<string>
     */
    public function getSessionInfo(): array
    {
        $select = sprintf(
            '
            SELECT
                session_id,
                session_timestamp,
                ip,
                success
            FROM
                %sfaquser
            WHERE
                user_id = %d',
            Database::getTablePrefix(),
            $this->getUserId()
        );

        $res = $this->config->getDb()->query($select);
        if (!$res or $this->config->getDb()->numRows($res) != 1) {
            return [];
        }

        return $this->config->getDb()->fetchArray($res);
    }

    /**
     * Updates the session-ID, does not care about time-outs.
     * Store session information in the user table: session_id,
     * session_timestamp and ip.
     * Optionally, it should update the 'last login' time.
     * Returns true to success, otherwise false.
     *
     * @param bool $updateLastLogin Update the last login time?
     */
    public function updateSessionId(bool $updateLastLogin = false): bool
    {
        // renew the session-ID
        $oldSessionId = session_id();
        if (session_regenerate_id(true)) {
            $sessionPath = session_save_path();
            if (str_contains($sessionPath, ';')) {
                $sessionPath = substr($sessionPath, strpos($sessionPath, ';') + 1);
            }
            $sessionFilename = $sessionPath . '/sess_' . $oldSessionId;
            if (@file_exists($sessionFilename)) {
                @unlink($sessionFilename);
            }
        }
        // store session-ID age
        $_SESSION[SESSION_ID_TIMESTAMP] = $_SERVER['REQUEST_TIME'];
        // save session information in user table
        $update = sprintf(
            "
            UPDATE
                %sfaquser
            SET
                session_id = '%s',
                session_timestamp = %d,
                %s
                ip = '%s'
            WHERE
                user_id = %d",
            Database::getTablePrefix(),
            session_id(),
            $_SERVER['REQUEST_TIME'],
            $updateLastLogin ? "last_login = '" . date('YmdHis', $_SERVER['REQUEST_TIME']) . "'," : '',
            Request::createFromGlobals()->getClientIp(),
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
     */
    public function saveToSession(): void
    {
        $_SESSION[SESSION_CURRENT_USER] = $this->getUserId();
    }

    /**
     * Deletes the CurrentUser from the session. The user
     * will be logged out. Return true to success, otherwise false.
     */
    public function deleteFromSession(bool $deleteCookie = false): bool
    {
        // delete CurrentUser object from session
        $_SESSION[SESSION_CURRENT_USER] = null;
        unset($_SESSION[SESSION_CURRENT_USER]);

        // log CurrentUser out
        $this->setLoggedIn(false);

        // delete session-ID
        $update = sprintf(
            '
            UPDATE
                %sfaquser
            SET
                session_id = NULL
                %s
            WHERE
                user_id = %d',
            Database::getTablePrefix(),
            $deleteCookie ? ', remember_me = NULL' : '',
            $this->getUserId()
        );

        $res = $this->config->getDb()->query($update);

        if (!$res) {
            $this->errors[] = $this->config->getDb()->error();

            return false;
        }

        if ($deleteCookie) {
            $this->session->setCookie(Session::PMF_COOKIE_NAME_REMEMBERME, '');
        }

        session_destroy();
        session_start();

        return true;
    }

    /**
     * Returns the current user object from cookie or session
     */
    public static function getCurrentUser(Configuration $faqConfig): CurrentUser
    {
        $user = self::getFromCookie($faqConfig);

        if (!$user instanceof CurrentUser) {
            $user = self::getFromSession($faqConfig);
        }
        if ($user instanceof CurrentUser) {
            $user->setLoggedIn(true);
        } else {
            $user = new CurrentUser($faqConfig);
        }

        return $user;
    }

    /**
     * Returns the current user ID and group IDs, default values are -1
     *
     * @param CurrentUser|null $user
     * @return array<int, int>
     */
    public static function getCurrentUserGroupId(CurrentUser $user = null): array
    {
        if (!is_null($user)) {
            $currentUser = $user->getUserId();
            if ($user->perm instanceof MediumPermission) {
                $currentGroups = $user->perm->getUserGroups($currentUser);
            } else {
                $currentGroups = [-1];
            }
            if (0 === (is_countable($currentGroups) ? count($currentGroups) : 0)) {
                $currentGroups = [-1];
            }
        } else {
            $currentUser = -1;
            $currentGroups = [-1];
        }

        return [ $currentUser, $currentGroups ];
    }

    /**
     * This static method returns a valid CurrentUser object if there is one
     * in the session that is not timed out. The session-ID is updated if
     * necessary. The CurrentUser will be removed from the session if it is
     * timed out. If there is no valid CurrentUser in the session or the
     * session is timed out, null will be returned. If the session data is
     * correct, but there is no user found in the user table, false will be
     * returned. On success, a valid CurrentUser object is returned.
     *
     * @static
     */
    public static function getFromSession(Configuration $config): ?CurrentUser
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
        $sessionInfo = $user->getSessionInfo();
        $sessionId = ($sessionInfo['session_id'] ?? '');
        if ($sessionId === '' || $sessionId !== session_id()) {
            return null;
        }
        // check ip
        if ($config->get('security.ipCheck') && $sessionInfo['ip'] != Request::createFromGlobals()->getClientIp()) {
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
     * The CurrentUser will be removed from the session if it is
     * timed out. If there is no valid CurrentUser in the cookie or the
     * cookie is timed out, null will be returned. If the cookie is correct,
     * but there is no user found in the user table, false will be returned.
     * On success, a valid CurrentUser object is returned.
     *
     * @static
     */
    public static function getFromCookie(Configuration $config): ?CurrentUser
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

        return $user;
    }

    /**
     * Sets the number of minutes when the current user stored in
     * the session gets invalid.
     *
     * @param int $timeout Timeout
     */
    public function setSessionTimeout(int $timeout): void
    {
        $this->sessionTimeout = abs($timeout);
    }

    /**
     * Enables to remember me decision.
     */
    public function enableRememberMe(): void
    {
        $this->rememberMe = true;
    }

    /**
     * Remember the auth container for administration
     */
    public function setAuthSource(string $authSource): bool
    {
        $update = sprintf(
            "UPDATE %sfaquser SET auth_source = '%s' WHERE user_id = %d",
            Database::getTablePrefix(),
            $this->config->getDb()->escape($authSource),
            $this->getUserId()
        );

        return $this->config->getDb()->query($update);
    }

    /**
     * Saves remember me token in the database.
     */
    protected function setRememberMe(string $rememberMe): bool
    {
        $update = sprintf(
            "UPDATE %sfaquser SET remember_me = '%s' WHERE user_id = %d",
            Database::getTablePrefix(),
            $this->config->getDb()->escape($rememberMe),
            $this->getUserId()
        );

        return $this->config->getDb()->query($update);
    }

    /**
     * Sets login success/failure.
     */
    public function setSuccess(bool $success): bool
    {
        $loginState = (int)$success;
        $this->loginAttempts = 0;

        $update = sprintf(
            '
            UPDATE
                %sfaquser
            SET
                success = %d,
                login_attempts = %d
            WHERE
                user_id = %d',
            Database::getTablePrefix(),
            $loginState,
            $this->loginAttempts,
            $this->getUserId()
        );

        return (bool) $this->config->getDb()->query($update);
    }

    /**
     * @param array<string> $token
     * @return bool
     * @throws \JsonException
     */
    public function setTokenData(array $token): bool
    {
        $update = sprintf(
            "
            UPDATE
                %sfaquser
            SET
                refresh_token = '%s',
                access_token = '%s',
                code_verifier = '%s',
                jwt = '%s'
            WHERE
                user_id = %d",
            Database::getTablePrefix(),
            $token['refresh_token'],
            $token['access_token'],
            $token['code_verifier'],
            json_encode($token['jwt'], JSON_THROW_ON_ERROR),
            $this->getUserId()
        );

        return (bool) $this->config->getDb()->query($update);
    }

    /**
     * Sets IP and session timestamp plus lockout time, a success flag to
     * false.
     */
    protected function setLoginAttempt(): mixed
    {
        $this->loginAttempts++;

        $update = sprintf(
            "
            UPDATE
                %sfaquser
            SET
                session_timestamp ='%s',
                ip = '%s',
                success = 0,
                login_attempts = login_attempts + 1
            WHERE
                user_id = %d",
            Database::getTablePrefix(),
            $_SERVER['REQUEST_TIME'],
            Request::createFromGlobals()->getClientIp(),
            $this->getUserId()
        );

        return $this->config->getDb()->query($update);
    }

    /**
     * Checks if the last login attempt from the current user failed.
     */
    protected function isFailedLastLoginAttempt(): bool
    {
        $select = sprintf(
            "
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
            Database::getTablePrefix(),
            $this->getUserId(),
            $_SERVER['REQUEST_TIME'],
            $this->lockoutTime,
            Request::createFromGlobals()->getClientIp()
        );

        $result = $this->config->getDb()->query($select);
        if ($this->config->getDb()->numRows($result) !== 0) {
            return true;
        } else {
            return false;
        }
    }
}
