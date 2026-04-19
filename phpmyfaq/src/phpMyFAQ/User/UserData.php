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
 * @copyright 2005-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2005-09-18
 */

declare(strict_types=1);

namespace phpMyFAQ\User;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use Symfony\Component\HttpFoundation\Request;

/**
 * UserData.
 *
 * @package   phpMyFAQ
 * @author    Lars Tiedemann <php@larstiedemann.de>
 * @copyright 2005-2026 phpMyFAQ Team
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
    public function __construct(
        private readonly Configuration $configuration,
    ) {
    }

    /**
     * Returns the field $field of the user data. If $field is an
     * array, an associative array will be returned.
     *
     * @param mixed $field Field(s)
     */
    public function get(mixed $field): mixed
    {
        $singleReturn = !is_array($field);
        $fields = $singleReturn ? $field : implode(', ', $field);

        $select = sprintf(
            'SELECT %s FROM %sfaquserdata WHERE user_id = %d',
            $fields,
            Database::getTablePrefix(),
            $this->userId,
        );

        try {
            $res = $this->configuration->getDb()->query($select);
        } catch (\Throwable) {
            if ($singleReturn && $field === 'keycloak_sub') {
                return '';
            }

            return false;
        }

        if ($res === false) {
            if ($singleReturn && $field === 'keycloak_sub') {
                return '';
            }

            return false;
        }

        if ($this->configuration->getDb()->numRows($res) !== 1) {
            return false;
        }

        $array = $this->configuration->getDb()->fetchArray($res);

        // Decode HTML entities in display_name for backward compatibility
        if (array_key_exists('display_name', $array) && is_string($array['display_name'])) {
            $array['display_name'] = html_entity_decode(
                $array['display_name'],
                ENT_QUOTES | ENT_HTML5 | ENT_SUBSTITUTE,
                encoding: 'UTF-8',
            );
        }

        if ($singleReturn && $field !== '*') {
            return match ($field) {
                'display_name', 'email', 'keycloak_sub', 'secret' => (string) ($array[$field] ?? ''),
                default => $array[$field] ?? null,
            };
        }

        return $array;
    }

    /**
     * Returns the first result of the given key.
     */
    public function fetch(string $key, string $value): ?string
    {
        $select = sprintf(
            "SELECT %s FROM %sfaquserdata WHERE %s = '%s'",
            $key,
            Database::getTablePrefix(),
            $key,
            $this->configuration->getDb()->escape($value),
        );

        $res = $this->configuration->getDb()->query($select);

        if (0 === $this->configuration->getDb()->numRows($res)) {
            return null;
        }

        return $this->configuration->getDb()->fetchObject($res)->$key;
    }

    /**
     * Returns the data of the given key.
     *
     * @return array<string, int>
     */
    public function fetchAll(string $key, string $value): array
    {
        $select = sprintf(
            "SELECT 
                user_id, last_modified, display_name, email, keycloak_sub, is_visible, twofactor_enabled, secret 
            FROM %sfaquserdata WHERE %s = '%s'",
            Database::getTablePrefix(),
            $key,
            $this->configuration->getDb()->escape($value),
        );

        try {
            $res = $this->configuration->getDb()->query($select);
        } catch (\Throwable) {
            $res = false;
        }

        if ($res === false) {
            $select = sprintf(
                "SELECT 
                    user_id, last_modified, display_name, email, is_visible, twofactor_enabled, secret 
                FROM %sfaquserdata WHERE %s = '%s'",
                Database::getTablePrefix(),
                $key,
                $this->configuration->getDb()->escape($value),
            );
            $res = $this->configuration->getDb()->query($select);
        }

        if ($this->configuration->getDb()->numRows($res) !== 1) {
            return ['user_id' => -1];
        }

        $this->data = $this->configuration->getDb()->fetchArray($res);
        $this->data['keycloak_sub'] ??= '';

        return $this->data;
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

        if (count($field) !== count($value)) {
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
        if ($userId <= 0 && $userId !== -1) {
            return false;
        }

        $this->userId = $userId;
        $select = sprintf('
            SELECT
                last_modified, 
                display_name, 
                email,
                keycloak_sub,
                is_visible,
                twofactor_enabled, 
                secret
            FROM
                %sfaquserdata
            WHERE
                user_id = %d', Database::getTablePrefix(), $this->userId);

        try {
            $res = $this->configuration->getDb()->query($select);
        } catch (\Throwable) {
            $res = false;
        }

        if ($res === false) {
            $select = sprintf('
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
                user_id = %d', Database::getTablePrefix(), $this->userId);
            $res = $this->configuration->getDb()->query($select);
        }

        if ($this->configuration->getDb()->numRows($res) !== 1) {
            return false;
        }

        $this->data = $this->configuration->getDb()->fetchArray($res);
        $this->data['keycloak_sub'] ??= '';

        return true;
    }

    /**
     * Saves the current user-data into the database.
     * Returns true on success, otherwise false.
     */
    public function save(): bool
    {
        $keycloakSubRaw = $this->data['keycloak_sub'] ?? null;
        $keycloakSubValue = is_string($keycloakSubRaw) && trim($keycloakSubRaw) !== ''
            ? "'" . $this->configuration->getDb()->escape($keycloakSubRaw) . "'"
            : 'NULL';

        $update = sprintf(
            "
            UPDATE
                %sfaquserdata
            SET
                last_modified = '%s',
                display_name = '%s',
                email = '%s',
                keycloak_sub = %s,
                is_visible = %d,
                twofactor_enabled = %d,
                secret = '%s'
            WHERE
                user_id = %d",
            Database::getTablePrefix(),
            date(format: 'YmdHis', timestamp: Request::createFromGlobals()->server->get('REQUEST_TIME')),
            $this->configuration->getDb()->escape((string) ($this->data['display_name'] ?? '')),
            $this->configuration->getDb()->escape((string) ($this->data['email'] ?? '')),
            $keycloakSubValue,
            $this->data['is_visible'] ?? 0,
            $this->data['twofactor_enabled'] ?? 0,
            $this->data['secret'] ?? '',
            $this->userId,
        );

        try {
            $res = $this->configuration->getDb()->query($update);
        } catch (\Throwable) {
            $res = false;
        }

        if ($res === false) {
            if (array_key_exists('keycloak_sub', $this->data)) {
                return false;
            }

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
                date(format: 'YmdHis', timestamp: Request::createFromGlobals()->server->get('REQUEST_TIME')),
                $this->configuration->getDb()->escape((string) ($this->data['display_name'] ?? '')),
                $this->configuration->getDb()->escape((string) ($this->data['email'] ?? '')),
                $this->data['is_visible'] ?? 0,
                $this->data['twofactor_enabled'] ?? 0,
                $this->data['secret'] ?? '',
                $this->userId,
            );

            $res = $this->configuration->getDb()->query($update);
        }

        return (bool) $res;
    }

    /**
     * Adds a new user entry for user-data in the database.
     * Returns true on success, otherwise false.
     *
     * @param int $userId User ID
     */
    public function add(int $userId): bool
    {
        if ($userId <= 0 && $userId !== -1) {
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
            date(format: 'YmdHis', timestamp: Request::createFromGlobals()->server->get('REQUEST_TIME')),
        );

        $res = $this->configuration->getDb()->query($insert);
        return (bool) $res;
    }

    /**
     * Deletes the user-data entry for the given user-ID $userId.
     * Returns true on success, otherwise false.
     *
     * @param int $userId User ID
     */
    public function delete(int $userId): bool
    {
        if ($userId <= 0 && $userId !== -1) {
            return false;
        }

        $this->userId = $userId;
        $delete = sprintf('DELETE FROM %sfaquserdata WHERE user_id = %d', Database::getTablePrefix(), $this->userId);

        $res = $this->configuration->getDb()->query($delete);
        if (!$res) {
            return false;
        }

        $this->data = [];

        return true;
    }

    /**
     * Checks if an email address already exists in the user data table.
     * Returns true if the email exists, false otherwise.
     *
     * @param string $email Email address to check
     */
    public function emailExists(string $email): bool
    {
        if ($email === '' || $email === '0') {
            return false;
        }

        $select = sprintf(
            "SELECT user_id FROM %sfaquserdata WHERE email = '%s'",
            Database::getTablePrefix(),
            $this->configuration->getDb()->escape($email),
        );

        $res = $this->configuration->getDb()->query($select);
        return $this->configuration->getDb()->numRows($res) > 0;
    }
}
