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
    ) {
    }

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
            while (true) {
                $row = $this->configuration->getDb()->fetchObject($result);
                if ($row === false || $row === null || $row === []) {
                    break;
                }

                $visits = (int) ($row->visits ?? 0);

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
        $language = $this->configuration->getDb()->escape($this->configuration->getLanguage()->getLanguage());
        $query = sprintf("
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
                fd.id DESC", Database::getTablePrefix(), $language);

        $result = $this->configuration->getDb()->query($query);
        $inactive = [];
        $data = [];

        $oldId = 0;
        while (true) {
            $row = $this->configuration->getDb()->fetchObject($result);
            if ($row === false || $row === null || $row === []) {
                break;
            }

            if ($oldId !== $row->id) {
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
        $language = $this->configuration->getDb()->escape($this->configuration->getLanguage()->getLanguage());
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
                    fd.id DESC",
            Database::getTablePrefix(),
            $language,
            Database::getTablePrefix(),
        );

        $result = $this->configuration->getDb()->query($query);
        $orphaned = [];
        $seen = [];
        while (true) {
            $row = $this->configuration->getDb()->fetchObject($result);
            if ($row === false || $row === null || $row === []) {
                break;
            }

            $key = $row->id . '-' . $row->lang;

            if (($seen[$key] ?? false) === false) {
                $seen[$key] = true;
                $data = new stdClass();
                $data->faqId = $row->id;
                $data->language = $row->lang;
                $data->question = $row->question;
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
    public function setStickyFaqOrder(array $faqIds, int $currentUserId = -1, array $currentGroups = [-1]): bool
    {
        $faqIds = $this->sanitizeFaqIds($faqIds);

        foreach ($faqIds as $faqId) {
            if (!$this->userCanEditFaq($faqId, $currentUserId, $currentGroups)) {
                return false;
            }
        }

        $normalizedFaqIds = array_map(static fn($faqId): int => (int) $faqId, $faqIds);
        $count = 1;
        $counter = count($normalizedFaqIds);
        for ($i = 0; $i < $counter; ++$i) {
            $query = sprintf(
                'UPDATE %sfaqdata SET sticky_order=%d WHERE id=%d',
                Database::getTablePrefix(),
                $count,
                $normalizedFaqIds[$i],
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

    /**
     * @param int[] $currentGroups
     */
    private function userCanEditFaq(int $faqId, int $currentUserId, array $currentGroups): bool
    {
        $query = $this->configuration->get(item: 'security.permLevel') !== 'basic'
            ? $this->buildGroupAwareFaqAccessQuery($faqId, $currentUserId, $currentGroups)
            : $this->buildBasicFaqAccessQuery($faqId, $currentUserId);

        $result = $this->configuration->getDb()->query($query);
        $row = $this->configuration->getDb()->fetchObject($result);

        return is_object($row);
    }

    /**
     * @param int[] $currentGroups
     */
    private function buildGroupAwareFaqAccessQuery(int $faqId, int $currentUserId, array $currentGroups): string
    {
        $groupIds = $this->sanitizePermissionIds($currentGroups);

        return sprintf('SELECT id FROM %1$sfaqdata fd WHERE fd.id = %2$d AND (
                EXISTS (
                    SELECT 1 FROM %1$sfaqdata_user fdu
                    WHERE fdu.record_id = fd.id AND fdu.user_id IN (-1, %3$d)
                )
                OR EXISTS (
                    SELECT 1 FROM %1$sfaqdata_group fdg
                    WHERE fdg.record_id = fd.id AND fdg.group_id IN (%4$s)
                )
                OR (
                    NOT EXISTS (SELECT 1 FROM %1$sfaqdata_user fdu_all WHERE fdu_all.record_id = fd.id)
                    AND NOT EXISTS (SELECT 1 FROM %1$sfaqdata_group fdg_all WHERE fdg_all.record_id = fd.id)
                )
            )', Database::getTablePrefix(), $faqId, $currentUserId, implode(', ', $groupIds));
    }

    private function buildBasicFaqAccessQuery(int $faqId, int $currentUserId): string
    {
        return sprintf('SELECT id FROM %1$sfaqdata fd WHERE fd.id = %2$d AND (
                EXISTS (
                    SELECT 1 FROM %1$sfaqdata_user fdu
                    WHERE fdu.record_id = fd.id AND fdu.user_id IN (-1, %3$d)
                )
                OR NOT EXISTS (
                    SELECT 1 FROM %1$sfaqdata_user fdu_all WHERE fdu_all.record_id = fd.id
                )
            )', Database::getTablePrefix(), $faqId, $currentUserId);
    }

    /**
     * @param array<int|string> $faqIds
     * @return int[]
     */
    private function sanitizeFaqIds(array $faqIds): array
    {
        $sanitizedFaqIds = array_map(static fn(int|string $faqId): int => (int) $faqId, $faqIds);
        $sanitizedFaqIds = array_filter($sanitizedFaqIds, static fn(int $faqId): bool => $faqId > 0);

        return array_values(array_unique($sanitizedFaqIds));
    }

    /**
     * @param array<int|string> $permissionIds
     * @return int[]
     */
    private function sanitizePermissionIds(array $permissionIds): array
    {
        $sanitizedPermissionIds = array_map(
            static fn(int|string $permissionId): int => (int) $permissionId,
            $permissionIds,
        );
        $sanitizedPermissionIds = array_values(array_unique($sanitizedPermissionIds));

        return $sanitizedPermissionIds === [] ? [-1] : $sanitizedPermissionIds;
    }
}
