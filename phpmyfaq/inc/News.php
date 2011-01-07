<?php
/**
 * The News class for phpMyFAQ news
 * 
 * PHP Version 5.2
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
 *
 * @category  phpMyFAQ
 * @package   PMF_News
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo Scaramuccia <matteo@scaramuccia.com>
 * @copyright 2006-2011 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2006-06-25
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * The News class for phpMyFAQ news
 *
 * @category  phpMyFAQ
 * @package   PMF_News
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo Scaramuccia <matteo@scaramuccia.com>
 * @copyright 2006-2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2006-06-25
 */
class PMF_News
{
    /**
     * DB handle
     *
     * @var PMF_DB
     */
    private $db;

    /**
    * Language
    *
    * @var  string
    */
    private $language;

    /**
    * Language strings
    *
    * @var  string
    */
    private $pmf_lang;

    /**
    * Constructor
    *
    */
    public function __construct()
    {
        global $PMF_LANG;

        $this->db       = PMF_Db::getInstance();
        $this->language = PMF_Language::$language;
        $this->pmf_lang = $PMF_LANG;
    }

    /**
     * Return the latest news data
     *
     * @param boolean $showArchive    Show archived news
     * @param boolean $active         Show active news
     * @param boolean $forceConfLimit Force to limit in configuration
     * 
     * @return array
     */
    public function getLatestData($showArchive = false, $active = true, $forceConfLimit = false)
    {
        $news      = array();
        $counter   = 0;
        $now       = date('YmdHis');
        $faqconfig = PMF_Configuration::getInstance();

        $query = sprintf("
            SELECT
                *
            FROM
                %sfaqnews
            WHERE
                date_start <= '%s'
            AND 
                date_end   >= '%s'
            %s
            AND
                lang = '%s'
            ORDER BY
                datum DESC",
            SQLPREFIX,
            $now,
            $now,
            $active ? "AND active = 'y'" : '',
            $this->language);
            
        $result = $this->db->query($query);

        if ($faqconfig->get('main.numberOfShownNewsEntries') > 0 && $this->db->num_rows($result) > 0) {
        	
            while (($row = $this->db->fetch_object($result))) {
            	
                $counter++;
                if (($showArchive  && ($counter > $faqconfig->get('main.numberOfShownNewsEntries'))) || 
                   ((!$showArchive) && (!$forceConfLimit) && 
                   ($counter <= $faqconfig->get('main.numberOfShownNewsEntries'))) || 
                   ((!$showArchive) && $forceConfLimit)) {
                   	
                    $item = array(
                        'id'            => $row->id,
                        'lang'          => $row->lang,
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
                        'target'        => $row->target);
                    $news[] = $item;
                }
            }
        }

        return $news;
    }

    /**
     * Function for generating the HTML5 code for the current news
     *
     * @param boolean $showArchive Show archived news
     * @param boolean $active      Show active news
     * 
     * @return string
     */
    public function getNews($showArchive = false, $active = true)
    {
        $output = '';
        $news   = $this->getLatestData($showArchive, $active);

        foreach ($news as $item) {

            $url = sprintf('%s?action=news&amp;newsid=%d&amp;newslang=%s',
                           PMF_Link::getSystemRelativeUri(),
                           $item['id'],
                           $item['lang']);
            $oLink = new PMF_Link($url);
            
            if (isset($item['header'])) {
                $oLink->itemTitle = $item['header'];
            }



            $output .= sprintf('<header><h3><a name="news_%d" href="%s">%s <img class="goNews" src="images/more.gif" width="11" height="11" alt="%s" /></a></h3></header>',
                $item['id'],
                $oLink->toString(),
                $item['header'],
                $item['header']
                );

            $output .= sprintf('%s', $item['content']);

            if (strlen($item['link']) > 1) {
                $output .= sprintf('<br />%s <a href="%s" target="_%s">%s</a>',
                    $this->pmf_lang['msgInfo'],
                    $item['link'],
                    $item['target'],
                    $item['linkTitle']);
            }
            
            $output .= sprintf('<div class="date">%s</div>', PMF_Date::createIsoDate($item['date']));
        }

        return ('' == $output) ? $this->pmf_lang['msgNoNews'] : $output;
    }

    /**
     * Fetches all news headers
     *
     * @return array
     */
    public function getNewsHeader()
    {
        $headers = array();
        $now     = date('YmdHis');
        
        $query = sprintf("
            SELECT
                id, datum, lang, header, active, date_start, date_end
            FROM
                %sfaqnews
            WHERE
                lang = '%s'
            ORDER BY
                datum DESC",
            SQLPREFIX,
            $this->language);
            
        $result = $this->db->query($query);

        if ($this->db->num_rows($result) > 0) {
            while ($row = $this->db->fetch_object($result)) {
                $expired = ($now > $row->date_end);
                $headers[] = array(
                    'id'        => $row->id,
                    'lang'      => $row->lang,
                    'header'    => $row->header,
                    'date'      => PMF_Date::createIsoDate($row->datum),
                    'active'    => $row->active,
                    'expired'   => $expired);
            }
        }

        return $headers;
    }

    /**
     * Fetches a news entry identified by its ID
     *
     * @param integer $id    ID of news
     * @param boolean $admin Is admin
     * 
     * @return array
     */
    function getNewsEntry($id, $admin = false)
    {
        $news = array();

        $query = sprintf(
            "SELECT
                *
            FROM
                %sfaqnews
            WHERE
                id = %d
            AND
                lang = '%s'",
            SQLPREFIX,
            $id,
            $this->language);
            
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
                    'lang'          => $row->lang,
                    'date'          => PMF_Date::createIsoDate($row->datum),
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
                    'target'        => $row->target);
            }
        }

        return $news;
    }

    /**
     * Adds a comment
     *
     * @param array $commentData Array with comment data
     * 
     * @return boolean
     */
    function addComment($commentData)
    {
        $oComment = new PMF_Comment();
        return $oComment->addComment($commentData);
    }

    /**
     * Deletes a comment
     *
     * @param integer $record_id  Record ID
     * @param integer $comment_id Comment ID
     * 
     * @return boolean
     */
    function deleteComment($record_id, $comment_id)
    {
        $oComment = new PMF_Comment();
        return $oComment->deleteComment($record_id, $comment_id);
    }

    /**
     * Adds a new news entry
     *
     * @param array $data Array with news data
     * 
     * @return boolean
     */
    function addNewsEntry($data)
    {
        $query = sprintf("
            INSERT INTO
                %sfaqnews
            (id, datum, lang, header, artikel, author_name, author_email, date_start, date_end, active, comment,
            link, linktitel, target)
                VALUES
            (%d, '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')",
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
            $data['target']);

        if (!$this->db->query($query)) {
            return false;
        }
        
        return true;
    }

    /**
     * Updates a new news entry identified by its ID
     *
     * @param integer $id   News ID
     * @param array   $data Array with news data
     * 
     * @return boolean
     */
    function updateNewsEntry($id, Array $data)
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
     * @param integer $id News ID
     * 
     * @return boolean
     */
    function deleteNews($id)
    {
        $query = sprintf("
            DELETE FROM
                %sfaqnews
            WHERE
                id = %d
            AND
                lang = '%s'",
            SQLPREFIX,
            $id,
            $this->language);
            
        if (!$this->db->query($query)) {
            return false;
        }
        
        return true;
    }
}