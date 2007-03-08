<?php
/**
 * $Id: News.php,v 1.15 2007-03-08 20:03:20 thorstenr Exp $
 *
 * The News class for phpMyFAQ news
 *
 * @author      Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author      Matteo Scaramuccia <matteo@scaramuccia.com>
 * @package     phpMyFAQ
 * @since       2006-06-25
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

// {{{ Includes
/**
 * This include is needed for manipulating PMF_Comment objects
 */
require_once('Comment.php');
/**
 * This include is needed for accessing to mod_rewrite support configuration value
 */
require_once('Link.php');
// }}}

// {{{ Classes
class PMF_News
{
    /**
    * DB handle
    *
    * @var  object
    */
    var $db;

    /**
    * Language
    *
    * @var  string
    */
    var $language;

    /**
    * Language strings
    *
    * @var  string
    */
    var $pmf_lang;

    /**
    * Constructor
    *
    */
    function PMF_News(&$db, $language)
    {
        global $PMF_LANG;

        $this->db = & $db;
        $this->language = $language;
        $this->pmf_lang = $PMF_LANG;
    }

    /**
     * getLatestData()
     *
     * Return the latest news data
     *
     * @param   boolean $showArchive
     * @param   boolean $active
     * @param   boolean $forceConfLimit
     * @return  string
     * @access  public
     * @since   2002-08-23
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     * @author  Matteo Scaramuccia <matteo@scaramuccia.com>
     */
    function getLatestData($showArchive = false, $active = true, $forceConfLimit = false)
    {
        global $PMF_CONF;

        $news = array();
        $counter = 0;
        $now = date('YmdHis');

        $query = sprintf(
            "SELECT
                id, datum, lang,
                header, artikel,
                author_name, author_email,
                date_start, date_end,
                active, comment,
                link, linktitel, target
            FROM
                %sfaqnews
            WHERE
                    date_start <= '%s'
                AND date_end   >= '%s'
                %s
            ORDER BY
                datum DESC",
            SQLPREFIX,
            $now,
            $now,
            $active ? "AND active = 'y'" : ''
            );
        $result = $this->db->query($query);

        if ($PMF_CONF['numNewsArticles'] > 0 && $this->db->num_rows($result) > 0) {
            while (    ($row = $this->db->fetch_object($result))
                   ) {
                $counter++;
                if (   ($showArchive  && ($counter > $PMF_CONF['numNewsArticles']))
                    || ((!$showArchive) && (!$forceConfLimit) && ($counter <= $PMF_CONF['numNewsArticles']))
                    || ((!$showArchive) && $forceConfLimit)
                   ) {
                    $item = array(
                        'id'            => $row->id,
                        'date'          => $row->datum,
                        'lang'          => $row->lang,
                        'header'        => $row->header,
                        'content'       => $row->artikel,
                        'authorName'    => $row->author_name,
                        'authorEmail'   => $row->author_email,
                        'dateStart'     => $row->date_start,
                        'dateEnd'       => $row->date_end,
                        'active'        => ('y' == $row->active),
                        'allowComments' => ('y' == $row->comment),
                        'link'          => $row->link,
                        'linkTitle'     => $row->linktitel,
                        'target'        => $row->target
                        );
                    $news[] = $item;
                }
            }
        }

        return $news;
    }

    /**
     * getNews()
     *
     * Function for generating the HTML fro the current news
     *
     * @param   boolean $showArchive
     * @param   boolean $active
     * @return  string
     * @access  public
     * @since   2002-08-23
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     * @author  Matteo Scaramuccia <matteo@scaramuccia.com>
     */
    function getNews($showArchive = false, $active = true)
    {
        $output = '';

        $news = $this->getLatestData($showArchive, $active);

        foreach ($news as $item) {
            $oLink = new PMF_Link(PMF_Link::getSystemRelativeUri().'?action=news&amp;newsid='.$item['id'].'&amp;newslang='.$item['lang']);;
            if (isset($item['header'])) {
                $oLink->itemTitle =$item['header'];
            }
            $output .= sprintf('<h3><a name="news_%d" href="%s">%s <img id="goNews" src="images/more.gif" width="11" height="11" alt="%s" /></a></h3><div class="block">%s',
                            $item['id'],
                            $oLink->toString(),
                            $item['header'],
                            $item['header'],
                            $item['content']
                        );
            if ('' != $item['link']) {
                $output .= sprintf('<br />Info: <a href="http://%s" target="_%s">%s</a>',
                                $item['link'],
                                $item['target'],
                                $item['linkTitle']
                            );
            }
            $output .= sprintf('</div><div class="date">%s</div>', makeDate($item['date']));
        }

        return ('' == $output) ? $this->pmf_lang['msgNoNews'] : $output;
    }

    /**
     * Fetches all news headers
     *
     * @param   boolean $active
     * @return  array
     * @access  public
     * @since   2006-06-25
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     * @author  Matteo Scaramuccia <matteo@scaramuccia.com>
     */
    function getNewsHeader($active = false)
    {
        $headers = array();

        $now = date('YmdHis');
        $query = sprintf("
            SELECT
                id, datum, lang, header,
                active,
                date_start, date_end
            FROM
                %sfaqnews
            ORDER BY
                datum DESC",
            SQLPREFIX
            );
        $result = $this->db->query($query);

        if ($this->db->num_rows($result) > 0) {
            while ($row = $this->db->fetch_object($result)) {
                $expired = ($now > $row->date_end);
                $headers[] = array(
                    'id'        => $row->id,
                    'lang'      => $row->lang,
                    'header'    => $row->header,
                    'date'      => makeDate($row->datum),
                    'active'    => $row->active,
                    'expired'   => $expired);
            }
        }
        
        return $headers;
    }

    /**
     * Fetches a news entry identified by its ID
     *
     * @param   integer $id
     * @param   boolean $admin
     * @return  array
     * @access  public
     * @since   2006-06-25
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     * @author  Matteo Scaramuccia <matteo@scaramuccia.com>
     */
    function getNewsEntry($id, $admin = false)
    {
        $news = array();
        
        $query = sprintf(
            "SELECT
                id, datum, lang,
                header, artikel,
                author_name, author_email,
                date_start, date_end,
                active, comment,
                link, linktitel, target
            FROM
                %sfaqnews
            WHERE
                id = %d",
            SQLPREFIX,
            $id);
        $result = $this->db->query($query);

        if ($this->db->num_rows($result) > 0) {
            if ($row = $this->db->fetch_object($result)) {
                $content        = $row->artikel;
                $active         = ('y' == $row->active);
                $allowComments  = ('y' == $row->comment);
                $expired        = (date('YmdHis') > $row->date_end);

                if (!$admin) {
                    if (!$active) {
                        $content = $this->pmf_lang['err_inactiveNews'];
                    }
                    if ($expired) {
                        $content = $this->pmf_lang['err_expiredNews'];
                    }
                }

                $news = array(
                    'id'            => $row->id,
                    'date'          => makeDate($row->datum),
                    'lang'          => $row->lang,
                    'header'        => $row->header,
                    'content'       => $content,
                    'authorName'    => $row->author_name,
                    'authorEmail'   => $row->author_email,
                    'dateStart'     => $row->date_start,
                    'dateEnd'       => $row->date_end,
                    'active'        => $active,
                    'allowComments' => $allowComments,
                    'link'          => $row->link,
                    'linkTitle'     => $row->linktitel,
                    'target'        => $row->target
                    );
            }
        }

        return $news;
    }

    /**
    * getComments()
    *
    * Returns all user comments from a news record
    *
    * @param    integer     record id
    * @return   string
    * @access   public
    * @since    2002-08-29
    * @author   Thorsten Rinne <thorsten@phpmyfaq.de>
    */
    function getComments($id)
    {
        $oComment = new PMF_Comment($this->db, $this->language);
        return $oComment->getComments($id, PMF_COMMENT_TYPE_NEWS);
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
        $oComment = new PMF_Comment($this->db, $this->language);
        return $oComment->addComment($commentData);
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
        $oComment = new PMF_Comment($this->db, $this->language);
        return $oComment->deleteComment($record_id, $comment_id);
    }

    /**
     * Adds a new news entry
     *
     * @param   array   $data
     * @return  boolean
     * @access  public
     * @since   2006-06-25
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    function addNewsEntry($data)
    {
        $query = sprintf("
            INSERT INTO
                %sfaqnews
            (
                id, datum, lang,
                header, artikel,
                author_name, author_email,
                date_start, date_end,
                active, comment,
                link, linktitel, target
            )
                VALUES
            (
                %d, '%s', '%s',
                '%s', '%s',
                '%s', '%s',
                '%s', '%s',
                '%s', '%s',
                '%s', '%s', '%s'
            )",
            SQLPREFIX,
            $this->db->nextID(SQLPREFIX.'faqnews', 'id'),
            $data['date'],
            $data['lang'],
            $data['header'],
            $data['content'],
            $data['authorName'],
            $data['authorEmail'],
            $data['dateStart'],
            $data['dateEnd'],
            $data['active'],
            $data['comment'],
            $data['link'],
            $data['linkTitle'],
            $data['target']
            );

        if (!$this->db->query($query)) {
            return false;
        }
        return true;
    }

    /**
     * Updates a new news entry identified by its ID
     *
     * @param   integer $id
     * @param   array   $data
     * @return  boolean
     * @access  public
     * @since   2006-06-25
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    function updateNewsEntry($id, $data)
    {
        $query = sprintf("
            UPDATE
                %sfaqnews
            SET
                datum = '%s',
                lang = '%s',
                header = '%s',
                artikel = '%s',
                author_name = '%s',
                author_email = '%s',
                date_start = '%s',
                date_end = '%s',
                active = '%s',
                comment = '%s',
                link = '%s',
                linktitel = '%s',
                target = '%s'
            WHERE
                id = %d",
            SQLPREFIX,
            $data['date'],
            $data['lang'],
            $data['header'],
            $data['content'],
            $data['authorName'],
            $data['authorEmail'],
            $data['dateStart'],
            $data['dateEnd'],
            $data['active'],
            $data['comment'],
            $data['link'],
            $data['linkTitle'],
            $data['target'],
            $id);

        if (!$this->db->query($query)) {
            return false;
        }
        return true;
    }

    /**
     * Deletes a news entry identified by its ID
     *
     * @param   integer $id
     * @return  array
     * @access  public
     * @since   2006-06-25
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    function deleteNews($id)
    {
        $query = sprintf("
            DELETE FROM
                %sfaqnews
            WHERE
                id = %d",
            SQLPREFIX,
            (int)$id);
        if (!$this->db->query($query)) {
            return false;
        }
        return true;
    }
}
// }}}
