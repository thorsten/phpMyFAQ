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

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;

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
        $row = false;
        if ($result) {
            $row = $this->configuration->getDb()->fetchObject($result);
        }

        if ($row) {
            $latestId = $row->solution_id;
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
            if ($row === false || $row === null || $row === []) {
                break;
            }

            $question = $row->question;
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

        return $row->keywords;
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

        return is_object($row) ? $row : null;
    }

    public function fetchRowBySolutionId(int $solutionId, int $userId, array $groups, bool $groupSupport): ?object
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
            $hasRestriction = $restrictionResult && $this->configuration->getDb()->fetchObject($restrictionResult);

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

        return is_object($row) ? $row : null;
    }
}
