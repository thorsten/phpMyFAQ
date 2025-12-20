<?php

/**
 * The reporting class for simple report generation.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Gustavo Solt <gustavo.solt@mayflower.de>
 * @copyright 2011-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2011-02-04
 */

declare(strict_types=1);

namespace phpMyFAQ\Administration;

use phpMyFAQ\Configuration;
use phpMyFAQ\Date;

/**
 * Class Report
 *
 * @package phpMyFAQ
 */
readonly class Report
{
    private ReportRepository $repository;

    /**
     * Constructor.
     */
    public function __construct(
        private Configuration $configuration,
    ) {
        $this->repository = new ReportRepository($configuration);
    }

    /**
     * Generates a huge array for the report.
     *
     * @return array<int, array>
     */
    public function getReportingData(): array
    {
        $report = [];
        $rows = $this->repository->fetchAllReportData();

        $lastId = 0;
        foreach ($rows as $row) {
            if ($row->id === $lastId) {
                ++$report[$row->id]['faq_translations'];
            }

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
        $outputString = html_entity_decode($outputString, ENT_QUOTES, encoding: 'utf-8');
        $outputString = str_replace(search: ',', replace: ' ', subject: $outputString);

        if (extension_loaded(extension: 'mbstring')) {
            $detected = mb_detect_encoding($outputString);

            if ($detected !== 'ASCII') {
                $outputString = mb_convert_encoding($outputString, to_encoding: 'UTF-16', from_encoding: $detected);
            }
        }

        $toBeRemoved = ['=', '+', '-', 'HYPERLINK'];
        return str_replace(search: $toBeRemoved, replace: '', subject: $outputString);
    }

    /**
     * Sanitizes input to avoid CSV injection.
     * @param int|string $value
     * @return string|int
     */
    public static function sanitize(int|string $value): string|int
    {
        if (preg_match(pattern: '/[=\+\-\@\|]/', subject: (string) $value)) {
            return '"' . str_replace(search: '"', replace: '""', subject: $value) . '"';
        }

        return $value;
    }
}
