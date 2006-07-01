<?php
/**
* $Id: News.php,v 1.6 2006-07-01 21:15:27 thorstenr Exp $
*
* The News class for phpMyFAQ news
*
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
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

        $this->db =& $db;
        $this->language = $language;
        $this->pmf_lang = $PMF_LANG;
    }

    /**
     * getNews
     *
     * Function for generating the FAQ news
     *
     * @return   string
     * @access   public
     * @since    2002-08-23
     * @author   Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    function getNews()
    {
        global $PMF_CONF;
        $counter = 0;
        $query = sprintf(
            "SELECT
                id, datum, header, artikel, link, linktitel, target
            FROM
                %sfaqnews
            ORDER BY
                datum DESC",
            SQLPREFIX);
        $result = $this->db->query($query);
        $output = '';
        if ($PMF_CONF['numNewsArticles'] > 0 && $this->db->num_rows($result) > 0) {
            while (($row = $this->db->fetch_object($result)) && $counter < $PMF_CONF['numNewsArticles']) {
                $counter++;
                $output .= sprintf('<h3><a name="news_%d">%s</a></h3><div class="block">%s',
                    $row->id,
                    $row->header,
                    $row->artikel);
                if ($row->link != '') {
                    $output .= sprintf('<br />Info: <a href="http://%s" target="_%s">%s</a>',
                        $row->link,
                        $row->target,
                        $row->linktitel);
                }
                $output .= sprintf('</div><div class="date">%s</div>', makeDate($row->datum));
            }
            return $output;
        } else {
            return $this->pmf_lang['msgNoNews'];
        }
    }

    /**
     * Fetches all news headers
     *
     * @return  array
     * @access  public
     * @since   2006-06-25
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    function getNewsHeader()
    {
        $headers = array();
        $query = sprintf("
            SELECT
                id, datum, header
            FROM
                %sfaqnews
            ORDER BY datum DESC",
            SQLPREFIX);
        $result = $this->db->query($query);
        if ($this->db->num_rows($result) > 0) {
            while ($row = $this->db->fetch_object($result)) {
                $headers[] = array(
                    'id'        => $row->id,
                    'header'    => $row->header,
                    'date'      => makeDate($row->datum));
            }
        }
        return $headers;
    }

    /**
     * Fetches a news entry identified by its ID
     *
     * @param  integer $id
     * @return array
     * @access public
     * @since  2006-06-25
     * @author Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    function getNewsEntry($id)
    {
        $news = array();
        return $news;
    }

    /**
     * Adds a new news entry
     *
     * @param  array    $data
     * @return boolean
     * @access public
     * @since  2006-06-25
     * @author Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    function addNewsEntry($data)
    {
        $query = sprintf("
            INSERT INTO
                %sfaqnews
            (id, header, artikel, link, linktitel, datum, target)
                VALUES
            (%d, '%s', '%s', '%s', '%s', '%s', '%s')",
            SQLPREFIX,
            $this->db->nextID(SQLPREFIX.'faqnews', 'id'),
            $data['header'],
            $data['content'],
            $data['link'],
            $data['linktitle'],
            $data['date'],
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
     * @param  integer $id
     * @param  array   $data
     * @return boolean
     * @access public
     * @since  2006-06-25
     * @author Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    function updateNewsEntry($id, $data)
    {
        $query = sprintf("
            UPDATE
                %sfaqnews
            SET
                header = '%s',
                artikel = '%s',
                link = '%s',
                linktitel = '%s',
                datum = '%s',
                target = '%s'
            WHERE
                id = %d",
            SQLPREFIX,
            $this->db->nextID(SQLPREFIX.'faqnews', 'id'),
            $data['header'],
            $data['content'],
            $data['link'],
            $data['linktitle'],
            $data['date'],
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
     * @param  integer $id
     * @return array
     * @access public
     * @since  2006-06-25
     * @author Thorsten Rinne <thorsten@phpmyfaq.de>
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