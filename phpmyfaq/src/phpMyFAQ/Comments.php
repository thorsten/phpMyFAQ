<?php

/**
 * The main Comment class.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2006-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2006-07-23
 */

declare(strict_types=1);

namespace phpMyFAQ;

use DateTimeInterface;
use phpMyFAQ\Comment\CommentsRepository;
use phpMyFAQ\Entity\Comment;
use phpMyFAQ\Entity\CommentType;

/**
 * Class Comments
 * @package phpMyFAQ
 */
class Comments
{
    private CommentsRepository $commentsRepository;

    /**
     * Constructor.
     */
    public function __construct(Configuration $configuration, ?CommentsRepository $commentsRepository = null)
    {
        $this->commentsRepository = $commentsRepository ?? new CommentsRepository($configuration);
    }

    /**
     * Returns all user comments from a record by type.
     *
     * @param int $referenceId record id
     * @param string $type record type: {faq|news}
     *
     * @return Comment[]
     */
    public function getCommentsData(int $referenceId, string $type): array
    {
        $comments = [];

        $rows = $this->commentsRepository->fetchByReferenceIdAndType($referenceId, $type);
        foreach ($rows as $row) {
            $comment = new Comment();
            $comment
                ->setId((int) $row->id_comment)
                ->setRecordId((int) $row->id)
                ->setComment($row->comment)
                ->setDate(Date::createIsoDateFromUnixTimestamp($row->datum, DateTimeInterface::ATOM))
                ->setUsername($row->usr)
                ->setEmail($row->email)
                ->setType($type);
            $comments[] = $comment;
        }

        return $comments;
    }

    /**
     * Returns paginated user comments from a record by type.
     *
     * @param int $referenceId Record ID
     * @param string $type Record type: {faq|news}
     * @param int $limit Items per page
     * @param int $offset Offset for pagination
     * @param string $sortField Field to sort by
     * @param string $sortOrder Sort order (ASC or DESC)
     *
     * @return Comment[]
     */
    public function getCommentsDataPaginated(
        int $referenceId,
        string $type,
        int $limit,
        int $offset,
        string $sortField = 'id_comment',
        string $sortOrder = 'ASC',
    ): array {
        $comments = [];

        $rows = $this->commentsRepository->fetchPaginated($referenceId, $type, $limit, $offset, $sortField, $sortOrder);

        foreach ($rows as $row) {
            $comment = new Comment();
            $comment
                ->setId((int) $row->id_comment)
                ->setRecordId((int) $row->id)
                ->setComment($row->comment)
                ->setDate(Date::createIsoDateFromUnixTimestamp($row->datum, DateTimeInterface::ATOM))
                ->setUsername($row->usr)
                ->setEmail($row->email)
                ->setType($type);
            $comments[] = $comment;
        }

        return $comments;
    }

    /**
     * Count total comments for a reference ID and type.
     *
     * @param int $referenceId Record ID
     * @param string $type Record type: {faq|news}
     * @return int
     */
    public function countComments(int $referenceId, string $type): int
    {
        return $this->commentsRepository->countByReferenceIdAndType($referenceId, $type);
    }

    /**
     * Adds a new comment.
     */
    public function create(Comment $comment): bool
    {
        return $this->commentsRepository->insert($comment);
    }

    /**
     * Deletes a comment.
     *
     * @param int    $commentId Comment id
     */
    public function delete(string $type, int $commentId): bool
    {
        return $this->commentsRepository->deleteByTypeAndId($type, $commentId);
    }

    /**
     * Returns the number of comments of each FAQ record as an array.
     *
     * @param string $type Type of comment: faq or news
     * @return string[]
     */
    public function getNumberOfComments(string $type = CommentType::FAQ): array
    {
        $num = [];

        $rows = $this->commentsRepository->countByTypeGroupedByRecordId($type);
        foreach ($rows as $row) {
            $num[$row->id] = $row->anz;
        }

        return $num;
    }

    /**
     * Returns the number of comments of each category as an array.
     * @return array<int>
     */
    public function getNumberOfCommentsByCategory(): array
    {
        $numbers = [];

        $rows = $this->commentsRepository->countByCategoryForFaq();
        foreach ($rows as $row) {
            $numbers[$row->category_id] = (int) $row->number;
        }

        return $numbers;
    }

    /**
     * Returns all comments with their categories.
     *
     * @param string $type Type of comment: faq or news
     * @return Comment[]
     */
    public function getAllComments(string $type = CommentType::FAQ): array
    {
        $comments = [];

        $rows = $this->commentsRepository->fetchAllWithCategories($type);
        foreach ($rows as $row) {
            $comment = new Comment();
            $comment
                ->setId((int) $row->comment_id)
                ->setRecordId((int) $row->record_id)
                ->setType($type)
                ->setComment($row->comment)
                ->setDate($row->comment_date)
                ->setUsername($row->username)
                ->setEmail($row->email);

            if (isset($row->category_id)) {
                $comment->setCategoryId((int) $row->category_id);
            }

            $comments[] = $comment;
        }

        return $comments;
    }

    /**
     * Checks if comments are disabled for the FAQ record.
     *
     * @param int    $recordId ID of FAQ or news entry
     * @param string $recordLang  Language
     * @param string $commentType Type of comment: faq or news
     * @return bool false, if comments are disabled
     */
    public function isCommentAllowed(int $recordId, string $recordLang, string $commentType = 'faq'): bool
    {
        return $this->commentsRepository->isCommentAllowed($recordId, $recordLang, $commentType);
    }
}
