<?php

/**
 * Creates a new user object.
 *
 * A user are recognized by the session-id using getUserBySessionId(), by his
 * using getUserById() or by his nickname (login) using getUserByLogin(). New
 * are created using createNewUser().
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Lars Tiedemann <php@larstiedemann.de>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Sarah Hermann <sayh@gmx.de>
 * @copyright 2005-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2005-09-17
 */

namespace phpMyFAQ;

use Exception;
use phpMyFAQ\Auth\AuthDriverInterface;
use phpMyFAQ\Permission\MediumPermission;
use phpMyFAQ\User\UserData;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

if (!defined('PMF_ENCRYPTION_TYPE')) {
    define('PMF_ENCRYPTION_TYPE', 'hash');
}

/**
 * Class User
 *
 * @package phpMyFAQ
 */
class User
{
    final public const ERROR_USER_ADD = 'Account could not be created. ';
    final public const ERROR_USER_CANNOT_CREATE_USER = 'User account could not be created. ';
    final public const ERROR_USER_CANNOT_CREATE_USERDATA = 'Entry for user data could not be created. ';
    final public const ERROR_USER_CANNOT_DELETE_USER = 'User account could not be deleted. ';
    final public const ERROR_USER_CANNOT_DELETE_USERDATA = 'Entry for user data could not be deleted. ';
    final public const ERROR_USER_CHANGE = 'Account could not be updated. ';
    final public const ERROR_USER_DELETE = 'Account could not be deleted. ';
    final public const ERROR_USER_INCORRECT_LOGIN = 'The login name could not be found. ';
    final public const ERROR_USER_INCORRECT_PASSWORD = 'The password is not correct.';
    final public const ERROR_USER_INVALID_STATUS = 'Undefined user status.';
    final public const ERROR_USER_LOGINNAME_TOO_SHORT = 'The chosen login name is too short.';
    final public const ERROR_USER_LOGIN_NOT_UNIQUE = 'Specified login name already exists. ';
    final public const ERROR_USER_LOGIN_INVALID = 'The chosen login is invalid. A valid login has at least four ' .
        'characters. Only letters, numbers and underscore _ are allowed. The first letter must be a letter. ';
    final public const ERROR_USER_NO_PERM = 'No permission container specified.';
    final public const ERROR_USER_NO_USERID = 'No user-ID found. ';
    final public const ERROR_USER_NO_USERLOGINDATA = 'No user login data found. ';
    final public const ERROR_USER_NOT_FOUND = 'User account could not be found. ';
    final public const ERROR_USER_NO_AUTH_WRITABLE = 'No authentication object is writable.';
    final public const ERROR_USER_TOO_MANY_FAILED_LOGINS = 'You exceeded the maximum amounts of login attempts and ' .
        'are temporarily blocked. Please try again later.';

    final public const STATUS_USER_PROTECTED = 'User account is protected. ';
    final public const STATUS_USER_BLOCKED = 'User account is blocked. ';
    final public const STATUS_USER_ACTIVE = 'User account is active. ';

    /**
     * Permission container.
     *
     * @var Permission
     */
    public Permission $perm;

    /**
     * User-data storage container.
     */
    public ?UserData $userdata = null;

    /**
     * Public array that contains error messages.
     *
     * @var array<string>
     */
    public array $errors = [];

    /**
     * authentication container.
     *
     * @var array<string, AuthDriverInterface>
     */
    protected array $authContainer = [];

    /**
     * Default Authentication properties.
     *
     * @var array<string, array<string, string>|string|false>
     */
    private array $authData = [
        'authSource' => [
            'name' => 'database',
            'type' => 'local',
        ],
        'encType' => PMF_ENCRYPTION_TYPE,
        'readOnly' => false,
    ];

    /**
     * login string.
     */
    private string $login = '';

    /**
     * minimum length of login string (default: 2).
     */
    private int $loginMinLength = 2;

    /**
     * regular expression to find invalid login strings
     * (default: /^[a-z0-9][\w\.\-@]+/is ).
     */
    private string $validUsername = '/^[a-z0-9][\w\.\-@]+/is';

    /**
     * user ID.
     */
    private int $userId = -1;

    /**
     * Status of user.
     */
    private string $status = '';

    /**
     * IS the user a super admin?
     */
    private bool $isSuperAdmin = false;

    /** @var string $authSource Authentication, e.g. local, ldap, azure, sso, ... */
    private string $authSource = 'local';

    /**
     * array of allowed values for status.
     *
     * @var array<string>
     */
    private array $allowedStatus = [
        'active' => self::STATUS_USER_ACTIVE,
        'blocked' => self::STATUS_USER_BLOCKED,
        'protected' => self::STATUS_USER_PROTECTED,
    ];

    /**
     * Constructor.
     *
     * @throws Core\Exception
     */
    public function __construct(protected ?Configuration $config)
    {
        $perm = Permission::selectPerm($this->config->get('security.permLevel'), $this->config);
        if (!$this->addPerm($perm)) {
            return;
        }

        // Always create a 'local' authentication object (see: $authData)
        $this->authContainer = [];
        $auth = new Auth($this->config);

        $selectedAuth = $auth->selectAuth($this->getAuthSource('name'));
        $selectedAuth->selectEncType($this->getAuthData('encType'));
        $selectedAuth->setReadOnly($this->getAuthData('readOnly'));

        if (!$this->addAuth($selectedAuth, $this->getAuthSource('type'))) {
            return;
        }

        // additionally, set given $auth objects
        /** @phpstan-ignore-next-line */
        foreach ($this->authContainer as $name => $authObject) {
            if (!$this->addAuth($authObject, $name)) {
                break;
            }
        }

        // user data object
        $this->userdata = new UserData($this->config);
    }

    /**
     * Adds a permission object to the user.
     *
     * @param Permission $perm Permission object
     */
    public function addPerm(Permission $perm): bool
    {
        $this->perm = $perm;
        return true;
    }

    /**
     * Returns a specific entry from the auth data source array.
     */
    public function getAuthSource(string $key): ?string
    {
        if (isset($this->authData['authSource'][$key])) {
            return $this->authData['authSource'][$key];
        }
        return null;
    }

    public function getUserAuthSource(): string
    {
        return $this->authSource;
    }

    /**
     * Returns a specific entry from the auth data array.
     */
    public function getAuthData(string $key): mixed
    {
        if (isset($this->authData[$key])) {
            return $this->authData[$key];
        }
        return null;
    }

    /**
     * adds a new authentication object to the user object.
     *
     * @param AuthDriverInterface $auth Driver object
     * @param string                                                          $name Auth name
     */
    public function addAuth(AuthDriverInterface $auth, string $name): bool
    {
        if ($this->checkAuth($auth)) {
            $this->authContainer[$name] = $auth;

            return true;
        }

        return false;
    }

    /**
     * Returns true if auth is a valid authentication object.
     *
     * @param Auth $auth Auth object
     */
    protected function checkAuth(Auth $auth): bool
    {
        $methods = ['checkCredentials'];
        foreach ($methods as $method) {
            if (!method_exists($auth, $method)) {
                return false;
            }
        }

        return true;
    }

    /**
     * loads basic user information from the database selecting the user with
     * specified cookie information.
     */
    public function getUserByCookie(string $cookie): bool
    {
        $select = sprintf(
            "
            SELECT
                user_id,
                login,
                account_status
            FROM
                %sfaquser
            WHERE
                remember_me = '%s' AND account_status != 'blocked'",
            Database::getTablePrefix(),
            $this->config->getDb()->escape($cookie)
        );

        $res = $this->config->getDb()->query($select);
        if ($this->config->getDb()->numRows($res) !== 1) {
            $this->errors[] = self::ERROR_USER_INCORRECT_LOGIN;

            return false;
        }
        $user = $this->config->getDb()->fetchArray($res);

        // Don't ever log in via anonymous user
        if (-1 === $user['user_id']) {
            return false;
        }

        $this->userId = (int)$user['user_id'];
        $this->login = (string)$user['login'];
        $this->status = (string)$user['account_status'];

        // get user-data
        if (!$this->userdata instanceof UserData) {
            $this->userdata = new UserData($this->config);
        }
        $this->userdata->load($this->getUserId());

        return true;
    }

    /**
     * Returns the User ID of the user.
     */
    public function getUserId(): int
    {
        if (isset($this->userId)) {
            return $this->userId;
        }

        $this->userId = -1;
        $this->errors[] = self::ERROR_USER_NO_USERID;

        return -1;
    }

    /**
     * Checks if display name is already used. Returns true, if already in use.
     */
    public function checkDisplayName(string $name): bool
    {
        if (!$this->userdata instanceof UserData) {
            $this->userdata = new UserData($this->config);
        }

        if ($name === $this->userdata->fetch('display_name', $name)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Checks if email address is already used. Returns true, if already in use.
     */
    public function checkMailAddress(string $name): bool
    {
        if (!$this->userdata instanceof UserData) {
            $this->userdata = new UserData($this->config);
        }

        if ($name === $this->userdata->fetch('email', $name)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * search users by login.
     *
     * @param string $search Login name
     * @return array<int, array>
     */
    public function searchUsers(string $search): array
    {
        $select = sprintf(
            "
            SELECT
                login, 
                user_id,
                account_status
            FROM
                %sfaquser
            WHERE 
                login LIKE '%s'",
            Database::getTablePrefix(),
            $this->config->getDb()->escape($search . '%')
        );

        $res = $this->config->getDb()->query($select);
        if (!$res) {
            return [];
        }

        $result = [];
        while ($row = $this->config->getDb()->fetchArray($res)) {
            $result[] = $row;
        }

        return $result;
    }

    /**
     * Creates a new user and stores basic data in the database.
     *
     * @throws Core\Exception
     * @throws Exception
     */
    public function createUser(string $login, string $pass = '', string $domain = '', int $userId = 0): bool
    {
        foreach ($this->authContainer as $auth) {
            if (!$this->checkAuth($auth)) {
                return false;
            }
        }

        // is $login valid?
        if (!$this->isValidLogin($login)) {
            $this->errors[] = self::ERROR_USER_LOGINNAME_TOO_SHORT;

            return false;
        }

        // does $login already exist?
        if ($this->getUserByLogin($login, false)) {
            $this->errors[] = self::ERROR_USER_LOGIN_NOT_UNIQUE;

            return false;
        }

        // set user-ID
        if (0 == $userId) {
            $this->userId = $this->config->getDb()->nextId(Database::getTablePrefix() . 'faquser', 'user_id');
        } else {
            $this->userId = $userId;
        }

        // create user entry
        $insert = sprintf(
            "INSERT INTO %sfaquser (user_id, login, session_timestamp, member_since) VALUES (%d, '%s', %d, '%s')",
            Database::getTablePrefix(),
            $this->getUserId(),
            $this->config->getDb()->escape($login),
            $_SERVER['REQUEST_TIME'],
            date('YmdHis', $_SERVER['REQUEST_TIME'])
        );

        $this->config->getDb()->query($insert);
        if (!$this->userdata instanceof UserData) {
            $this->userdata = new UserData($this->config);
        }
        $data = $this->userdata->add($this->getUserId());
        if (!$data) {
            $this->errors[] = self::ERROR_USER_CANNOT_CREATE_USERDATA;

            return false;
        }

        // create authentication entry
        if (strlen($pass) === 0) {
            $pass = $this->createPassword();
        }
        $success = false;

        foreach ($this->authContainer as $name => $auth) {
            if ($auth->setReadOnly()) {
                continue;
            }
            if (!$auth->create($login, $pass, $domain)) {
                $this->errors[] = self::ERROR_USER_CANNOT_CREATE_USER . 'in Auth ' . $name;
            } else {
                $success = true;
            }
        }
        if (!$success) {
            return false;
        }

        if ($this->perm instanceof MediumPermission) {
            $this->perm->autoJoin($this->userId);
        }

        return $this->getUserByLogin($login, false);
    }

    /**
     * returns true if login is a valid login string.
     * $this->loginMinLength defines the minimum length the
     * login string. If login has more characters than allowed,
     * false is returned.
     * $this->login_invalidRegExp is a regular expression.
     * If login matches this false is returned.
     *
     * @param string $login Login name
     */
    public function isValidLogin(string $login): bool
    {
        if (strlen($login) < $this->loginMinLength || !preg_match($this->validUsername, $login)) {
            $this->errors[] = self::ERROR_USER_LOGIN_INVALID;

            return false;
        }

        return true;
    }

    /**
     * loads basic user information from the database selecting the user with
     * specified login.
     *
     * @param string $login Login name
     * @param bool   $raiseError Raise error?
     */
    public function getUserByLogin(string $login, bool $raiseError = true): bool
    {
        $select = sprintf(
            "
            SELECT
                user_id,
                login,
                account_status,
                is_superadmin
            FROM
                %sfaquser
            WHERE
                login = '%s'",
            Database::getTablePrefix(),
            $this->config->getDb()->escape($login)
        );

        $res = $this->config->getDb()->query($select);
        if ($this->config->getDb()->numRows($res) !== 1) {
            if ($raiseError) {
                $this->errors[] = self::ERROR_USER_INCORRECT_LOGIN;
            }

            return false;
        }

        $user = $this->config->getDb()->fetchArray($res);

        $this->userId = (int) $user['user_id'];
        $this->login = (string) $user['login'];
        $this->status = (string) $user['account_status'];
        $this->isSuperAdmin = (bool) $user['is_superadmin'];

        // get user-data
        if (!$this->userdata instanceof UserData) {
            $this->userdata = new UserData($this->config);
        }
        $this->userdata->load($this->getUserId());

        return true;
    }

    /**
     * Returns a new password.
     *
     * @throws Exception
     */
    public function createPassword(int $minimumLength = 8, bool $allowUnderscore = true): string
    {
        // To make passwords harder to get wrong, a few letters & numbers have been omitted.
        // This will ensure safety with browsers using fonts with confusable letters.
        // Removed: o,O,0,1,l,L
        $consonants = ['b', 'c', 'd', 'f', 'g', 'h', 'j', 'k', 'm', 'n', 'p', 'r', 's', 't', 'v', 'w', 'x', 'y', 'z'];
        $vowels = ['a', 'e', 'i', 'u'];
        $newPassword = '';
        $nextChar = '';
        $skipped = false;

        while (strlen($newPassword) < $minimumLength) {
            $caseFunc = random_int(0, 1) ? 'strtoupper' : 'strtolower';

            switch (random_int(0, $skipped ? 3 : ($allowUnderscore ? 5 : 4))) {
                case 0:
                case 1:
                    $nextChar = $caseFunc($consonants[random_int(0, 18)]);
                    break;
                case 2:
                case 3:
                    $nextChar = $caseFunc($vowels[random_int(0, 3)]);
                    break;
                case 4:
                    $nextChar = (string)random_int(2, 9);
                    break;
                case 5:
                    $newPassword .= '_';
                    continue 2;
            }

            $skipped = false;

            // Ensure letters and numbers only occur once.
            if (!str_contains($newPassword, $nextChar)) {
                $newPassword .= $nextChar;
            } else {
                $skipped = true;
            }
        }

        return $newPassword;
    }

    /**
     * deletes the user from the database.
     */
    public function deleteUser(): bool
    {
        if (!isset($this->userId) || $this->userId == 0) {
            $this->errors[] = self::ERROR_USER_NO_USERID;

            return false;
        }

        if (!isset($this->login) || strlen($this->login) == 0) {
            $this->errors[] = self::ERROR_USER_LOGIN_INVALID;

            return false;
        }

        if (
            isset($this->allowedStatus[$this->status]) &&
            $this->allowedStatus[$this->status] === self::STATUS_USER_PROTECTED
        ) {
            $this->errors[] = self::ERROR_USER_CANNOT_DELETE_USER . self::STATUS_USER_PROTECTED;

            return false;
        }

        $this->perm->refuseAllUserRights($this->userId);

        $delete = sprintf(
            'DELETE FROM %sfaquser WHERE user_id = %d',
            Database::getTablePrefix(),
            $this->userId
        );

        $res = $this->config->getDb()->query($delete);
        if (!$res) {
            $this->errors[] = self::ERROR_USER_CANNOT_DELETE_USER . 'error(): ' . $this->config->getDb()->error();

            return false;
        }

        if (!$this->userdata instanceof UserData) {
            $this->userdata = new UserData($this->config);
        }
        $data = $this->userdata->delete($this->getUserId());
        if (!$data) {
            $this->errors[] = self::ERROR_USER_CANNOT_DELETE_USERDATA;

            return false;
        }

        $readOnly = 0;
        $authCount = 0;
        $delete = [];
        foreach ($this->authContainer as $auth) {
            ++$authCount;
            if ($auth->setReadOnly()) {
                ++$readOnly;
                continue;
            }
            $delete[] = $auth->delete($this->login);
        }

        if ($readOnly == $authCount) {
            $this->errors[] = self::ERROR_USER_NO_AUTH_WRITABLE;
        }
        if (!in_array(true, $delete)) {
            return false;
        }

        return true;
    }

    /**
     * Returns a string with error messages.
     * The string returned by error() contains messages for all errors that
     * during object processing. Messages are separated by new lines.
     * Error messages are stored in the public array errors.
     */
    public function error(): string
    {
        $message = '';

        foreach ($this->errors as $error) {
            $message .= $error . "<br>\n";
        }
        $this->errors = [];

        return $message;
    }

    /**
     * Returns the data aof the auth container.
     *
     * @return AuthDriverInterface[]
     */
    public function getAuthContainer(): array
    {
        return $this->authContainer;
    }

    /**
     * Returns an array with the user-IDs of all users found in
     * the database. By default, the Anonymous User will not be returned.
     *
     * @param bool $withoutAnonymous Without anonymous?
     * @param bool $allowBlockedUsers Allow blocked users as well, e.g. in admin
     * @return array<int>
     */
    public function getAllUsers(bool $withoutAnonymous = true, bool $allowBlockedUsers = true): array
    {
        $select = sprintf(
            '
            SELECT
                user_id
            FROM
                %sfaquser
            WHERE
                1 = 1
            %s
            %s
            ORDER BY
                user_id ASC',
            Database::getTablePrefix(),
            ($withoutAnonymous ? 'AND user_id <> -1' : ''),
            ($allowBlockedUsers ? '' : "AND account_status != 'blocked'")
        );

        $res = $this->config->getDb()->query($select);
        if (!$res) {
            return [];
        }

        $result = [];
        while ($row = $this->config->getDb()->fetchArray($res)) {
            $result[] = (int) $row['user_id'];
        }

        return $result;
    }

    /**
     * Loads basic user information from the database selecting the user with
     * specified user-ID.
     *
     * @param int  $userId User ID
     * @param bool $allowBlockedUsers Allow blocked users as well, e.g. in admin
     */
    public function getUserById(int $userId, bool $allowBlockedUsers = false): bool
    {
        $select = sprintf(
            '
            SELECT
                user_id,
                login,
                account_status,
                is_superadmin,
                auth_source
            FROM
                %sfaquser
            WHERE
                user_id = %d ' . ($allowBlockedUsers ? '' : "AND account_status != 'blocked'"),
            Database::getTablePrefix(),
            $userId
        );

        $res = $this->config->getDb()->query($select);
        if ($this->config->getDb()->numRows($res) != 1) {
            $this->errors[] = self::ERROR_USER_NO_USERID . 'error(): ' . $this->config->getDb()->error();

            return false;
        }

        $user = $this->config->getDb()->fetchArray($res);

        $this->userId = (int) $user['user_id'];
        $this->login = (string) $user['login'];
        $this->status = (string) $user['account_status'];
        $this->isSuperAdmin = (bool) $user['is_superadmin'];
        $this->authSource = (string) $user['auth_source'];

        // get encrypted password
        // @todo: Add a getEncPassword method to the Auth* classes for the (local and remote) Auth Sources.
        if ('db' === $this->getAuthSource('name')) {
            $select = sprintf(
                "
                SELECT
                    pass
                FROM
                    %sfaquserlogin
                WHERE
                    login = '%s'",
                Database::getTablePrefix(),
                $this->login
            );

            $res = $this->config->getDb()->query($select);
            if ($this->config->getDb()->numRows($res) != 1) {
                $this->errors[] = self::ERROR_USER_NO_USERLOGINDATA . 'error(): ' . $this->config->getDb()->error();

                return false;
            }
        }
        // get user-data
        if (!$this->userdata instanceof UserData) {
            $this->userdata = new UserData($this->config);
        }
        $this->userdata->load($this->getUserId());

        return true;
    }

    /**
     * Returns the data of the current user.
     *
     * @param string $field Field
     * @return array<string>|string|int|null
     */
    public function getUserData(string $field = '*'): array|int|string|null
    {
        if (!($this->userdata instanceof UserData)) {
            $this->userdata = new UserData($this->config);
        }

        return $this->userdata->get($field);
    }

    /**
     * Adds user data.
     *
     * @param array<string> $data Array with user data
     */
    public function setUserData(array $data): bool
    {
        if (!($this->userdata instanceof UserData)) {
            $this->userdata = new UserData($this->config);
        }
        $this->userdata->load($this->getUserId());

        return $this->userdata->set(array_keys($data), array_values($data));
    }

    /**
     * returns the user's login.
     */
    public function getLogin(): string
    {
        return $this->login;
    }

    /**
     * Returns the user ID from the given email address
     */
    public function getUserIdByEmail(string $email): int
    {
        if (!$this->userdata instanceof UserData) {
            $this->userdata = new UserData($this->config);
        }

        $userData = $this->userdata->fetchAll('email', $email);

        return $userData['user_id'];
    }

    /**
     * Returns true or false for the visibility for the given email
     * address, if the user is not a registered user, the method
     * returns false for anonymous users
     */
    public function getUserVisibilityByEmail(string $email): bool
    {
        if (!$this->userdata instanceof UserData) {
            $this->userdata = new UserData($this->config);
        }

        $userData = $this->userdata->fetchAll('email', $email);

        return !isset($userData['is_visible']) || $userData['is_visible'];
    }

    /**
     * Returns true on success.
     * This will change a users' status to active, and send an email with a new password.
     *
     * @throws Exception|TransportExceptionInterface
     */
    public function activateUser(): bool
    {
        if ($this->getStatus() == 'blocked') {
            // Generate and change user password.
            $newPassword = $this->createPassword();
            $this->changePassword($newPassword);
            // Send activation email.
            $subject = '[%sitename%] Login name / activation';
            $message = sprintf(
                "\nName: %s\nLogin name: %s\nNew password: %s\n\n",
                $this->getUserData('display_name'),
                $this->getLogin(),
                $newPassword
            );
            // Only set to active if the activation mail sent correctly.
            if ($this->mailUser($subject, $message)) {
                return $this->setStatus('active');
            }
            return true;
        }

        return false;
    }

    /**
     * returns the user's status.
     */
    public function getStatus(): string
    {
        if (isset($this->status) && strlen($this->status) > 0) {
            return $this->status;
        }

        return '';
    }

    /**
     * Sets the user's status and updates the database entry.
     *
     * @param string $status Status
     */
    public function setStatus(string $status): bool
    {
        // is status allowed?
        $status = strtolower($status);
        if (!in_array($status, array_keys($this->allowedStatus))) {
            $this->errors[] = self::ERROR_USER_INVALID_STATUS;

            return false;
        }

        // update status
        $this->status = $status;
        $update = sprintf(
            "
            UPDATE
                %sfaquser
            SET
                account_status = '%s'
            WHERE
                user_id = %d",
            Database::getTablePrefix(),
            $this->config->getDb()->escape($status),
            $this->userId
        );

        $res = $this->config->getDb()->query($update);

        if ($res) {
            return true;
        }

        return false;
    }

    /**
     * changes the user's password. If $pass is omitted, a new
     * password is generated using the createPassword() method.
     *
     * @param string $pass Password
     * @throws Exception
     */
    public function changePassword(string $pass = ''): bool
    {
        foreach ($this->authContainer as $auth) {
            if (!$this->checkAuth($auth)) {
                return false;
            }
        }

        $login = $this->getLogin();
        if ($pass == '') {
            $pass = $this->createPassword();
        }

        $success = false;
        foreach ($this->authContainer as $auth) {
            if ($auth->setReadOnly()) {
                continue;
            }
            if (!$auth->update($login, $pass)) {
                continue;
            } else {
                $success = true;
            }
        }

        return $success;
    }

    /**
     * Sends mail to the current user.
     *
     * @throws Core\Exception|TransportExceptionInterface
     */
    public function mailUser(string $subject, string $message): int
    {
        $mail = new Mail($this->config);
        $mail->addTo($this->getUserData('email'));
        $mail->subject = $subject;
        $mail->message = $message;
        $result = $mail->send();
        unset($mail);

        return $result;
    }

    /**
     * Returns true, if a user is a super admin.
     */
    public function isSuperAdmin(): bool
    {
        return $this->isSuperAdmin;
    }

    /**
     * Sets the users "is_superadmin" flag and updates the database entry.
     */
    public function setSuperAdmin(bool $isSuperAdmin): bool
    {
        $this->isSuperAdmin = $isSuperAdmin;
        $update = sprintf(
            "
            UPDATE
                %sfaquser
            SET
                is_superadmin = %d
            WHERE
                user_id = %d",
            Database::getTablePrefix(),
            (int)$this->isSuperAdmin,
            $this->userId
        );

        $res = $this->config->getDb()->query($update);

        if ($res) {
            return true;
        }

        return false;
    }
}
