<?php

/**
 * Manages user authentication with databases.
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ 
 *
 * @author    Lars Tiedemann <php@larstiedemann.de>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2005-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      https://www.phpmyfaq.de
 * @since     2005-09-30
 */
if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Auth_Db.
 *
 * @category  phpMyFAQ 
 *
 * @author    Lars Tiedemann <php@larstiedemann.de>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2005-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      https://www.phpmyfaq.de
 * @since     2005-09-30
 */
class PMF_Auth_Db extends PMF_Auth implements PMF_Auth_Driver
{
    /**
     * Database connection.
     *
     * @var PMF_DB_Driver
     */
    private $db = null;

    /**
     * Constructor.
     *
     * @param PMF_Configuration $config
     *
     * @return PMF_Auth_Db
     */
    public function __construct(PMF_Configuration $config)
    {
        parent::__construct($config);

        $this->db = $this->_config->getDb();
    }

    /**
     * Adds a new user account to the faquserlogin table. Returns true on
     * success, otherwise false. Error messages are added to the array errors.
     *
     * @param string $login Loginname
     * @param string $pass  Password
     *
     * @return bool
     */
    public function add($login, $pass)
    {
        if ($this->checkLogin($login) > 0) {
            $this->errors[] = PMF_User::ERROR_USER_ADD.PMF_User::ERROR_USER_LOGIN_NOT_UNIQUE;

            return false;
        }

        $add = sprintf("
            INSERT INTO
                %sfaquserlogin
            (login, pass)
                VALUES
            ('%s', '%s')",
            PMF_Db::getTablePrefix(),
            $this->db->escape($login),
            $this->db->escape($this->encContainer->setSalt($login)->encrypt($pass)));

        $add = $this->db->query($add);
        $error = $this->db->error();

        if (strlen($error) > 0) {
            $this->errors[] = PMF_User::ERROR_USER_ADD.'error(): '.$error;

            return false;
        }
        if (!$add) {
            $this->errors[] = PMF_User::ERROR_USER_ADD;

            return false;
        }

        return true;
    }

    /**
     * Changes the password for the account specified by login.
     *
     * Returns true on success, otherwise false.
     *
     * Error messages are added to the array errors.
     *
     * @param string $login Loginname
     * @param string $pass  Password
     *
     * @return bool
     */
    public function changePassword($login, $pass)
    {
        $change = sprintf("
            UPDATE
                %sfaquserlogin
            SET
                pass = '%s'
            WHERE
                login = '%s'",
            PMF_Db::getTablePrefix(),
            $this->db->escape($this->encContainer->setSalt($login)->encrypt($pass)),
            $this->db->escape($login)
        );

        $change = $this->db->query($change);
        $error = $this->db->error();

        if (strlen($error) > 0) {
            $this->errors[] = PMF_User::ERROR_USER_CHANGE.'error(): '.$error;

            return false;
        }
        if (!$change) {
            $this->errors[] = PMF_User::ERROR_USER_CHANGE;

            return false;
        }

        return true;
    }

    /**
     * Deletes the user account specified by login.
     *
     * Returns true on success, otherwise false.
     *
     * Error messages are added to the array errors.
     *
     * @param string $login Loginname
     *
     * @return bool
     */
    public function delete($login)
    {
        $delete = sprintf("
            DELETE FROM
                %sfaquserlogin
            WHERE
                login = '%s'",
            PMF_Db::getTablePrefix(),
            $this->db->escape($login));

        $delete = $this->db->query($delete);
        $error = $this->db->error();

        if (strlen($error) > 0) {
            $this->errors[] = PMF_User::ERROR_USER_DELETE.'error(): '.$error;

            return false;
        }
        if (!$delete) {
            $this->errors[] = PMF_User::ERROR_USER_DELETE;

            return false;
        }

        return true;
    }

    /**
     * checks the password for the given user account.
     *
     * Returns true if the given password for the user account specified by
     * is correct, otherwise false.
     * Error messages are added to the array errors.
     *
     * @param string $login        Loginname
     * @param string $password     Password
     * @param array  $optionalData Optional data
     *
     * @return bool
     */
    public function checkPassword($login, $password, Array $optionalData = null)
    {
        $check = sprintf("
            SELECT
                login, pass
            FROM
                %sfaquserlogin
            WHERE
                login = '%s'",
            PMF_Db::getTablePrefix(),
            $this->db->escape($login)
        );

        $check = $this->db->query($check);
        $error = $this->db->error();

        if (strlen($error) > 0) {
            $this->errors[] = PMF_User::ERROR_USER_NOT_FOUND.'error(): '.$error;

            return false;
        }

        $numRows = $this->db->numRows($check);
        if ($numRows < 1) {
            $this->errors[] = PMF_User::ERROR_USER_NOT_FOUND;

            return false;
        }

        // if login not unique, raise an error, but continue
        if ($numRows > 1) {
            $this->errors[] = PMF_User::ERROR_USER_LOGIN_NOT_UNIQUE;
        }

        // if multiple accounts are ok, just 1 valid required
        while ($user = $this->db->fetchArray($check)) {

            // Check password against old one
            if ($this->_config->get('security.forcePasswordUpdate')) {
                if ($this->checkEncryptedPassword($user['pass'], $password) &&
                    $this->encContainer->setSalt($user['login'])->encrypt($password) !== $user['pass']) {
                    return $this->changePassword($login, $password);
                }
            }

            if ($user['pass'] === $this->encContainer->setSalt($user['login'])->encrypt($password)) {
                return true;
                break;
            }
        }
        $this->errors[] = PMF_User::ERROR_USER_INCORRECT_PASSWORD;

        return false;
    }

    /**
     * Checks the number of entries of given login name.
     *
     * @param string $login        Loginname
     * @param array  $optionalData Optional data
     *
     * @return int
     */
    public function checkLogin($login, Array $optionalData = null)
    {
        $check = sprintf("
            SELECT
                login
            FROM
                %sfaquserlogin
            WHERE
                login = '%s'",
            PMF_Db::getTablePrefix(),
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
