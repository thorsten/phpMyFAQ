<?php

/**
 * The userdata class provides methods to manage user information.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Lars Tiedemann <php@larstiedemann.de>
 * @copyright 2005-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2005-09-18
 */

namespace phpMyFAQ\User;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;

/**
 * UserData.
 *
 * @package   phpMyFAQ
 * @author    Lars Tiedemann <php@larstiedemann.de>
 * @copyright 2005-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2005-09-18
 */
class UserData
{
    /**
     * associative array containing user data.
     *
     * @var string[]
     */
    private array $data = [];

    /**
     * User-ID.
     */
    private int $userId = 0;

    /**
     * Constructor.
     */
    public function __construct(private readonly Configuration $config)
    {
    }

    /**
     * Returns the field $field of the user data. If $field is an
     * array, an associative array will be returned.
     *
     * @param mixed $field Field(s)
     */
    public function get(mixed $field): mixed
    {
        $singleReturn = false;
        if (!is_array($field)) {
            $singleReturn = true;
            $fields = $field;
        } else {
            $fields = implode(', ', $field);
        }

        $select = sprintf(
            '
            SELECT
                %s
            FROM
                %sfaquserdata
            WHERE
                user_id = %d',
            $fields,
            Database::getTablePrefix(),
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
     * Returns the first result of the given key.
     */
    public function fetch(string $key, string $value): ?string
    {
        $select = sprintf(
            "
            SELECT
                %s
            FROM
                %sfaquserdata
            WHERE
                %s = '%s'",
            $key,
            Database::getTablePrefix(),
            $key,
            $this->config->getDb()->escape($value)
        );

        $res = $this->config->getDb()->query($select);

        if (0 === $this->config->getDb()->numRows($res)) {
            return null;
        } else {
            return $this->config->getDb()->fetchObject($res)->$key;
        }
    }

    /**
     * Returns the data of the given key.
     *
     * @return array<string, int>
     */
    public function fetchAll(string $key, string $value): array
    {
        $select = sprintf(
            "SELECT user_id,
            last_modified,
            display_name, email,
            is_visible,
            twofactor_enabled,
            secret FROM %sfaquserdata WHERE %s = '%s'",
            Database::getTablePrefix(),
            $key,
            $this->config->getDb()->escape($value)
        );

        $res = $this->config->getDb()->query($select);
        if ($this->config->getDb()->numRows($res) != 1) {
            return ['user_id' => -1];
        }
        return $this->data = $this->config->getDb()->fetchArray($res);
    }

    /**
     * Sets the user data given by $field and $value. If $field
     * and $value are arrays, all fields with the corresponding
     * values are updated. Changes are being stored in the database.
     *
     * @param mixed $field Field(s)
     * @param mixed $value Value(s)
     */
    public function set(mixed $field, mixed $value = null): bool
    {
        // check input
        if (!is_array($field)) {
            $field = [$field];
        }
        if (!is_array($value)) {
            $value = [$value];
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
     */
    public function load(int $userId): bool
    {
        if (($userId <= 0) && ($userId != -1)) {
            return false;
        }

        $this->userId = $userId;
        $select = sprintf(
            '
            SELECT
                last_modified, 
                display_name, 
                email,
                is_visible,
                twofactor_enabled, 
                secret
            FROM
                %sfaquserdata
            WHERE
                user_id = %d',
            Database::getTablePrefix(),
            $this->userId
        );

        $res = $this->config->getDb()->query($select);
        if ($this->config->getDb()->numRows($res) !== 1) {
            return false;
        }
        $this->data = $this->config->getDb()->fetchArray($res);

        return true;
    }

    /**
     * Saves the current user-data into the database.
     * Returns true on success, otherwise false.
     */
    public function save(): bool
    {
        $update = sprintf(
            "
            UPDATE
                %sfaquserdata
            SET
                last_modified = '%s',
                display_name = '%s',
                email = '%s',
                is_visible = %d,
                twofactor_enabled = %d,
                secret = '%s'
            WHERE
                user_id = %d",
            Database::getTablePrefix(),
            date('YmdHis', $_SERVER['REQUEST_TIME']),
            $this->config->getDb()->escape($this->data['display_name']),
            $this->config->getDb()->escape($this->data['email']),
            $this->data['is_visible'],
            $this->data['twofactor_enabled'],
            $this->data['secret'],
            $this->userId
        );

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
     */
    public function add(int $userId): bool
    {
        if (($userId <= 0) && ($userId != -1)) {
            return false;
        }

        $this->userId = $userId;
        $insert = sprintf(
            "
            INSERT INTO
                %sfaquserdata
            (user_id, last_modified, is_visible, twofactor_enabled, secret)
                VALUES
            (%d, '%s', 1, 0, '')",
            Database::getTablePrefix(),
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
     */
    public function delete(int $userId): bool
    {
        if (($userId <= 0) && ($userId != -1)) {
            return false;
        }

        $this->userId = $userId;
        $delete = sprintf(
            '
            DELETE FROM
                %sfaquserdata
            WHERE
                user_id = %d',
            Database::getTablePrefix(),
            $this->userId
        );

        $res = $this->config->getDb()->query($delete);
        if (!$res) {
            return false;
        }
        $this->data = [];

        return true;
    }
}
