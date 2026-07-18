<?php

/**
 * FAQ repository: database access for FAQ lookups.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-06-29
 */

declare(strict_types=1);

namespace phpMyFAQ\Faq;

use DateTime;
use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Entity\FaqEntity;

final class FaqRepository implements FaqRepositoryInterface
{
    public function __construct(
        private readonly Configuration $configuration,
    ) {
    }

    public function getNextSolutionId(): int
    {
        $latestId = 0;

        $query = sprintf('SELECT MAX(solution_id) AS solution_id FROM %sfaqdata', Database::getTablePrefix());

        $result = $this->configuration->getDb()->query($query);
        if ($result) {
            $row = $this->configuration->getDb()->fetchObject($result);
            if ($row instanceof \stdClass) {
                $latestId = (int) $row->solution_id;
            }
        }

        if ($latestId < PMF_SOLUTION_ID_START_VALUE) {
            return PMF_SOLUTION_ID_START_VALUE;
        }

        return $latestId + PMF_SOLUTION_ID_INCREMENT_VALUE;
    }

    public function getSolutionIdFromId(int $faqId, string $faqLang): int
    {
        $query = sprintf(
            "SELECT solution_id FROM %sfaqdata WHERE id = %d AND lang = '%s'",
            Database::getTablePrefix(),
            $faqId,
            $this->configuration->getDb()->escape($faqLang),
        );

        $result = $this->configuration->getDb()->query($query);

        $row = $this->configuration->getDb()->fetchObject($result);
        if ($row) {
            return (int) $row->solution_id;
        }

        return $this->getNextSolutionId();
    }

    public function hasTranslation(int $faqId, string $faqLang): bool
    {
        $query = sprintf(
            "
            SELECT
                id, lang
            FROM
                %sfaqdata
            WHERE
                id = %d
            AND
                lang = '%s'",
            Database::getTablePrefix(),
            $faqId,
            $this->configuration->getDb()->escape($faqLang),
        );

        $result = $this->configuration->getDb()->query($query);
        return (bool) $this->configuration->getDb()->numRows($result);
    }

    public function isActive(int $faqId, string $faqLang, string $commentType = 'faq'): bool
    {
        $table = 'news' === $commentType ? 'faqnews' : 'faqdata';

        $query = sprintf(
            "
            SELECT
                active
            FROM
                %s%s
            WHERE
                id = %d
            AND
                lang = '%s'",
            Database::getTablePrefix(),
            $table,
            $faqId,
            $this->configuration->getDb()->escape($faqLang),
        );

        $result = $this->configuration->getDb()->query($query);

        $row = $this->configuration->getDb()->fetchObject($result);
        if (!$row) {
            return false;
        }

        if ($row->active === 'y' || $row->active === 'yes') {
            return true;
        }

        return false;
    }

    public function getIdFromSolutionId(int $solutionId, int $userId, array $groups, bool $groupSupport): array
    {
        $queryHelper = new QueryHelper($userId, $groups);
        $query = sprintf(
            '
            SELECT
                fd.id,
                fd.lang,
                fd.thema AS question,
                fd.content,
                fcr.category_id AS category_id
            FROM
                %sfaqdata fd
            LEFT JOIN
                %sfaqcategoryrelations fcr
            ON
                fd.id = fcr.record_id
            AND
                fd.lang = fcr.record_lang
            LEFT JOIN
                %sfaqdata_group fdg
            ON
                fd.id = fdg.record_id
            LEFT JOIN
                %sfaqdata_user fdu
            ON
                fd.id = fdu.record_id
            WHERE
                fd.solution_id = %d
                %s',
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            $solutionId,
            $queryHelper->queryPermission($groupSupport),
        );

        $result = $this->configuration->getDb()->query($query);

        $row = $this->configuration->getDb()->fetchObject($result);
        if ($row) {
            return [
                'id' => $row->id,
                'lang' => $row->lang,
                'question' => $row->question,
                'content' => $row->content,
                'category_id' => $row->category_id,
            ];
        }

        return [];
    }

    public function fetchQuestion(int $faqId, string $language): ?string
    {
        $query = sprintf(
            "SELECT thema AS question FROM %sfaqdata WHERE id = %d AND lang = '%s'",
            Database::getTablePrefix(),
            $faqId,
            $this->configuration->getDb()->escape($language),
        );
        $result = $this->configuration->getDb()->query($query);

        if ($this->configuration->getDb()->numRows($result) === 0) {
            return null;
        }

        $question = null;
        while (true) {
            $row = $this->configuration->getDb()->fetchObject($result);
            if (!$row instanceof \stdClass) {
                break;
            }

            $question = (string) $row->question;
        }

        return $question;
    }

    public function fetchKeywords(int $faqId, string $language): ?string
    {
        $query = sprintf(
            "SELECT keywords FROM %sfaqdata WHERE id = %d AND lang = '%s'",
            Database::getTablePrefix(),
            $faqId,
            $this->configuration->getDb()->escape($language),
        );

        $result = $this->configuration->getDb()->query($query);

        if ($this->configuration->getDb()->numRows($result) === 0) {
            return null;
        }

        $row = $this->configuration->getDb()->fetchObject($result);

        return $row instanceof \stdClass ? (string) $row->keywords : null;
    }

    public function getFaqResult(
        int $faqId,
        string $faqLanguage,
        ?int $faqRevisionId,
        bool $isAdmin,
        int $userId,
        array $groups,
        bool $groupSupport,
    ): mixed {
        $queryHelper = new QueryHelper($userId, $groups);
        $query = sprintf(
            "SELECT
                 id, lang, solution_id, revision_id, active, sticky, keywords,
                 thema, content, author, email, comment, updated, date_start,
                 date_end, created, notes
            FROM
                %s%s fd
            LEFT JOIN
                %sfaqdata_group fdg
            ON
                fd.id = fdg.record_id
            LEFT JOIN
                %sfaqdata_user fdu
            ON
                fd.id = fdu.record_id
            WHERE
                fd.id = %d
            %s
            AND
                fd.lang = '%s'
                %s",
            Database::getTablePrefix(),
            $faqRevisionId !== null ? 'faqdata_revisions' : 'faqdata',
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            $faqId,
            $faqRevisionId !== null ? 'AND revision_id = ' . $faqRevisionId : '',
            $this->configuration->getDb()->escape($faqLanguage),
            $isAdmin ? 'AND 1=1' : $queryHelper->queryPermission($groupSupport),
        );

        return $this->configuration->getDb()->query($query);
    }

    public function fetchFaqByIdAndCategoryId(
        int $faqId,
        int $categoryId,
        bool $onlyActive,
        int $userId,
        array $groups,
        bool $groupSupport,
    ): ?object {
        $queryHelper = new QueryHelper($userId, $groups);
        $now = date(format: 'YmdHis');
        $query = sprintf(
            "
            SELECT
                fd.id AS id,
                fd.lang AS lang,
                fd.solution_id AS solution_id,
                fd.revision_id AS revision_id,
                fd.active AS active,
                fd.sticky AS sticky,
                fd.keywords AS keywords,
                fd.thema AS question,
                fd.content AS answer,
                fd.author AS author,
                fd.email AS email,
                fd.comment AS comment,
                fd.updated AS updated,
                fd.date_start AS date_start,
                fd.date_end AS date_end,
                fd.created AS created,
                fcr.category_id AS category_id
            FROM
                %sfaqdata AS fd
            LEFT JOIN
                %sfaqcategoryrelations AS fcr
            ON
                fd.id = fcr.record_id
            AND
                fd.lang = fcr.record_lang
            LEFT JOIN
                %sfaqdata_group AS fdg
            ON
                fd.id = fdg.record_id
            LEFT JOIN
                %sfaqdata_user AS fdu
            ON
                fd.id = fdu.record_id
            WHERE
                fd.id = %d
            AND
                fcr.category_id = %d
            AND
                fd.lang = '%s'
                %s
                %s",
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            $faqId,
            $categoryId,
            $this->configuration->getDb()->escape($this->configuration->getLanguage()->getLanguage()),
            $onlyActive
                ? sprintf("AND fd.active = 'yes' AND fd.date_start <= '%s' AND fd.date_end >= '%s'", $now, $now)
                : '',
            $queryHelper->queryPermission($groupSupport),
        );

        $result = $this->configuration->getDb()->query($query);
        $row = $this->configuration->getDb()->fetchObject($result);

        return $row instanceof \stdClass ? $row : null;
    }

    public function fetchRowBySolutionId(int $solutionId, int $userId, array $groups, bool $groupSupport): ?\stdClass
    {
        $queryHelper = new QueryHelper($userId, $groups);
        $query = sprintf(
            'SELECT
                fd.*, COALESCE(fdg.group_id, -1) AS group_id, fdu.user_id
            FROM
                %sfaqdata fd
            LEFT JOIN (
                SELECT record_id, group_id FROM %sfaqdata_group fdg WHERE fdg.group_id <> -1
                UNION ALL
                SELECT fd.id AS record_id, -1 AS group_id FROM %sfaqdata fd WHERE fd.solution_id = %d
            ) AS fdg
            ON
                fd.id = fdg.record_id
            LEFT JOIN
                %sfaqdata_user fdu
            ON
                fd.id = fdu.record_id
            WHERE
                fd.solution_id = %d
                %s',
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            $solutionId,
            Database::getTablePrefix(),
            $solutionId,
            $queryHelper->queryPermission($groupSupport),
        );

        $result = $this->configuration->getDb()->query($query);

        $row = $this->configuration->getDb()->fetchObject($result);

        if (false === $row || null === $row) {
            $restrictionQuery = sprintf('SELECT 1
                FROM %1$sfaqdata fd
                LEFT JOIN %1$sfaqdata_user fdu ON fd.id = fdu.record_id
                LEFT JOIN %1$sfaqdata_group fdg ON fd.id = fdg.record_id
                WHERE fd.solution_id = %2$d
                AND (fdu.user_id IS NOT NULL OR fdg.group_id IS NOT NULL)
                LIMIT 1', Database::getTablePrefix(), $solutionId);
            $restrictionResult = $this->configuration->getDb()->query($restrictionQuery);
            $hasRestriction =
                $restrictionResult !== false
                && $restrictionResult !== null
                && $this->configuration->getDb()->fetchObject($restrictionResult) instanceof \stdClass;

            if (!$hasRestriction) {
                $fallbackQuery = sprintf(
                    'SELECT * FROM %sfaqdata fd WHERE fd.solution_id = %d LIMIT 1',
                    Database::getTablePrefix(),
                    $solutionId,
                );
                $fallbackResult = $this->configuration->getDb()->query($fallbackQuery);
                $row = $this->configuration->getDb()->fetchObject($fallbackResult);
            }
        }

        return $row instanceof \stdClass ? $row : null;
    }

    /**
     * @return list<\stdClass>
     */
    public function fetchAvailableFaqsByCategoryId(
        int $categoryId,
        string $orderTable,
        string $orderColumn,
        string $sortDirection,
        int $userId,
        array $groups,
        bool $groupSupport,
    ): array {
        $now = date(format: 'YmdHis');
        $queryHelper = new QueryHelper($userId, $groups);
        $query = sprintf(
            "
            SELECT
                fd.id AS id,
                fd.lang AS lang,
                fd.thema AS thema,
                fd.content AS record_content,
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
            WHERE
                fd.date_start <= '%s'
            AND
                fd.date_end   >= '%s'
            AND
                fd.active = 'yes'
            AND
                fcr.category_id = %d
            AND
                fd.lang = '%s'
                %s
            ORDER BY
                %s.%s %s",
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            $now,
            $now,
            $categoryId,
            $this->configuration->getDb()->escape($this->configuration->getLanguage()->getLanguage()),
            $queryHelper->queryPermissionExistsAny($groupSupport),
            $orderTable,
            $orderColumn,
            $sortDirection,
        );

        return $this->fetchAllRows($this->configuration->getDb()->query($query));
    }

    /**
     * @return list<\stdClass>
     */
    public function fetchFaqsByIds(
        string $records,
        bool $onlyActive,
        int $userId,
        array $groups,
        bool $groupSupport,
    ): array {
        $now = date(format: 'YmdHis');
        $queryHelper = new QueryHelper($userId, $groups);
        $query = sprintf(
            "SELECT
                 fd.id AS id,
                 fd.lang AS lang,
                 fd.thema AS question,
                 fd.content AS answer,
                 fd.updated AS updated,
                 fd.created AS created,
                 fcr.category_id AS category_id,
                 fv.visits AS visits
            FROM
                %sfaqdata fd
            LEFT JOIN
                %sfaqcategoryrelations fcr
            ON
                fd.id = fcr.record_id
            AND
                fd.lang = fcr.record_lang
            LEFT JOIN
                %sfaqdata_group fdg
            ON
                fd.id = fdg.record_id
            LEFT JOIN
                %sfaqvisits AS fv
            ON
                fd.id = fv.id
            AND
                fv.lang = fd.lang
            LEFT JOIN
                %sfaqdata_user fdu
            ON
                fd.id = fdu.record_id
            WHERE
                fd.id IN (%s)
            AND
                fd.lang = '%s'
                %s
                %s",
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            $records,
            $this->configuration->getDb()->escape($this->configuration->getLanguage()->getLanguage()),
            $onlyActive
                ? sprintf("AND fd.active = 'yes' AND fd.date_start <= '%s' AND fd.date_end >= '%s'", $now, $now)
                : '',
            $queryHelper->queryPermission($groupSupport),
        );

        return $this->fetchAllRows($this->configuration->getDb()->query($query));
    }

    /**
     * @return list<\stdClass>
     */
    public function fetchStickyFaqs(int $userId, array $groups, bool $groupSupport): array
    {
        $queryHelper = new QueryHelper($userId, $groups);
        $query = sprintf(
            "
            SELECT
                fd.id AS id,
                fd.lang AS lang,
                fd.thema AS thema,
                fd.sticky_order AS sticky_order,
                fcr.category_id AS category_id,
                fv.visits AS visits
            FROM
                %sfaqdata fd
            LEFT JOIN
                %sfaqvisits fv
            ON
                fd.id = fv.id
            AND
                fd.lang = fv.lang
            LEFT JOIN
                %sfaqcategoryrelations fcr
            ON
                fd.id = fcr.record_id
            AND
                fd.lang = fcr.record_lang
            LEFT JOIN
                %sfaqdata_group AS fdg
            ON
                fd.id = fdg.record_id
            LEFT JOIN
                %sfaqdata_user AS fdu
            ON
                fd.id = fdu.record_id
            WHERE
                fd.lang = '%s'
            AND
                fd.active = 'yes'
            AND
                fd.sticky = 1
            %s
            GROUP BY
                fd.id, fd.lang, fd.thema, fcr.category_id, fv.visits
            ORDER BY
                fv.visits DESC",
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            $this->configuration->getDb()->escape($this->configuration->getLanguage()->getLanguage()),
            $queryHelper->queryPermission($groupSupport),
        );

        return $this->fetchAllRows($this->configuration->getDb()->query($query));
    }

    /**
     * @return list<\stdClass>
     */
    public function fetchAllFaqs(
        ?array $condition,
        string $orderBy,
        int $userId,
        array $groups,
        bool $groupSupport,
    ): array {
        $where = $this->buildConditionWhereClause($condition);

        // prevents multiple display of FAQ in case it is tagged under multiple groups.
        $groupBy =
            ' group by fd.id, fcr.category_id,fd.solution_id,fd.revision_id,fd.active,fd.sticky,fd.keywords,'
            . 'fd.thema,fd.content,fd.author,fd.email,fd.comment,fd.updated,'
            . 'fd.date_start,fd.date_end,fd.sticky,fd.created,fd.notes,fd.lang ';
        $queryHelper = new QueryHelper($userId, $groups);
        $query = sprintf(
            '
            SELECT
                fd.id AS id,
                fd.lang AS lang,
                fcr.category_id AS category_id,
                fd.solution_id AS solution_id,
                fd.revision_id AS revision_id,
                fd.active AS active,
                fd.sticky AS sticky,
                fd.keywords AS keywords,
                fd.thema AS thema,
                fd.content AS content,
                fd.author AS author,
                fd.email AS email,
                fd.comment AS comment,
                fd.updated AS updated,
                fd.date_start AS date_start,
                fd.date_end AS date_end,
                fd.sticky AS sticky,
                fd.created AS created,
                fd.notes AS notes
            FROM
                %sfaqdata fd
            LEFT JOIN
                %sfaqcategoryrelations fcr
            ON
                fd.id = fcr.record_id
            AND
                fd.lang = fcr.record_lang
            LEFT JOIN
                %sfaqdata_group AS fdg
            ON
                fd.id = fdg.record_id
            LEFT JOIN
                %sfaqdata_user AS fdu
            ON
                fd.id = fdu.record_id
            %s
            %s
            %s
            %s',
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            $where,
            $queryHelper->queryPermission($groupSupport),
            $groupBy,
            $orderBy,
        );

        return $this->fetchAllRows($this->configuration->getDb()->query($query));
    }

    public function insert(FaqEntity $faqEntity): void
    {
        $query = sprintf(
            "INSERT INTO %sfaqdata
            (id, lang, solution_id, revision_id, active, sticky, keywords, thema, content, author, email, comment,
            updated, date_start, date_end, created, notes)
            VALUES
            (%d, '%s', %d, %d, '%s', %d, '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')",
            Database::getTablePrefix(),
            $faqEntity->getId(),
            $this->configuration->getDb()->escape($faqEntity->getLanguage()),
            $faqEntity->getSolutionId(),
            $faqEntity->getRevisionId(),
            $faqEntity->isActive() ? 'yes' : 'no',
            $faqEntity->isSticky() ? 1 : 0,
            $this->configuration->getDb()->escape($faqEntity->getKeywords()),
            $this->configuration->getDb()->escape($faqEntity->getQuestion()),
            $this->configuration->getDb()->escape($faqEntity->getAnswer()),
            $this->configuration->getDb()->escape($faqEntity->getAuthor()),
            $this->configuration->getDb()->escape($faqEntity->getEmail()),
            $faqEntity->isComment() ? 'y' : 'n',
            date(format: 'YmdHis'),
            '00000000000000',
            '99991231235959',
            date(format: 'Y-m-d H:i:s'),
            $this->configuration->getDb()->escape($faqEntity->getNotes()),
        );

        $this->configuration->getDb()->query($query);
    }

    public function update(FaqEntity $faqEntity): void
    {
        $query = sprintf(
            "UPDATE %sfaqdata SET
            revision_id = %d,
            active = '%s',
            sticky = %d,
            keywords = '%s',
            thema = '%s',
            content = '%s',
            author = '%s',
            email = '%s',
            comment = '%s',
            date_start = '%s',
            date_end = '%s',
            notes = '%s'",
            Database::getTablePrefix(),
            $faqEntity->getRevisionId(),
            $faqEntity->isActive() ? 'yes' : 'no',
            $faqEntity->isSticky() ? 1 : 0,
            $this->configuration->getDb()->escape($faqEntity->getKeywords()),
            $this->configuration->getDb()->escape($faqEntity->getQuestion()),
            $this->configuration->getDb()->escape($faqEntity->getAnswer()),
            $this->configuration->getDb()->escape($faqEntity->getAuthor()),
            $this->configuration->getDb()->escape($faqEntity->getEmail()),
            $faqEntity->isComment() ? 'y' : 'n',
            $faqEntity->getValidFrom()->format('YmdHis'),
            $faqEntity->getValidTo()->format('YmdHis'),
            $this->configuration->getDb()->escape($faqEntity->getNotes()),
        );

        // Conditionally add the updated field
        $updatedDate = $faqEntity->getUpdatedDate();
        if ($updatedDate instanceof DateTime) {
            $query .= sprintf(", updated = '%s'", $updatedDate->format('YmdHis'));
        }

        $query .= sprintf(
            " WHERE id = %d AND lang = '%s'",
            $faqEntity->getId(),
            $this->configuration->getDb()->escape($faqEntity->getLanguage()),
        );

        $this->configuration->getDb()->query($query);
    }

    public function deleteByIdAndLanguage(int $faqId, string $faqLang): void
    {
        $queries = [
            sprintf('DELETE FROM %sfaqbookmarks WHERE faqid = %d', Database::getTablePrefix(), $faqId),
            sprintf(
                "DELETE FROM %sfaqchanges WHERE beitrag = %d AND lang = '%s'",
                Database::getTablePrefix(),
                $faqId,
                $this->configuration->getDb()->escape($faqLang),
            ),
            sprintf(
                "DELETE FROM %sfaqcategoryrelations WHERE record_id = %d AND record_lang = '%s'",
                Database::getTablePrefix(),
                $faqId,
                $this->configuration->getDb()->escape($faqLang),
            ),
            sprintf(
                "DELETE FROM %sfaqdata WHERE id = %d AND lang = '%s'",
                Database::getTablePrefix(),
                $faqId,
                $this->configuration->getDb()->escape($faqLang),
            ),
            sprintf(
                "DELETE FROM %sfaqdata_revisions WHERE id = %d AND lang = '%s'",
                Database::getTablePrefix(),
                $faqId,
                $this->configuration->getDb()->escape($faqLang),
            ),
            sprintf(
                "DELETE FROM %sfaqvisits WHERE id = %d AND lang = '%s'",
                Database::getTablePrefix(),
                $faqId,
                $this->configuration->getDb()->escape($faqLang),
            ),
            sprintf('DELETE FROM %sfaqdata_user WHERE record_id = %d', Database::getTablePrefix(), $faqId),
            sprintf('DELETE FROM %sfaqdata_group WHERE record_id = %d', Database::getTablePrefix(), $faqId),
            sprintf('DELETE FROM %sfaqdata_tags WHERE record_id = %d', Database::getTablePrefix(), $faqId),
            sprintf(
                'DELETE FROM %sfaqdata_tags WHERE %sfaqdata_tags.record_id NOT IN (SELECT %sfaqdata.id FROM %sfaqdata)',
                Database::getTablePrefix(),
                Database::getTablePrefix(),
                Database::getTablePrefix(),
                Database::getTablePrefix(),
            ),
            sprintf('DELETE FROM %sfaqcomments WHERE id = %d', Database::getTablePrefix(), $faqId),
            sprintf('DELETE FROM %sfaqvoting WHERE artikel = %d', Database::getTablePrefix(), $faqId),
        ];

        foreach ($queries as $query) {
            $this->configuration->getDb()->query($query);
        }
    }

    public function queryRenderableFaqsByCategoryId(
        int $categoryId,
        string $order,
        int $userId,
        array $groups,
        bool $groupSupport,
        int $offset = 0,
        int $rowcount = 0,
    ): mixed {
        $now = date(format: 'YmdHis');
        $queryHelper = new QueryHelper($userId, $groups);
        $query = sprintf(
            "
            SELECT
                fd.id AS id,
                fd.lang AS lang,
                fd.sticky AS sticky,
                fd.thema AS question,
                fd.content as answer,
                fcr.category_id AS category_id,
                fv.visits AS visits
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
            WHERE
                fd.date_start <= '%s'
            AND
                fd.date_end   >= '%s'
            AND
                fd.active = 'yes'
            AND
                fcr.category_id = %d
            AND
                fd.lang = '%s'
            %s
            %s",
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            $now,
            $now,
            $categoryId,
            $this->configuration->getDb()->escape($this->configuration->getLanguage()->getLanguage()),
            $queryHelper->queryPermissionExistsAny($groupSupport),
            $order,
        );

        return $this->configuration->getDb()->query($query, $offset, $rowcount);
    }

    /**
     * Counts the renderable FAQs of one category for the given permission context,
     * matching the filters of queryRenderableFaqsByCategoryId().
     */
    public function countRenderableFaqsByCategoryId(
        int $categoryId,
        int $userId,
        array $groups,
        bool $groupSupport,
    ): int {
        $now = date(format: 'YmdHis');
        $queryHelper = new QueryHelper($userId, $groups);
        $query = sprintf(
            'SELECT COUNT(*) AS total FROM %sfaqdata fd '
            . "WHERE fd.date_start <= '%s' AND fd.date_end >= '%s' AND fd.active = 'yes' AND fd.lang = '%s' "
            . 'AND EXISTS (SELECT 1 FROM %sfaqcategoryrelations fcr '
            . 'WHERE fcr.record_id = fd.id AND fcr.record_lang = fd.lang AND fcr.category_id = %d) %s',
            Database::getTablePrefix(),
            $now,
            $now,
            $this->configuration->getDb()->escape($this->configuration->getLanguage()->getLanguage()),
            Database::getTablePrefix(),
            $categoryId,
            $queryHelper->queryPermissionExistsAny($groupSupport),
        );

        $result = $this->configuration->getDb()->query($query);
        $row = $result !== false ? $this->configuration->getDb()->fetchObject($result) : null;

        return is_object($row) ? (int) $row->total : 0;
    }

    public function queryRenderableFaqsByIds(
        string $records,
        string $orderExpression,
        string $sortDirection,
        int $userId,
        array $groups,
        bool $groupSupport,
    ): mixed {
        $now = date(format: 'YmdHis');
        $queryHelper = new QueryHelper($userId, $groups);
        $query = sprintf(
            "
            SELECT
                fd.id AS id,
                fd.lang AS lang,
                fd.thema AS question,
                fd.content AS answer,
                fcr.category_id AS category_id,
                fv.visits AS visits
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
            WHERE
                fd.date_start <= '%s'
            AND
                fd.date_end   >= '%s'
            AND
                fd.active = 'yes'
            AND
                fd.id IN (%s)
            AND
                fd.lang = '%s'
                %s
            ORDER BY
                %s %s",
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            $now,
            $now,
            $records,
            $this->configuration->getDb()->escape($this->configuration->getLanguage()->getLanguage()),
            $queryHelper->queryPermissionExistsAny($groupSupport),
            $orderExpression,
            $sortDirection,
        );

        return $this->configuration->getDb()->query($query);
    }

    /**
     * Builds the WHERE clause for getAllFaqs() from a field => condition map, escaping values.
     *
     * @param array<string, mixed>|null $condition
     */
    private function buildConditionWhereClause(?array $condition): string
    {
        if ($condition === null) {
            return '';
        }

        $condition = array_filter($condition, static fn($value): bool => $value !== null);

        $num = count($condition);
        $where = 'WHERE ';
        foreach ($condition as $field => $data) {
            --$num;
            $where .= $field;
            if (is_array($data)) {
                $where .= ' IN (';
                $separator = '';
                foreach ($data as $value) {
                    $where .= $separator . "'" . $this->configuration->getDb()->escape((string) $value) . "'";
                    $separator = ', ';
                }

                $where .= ')';
            }

            if (!is_array($data) && $data === 'IS NOT NULL') {
                $where .= ' IS NOT NULL';
            }

            if (!is_array($data) && $data === 'IS NULL') {
                $where .= ' IS NULL';
            }

            if (!is_array($data) && $data !== 'IS NOT NULL' && $data !== 'IS NULL') {
                $where .= " = '" . $this->configuration->getDb()->escape((string) $data) . "'";
            }

            if ($num > 0) {
                $where .= ' AND ';
            }
        }

        return $where;
    }

    /**
     * Collects every row of a database result into an array of objects.
     *
     * @return list<\stdClass>
     */
    private function fetchAllRows(mixed $result): array
    {
        $rows = [];
        while (true) {
            $row = $this->configuration->getDb()->fetchObject($result);
            if (!$row instanceof \stdClass) {
                break;
            }

            $rows[] = $row;
        }

        return $rows;
    }
}
