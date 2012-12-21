<?php
/**
 * The main Comment class
 *
 * PHP Version 5.3
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   PMF_Comment
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2006-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2006-07-23
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Comment
 *
 * @category  phpMyFAQ
 * @package   PMF_Comment
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2006-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2006-07-23
 */
class PMF_Comment
{
    /**
     * FAQ type
     *
     * @const string
     */
    const COMMENT_TYPE_FAQ  = 'faq';

    /**
     * News type
     *
     * @const string
     */
    const COMMENT_TYPE_NEWS ='news';

    /**
     * @var PMF_Configuration
     */
    private $config;

    /**
     * Language strings
     *
     * @var string
     */
    private $pmfStr;

    /**
     * Constructor
     *
     * @param PMF_Configuration $config
     *
     * @return PMF_Comment
     */
    public function __construct(PMF_Configuration $config)
    {
        global $PMF_LANG;

        $this->config = $config;
        $this->pmfStr = $PMF_LANG;
    }

    //
    // PUBLIC METHODS
    //

    /**
     * Returns a user comment
     *
     * @param  integer $id comment id
     * @return string
     */
    public function getCommentDataById($id)
    {
        $item = array();

        $query = sprintf("
            SELECT
                id_comment, id, type, usr, email, comment, datum
            FROM
                %sfaqcomments
            WHERE
                id_comment = %d",
            PMF_Db::getTablePrefix(),
            $id);

        $result = $this->config->getDb()->query($query);
        if (($this->config->getDb()->numRows($result) > 0) && ($row = $this->config->getDb()->fetchObject($result))) {
            $item = array(
                'id'       => $row->id_comment,
                'recordId' => $row->id,
                'type'     => $row->type,
                'content'  => $row->comment,
                'date'     => $row->datum,
                'user'     => $row->usr,
                'email'    => $row->email
            );
        }

        return $item;
    }

    /**
     * Returns all user comments from a record by type
     *
     * @param integer $id   record id
     * @param integer $type record type: {faq|news}
     *
     * @return array
     */
    public function getCommentsData($id, $type)
    {
        $comments = array();

        $query = sprintf("
            SELECT
                id_comment, usr, email, comment, datum
            FROM
                %sfaqcomments
            WHERE
                type = '%s'
            AND 
                id = %d",
            PMF_Db::getTablePrefix(),
            $type,
            $id);

        $result = $this->config->getDb()->query($query);
        if ($this->config->getDb()->numRows($result) > 0) {
            while ($row = $this->config->getDb()->fetchObject($result)) {
                $comments[] = array(
                    'id'      => $row->id_comment,
                    'content' => $row->comment,
                    'date'    => $row->datum,
                    'user'    => $row->usr,
                    'email'   => $row->email
                );
            }
        }

        return $comments;
    }

    /**
     * Returns all user comments (HTML formatted) from a record by type
     *
     * @todo Move this code to a helper class
     *
     * @param integer $id   Comment ID
     * @param integer $type Comment type: {faq|news}
     *
     * @return string
     */
    public function getComments($id, $type)
    {
        $comments = $this->getCommentsData($id, $type);
        $date     = new PMF_Date($this->config);
        $mail     = new PMF_Mail($this->config);

        $output = '';
        foreach ($comments as $item) {
            $output .= '<p class="comment">';
            $output .= '<img src="assets/img/bubbles.gif" />';
            $output .= sprintf(
                '<strong>%s<a href="mailto:%s">%s</a>:</strong><br />%s<br />%s</p>',
                $this->pmfStr['msgCommentBy'],
                $mail->safeEmail($item['email']),
                $item['user'],
                $this->showShortComment($id, $item['content']),
                $this->pmfStr['newsCommentDate'] .
                    $date->format(
                        PMF_Date::createIsoDate($item['date'], 'Y-m-d H:i', false)
                    )
            );
        }

        return $output;
    }

    /**
     * Adds a comment
     *
     * @param  array $commentData Array with comment dara
     * @return boolean
     */
    function addComment(Array $commentData)
    {
        $query = sprintf("
            INSERT INTO
                %sfaqcomments
            VALUES
                (%d, %d, '%s', '%s', '%s', '%s', %d, '%s')",
            PMF_Db::getTablePrefix(),
            $this->config->getDb()->nextId(PMF_Db::getTablePrefix().'faqcomments', 'id_comment'),
            $commentData['record_id'],
            $commentData['type'],
            $commentData['username'],
            $commentData['usermail'],
            $commentData['comment'],
            $commentData['date'],
            $commentData['helped']
        );

    if (!$this->config->getDb()->query($query)) {
            return false;
        }

        return true;
    }

    /**
     * Deletes a comment
     *
     * @param  integer $recordId  Record id
     * @param  integer $commentId Comment id
     * @return boolean
     */
    public function deleteComment($recordId, $commentId)
    {
        if (!is_int($recordId) && !is_int($commentId)) {
            return false;
        }

        $query = sprintf("
            DELETE FROM
                %sfaqcomments
            WHERE
                id = %d
            AND
                id_comment = %d",
            PMF_Db::getTablePrefix(),
            $recordId,
            $commentId
        );

        if (!$this->config->getDb()->query($query)) {
            return false;
        }

        return true;
    }

    /**
     * Returns the number of comments of each FAQ record as an array
     *
     * @param  string $type Type of comment: faq or news
     * @return array
     */
    public function getNumberOfComments($type = self::COMMENT_TYPE_FAQ)
    {
        $num = array();

        $query = sprintf("
            SELECT
                COUNT(id) AS anz,
                id
            FROM
                %sfaqcomments
            WHERE
                type = '%s'
            GROUP BY id
            ORDER BY id",
            PMF_Db::getTablePrefix(),
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
     * Returns all comments with their categories
     *
     * @param  string $type Type of comment: faq or news
     * @return array
     */
    public function getAllComments($type = self::COMMENT_TYPE_FAQ)
    {
        $comments = array();

        $query = sprintf("
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
            ($type == self::COMMENT_TYPE_FAQ) ? "fcg.category_id,\n" : '',
            PMF_Db::getTablePrefix(),
            ($type == self::COMMENT_TYPE_FAQ) ? "LEFT JOIN
                ".PMF_Db::getTablePrefix()."faqcategoryrelations fcg
            ON
                fc.id = fcg.record_id\n" : '',
            $type
        );
            
        $result = $this->config->getDb()->query($query);
        if ($this->config->getDb()->numRows($result) > 0) {
            while ($row = $this->config->getDb()->fetchObject($result)) {
                $comments[] = array(
                    'comment_id'  => $row->comment_id,
                    'record_id'   => $row->record_id,
                    'category_id' => (isset($row->category_id) ? $row->category_id : null),
                    'content'     => $row->comment,
                    'date'        => $row->comment_date,
                    'username'    => $row->username,
                    'email'       => $row->email
                );
            }
        }

        return $comments;
    }

    /**
     * Adds some fancy HTML if a comment is too long
     *
     * @param integer $id
     * @param string  $comment
     *
     * @return string
     */
    public function showShortComment($id, $comment)
    {
        $words    = explode(' ', nl2br($comment));
        $numWords = 0;

        $comment = '';
        foreach ($words as $word) {
            $comment .= $word . ' ';
            if (15 === $numWords) {
            $comment .= '<span class="comment-dots-' . $id . '">... </span>' .
                        '<a onclick="showLongComment(' . $id . ')" class="comment-show-more-' . $id .
                        ' pointer">' . $this->pmfStr['msgShowMore'] . '</a>' .
                        '<span class="comment-more-' . $id . ' hide">';
            }
            $numWords++;
        }

        return $comment . '</span>';
    }
}