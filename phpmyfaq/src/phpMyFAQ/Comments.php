<?php

/**
 * The main Comment class.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2006-2022 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2006-07-23
 */

namespace phpMyFAQ;

use phpMyFAQ\Entity\CommentType;
use phpMyFAQ\Services\Gravatar;
use phpMyFAQ\Entity\Comment;

/**
 * Class Comments
 * @package phpMyFAQ
 */
class Comments
{
    /**
     * @var Configuration
     */
    private Configuration $config;

    /**
     * Language strings.
     *
     * @var string
     */
    private $pmfStr;

    /**
     * Constructor.
     *
     * @param Configuration $config
     */
    public function __construct(Configuration $config)
    {
        global $PMF_LANG;

        $this->config = $config;
        $this->pmfStr = $PMF_LANG;
    }

    /**
     * Returns all user comments (HTML formatted) from a record by type.
     *
     * @param int $id Comment ID
     * @param string $type Comment type: {faq|news}
     * @return string
     * @throws \Exception
     * @todo   Move this code to a helper class
     */
    public function getComments(int $id, string $type): string
    {
        $comments = $this->getCommentsData($id, $type);
        $date = new Date($this->config);
        $mail = new Mail($this->config);
        $gravatar = new Gravatar();

        $output = '';
        foreach ($comments as $item) {
            $output .= '<div class="row mt-2 mb-2">';
            $output .= '  <div class="col-sm-1">';
            $output .= '    <div class="thumbnail">';
            $output .= $gravatar->getImage($item->getEmail(), ['class' => 'img-thumbnail']);
            $output .= '   </div>';
            $output .= '  </div>';

            $output .= '  <div class="col-sm-11">';
            $output .= '    <div class="card">';
            $output .= '     <div class="card-header card-header-comments">';
            $output .= sprintf(
                '<strong><a href="mailto:%s">%s</a></strong>',
                $mail->safeEmail($item->getEmail()),
                $item->getUsername()
            );
            $output .= sprintf(' <span class="text-muted">(%s)</span>', $date->format($item->getDate()));
            $output .= '     </div>';
            $output .= sprintf(
                '<div class="card-body">%s</div>',
                $this->showShortComment($item->getId(), $item->getComment())
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

        $result = $this->config->getDb()->query($query);
        if ($this->config->getDb()->numRows($result) > 0) {
            while ($row = $this->config->getDb()->fetchObject($result)) {
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
     *
     * @param int $id
     * @param string $comment
     * @return string
     */
    private function showShortComment(int $id, string $comment): string
    {
        $words = explode(' ', nl2br($comment));
        $numWords = 0;

        $comment = '';
        foreach ($words as $word) {
            $comment .= $word . ' ';
            if (15 === $numWords) {
                $comment .= '<span class="comment-dots-' . $id . '">&hellip; </span>' .
                    '<a href="#" data-comment-id="' . $id . '" class="pmf-comments-show-more comment-show-more-' . $id .
                    '">' . $this->pmfStr['msgShowMore'] . '</a>' .
                    '<span class="comment-more-' . $id . ' d-none">';
            }
            ++$numWords;
        }

        // Convert URLs to HTML anchors
        return Utils::parseUrl($comment) . '</span>';
    }

    /**
     * Adds a new comment.
     * @param Comment $comment
     * @return bool
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
            $this->config->getDb()->nextId(Database::getTablePrefix() . 'faqcomments', 'id_comment'),
            $comment->getRecordId(),
            $comment->getType(),
            $this->config->getDb()->escape($comment->getUsername()),
            $this->config->getDb()->escape($comment->getEmail()),
            $this->config->getDb()->escape($comment->getComment()),
            $comment->getDate(),
            $comment->hasHelped()
        );

        if (!$this->config->getDb()->query($query)) {
            return false;
        }

        return true;
    }

    /**
     * Deletes a comment.
     *
     * @param int $recordId Record id
     * @param int $commentId Comment id
     *
     * @return bool
     */
    public function deleteComment(int $recordId, int $commentId): bool
    {
        $query = sprintf(
            '
            DELETE FROM
                %sfaqcomments
            WHERE
                id = %d
            AND
                id_comment = %d',
            Database::getTablePrefix(),
            $recordId,
            $commentId
        );

        if (!$this->config->getDb()->query($query)) {
            return false;
        }

        return true;
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

        $result = $this->config->getDb()->query($query);
        if ($this->config->getDb()->numRows($result) > 0) {
            while ($row = $this->config->getDb()->fetchObject($result)) {
                $num[$row->id] = $row->anz;
            }
        }

        return $num;
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

        $result = $this->config->getDb()->query($query);
        if ($this->config->getDb()->numRows($result) > 0) {
            while ($row = $this->config->getDb()->fetchObject($result)) {
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
}
