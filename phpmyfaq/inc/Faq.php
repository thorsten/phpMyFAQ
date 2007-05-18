<?php
/**
 * $Id: Faq.php,v 1.113.2.2 2007-05-18 21:10:30 thorstenr Exp $
 *
 * The main FAQ class
 *
 * @author       Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author       Matteo Scaramuccia <matteo@scaramuccia.com>
 * @package      phpMyFAQ
 * @since        2005-12-20
 * @copyright    (c) 2005-2007 phpMyFAQ Team
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
require_once(PMF_INCLUDE_DIR.'/Comment.php');
/**
 * This include is needed for accessing to mod_rewrite support configuration value
 */
require_once(PMF_INCLUDE_DIR.'/Link.php');
/**
 * This include is needed in _getSQLQuery() private method
 */
require_once(PMF_INCLUDE_DIR.'/Utils.php');
// }}}

// {{{ Constants
/**
 * SQL constants definitions
 */
define('FAQ_SQL_YES',        'y');
define('FAQ_SQL_NO',         'n');
define('FAQ_SQL_ACTIVE_YES', 'yes');
define('FAQ_SQL_ACTIVE_NO',  'no');

/**
 * Query type definitions
 */
define('FAQ_QUERY_TYPE_DEFAULT',        'faq_default');
define('FAQ_QUERY_TYPE_APPROVAL',       'faq_approval');
define('FAQ_QUERY_TYPE_EXPORT_DOCBOOK', 'faq_export_docbook');
define('FAQ_QUERY_TYPE_EXPORT_PDF',     'faq_export_pdf');
define('FAQ_QUERY_TYPE_EXPORT_XHTML',   'faq_export_xhtml');
define('FAQ_QUERY_TYPE_EXPORT_XML',     'faq_export_xml');
define('FAQ_QUERY_TYPE_RSS_LATEST',     'faq_rss_latest');

/**
 * Sorting type definitions
 */
define('FAQ_SORTING_TYPE_NONE', 0);
define('FAQ_SORTING_TYPE_CATID_FAQID', 1);
define('FAQ_SORTING_TYPE_FAQTITLE_FAQID', 2);
define('FAQ_SORTING_TYPE_DATE_FAQID', 3);
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
     * Users
     *
     * @var array
     */
    var $user = null;

    /**
     * Groups
     *
     * @var array
     */
    var $groups = array();

    /**
     * Constructor
     *
     * @param   object  $db
     * @param   string  $language
     * @param   integer $user
     * @param   array   $groups
     */
    function PMF_Faq(&$db, $language, $user = null, $groups = null)
    {
        global $PMF_LANG;

        $this->db = &$db;
        $this->language = $language;
        $this->pmf_lang = $PMF_LANG;

        if (is_null($user)) {
            $this->user  = -1;
        } else {
            $this->user  = $user;
        }
        if (is_null($groups)) {
            $this->groups = array(-1);
        } else {
            $this->groups = $groups;
        }
    }

    //
    //
    // PUBLIC METHODS
    //
    //

    /**
     * This function returns all not expired records from one category
     *
     * @param   int     $category id
     * @param   string  $orderby
     * @param   string  $sortby
     * @return  string
     * @access  public
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     * @since   2002-08-27
     */
    function showAllRecords($category_id, $orderby = 'id', $sortby = 'ASC')
    {
        global $sids, $PMF_CONF, $category;

        $page = 1;
        $output = '';

        if (isset($_REQUEST['seite'])) {
            $page = (int)$_REQUEST['seite'];
        }

        if ($orderby == 'visits') {
            $current_table = 'fv';
        } else {
            $current_table = 'fd';
        }

        $now = date('YmdHis');
        $query = sprintf("
            SELECT
                fd.id AS id,
                fd.lang AS lang,
                fd.thema AS thema,
                fcr.category_id AS category_id,
                fv.visits AS visits
            FROM
                %sfaqdata AS fd
            LEFT JOIN
                %sfaqcategoryrelations AS fcr
            ON
                fd.id = fcr.record_id
            AND
                fd.lang = fcr.record_lang
            LEFT JOIN
                %sfaqvisits AS fv
            ON
                fd.id = fv.id
            AND
                fv.lang = fd.lang
            LEFT JOIN
                %sfaqdata_group AS fdg
            ON
                fd.id = fdg.record_id
            LEFT JOIN
                %sfaqdata_user AS fdu
            ON
                fd.id = fdu.record_id
            WHERE
                fd.date_start <= '%s'
            AND
                fd.date_end   >= '%s'
            AND
                fd.active = 'yes'
            AND
                fcr.category_id = %d
            AND
                fd.lang = '%s'
            AND
                ( fdg.group_id IN (%s)
            OR
                (fdu.user_id = %d AND fdg.group_id IN (%s)))
            ORDER BY
                %s.%s %s",
            SQLPREFIX,
            SQLPREFIX,
            SQLPREFIX,
            SQLPREFIX,
            SQLPREFIX,
            $now,
            $now,
            $category_id,
            $this->language,
            implode(', ', $this->groups),
            $this->user,
            implode(', ', $this->groups),
            $current_table,
            $this->db->escape_string($orderby),
            $this->db->escape_string($sortby));

        $result = $this->db->query($query);

        $num = $this->db->num_rows($result);
        $pages = ceil($num / $PMF_CONF["main.numberOfRecordsPerPage"]);

        if ($page == 1) {
            $first = 0;
        } else {
            $first = ($page * $PMF_CONF["main.numberOfRecordsPerPage"]) - $PMF_CONF["main.numberOfRecordsPerPage"];
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
            while (($row = $this->db->fetch_object($result)) && $displayedCounter < $PMF_CONF['main.numberOfRecordsPerPage']) {
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

                $title = PMF_htmlentities($row->thema, ENT_QUOTES, $this->pmf_lang['metaCharset']);
                $url   = sprintf('%saction=artikel&amp;cat=%d&amp;id=%d&amp;artlang=%s',
                            $sids,
                            $row->category_id,
                            $row->id,
                            $row->lang
                        );
                $oLink = new PMF_Link(PMF_Link::getSystemRelativeUri().'?'.$url);
                $oLink->itemTitle = $row->thema;
                $oLink->text = $title;
                $oLink->tooltip = $title;
                $listItem = sprintf('<li>%s<br /><span class="little">(%d %s)</span></li>',
                    $oLink->toHtmlAnchor(),
                    $visits,
                    $this->pmf_lang['msgViews']);

                $output .= $listItem;
            }
            $output .= '</ul><span id="totFaqRecords" style="display: none;">'.$num.'</span>';
        } else {
            return false;
        }

        $categoryName = 'CategoryId-'.$category_id;
        if (isset($category)) {
            $categoryName = $category->categoryName[$category_id]['name'];
        }
        if ($pages > 1) {
            $output .= "<p align=\"center\"><strong>";
            $previous = $page - 1;
            $next = $page + 1;

            if ($previous != 0) {
                $title = $this->pmf_lang['msgPrevious'];
                $url   = sprintf('%saction=show&amp;cat=%d&amp;seite=%d',
                            $sids,
                            $category_id,
                            $previous);
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
                            $category_id,
                            $i);
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
                            $category_id,
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
     * This function returns all not expired records from the given record ids
     *
     * @param   array   $record_ids
     * @param   string  $orderby
     * @param   string  $sortby
     * @return  string
     * @access  public
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     * @since   2007-04-20
     */
    function showAllRecordsByIds($record_ids, $orderby = 'id', $sortby = 'ASC')
    {
        global $sids, $PMF_CONF, $category;

        $records = implode(', ', $record_ids);
        $page    = 1;
        $output  = '';

        if (isset($_REQUEST['seite'])) {
            $page = (int)$_REQUEST['seite'];
        }

        $now = date('YmdHis');
        $query = sprintf("
            SELECT
                fd.id AS id,
                fd.lang AS lang,
                fd.thema AS thema,
                fcr.category_id AS category_id,
                fv.visits AS visits
            FROM
                %sfaqdata AS fd
            LEFT JOIN
                %sfaqcategoryrelations AS fcr
            ON
                fd.id = fcr.record_id
            AND
                fd.lang = fcr.record_lang
            LEFT JOIN
                %sfaqvisits AS fv
            ON
                fd.id = fv.id
            AND
                fv.lang = fd.lang
            LEFT JOIN
                %sfaqdata_group AS fdg
            ON
                fd.id = fdg.record_id
            LEFT JOIN
                %sfaqdata_user AS fdu
            ON
                fd.id = fdu.record_id
            WHERE
                fd.date_start <= '%s'
            AND
                fd.date_end   >= '%s'
            AND
                fd.active = 'yes'
            AND
                fd.id IN (%s)
            AND
                fd.lang = '%s'
            AND
                ( fdg.group_id IN (%s)
            OR
                (fdu.user_id = %d AND fdg.group_id IN (%s)))
            ORDER BY
                %s %s",
            SQLPREFIX,
            SQLPREFIX,
            SQLPREFIX,
            SQLPREFIX,
            SQLPREFIX,
            $now,
            $now,
            $records,
            $this->language,
            implode(', ', $this->groups),
            $this->user,
            implode(', ', $this->groups),
            $this->db->escape_string($orderby),
            $this->db->escape_string($sortby));

        $result = $this->db->query($query);

        $num = $this->db->num_rows($result);
        $pages = ceil($num / $PMF_CONF["main.numberOfRecordsPerPage"]);

        if ($page == 1) {
            $first = 0;
        } else {
            $first = ($page * $PMF_CONF["main.numberOfRecordsPerPage"]) - $PMF_CONF["main.numberOfRecordsPerPage"];
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
            while (($row = $this->db->fetch_object($result)) && $displayedCounter < $PMF_CONF['main.numberOfRecordsPerPage']) {
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

                $title = PMF_htmlentities($row->thema, ENT_QUOTES, $this->pmf_lang['metaCharset']);
                $url   = sprintf('%saction=artikel&amp;cat=%d&amp;id=%d&amp;artlang=%s',
                            $sids,
                            $row->category_id,
                            $row->id,
                            $row->lang
                        );
                $oLink = new PMF_Link(PMF_Link::getSystemRelativeUri().'?'.$url);
                $oLink->itemTitle = $row->thema;
                $oLink->text = $title;
                $oLink->tooltip = $title;
                $listItem = sprintf('<li>%s<br /><span class="little">(%d %s)</span></li>',
                    $oLink->toHtmlAnchor(),
                    $visits,
                    $this->pmf_lang['msgViews']);

                $output .= $listItem;
            }
            $output .= '</ul><span id="totFaqRecords" style="display: none;">'.$num.'</span>';
        } else {
            return false;
        }

       return $output;
    }

    /**
     * Returns an array with all data from a FAQ record
     *
     * @param    integer    record id
     * @param    integer    revision id
     * @param    boolean    must be true if it is called by an admin/author context
     * @return   void
     * @access   public
     * @since    2005-12-20
     * @author   Thorsten Rinne <thorsten@phpmyfaq.de>
     * @author   Matteo Scaramuccia <matteo@scaramuccia.com>
     */
    function getRecord($id, $revision_id = null, $admin = false)
    {
        global $PMF_LANG;

        $query = sprintf(
            "SELECT
                 id, lang, solution_id, revision_id, active, keywords, thema,
                 content, author, email, comment, datum, links_state,
                 links_check_date, date_start, date_end
            FROM
                %s%s fd
            LEFT JOIN
                %sfaqdata_group
            ON
                fd.id = %sfaqdata_group.record_id
            LEFT JOIN
                %sfaqdata_user
            ON
                fd.id = %sfaqdata_user.record_id
            WHERE
                id = %d
            %s
            AND
                lang = '%s'",
            SQLPREFIX,
            isset($revision_id) ? 'faqdata_revisions': 'faqdata',

            SQLPREFIX,
            SQLPREFIX,
            SQLPREFIX,
            SQLPREFIX,
            $id,
            isset($revision_id) ? 'AND revision_id = '.$revision_id : '',
            $this->language);

        $result = $this->db->query($query);

        if ($row = $this->db->fetch_object($result)) {

            $content        = $row->content;
            $active         = ('yes' == $row->active);
            $expired        = (date('YmdHis') > $row->date_end);

            if (!$admin) {
                if (!$active) {
                    $content = $this->pmf_lang['err_inactiveArticle'];
                }
                if ($expired) {
                    $content = $this->pmf_lang['err_expiredArticle'];
                }
            }

            $this->faqRecord = array(
                'id'            => $row->id,
                'lang'          => $row->lang,
                'solution_id'   => $row->solution_id,
                'revision_id'   => $row->revision_id,
                'active'        => $row->active,
                'keywords'      => $row->keywords,
                'title'         => $row->thema,
                'content'       => $content,
                'author'        => $row->author,
                'email'         => $row->email,
                'comment'       => $row->comment,
                'date'          => makeDate($row->datum),
                'dateStart'     => $row->date_start,
                'dateEnd'       => $row->date_end,
                'linkState'     => $row->links_state,
                'linkCheckDate' => $row->links_check_date
                );
        } else {
            $this->faqRecord = array(
                'id'            => $id,
                'lang'          => $this->language,
                'solution_id'   => 42,
                'revision_id'   => 0,
                'active'        => 'no',
                'keywords'      => '',
                'title'         => '',
                'content'       => $PMF_LANG['err_inactiveArticle'],
                'author'        => '',
                'email'         => '',
                'comment'       => '',
                'date'          => makeDate(date('YmdHis')),
                'dateStart'     => '',
                'dateEnd'       => '',
                'linkState'     => '',
                'linkCheckDate' => ''
                );
        }
    }

    /**
     * Adds a new record
     *
     * @param    array    $data
     * @param    boolean  $new_record
     * @return   integer
     * @access   public
     * @since    2006-06-18
     * @author   Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    function addRecord($data, $new_record = true)
    {
        if (!is_array($data)) {
            return false;
        }

        if ($new_record) {
            $record_id = $this->db->nextID(SQLPREFIX.'faqdata', 'id');
        } else {
            $record_id = $data['id'];
        }

        // Add new entry
        $query = sprintf(
            "INSERT INTO
                %sfaqdata
             (id, lang, solution_id, revision_id, active, keywords, thema, content, author, email, comment, datum, links_state, links_check_date, date_start, date_end)
                VALUES
            (%d, '%s', %d, %d, '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', %d, '%s', '%s')",
            SQLPREFIX,
            $record_id,
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
            $data['date'],
            $data['linkState'],
            $data['linkDateCheck'],
            $data['dateStart'],
            $data['dateEnd']);

        $this->db->query($query);
        return $record_id;
    }

    /**
     * Updates a record
     *
     * @param    array    $data
     * @return   boolean
     * @access   public
     * @since    2006-06-18
     * @author   Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    function updateRecord($data)
    {
        if (!is_array($data)) {
            return false;
        }

        // Update entry
        $query = sprintf("
            UPDATE
                %sfaqdata
            SET
                revision_id = %d,
                active = '%s',
                keywords = '%s',
                thema = '%s',
                content = '%s',
                author = '%s',
                email = '%s',
                comment = '%s',
                datum = '%s',
                links_state = '%s',
                links_check_date = %d,
                date_start = '%s',
                date_end = '%s'
            WHERE
                id = %d
            AND
                lang = '%s'",
            SQLPREFIX,
            $data['revision_id'],
            $data['active'],
            $data['keywords'],
            $data['thema'],
            $data['content'],
            $data['author'],
            $data['email'],
            $data['comment'],
            $data['date'],
            $data['linkState'],
            $data['linkDateCheck'],
            $data['dateStart'],
            $data['dateEnd'],
            $data['id'],
            $data['lang']);

        $this->db->query($query);
        return true;
    }

    /**
     * Deletes a record and all the dependencies
     *
     * @param   integer $record_id
     * @param   string  $record_lang
     * @return  boolean
     * @access  public
     * @since   2006-11-04
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    function deleteRecord($record_id, $record_lang)
    {
        $queries = array(
            sprintf("DELETE FROM %sfaqchanges WHERE beitrag = %d AND lang = '%s'",
                SQLPREFIX, $record_id, $record_lang),
            sprintf("DELETE FROM %sfaqcategoryrelations WHERE record_id = %d AND record_lang = '%s'",
                SQLPREFIX, $record_id, $record_lang),
            sprintf("DELETE FROM %sfaqdata WHERE id = %d AND lang = '%s'",
                SQLPREFIX, $record_id, $record_lang),
            sprintf("DELETE FROM %sfaqdata_revisions WHERE id = %d AND lang = '%s'",
                SQLPREFIX, $record_id, $record_lang),
            sprintf("DELETE FROM %sfaqvisits WHERE id = %d AND lang = '%s'",
                SQLPREFIX, $record_id, $record_lang),
            sprintf("DELETE FROM %faqcategory_user WHERE record_id = %d AND record_lang = '%s'",
                SQLPREFIX, $record_id, $record_lang),
            sprintf("DELETE FROM %faqcategory_group WHERE record_id = %d AND record_lang = '%s'",
                SQLPREFIX, $record_id, $record_lang),
            sprintf("DELETE FROM %faqdata_user WHERE record_id = %d AND record_lang = '%s'",
                SQLPREFIX, $record_id, $record_lang),
            sprintf("DELETE FROM %faqdata_group WHERE record_id = %d AND record_lang = '%s'",
                SQLPREFIX, $record_id, $record_lang),
            sprintf("DELETE FROM %faqdata_tags WHERE record_id = %d",
                SQLPREFIX, $record_id),
            sprintf("DELETE FROM %faqcomments WHERE id = %d",
                SQLPREFIX, $record_id),
            sprintf("DELETE FROM %faqvoting WHERE artikel = %d",
                SQLPREFIX, $record_id));

         foreach($queries as $query) {
            $this->db->query($query);
         }

         return true;
    }

    /**
     * Checks if a record is already translated
     *
     * @param   integer $record_id
     * @param   string  $record_lang
     * @return  boolean
     * @access  public
     * @since   2006-11-04
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    function isAlreadyTranslated($record_id, $record_lang)
    {
        $query = sprintf("
            SELECT
                id, lang
            FROM
                %sfaqdata
            WHERE
                id = %d
            AND
                lang = '%s'",
            SQLPREFIX,
            $record_id,
            $record_lang);

        $result = $this->db->query($query);

        if ($this->db->num_rows($result)) {
            return true;
        }

        return false;
    }

    /**
     * Adds new category relations to a record
     *
     * @param   array   $categories
     * @param   integer $record_id
     * @param   string  $language
     * @return  integer
     * @access  public
     * @since   2006-12-31
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     * @author  Matteo Scaramuccia <matteo@scaramuccia.com>
     */
    function addCategoryRelations($categories, $record_id, $language)
    {
        if (!is_array($categories)) {
            return false;
        }

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
     * Adds new category relation to a record
     *
     * @param   mixed   $categories
     * @param   integer $record_id
     * @param   string  $language
     * @return  integer
     * @access  public
     * @since   2006-07-02
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     * @author  Matteo Scaramuccia <matteo@scaramuccia.com>
     */
    function addCategoryRelation($category, $record_id, $language)
    {
        // Just a fallback when (wrong case) $category is an array
        if (is_array($category)) {
            addCategoryRelations($category, $record_id, $language);
        }
        $categories[] = $category;

        return addCategoryRelations($categories, $record_id, $language);
    }

    /**
     * Deletes category relations to a record
     *
     * @param    integer  $record_id
     * @param    string   $language
     * @return   integer
     * @access   public
     * @since    2006-11-01
     * @author   Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    function deleteCategoryRelations($record_id, $record_lang)
    {
        $query = sprintf("
            DELETE FROM
                %sfaqcategoryrelations
            WHERE
                record_id = %d
            AND
                record_lang = '%s'",
            SQLPREFIX,
            $record_id,
            $record_lang);
        $this->db->query($query);

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
            LEFT JOIN
                %sfaqdata_group
            ON
                %sfaqdata.id = %sfaqdata_group.record_id
            LEFT JOIN
                %sfaqdata_user
            ON
                %sfaqdata.id = %sfaqdata_user.record_id
            WHERE
                solution_id = %d",
            SQLPREFIX,
            SQLPREFIX,
            SQLPREFIX,
            SQLPREFIX,
            SQLPREFIX,
            SQLPREFIX,
            SQLPREFIX,
            $solution_id);
        $result = $this->db->query($query);
        if ($row = $this->db->fetch_object($result)) {
            $content        = $row->content;
            $active         = ('yes' == $row->active);
            $expired        = (date('YmdHis') > $row->date_end);

            if (!$active) {
                $content = $this->pmf_lang['err_inactiveArticle'];
            }
            if ($expired) {
                $content = $this->pmf_lang['err_expiredArticle'];
            }

            $this->faqRecord = array(
                'id'            => $row->id,
                'lang'          => $row->lang,
                'solution_id'   => $row->solution_id,
                'revision_id'   => $row->revision_id,
                'active'        => $row->active,
                'keywords'      => $row->keywords,
                'title'         => $row->thema,
                'content'       => $content,
                'author'        => $row->author,
                'email'         => $row->email,
                'comment'       => $row->comment,
                'date'          => makeDate($row->datum),
                'dateStart'     => $row->date_start,
                'dateEnd'       => $row->date_end,
                'linkState'     => $row->links_state,
                'linkCheckDate' => $row->links_check_date
                );
        }
    }

    /**
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
     * Returns an array with all data from all FAQ records
     *
     * @param   integer $sortType
     * @param   array   $condition
     * @param   string  $sortOrder
     * @return  void
     * @access  public
     * @since   2005-12-26
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    function getAllRecords($sortType = FAQ_SORTING_TYPE_CATID_FAQID, $condition = null, $sortOrder = 'ASC')
    {
        $where = '';
        if (!is_null($condition)) {
            $num = count($condition);
            $where = 'WHERE ';
            foreach ($condition as $field => $data) {
                $num--;
                $where .= $field;
                if (is_array($data)) {
                    $where .= " IN (";
                    $separator = "";
                    foreach ($data as $value) {
                        $where .= $separator."'".$this->db->escape_string($value)."'";
                        $separator = ", ";
                    }
                    $where .= ")";
                } else {
                    $where .= " = '".$this->db->escape_string($data)."'";
                }
                if ($num > 0) {
                    $where .= " AND ";
                }
            }
        }

        $orderBy = '';
        switch($sortType)
        {
            case FAQ_SORTING_TYPE_CATID_FAQID:
                $orderBy = sprintf("
            ORDER BY
                %sfaqcategoryrelations.category_id,
                %sfaqdata.id %s",
                    SQLPREFIX,
                    SQLPREFIX,
                    $sortOrder);
                break;

            case FAQ_SORTING_TYPE_FAQTITLE_FAQID:
                $orderBy = sprintf("
            ORDER BY
                %sfaqcategoryrelations.category_id,
                %sfaqdata.thema %s",
                    SQLPREFIX,
                    SQLPREFIX,
                    $sortOrder);
                break;

            case FAQ_SORTING_TYPE_DATE_FAQID:
                $orderBy = sprintf("
            ORDER BY
                %sfaqcategoryrelations.category_id,
                %sfaqdata.datum %s",
                    SQLPREFIX,
                    SQLPREFIX,
                    $sortOrder);
                break;
        }

        $query = sprintf("
            SELECT
                %sfaqdata.id AS id,
                %sfaqdata.lang AS lang,
                %sfaqcategoryrelations.category_id AS category_id,
                %sfaqdata.solution_id AS solution_id,
                %sfaqdata.revision_id AS revision_id,
                %sfaqdata.active AS active,
                %sfaqdata.keywords AS keywords,
                %sfaqdata.thema AS thema,
                %sfaqdata.content AS content,
                %sfaqdata.author AS author,
                %sfaqdata.email AS email,
                %sfaqdata.comment AS comment,
                %sfaqdata.datum AS datum,
                %sfaqdata.links_state AS links_state,
                %sfaqdata.links_check_date AS links_check_date,
                %sfaqdata.date_start AS date_start,
                %sfaqdata.date_end AS date_end
            FROM %sfaqdata
            LEFT JOIN %sfaqcategoryrelations
                ON %sfaqdata.id = %sfaqcategoryrelations.record_id
                AND %sfaqdata.lang = %sfaqcategoryrelations.record_lang
            %s
            %s",
            SQLPREFIX,
            SQLPREFIX,
            SQLPREFIX,
            SQLPREFIX,
            SQLPREFIX,
            SQLPREFIX,
            SQLPREFIX,
            SQLPREFIX,
            SQLPREFIX,
            SQLPREFIX,
            SQLPREFIX,
            SQLPREFIX,
            SQLPREFIX,
            SQLPREFIX,
            SQLPREFIX,
            SQLPREFIX,
            SQLPREFIX,
            SQLPREFIX,
            SQLPREFIX,
            SQLPREFIX,
            SQLPREFIX,
            SQLPREFIX,
            SQLPREFIX,
            $where,
            $orderBy
            );

        $result = $this->db->query($query);

        while ($row = $this->db->fetch_object($result)) {
            $content        = $row->content;
            $active         = ('yes' == $row->active);
            $expired        = (date('YmdHis') > $row->date_end);

            if (!$active) {
                $content = $this->pmf_lang['err_inactiveArticle'];
            }
            if ($expired) {
                $content = $this->pmf_lang['err_expiredArticle'];
            }

            $this->faqRecords[] = array(
                'id'            => $row->id,
                'category_id'   => $row->category_id,
                'lang'          => $row->lang,
                'solution_id'   => $row->solution_id,
                'revision_id'   => $row->revision_id,
                'active'        => $row->active,
                'keywords'      => $row->keywords,
                'title'         => PMF_htmlentities($row->thema, ENT_QUOTES, $this->pmf_lang['metaCharset']),
                'content'       => $content,
                'author'        => $row->author,
                'email'         => $row->email,
                'comment'       => $row->comment,
                'date'          => makeDate($row->datum),
                'dateStart'     => $row->date_start,
                'dateEnd'       => $row->date_end
                );
        }
    }

    /**
     * Returns the FAQ record title from the ID and language
     *
     * @param   integer     record id
     * @param   bool        Fix html special chars? (default, true)
     * @return  string
     * @since   2002-08-28
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    function getRecordTitle($id, $encode = true)
    {
        if (isset($this->faqRecord['id']) && ($this->faqRecord['id'] == $id)) {
            return ($encode ? PMF_htmlentities($this->faqRecord['title'], ENT_QUOTES, $this->pmf_lang['metaCharset']) : $this->faqRecord['title']);
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
            $this->language
            );
        $result = $this->db->query($query);

        if ($this->db->num_rows($result) > 0) {
            while ($row = $this->db->fetch_object($result)) {
                if ($encode) {
                    $output = PMF_htmlentities($row->thema, ENT_QUOTES, $this->pmf_lang['metaCharset']);
                } else {
                    $output = $row->thema;
                }
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
     * @param    string     $record_lang
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
                AND lang = '%s'
            ORDER BY
                revision_id",
            SQLPREFIX,
            $record_id,
            $record_lang
            );
        $result = $this->db->query($query);

        if ($this->db->num_rows($result) > 0) {
            while ($row = $this->db->fetch_object($result)) {
                $revision_data[] = array(
                    'revision_id'   => $row->revision_id,
                    'datum'         => $row->datum,
                    'author'        => $row->author);
            }
        }

        return $revision_data;
    }

    /**
     * Adds a new revision from a given record ID
     *
     * @param    integer    $record_id
     * @param    string     $record_lang
     * @return   array
     * @access   public
     * @since    2006-07-24
     * @author   Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    function addNewRevision($record_id, $record_lang)
    {
        $query = sprintf("
            INSERT INTO
                %sfaqdata_revisions
            SELECT * FROM
                %sfaqdata
            WHERE
                id = %d
            AND
                lang = '%s'",
            SQLPREFIX,
            SQLPREFIX,
            $record_id,
            $record_lang);
        $this->db->query($query);

        return true;
    }


    /**
     * Returns the keywords of a FAQ record from the ID and language
     *
     * @param   integer     record id
     * @return  string
     * @since   2005-11-30
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    function getRecordKeywords($id)
    {
        if (isset($this->faqRecord['id']) && ($this->faqRecord['id'] == $id)) {
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
            return PMF_htmlentities($row->keywords, ENT_QUOTES, $this->pmf_lang['metaCharset']);
        } else {
            return '';
        }
    }

    /**
     * Returns the number of activated and not expired records, optionally
     * not limited to the current language
     *
     * @param   string
     * @return  int
     * @access  public
     * @since   2002-08-23
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    function getNumberOfRecords($language = null)
    {
        $now = date('YmdHis');

        $query = sprintf(
            "SELECT
                id
            FROM
                %sfaqdata
            WHERE
                    active = 'yes'
                %s
                AND date_start <= '%s'
                AND date_end   >= '%s'",
            SQLPREFIX,
            null == $language ? '' : "AND lang = '".$language."'",
            $now,
            $now
            );
        $num = $this->db->num_rows($this->db->query($query));
        if ($num > 0) {
            return $num;
        } else {
            return 0;
        }
    }

    /**
     * Counting the views of a FAQ record
     *
     * @param   integer     id
     * @return  void
     * @access  public
     * @since   2001-02-15
     * @auhtor  Bastian P�ttner <bastian@poettner.net>
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
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
                id = %d
            AND
                lang = '%s'",
            SQLPREFIX,
            $id,
            $this->language);

        $result = $this->db->query($query);
        if ($this->db->num_rows($result)) {
            $row = $this->db->fetch_object($result);
            $nVisits = $row->visits;
        }
        if ($nVisits == 0) {
            $this->createNewVisit($id, $this->language);
        } else {
            $this->updateVisit($id);
        }
    }

    /**
     * Calculates the rating of the user votings
     *
     * @param   integer    $id
     * @return  string
     * @access  public
     * @since   2002-08-29
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
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
     * Returns all user comments from a FAQ record
     *
     * @param   integer     record id
     * @return  string
     * @access  public
     * @since   2002-08-29
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    function getComments($id)
    {
        $oComment = new PMF_Comment($this->db, $this->language);
        return $oComment->getComments($id, PMF_COMMENT_TYPE_FAQ);
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
        $oComment = new PMF_Comment($this->db, $this->language);
        return $oComment->addComment($commentData);
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
    function deleteComment($record_id, $comment_id)
    {
        $oComment = new PMF_Comment($this->db, $this->language);
        return $oComment->deleteComment($record_id, $comment_id);
    }

    /**
     * This function generates the Top Ten with the mosted viewed records
     *
     * @return  string
     * @access  public
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     * @since   2002-05-07
     */
    function getTopTen()
    {
        $result = $this->getTopTenData(PMF_NUMBER_RECORDS_TOPTEN, 0, $this->language);

        if (count($result) > 0) {
            $output = '<ol>';
            foreach ($result as $row) {
                $output .= sprintf('<li><strong>%d %s:</strong><br />',
                    $row['visits'],
                    $this->pmf_lang['msgViews']);
                $shortTitle = PMF_Utils::makeShorterText(PMF_htmlentities($row['thema'], ENT_QUOTES, $this->pmf_lang['metaCharset']), 8);
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
     * This function generates the list with the latest published records
     *
     * @return  string
     * @access  public
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     * @since   2002-05-07
     */
    function getLatest()
    {
        $result = $this->getLatestData(PMF_NUMBER_RECORDS_LATEST, $this->language);

        if (count ($result) > 0) {
            $output = '<ol>';
            foreach ($result as $row) {
                $shortTitle = PMF_Utils::makeShorterText(PMF_htmlentities($row['thema'], ENT_QUOTES, $this->pmf_lang['metaCharset']), 8);
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

    /**
     * Deletes a question for the table faquestion
     *
     * @param   integer $question_id
     * @return  boolean
     * @access  public
     * @since   2006-11-04
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    function deleteQuestion($question_id)
    {
        $query = sprintf('
            DELETE FROM
                %sfaqquestions
            WHERE
                id = %d',
            SQLPREFIX,
            $question_id);

        $this->db->query($query);
        return true;
    }

    /**
     * Returns the visibilty of a question
     *
     * @param   integer $question_id
     * @return  string
     * @access  public
     * @since   2006-11-04
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     */
     function getVisibilityOfQuestion($question_id)
     {
        $query = sprintf('
            SELECT
                is_visible
            FROM
                %sfaqquestions
            WHERE
                id = %d',
            SQLPREFIX,
            $question_id);

        $result = $this->db->query($query);
        if ($this->db->num_rows($result) > 0) {
            $row = $this->db->fetch_object($result);
            return $row->is_visible;
        }
        return null;
     }

    /**
     * Sets the visibilty of a question
     *
     * @param   integer $question_id
     * @param   string  $is_visible
     * @return  boolean
     * @access  public
     * @since   2006-11-04
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     */
     function setVisibilityOfQuestion($question_id, $is_visible)
     {
        $query = sprintf("
            UPDATE
                %sfaqquestions
            SET
                is_visible = '%s'
            WHERE
                id = %d",
            SQLPREFIX,
            $is_visible,
            $question_id);

        $this->db->query($query);
        return true;
     }

    //
    //
    // PRIVATE METHODS
    //
    //

    /**
     * This function generates the Top Ten data with the mosted viewed records
     *
     * @param   integer
     * @param   integer
     * @param   string
     * @return  array
     * @access  private
     * @author  Robin Wood <robin@digininja.org>
     * @author  Thorsten Rinne <thorsten@rinne.info>
     * @author  Matteo Scaramuccia <matteo@scaramuccia.com>
     * @since   2005-03-06
     */
    function getTopTenData($count = PMF_NUMBER_RECORDS_TOPTEN, $categoryId = 0, $language = null)
    {
        global $sids, $PMF_CONF;

        $now = date('YmdHis');
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
                    '.SQLPREFIX.'faqdata.date_start <= \''.$now.'\'
                AND '.SQLPREFIX.'faqdata.date_end   >= \''.$now.'\'
                AND '.SQLPREFIX.'faqdata.id = '.SQLPREFIX.'faqvisits.id
                AND '.SQLPREFIX.'faqdata.lang = '.SQLPREFIX.'faqvisits.lang
                AND '.SQLPREFIX.'faqdata.active = \'yes\'';
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

                $title = PMF_htmlentities($row->thema, ENT_QUOTES, $this->pmf_lang['metaCharset']);
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
     * This function generates an array with a specified number of most recent
     * published records
     *
     * @param   integer
     * @param   string
     * @return  array
     * @access  public
     * @author  Robin Wood <robin@digininja.org>
     * @author  Matteo Scaramuccia <matteo@scaramuccia.com>
     * @since   2005-03-06
     */
    function getLatestData($count = PMF_NUMBER_RECORDS_LATEST, $language = null)
    {
        global $sids, $PMF_CONF;

        $now = date('YmdHis');
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
                    '.SQLPREFIX.'faqdata.date_start <= \''.$now.'\'
                AND '.SQLPREFIX.'faqdata.date_end   >= \''.$now.'\'
                AND '.SQLPREFIX.'faqdata.id = '.SQLPREFIX.'faqvisits.id
                AND '.SQLPREFIX.'faqdata.lang = '.SQLPREFIX.'faqvisits.lang
                AND '.SQLPREFIX.'faqdata.active = \'yes\'';
        if (isset($language) && PMF_Init::isASupportedLanguage($language)) {
            $query .= '
            AND
                '.SQLPREFIX.'faqdata.lang = \''.$language.'\'';
        }
        $query .= '
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

                $title = PMF_htmlentities($row->thema, ENT_QUOTES, $this->pmf_lang['metaCharset']);
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
     * Reload locking for user votings
     *
     * @param    integer    FAQ record id
     * @param    string     IP
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
        if ($this->db->num_rows($this->db->query($query))) {
            return false;
        }
        return true;
    }

    /**
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
            if ($row = $this->db->fetch_object($result)) {
                return $row->usr;
            }
        }
        return 0;
    }

    /**
     * Adds a new voting record
     *
     * @param    array  $votingData
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
     * Adds a new question
     *
     * @param    array  $questionData
     * @return   boolean
     * @access   public
     * @since    2006-09-09
     * @author   Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    function addQuestion($questionData)
    {
        if (!is_array($questionData)) {
            return false;
        }

        $query = sprintf("
            INSERT INTO
                %sfaqquestions
            VALUES
                (%d, '%s', '%s', %d, '%s', '%s', '%s')",
            SQLPREFIX,
            $this->db->nextID(SQLPREFIX.'faqquestions', 'id'),
            $this->db->escape_string($questionData['ask_username']),
            $this->db->escape_string($questionData['ask_usermail']),
            $questionData['ask_category'],
            $this->db->escape_string($questionData['ask_content']),
            $questionData['ask_date'],
            $questionData['is_visible']);
        $this->db->query($query);

        return true;
    }


    /**
     * Returns a new question
     *
     * @param    integer    $question_id
     * @return   array
     * @access   public
     * @since    2006-11-11
     * @author   Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    function getQuestion($id_question)
    {
        $question = array(
            'id'            => 0,
            'user'          => '',
            'email'         => '',
            'category'      => '',
            'question'      => '',
            'date'          => '',
            'is_visible'    => ''
            );

        if (!is_int($id_question)) {
            return $question;
        }

        $question = array();

        $query = sprintf('
            SELECT
                 id, ask_username, ask_usermail, ask_rubrik, ask_content, ask_date, is_visible
            FROM
                %sfaqquestions
            WHERE
                id = %d',
            SQLPREFIX,
            $id_question);

        if ($result = $this->db->query($query)) {
            if ($row = $this->db->fetch_object($result)) {
                $question = array(
                    'id'            => $row->id,
                    'user'          => $row->ask_username,
                    'email'         => $row->ask_usermail,
                    'category'      => $row->ask_rubrik,
                    'question'      => $row->ask_content,
                    'date'          => $row->ask_date,
                    'is_visible'    => $row->is_visible
                    );
            }
        }

        return $question;
    }

    /**
     * Returns all open questions
     *
     * @return  array
     * @access  public
     * @since   2007-02-11
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     */
     function getAllOpenQuestions()
     {
        $questions = array();

        $query = sprintf("
            SELECT
                id, ask_username, ask_usermail, ask_rubrik, ask_content, ask_date, is_visible
            FROM
                %sfaqquestions
            ORDER BY ask_date ASC",
            SQLPREFIX);

        if ($result = $this->db->query($query)) {
            while ($row = $this->db->fetch_object($result)) {
                $questions[] = array(
                    'id'            => $row->id,
                    'user'          => $row->ask_username,
                    'email'         => $row->ask_usermail,
                    'category'      => $row->ask_rubrik,
                    'question'      => $row->ask_content,
                    'date'          => $row->ask_date,
                    'is_visible'    => $row->is_visible);
            }
        }
        return $questions;
     }

    /**
     * Updates an existing voting record
     *
     * @param    array  $votingData
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
     * Adds a new entry in the table faqvisits
     *
     * @param   integer $id
     * @param   string  $lang
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
     * Updates an entry in the table faqvisits
     *
     * @param   integer $id
     * @return  boolean
     * @access  private
     * @since   2006-06-18
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
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
     * Get all the entries from the table faqvisits
     *
     * @return  array
     * @access  public
     * @since   2006-09-07
     * @author  Matteo Scaramuccia <matteo@scaramuccia.com>
     */
    function getAllVisitsData()
    {
        $data = array();

        $query = sprintf(
            "SELECT
                *
             FROM
                %sfaqvisits
             ORDER BY
                visits DESC",
            SQLPREFIX
            );
        $result = $this->db->query($query);

        while ($row = $this->db->fetch_object($result)) {
            $data[] = array(
                          'id'          => $row->id,
                          'lang'        => $row->lang,
                          'visits'      => $row->visits,
                          'last_visit'  => $row->last_visit
                      );
        }

        return $data;
    }

    /**
     * Get the entry from the table faqvisits
     *
     * @param   integer $id
     * @param   string  $lang
     * @return  boolean
     * @access  public
     * @since   2006-09-07
     * @author  Matteo Scaramuccia <matteo@scaramuccia.com>
     */
    function getVisitsData($id, $lang)
    {
        if (!is_numeric($id) && !is_string($lang)) {
            return false;
        }
        $data = array('visits' => 0, 'last_visit' => time());

        $query = sprintf(
            "SELECT
                visits, last_visit
             FROM
                %sfaqvisits
             WHERE
                      id = %d
                  AND lang = '%s'",
            SQLPREFIX,
            $id,
            $lang
            );
        $result = $this->db->query($query);

        if ($row = $this->db->fetch_object($result)) {
            $data['visits'] = $row->visits;
            $data['last_visit'] = $row->last_visit;
        }

        return $data;
    }


    /**
     * Adds a new changelog entry in the table faqchanges
     *
     * @param   integer $id
     * @param   integer $userId
     * @param   string  $text
     * @param   string  $lang
     * @return  boolean
     * @access  private
     * @since   2006-08-18
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     * @author  Matteo Scaramuccia <matteo@scaramuccia.com>
     */
    function createChangeEntry($id, $userId, $text, $lang)
    {
        if (   !is_numeric($id)
            && !is_numeric($userId)
            && !is_string($text)
            && !is_string($lang)
            ) {
            return false;
        }

        $query = sprintf(
            "INSERT INTO
                %sfaqchanges
            (id, beitrag, usr, datum, what, lang)
            VALUES
                (%d, %d, %d, %d, '%s', '%s')",
            SQLPREFIX,
            $this->db->nextID(SQLPREFIX."faqchanges", "id"),
            $id,
            $userId,
            time(),
            $text,
            $lang
        );
        $this->db->query($query);

        return true;
    }

    /**
     * Returns the changelog of a FAQ record
     *
     * @param   integer $record_id
     * @return  array
     * @access  public
     * @since   2007-03-03
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    function getChangeEntries($record_id)
    {
        $entries = array();

        $query = sprintf("
            SELECT
                revision_id, usr, datum, what
            FROM
                %sfaqchanges
            WHERE
                beitrag = %d
            ORDER BY id DESC",
            SQLPREFIX,
            $record_id
            );

       if ($result = $this->db->query($query)) {
            while ($row = $this->db->fetch_object($result)) {
                $entries[] = array(
                    'revision_id'   => $row->revision_id,
                    'user'          => $row->usr,
                    'date'          => $row->datum,
                    'changelog'     => $row->what);
            }
        }

        return $entries;
    }

    /**
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
    function get($QueryType = FAQ_QUERY_TYPE_DEFAULT, $nCatid = 0, $bDownwards = true, $lang = '', $date = '')
    {
        $faqs = array();

        $result = $this->db->query($this->_getSQLQuery($QueryType, $nCatid, $bDownwards, $lang, $date));

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
    function _getCatidWhereSequence($nCatid, $logicOp = 'OR', $oCat = null)
    {
        $sqlWherefilter = '';

        if (!isset($oCat)) {
            $oCat  = new PMF_Category();
        }
        $aChildren = array_values($oCat->getChildren($nCatid));

        foreach ($aChildren as $catid) {
            $sqlWherefilter .= " ".$logicOp." fcr.category_id = ".$catid;
            $sqlWherefilter .= $this->_getCatidWhereSequence($catid, 'OR', $oCat);
        }

        return $sqlWherefilter;
    }

    /**
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

        $now = date('YmdHis');
        $sql = "";
        // Fields selection
        // --------------------------------------------------------------------------------------------------------------------------------------------------
        // id | solution_id | revision_id | lang | category_id | active | keywords | thema | content | author | email | comment | datum | visits | last_visit
        // --------------------------------------------------------------------------------------------------------------------------------------------------
        $sql  = "
            SELECT
                fd.id AS id,
                fd.solution_id AS solution_id,
                fd.revision_id AS revision_id,
                fd.lang AS lang,
                fcr.category_id AS category_id,
                fd.active AS active,
                fd.keywords AS keywords,
                fd.thema AS thema,
                fd.content AS content,
                fd.author AS author,
                fd.email AS email,
                fd.comment AS comment,
                fd.datum AS datum,
                fv.visits AS visits,
                fv.last_visit AS last_visit
            FROM
                ".SQLPREFIX."faqdata fd,
                ".SQLPREFIX."faqvisits fv,
                ".SQLPREFIX."faqcategoryrelations fcr
            WHERE
                fd.id = fcr.record_id
            AND
                fd.lang = fcr.record_lang
            AND
                fd.date_start <= '".$now."'
            AND
                fd.date_end   >= '".$now."'
            AND ";
        // faqvisits data selection
        if (!empty($faqid)) {
            // Select ONLY the faq with the provided $faqid
            $sql .= "fd.id = '".$faqid."' AND ";
        }
        $sql .= "fd.id = fv.id
            AND
                fd.lang = fv.lang";
        $needAndOp = true;
        if ((!empty($nCatid)) && (PMF_Utils::isInteger($nCatid)) && ($nCatid > 0)) {
            if ($needAndOp) {
                $sql .= " AND";
            }
            $sql .= " (fcr.category_id = ".$nCatid;
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
            $sql .= " fd.datum LIKE '".$date."'";
            $needAndOp = true;
        }
        if ((!empty($lang)) && PMF_Utils::isLanguage($lang)) {
            if ($needAndOp) {
                $sql .= " AND";
            }
            $sql .= " fd.lang = '".$lang."'";
            $needAndOp = true;
        }
        switch ($QueryType) {
            case FAQ_QUERY_TYPE_APPROVAL:
                if ($needAndOp) {
                    $sql .= " AND";
                }
                $sql .= " fd.active = '".FAQ_SQL_ACTIVE_NO."'";
                $needAndOp = true;
                break;
            case FAQ_QUERY_TYPE_EXPORT_DOCBOOK:
            case FAQ_QUERY_TYPE_EXPORT_PDF:
            case FAQ_QUERY_TYPE_EXPORT_XHTML:
            case FAQ_QUERY_TYPE_EXPORT_XML:
                if ($needAndOp) {
                    $sql .= " AND";
                }
                $sql .= " fd.active = '".FAQ_SQL_ACTIVE_YES."'";
                $needAndOp = true;
                break;
            default:
                if ($needAndOp) {
                    $sql .= " AND";
                }
                $sql .= " fd.active = '".FAQ_SQL_ACTIVE_YES."'";
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
                $sql .= "\nORDER BY fd.thema";
                break;
            case FAQ_QUERY_TYPE_RSS_LATEST:
                $sql .= "\nORDER BY fd.datum DESC";
                break;
            default:
                // Normal ordering
                $sql .= "\nORDER BY fcr.category_id, fd.id";
                break;
        }

        return $sql;
    }

    /**
     * Adds the record permissions for users and groups
     *
     * @param   string  $mode           'group' or 'user'
     * @param   integer $record_id      ID of the current record
     * @param   integer $id             group ID or user ID
     * @return  boolean
     * @access  public
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    function addPermission($mode, $record_id, $id)
    {
        if (!($mode == "user" || $mode == "group")) {
            return false;
        }

        $query = sprintf("
            INSERT INTO
                %sfaqdata_%s
            (record_id, %s_id)
                VALUES
            (%d, %d)",
            SQLPREFIX,
            $mode,
            $mode,
            $record_id,
            $id);

        $this->db->query($query);

        return true;
    }

    /**
     * Deletes the record permissions for users and groups
     *
     * @param   string  $mode           'group' or 'user'
     * @param   integer $record_id      ID of the current record
     * @return  boolean
     * @access  public
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    function deletePermission($mode, $record_id)
    {
        if (!($mode == "user" || $mode == "group")) {
            return false;
        }
        if (!is_int($record_id)) {
            return false;
        }

        $query = sprintf("
            DELETE FROM
                %sfaqdata_%s
            WHERE
                record_id = %d",
            SQLPREFIX,
            $mode,
            $record_id);
        $this->db->query($query);

        return true;
    }

    /**
     * Returns the record permissions for users and groups
     *
     * @param   string  $mode           'group' or 'user'
     * @param   integer $record_id
     * @return  array
     * @access  boolean
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    function getPermission($mode, $record_id)
    {
        $permissions = null;

        if (!($mode == 'user' || $mode == 'group')) {
            return false;
        }

        $query = sprintf("
            SELECT
                %s_id AS permission
            FROM
                %sfaqdata_%s
            WHERE
                record_id = %d",
            $mode,
            SQLPREFIX,
            $mode,
            (int)$record_id);

        $result = $this->db->query($query);
        if ($this->db->num_rows($result) > 0) {
            $row = $this->db->fetch_object($result);
            $permissions[] = $row->permission;
        }
        return $permissions;
    }

    /**
     * Returns all records of one category
     *
     * @param   integer $category
     * @return  string
     * @access  public
     * @since   2007-04-04
     * @author  Georgi Korchev <korchev@yahoo.com>
     */
    function showAllRecordsWoPaging($category) {

        global $sids, $PMF_CONF, $tree;

        $now = date('YmdHis');
        $query = '
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
            LEFT JOIN
                '.SQLPREFIX.'faqdata_group
            ON
                '.SQLPREFIX.'faqdata.id = '.SQLPREFIX.'faqdata_group.record_id
            LEFT JOIN
                '.SQLPREFIX.'faqdata_user
            ON
                '.SQLPREFIX.'faqdata.id = '.SQLPREFIX.'faqdata_user.record_id
            WHERE
                    '.SQLPREFIX.'faqdata.date_start <= \''.$now.'\'
                AND '.SQLPREFIX.'faqdata.date_end   >= \''.$now.'\'
                AND '.SQLPREFIX.'faqdata.active = \'yes\'
                AND '.SQLPREFIX.'faqcategoryrelations.category_id = '.$category.'
                AND '.SQLPREFIX.'faqdata.lang = \''.$this->language.'\'
            ORDER BY
                '.SQLPREFIX.'faqdata.id';

        $result = $this->db->query($query);

        $output = '<ul class="phpmyfaq_ul">';

        while (($row = $this->db->fetch_object($result))) {
            $title = PMF_htmlentities($row->thema, ENT_NOQUOTES, $this->pmf_lang['metaCharset']);
                            $url   = sprintf('%saction=artikel&amp;cat=%d&amp;id=%d&amp;artlang=%s',
                            $sids,
                            $row->category_id,
                            $row->id,
                            $row->lang
                        );
                $oLink = new PMF_Link(PMF_Link::getSystemRelativeUri().'?'.$url);
                $oLink->itemTitle = $row->thema;
                $oLink->text = $title;
                $oLink->tooltip = $title;
                $listItem = sprintf('<li>%s</li>',
                    $oLink->toHtmlAnchor(),
                    $this->pmf_lang['msgViews']);
                $listItem = '<li>'.$oLink->toHtmlAnchor().'</li>';

                $output .= $listItem;
        }
        $output .= '</ul>';

        return $output;
    }

    /**
     * Prints the open questions as a XHTML table
     *
     * @return  string
     * @access  public
     * @since   2002-09-17
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    function printOpenQuestions()
    {
        global $sids, $category;

        $query = sprintf("
            SELECT
                COUNT(*) AS num
            FROM
                %sfaqquestions
            WHERE
                is_visible != 'Y'",
            SQLPREFIX);

        $result = $this->db->query($query);
        $row = $this->db->fetch_object($result);
        $numOfInvisibles = $row->num;

        if ($numOfInvisibles > 0) {
            $extraout = sprintf('<tr><td colspan="3"><hr />%s%s</td></tr>',
                $this->pmf_lang['msgQuestionsWaiting'],
                $numOfInvisibles);
        } else {
            $extraout = '';
        }

        $query = sprintf("
            SELECT
                id,
                ask_username,
                ask_usermail,
                ask_rubrik,
                ask_content,
                ask_date
            FROM
                %sfaqquestions
            WHERE
                is_visible = 'Y'
            ORDER BY
                ask_date ASC",
            SQLPREFIX);

        $result = $this->db->query($query);
        $output = '';
        if ($this->db->num_rows($result) > 0) {
            while ($row = $this->db->fetch_object($result)) {
                $output .= '<tr class="openquestions">';
                $output .= sprintf('<td valign="top" nowrap="nowrap">%s<br /><a href="mailto:%s">%s</a></td>',
                    makeDate($row->ask_date),
                    safeEmail($row->ask_usermail),
                    $row->ask_username);
                $output .= sprintf('<td valign="top"><strong>%s:</strong><br />%s</td>',
                    $category->categoryName[$row->ask_rubrik]['name'],
                    strip_tags($row->ask_content));
                $output .= sprintf('<td valign="top"><a href="?%saction=add&amp;question=%d&amp;cat=%d">%s</a></td>',
                    $sids,
                    $row->id,
                    $row->ask_rubrik,
                    $this->pmf_lang['msg2answer']);
                $output .= '</tr>';
            }
        } else {
            $output = sprintf('<tr><td colspan="3">%s</td></tr>',
                $this->pmf_lang['msgNoQuestionsAvailable']);
        }

        return $output.$extraout;
    }
}
