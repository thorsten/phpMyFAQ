<?php

/**
 * Report Repository.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2025-11-30
 */

declare(strict_types=1);

namespace phpMyFAQ\Administration;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;

readonly class ReportRepository
{
    public function __construct(
        private Configuration $configuration,
    ) {
    }

    /**
     * Fetches all FAQ data with related information for reporting.
     *
     * @return array<int, object>
     */
    public function fetchAllReportData(): array
    {
        $query = sprintf(
            '
            SELECT
                fd.id AS id,
                fd.lang AS lang,
                fcr.category_id AS category_id,
                c.name as category_name,
                c.parent_id as parent_id,
                fd.sticky AS sticky,
                fd.thema AS question,
                fd.author AS original_author,
                fd.updated AS updated,
                fv.visits AS visits,
                u.display_name AS last_author
            FROM
                %sfaqdata fd
            LEFT JOIN
                %sfaqcategoryrelations fcr
            ON
                (fd.id = fcr.record_id AND fd.lang = fcr.record_lang)
            LEFT JOIN
                %sfaqvisits fv
            ON
                (fd.id = fv.id AND fd.lang = fv.lang)
            LEFT JOIN
                %sfaqchanges as fc
            ON
                (fd.id = fc.id AND fd.lang = fc.lang)
            LEFT JOIN
                %sfaquserdata as u
            ON
                (u.user_id = fc.usr)
            LEFT JOIN
                %sfaqcategories as c
            ON
                (c.id = fcr.category_id AND c.lang = fcr.record_lang)
            ORDER BY
                fd.id
            ASC',
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            Database::getTablePrefix(),
        );

        $result = $this->configuration->getDb()->query($query);

        $rows = [];
        while ($row = $this->configuration->getDb()->fetchObject($result)) {
            $rows[] = $row;
        }

        return $rows;
    }
}
