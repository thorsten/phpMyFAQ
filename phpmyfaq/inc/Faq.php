<?php
/**
* $Id: Faq.php,v 1.40 2006-07-24 18:57:55 thorstenr Exp $
*
* The main FAQ class
*
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @author       Matteo Scaramuccia <matteo@scaramuccia.com>
* @package      phpMyFAQ
* @since        2005-12-20
* @copyright    (c) 2005-2006 phpMyFAQ Team
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
/**
 * This include is needed in _getSQLQuery() private method
 */
require_once('Utils.php');
// }}}

// {{{ Constants
/**#@+
  * SQL constants definitions
  */
define("FAQ_SQL_YES", "y");
define("FAQ_SQL_NO", "n");
define("FAQ_SQL_ACTIVE_YES", "yes");
define("FAQ_SQL_ACTIVE_NO", "no");
/**#@-*/
/**#@+
  * Query type definitions
  */
define("FAQ_QUERY_TYPE_DEFAULT", "faq_default");
define("FAQ_QUERY_TYPE_APPROVAL", "faq_approval");
define("FAQ_QUERY_TYPE_EXPORT_DOCBOOK", "faq_export_docbook");
define("FAQ_QUERY_TYPE_EXPORT_PDF", "faq_export_pdf");
define("FAQ_QUERY_TYPE_EXPORT_XHTML", "faq_export_xhtml");
define("FAQ_QUERY_TYPE_EXPORT_XML", "faq_export_xml");
define("FAQ_QUERY_TYPE_RSS_LATEST", "faq_rss_latest");
/**#@-*/
// }}}

// {{{ Classes
class PMF_Faq
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
    function PMF_Faq(&$db, $language)
    {
        global $PMF_LANG;

        $this->db = &$db;
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
        global $sids, $PMF_CONF, $tree;
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
                $output .= sprintf('<p><strong>%s %s %s</strong></p>',
                    $this->pmf_lang['msgPage'] . $page,
                    $this->pmf_lang['msgVoteFrom'],
                    $pages . $this->pmf_lang['msgPages']);
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
                $url   = sprintf('%saction=artikel&amp;cat=%d&amp;id=%d&amp;artlang=%s',
                            $sids,
                            $row->category_id,
                            $row->id,
                            $row->lang
                        );
                $oLink = new PMF_Link(PMF_Link::getSystemRelativeUri().'?'.$url);
                $oLink->itemTitle = $row->thema;
                $oLink->text = sprintf('%s</a><br /><span class="little">(%d %s)</span>',
                            $title,
                            $visits,
                            $this->pmf_lang['msgViews']
                        );
                $oLink->tooltip = $title;
                $listItem = '<li>'.$oLink->toHtmlAnchor().'</li>';

                $output .= $listItem;
            }
            $output .= '</ul>';
        } else {
            return false;
        }

        $categoryName = 'CategoryId-'.$category;
        // Hack: we need the Category Name for the new SEO URL schema
        //       so we try to use the global $tree object, if it exist
        if (isset($tree)) {
            $categoryName = $tree->categoryName[$category]['name'];
        }
        if ($pages > 1) {
            $output .= "<p align=\"center\"><strong>";
            $previous = $page - 1;
            $next = $page + 1;

            if ($previous != 0) {
                $title = $this->pmf_lang['msgPrevious'];
                $url   = sprintf('%saction=show&amp;cat=%d&amp;seite=%d',
                            $sids,
                            $category,
                            $previous
                        );
                $oLink = new PMF_Link(PMF_Link::getSystemRelativeUri().'?'.$url);
                $oLink->itemTitle = $categoryName;
                $oLink->text = $title;
                $oLink->tooltip = $title;
                $output .= '[ '.$oLink->toHtmlAnchor().' ]';
            }

            $output .= ' ';

            for ($i = 1; $i <= $pages; $i++) {
                $title = $i;
                $url   = sprintf('%saction=show&amp;cat=%d&amp;seite=%d',
                            $sids,
                            $category,
                            $i
                        );
                $oLink = new PMF_Link(PMF_Link::getSystemRelativeUri().'?'.$url);
                $oLink->itemTitle = $categoryName;
                $oLink->text = $title;
                $oLink->tooltip = $title;
                $output .= '[ '.$oLink->toHtmlAnchor().' ]';
            }

            $output .= ' ';

            if ($next <= $pages) {
                $title = $this->pmf_lang['msgNext'];
                $url   = sprintf('%saction=show&amp;cat=%d&amp;seite=%d',
                            $sids,
                            $category,
                            $next
                        );
                $oLink = new PMF_Link(PMF_Link::getSystemRelativeUri().'?'.$url);
                $oLink->itemTitle = $categoryName;
                $oLink->text = $title;
                $oLink->tooltip = $title;
                $output .= '[ '.$oLink->toHtmlAnchor().' ]';
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
        $query = sprintf(
            "SELECT
                *
            FROM
                %sfaqdata
            WHERE
                id = %d AND lang = '%s'",
            SQLPREFIX,
            $id,
            $this->language);

        $result = $this->db->query($query);
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
     * Adds a new record
     *
     * @param    array    $data
     * @return   integer
     * @access   public
     * @since    2006-06-18
     * @author   Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    function addRecord($data)
    {
        if (!is_array($data)) {
            return false;
        }

        // Get new ID first
        $newId = $this->db->nextID(SQLPREFIX.'faqdata', 'id');

        // Add new entry
        $this->db->query(sprintf(
            "INSERT INTO
                %sfaqdata
             (id, lang, solution_id, revision_id, active, keywords, thema, content, author, email, comment, datum)
                VALUES
            (%d, '%s', %d, %d, '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')",
            SQLPREFIX,
            $newId,
            $data['lang'],
            $this->getSolutionId(),
            0,
            $data['active'],
            $data['keywords'],
            $data['thema'],
            $data['content'],
            $data['author'],
            $data['email'],
            $data['comment'],
            $data['date']));

        return $newId;
    }

    /**
     * Adds a new category relation to a record
     *
     * @param    array    $categories
     * @param    integer  $record_id
     * @param    string   $language
     * @return   integer
     * @access   public
     * @since    2006-07-02
     * @author   Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    function addCategoryRelation($categories, $record_id, $language)
    {
        foreach ($categories as $_category) {
            $this->db->query(sprintf(
                "INSERT INTO
                    %sfaqcategoryrelations
                VALUES
                    (%d, '%s', %d, '%s')",
                SQLPREFIX,
                $_category,
                $language,
                $record_id,
                $language));
        }
        return true;
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
        $query = sprintf(
            "SELECT
                *
            FROM
                %sfaqdata
            WHERE
                solution_id = %d",
            SQLPREFIX,
            $solution_id);
        $result = $this->db->query($query);
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
        $query = sprintf(
            "SELECT
                id, lang
            FROM
                %sfaqdata
            WHERE
                solution_id = %d",
            SQLPREFIX,
            $solution_id);
        $result = $this->db->query($query);
        if ($row = $this->db->fetch_object($result)) {
            return array('id' => $row->id, 'lang' => $row->lang);
        }
        return null;
    }

    /**
      * Gets the latest solution id for a FAQ record
     *
      * @return  integer
      * @access  public
      * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
      */
    function getSolutionId()
    {
        $latest_id = 0;
        $next_solution_id = 0;

        $query = sprintf('
            SELECT
                MAX(solution_id) AS solution_id
            FROM
                %sfaqdata',
            SQLPREFIX);
        $result = $this->db->query($query);

        if ($result && $row = $this->db->fetch_object($result)) {
            $latest_id = $row->solution_id;
        }

        if ($latest_id < PMF_SOLUTION_ID_START_VALUE) {
            $next_solution_id = PMF_SOLUTION_ID_START_VALUE;
        } else {
            $next_solution_id = $latest_id + PMF_SOLUTION_ID_INCREMENT_VALUE;
        }
        return $next_solution_id;
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
        $query = sprintf(
            "SELECT
                *
            FROM
                %sfaqdata
            ORDER BY id",
            SQLPREFIX);
        $result = $this->db->query($query);
        if ($row = $this->db->fetch_object($result)) {
            $this->faqRecords[] = array(
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

        $query = sprintf(
            "SELECT
                thema
            FROM
                %sfaqdata
            WHERE
                id = %d AND lang = '%s'",
            SQLPREFIX,
            $id,
            $this->language);
        $result = $this->db->query($query);
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
     * Gets all revisions from a given record ID
     *
     * @param    integer    $record_id
     * @param    string    $record_lang
     * @return   array
     * @access   public
     * @since    2006-07-24
     * @author   Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    function getRevisionIds($record_id, $record_lang)
    {
        $revision_data = array();
        $query = sprintf("
            SELECT
                revision_id, datum, author
            FROM
                %sfaqdata_revisions
            WHERE
                id = %d
            and
                lang = '%s'
            ORDER BY
                revision_id",
            SQLPREFIX,
            $record_id,
            $record_lang);
        $result = $this->db->query($query);
        if ($this->db->num_rows($result) > 0) {
            while ($row = $db->fetch_object($result)) {
                $revision_data[] = array(
                    'revision_id'   => $row->revision_id,
                    'datum'         => $row->datum,
                    'author'        => $row->author);
            }
        } else {
            return $revision_data;
        }
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

        $query = sprintf(
            "SELECT
                keywords
            FROM
                %sfaqdata
            WHERE id = %d AND lang = '%s'",
            SQLPREFIX,
            $id,
            $this->language);
        $result = $this->db->query($query);
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
        $query = sprintf(
            "SELECT
                id
            FROM
                %sfaqdata
            WHERE
                active = 'yes' AND lang = '%s'",
            SQLPREFIX,
            $this->language);
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
        $query = sprintf(
            "SELECT
                visits
            FROM
                %sfaqvisits
            WHERE
                id = %d AND lang = '%s'",
            SQLPREFIX,
            $id,
            $this->language);

        if ($result = $this->db->query($query)) {
            $row = $this->db->fetch_object($result);
            $nVisits = $row->visits;
        }
        if ($nVisits == 0 || $nVisits == '') {
            $this->createNewVisit($id, $this->language);
        } else {
            $this->updateVisit($id);
        }
    }

    /**
    * getVotingResult()
    *
    * Calculates the rating of the user votings
    *
    * @param    integer    $id
    * @return   string
    * @access   public
    * @since    2002-08-29
    * @author   Thorsten Rinne <thorsten@phpmyfaq.de>
    */
    function getVotingResult($id)
    {
        $query = sprintf(
            'SELECT
                (vote/usr) as voting, usr
            FROM
                %sfaqvoting
            WHERE
                artikel = %d',
            SQLPREFIX,
            $id);
       $result = $this->db->query($query);
       if ($this->db->num_rows($result) > 0) {
            $row = $this->db->fetch_object($result);
            return sprintf(' %s %s 5 (%d %s)',
                round($row->voting, 2),
                $this->pmf_lang['msgVoteFrom'],
                $row->usr,
                $this->pmf_lang['msgVotings']);
       } else {
            return sprintf(' 0 %s 5 (0 %s)',
                $this->pmf_lang['msgVoteFrom'],
                $this->pmf_lang['msgVotings']);
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
        $oComment = new PMF_Comment($this->db, $this->language);
        return $oComment->getComments($id, PMF_COMMENT_TYPE_FAQ);
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
                $output .= sprintf('<li><strong>%d %s:</strong><br />',
                    $row['visits'],
                    $this->pmf_lang['msgViews']);
                $shortTitle = makeShorterText(PMF_htmlentities($row['thema'], ENT_NOQUOTES, $this->pmf_lang['metaCharset']), 8);
                $output .= sprintf('<a href="%s">%s</a></li>',
                    $row['url'],
                    $shortTitle);
            }
            $output .= '</ol>';
        } else {
            $output = $this->pmf_lang['err_noTopTen'];
        }
        return $output;
    }

    /**
    * getLatest()
    *
    * This function generates the list with the latest published records
    *
    * @return   string
    * @access   public
    * @author   Thorsten Rinne <thorsten@phpmyfaq.de>
    * @since    2002-05-07
    */
    function getLatest()
    {
        $result = $this->getLatestData(PMF_NUMBER_RECORDS_LATEST);
        if (count ($result) > 0) {
            $output = '<ol>';
            foreach ($result as $row) {
                $shortTitle = makeShorterText(PMF_htmlentities($row['thema'], ENT_NOQUOTES, $this->pmf_lang['metaCharset']), 8);
                $output .= sprintf('<li><a href="%s">%s</a> (%s)</li>',
                    $row['url'],
                    $shortTitle,
                    makeDate($row['datum']));
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
'            SELECT
                '.SQLPREFIX.'faqdata.id AS id,
                '.SQLPREFIX.'faqdata.lang AS lang,
                '.SQLPREFIX.'faqdata.thema AS thema,
                '.SQLPREFIX.'faqdata.datum AS datum,
                '.SQLPREFIX.'faqcategoryrelations.category_id AS category_id,
                '.SQLPREFIX.'faqvisits.visits AS visits,
                '.SQLPREFIX.'faqvisits.last_visit AS last_visit
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
                $data['date'] = $row->datum;
                $data['last_visit'] = $row->last_visit;

                $title = PMF_htmlentities($row->thema, ENT_NOQUOTES, $this->pmf_lang['metaCharset']);
                $url   = sprintf('%saction=artikel&amp;cat=%d&amp;id=%d&amp;artlang=%s',
                        $sids,
                        $row->category_id,
                        $row->id,
                        $row->lang
                        );
                $oLink = new PMF_Link(PMF_Link::getSystemRelativeUri().'?'.$url);
                $oLink->itemTitle = $row->thema;
                $oLink->tooltip = $title;
                $data['url'] = $oLink->toString();

                $topten[] = $data;
                $i++;
            }
            $oldId = $row->id;
        }

        return $topten;
    }

    /**
    * getLatestData()
    *
    * This function generates an array with a specified number of most recent
    * published records
    *
    * @param    integer
    * @return   array
    * @access   public
    * @author   Robin Wood <robin@digininja.org>
    * @since    2005-03-06
    */
    function getLatestData($count = PMF_NUMBER_RECORDS_LATEST)
    {
        global $sids, $PMF_CONF;
        $query =
'            SELECT
                '.SQLPREFIX.'faqdata.id AS id,
                '.SQLPREFIX.'faqdata.lang AS lang,
                '.SQLPREFIX.'faqcategoryrelations.category_id AS category_id,
                '.SQLPREFIX.'faqdata.thema AS thema,
                '.SQLPREFIX.'faqdata.content AS content,
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
                $data['content'] = $row->content;
                $data['visits'] = $row->visits;

                $title = PMF_htmlentities($row->thema, ENT_NOQUOTES, $this->pmf_lang['metaCharset']);
                $url   = sprintf('%saction=artikel&amp;cat=%d&amp;id=%d&amp;artlang=%s',
                        $sids,
                        $row->category_id,
                        $row->id,
                        $row->lang
                        );
                $oLink = new PMF_Link(PMF_Link::getSystemRelativeUri().'?'.$url);
                $oLink->itemTitle = $row->thema;
                $oLink->tooltip = $title;
                $data['url'] = $oLink->toString();

                $latest[] = $data;
                $i++;
            }
            $oldId = $row->id;
        }

        return $latest;
    }

    /**
     * votingCheck()
     *
     * Reload locking for user votings
     *
     * @param    integer     FAQ record id
     * @param    string      IP
     * @return   boolean
     * @since    2003-05-15
     * @author   Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    function votingCheck($id, $ip)
    {
        $check = time() - 300;
        $query = sprintf(
            "SELECT
                id
            FROM
                %sfaqvoting
            WHERE
                artikel = %d AND (ip = '%s' AND datum > %d)",
            SQLPREFIX,
            $id,
            $ip,
            $check);
        if ($db->num_rows($db->query($query))) {
            return false;
        }
        return true;
    }

    /**
     * getNumberOfVotings()
     *
     * Returns the number of users from the table faqvotings
     *
     * @param   integer $record_id
     * @return  integer
     * @access  public
     * @since   2006-06-18
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    function getNumberOfVotings($record_id)
    {
        $query = sprintf(
            'SELECT
                usr
            FROM
                %sfaqvoting
            WHERE
                artikel = %d',
            SQLPREFIX,
            $record_id);
        if ($result = $this->db->query($query)) {
            $row = $db->fetch_object($result);
            return $row->usr;
        }
        return 0;
    }

    /**
     * addVoting()
     *
     * Adds a new voting record
     *
     * @param    array    $votingData
     * @return   boolean
     * @access   public
     * @since    2006-06-18
     * @author   Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    function addVoting($votingData)
    {
        if (!is_array($votingData)) {
            return false;
        }

        $query = sprintf(
            "INSERT INTO
                %sfaqvoting
            VALUES
                (%d, %d, %d, 1, %d, '%s')",
            SQLPREFIX,
            $this->db->nextID(SQLPREFIX.'faqvoting', 'id'),
            $votingData['record_id'],
            $votingData['vote'],
            time(),
            $votingData['user_ip']);
        $this->db->query($query);

        return true;
    }

    /**
     * updateVoting()
     *
     * Updates an existing voting record
     *
     * @param    array    $votingData
     * @return   boolean
     * @access   public
     * @since    2006-06-18
     * @author   Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    function updateVoting($votingData)
    {
        if (!is_array($votingData)) {
            return false;
        }

        $query = sprintf(
            "UPDATE
                %sfaqvoting
            SET
                vote    = vote + %d,
                usr     = usr + 1,
                datum   = %d,
                ip      = '%s'
            WHERE
                artikel = %d",
            SQLPREFIX,
            $votingData['vote'],
            time(),
            $votingData['user_ip'],
            $votingData['record_id']);
        $this->db->query($query);

        return true;
    }

    /**
     * Adds a new changelog entry
     *
     *
     */

    /**
     * createNewVisit()
     *
     * Adds a new entry in the table faqvisits
     *
     * @param   integer $id
     * @param   string    $lang
     * @return  boolean
     * @access  private
     * @since   2006-06-18
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    function createNewVisit($id, $lang)
    {
        if (!is_numeric($id) && !is_string($lang)) {
            return false;
        }

        $query = sprintf(
            "INSERT INTO
                %sfaqvisits
            VALUES
                (%d, '%s', %d, %d)",
            SQLPREFIX,
            $id,
            $lang,
            1,
            time());
        $this->db->query($query);

        return true;
    }

    /**
     * updateVisit()
     *
     * Updates an entry in the table faqvisits
     *
     * @param    integer    $id
     * @return    boolean
     * @access    private
     * @since    2006-06-18
     * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    function updateVisit($id)
    {
        if (!is_numeric($id)) {
            return false;
        }

        $query = sprintf(
            "UPDATE
                %sfaqvisits
            SET
                visits = visits+1,
                last_visit = %d
            WHERE
                id = %d AND lang = '%s'",
            SQLPREFIX,
            time(),
            $id,
            $this->language);
        $this->db->query($query);

        return true;
    }

    /**
     * get()
     *
     * Retrieve faq records according to the constraints provided
     *
     * @param   $QueryType
     * @param   $nCatid
     * @param   $bDownwards
     * @param   $lang
     * @param   $date
     * @return  array
     * @access  public
     * @since   2005-11-02
     * @author  Matteo Scaramuccia <matteo@scaramuccia.com>
     */
    function get($QueryType = FAQ_QUERY_TYPE_DEFAULT, $nCatid = 0, $bDownwards = true, $lang = "", $date = "")
    {
        $faqs = array();

        $result = $this->db->query($this->_getSQLQuery($QueryType, $nCatid, $bDownwards, $lang, $date));
        // ----------------------------------------------------------------------------------------------------------------------
        // id | solution_id | revision_id | lang | category_id | active | keywords | thema | content | author | email | comment | datum | visits | last_visit
        // ----------------------------------------------------------------------------------------------------------------------
        if ($this->db->num_rows($result) > 0) {
            $i = 0;
            while ($row = $this->db->fetch_object($result)) {
                $faq = array();
                $faq['id']             = $row->id;
                $faq['solution_id']    = $row->solution_id;
                $faq['revision_id']    = $row->revision_id;
                $faq['lang']           = $row->lang;
                $faq['category_id']    = $row->category_id;
                $faq['active']         = $row->active;
                $faq['keywords']       = $row->keywords;
                $faq['topic']          = $row->thema;
                $faq['content']        = $row->content;
                $faq['author_name']    = $row->author;
                $faq['author_email']   = $row->email;
                $faq['comment_enable'] = $row->comment;
                $faq['lastmodified']   = $row->datum;
                $faq['hits']           = $row->visits;
                $faq['hits_last']      = $row->last_visit;
                $faqs[$i] = $faq;
                $i++;
            }
        }

        return $faqs;
    }

    /**
     * _getCatidWhereSequence()
     *
     * Build a logic sequence, for a WHERE statement, of those category IDs children of the provided category ID, if any
     *
     * @param   $nCatid
     * @param   $logicOp
     * @param   $oCat
     * @return  string
     * @access  private
     * @since   2005-11-02
     * @author  Matteo Scaramuccia <matteo@scaramuccia.com>
     */
    function _getCatidWhereSequence($nCatid, $logicOp = "OR", $oCat = NULL)
    {
        $sqlWherefilter = "";

        if (!isset($oCat)) {
            $oCat  = new PMF_Category();
        }
        $aChildren = array_values($oCat->getChildren($nCatid));

        foreach ($aChildren as $catid) {
            $sqlWherefilter .= " ".$logicOp." ".SQLPREFIX."faqcategoryrelations.category_id = '".$catid."'";
            $sqlWherefilter .= $this->_getCatidWhereSequence($catid, "OR", $oCat);
        }

        return $sqlWherefilter;
    }

    /**
     * _getSQLQuery()
     *
     * Build the SQL query for retrieving faq records according to the constraints provided
     *
     * @param   $QueryType
     * @param   $nCatid
     * @param   $bDownwards
     * @param   $lang
     * @param   $date
     * @param   $faqid
     * @return  array
     * @access  private
     * @since   2005-11-02
     * @author  Matteo Scaramuccia <matteo@scaramuccia.com>
     */
    function _getSQLQuery($QueryType, $nCatid, $bDownwards, $lang, $date, $faqid = 0)
    {
        global $DB;

        $sql = "";
        // Fields selection
        // --------------------------------------------------------------------------------------------------------------------------------------------------
        // id | solution_id | revision_id | lang | category_id | active | keywords | thema | content | author | email | comment | datum | visits | last_visit
        // --------------------------------------------------------------------------------------------------------------------------------------------------
        $sql  = "SELECT
              ".SQLPREFIX."faqdata.id AS id,
              ".SQLPREFIX."faqdata.solution_id AS solution_id,
              ".SQLPREFIX."faqdata.revision_id AS revision_id,
              ".SQLPREFIX."faqdata.lang AS lang,
              ".SQLPREFIX."faqcategoryrelations.category_id AS category_id,
              ".SQLPREFIX."faqdata.active AS active,
              ".SQLPREFIX."faqdata.keywords AS keywords,
              ".SQLPREFIX."faqdata.thema AS thema,
              ".SQLPREFIX."faqdata.content AS content,
              ".SQLPREFIX."faqdata.author AS author,
              ".SQLPREFIX."faqdata.email AS email,
              ".SQLPREFIX."faqdata.comment AS comment,
              ".SQLPREFIX."faqdata.datum AS datum,
              ".SQLPREFIX."faqvisits.visits AS visits,
              ".SQLPREFIX."faqvisits.last_visit AS last_visit
              "."FROM ".SQLPREFIX."faqdata, ".SQLPREFIX."faqvisits";
        // Join criteria
        // TODO: why LEFT? Ask to Thorsten: it would be better INNER
        $sql .= "\nLEFT JOIN ".SQLPREFIX."faqcategoryrelations
              ON ".SQLPREFIX."faqdata.id = ".SQLPREFIX."faqcategoryrelations.record_id
              AND ".SQLPREFIX."faqdata.lang = ".SQLPREFIX."faqcategoryrelations.record_lang";
        // Filter criteria
        $sql .= "\nWHERE ";
        // faqvisits data selection
        if (!empty($faqid)) {
            // Select ONLY the faq with the provided $faqid
            $sql .= SQLPREFIX."faqdata.id = '".$faqid."' AND ";
        }
        $sql .= SQLPREFIX."faqdata.id = ".SQLPREFIX."faqvisits.id
              AND ".SQLPREFIX."faqdata.lang = ".SQLPREFIX."faqvisits.lang";
        $needAndOp = true;
        if ((!empty($nCatid)) && (PMF_Utils::isInteger($nCatid)) && ($nCatid > 0)) {
            if ($needAndOp) {
                $sql .= " AND";
            }
            $sql .= " (".SQLPREFIX."faqcategoryrelations.category_id = '".$nCatid."'";
            if ($bDownwards) {
                $sql .= $this->_getCatidWhereSequence($nCatid, "OR");
            }
            $sql .= ")";
            $needAndOp = true;
        }
        if ((!empty($date)) && PMF_Utils::isLikeOnPMFDate($date)) {
            if ($needAndOp) {
                $sql .= " AND";
            }
            $sql .= " ".SQLPREFIX."faqdata.datum LIKE '".$date."'";
            $needAndOp = true;
        }
        if ((!empty($lang)) && PMF_Utils::isLanguage($lang)) {
            if ($needAndOp) {
                $sql .= " AND";
            }
            $sql .= " ".SQLPREFIX."faqdata.lang = '".$lang."'";
            $needAndOp = true;
        }
        switch ($QueryType) {
            case FAQ_QUERY_TYPE_APPROVAL:
                if ($needAndOp) {
                    $sql .= " AND";
                }
                $sql .= " ".SQLPREFIX."faqdata.active = '".FAQ_SQL_ACTIVE_NO."'";
                $needAndOp = true;
                break;
            case FAQ_QUERY_TYPE_EXPORT_DOCBOOK:
            case FAQ_QUERY_TYPE_EXPORT_PDF:
            case FAQ_QUERY_TYPE_EXPORT_XHTML:
            case FAQ_QUERY_TYPE_EXPORT_XML:
                if ($needAndOp) {
                    $sql .= " AND";
                }
                $sql .= " ".SQLPREFIX."faqdata.active = '".FAQ_SQL_ACTIVE_YES."'";
                $needAndOp = true;
                break;
            default:
                if ($needAndOp) {
                    $sql .= " AND";
                }
                $sql .= " ".SQLPREFIX."faqdata.active = '".FAQ_SQL_ACTIVE_YES."'";
                $needAndOp = true;
                break;
        }
        // Sort criteria
        switch ($QueryType) {
            case FAQ_QUERY_TYPE_EXPORT_DOCBOOK:
            case FAQ_QUERY_TYPE_EXPORT_PDF:
            case FAQ_QUERY_TYPE_EXPORT_XHTML:
            case FAQ_QUERY_TYPE_EXPORT_XML:
                // Preferred ordering: Sitemap-like
                // TODO: see if this sort is compatible with the current set of indexes
                $sql .= "\nORDER BY ".SQLPREFIX."faqdata.thema";
                break;
            case FAQ_QUERY_TYPE_RSS_LATEST:
                $sql .= "\nORDER BY ".SQLPREFIX."faqdata.datum DESC";
                break;
            default:
                // Normal ordering
                $sql .= "\nORDER BY ".SQLPREFIX."faqcategoryrelations.category_id, ".SQLPREFIX."faqdata.id";
                break;
        }

        return $sql;
    }
}
// }}}
