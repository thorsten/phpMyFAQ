<?php

/**
 * FAQ permissions class for phpMyFAQ.
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2020-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2020-11-04
 */

namespace phpMyFAQ\Faq;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Filter;

/**
 * Class FaqPermission
 *
 * @package phpMyFAQ\Faq
 */
class FaqPermission
{
    /** @var string */
    final public const USER = 'user';

    /** @var string */
    final public const GROUP = 'group';

    /**
     * FaqPermission constructor.
     */
    public function __construct(private readonly Configuration $config)
    {
    }

    /**
     * Adds the record permissions for users and groups.
     *
     * @param string $mode 'group' or 'user'
     * @param int    $faqId ID of the current record
     * @param int[]  $ids Array of group or user IDs
     */
    public function add(string $mode, int $faqId, array $ids): bool
    {
        if (self::USER !== $mode && self::GROUP !== $mode) {
            return false;
        }

        foreach ($ids as $id) {
            $query = sprintf(
                'INSERT INTO %sfaqdata_%s (record_id, %s_id) VALUES (%d, %d)',
                Database::getTablePrefix(),
                $mode,
                $mode,
                $faqId,
                $id
            );

            $this->config->getDb()->query($query);
        }

        return true;
    }

    /**
     * Deletes the record permissions for users and groups.
     *
     * @param string $mode 'group' or 'user'
     * @param int    $faqId ID of the current record
     */
    public function delete(string $mode, int $faqId): bool
    {
        if (self::USER !== $mode && self::GROUP !== $mode) {
            return false;
        }

        $query = sprintf(
            'DELETE FROM %sfaqdata_%s WHERE record_id = %d',
            Database::getTablePrefix(),
            $mode,
            $faqId
        );
        $this->config->getDb()->query($query);

        return true;
    }

    /**
     * Returns the record permissions for users and groups.
     *
     * @param string $mode 'group' or 'user'
     */
    public function get(string $mode, int $faqId): array
    {
        $permissions = [];

        if (self::USER !== $mode && self::GROUP !== $mode) {
            return $permissions;
        }

        if (0 === $faqId) {
            return [-1];
        }

        $query = sprintf(
            'SELECT %s_id AS permission FROM %sfaqdata_%s WHERE record_id = %d',
            $mode,
            Database::getTablePrefix(),
            $mode,
            $faqId
        );

        $result = $this->config->getDb()->query($query);

        if ($this->config->getDb()->numRows($result) > 0) {
            while (($row = $this->config->getDb()->fetchObject($result))) {
                $permissions[] = (int)$row->permission;
            }
        }

        return $permissions;
    }

    public function createPermissionArray(): array
    {
        $permissions = [];

        if ('all' === Filter::filterInput(INPUT_POST, 'userpermission', FILTER_SANITIZE_SPECIAL_CHARS)) {
            $permissions += [
                'restricted_user' => [-1],
            ];
        } else {
            $permissions += [
                'restricted_user' => [
                    Filter::filterInput(INPUT_POST, 'restricted_users', FILTER_VALIDATE_INT),
                ],
            ];
        }

        if ('all' === Filter::filterInput(INPUT_POST, 'grouppermission', FILTER_SANITIZE_SPECIAL_CHARS)) {
            $permissions += [
                'restricted_groups' => [-1],
            ];
        } else {
            $permissions += Filter::filterInputArray(
                INPUT_POST,
                [
                    'restricted_groups' => [
                        'filter' => FILTER_VALIDATE_INT,
                        'flags' => FILTER_REQUIRE_ARRAY,
                    ],
                ]
            );
        }

        return $permissions;
    }
}
