<?php
/**
 * The main Comment class
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @since     2006-07-23
 * @copyright 2006-2009 phpMyFAQ Team
 * @version   SVN: $Id$
 *
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 */

// {{{ Classes
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
     * DB handle
     *
     * @var object
     */
    private $db;

    /**
     * Language
     *
     * @var string
     */
    private $language;

    /**
     * Language strings
     *
     * @var string
     */
    private $pmf_lang;

    /**
     * Constructor
     *
     * @param   object
     * @param   string
     */
    public function __construct($db, $language)
    {
        global $PMF_LANG;

        $this->db       = $db;
        $this->language = $language;
        $this->pmf_lang = $PMF_LANG;
    }

    //
    // PUBLIC METHODS
    //

    /**
     * Returns a user comment
     *
     * @param   integer     comment id
     * @return  string
     * @access  public
     * @since   2006-07-13
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     * @author  Matteo Scaramuccia <matteo@scaramuccia.com>
     */
    public function getCommentDataById($id)
    {
        $item = array();

        $query = sprintf(
                    "SELECT
                        id_comment, id, type, usr, email, comment, datum
                    FROM
                        %sfaqcomments
                    WHERE
                        id_comment = %d",
                    SQLPREFIX,
                    $id);

        $result = $this->db->query($query);
        if (($this->db->num_rows($result) > 0) && ($row = $this->db->fetch_object($result))) {
            $item = array(
                    'id'        => $row->id_comment,
                    'recordId'  => $row->id,
                    'type'      => $row->type,
                    'content'   => $row->comment,
                    'date'      => $row->datum,
                    'user'      => $row->usr,
                    'email'     => $row->email);
        }

        return $item;
    }

    /**
     * Returns all user comments from a record by type
     *
     * @param   integer     record id
     * @param   integer     record type: {faq|news}
     * @return  string
     * @access  public
     * @since   2002-08-29
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     * @author  Matteo Scaramuccia <matteo@scaramuccia.com>
     */
    public function getCommentsData($id, $type)
    {
        $comments = array();

        $query = sprintf(
                    "SELECT
                        id_comment, usr, email, comment, datum
                    FROM
                        %sfaqcomments
                    WHERE
                        type = '%s'
                        AND id = %d",
                    SQLPREFIX,
                    $type,
                    $id);


        $result = $this->db->query($query);
        if ($this->db->num_rows($result) > 0) {
            while ($row = $this->db->fetch_object($result)) {
                $item = array(
                        'id'        => $row->id_comment,
                        'content'   => $row->comment,
                        'date'      => $row->datum,
                        'user'      => $row->usr,
                        'email'     => $row->email
                        );
                $comments[] = $item;
            }
        }

        return $comments;
    }

    /**
     * Returns all user comments (HTML formatted) from a record by type
     *
     * @param   integer     record id
     * @param   integer     record type: {faq|news}
     * @return  string
     * @access  public
     * @since   2002-08-29
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    public function getComments($id, $type)
    {
        $comments = $this->getCommentsData($id, $type);

        $output = '';
        foreach ($comments as $item) {
            $output .= '<p class="comment">';
            $output .= sprintf('<strong>%s<a href="mailto:%s">%s</a>:</strong><br />%s<br />%s</p>',
                                $this->pmf_lang['msgCommentBy'],
                                safeEmail($item['email']),
                                $item['user'],
                                $item['content'],
                                $this->pmf_lang['newsCommentDate'].makeCommentDate($item['date']));
        }

        return $output;
    }

    /**
     * Adds a comment
     *
     * @param   array       $commentData
     * @return  boolean
     * @access  public
     * @since   2006-06-18
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    function addComment($commentData)
    {
        if (!is_array($commentData)) {
            return false;
        }

        $query = sprintf(
                    "INSERT INTO
                        %sfaqcomments
                    VALUES
                        (%d, %d, '%s', '%s', '%s', '%s', %d, '%s')",
                    SQLPREFIX,
                    $this->db->nextID(SQLPREFIX.'faqcomments', 'id_comment'),
                    $commentData['record_id'],
                    $commentData['type'],
                    $commentData['username'],
                    $commentData['usermail'],
                    $commentData['comment'],
                    $commentData['date'],
                    $commentData['helped']);

        if (!$this->db->query($query)) {
            return false;
        }

        return true;
    }

    /**
     * Deletes a comment
     *
     * @param   integer     $record_id
     * @param   integer     $comment_id
     * @return  boolean
     * @access  public
     * @since   2006-06-18
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    public function deleteComment($record_id, $comment_id)
    {
        if (!is_int($record_id) && !is_int($comment_id)) {
            return false;
        }

        $query = sprintf(
            'DELETE FROM
                %sfaqcomments
            WHERE
                id = %d
            AND
            id_comment = %d',
            SQLPREFIX,
            $record_id,
            $comment_id);

        if (!$this->db->query($query)) {
            return false;
        }

        return true;
    }

    /**
     * Returns the number of comments of each FAQ record as an array
     *
     * @param  string $type Type of comment: faq or news
     * @return array
     * @since  2007-02-11
     * @author Thorsten Rinne <thorsten@rinne.info>
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

        $result = $this->db->query($query);
        if ($this->db->num_rows($result) > 0) {
            while ($row = $this->db->fetch_object($result)) {
                $num[$row->id] = $row->anz;
            }
        }

        return $num;
    }

    /**
     * Returns all comments with their categories
     *
     * @param   $type
     * @return  array
     * @access  public
     * @since   2007-03-04
     * @author  Thorsten Rinne <thorsten@rinne.info>
     */
    public function getAllComments($type = self::COMMENT_TYPE_FAQ)
    {
        $comments = array();

        $query = sprintf("
            SELECT
                fc.id_comment AS comment_id,
                fc.id AS record_id,
                %s
                fc.usr AS user,
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

        $result = $this->db->query($query);
        if ($this->db->num_rows($result) > 0) {
            while ($row = $this->db->fetch_object($result)) {
                $comments[] = array(
                    'comment_id'  => $row->comment_id,
                    'record_id'   => $row->record_id,
                    'category_id' => (isset($row->category_id) ? $row->category_id : null),
                    'content'     => $row->comment,
                    'date'        => $row->comment_date,
                    'user'        => $row->user,
                    'email'       => $row->email);
            }
        }

        return $comments;
    }
}