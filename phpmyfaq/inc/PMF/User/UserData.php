<?php

/**
 * The userdata class provides methods to manage user information.
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
 * @copyright 2005-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      https://www.phpmyfaq.de
 * @since     2005-09-18
 */
if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_User_UserData.
 *
 * @category  phpMyFAQ
 *
 * @author    Lars Tiedemann <php@larstiedemann.de>
 * @copyright 2005-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      https://www.phpmyfaq.de
 * @since     2005-09-18
 */
class PMF_User_UserData
{
    /**
     * @var PMF_Configuration
     */
    private $config = null;

    /**
     * associative array containing user data.
     *
     * @var array
     */
    private $data = [];

    /**
     * User-ID.
     *
     * @var int
     */
    private $userId = 0;

    /**
     * Constructor.
     *
     * @param PMF_Configuration $config
     *
     * @return PMF_User_UserData
     */
    public function __construct(PMF_Configuration $config)
    {
        $this->config = $config;
    }

    /**
     * Returns the field $field of the user data. If $field is an
     * array, an associative array will be returned.
     *
     * @param mixed $field Field(s)
     *
     * @return mixed
     */
    public function get($field)
    {
        $singleReturn = false;
        if (!is_array($field)) {
            $singleReturn = true;
            $fields = $field;
        } else {
            $fields = implode(', ', $field);
        }

        $select = sprintf('
            SELECT
                %s
            FROM
                %sfaquserdata
            WHERE
                user_id = %d',
            $fields,
            PMF_Db::getTablePrefix(),
            $this->userId
        );

        $res = $this->config->getDb()->query($select);
        if ($this->config->getDb()->numRows($res) != 1) {
            return false;
        }
        $arr = $this->config->getDb()->fetchArray($res);
        if ($singleReturn and $field != '*') {
            return $arr[$field];
        }

        return $arr;
    }

    /**
     * Returns the first result of the given data.
     *
     * @param string $key
     * @param string $value
     *
     * @return string
     */
    public function fetch($key, $value)
    {
        $select = sprintf("
            SELECT
                %s
            FROM
                %sfaquserdata
            WHERE
                %s = '%s'",
            $key,
            PMF_Db::getTablePrefix(),
            $key,
            $this->config->getDb()->escape($value)
        );

        $res = $this->config->getDb()->query($select);

        if (0 === $this->config->getDb()->numRows($res)) {
            return false;
        } else {
            return $this->config->getDb()->fetchObject($res)->$key;
        }
    }

    /**
     * Sets the user data given by $field and $value. If $field
     * and $value are arrays, all fields with the corresponding
     * values are updated. Changes are being stored in the database.
     *
     * @param mixed $field Field(s)
     * @param mixed $value Value(s)
     *
     * @return bool
     */
    public function set($field, $value = null)
    {
        // check input
        if (!is_array($field)) {
            $field = array($field);
        }
        if (!is_array($value)) {
            $value = array($value);
        }
        if (count($field) != count($value)) {
            return false;
        }
        // update data
        $num = count($field);
        for ($i = 0; $i < $num; ++$i) {
            $this->data[$field[$i]] = $value[$i];
        }

        return $this->save();
    }

    /**
     * Loads the user-data from the database and returns an
     * associative array with the fields and values.
     *
     * @param int $userId User ID
     *
     * @return bool
     */
    public function load($userId)
    {
        $userId = (int) $userId;
        if (($userId <= 0) && ($userId != -1)) {
            return false;
        }

        $this->userId = $userId;
        $select = sprintf('
            SELECT
                last_modified, 
                display_name, 
                email
            FROM
                %sfaquserdata
            WHERE
                user_id = %d',
            PMF_Db::getTablePrefix(),
            $this->userId);

        $res = $this->config->getDb()->query($select);
        if ($this->config->getDb()->numRows($res) != 1) {
            return false;
        }
        $this->data = $this->config->getDb()->fetchArray($res);

        return true;
    }

    /**
     * Saves the current user-data into the database.
     * Returns true on success, otherwise false.
     *
     * @return bool
     */
    public function save()
    {
        $update = sprintf("
            UPDATE
                %sfaquserdata
            SET
                last_modified = '%s',
                display_name  = '%s',
                email         = '%s'
            WHERE
                user_id = %d",
            PMF_Db::getTablePrefix(),
            date('YmdHis', $_SERVER['REQUEST_TIME']),
            $this->config->getDb()->escape($this->data['display_name']),
            $this->config->getDb()->escape($this->data['email']),
            $this->userId);

        $res = $this->config->getDb()->query($update);
        if (!$res) {
            return false;
        }

        return true;
    }

    /**
     * Adds a new user entry for user-data in the database.
     * Returns true on success, otherwise false.
     *
     * @param int $userId User ID
     *
     * @return bool
     */
    public function add($userId)
    {
        $userId = (int) $userId;
        if (($userId <= 0) && ($userId != -1)) {
            return false;
        }

        $this->userId = $userId;
        $insert = sprintf("
            INSERT INTO
                %sfaquserdata
            (user_id, last_modified)
                VALUES
            (%d, '%s')",
            PMF_Db::getTablePrefix(),
            $this->userId,
            date('YmdHis', $_SERVER['REQUEST_TIME'])
        );

        $res = $this->config->getDb()->query($insert);
        if (!$res) {
            return false;
        }

        return true;
    }

    /**
     * Deletes the user-data entry for the given user-ID $userId.
     * Returns true on success, otherwise false.
     *
     * @param int $userId User ID
     *
     * @return bool
     */
    public function delete($userId)
    {
        $userId = (int) $userId;
        if (($userId <= 0) && ($userId != -1)) {
            return false;
        }

        $this->userId = $userId;
        $delete = sprintf('
            DELETE FROM
                %sfaquserdata
            WHERE
                user_id = %d',
            PMF_Db::getTablePrefix(),
            $this->userId);

        $res = $this->config->getDb()->query($delete);
        if (!$res) {
            return false;
        }
        $this->data = [];

        return true;
    }
}
