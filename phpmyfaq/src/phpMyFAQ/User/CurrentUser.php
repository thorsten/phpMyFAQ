<?php

/**
 * Manages the authentication process using PHP sessions.
 * The CurrentUser class is an extension of the User class.
 * It provides methods to manage user authentication using multiple database accesses.
 * There are three ways of making a new current user object, using the login(), getFromSession(), getFromCookie() or
 * manually. login(), getFromSession() and getFromCookie() may be combined.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Lars Tiedemann <php@larstiedemann.de>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2005-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2005-09-28
 */

declare(strict_types=1);

namespace phpMyFAQ\User;

use phpMyFAQ\Auth;
use phpMyFAQ\Auth\AuthDriverInterface;
use phpMyFAQ\Auth\AuthException;
use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Filter;
use phpMyFAQ\Session\SessionWrapper;
use phpMyFAQ\User;
use SensitiveParameter;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class CurrentUser
 *
 * @package   phpMyFAQ
 * @author    Lars Tiedemann <php@larstiedemann.de>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2005-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2005-09-28
 */
class CurrentUser extends User
{
    use CurrentUserAccountStateTrait;

    #[\Override]
    protected function accountStateConfiguration(): Configuration
    {
        return $this->configuration;
    }

    use CurrentUserSessionLookupTrait;

    public const string SESSION_CURRENT_USER = 'CURRENT_USER';
    public const string SESSION_ID_TIMESTAMP = 'SESSION_TIMESTAMP';

    private const int PMF_REMEMBER_ME_EXPIRED_TIME = 1_209_600; // 2 weeks

    private bool $loggedIn = false;

    /**
     * Specifies the timeout for the session in minutes. If the session ID was
     * not updated for the last $this->sessionTimeout minutes, the CurrentUser
     * will be logged out automatically if no cookie was set.
     */
    private int $sessionTimeout = PMF_AUTH_TIMEOUT;

    /**
     * The Session class object
     */
    private readonly UserSession $userSession;

    /**
     * The Session wrapper for Symfony Session
     */
    private readonly SessionWrapper $sessionWrapper;

    /**
     * Specifies the timeout for the session-ID in minutes. If the session ID
     * was not updated for the last $this->sessionIdTimeout minutes, it will
     * be updated. If set to 0, the session ID will be updated on every click.
     * The session ID timeout must not be greater than Session timeout.
     */
    private int $sessionIdTimeout = 1;

    /**
     * Remember me activated or deactivated.
     */
    private bool $rememberMe = false;

    /**
     * Failed attempts above this number lock the account for the lockout time.
     */
    private const int MAX_FAILED_LOGIN_ATTEMPTS = 5;

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
     *
     * @throws Exception
     * @throws \Exception
     */
    public function __construct(Configuration $configuration)
    {
        parent::__construct($configuration);
        $this->userSession = new UserSession($configuration);
        $this->sessionWrapper = new SessionWrapper();
    }

    /**
     * Checks the given login and password in all auth-objects.
     * Returns true for success, otherwise false.
     * On success, the CurrentUser instance will be labeled as logged in.
     * The name of the successful auth container will be stored in the user table.
     * A new auth object may be added by using addAuth() method.
     * The given password must not be encrypted, since the auth object takes care of the encryption method.
     *
     * @param string $login Login name
     * @param string $password Password
     * @throws UserException
     * @throws AuthException
     * @throws \Exception
     */
    public function login(string $login, #[SensitiveParameter] string $password): bool
    {
        $request = Request::createFromGlobals();

        // Check if the login is an email address and convert it to a username if needed
        if (
            true === $this->configuration->get(item: 'security.loginWithEmailAddress')
            && is_string(Filter::filterVar($login, FILTER_VALIDATE_EMAIL))
        ) {
            $userId = $this->getUserIdByEmail($login);
            $this->getUserById($userId);
            $login = $this->getLogin();
        }

        // First check for brute force attack
        $this->getUserByLogin($login);
        if ($this->isFailedLastLoginAttempt()) {
            throw new UserException(parent::ERROR_USER_TOO_MANY_FAILED_LOGINS);
        }

        // Extract domain if LDAP is active and ldap_use_domain_prefix is true
        $optData = [];
        if (
            $this->configuration->isLdapActive()
            && true === $this->configuration->get(item: 'ldap.ldap_use_domain_prefix')
            && '' !== $password
            && ($pos = strpos($login, needle: '\\')) !== false
        ) {
            $optData['domain'] = $pos !== 0 ? substr($login, offset: 0, length: $pos) : '';
            $login = substr($login, $pos + 1);
        }

        // Handle SSO authentication
        if (
            true === $this->configuration->get(item: 'security.ssoSupport')
            && '' !== (string) $request->server->get('REMOTE_USER')
            && '' === $password
        ) {
            $ssoLogin = strtok($login, token: chr(64) . '\\');
            $login = $ssoLogin === false ? $login : $ssoLogin;
        }

        // Attempt to authenticate a user by login and password
        $this->authContainer = $this->sortAuthContainer($this->authContainer);
        foreach ($this->authContainer as $authSource => $auth) {
            if ($auth->isValidLogin($login, $optData) === 0) {
                continue; // Login does not exist, try the next auth method
            }

            try {
                $credentialsAreValid = $auth->checkCredentials($login, $password, $optData);
            } catch (AuthException) {
                // Drivers signal a wrong password by throwing; treat it as a failed
                // attempt so the fall-through failure handling counts it for lockout.
                $credentialsAreValid = false;
            }

            if (!$credentialsAreValid) {
                continue; // Incorrect password, try the next auth method
            }

            // Login successful, proceed with post-login actions
            $this->getUserByLogin($login);
            if ((int) $this->getUserData('twofactor_enabled') !== 1) {
                $this->setLoggedIn(true);
                $this->updateSessionId(true);
                $this->saveToSession();
            }

            if ($this->rememberMe) {
                // The remember-me cookie is a password-bypassing credential, so it must be an
                // unpredictable CSPRNG value (not derived from the session id) and stored hashed
                // at rest so a database read cannot yield a usable cookie.
                $rememberMeToken = bin2hex(random_bytes(32));
                $this->setRememberMe(hash('sha256', $rememberMeToken));
                $this->userSession->setCookie(
                    UserSession::COOKIE_NAME_REMEMBER_ME,
                    $rememberMeToken,
                    (int) $request->server->get('REQUEST_TIME') + self::PMF_REMEMBER_ME_EXPIRED_TIME,
                );
            }

            if (!$this->setAuthSource($authSource)) {
                $this->setSuccess(false);
                return false;
            }

            if ((int) $this->getUserData('twofactor_enabled') !== 1) {
                $this->setSuccess(true);
            }

            return true; // Login successful
        }

        // No successful login: count the failed attempt so the account lockout engages
        $this->setLoginAttempt();

        if (
            true === $this->configuration->get(item: 'security.loginWithEmailAddress')
            && !is_string(Filter::filterVar($login, FILTER_VALIDATE_EMAIL))
        ) {
            throw new UserException(parent::ERROR_USER_INCORRECT_LOGIN);
        }

        if (!$this->isFailedLastLoginAttempt()) {
            throw new UserException(parent::ERROR_USER_INCORRECT_PASSWORD);
        }

        throw new UserException(parent::ERROR_USER_TOO_MANY_FAILED_LOGINS);
    }

    /**
     * Returns true if CurrentUser is logged in, otherwise false.
     */
    public function isLoggedIn(): bool
    {
        return $this->loggedIn;
    }

    /**
     * Sets loggedIn to true if the 2FA-auth was successful and saves the login to session.
     */
    public function twoFactorSuccess(): bool
    {
        $this->setLoggedIn(true);
        $this->updateSessionId(true);
        $this->saveToSession();
        $this->setSuccess(true);

        return true;
    }

    /**
     * Sets loggedIn to false and deletes the login from session.
     */
    public function setLoggedIn(bool $loggedIn): void
    {
        $this->loggedIn = $loggedIn;
    }

    /**
     * Returns false if the CurrentUser object stored in the session is valid and not timed out.
     * There are two parameters for session timeouts: $this->sessionTimeout and $this->sessionIdTimeout.
     */
    public function sessionIsTimedOut(): bool
    {
        return $this->sessionTimeout <= $this->sessionAge();
    }

    /**
     * Returns false if the session-ID is not timed out.
     */
    public function sessionIdIsTimedOut(): bool
    {
        return $this->sessionIdTimeout <= $this->sessionAge();
    }

    /**
     * Returns the age of the current session-ID in minutes.
     */
    public function sessionAge(): float
    {
        if (!$this->sessionWrapper->has(self::SESSION_ID_TIMESTAMP)) {
            return 0;
        }

        $requestTime = (int) Request::createFromGlobals()->server->get('REQUEST_TIME');
        $sessionTimestamp = (int) $this->sessionWrapper->get(self::SESSION_ID_TIMESTAMP);
        return ($requestTime - $sessionTimestamp) / 60;
    }

    /**
     * Returns an associative array with session information stored
     * in the user table. The array has the following keys:
     * session_id, session_timestamp and ip.
     *
     * @return array<array-key, mixed>
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
                user_id = %d', Database::getTablePrefix(), $this->getUserId());

        $res = $this->configuration->getDb()->query($select);
        if (!$res || $this->configuration->getDb()->numRows($res) !== 1) {
            return [];
        }

        $sessionInfo = $this->configuration->getDb()->fetchArray($res);

        return is_array($sessionInfo) ? $sessionInfo : [];
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
        // renew the session-ID; API and CLI logins run without an active PHP session
        $oldSessionId = session_id();
        if (session_status() === PHP_SESSION_ACTIVE && session_regenerate_id(true)) {
            $sessionPath = (string) session_save_path();
            if (str_contains($sessionPath, ';')) {
                $sessionPath = substr($sessionPath, (int) strpos($sessionPath, needle: ';') + 1);
            }

            $sessionFilename = $sessionPath . '/sess_' . $oldSessionId;
            if (file_exists($sessionFilename)) {
                unlink($sessionFilename);
            }
        }

        // store session-ID age
        $this->sessionWrapper->set(
            self::SESSION_ID_TIMESTAMP,
            Request::createFromGlobals()->server->get('REQUEST_TIME'),
        );

        $requestTime = (int) Request::createFromGlobals()->server->get('REQUEST_TIME');

        // save session information in the user table
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
            $requestTime,
            $updateLastLogin ? "last_login = '" . date(format: 'YmdHis', timestamp: $requestTime) . "'," : '',
            Request::createFromGlobals()->getClientIp(),
            $this->getUserId(),
        );

        $res = $this->configuration->getDb()->query($update);
        if (!$res) {
            $this->errors[] = $this->configuration->getDb()->error();

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
        $this->sessionWrapper->set(self::SESSION_CURRENT_USER, $this->getUserId());
    }

    /**
     * Deletes the CurrentUser from the session. The user
     * will be logged out. Return true to success, otherwise false.
     */
    public function deleteFromSession(bool $deleteCookie = false): bool
    {
        // delete CurrentUser object from session
        $this->sessionWrapper->remove(self::SESSION_CURRENT_USER);

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
            $this->getUserId(),
        );

        $res = $this->configuration->getDb()->query($update);

        if (!$res) {
            $this->errors[] = $this->configuration->getDb()->error();

            return false;
        }

        if ($deleteCookie) {
            $this->userSession->setCookie(UserSession::COOKIE_NAME_REMEMBER_ME, '');
        }

        // @todo Check if session_destroy() is really needed here
        //session_destroy();

        return true;
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
     * Enables to "remember me" decision.
     */
    public function enableRememberMe(): void
    {
        $this->rememberMe = true;
    }

    /**
     * Sets the auth container
     */
    #[\Override]
    public function setAuthSource(string $authSource): bool
    {
        $update = sprintf(
            "UPDATE %sfaquser SET auth_source = '%s' WHERE user_id = %d",
            Database::getTablePrefix(),
            $this->configuration->getDb()->escape($authSource),
            $this->getUserId(),
        );

        return (bool) $this->configuration->getDb()->query($update);
    }

    /**
     * Saves remember me token in the database.
     */
    public function setRememberMe(string $rememberMe): bool
    {
        $update = sprintf(
            "UPDATE %sfaquser SET remember_me = '%s' WHERE user_id = %d",
            Database::getTablePrefix(),
            $this->configuration->getDb()->escape($rememberMe),
            $this->getUserId(),
        );

        return (bool) $this->configuration->getDb()->query($update);
    }

    /**
     * Sets login success/failure.
     */
    public function setSuccess(bool $success): bool
    {
        $loginState = (int) $success;
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
            $this->getUserId(),
        );

        return (bool) $this->configuration->getDb()->query($update);
    }

    /**
     * @param array{refresh_token: string, access_token: string, code_verifier: string, jwt: mixed} $token
     * @throws \JsonException
     */
    public function setTokenData(#[\SensitiveParameter] array $token): bool
    {
        $db = $this->configuration->getDb();
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
            $db->escape($token['refresh_token']),
            $db->escape($token['access_token']),
            $db->escape($token['code_verifier']),
            $db->escape(json_encode($token['jwt'], JSON_THROW_ON_ERROR)),
            $this->getUserId(),
        );

        return (bool) $db->query($update);
    }

    /**
     * Sets IP and session timestamp plus lockout time, a success flag to
     * false.
     */
    protected function setLoginAttempt(): mixed
    {
        ++$this->loginAttempts;

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
            (int) Request::createFromGlobals()->server->get('REQUEST_TIME'),
            Request::createFromGlobals()->getClientIp(),
            $this->getUserId(),
        );

        return $this->configuration->getDb()->query($update);
    }

    /**
     * Checks whether the account is locked out after too many recent failed logins.
     * Deliberately independent of the client IP: an attacker rotating IPs must not
     * be able to keep guessing a single account's password.
     */
    protected function isFailedLastLoginAttempt(): bool
    {
        $select = sprintf(
            "
            SELECT
                session_timestamp,
                success,
                login_attempts
            FROM
                %sfaquser
            WHERE
                user_id = %d
            AND
                ('%d' - session_timestamp) <= %d
            AND
                success = 0
            AND
                login_attempts > %d",
            Database::getTablePrefix(),
            $this->getUserId(),
            (int) Request::createFromGlobals()->server->get('REQUEST_TIME'),
            $this->lockoutTime,
            self::MAX_FAILED_LOGIN_ATTEMPTS,
        );

        /** @var mixed $result */
        $result = $this->configuration->getDb()->query($select);
        return $this->configuration->getDb()->numRows($result) !== 0;
    }

    /**
     * Sorts the auth container array.
     * @param array<string, Auth&AuthDriverInterface> $authContainer
     * @return array<string, Auth&AuthDriverInterface>
     */
    protected function sortAuthContainer(array $authContainer): array
    {
        uksort($authContainer, static function ($first, $second): int {
            if ($first === 'local') {
                return 1;
            }

            if ($second === 'local') {
                return -1;
            }

            return 0;
        });

        return $authContainer;
    }
}
