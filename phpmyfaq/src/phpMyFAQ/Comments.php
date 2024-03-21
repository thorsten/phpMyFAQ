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
 * @copyright 2006-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2006-07-23
 */

namespace phpMyFAQ;

use Exception;
use phpMyFAQ\Entity\CommentType;
use phpMyFAQ\Services\Gravatar;
use phpMyFAQ\Entity\Comment;

/**
 * Class Comments
 * @package phpMyFAQ
 */
readonly class Comments
{
    /**
     * Constructor.
     */
    public function __construct(private Configuration $configuration)
    {
    }

    /**
     * Returns all user comments (HTML formatted) from a record by type.
     *
     * @param int $id Comment ID
     * @param string $type Comment type: {faq|news}
     * @throws Exception
     * @todo   Move this code to a helper class
     */
    public function getComments(int $id, string $type): string
    {
        $comments = $this->getCommentsData($id, $type);
        $date = new Date($this->configuration);
        $mail = new Mail($this->configuration);
        $gravatar = new Gravatar();

        $output = '';
        foreach ($comments as $comment) {
            $output .= '<div class="row mt-2 mb-2">';
            $output .= '  <div class="col-sm-1">';
            $output .= '    <div class="thumbnail">';
            $output .= $gravatar->getImage($comment->getEmail(), ['class' => 'img-thumbnail']);
            $output .= '   </div>';
            $output .= '  </div>';

            $output .= '  <div class="col-sm-11">';
            $output .= '    <div class="card">';
            $output .= '     <div class="card-header card-header-comments">';
            $output .= sprintf(
                '<strong><a href="mailto:%s">%s</a></strong>',
                $mail->safeEmail($comment->getEmail()),
                Strings::htmlentities($comment->getUsername())
            );
            $output .= sprintf(' <span class="text-muted">(%s)</span>', $date->format($comment->getDate()));
            $output .= '     </div>';
            $output .= sprintf(
                '<div class="card-body">%s</div>',
                $this->showShortComment($comment->getId(), $comment->getComment())
            );
            $output .= '   </div>';
            $output .= '  </div>';
            $output .= '</div>';
        }

        return $output;
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

        $query = sprintf(
            "
            SELECT
                id_comment, id, usr, email, comment, datum
            FROM
                %sfaqcomments
            WHERE
                type = '%s'
            AND 
                id = %d",
            Database::getTablePrefix(),
            $type,
            $referenceId
        );

        $result = $this->configuration->getDb()->query($query);
        if ($this->configuration->getDb()->numRows($result) > 0) {
            while ($row = $this->configuration->getDb()->fetchObject($result)) {
                $comment = new Comment();
                $comment
                    ->setId($row->id_comment)
                    ->setRecordId($row->id)
                    ->setComment($row->comment)
                    ->setDate(Date::createIsoDate($row->datum, DATE_ISO8601, false))
                    ->setUsername($row->usr)
                    ->setEmail($row->email)
                    ->setType($type);
                $comments[] = $comment;
            }
        }

        return $comments;
    }

    /**
     * Adds some fancy HTML if a comment is too long.
     * @todo Move this code to a helper class
     */
    private function showShortComment(int $id, string $comment): string
    {
        $words = explode(' ', nl2br($comment));
        $numWords = 0;

        $comment = '';
        foreach ($words as $word) {
            $comment .= Strings::htmlentities($word . ' ');
            if (15 === $numWords) {
                $comment .= '<span class="comment-dots-' . $id . '">&hellip; </span>' .
                    '<a href="#" data-comment-id="' . $id . '" class="pmf-comments-show-more comment-show-more-' . $id .
                    '">' . Translation::get('msgShowMore') . '</a>' .
                    '<span class="comment-more-' . $id . ' d-none">';
            }

            ++$numWords;
        }

        // Convert URLs to HTML anchors
        return Utils::parseUrl($comment) . '</span>';
    }

    /**
     * Adds a new comment.
     */
    public function addComment(Comment $comment): bool
    {
        $query = sprintf(
            "
            INSERT INTO
                %sfaqcomments
            VALUES
                (%d, %d, '%s', '%s', '%s', '%s', %d, '%s')",
            Database::getTablePrefix(),
            $this->configuration->getDb()->nextId(Database::getTablePrefix() . 'faqcomments', 'id_comment'),
            $comment->getRecordId(),
            $comment->getType(),
            $this->configuration->getDb()->escape($comment->getUsername()),
            $this->configuration->getDb()->escape($comment->getEmail()),
            $this->configuration->getDb()->escape($comment->getComment()),
            $comment->getDate(),
            $comment->hasHelped()
        );
        return (bool) $this->configuration->getDb()->query($query);
    }

    /**
     * Deletes a comment.
     *
     * @param int    $commentId Comment id
     */
    public function delete(string $type, int $commentId): bool
    {
        $query = sprintf(
            "
            DELETE FROM
                %sfaqcomments
            WHERE
                type = '%s'
            AND
                id_comment = %d",
            Database::getTablePrefix(),
            $type,
            $commentId
        );
        return (bool) $this->configuration->getDb()->query($query);
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

        $query = sprintf(
            "
            SELECT
                COUNT(id) AS anz,
                id
            FROM
                %sfaqcomments
            WHERE
                type = '%s'
            GROUP BY id
            ORDER BY id",
            Database::getTablePrefix(),
            $type
        );

        $result = $this->configuration->getDb()->query($query);
        if ($this->configuration->getDb()->numRows($result) > 0) {
            while ($row = $this->configuration->getDb()->fetchObject($result)) {
                $num[$row->id] = $row->anz;
            }
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

        $query = sprintf(
            "
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
            ORDER BY fcg.category_id",
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            CommentType::FAQ
        );

        $result = $this->configuration->getDb()->query($query);
        if ($this->configuration->getDb()->numRows($result) > 0) {
            while ($row = $this->configuration->getDb()->fetchObject($result)) {
                $numbers[$row->category_id] = (int)$row->number;
            }
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

        $query = sprintf(
            "
            SELECT
                fc.id_comment AS comment_id,
                fc.id AS record_id,
                %s
                fc.usr AS username,
                fc.email AS email,
                fc.comment AS comment,
                fc.datum AS comment_date
            FROM
                %sfaqcomments fc
            %s
            WHERE
                type = '%s'",
            ($type == CommentType::FAQ) ? "fcg.category_id,\n" : '',
            Database::getTablePrefix(),
            ($type == CommentType::FAQ) ? 'LEFT JOIN
                ' . Database::getTablePrefix() . "faqcategoryrelations fcg
            ON
                fc.id = fcg.record_id\n" : '',
            $type
        );

        $result = $this->configuration->getDb()->query($query);
        if ($this->configuration->getDb()->numRows($result) > 0) {
            while ($row = $this->configuration->getDb()->fetchObject($result)) {
                $comment = new Comment();
                $comment
                    ->setId($row->comment_id)
                    ->setRecordId($row->record_id)
                    ->setType($type)
                    ->setComment($row->comment)
                    ->setDate($row->comment_date)
                    ->setUsername($row->username)
                    ->setEmail($row->email);

                if (isset($row->category_id)) {
                    $comment->setCategoryId($row->category_id);
                }

                $comments[] = $comment;
            }
        }

        return $comments;
    }
    /**
     * Checks, if comments are disabled for the FAQ record.
     *
     * @param int    $recordId ID of FAQ or news entry
     * @param string $recordLang  Language
     * @param string $commentType Type of comment: faq or news
     * @return bool false, if comments are disabled
     */
    public function isCommentAllowed(int $recordId, string $recordLang, string $commentType = 'faq'): bool
    {
        $table = 'news' === $commentType ? 'faqnews' : 'faqdata';

        $query = sprintf(
            "
            SELECT
                comment
            FROM
                %s%s
            WHERE
                id = %d
            AND
                lang = '%s'",
            Database::getTablePrefix(),
            $table,
            $recordId,
            $this->configuration->getDb()->escape($recordLang)
        );

        $result = $this->configuration->getDb()->query($query);

        if ($row = $this->configuration->getDb()->fetchObject($result)) {
            return $row->comment === 'y';
        }

        return false;
    }
}
