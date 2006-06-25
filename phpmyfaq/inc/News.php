<?php
/**
* $Id: News.php,v 1.1 2006-06-25 11:01:57 thorstenr Exp $
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

        $this->db = &$db;
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

}