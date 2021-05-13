<?php

/**
 * Manages user authentication with databases.
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Lars Tiedemann <php@larstiedemann.de>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2005-2021 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2005-09-30
 */

namespace phpMyFAQ\Auth;

use phpMyFAQ\Auth;
use phpMyFAQ\Configuration;
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
    /** @var DatabaseDriver */
    private $db;

    /**
     * @inheritDoc
     */
    public function __construct(Configuration $config)
    {
        parent::__construct($config);

        $this->db = $this->config->getDb();
    }

    /**
     * @inheritDoc
     */
    public function create(string $login, string $password, string $domain = ''): bool
    {
        if ($this->isValidLogin($login) > 0) {
            $this->errors[] = User::ERROR_USER_ADD . User::ERROR_USER_LOGIN_NOT_UNIQUE;

            return false;
        }

        $add = sprintf(
            "INSERT INTO %sfaquserlogin (login, pass, domain) VALUES ('%s', '%s', '%s')",
            Database::getTablePrefix(),
            $this->db->escape($login),
            $this->db->escape($this->encContainer->setSalt($login)->encrypt($password)),
            $this->db->escape($domain)
        );


        $add = $this->db->query($add);
        $error = $this->db->error();

        if (strlen($error) > 0) {
            $this->errors[] = User::ERROR_USER_ADD . 'error(): ' . $error;

            return false;
        }
        if (!$add) {
            $this->errors[] = User::ERROR_USER_ADD;

            return false;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function update(string $login, string $password): bool
    {
        $change = sprintf(
            "UPDATE %sfaquserlogin SET pass = '%s' WHERE login = '%s'",
            Database::getTablePrefix(),
            $this->db->escape($this->encContainer->setSalt($login)->encrypt($password)),
            $this->db->escape($login)
        );

        $change = $this->db->query($change);
        $error = $this->db->error();

        if (strlen($error) > 0) {
            $this->errors[] = User::ERROR_USER_CHANGE . 'error(): ' . $error;

            return false;
        }
        if (!$change) {
            $this->errors[] = User::ERROR_USER_CHANGE;

            return false;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function delete(string $login): bool
    {
        $delete = sprintf(
            "DELETE FROM %sfaquserlogin WHERE login = '%s'",
            Database::getTablePrefix(),
            $this->db->escape($login)
        );

        $delete = $this->db->query($delete);
        $error = $this->db->error();

        if (strlen($error) > 0) {
            $this->errors[] = User::ERROR_USER_DELETE . 'error(): ' . $error;

            return false;
        }
        if (!$delete) {
            $this->errors[] = User::ERROR_USER_DELETE;

            return false;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function checkCredentials(string $login, string $password, array $optionalData = null): bool
    {
        $check = sprintf(
            "SELECT login, pass FROM %sfaquserlogin WHERE login = '%s'",
            Database::getTablePrefix(),
            $this->db->escape($login)
        );

        $check = $this->db->query($check);
        $error = $this->db->error();

        if (strlen($error) > 0) {
            $this->errors[] = User::ERROR_USER_NOT_FOUND . 'error(): ' . $error;

            return false;
        }

        $numRows = $this->db->numRows($check);
        if ($numRows < 1) {
            $this->errors[] = User::ERROR_USER_NOT_FOUND;

            return false;
        }

        // if login not unique, raise an error, but continue
        if ($numRows > 1) {
            $this->errors[] = User::ERROR_USER_LOGIN_NOT_UNIQUE;
        }

        // if multiple accounts are ok, just 1 valid required
        while ($user = $this->db->fetchArray($check)) {
            if ($user['pass'] === $this->encContainer->setSalt($user['login'])->encrypt($password)) {
                return true;
            }
        }
        $this->errors[] = User::ERROR_USER_INCORRECT_PASSWORD;

        return false;
    }

    /**
     * @inheritDoc
     */
    public function isValidLogin(string $login, array $optionalData = null): int
    {
        $check = sprintf(
            "SELECT login FROM %sfaquserlogin WHERE login = '%s'",
            Database::getTablePrefix(),
            $this->db->escape($login)
        );

        $check = $this->db->query($check);
        $error = $this->db->error();

        if (strlen($error) > 0) {
            $this->errors[] = $error;

            return 0;
        }

        return $this->db->numRows($check);
    }
}
