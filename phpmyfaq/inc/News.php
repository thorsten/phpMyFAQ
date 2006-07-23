<?php
/**
* $Id: News.php,v 1.8 2006-07-23 09:14:46 matteo Exp $
*
* The News class for phpMyFAQ news
*
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @author       Matteo Scaramuccia <matteo@scaramuccia.com>
* @package      phpMyFAQ
* @since        2006-06-25
* @copyright    (c) 2006 phpMyFAQ Team
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
     * getNews
     *
     * Function for generating the FAQ news
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
        global $PMF_CONF;

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
        
        $output = '';
        if ($PMF_CONF['numNewsArticles'] > 0 && $this->db->num_rows($result) > 0) {
            while (    ($row = $this->db->fetch_object($result))
                   ) {
                $counter++;
                if (   ($showArchive  && ($counter > $PMF_CONF['numNewsArticles']))
                    || ((!$showArchive) && ($counter <= $PMF_CONF['numNewsArticles']))
                   ) {
                $output .= sprintf('<h3><a name="news_%d">%s</a><a href="?action=news&amp;newsid=%d&amp;newslang=%s"> <img id="goNews" src="images/more.gif" width="11" height="11" alt="%s" /></a></h3><div class="block">%s',
                                $row->id,
                                $row->header,
                                $row->id,
                                $row->lang,
                                $row->header,
                                $row->artikel
                            );
                if ($row->link != '') {
                    $output .= sprintf('<br />Info: <a href="http://%s" target="_%s">%s</a>',
                                    $row->link,
                                    $row->target,
                                    $row->linktitel
                                );
                }
                $output .= sprintf('</div><div class="date">%s</div>', makeDate($row->datum));
                }
            }
            return $output;
        } else {
            return $this->pmf_lang['msgNoNews'];
        }
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
     * @return  array
     * @access  public
     * @since   2006-06-25
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     * @author  Matteo Scaramuccia <matteo@scaramuccia.com>
     */
    function getNewsEntry($id)
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
                $news = array(
                    'id'            => $row->id,
                    'date'         => makeDate($row->datum),
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
            }
        }

        return $news;
    }

    function getCommentsData($id)
    {
        $comments = array();

        return $comments;
    }

    function getComments($id)
    {
        $comments = $this->getCommentsData($id);
        $html = '';

        return $html;
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

    /**
     * Print the HTML for the date time window
     *
     * @param   string  $key
     * @param   array   $date
     * @return  array
     * @access  public
     * @since   2006-07-23
     * @author  Matteo Scaramuccia <matteo@scaramuccia.com>
     */
    function printDateTimeInput($key, $date)
    {
        $html = '';
        // YYYY
        $html .= '<div style="float: left;"><select name="'.$key.'YYYY"><option></option>';
        for ($i = 2006; $i < 2011; $i++) {
            $html .= '<option'.( $date['YYYY'] == $i ? ' selected="selected"' : '').'>'.$i.'</option>';
        }
        $html .= '</select>&nbsp;-&nbsp;</div>';
        // MM
        $html .= '<div style="float: left;"><select name="'.$key.'MM"><option></option>';
        for ($i = 1; $i < 13; $i++) {
            $html .= '<option'.( ($date['MM'] == $i) && ('' != $date['YYYY']) ? ' selected="selected"' : '').'>'.str_pad($i, 2, "0", STR_PAD_LEFT).'</option>';
        }
        $html .= '</select>&nbsp;-&nbsp;</div>';
        // DD
        $html .= '<div style="float: left;"><select name="'.$key.'DD"><option></option>';
        for ($i = 1; $i < 32; $i++) {
            $html .= '<option'.( ($date['DD'] == $i) && ('' != $date['MM']) ? ' selected="selected"' : '').'>'.str_pad($i, 2, "0", STR_PAD_LEFT).'</option>';
        }
        $html .= '</select>&nbsp;&nbsp;&nbsp;</div>';
        // HH
        $html .= '<div style="float: left;"><select name="'.$key.'HH"><option></option>';
        for ($i = 0; $i < 24; $i++) {
            $html .= '<option'.( ($date['HH'] == $i) && ('' != $date['DD']) ? ' selected="selected"' : '').'>'.str_pad($i, 2, "0", STR_PAD_LEFT).'</option>';
        }
        $html .= '</select>&nbsp;:&nbsp;</div>';
        // mm
        $html .= '<div style="float: left;"><select name="'.$key.'mm"><option></option>';
        for ($i = 0; $i < 60; $i++) {
            $html .= '<option'.( ($date['mm'] == $i) && ('' != $date['HH']) ? ' selected="selected"' : '').'>'.str_pad($i, 2, "0", STR_PAD_LEFT).'</option>';
        }
        $html .= '</select>&nbsp;:&nbsp;</div>';
        // ss
        $html .= '<div style="float: left;"><select name="'.$key.'ss"><option></option>';
        for ($i = 0; $i < 60; $i++) {
            $html .= '<option'.( ($date['ss'] == $i) && ('' != $date['mm']) ? ' selected="selected"' : '').'>'.str_pad($i, 2, "0", STR_PAD_LEFT).'</option>';
        }
        $html .= '</select></div>';
        
        return $html;
    }
}
