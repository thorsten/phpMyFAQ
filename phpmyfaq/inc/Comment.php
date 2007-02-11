<?php
/**
 * $Id: Comment.php,v 1.8 2007-02-11 20:38:08 thorstenr Exp $
 *
 * The main Comment class
 *
 * @author      Thorsten Rinne <thorsten@phpmyfaq.de>
 * @since       2006-07-23
 * @copyright   (c) 2006-2007 phpMyFAQ Team
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

// {{{ Constants
/**#@+
  * Comment type
  */
define('PMF_COMMENT_TYPE_FAQ', 'faq');
define('PMF_COMMENT_TYPE_NEWS', 'news');
/**#@-*/
// }}}

// {{{ Classes
class PMF_Comment
{
    /**
     * DB handle
     *
     * @var object
     */
    var $db;

    /**
     * Language
     *
     * @var string
     */
    var $language;

    /**
     * Language strings
     *
     * @var string
     */
    var $pmf_lang;

    /**
     * Constructor
     *
     * @param   object
     * @param   string
     */
    function PMF_Comment(&$db, $language)
    {
        global $PMF_LANG;

        $this->db = &$db;
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
    function getCommentDataById($id)
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
                    $id
                );

        $output = '';
        $result = $this->db->query($query);
        if (($this->db->num_rows($result) > 0) && ($row = $this->db->fetch_object($result))) {
            $item = array(
                    'id'        => $row->id_comment,
                    'recordId'  => $row->id,
                    'type'      => $row->type,
                    'content'   => $row->comment,
                    'date'      => $row->datum,
                    'user'      => $row->usr,
                    'email'     => $row->email
                    );
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
    function getCommentsData($id, $type)
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
                    $id
                );


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
    function getComments($id, $type)
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
                                $this->pmf_lang['newsCommentDate'].makeCommentDate($item['date'])
                        );
        }

        return $output;
    }

    /**
     * addComment()
     *
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
                    $commentData['helped']
                );

        if (!$this->db->query($query)) {
            return false;
        }
        
        return true;
    }

    /**
     * deleteComment()
     *
     * Deletes a comment
     *
     * @param   integer     $record_id
     * @param   integer     $comment_id
     * @return  boolean
     * @access  public
     * @since   2006-06-18
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    function deleteComment($record_id, $comment_id)
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
     * @return  array
     * @access  public
     * @since   2007-02-11
     * @author  Thorsten Rinne <thorsten@rinne.info>
     */
    function getNumberOfComments()
    {
        $num = array();
        
        $query = sprintf("
            SELECT
                COUNT(id) AS anz,
                id 
            FROM 
                %sfaqcomments
            GROUP BY id
            ORDER BY id",
            SQLPREFIX);
        
        $result = $this->db->query($query);
        if ($this->db->num_rows($result) > 0) {
            while ($row = $this->db->fetch_object($result)) {
                $num[$row->id] = $row->anz;
            }
        }
        
        return $num;
    }
}
// }}}
