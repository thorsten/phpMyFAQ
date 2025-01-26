<?php

/**
 * FAQ permissions class for phpMyFAQ.
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2020-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2020-11-04
 */

namespace phpMyFAQ\Faq;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Filter;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class FaqPermission
 *
 * @package phpMyFAQ\Faq
 */
class Permission
{
    /** @var string */
    final public const USER = 'user';

    /** @var string */
    final public const GROUP = 'group';

    /**
     * FaqPermission constructor.
     */
    public function __construct(private readonly Configuration $configuration)
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

            $this->configuration->getDb()->query($query);
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

        return (bool) $this->configuration->getDb()->query($query);
    }

    /**
     * Returns the record permissions for users and groups.
     *
     * @param string $mode 'group' or 'user'
     * @return array<int>
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

        $result = $this->configuration->getDb()->query($query);

        if ($this->configuration->getDb()->numRows($result) > 0) {
            while (($row = $this->configuration->getDb()->fetchObject($result))) {
                $permissions[] = (int)$row->permission;
            }
        }

        return $permissions;
    }

    /**
     * Creates the permission array.
     *
     * @return array<string, array<int>>
     */
    public function createPermissionArray(): array
    {
        $permissions = [];

        $data = json_decode(Request::createFromGlobals()->getContent())->data;

        if ('all' === Filter::filterVar($data->userpermission, FILTER_SANITIZE_SPECIAL_CHARS)) {
            $permissions += [
                'restricted_user' => [-1],
            ];
        } else {
            if (is_string($data->restricted_users)) {
                $permissions += [
                    'restricted_user' => [Filter::filterVar($data->restricted_users, FILTER_VALIDATE_INT)],
                ];
            } else {
                $permissions += [
                    'restricted_user' => Filter::filterArray($data->restricted_users, FILTER_VALIDATE_INT),
                ];
            }
        }

        if ('all' === Filter::filterVar($data->grouppermission, FILTER_SANITIZE_SPECIAL_CHARS)) {
            $permissions += [
                'restricted_groups' => [-1],
            ];
        } else {
            if (is_string($data->restricted_groups)) {
                $permissions += [
                    'restricted_groups' => [Filter::filterVar($data->restricted_groups, FILTER_VALIDATE_INT)]
                ];
            } else {
                $permissions += [
                    'restricted_groups' => Filter::filterArray($data->restricted_groups, FILTER_VALIDATE_INT)
                ];
            }
        }

        return $permissions;
    }
}
