<?php

/**
 * The Admin FAQ class
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2024-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-04-02
 */

declare(strict_types=1);

namespace phpMyFAQ\Administration;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use stdClass;

class Faq
{
    private ?string $language = null;

    public function __construct(
        private readonly Configuration $configuration,
    ) {}

    /**
     * Get all FAQs by category
     */
    public function getAllFaqsByCategory(int $categoryId, bool $onlyInactive = false, bool $onlyNew = false): array
    {
        $faqData = [];

        $query = sprintf(
            "
            SELECT
                fd.id AS id,
                fd.lang AS lang,
                fd.solution_id AS solution_id,
                fd.active AS active,
                fd.sticky AS sticky,
                fd.thema AS question,
                fd.updated AS updated,
                fcr.category_id AS category_id,
                fv.visits AS visits,
                fd.created AS created
            FROM
                %sfaqdata AS fd
            LEFT JOIN
                %sfaqcategoryrelations AS fcr
            ON
                fd.id = fcr.record_id
            AND
                fd.lang = fcr.record_lang
            LEFT JOIN
                %sfaqvisits AS fv
            ON
                fd.id = fv.id
            AND
                fv.lang = fd.lang
            LEFT JOIN
                %sfaqdata_group AS fdg
            ON
                fd.id = fdg.record_id
            LEFT JOIN
                %sfaqdata_user AS fdu
            ON
                fd.id = fdu.record_id
            WHERE
                fcr.category_id = %d
            AND
                fd.lang = '%s'
            %s
            %s
            ORDER BY
                fd.id ASC",
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            $categoryId,
            $this->configuration->getDb()->escape($this->getLanguage()),
            $onlyInactive ? "AND fd.active = 'no'" : '',
            $onlyNew
                ? sprintf("AND fd.created > '%s'", date(
                    format: 'Y-m-d H:i:s',
                    timestamp: strtotime(datetime: '-1 month'),
                ))
                : '',
        );

        $result = $this->configuration->getDb()->query($query);
        $num = $this->configuration->getDb()->numRows($result);

        if ($num > 0) {
            while ($row = $this->configuration->getDb()->fetchObject($result)) {
                $visits = empty($row->visits) ? 0 : $row->visits;

                $faqData[] = [
                    'id' => (int) $row->id,
                    'language' => $row->lang,
                    'solution_id' => (int) $row->solution_id,
                    'active' => $row->active,
                    'sticky' => $row->sticky ? 'yes' : 'no',
                    'category_id' => (int) $row->category_id,
                    'question' => $row->question,
                    'updated' => $row->updated,
                    'visits' => (int) $visits,
                    'created' => $row->created,
                ];
            }
        }

        return $faqData;
    }

    /**
     * Set or unset a faq item flag.
     *
     * @param int    $faqId       FAQ id
     * @param string $faqLanguage Language code which is valid with Language::isASupportedLanguage
     * @param bool   $flag        FAQ is set to sticky or not
     * @param string $type        Type of the flag to set, use the column name
     */
    public function updateRecordFlag(int $faqId, string $faqLanguage, bool $flag, string $type): bool
    {
        $flag = match ($type) {
            'sticky' => $flag ? 1 : 0,
            'active' => $flag ? "'yes'" : "'no'",
            default => null,
        };

        if (null !== $flag) {
            $update = sprintf(
                "
                UPDATE 
                    %sfaqdata 
                SET 
                    %s = %s 
                WHERE 
                    id = %d 
                AND 
                    lang = '%s'",
                Database::getTablePrefix(),
                $type,
                $flag,
                $faqId,
                $this->configuration->getDb()->escape($faqLanguage),
            );

            return (bool) $this->configuration->getDb()->query($update);
        }

        return false;
    }

    /**
     * Returns the inactive records with admin URL to edit the FAQ and title.
     */
    public function getInactiveFaqsData(): array
    {
        $query = sprintf(
            "
            SELECT
                fd.id AS id,
                fd.lang AS lang,
                fd.thema AS thema
            FROM
                %sfaqdata fd
            WHERE
                fd.lang = '%s'
            AND 
                fd.active = 'no'
            GROUP BY
                fd.id, fd.lang, fd.thema
            ORDER BY
                fd.id DESC",
            Database::getTablePrefix(),
            $this->configuration->getLanguage()->getLanguage(),
        );

        $result = $this->configuration->getDb()->query($query);
        $inactive = [];
        $data = [];

        $oldId = 0;
        while ($row = $this->configuration->getDb()->fetchObject($result)) {
            if ($oldId != $row->id) {
                $data['question'] = $row->thema;
                $data['url'] = sprintf(
                    '%sadmin/faq/edit/%d/%s',
                    $this->configuration->getDefaultUrl(),
                    $row->id,
                    $row->lang,
                );
                $inactive[] = $data;
            }

            $oldId = $row->id;
        }

        return $inactive;
    }

    /**
     * Returns the orphaned records with admin URL to edit the FAQ and title.
     *
     * @return stdClass[]
     */
    public function getOrphanedFaqs(): array
    {
        $query = sprintf(
            "
                SELECT
                    fd.id AS id,
                    fd.lang AS lang,
                    fd.thema AS question
                FROM
                    %sfaqdata fd
                WHERE
                    fd.active = 'yes'
                AND
                    fd.id NOT IN (
                        SELECT
                            record_id
                        FROM
                            %sfaqcategoryrelations
                        WHERE
                            record_lang = fd.lang
                    )
                GROUP BY
                    fd.id, fd.lang, fd.thema
                ORDER BY
                    fd.lang ASC, fd.id DESC",
            Database::getTablePrefix(),
            Database::getTablePrefix(),
        );

        $result = $this->configuration->getDb()->query($query);
        $orphaned = [];
        $currentBackendLang = $this->configuration->getLanguage()->getLanguage();
        $seen = [];
        while ($row = $this->configuration->getDb()->fetchObject($result)) {
            $key = $row->id . '-' . $row->lang;

            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $data = new stdClass();
                $data->faqId = $row->id;
                $data->language = $row->lang;
                $data->question = $row->question;

                if (class_exists('\Locale')) {
                    $displayName = \Locale::getDisplayLanguage($row->lang, $currentBackendLang);
                    $data->languageName = $displayName !== '' ? $displayName : $row->lang;
                } else {
                    $data->languageName = $row->lang;
                }

                $data->url = sprintf(
                    '%sadmin/faq/edit/%d/%s',
                    $this->configuration->getDefaultUrl(),
                    $row->id,
                    $row->lang,
                );
                $orphaned[] = $data;
            }
        }

        return $orphaned;
    }

    /**
     * Returns true if saving the order of the sticky faqs was successfully.
     *
     * @param array $faqIds Order of record id's
     */
    public function setStickyFaqOrder(array $faqIds): bool
    {
        $count = 1;
        $counter = count($faqIds);
        for ($i = 0; $i < $counter; ++$i) {
            $query = sprintf(
                'UPDATE %sfaqdata SET sticky_order=%d WHERE id=%d',
                Database::getTablePrefix(),
                $count,
                $faqIds[$i],
            );
            $this->configuration->getDb()->query($query);
            ++$count;
        }

        return true;
    }

    public function setLanguage(string $language): Faq
    {
        $this->language = $language;
        return $this;
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }
}
