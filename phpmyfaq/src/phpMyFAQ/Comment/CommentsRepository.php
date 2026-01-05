<?php

/**
 * Repository for comments-related database operations
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
 * @since     2025-11-04
 */

declare(strict_types=1);

namespace phpMyFAQ\Comment;

use phpMyFAQ\Configuration as CoreConfiguration;
use phpMyFAQ\Database;
use phpMyFAQ\Entity\Comment;
use phpMyFAQ\Entity\CommentType;

readonly class CommentsRepository implements CommentsRepositoryInterface
{
    public function __construct(
        private CoreConfiguration $coreConfiguration,
    ) {
    }

    /**
     * @return array<int, object>
     */
    public function fetchByReferenceIdAndType(int $referenceId, string $type): array
    {
        $sql = <<<SQL
            SELECT
                id_comment, id, usr, email, comment, datum
            FROM
                %sfaqcomments
            WHERE
                type = '%s'
            AND 
                id = %d
        SQL;

        $query = sprintf(
            $sql,
            Database::getTablePrefix(),
            $this->coreConfiguration->getDb()->escape($type),
            $referenceId,
        );

        $result = $this->coreConfiguration->getDb()->query($query);
        $rows = $this->coreConfiguration->getDb()->fetchAll($result);
        return is_array($rows) ? $rows : [];
    }

    public function insert(Comment $comment): bool
    {
        $helpedValue = $comment->hasHelped();
        $helpedSql = $helpedValue === null ? 'NULL' : "'" . ($helpedValue ? 'y' : 'n') . "'";

        $sql = <<<SQL
            INSERT INTO
                %sfaqcomments (id_comment, id, type, usr, email, comment, datum, helped)
            VALUES
                (%d, %d, '%s', '%s', '%s', '%s', '%s', %s)
        SQL;

        $query = sprintf(
            $sql,
            Database::getTablePrefix(),
            $this->coreConfiguration->getDb()->nextId(Database::getTablePrefix() . 'faqcomments', 'id_comment'),
            $comment->getRecordId(),
            $this->coreConfiguration->getDb()->escape($comment->getType()),
            $this->coreConfiguration->getDb()->escape($comment->getUsername()),
            $this->coreConfiguration->getDb()->escape($comment->getEmail()),
            $this->coreConfiguration->getDb()->escape($comment->getComment()),
            $this->coreConfiguration->getDb()->escape($comment->getDate()),
            $helpedSql,
        );

        return (bool) $this->coreConfiguration->getDb()->query($query);
    }

    public function deleteByTypeAndId(string $type, int $commentId): bool
    {
        $sql = <<<SQL
            DELETE FROM
                %sfaqcomments
            WHERE
                type = '%s'
            AND
                id_comment = %d
        SQL;

        $query = sprintf(
            $sql,
            Database::getTablePrefix(),
            $this->coreConfiguration->getDb()->escape($type),
            $commentId,
        );

        return (bool) $this->coreConfiguration->getDb()->query($query);
    }

    /**
     * @return array<int, object>
     */
    public function countByTypeGroupedByRecordId(string $type = CommentType::FAQ): array
    {
        $sql = <<<SQL
            SELECT
                COUNT(id) AS anz,
                id
            FROM
                %sfaqcomments
            WHERE
                type = '%s'
            GROUP BY id
            ORDER BY id
        SQL;

        $query = sprintf($sql, Database::getTablePrefix(), $this->coreConfiguration->getDb()->escape($type));

        $result = $this->coreConfiguration->getDb()->query($query);
        $rows = $this->coreConfiguration->getDb()->fetchAll($result);
        return is_array($rows) ? $rows : [];
    }

    /**
     * @return array<int, object>
     */
    public function countByCategoryForFaq(): array
    {
        $sql = <<<SQL
            SELECT
                COUNT(fc.id) AS number,
                fcg.category_id AS category_id
            FROM
                %sfaqcomments fc
            LEFT JOIN
                %sfaqcategoryrelations fcg
            ON
                fc.id = fcg.record_id
            WHERE
                fc.type = '%s'
            GROUP BY fcg.category_id
            ORDER BY fcg.category_id
        SQL;

        $query = sprintf($sql, Database::getTablePrefix(), Database::getTablePrefix(), CommentType::FAQ);

        $result = $this->coreConfiguration->getDb()->query($query);
        $rows = $this->coreConfiguration->getDb()->fetchAll($result);
        return is_array($rows) ? $rows : [];
    }

    /**
     * @return array<int, object>
     */
    public function fetchAllWithCategories(string $type = CommentType::FAQ): array
    {
        $prefix = Database::getTablePrefix();
        $escapedType = $this->coreConfiguration->getDb()->escape($type);

        if ($type === CommentType::FAQ) {
            $query = sprintf(
                'SELECT fc.id_comment AS comment_id, fc.id AS record_id, fcg.category_id, fc.usr AS username, '
                . 'fc.email AS email, fc.comment AS comment, fc.datum AS comment_date FROM %sfaqcomments fc '
                . "LEFT JOIN %sfaqcategoryrelations fcg ON fc.id = fcg.record_id WHERE type = '%s'",
                $prefix,
                $prefix,
                $escapedType,
            );
        } else {
            $query = sprintf(
                'SELECT fc.id_comment AS comment_id, fc.id AS record_id, fc.usr AS username, fc.email AS email, '
                . "fc.comment AS comment, fc.datum AS comment_date FROM %sfaqcomments fc WHERE type = '%s'",
                $prefix,
                $escapedType,
            );
        }

        $result = $this->coreConfiguration->getDb()->query($query);
        $rows = $this->coreConfiguration->getDb()->fetchAll($result);
        return is_array($rows) ? $rows : [];
    }

    public function isCommentAllowed(int $recordId, string $recordLang, string $commentType = 'faq'): bool
    {
        $table = 'news' === $commentType ? 'faqnews' : 'faqdata';

        $sql = <<<SQL
            SELECT
                comment
            FROM
                %s%s
            WHERE
                id = %d
            AND
                lang = '%s'
        SQL;

        $query = sprintf(
            $sql,
            Database::getTablePrefix(),
            $table,
            $recordId,
            $this->coreConfiguration->getDb()->escape($recordLang),
        );

        $result = $this->coreConfiguration->getDb()->query($query);
        if ($row = $this->coreConfiguration->getDb()->fetchObject($result)) {
            return $row->comment === 'y';
        }

        return false;
    }
}
