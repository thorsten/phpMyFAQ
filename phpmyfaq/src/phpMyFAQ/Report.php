<?php

/**
 * The reporting class for simple report generation.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Gustavo Solt <gustavo.solt@mayflower.de>
 * @copyright 2011-2022 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2011-02-04
 */

namespace phpMyFAQ;

/**
 * Class Report
 *
 * @package phpMyFAQ
 */
class Report
{
    /**
     * @var Configuration
     */
    private $config;

    /**
     * Constructor.
     *
     * @param Configuration $config
     */
    public function __construct(Configuration $config)
    {
        $this->config = $config;
    }

    /**
     * Generates a huge array for the report.
     *
     * @return array<int, array<mixed>>
     */
    public function getReportingData(): array
    {
        $report = [];

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
            Database::getTablePrefix()
        );

        $result = $this->config->getDb()->query($query);

        $lastId = 0;
        while ($row = $this->config->getDb()->fetchObject($result)) {
            if ($row->id == $lastId) {
                $report[$row->id]['faq_translations'] += 1;
            } else {
                $report[$row->id] = [
                    'faq_id' => $row->id,
                    'faq_language' => $row->lang,
                    'category_id' => $row->category_id,
                    'category_parent' => $row->parent_id,
                    'category_name' => $row->category_name,
                    'faq_translations' => 0,
                    'faq_sticky' => $row->sticky,
                    'faq_question' => $row->question,
                    'faq_org_author' => $row->original_author,
                    'faq_updated' => Date::createIsoDate($row->updated),
                    'faq_visits' => $row->visits,
                    'faq_last_author' => $row->last_author,
                ];
            }
            $lastId = $row->id;
        }

        return $report;
    }

    /**
     * Convert string to the correct encoding and removes possible
     * bad strings to avoid formula injection attacks.
     *
     * @param  string $outputString String to encode.
     * @return string Encoded string.
     */
    public function convertEncoding(string $outputString = ''): string
    {
        $outputString = html_entity_decode($outputString, ENT_QUOTES, 'utf-8');
        $outputString = str_replace(',', ' ', $outputString);

        if (extension_loaded('mbstring')) {
            $detected = mb_detect_encoding($outputString);

            if ($detected !== 'ASCII') {
                $outputString = mb_convert_encoding($outputString, 'UTF-16', $detected);
            }
        }

        $toBeRemoved = ['=', '+', '-', 'HYPERLINK'];
        return str_replace($toBeRemoved, '', $outputString);
    }
}
