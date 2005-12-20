<?php
/**
* $Id: Faq.php,v 1.1 2005-12-20 08:31:38 thorstenr Exp $
*
* The main FAQ class
*
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @package      phpMyFAQ
* @since        2005-12-20
* @copyright    (c) 2005 phpMyFAQ Team
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

class FAQ
{
    /**
    * DB handle
    *
    */
    var $db;
    
    /**
    * Language
    *
    */
    var $language;
    
    /**
    * Constructor
    *
    */
    function FAQ($db, $language)
    {
    	return $this->__construct();
    }
    function __construct($db, $language)
    {
        $this->db = $db;
        $this->language = $language;
	}
	
	/**
	* showAllRecords()
	*
	* This function returns all records from one category
	*
	* @param    int     category id
	* @return   string
	* @access   public
	* @author   Thorsten Rinne <thorsten@phpmyfaq.de>
	* @since    2002-08-27
	*/
    function showAllRecords($category)
    {
        global $sids, $PMF_LANG, $PMF_CONF;
        $page = 1;
        $output = '';
        
        if (isset($_REQUEST["seite"])) {
            $page = (int)$_REQUEST["seite"];
        }
        
        $result = $this->db->query('
            SELECT
                '.SQLPREFIX.'faqdata.id AS id,
                '.SQLPREFIX.'faqdata.lang AS lang,
                '.SQLPREFIX.'faqdata.thema AS thema,
                '.SQLPREFIX.'faqcategoryrelations.category_id AS category_id,
                '.SQLPREFIX.'faqvisits.visits AS visits
            FROM 
                '.SQLPREFIX.'faqdata
            LEFT JOIN 
                '.SQLPREFIX.'faqcategoryrelations
            ON
                '.SQLPREFIX.'faqdata.id = '.SQLPREFIX.'faqcategoryrelations.record_id
            AND
                '.SQLPREFIX.'faqdata.lang = '.SQLPREFIX.'faqcategoryrelations.record_lang
            LEFT JOIN
                '.SQLPREFIX.'faqvisits
            ON
                '.SQLPREFIX.'faqdata.id = '.SQLPREFIX.'faqvisits.id
            AND
                '.SQLPREFIX.'faqvisits.lang = '.SQLPREFIX.'faqdata.lang
            WHERE
                '.SQLPREFIX.'faqdata.active = \'yes\'
            AND
                '.SQLPREFIX.'faqcategoryrelations.category_id = '.$category.'
            ORDER BY
                '.SQLPREFIX.'faqdata.id');
        
        $num = $this->db->num_rows($result);
        $pages = ceil($num / $PMF_CONF["numRecordsPage"]);
        
        if ($page == 1) {
            $first = 0;
        } else {
            $first = ($page * $PMF_CONF["numRecordsPage"]) - $PMF_CONF["numRecordsPage"];
        }

        if ($num > 0) {
            if ($pages > 1) {
                $output .= sprintf('<p><strong>%s %s %s</strong></p>', $PMF_LANG['msgPage'].$page, $PMF_LANG['msgVoteFrom'], $pages.$PMF_LANG['msgPages']);
            }
            $output .= '<ul class="phpmyfaq_ul">';
            $counter = 0;
            $displayedCounter = 0;
            while (($row = $this->db->fetch_object($result)) && $displayedCounter < $PMF_CONF['numRecordsPage']) {
                $counter ++;
                if ($counter <= $first) {
                    continue;
                }
                $displayedCounter++;

                if (empty($row->visits)) {
                    $visits = 0;
                } else {
                    $visits = $row->visits;
                }
                
                $title = PMF_htmlentities($row->thema, ENT_NOQUOTES, $PMF_LANG['metaCharset']);

                if (isset($PMF_CONF["mod_rewrite"]) && $PMF_CONF['mod_rewrite'] == true) {
                    $output .= sprintf('<li><a href="%d_%d_%s.html\">%s</a><br /><span class="little">(%d %s)</soan></li>', $row->category_id, $row->id, $row->lang, $title, $visits, $PMF_LANG['msgViews']);
                } else {
                    $output .= sprintf('<li><a href="?%saction=artikel&amp;cat=%d&amp;id=%d&amp;artlang=%s">%s</a><span class="little">(%d %s)</soan></li>', $sids, $row->category_id, $row->id, $row->lang, $title, $visits, $PMF_LANG['msgViews']);
                }
            }
            $output .= '</ul>';
        } else {
            return false;
        }

        if ($pages > 1) {
            $output .= "<p align=\"center\"><strong>";
            $previous = $page - 1;
            $next = $page + 1;

            if ($previous != 0) {
                if (isset($PMF_CONF['mod_rewrite']) && $PMF_CONF['mod_rewrite'] == true) {
                    $output .= sprintf('[ <a href="category%d_%d.html">%s</a> ]', $category, $previous, $PMF_LANG['msgPrevious']);
                } else {
                    $output .= sprintf('[ <a href="?%daction=show&amp;cat=%d&amp;seite=%d">%s</a> ]', $sids, $category, $previous, $PMF_LANG['msgPrevious']);
                }
            }

            $output .= ' ';

            for ($i = 1; $i <= $pages; $i++) {
                if (isset($PMF_CONF['mod_rewrite']) && $PMF_CONF['mod_rewrite'] == true) {
                    $output .= sprintf('[ <a href="category%d_%d.html">%s</a> ] ', $category, $i, $i);
                } else {
                    $output .= sprintf('[ <a href="?%daction=show&amp;cat=%d&amp;seite=%d">%d</a> ] ', $sids, $category, $i, $i);
                }
            }
            
            if ($next <= $pages) {
                if (isset($PMF_CONF['mod_rewrite']) && $PMF_CONF['mod_rewrite'] == true) {
                    $output .= sprintf('[ <a href="category%d_%d.html">%s</a> ]', $category, $next, $PMF_LANG['msgNext']);
                } else {
                    $output .= sprintf('[ <a href="?%daction=show&amp;cat=%d&amp;seite=%d">%s</a> ]', $sids, $category, $next, $PMF_LANG['msgNext']);
                }
            }

            $output .= "</strong></p>";
        }
	   return $output;
    }
    
}