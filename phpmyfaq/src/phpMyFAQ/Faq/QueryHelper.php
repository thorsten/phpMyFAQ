<?php

/**
 * The query helpers for the FAQ class.
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
 * @since     2024-03-17
 */

declare(strict_types=1);

namespace phpMyFAQ\Faq;

use phpMyFAQ\Category;
use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Utils;

readonly class QueryHelper
{
    public const string FAQ_SQL_ACTIVE_YES = 'yes';

    public const string FAQ_SQL_ACTIVE_NO = 'no';

    public const string FAQ_QUERY_TYPE_APPROVAL = 'faq_approval';

    public const string FAQ_QUERY_TYPE_EXPORT_PDF = 'faq_export_pdf';

    public const string FAQ_QUERY_TYPE_EXPORT_JSON = 'faq_export_json';

    private Configuration $configuration;

    /**
     * @param int[] $groups
     */
    public function __construct(
        private int $user,
        private array $groups,
    ) {
        $this->configuration = Configuration::getConfigurationInstance();
    }

    public function queryPermission(bool $hasGroupSupport = false): string
    {
        $groupList = $this->normalizeIdList($this->groups);
        if ($hasGroupSupport) {
            if (-1 === $this->user) {
                return sprintf('AND fdg.group_id IN (%s)', $groupList);
            }

            return sprintf('AND ( fdu.user_id = %d OR fdg.group_id IN (%s) )', $this->user, $groupList);
        }

        if (-1 !== $this->user) {
            return sprintf('AND ( fdu.user_id = %d OR fdu.user_id = -1 )', $this->user);
        }

        return 'AND fdu.user_id = -1';
    }

    /**
     * Permission filter as EXISTS subqueries requiring BOTH a user grant and a group
     * grant (the effective statistics semantics). Unlike queryPermission(), this does
     * not depend on LEFT JOINs against faqdata_user/faqdata_group in the outer query,
     * so it never fans one FAQ out into multiple result rows — a prerequisite for SQL LIMIT.
     */
    public function queryPermissionExistsAll(bool $hasGroupSupport = false): string
    {
        $clause = 'AND ' . $this->userGrantExists();

        if ($hasGroupSupport) {
            $clause .= ' AND ' . $this->groupGrantExists();
        }

        return $clause;
    }

    /**
     * Permission filter as EXISTS subqueries with the same semantics as queryPermission()
     * (a user grant OR a group grant suffices), but without the LEFT JOIN fanout, so
     * result rows stay one-per-FAQ and SQL LIMIT/COUNT are reliable.
     */
    public function queryPermissionExistsAny(bool $hasGroupSupport = false): string
    {
        if (!$hasGroupSupport) {
            return 'AND ' . $this->userGrantExists();
        }

        if (-1 === $this->user) {
            return 'AND ' . $this->groupGrantExists();
        }

        return sprintf(
            'AND (EXISTS (SELECT 1 FROM %sfaqdata_user pfdu '
            . 'WHERE pfdu.record_id = fd.id AND pfdu.user_id = %d) OR %s)',
            Database::getTablePrefix(),
            $this->user,
            $this->groupGrantExists(),
        );
    }

    private function userGrantExists(): string
    {
        $userIds = -1 === $this->user ? '-1' : sprintf('-1, %d', $this->user);

        return sprintf(
            'EXISTS (SELECT 1 FROM %sfaqdata_user pfdu WHERE pfdu.record_id = fd.id AND pfdu.user_id IN (%s))',
            Database::getTablePrefix(),
            $userIds,
        );
    }

    private function groupGrantExists(): string
    {
        return sprintf(
            'EXISTS (SELECT 1 FROM %sfaqdata_group pfdg WHERE pfdg.record_id = fd.id AND pfdg.group_id IN (%s))',
            Database::getTablePrefix(),
            $this->normalizeIdList($this->groups),
        );
    }

    /**
     * Build the SQL query for retrieving FAQ records according to the constraints provided.
     */
    public function getQuery(
        string $queryType,
        int $categoryId,
        bool $bDownwards,
        string $lang,
        string $date,
        int $faqId = 0,
    ): string {
        $query = sprintf(
            '
            SELECT
                fd.id AS id,
                fd.solution_id AS solution_id,
                fd.revision_id AS revision_id,
                fd.lang AS lang,
                fcr.category_id AS category_id,
                fd.active AS active,
                fd.sticky AS sticky,
                fd.keywords AS keywords,
                fd.thema AS thema,
                fd.content AS content,
                fd.author AS author,
                fd.email AS email,
                fd.comment AS comment,
                fd.updated AS updated,
                fd.notes AS notes,
                fv.visits AS visits,
                fv.last_visit AS last_visit
            FROM
                %sfaqdata fd,
                %sfaqvisits fv,
                %sfaqcategoryrelations fcr
            WHERE
                fd.id = fcr.record_id
            AND
                fd.lang = fcr.record_lang
            AND ',
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            Database::getTablePrefix(),
        );
        // faqvisits data selection
        if ($faqId !== 0) {
            // Select ONLY the faq with the provided $faqid
            $query .= "fd.id = '" . $faqId . "' AND ";
        }

        $query .= 'fd.id = fv.id
            AND
                fd.lang = fv.lang';

        if ($categoryId > 0) {
            $query .= ' AND';
            $query .= ' (fcr.category_id = ' . $categoryId;
            if ($bDownwards) {
                $query .= $this->getCategoryIdWhereSequence($categoryId);
            }

            $query .= ')';
        }

        if ($date !== '' && $date !== '0' && Utils::isLikeOnPMFDate($date)) {
            $query .= ' AND';
            $query .= " fd.updated LIKE '" . $date . "'";
        }

        if ($lang !== '' && $lang !== '0' && Utils::isLanguage($lang)) {
            $query .= ' AND';
            $query .= " fd.lang = '" . $this->configuration->getDb()->escape($lang) . "'";
        }

        switch ($queryType) {
            case self::FAQ_QUERY_TYPE_APPROVAL:
                $query .= ' AND';
                $query .= " fd.active = '" . self::FAQ_SQL_ACTIVE_NO . "'";
                break;
            case self::FAQ_QUERY_TYPE_EXPORT_PDF:
            case self::FAQ_QUERY_TYPE_EXPORT_JSON:
            default:
                $query .= ' AND';
                $query .= " fd.active = '" . self::FAQ_SQL_ACTIVE_YES . "'";
                break;
        }

        match ($queryType) {
            self::FAQ_QUERY_TYPE_EXPORT_PDF,
            self::FAQ_QUERY_TYPE_EXPORT_JSON,
                => $query .= "\nORDER BY fcr.category_id, fd.id",
            default => $query .= "\nORDER BY fcr.category_id, fd.id",
        };

        return $query;
    }

    /**
     * Build a logic sequence, for a WHERE statement, of those category IDs
     * children of the provided category ID, if any.
     */
    private function getCategoryIdWhereSequence(int $categoryId, ?Category $category = null): string
    {
        $sqlWhereFilter = '';

        if ($category === null) {
            $category = new Category($this->configuration);
        }

        $aChildren = array_values($category->getChildren($categoryId));

        foreach ($aChildren as $aChild) {
            $sqlWhereFilter .= ' OR fcr.category_id = ' . $aChild;
            $sqlWhereFilter .= $this->getCategoryIdWhereSequence($aChild, $category);
        }

        return $sqlWhereFilter;
    }

    /**
     * @param array<int|string> $ids
     */
    private function normalizeIdList(array $ids): string
    {
        $normalizedIds = array_map(static fn($id): int => (int) $id, $ids);

        return $normalizedIds === [] ? '-1' : implode(', ', $normalizedIds);
    }
}
