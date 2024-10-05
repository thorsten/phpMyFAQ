<?php

/**
 * The Rating data class for the administration.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-03-29
 */

namespace phpMyFAQ\Administration;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Link;
use phpMyFAQ\Strings;

readonly class RatingData
{
    public function __construct(private Configuration $configuration)
    {
    }

    /**
     * Returns all ratings of FAQ records.
     *
     * @return array
     */
    public function getAll(): array
    {
        $ratings = [];
        $query = $this->buildQuery();
        $result = $this->configuration->getDb()->query($query);

        while ($row = $this->configuration->getDb()->fetchObject($result)) {
            $ratings[] = $this->mapRowToRating($row);
        }

        return $ratings;
    }

    private function buildQuery(): string
    {
        $prefix = Database::getTablePrefix();
        return match (Database::getType()) {
            'sqlsrv' => sprintf(
                '
                    SELECT
                        fd.id AS id,
                        fd.lang AS lang,
                        fcr.category_id AS category_id,
                        CAST(fd.thema as char(2000)) AS question,
                        (fv.vote / fv.usr) AS num,
                        fv.usr AS usr
                    FROM
                        %sfaqvoting fv,
                        %sfaqdata fd
                    LEFT JOIN
                        %sfaqcategoryrelations fcr
                    ON
                        fd.id = fcr.record_id
                    AND
                        fd.lang = fcr.record_lang
                    WHERE
                        fd.id = fv.artikel
                    GROUP BY
                        fd.id,
                        fd.lang,
                        fd.active,
                        fcr.category_id,
                        CAST(fd.thema as char(2000)),
                        fv.vote,
                        fv.usr
                    ORDER BY
                        fcr.category_id',
                $prefix, $prefix, $prefix
            ),
            default => sprintf(
                '
                    SELECT
                        fd.id AS id,
                        fd.lang AS lang,
                        fcr.category_id AS category_id,
                        fd.thema AS question,
                        (fv.vote / fv.usr) AS num,
                        fv.usr AS usr
                    FROM
                        %sfaqvoting fv,
                        %sfaqdata fd
                    LEFT JOIN
                        %sfaqcategoryrelations fcr
                    ON
                        fd.id = fcr.record_id
                    AND
                        fd.lang = fcr.record_lang
                    WHERE
                        fd.id = fv.artikel
                    GROUP BY
                        fd.id,
                        fd.lang,
                        fd.active,
                        fcr.category_id,
                        fd.thema,
                        fv.vote,
                        fv.usr
                    ORDER BY
                        fcr.category_id',
                $prefix,
                $prefix,
                $prefix
            ),
        };
    }

    private function mapRowToRating(object $row): array
    {
        var_dump($this->configuration->getDefaultUrl());

        $question = Strings::htmlspecialchars(trim((string) $row->question));
        $url = sprintf(
            '%sindex.php?action=faq&cat=%d&id=%d&artlang=%s',
            $this->configuration->getDefaultUrl(),
            $row->category_id,
            $row->id,
            $row->lang
        );

        $link = new Link($url, $this->configuration);
        $link->itemTitle = $question;

        return [
            'id' => $row->id,
            'lang' => $row->lang,
            'category_id' => $row->category_id,
            'question' => $question,
            'url' => $link->toString(),
            'number' => $row->num,
            'user' => $row->usr
        ];
    }
}
