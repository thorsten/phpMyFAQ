<?php
/**
* $Id: Faq.php,v 1.11 2006-06-11 18:11:25 matteo Exp $
*
* The main FAQ class
*
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @package      phpMyFAQ
* @since        2005-12-20
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

class FAQ
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
    * The current FAQ record
    *
    * @var  array
    */
    var $faqRecord = array();

    /**
    * All current FAQ records in an array
    *
    * @var  array
    */
    var $faqRecords = array();

    /**
    * Constructor
    *
    */
    function FAQ($db, $language)
    {
        global $PMF_LANG;

        $this->db = $db;
        $this->language = $language;
        $this->pmf_lang = $PMF_LANG;
	}

	//
	//
	// PUBLIC METHODS
	//
	//

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
        global $sids, $PMF_CONF;
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
                $output .= sprintf('<p><strong>%s %s %s</strong></p>', $this->pmf_lang['msgPage'].$page, $this->pmf_lang['msgVoteFrom'], $pages.$this->pmf_lang['msgPages']);
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

                $title = PMF_htmlentities($row->thema, ENT_NOQUOTES, $this->pmf_lang['metaCharset']);

                if (isset($PMF_CONF["mod_rewrite"]) && $PMF_CONF['mod_rewrite'] == true) {
                    $output .= sprintf('<li><a href="%d_%d_%s.html\">%s</a><br /><span class="little">(%d %s)</soan></li>', $row->category_id, $row->id, $row->lang, $title, $visits, $this->pmf_lang['msgViews']);
                } else {
                    $output .= sprintf('<li><a href="?%saction=artikel&amp;cat=%d&amp;id=%d&amp;artlang=%s">%s</a><span class="little">(%d %s)</soan></li>', $sids, $row->category_id, $row->id, $row->lang, $title, $visits, $this->pmf_lang['msgViews']);
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
                    $output .= sprintf('[ <a href="category%d_%d.html">%s</a> ]', $category, $previous, $this->pmf_lang['msgPrevious']);
                } else {
                    $output .= sprintf('[ <a href="?%daction=show&amp;cat=%d&amp;seite=%d">%s</a> ]', $sids, $category, $previous, $this->pmf_lang['msgPrevious']);
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
                    $output .= sprintf('[ <a href="category%d_%d.html">%s</a> ]', $category, $next, $this->pmf_lang['msgNext']);
                } else {
                    $output .= sprintf('[ <a href="?%daction=show&amp;cat=%d&amp;seite=%d">%s</a> ]', $sids, $category, $next, $this->pmf_lang['msgNext']);
                }
            }

            $output .= "</strong></p>";
        }
	   return $output;
    }

    /**
    * getRecord()
    *
    * Returns an array with all data from a FAQ record
    *
    * @param    integer     record id
    * @return   void
    * @access   public
    * @since    2005-12-20
    * @author   Thorsten Rinne <thorsten@phpmyfaq.de>
    */
    function getRecord($id)
    {
        $result = $this->db->query(sprintf("SELECT * FROM %sfaqdata WHERE id = %d AND lang = '%s'", SQLPREFIX, $id, $this->language));
        if ($row = $this->db->fetch_object($result)) {
            $this->faqRecord = array(
                'id'            => $row->id,
                'lang'          => $row->lang,
                'solution_id'   => $row->solution_id,
                'revision_id'   => $row->revision_id,
                'active'        => $row->active,
                'keywords'      => $row->keywords,
                'title'         => $row->thema,
                'content'       => (('yes' == $row->active) ? $row->content : $this->pmf_lang['err_inactiveArticle']),
                'author'        => $row->author,
                'email'         => $row->email,
                'comment'       => $row->comment,
                'date'          => makeDate($row->datum));
        }
    }

    /**
    * getRecordBySolutionId()
    *
    * Returns an array with all data from a FAQ record
    *
    * @param    integer $solution_id
    * @return   void
    * @access   public
    * @since    2005-12-20
    * @author   Thorsten Rinne <thorsten@phpmyfaq.de>
    */
    function getRecordBySolutionId($solution_id)
    {
        $result = $this->db->query(sprintf("SELECT * FROM %sfaqdata WHERE solution_id = %s", SQLPREFIX, $solution_id));
        if ($row = $this->db->fetch_object($result)) {
            $this->faqRecord = array(
                'id'            => $row->id,
                'lang'          => $row->lang,
                'solution_id'   => $row->solution_id,
                'revision_id'   => $row->revision_id,
                'active'        => $row->active,
                'keywords'      => $row->keywords,
                'title'         => $row->thema,
                'content'       => (('yes' == $row->active) ? $row->content : $this->pmf_lang['err_inactiveArticle']),
                'author'        => $row->author,
                'email'         => $row->email,
                'comment'       => $row->comment,
                'date'          => makeDate($row->datum));
        }
    }

    /**
     * getIdFromSolutionId()
     *
     * Gets the record ID from a given solution ID
     *
     * @param   integer
     * @return  array
     * @access  public
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    function getIdFromSolutionId($solution_id)
    {
        $query = sprintf("SELECT id, lang FROM %sfaqdata WHERE solution_id = %s", SQLPREFIX, $solution_id);
        $result = $this->db->query($query);
        if ($row = $this->db->fetch_object($result)) {
        	return array('id' => $row->id, 'lang' => $row->lang);
        }
        return null;
    }

    /**
    * getAllRecords()
    *
    * Returns an array with all data from all FAQ records
    *
    * @return   void
    * @access   public
    * @since    2005-12-26
    * @author   Thorsten Rinne <thorsten@phpmyfaq.de>
    */
    function getAllRecords()
    {
        $result = $this->db->query(sprintf("SELECT * FROM %sfaqdata ORDER BY id", SQLPREFIX));
        if ($row = $this->db->fetch_object($result)) {
            $this->faqRecords[] = array(
                'id'        => $row->id,
                'lang'      => $row->lang,
                'active'    => $row->active,
                'keywords'  => $row->keywords,
                'title'     => $row->thema,
                'content'   => (('yes' == $row->active) ? $row->content : $this->pmf_lang['err_inactiveArticle']),
                'author'    => $row->author,
                'email'     => $row->email,
                'comment'   => $row->comment,
                'date'      => makeDate($row->datum));
        }
    }

    /**
    * getRecordTitle()
    *
    * Returns the FAQ record title from the ID and language
    *
    * @param    integer     record id
    * @return   string
    * @since    2002-08-28
    * @author   Thorsten Rinne <thorsten@phpmyfaq.de>
    */
    function getRecordTitle($id)
    {
        if (isset($this->faqRecord[$id])) {
            return $this->faqRecord['title'];
        }
        $result = $this->db->query(sprintf("SELECT thema FROM %sfaqdata WHERE id = %d AND lang = '%s'", SQLPREFIX, $id, $this->language));
        if ($this->db->num_rows($result) > 0) {
            while ($row = $this->db->fetch_object($result)) {
                $output = PMF_htmlentities($row->thema, ENT_NOQUOTES, $this->pmf_lang['metaCharset']);
            }
        } else {
            $output = $this->pmf_lang['no_cats'];
        }
        return $output;
    }

    /**
    * getKeywords()
    *
    * Returns the keywords of a FAQ record from the ID and language
    *
    * @param    integer     record id
    * @return   string
    * @since    2005-11-30
    * @author   Thorsten Rinne <thorsten@phpmyfaq.de>
    */
    function getRecordKeywords($id)
    {
        if (isset($this->faqRecord[$id])) {
            return $this->faqRecord['keywords'];
        }
        $result = $this->db->query(sprintf("SELECT keywords FROM %sfaqdata WHERE id = %d AND lang = '%s'", SQLPREFIX, $id, $this->language));
        if ($this->db->num_rows($result) > 0) {
            $row = $this->db->fetch_object($result);
            return PMF_htmlentities($row->keywords, ENT_NOQUOTES, $this->pmf_lang['metaCharset']);
        } else {
            return '';
        }
    }

    /**
    * Returns the number of activated records
    *
    * @param    string
    * @return   int
    * @access   public
    * @since    2002-08-23
    * @author   Thorsten Rinne <thorsten@phpmyfaq.de>
    */
    function getNumberOfRecords()
    {
        $query = sprintf("SELECT id FROM %sfaqdata WHERE active = 'yes' AND lang = '%s'", SQLPREFIX, $this->language);
        $num = $this->db->num_rows($this->db->query($query));
        if ($num > 0) {
            return $num;
        } else {
            return 0;
        }
    }

    /**
    * logViews()
    *
    * Counting the views of a FAQ record
    *
    * @param    integer     id
    * @param    string      lang
    * @return   void
    * @access   public
    * @since    2001-02-15
    * @auhtor   Bastian Pöttner <bastian@poettner.net>
    * @author   Thorsten Rinne <thorsten@phpmyfaq.de>
    */
    function logViews($id)
    {
        $nVisits = 0;
        $today = time();
        $query = sprintf("SELECT visits FROM %sfaqvisits WHERE id = %d AND lang = '%s'", SQLPREFIX, $id, $this->language);
        if ($result = $this->db->query($query)) {
            $row = $this->db->fetch_object($result);
            $nVisits = $row->visits;
        }
        if ($nVisits == "0" || $nVisits == "") {
            $query = sprintf("INSERT INTO %sfaqvisits (id, lang, visits, last_visit) VALUES (%d, '%s', 1, %d)", SQLPREFIX, $id, $this->language, $today);
            $this->db->query($query);
        } else {
            $query = sprintf("UPDATE %sfaqvisits SET visits = visits+1, last_visit = %d WHERE id = %d AND lang = '%s'", SQLPREFIX, $today, $id, $this->language);
            $this->db->query($query);
        }
    }

    /**
    * getVotingResult()
    *
    * Calculates the rating of the user votings
    *
    * @param    integer     record id
    * @return   string
    * @access   public
    * @since    2002-08-29
    * @author   Thorsten Rinne <thorsten@phpmyfaq.de>
    */
    function getVotingResult($id)
    {
 	  $result = $this->db->query(sprintf('SELECT (vote/usr) as voting, usr FROM %sfaqvoting WHERE artikel = %d', SQLPREFIX, $id));
	   if ($this->db->num_rows($result) > 0) {
            $row = $this->db->fetch_object($result);
            return sprintf(' %s %s 5 (%d %s)', round($row->voting, 2), $this->pmf_lang['msgVoteFrom'], $row->usr, $this->pmf_lang['msgVotings']);
       } else {
            return sprintf(' 0 %s 5 (0 %s)', $this->pmf_lang['msgVoteFrom'], $this->pmf_lang['msgVotings']);
       }
    }

    /**
    * getComments()
    *
    * Returns all user comments from a FAQ record
    *
    * @param    integer     record id
    * @return   string
    * @access   public
    * @since    2002-08-29
    * @author   Thorsten Rinne <thorsten@phpmyfaq.de>
    */
    function getComments($id)
    {
        $result = $this->db->query(sprintf("SELECT usr, email, comment FROM %sfaqcomments WHERE id = %d", SQLPREFIX, $id));
        $output = '';
        if ($this->db->num_rows($result) > 0) {
            while ($row = $this->db->fetch_object($result)) {
                $output .= '<p class="comment">';
                $output .= sprintf('<strong>%s<a href="mailto:%s">%s</a>:</strong>', $this->pmf_lang['msgCommentBy'], safeEmail($row->email), $row->usr);
                $output .= sprintf('<br />%s</p>', $row->comment);
            }
        }
        return $output;
    }

    /**
    * getTopTen()
    *
    * This function generates the Top Ten with the mosted viewed records
    *
    * @return   string
    * @access   public
    * @author   Thorsten Rinne <thorsten@phpmyfaq.de>
    * @since    2002-05-07
    */
    function getTopTen()
    {
        global $PMF_CONF;
        $result = $this->getTopTenData(PMF_NUMBER_RECORDS_TOPTEN);
        if (count($result) > 0) {
            $output = '<ol>';
            foreach ($result as $row) {
                $output .= sprintf('<li><strong>%d %s:</strong><br />', $row['visits'], $this->pmf_lang['msgViews']);
                $shortTitle = makeShorterText(PMF_htmlentities($row['thema'], ENT_NOQUOTES, $this->pmf_lang['metaCharset']), 8);
                $output .= sprintf('<a href="%s">%s</a></li>', $row['url'], $shortTitle);
            }
            $output .= '</ol>';
        } else {
            $output = $this->pmf_lang['err_noTopTen'];
        }
        return $output;
    }

    /**
    * getFiveLatest()
    *
    * This function generates the list with the five latest published records
    *
    * @return   string
    * @access   public
    * @author   Thorsten Rinne <thorsten@phpmyfaq.de>
    * @since    2002-05-07
    */
    function getFiveLatest()
    {
        $result = $this->getFiveLatestData(PMF_NUMBER_RECORDS_LATEST);
        if (count ($result) > 0) {
            $output = '<ol>';
            foreach ($result as $row) {
                $shortTitle = makeShorterText(PMF_htmlentities($row['thema'], ENT_NOQUOTES, $this->pmf_lang['metaCharset']), 8);
                $output .= sprintf('<a href="%s">%s</a> (%s)</li>', $row['url'], $shortTitle, makeDate($row['datum']));
            }
            $output .= '</ol>';
        } else {
            $output = $this->pmf_lang["err_noArticles"];
        }
        return $output;
    }

    //
    //
    // PRIVATE METHODS
    //
    //

    /**
    * getTopTenData()
    *
    * This function generates the Top Ten data with the mosted viewed records
    *
    * @param    integer
    * @param    integer
    * @param    string
    * @return   array
    * @access   private
    * @author   Robin Wood <robin@digininja.org>
    * @author   Thorsten Rinne <thorsten@rinne.info>
    * @author   Matteo Scaramuccia <matteo@scaramuccia.com>
    * @since    2005-03-06
    */
    function getTopTenData($count = PMF_NUMBER_RECORDS_TOPTEN, $categoryId = 0, $language = null)
    {
        global $sids, $PMF_CONF;
        $query =
            'SELECT
                DISTINCT '.SQLPREFIX.'faqdata.id AS id,
                '.SQLPREFIX.'faqdata.lang AS lang,
                '.SQLPREFIX.'faqdata.thema AS thema,
                '.SQLPREFIX.'faqcategoryrelations.category_id AS category_id,
                '.SQLPREFIX.'faqvisits.visits AS visits
            FROM
                '.SQLPREFIX.'faqvisits,
                '.SQLPREFIX.'faqdata
            LEFT JOIN
                '.SQLPREFIX.'faqcategoryrelations
            ON
                '.SQLPREFIX.'faqdata.id = '.SQLPREFIX.'faqcategoryrelations.record_id
            AND
                '.SQLPREFIX.'faqdata.lang = '.SQLPREFIX.'faqcategoryrelations.record_lang
            WHERE
                '.SQLPREFIX.'faqdata.id = '.SQLPREFIX.'faqvisits.id
            AND
                '.SQLPREFIX.'faqdata.lang = '.SQLPREFIX.'faqvisits.lang
            AND
                '.SQLPREFIX.'faqdata.active = \'yes\'';
        if (isset($categoryId) && is_numeric($categoryId) && ($categoryId != 0)) {
            $query .= '
            AND
                '.SQLPREFIX.'faqcategoryrelations.category_id = \''.$categoryId.'\'';
        }
        if (isset($language) && PMF_Init::isASupportedLanguage($language)) {
            $query .= '
            AND
                '.SQLPREFIX.'faqdata.lang = \''.$language.'\'';
        }
        $query .= '
            ORDER BY
                '.SQLPREFIX.'faqvisits.visits DESC';

        $result = $this->db->query($query);
        $topten = array();
        $data = array();

        $i = 1;
        $oldId = 0;
        while (($row = $this->db->fetch_object($result)) && $i <= $count) {
            if ($oldId != $row->id) {
                $data['visits'] = $row->visits;
                $data['thema'] = $row->thema;
                if (isset($PMF_CONF["mod_rewrite"]) && $PMF_CONF["mod_rewrite"] == true) {
                    $data['url'] = sprintf('%d_%d_%s.html', $row->category_id, $row->id, $row->lang);
                } else {
                    $data['url'] = sprintf('?%daction=artikel&amp;cat=%d&amp;id=%d&amp;artlang=%s', $sids, $row->category_id, $row->id, $row->lang);
                }
	        $topten[] = $data;
	        $i++;
            }
            $oldId = $row->id;
        }
        return $topten;
    }

    /**
    * getFiveLatestData()
    *
    * This function generates an array with a specified number of most recent published records
    *
    * @param    integer
    * @return   array
    * @access   public
    * @author   Robin Wood <robin@digininja.org>
    * @since    2005-03-06
    */
    function getFiveLatestData($count = PMF_NUMBER_RECORDS_LATEST)
    {
        global $sids, $PMF_CONF;
        $query =
            'SELECT
                DISTINCT '.SQLPREFIX.'faqdata.id AS id,
                '.SQLPREFIX.'faqdata.lang AS lang,
                '.SQLPREFIX.'faqcategoryrelations.category_id AS category_id,
                '.SQLPREFIX.'faqdata.thema AS thema,
                '.SQLPREFIX.'faqdata.datum AS datum,
                '.SQLPREFIX.'faqvisits.visits AS visits
            FROM
                '.SQLPREFIX.'faqvisits,
                '.SQLPREFIX.'faqdata
            LEFT JOIN
                '.SQLPREFIX.'faqcategoryrelations
            ON
                '.SQLPREFIX.'faqdata.id = '.SQLPREFIX.'faqcategoryrelations.record_id
            AND
                '.SQLPREFIX.'faqdata.lang = '.SQLPREFIX.'faqcategoryrelations.record_lang
            WHERE
                '.SQLPREFIX.'faqdata.id = '.SQLPREFIX.'faqvisits.id
            AND
                '.SQLPREFIX.'faqdata.lang = '.SQLPREFIX.'faqvisits.lang
            AND
                '.SQLPREFIX.'faqdata.active = \'yes\'
            ORDER BY
                '.SQLPREFIX.'faqdata.datum DESC';

        $result = $this->db->query($query);
        $latest = array();
        $data = array();

        $i = 0;
        $oldId = 0;
        while (($row = $this->db->fetch_object($result)) && $i < $count ) {
            if ($oldId != $row->id) {
                $data['datum'] = $row->datum;
                $data['thema'] = $row->thema;
                $data['visits'] = $row->visits;
                if (isset($PMF_CONF["mod_rewrite"]) && $PMF_CONF["mod_rewrite"] == true) {
                    $data['url'] = sprintf('%d_%d_%s.html', $row->category_id, $row->id, $row->lang);
                } else {
                    $data['url'] = sprintf('?%daction=artikel&amp;cat=%d&amp;id=%d&amp;artlang=%s', $sids, $row->category_id, $row->id, $row->lang);
                }
                $latest[] = $data;
                $i++;
            }
            $oldId = $row->id;
        }
        return $latest;
    }



}
