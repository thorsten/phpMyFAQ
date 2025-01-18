<?php

/**
 * Manages user authentication with databases.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Lars Tiedemann <php@larstiedemann.de>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2005-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2005-09-30
 */

namespace phpMyFAQ\Auth;

use phpMyFAQ\Auth;
use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Database\DatabaseDriver;
use phpMyFAQ\User;

/**
 * Class AuthDatabase
 *
 * @package phpMyFAQ\Auth
 */
class AuthDatabase extends Auth implements AuthDriverInterface
{
    private readonly DatabaseDriver $databaseDriver;

    /**
     * @inheritDoc
     */
    public function __construct(Configuration $configuration)
    {
        parent::__construct($configuration);

        $this->databaseDriver = $this->configuration->getDb();
    }

    /**
     * @inheritDoc
     * @throws AuthException
     */
    public function create(string $login, #[\SensitiveParameter] string $password, string $domain = ''): bool
    {
        if ($this->isValidLogin($login) > 0) {
            throw new AuthException(User::ERROR_USER_ADD . ': ' . User::ERROR_USER_LOGIN_NOT_UNIQUE);
        }

        $add = sprintf(
            "INSERT INTO %sfaquserlogin (login, pass, domain) VALUES ('%s', '%s', '%s')",
            Database::getTablePrefix(),
            $this->databaseDriver->escape($login),
            $this->databaseDriver->escape($this->encContainer->setSalt($login)->encrypt($password)),
            $this->databaseDriver->escape($domain)
        );


        $add = $this->databaseDriver->query($add);

        $error = $this->databaseDriver->error();

        if (strlen($error) > 0) {
            throw new AuthException(User::ERROR_USER_ADD . ': ' . $error);
        }

        if (!$add) {
            throw new AuthException(User::ERROR_USER_ADD);
        }

        return true;
    }

    /**
     * @inheritDoc
     * @throws AuthException
     */
    public function update(string $login, #[\SensitiveParameter] string $password): bool
    {
        if ($this->isValidLogin($login) < 1) {
            throw new AuthException(User::ERROR_USER_CHANGE . ': ' . User::ERROR_USER_NOT_FOUND);
        }

        $change = sprintf(
            "UPDATE %sfaquserlogin SET pass = '%s' WHERE login = '%s'",
            Database::getTablePrefix(),
            $this->databaseDriver->escape($this->encContainer->setSalt($login)->encrypt($password)),
            $this->databaseDriver->escape($login)
        );

        $change = $this->databaseDriver->query($change);

        $error = $this->databaseDriver->error();

        if (strlen($error) > 0) {
            throw new AuthException(User::ERROR_USER_CHANGE . ': ' . $error);
        }

        if (!$change) {
            throw new AuthException(User::ERROR_USER_CHANGE);
        }

        return true;
    }

    /**
     * @inheritDoc
     * @throws AuthException|Exception
     */
    public function delete(string $login): bool
    {
        if ($this->isValidLogin($login) < 1) {
            throw new Exception(User::ERROR_USER_DELETE . User::ERROR_USER_NOT_FOUND);
        }

        $delete = sprintf(
            "DELETE FROM %sfaquserlogin WHERE login = '%s'",
            Database::getTablePrefix(),
            $this->databaseDriver->escape($login)
        );

        $delete = $this->databaseDriver->query($delete);

        $error = $this->databaseDriver->error();

        if (strlen($error) > 0) {
            throw new AuthException(User::ERROR_USER_DELETE . ': ' . $error);
        }

        if (!$delete) {
            throw new AuthException(User::ERROR_USER_DELETE . ': ' . $error);
        }

        return true;
    }

    /**
     * @inheritDoc
     * @throws AuthException
     */
    public function checkCredentials(
        string $login,
        #[\SensitiveParameter] string $password,
        ?array $optionalData = null
    ): bool {
        $check = sprintf(
            "SELECT login, pass FROM %sfaquserlogin WHERE login = '%s'",
            Database::getTablePrefix(),
            $this->databaseDriver->escape($login)
        );

        $check = $this->databaseDriver->query($check);

        $error = $this->databaseDriver->error();

        if (strlen($error) > 0) {
            throw new AuthException(User::ERROR_USER_NOT_FOUND . ': ' . $error);
        }

        $numRows = $this->databaseDriver->numRows($check);
        if ($numRows < 1) {
            throw new AuthException(User::ERROR_USER_NOT_FOUND);
        }

        // if login not unique, raise an error, but continue
        if ($numRows > 1) {
            throw new AuthException(User::ERROR_USER_LOGIN_NOT_UNIQUE);
        }

        // if multiple accounts are ok, just 1 valid required
        while ($user = $this->databaseDriver->fetchArray($check)) {
            if ($user['pass'] === $this->encContainer->setSalt($user['login'])->encrypt($password)) {
                return true;
            }
        }

        throw new AuthException(User::ERROR_USER_INCORRECT_PASSWORD);
    }

    /**
     * @inheritDoc
     * @throws AuthException
     */
    public function isValidLogin(string $login, ?array $optionalData = null): int
    {
        $check = sprintf(
            "SELECT login FROM %sfaquserlogin WHERE login = '%s'",
            Database::getTablePrefix(),
            $this->databaseDriver->escape($login)
        );

        $check = $this->databaseDriver->query($check);

        $error = $this->databaseDriver->error();

        if (strlen($error) > 0) {
            throw new AuthException($error);
        }

        return $this->databaseDriver->numRows($check);
    }
}
