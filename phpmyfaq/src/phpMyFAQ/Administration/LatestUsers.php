<?php

/**
 * Service to fetch the latest users for admin dashboard widgets.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2025-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2025-11-01
 */

declare(strict_types=1);

namespace phpMyFAQ\Administration;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Date;

final readonly class LatestUsers
{
    public function __construct(
        private Configuration $configuration,
    ) {
    }

    /**
     * Returns latest users for the admin dashboard.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getList(int $limit = 5): array
    {
        $users = [];
        $databaseDriver = $this->configuration->getDb();

        $query = sprintf(
            'SELECT fu.user_id, fu.login, fu.member_since, fud.display_name FROM %sfaquser fu LEFT JOIN %sfaquserdata fud '
            . 'ON (fud.user_id = fu.user_id) WHERE fu.user_id <> -1 ORDER BY fu.member_since DESC',
            Database::getTablePrefix(),
            Database::getTablePrefix(),
        );

        $result = $databaseDriver->query($query, 0, $limit);
        if ($result) {
            while ($row = $databaseDriver->fetchArray($result)) {
                $users[] = [
                    'id' => (int) ($row['user_id'] ?? 0),
                    'login' => (string) ($row['login'] ?? ''),
                    'display_name' => (string) ($row['display_name'] ?? ''),
                    'member_since_iso' => empty($row['member_since']) ? '' : Date::createIsoDate($row['member_since']),
                ];
            }
        }

        return $users;
    }
}
