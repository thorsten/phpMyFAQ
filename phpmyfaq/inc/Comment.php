<?php
/**
 * The main Comment class
 *
 * PHP Version 5.2.3
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
    private $_config;

    /**
     * Language strings
     *
     * @var string
     */
    private $pmf_lang;

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

        $this->_config  = $config;
        $this->pmf_lang = $PMF_LANG;
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
            SQLPREFIX,
            $id);

        $result = $this->_config->getDb()->query($query);
        if (($this->_config->getDb()->numRows($result) > 0) && ($row = $this->_config->getDb()->fetchObject($result))) {
            $item = array(
                'id'       => $row->id_comment,
                'recordId' => $row->id,
                'type'     => $row->type,
                'content'  => $row->comment,
                'date'     => $row->datum,
                'user'     => $row->usr,
                'email'    => $row->email);
        }

        return $item;
    }

    /**
     * Returns all user comments from a record by type
     *
     * @param  integer $id   record id
     * @param  integer $type record type: {faq|news}
     * @return string
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
            SQLPREFIX,
            $type,
            $id);

        $result = $this->_config->getDb()->query($query);
        if ($this->_config->getDb()->numRows($result) > 0) {
            while ($row = $this->_config->getDb()->fetchObject($result)) {
                $item = array(
                    'id'      => $row->id_comment,
                    'content' => $row->comment,
                    'date'    => $row->datum,
                    'user'    => $row->usr,
                    'email'   => $row->email);
                $comments[] = $item;
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
        $date     = new PMF_Date($this->_config);
        $mail     = new PMF_Mail($this->_config);

        $output = '';
        foreach ($comments as $item) {
            $output .= '<p class="comment">';
            $output .= '<img src="images/bubbles.gif" />';
            $output .= sprintf('<strong>%s<a href="mailto:%s">%s</a>:</strong><br />%s<br />%s</p>',
                $this->pmf_lang['msgCommentBy'],
                $mail->safeEmail($item['email']),
                $item['user'],
                nl2br($item['content']),
                $this->pmf_lang['newsCommentDate'] .
                    $date->format(PMF_Date::createIsoDate($item['date'], 'Y-m-d H:i', false))
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
            SQLPREFIX,
            $this->_config->getDb()->nextId(SQLPREFIX.'faqcomments', 'id_comment'),
            $commentData['record_id'],
            $commentData['type'],
            $commentData['username'],
            $commentData['usermail'],
            $commentData['comment'],
            $commentData['date'],
            $commentData['helped']);

        if (!$this->_config->getDb()->query($query)) {
            return false;
        }

        return true;
    }

    /**
     * Deletes a comment
     *
     * @param  integer $record_id  Record id
     * @param  integer $comment_id Comment id
     * @return boolean
     */
    public function deleteComment($record_id, $comment_id)
    {
        if (!is_int($record_id) && !is_int($comment_id)) {
            return false;
        }

        $query = sprintf("
            DELETE FROM
                %sfaqcomments
            WHERE
                id = %d
            AND
                id_comment = %d",
            SQLPREFIX,
            $record_id,
            $comment_id);

        if (!$this->_config->getDb()->query($query)) {
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
            SQLPREFIX,
            $type);

        $result = $this->_config->getDb()->query($query);
        if ($this->_config->getDb()->numRows($result) > 0) {
            while ($row = $this->_config->getDb()->fetchObject($result)) {
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
            SQLPREFIX,
            ($type == self::COMMENT_TYPE_FAQ) ? "LEFT JOIN
                ".SQLPREFIX."faqcategoryrelations fcg
            ON
                fc.id = fcg.record_id\n" : '',
            $type);
            
        $result = $this->_config->getDb()->query($query);
        if ($this->_config->getDb()->numRows($result) > 0) {
            while ($row = $this->_config->getDb()->fetchObject($result)) {
                $comments[] = array(
                    'comment_id'  => $row->comment_id,
                    'record_id'   => $row->record_id,
                    'category_id' => (isset($row->category_id) ? $row->category_id : null),
                    'content'     => $row->comment,
                    'date'        => $row->comment_date,
                    'username'    => $row->username,
                    'email'       => $row->email);
            }
        }

        return $comments;
    }
}