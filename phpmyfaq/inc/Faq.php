<?php
/**
 * The main FAQ class
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
 * @package   PMF_Faq
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo Scaramuccia <matteo@scaramuccia.com>
 * @author    Georgi Korchev <korchev@yahoo.com>
 * @author    Adrianna Musiol <musiol@imageaccess.de>
 * @copyright 2005-2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2005-12-20
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

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
define('FAQ_SORTING_TYPE_FAQID', 4);

/**
 * PMF_Faq
 *
 * @category  phpMyFAQ
 * @package   PMF_Faq
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo Scaramuccia <matteo@scaramuccia.com>
 * @author    Georgi Korchev <korchev@yahoo.com>
 * @author    Adrianna Musiol <musiol@imageaccess.de>
 * @copyright 2005-2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2005-12-20
 */
class PMF_Faq
{
    /**
    * DB handle
    *
    * @var PMF_Db
    */
    private $db;

    /**
    * Language
    *
    * @var  string
    */
    public $language;

    /**
    * Language strings
    *
    * @var  string
    */
    private $pmf_lang;

    /**
    * Plural form support
    *
    * @var  PMF_Language_Plurals
    */
    private $plr;

    /**
    * The current FAQ record
    *
    * @var  array
    */
    public $faqRecord = array();

    /**
    * All current FAQ records in an array
    *
    * @var  array
    */
    public $faqRecords = array();

    /**
     * Users
     *
     * @var array
     */
    private $user = null;

    /**
     * Groups
     *
     * @var array
     */
    private $groups = array();

    /**
     * Flag for Group support
     *
     * @var boolean
     */
    private $groupSupport = false;

    /**
     * PMF_Category_Releations object
     * 
     * @var PMF_Category_Releations
     */
    private $categoryRelations = null;
    
    /**
     * PMF_Faq_Record object
     * 
     * @var PMF_Faq_Record
     */
    //private $faqRecord = null;
    
    /**
     * PMF_Faq_User object
     * 
     * @var PMF_Faq_User
     */
    private $faqUser = null;
    
    /**
     * PMF_Faq_Group object
     * 
     * @var PMF_Faq_Group
     */
    private $faqGroup = null;
    
    /**
     * Constructor
     *
     * @param  integer $user   User
     * @param  array   $groups Groups
     * @return void
     */
    public function __construct($user = null, $groups = null)
    {
        global $PMF_LANG, $plr;

        $this->db       = PMF_Db::getInstance();
        $this->language = PMF_Language::$language;
        $this->pmf_lang = $PMF_LANG;
        $this->plr      = $plr;

        if (is_null($user)) {
            $this->user = -1;
        } else {
            $this->user = $user;
        }
        if (is_null($groups)) {
            $this->groups = array(-1);
        } else {
            $this->groups = $groups;
        }
        
        $faqconfig = PMF_Configuration::getInstance();
        if ($faqconfig->get('main.permLevel') == 'medium') {
            $this->groupSupport = true;
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
     * @param  int     $category_id Category ID
     * @param  string  $orderby     Order by
     * @param  string  $sortby      Sorty by
     * @return array
     */
    public function getAllRecordPerCategory($category_id, $orderby = 'id', $sortby = 'ASC')
    {
        global $sids;

        $faqdata = array();

        if ($orderby == 'visits') {
            $current_table = 'fv';
        } else {
            $current_table = 'fd';
        }

        if ($this->groupSupport) {
            $permPart = sprintf("( fdg.group_id IN (%s)
            OR
                (fdu.user_id = %d AND fdg.group_id IN (%s)))",
                implode(', ', $this->groups),
                $this->user,
                implode(', ', $this->groups));
        } else {
            $permPart = sprintf("( fdu.user_id = %d OR fdu.user_id = -1 )",
                $this->user);
        }

        $now   = date('YmdHis');
        $query = sprintf("
            SELECT
                fd.id AS id,
                fd.lang AS lang,
                fd.thema AS thema,
                fd.content AS record_content,
                fd.datum AS record_date,
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
                %s
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
            $permPart,
            $current_table,
            $this->db->escapeString($orderby),
            $this->db->escapeString($sortby));

        $result = $this->db->query($query);
        $num    = $this->db->numRows($result);

        if ($num > 0) {
            while (($row = $this->db->fetchObject($result))) {

                if (empty($row->visits)) {
                    $visits = 0;
                } else {
                    $visits = $row->visits;
                }

                $url   = sprintf('%saction=artikel&cat=%d&id=%d&artlang=%s',
                            $sids,
                            $row->category_id,
                            $row->id,
                            $row->lang
                        );
                $oLink = new PMF_Link(PMF_Link::getSystemRelativeUri().'?'.$url);
                $oLink->itemTitle = $oLink->text = $oLink->tooltip = $row->thema;;

                $faqdata[] = array(
                    'record_id'      => $row->id,
                    'record_lang'    => $row->lang,
                    'category_id'    => $row->category_id,
                    'record_title'   => $row->thema,
                    'record_preview' => PMF_Utils::chopString(strip_tags($row->record_content), 25),
                    'record_link'    => $oLink->toString(),
                    'record_date'    => $row->record_date,
                    'visits'         => $visits);
            }
        } else {
            return $faqdata;
        }

        return $faqdata;
    }

    /**
     * This function returns all not expired records from one category
     *
     * @param  int     $category_id Category ID
     * @param  string  $orderby     Order by
     * @param  string  $sortby      Sorty by
     * @return string
     */
    public function showAllRecords($category_id, $orderby = 'id', $sortby = 'ASC')
    {
        global $sids, $category;

        $faqconfig = PMF_Configuration::getInstance();
        $page      = PMF_Filter::filterInput(INPUT_GET, 'seite', FILTER_VALIDATE_INT, 1);
        $output    = '';

        if ($orderby == 'visits') {
            $current_table = 'fv';
        } else {
            $current_table = 'fd';
        }

        if ($this->groupSupport) {
            $permPart = sprintf("( fdg.group_id IN (%s)
            OR
                (fdu.user_id = %d AND fdg.group_id IN (%s)))",
                implode(', ', $this->groups),
                $this->user,
                implode(', ', $this->groups));
        } else {
            $permPart = sprintf("( fdu.user_id = %d OR fdu.user_id = -1 )",
                $this->user);
        }

        $now = date('YmdHis');
        $query = sprintf("
            SELECT
                fd.id AS id,
                fd.lang AS lang,
                fd.sticky AS sticky,
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
                %s
            ORDER BY
                fd.sticky DESC, %s.%s %s",
            SQLPREFIX,
            SQLPREFIX,
            SQLPREFIX,
            SQLPREFIX,
            SQLPREFIX,
            $now,
            $now,
            $category_id,
            $this->language,
            $permPart,
            $current_table,
            $this->db->escapeString($orderby),
            $this->db->escapeString($sortby));

        $result = $this->db->query($query);

        $num   = $this->db->numRows($result);
        $pages = ceil($num / $faqconfig->get("main.numberOfRecordsPerPage"));

        if ($page == 1) {
            $first = 0;
        } else {
            $first = ($page * $faqconfig->get("main.numberOfRecordsPerPage")) - $faqconfig->get("main.numberOfRecordsPerPage");
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
            while (($row = $this->db->fetchObject($result)) && $displayedCounter < $faqconfig->get("main.numberOfRecordsPerPage")) {
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

                $title = $row->thema;
                $url   = sprintf('%saction=artikel&amp;cat=%d&amp;id=%d&amp;artlang=%s',
                            $sids,
                            $row->category_id,
                            $row->id,
                            $row->lang);
                            
                $oLink            = new PMF_Link(PMF_Link::getSystemRelativeUri().'?'.$url);
                $oLink->itemTitle = $row->thema;
                $oLink->text      = $title;
                $oLink->tooltip   = $title;
                
                $listItem = sprintf('<li>%s<span id="viewsPerRecord"><br /><span class="little">(%s)</span>%s</span></li>',
                    $oLink->toHtmlAnchor(),
                    $this->plr->GetMsg('plmsgViews', $visits),
                    ($row->sticky == 1) ? '<br /><br />' : '');

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
            
            $baseUrl = PMF_Link::getSystemRelativeUri() . '?'
                     . (empty($sids) ? '' : "$sids&amp;")
                     . 'action=show&amp;cat=' . $category_id
                     . '&amp;seite=' . $page;
            
            $options = array('baseUrl'         => $baseUrl,
                             'total'           => $num,
                             'perPage'         => $faqconfig->get('main.numberOfRecordsPerPage'),
                             'pageParamName'   => 'seite',
                             'nextPageLinkTpl' => '<a href="{LINK_URL}">' . $this->pmf_lang['msgNext'] . '</a>',
                             'prevPageLinkTpl' => '<a href="{LINK_URL}">' . $this->pmf_lang['msgPrevious'] . '</a>',
                             'layoutTpl'       => '<p align="center"><strong>{LAYOUT_CONTENT}</strong></p>');
        
            $pagination = new PMF_Pagination($options);
            $output    .= $pagination->render();
        }
        
        return $output;
    }

    /**
     * This function returns all not expired records from the given record ids
     *
     * @param   array   $record_ids Array of record ids
     * @param   string  $orderby    Order by
     * @param   string  $sortby     Sort by
     * @return  string
     */
    public function showAllRecordsByIds(Array $record_ids, $orderby = 'fd.id', $sortby = 'ASC')
    {
        global $sids;

        $faqconfig  = PMF_Configuration::getInstance();
        $records    = implode(', ', $record_ids);
        $page       = PMF_Filter::filterInput(INPUT_GET, 'seite', FILTER_VALIDATE_INT, 1);
        $tagging_id = PMF_Filter::filterInput(INPUT_GET, 'tagging_id', FILTER_VALIDATE_INT); 
        $output     = '';

        if ($this->groupSupport) {
            $permPart = sprintf("( fdg.group_id IN (%s)
            OR
                (fdu.user_id = %d AND fdg.group_id IN (%s)))",
                implode(', ', $this->groups),
                $this->user,
                implode(', ', $this->groups));
        } else {
            $permPart = sprintf("( fdu.user_id = %d OR fdu.user_id = -1 )",
                $this->user);
        }

        $now   = date('YmdHis');
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
                %s
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
            $permPart,
            $this->db->escapeString($orderby),
            $this->db->escapeString($sortby));

        $result = $this->db->query($query);

        $num = $this->db->numRows($result);
        $pages = ceil($num / $faqconfig->get('main.numberOfRecordsPerPage'));

        if ($page == 1) {
            $first = 0;
        } else {
            $first = ($page * $faqconfig->get('main.numberOfRecordsPerPage')) - $faqconfig->get('main.numberOfRecordsPerPage');
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
            while (($row = $this->db->fetchObject($result)) && $displayedCounter < $faqconfig->get('main.numberOfRecordsPerPage')) {
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

                $title = $row->thema;
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
                $listItem = sprintf('<li>%s<br /><span class="little">('.$this->plr->GetMsg('plmsgViews',$visits).')</span></li>',
                    $oLink->toHtmlAnchor());

                $output .= $listItem;
            }
            $output .= '</ul><span id="totFaqRecords" style="display: none;">'.$num.'</span>';
        } else {
            return false;
        }

        if ($num > $faqconfig->get('main.numberOfRecordsPerPage')) {
            $output .= "<p align=\"center\"><strong>";
            if (!isset($page)) {
                $page = 1;
            }
            $vor  = $page - 1;
            $next = $page + 1;
            if ($vor != 0) {
                $url              = $sids.'&amp;action=search&amp;tagging_id='.$tagging_id.'&amp;seite='.$vor;
                $oLink            = new PMF_Link(PMF_Link::getSystemRelativeUri().'?'.$url);
                $oLink->itemTitle = 'tag';
                $oLink->text      = $this->pmf_lang["msgPrevious"];
                $oLink->tooltip   = $this->pmf_lang["msgPrevious"];
                $output          .= '[ '.$oLink->toHtmlAnchor().' ]'; 
            }
            $output .= " ";
            if ($next <= $pages) {
                $url              = $sids.'&amp;action=search&amp;tagging_id='.$tagging_id.'&amp;seite='.$next;
                $oLink            = new PMF_Link(PMF_Link::getSystemRelativeUri().'?'.$url);
                $oLink->itemTitle = 'tag';
                $oLink->text      = $this->pmf_lang["msgNext"];
                $oLink->tooltip   = $this->pmf_lang["msgNext"];
                $output          .= '[ '.$oLink->toHtmlAnchor().' ]';
            }
            $output .= "</strong></p>";
        }

        return $output;
    }

    /**
     * Returns an array with all data from a FAQ record
     *
     * @param  integer $record_id   Record id
     * @param  integer $revision_id Revidion id
     * @param  boolean $admin       must be true if it is called by an admin/author context
     * @return void
     */
    public function getRecord($id, $revision_id = null, $admin = false)
    {
        global $PMF_LANG;

        if ($this->groupSupport) {
            $permPart = sprintf("( fdg.group_id IN (%s)
            OR
                (fdu.user_id = %d AND fdg.group_id IN (%s)))",
                implode(', ', $this->groups),
                $this->user,
                implode(', ', $this->groups));
        } else {
            $permPart = sprintf("( fdu.user_id = %d OR fdu.user_id = -1 )",
                $this->user);
        }
        
        $query = sprintf(
            "SELECT
                 id, lang, solution_id, revision_id, active, sticky, keywords, 
                 thema, content, author, email, comment, datum, links_state, 
                 links_check_date, date_start, date_end
            FROM
                %s%s fd
            LEFT JOIN
                %sfaqdata_group fdg
            ON
                fd.id = fdg.record_id
            LEFT JOIN
                %sfaqdata_user fdu
            ON
                fd.id = fdu.record_id
            WHERE
                fd.id = %d
            %s
            AND
                fd.lang = '%s'
            AND
                %s",
            SQLPREFIX,
            isset($revision_id) ? 'faqdata_revisions': 'faqdata',
            SQLPREFIX,
            SQLPREFIX,
            $id,
            isset($revision_id) ? 'AND revision_id = '.$revision_id : '',
            $this->language,
            ($admin) ? '1=1' : $permPart);

        $result = $this->db->query($query);

        if ($row = $this->db->fetchObject($result)) {

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
                'sticky'        => $row->sticky,
                'keywords'      => $row->keywords,
                'title'         => $row->thema,
                'content'       => $content,
                'author'        => $row->author,
                'email'         => $row->email,
                'comment'       => $row->comment,
                'date'          => PMF_Date::createIsoDate($row->datum),
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
                'sticky'        => 0,
                'keywords'      => '',
                'title'         => '',
                'content'       => $PMF_LANG['err_inactiveArticle'],
                'author'        => '',
                'email'         => '',
                'comment'       => '',
                'date'          => PMF_Date::createIsoDate(date('YmdHis')),
                'dateStart'     => '',
                'dateEnd'       => '',
                'linkState'     => '',
                'linkCheckDate' => ''
                );
        }
    }

    /**
     * Deletes a record and all the dependencies
     *
     * @param integer $record_id   Record id
     * @param string  $record_lang Record language
     * 
     * @return boolean
     */
    public function deleteRecord($record_id, $record_lang)
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
            sprintf("DELETE FROM %sfaqdata_user WHERE record_id = %d",
                SQLPREFIX, $record_id, $record_lang),
            sprintf("DELETE FROM %sfaqdata_group WHERE record_id = %d",
                SQLPREFIX, $record_id, $record_lang),
            sprintf("DELETE FROM %sfaqdata_tags WHERE record_id = %d",
                SQLPREFIX, $record_id),
            sprintf('DELETE FROM %sfaqdata_tags WHERE %sfaqdata_tags.record_id NOT IN (SELECT %sfaqdata.id FROM %sfaqdata)',
                SQLPREFIX, SQLPREFIX, SQLPREFIX, SQLPREFIX),
            sprintf("DELETE FROM %sfaqcomments WHERE id = %d",
                SQLPREFIX, $record_id),
            sprintf("DELETE FROM %sfaqvoting WHERE artikel = %d",
                SQLPREFIX, $record_id));

         foreach($queries as $query) {
            $this->db->query($query);
         }

         return true;
    }

    /**
     * Checks if a record is already translated
     *
     * @param  integer $record_id   Record id
     * @param  string  $record_lang Record language
     * @return boolean
     */
    public function isAlreadyTranslated($record_id, $record_lang)
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

        if ($this->db->numRows($result)) {
            return true;
        }

        return false;
    }
    
    /**
     * Checks, if comments are disabled for the FAQ record
     * 
     * @param  integer $record_id   Id of FAQ or news entry
     * @param  string  $record_lang Language
     * @param  string  $record_type Type of comment: faq or news
     * @return boolean true, if comments are disabled
     */
    public function commentDisabled($record_id, $record_lang, $record_type = 'faq')
    {
        if ('news' == $record_type) {
            $table = 'faqnews';
        } else {
            $table = 'faqdata';
        }
        
        $query = sprintf("
            SELECT
                comment
            FROM
                %s%s
            WHERE
                id = %d
            AND
                lang = '%s'",
            SQLPREFIX,
            $table,
            $record_id,
            $record_lang);

        $result = $this->db->query($query);
        
        if ($row = $this->db->fetchObject($result)) {
            return ($row->comment === 'y') ? false : true;
        } else {
            return true;
        }
    }

    /**
     * Returns an array with all data from a FAQ record
     *
     * @param  integer $solution_id Solution ID
     * @return void
     */
    public function getRecordBySolutionId($solution_id)
    {
        if ($this->groupSupport) {
            $permPart = sprintf("( fdg.group_id IN (%s)
            OR
                (fdu.user_id = %d AND fdg.group_id IN (%s)))",
                implode(', ', $this->groups),
                $this->user,
                implode(', ', $this->groups));
        } else {
            $permPart = sprintf("( fdu.user_id = %d OR fdu.user_id = -1 )",
                $this->user);
        }
        
        $query = sprintf(
            "SELECT
                *
            FROM
                %sfaqdata fd
            LEFT JOIN
                %sfaqdata_group fdg
            ON
                fd.id = fdg.record_id
            LEFT JOIN
                %sfaqdata_user fdu
            ON
                fd.id = fdu.record_id
            WHERE
                fd.solution_id = %d
            AND
                %s",
            SQLPREFIX,
            SQLPREFIX,
            SQLPREFIX,
            $solution_id,
            $permPart);

        $result = $this->db->query($query);

        if ($row = $this->db->fetchObject($result)) {
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
                'sticky'        => $row->sticky,
                'keywords'      => $row->keywords,
                'title'         => $row->thema,
                'content'       => $content,
                'author'        => $row->author,
                'email'         => $row->email,
                'comment'       => $row->comment,
                'date'          => PMF_Date::createIsoDate($row->datum),
                'dateStart'     => $row->date_start,
                'dateEnd'       => $row->date_end,
                'linkState'     => $row->links_state,
                'linkCheckDate' => $row->links_check_date);
        }
    }

    /**
     * Gets the record ID from a given solution ID
     *
     * @param  integer $solution_id Solution ID
     * @return array
     */
    public function getIdFromSolutionId($solution_id)
    {
        $query = sprintf("
            SELECT
                id, lang, content
            FROM
                %sfaqdata
            WHERE
                solution_id = %d",
            SQLPREFIX,
            $solution_id);

        $result = $this->db->query($query);

        if ($row = $this->db->fetchObject($result)) {
            return array('id'      => $row->id,
                         'lang'    => $row->lang,
                         'content' => $row->content);
        }

        return null;
    }

    /**
     * Gets the latest solution id for a FAQ record
     *
     * @return integer
     */
    public function getSolutionId()
    {
        $latest_id        = 0;
        $next_solution_id = 0;

        $query = sprintf('
            SELECT
                MAX(solution_id) AS solution_id
            FROM
                %sfaqdata',
            SQLPREFIX);
        $result = $this->db->query($query);

        if ($result && $row = $this->db->fetchObject($result)) {
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
     * @param  integer $sortType  Sorting type
     * @param  array   $condition Condition
     * @param  string  $sortOrder Sorting order
     * @return void
     */
    public function getAllRecords($sortType = FAQ_SORTING_TYPE_CATID_FAQID, Array $condition = null, $sortOrder = 'ASC')
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
                        $where .= $separator."'".$this->db->escapeString($value)."'";
                        $separator = ", ";
                    }
                    $where .= ")";
                } else {
                    $where .= " = '".$this->db->escapeString($data)."'";
                }
                if ($num > 0) {
                    $where .= " AND ";
                }
            }
        }

        $orderBy = '';
        switch ($sortType) {
        	
            case FAQ_SORTING_TYPE_CATID_FAQID:
                $orderBy = sprintf("
            ORDER BY
                fcr.category_id,
                fd.id %s",
                    $sortOrder);
                break;

            case FAQ_SORTING_TYPE_FAQID:
                $orderBy = sprintf("
            ORDER BY
                fd.id %s",
                    $sortOrder);
                break;
                
            case FAQ_SORTING_TYPE_FAQTITLE_FAQID:
                $orderBy = sprintf("
            ORDER BY
                fcr.category_id,
                fd.thema %s",
                    $sortOrder);
                break;

            case FAQ_SORTING_TYPE_DATE_FAQID:
                $orderBy = sprintf("
            ORDER BY
                fcr.category_id,
                fd.datum %s",
                    $sortOrder);
                break;
        }

        $query = sprintf("
            SELECT
                fd.id AS id,
                fd.lang AS lang,
                fcr.category_id AS category_id,
                fd.solution_id AS solution_id,
                fd.revision_id AS revision_id,
                fd.active AS active,
                fd.sticky AS sticky,
                fd.keywords AS keywords,
                fd.thema AS thema,
                fd.content AS content,
                fd.author AS author,
                fd.email AS email,
                fd.comment AS comment,
                fd.datum AS datum,
                fd.links_state AS links_state,
                fd.links_check_date AS links_check_date,
                fd.date_start AS date_start,
                fd.date_end AS date_end,
                fd.sticky AS sticky
            FROM
                %sfaqdata fd
            LEFT JOIN
                %sfaqcategoryrelations fcr
            ON
                fd.id = fcr.record_id
            AND
                fd.lang = fcr.record_lang
            %s
            %s",
            SQLPREFIX,
            SQLPREFIX,
            $where,
            $orderBy);

        $result = $this->db->query($query);

        while ($row = $this->db->fetchObject($result)) {
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
                'sticky'        => $row->sticky,
                'keywords'      => $row->keywords,
                'title'         => $row->thema,
                'content'       => $content,
                'author'        => $row->author,
                'email'         => $row->email,
                'comment'       => $row->comment,
                'date'          => PMF_Date::createIsoDate($row->datum),
                'dateStart'     => $row->date_start,
                'dateEnd'       => $row->date_end);
        }
    }

    /**
     * Returns the FAQ record title from the ID and language
     *
     * @param  integer $id Record id
     * @return string
     */
    public function getRecordTitle($id)
    {
        if (isset($this->faqRecord['id']) && ($this->faqRecord['id'] == $id)) {
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
            $this->language
            );
        $result = $this->db->query($query);

        if ($this->db->numRows($result) > 0) {
            while ($row = $this->db->fetchObject($result)) {
                $output = $row->thema;
            }
        } else {
            $output = $this->pmf_lang['no_cats'];
        }

        return $output;
    }

    /**
     * Gets all revisions from a given record ID
     *
     * @param  integer $record_id   Record id
     * @param  string  $record_lang Record language
     * @return array
     */
    public function getRevisionIds($record_id, $record_lang)
    {
        $revision_data = array();

        $query = sprintf("
            SELECT
                revision_id, datum, author
            FROM
                %sfaqdata_revisions
            WHERE
                id = %d
            AND
                lang = '%s'
            ORDER BY
                revision_id",
            SQLPREFIX,
            $record_id,
            $record_lang);

        $result = $this->db->query($query);

        if ($this->db->numRows($result) > 0) {
            while ($row = $this->db->fetchObject($result)) {
                $revision_data[] = array(
                    'revision_id' => $row->revision_id,
                    'datum'       => $row->datum,
                    'author'      => $row->author);
            }
        }

        return $revision_data;
    }

    /**
     * Returns the keywords of a FAQ record from the ID and language
     *
     * @param  integer $id record id
     * @return string
     */
    public function getRecordKeywords($id)
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

        if ($this->db->numRows($result) > 0) {
            $row = $this->db->fetchObject($result);
            return PMF_String::htmlspecialchars($row->keywords, ENT_QUOTES, 'utf-8');
        } else {
            return '';
        }
    }
    
    /**
     * Returns a answer preview of the FAQ record
     *
     * @param integer $recordId  FAQ record ID
     * @param integer $wordCount Number of words, default: 12
     * 
     * @return string 
     */
    public function getRecordPreview($recordId, $wordCount = 12)
    {
    	$answerPreview = '';
    	
        if (isset($this->faqRecord['id']) && ($this->faqRecord['id'] == $recordId)) {
            $answerPreview = $this->faqRecord['content'];
        }
        
        $query = sprintf("
            SELECT
                content as answer
            FROM
                %sfaqdata
            WHERE 
                id = %d 
            AND 
                lang = '%s'",
            SQLPREFIX,
            $recordId,
            $this->language);

        $result = $this->db->query($query);

        if ($this->db->numRows($result) > 0) {
            $row           = $this->db->fetchObject($result);
            $answerPreview = strip_tags($row->answer);
        } else {
            $answerPreview = PMF_Configuration::getInstance()->get('main.metaDescription');
        }
    	
    	return PMF_Utils::makeShorterText($answerPreview, $wordCount);
    }

    /**
     * Returns the number of activated and not expired records, optionally
     * not limited to the current language
     *
     * @param  string $language Language
     * @return int
     */
    public function getNumberOfRecords($language = null)
    {
        $now = date('YmdHis');

        $query = sprintf("
            SELECT
                id
            FROM
                %sfaqdata
            WHERE
                active = 'yes'
            %s
            AND
                date_start <= '%s'
            AND
                date_end   >= '%s'",
            SQLPREFIX,
            null == $language ? '' : "AND lang = '".$language."'",
            $now,
            $now);

        $num = $this->db->numRows($this->db->query($query));

        if ($num > 0) {
            return $num;
        } else {
            return 0;
        }
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
    public function addComment($commentData)
    {
        $oComment = new PMF_Comment();
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
    public function deleteComment($record_id, $comment_id)
    {
        $oComment = new PMF_Comment();
        return $oComment->deleteComment($record_id, $comment_id);
    }

    /**
     * This function generates a list with the mosted voted or most visited records
     * 
     * @param  string $type Type definition visits/voted
     * @access public
     * @since  2009-11-03
     * @author Max Köhler <me@max-koehler.de>
     * @return array
     */
    public function getTopTen($type = 'visits')
    {
        if ('visits' == $type) {
            $result = $this->getTopTenData(PMF_NUMBER_RECORDS_TOPTEN, 0, $this->language);
        } else {
            $result = $this->getTopVotedData(PMF_NUMBER_RECORDS_TOPTEN, 0, $this->language);
        }
        $output = array();

        if (count($result) > 0) {
            foreach ($result as $row) {
                if ('visits' == $type) {
                    $output['title'][]  = PMF_Utils::makeShorterText($row['thema'], 8);
                    $output['url'][]    = $row['url'];
                    $output['visits'][] = $this->plr->GetMsg('plmsgViews',$row['visits']);
                } else {
                    $output['title'][]  = PMF_Utils::makeShorterText($row['thema'], 8);
                    $output['url'][]    = $row['url'];
                    $output['voted'][]  = sprintf('%s %s 5 - %s',
                                                  round($row['avg'], 2),
                                                  $this->pmf_lang['msgVoteFrom'],
                                                  $this->plr->GetMsg('plmsgVotes', $row['user']));
                }
            }
        } else {
            $output['error'] = $this->pmf_lang['err_noTopTen'];
        }

        return $output;
    }

    /**
     * This function generates the list with the latest published records
     *
     * @return array
     */
    public function getLatest()
    {
        $result = $this->getLatestData(PMF_NUMBER_RECORDS_LATEST, $this->language);
        $output = array();
        
        if (count ($result) > 0) {
            foreach ($result as $row) {
                $output['url'][]   =  $row['url'];
                $output['title'][] = PMF_Utils::makeShorterText($row['thema'], 8);
                $output['date'][]  = PMF_Date::createIsoDate($row['datum']);
            }
        } else {
            $output['error'] = $this->pmf_lang["err_noArticles"];
        }

        return $output;
    }

    /**
     * This function generates a data-set with the mosted voted recors
     *  
     * @param  integer $count      Number of records
     * @param  integer $categoryId Category ID
     * @param  string  $language   Language
     * @return array
     */
    public function getTopVotedData($count = PMF_NUMBER_RECORDS_TOPTEN, $category = 0, $language = nuLL)
    {
        global $sids;
                    
        if ($this->groupSupport) {
            $permPart = sprintf("( fdg.group_id IN (%s)
            OR
                (fdu.user_id = %d AND fdg.group_id IN (%s)))",
                implode(', ', $this->groups),
                $this->user,
                implode(', ', $this->groups));
        } else {
            $permPart = sprintf("( fdu.user_id = %d OR fdu.user_id = -1 )",
                $this->user);
        }

        $now = date('YmdHis');
        $query =
'            SELECT
                fd.id AS id,
                fd.lang AS lang,
                fd.thema AS thema,
                fd.datum AS datum,
                fcr.category_id AS category_id,
                (fv.vote/fv.usr) AS avg,
                fv.usr AS user
            FROM
                '.SQLPREFIX.'faqvoting fv,
                '.SQLPREFIX.'faqdata fd
            LEFT JOIN
                '.SQLPREFIX.'faqcategoryrelations fcr
            ON
                fd.id = fcr.record_id
            AND
                fd.lang = fcr.record_lang
            LEFT JOIN
                '.SQLPREFIX.'faqdata_group AS fdg
            ON
                fd.id = fdg.record_id
            LEFT JOIN
                '.SQLPREFIX.'faqdata_user AS fdu
            ON
                fd.id = fdu.record_id
            WHERE
                    fd.date_start <= \''.$now.'\'
                AND fd.date_end   >= \''.$now.'\'
                AND fd.id = fv.artikel
                AND fd.active = \'yes\'';

        if (isset($categoryId) && is_numeric($categoryId) && ($categoryId != 0)) {
            $query .= '
            AND
                fcr.category_id = \''.$categoryId.'\'';
        }
        if (isset($language) && PMF_Language::isASupportedLanguage($language)) {
            $query .= '
            AND
                fd.lang = \''.$language.'\'';
        }
        $query .= '
            AND
                '.$permPart.'
            ORDER BY
                avg DESC';

        $result = $this->db->query($query);
        $topten = array();
        $data = array();

        $i = 1;
        $oldId = 0;
        while (($row = $this->db->fetchObject($result)) && $i <= $count) {
            if ($oldId != $row->id) {
                $data['avg'] = $row->avg;
                $data['thema'] = $row->thema;
                $data['date'] = $row->datum;
                $data['user'] = $row->user;

                $title = $row->thema;
                $url   = sprintf('%saction=artikel&amp;cat=%d&amp;id=%d&amp;artlang=%s',
                        $sids,
                        $row->category_id,
                        $row->id,
                        $row->lang
                        );
                $oLink = new PMF_Link(PMF_Link::getSystemRelativeUri().'?'.$url);
                $oLink->itemTitle = $row->thema;
                $oLink->tooltip   = $title;
                $data['url']      = $oLink->toString();

                $topten[] = $data;
                $i++;
            }
            $oldId = $row->id;
        }

        return $topten;
    }
     
    /**
     * This function generates the Top Ten data with the mosted viewed records
     *
     * @param  integer $count      Number of records
     * @param  integer $categoryId Category ID
     * @param  string  $language   Language
     * @return array
     */
    public function getTopTenData($count = PMF_NUMBER_RECORDS_TOPTEN, $categoryId = 0, $language = null)
    {
        global $sids;

        if ($this->groupSupport) {
            $permPart = sprintf("( fdg.group_id IN (%s)
            OR
                (fdu.user_id = %d AND fdg.group_id IN (%s)))",
                implode(', ', $this->groups),
                $this->user,
                implode(', ', $this->groups));
        } else {
            $permPart = sprintf("( fdu.user_id = %d OR fdu.user_id = -1 )",
                $this->user);
        }

        $now = date('YmdHis');
        $query =
'            SELECT
                fd.id AS id,
                fd.lang AS lang,
                fd.thema AS thema,
                fd.datum AS datum,
                fcr.category_id AS category_id,
                fv.visits AS visits,
                fv.last_visit AS last_visit
            FROM
                '.SQLPREFIX.'faqvisits fv,
                '.SQLPREFIX.'faqdata fd
            LEFT JOIN
                '.SQLPREFIX.'faqcategoryrelations fcr
            ON
                fd.id = fcr.record_id
            AND
                fd.lang = fcr.record_lang
            LEFT JOIN
                '.SQLPREFIX.'faqdata_group AS fdg
            ON
                fd.id = fdg.record_id
            LEFT JOIN
                '.SQLPREFIX.'faqdata_user AS fdu
            ON
                fd.id = fdu.record_id
            WHERE
                    fd.date_start <= \''.$now.'\'
                AND fd.date_end   >= \''.$now.'\'
                AND fd.id = fv.id
                AND fd.lang = fv.lang
                AND fd.active = \'yes\'';

        if (isset($categoryId) && is_numeric($categoryId) && ($categoryId != 0)) {
            $query .= '
            AND
                fcr.category_id = \''.$categoryId.'\'';
        }
        if (isset($language) && PMF_Language::isASupportedLanguage($language)) {
            $query .= '
            AND
                fd.lang = \''.$language.'\'';
        }
        $query .= '
            AND
                '.$permPart.'
            ORDER BY
                fv.visits DESC';

        $result = $this->db->query($query);
        $topten = array();
        $data = array();

        $i = 1;
        $oldId = 0;
        while (($row = $this->db->fetchObject($result)) && $i <= $count) {
            if ($oldId != $row->id) {
                $data['visits'] = $row->visits;
                $data['thema'] = $row->thema;
                $data['date'] = $row->datum;
                $data['last_visit'] = $row->last_visit;

                $title = $row->thema;
                $url   = sprintf('%saction=artikel&amp;cat=%d&amp;id=%d&amp;artlang=%s',
                        $sids,
                        $row->category_id,
                        $row->id,
                        $row->lang
                        );
                $oLink = new PMF_Link(PMF_Link::getSystemRelativeUri().'?'.$url);
                $oLink->itemTitle = $row->thema;
                $oLink->tooltip   = $title;
                $data['url']      = $oLink->toString();

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
     * @param  integer $count    Number of recorsd
     * @param  string  $language Language
     * @return array
     */
    public function getLatestData($count = PMF_NUMBER_RECORDS_LATEST, $language = null)
    {
        global $sids;

        if ($this->groupSupport) {
            $permPart = sprintf("( fdg.group_id IN (%s)
            OR
                (fdu.user_id = %d AND fdg.group_id IN (%s)))",
                implode(', ', $this->groups),
                $this->user,
                implode(', ', $this->groups));
        } else {
            $permPart = sprintf("( fdu.user_id = %d OR fdu.user_id = -1 )",
                $this->user);
        }

        $now = date('YmdHis');
        $query =
'            SELECT
                fd.id AS id,
                fd.lang AS lang,
                fcr.category_id AS category_id,
                fd.thema AS thema,
                fd.content AS content,
                fd.datum AS datum,
                fv.visits AS visits
            FROM
                '.SQLPREFIX.'faqvisits fv,
                '.SQLPREFIX.'faqdata fd
            LEFT JOIN
                '.SQLPREFIX.'faqcategoryrelations fcr
            ON
                fd.id = fcr.record_id
            AND
                fd.lang = fcr.record_lang
            LEFT JOIN
                '.SQLPREFIX.'faqdata_group AS fdg
            ON
                fd.id = fdg.record_id
            LEFT JOIN
                '.SQLPREFIX.'faqdata_user AS fdu
            ON
                fd.id = fdu.record_id
            WHERE
                    fd.date_start <= \''.$now.'\'
                AND fd.date_end   >= \''.$now.'\'
                AND fd.id = fv.id
                AND fd.lang = fv.lang
                AND fd.active = \'yes\'';

        if (isset($language) && PMF_Language::isASupportedLanguage($language)) {
            $query .= '
            AND
                fd.lang = \''.$language.'\'';
        }
        $query .= '
            AND
                '.$permPart.'
            ORDER BY
                fd.datum DESC';

        $result = $this->db->query($query);
        $latest = array();
        $data = array();

        $i = 0;
        $oldId = 0;
        while (($row = $this->db->fetchObject($result)) && $i < $count ) {
            if ($oldId != $row->id) {
                $data['datum'] = $row->datum;
                $data['thema'] = $row->thema;
                $data['content'] = $row->content;
                $data['visits'] = $row->visits;

                $title = $row->thema;
                $url   = sprintf('%saction=artikel&amp;cat=%d&amp;id=%d&amp;artlang=%s',
                        $sids,
                        $row->category_id,
                        $row->id,
                        $row->lang);
                $oLink = new PMF_Link(PMF_Link::getSystemRelativeUri().'?'.$url);
                $oLink->itemTitle = $row->thema;
                $oLink->tooltip   = $title;
                $data['url']      = $oLink->toString();

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
     * @param  integer $id FAQ record id
     * @param  string  $ip IP
     * @return boolean
     */
    public function votingCheck($id, $ip)
    {
        $check = $_SERVER['REQUEST_TIME'] - 300;
        $query = sprintf(
            "SELECT
                id
            FROM
                %sfaqvoting
            WHERE
                artikel = %d AND (ip = '%s' AND datum > '%s')",
            SQLPREFIX,
            $id,
            $ip,
            $check);
        if ($this->db->numRows($this->db->query($query))) {
            return false;
        }
        return true;
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

        if ($this->db->numRows($result) > 0) {
            $i = 0;
            while ($row = $this->db->fetchObject($result)) {
                $faq = array();
                $faq['id']             = $row->id;
                $faq['solution_id']    = $row->solution_id;
                $faq['revision_id']    = $row->revision_id;
                $faq['lang']           = $row->lang;
                $faq['category_id']    = $row->category_id;
                $faq['active']         = $row->active;
                $faq['sticky']         = $row->sticky;
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
    private function _getSQLQuery($QueryType, $nCatid, $bDownwards, $lang, $date, $faqid = 0)
    {
        $now = date('YmdHis');
        $query = sprintf("
            SELECT
                fd.id AS id,
                fd.solution_id AS solution_id,
                fd.revision_id AS revision_id,
                fd.lang AS lang,
                fcr.category_id AS category_id,
                fd.active AS active,
                fd.sticky AS sticky,
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
                %sfaqdata fd,
                %sfaqvisits fv,
                %sfaqcategoryrelations fcr
            WHERE
                fd.id = fcr.record_id
            AND
                fd.lang = fcr.record_lang
            AND
                fd.date_start <= '%s'
            AND
                fd.date_end   >= '%s'
            AND ",
            SQLPREFIX,
            SQLPREFIX,
            SQLPREFIX,
            $now,
            $now);
        // faqvisits data selection
        if (!empty($faqid)) {
            // Select ONLY the faq with the provided $faqid
            $query .= "fd.id = '".$faqid."' AND ";
        }
        $query .= "fd.id = fv.id
            AND
                fd.lang = fv.lang";
        $needAndOp = true;
        if ((!empty($nCatid)) && is_int($nCatid) && $nCatid > 0) {
            if ($needAndOp) {
                $query .= " AND";
            }
            $query .= " (fcr.category_id = ".$nCatid;
            if ($bDownwards) {
                $query .= $this->_getCatidWhereSequence($nCatid, "OR");
            }
            $query .= ")";
            $needAndOp = true;
        }
        if ((!empty($date)) && PMF_Utils::isLikeOnPMFDate($date)) {
            if ($needAndOp) {
                $query .= " AND";
            }
            $query .= " fd.datum LIKE '".$date."'";
            $needAndOp = true;
        }
        if ((!empty($lang)) && PMF_Utils::isLanguage($lang)) {
            if ($needAndOp) {
                $query .= " AND";
            }
            $query .= " fd.lang = '".$lang."'";
            $needAndOp = true;
        }
        switch ($QueryType) {
            case FAQ_QUERY_TYPE_APPROVAL:
                if ($needAndOp) {
                    $query .= " AND";
                }
                $query .= " fd.active = '".FAQ_SQL_ACTIVE_NO."'";
                $needAndOp = true;
                break;
            case FAQ_QUERY_TYPE_EXPORT_DOCBOOK:
            case FAQ_QUERY_TYPE_EXPORT_PDF:
            case FAQ_QUERY_TYPE_EXPORT_XHTML:
            case FAQ_QUERY_TYPE_EXPORT_XML:
                if ($needAndOp) {
                    $query .= " AND";
                }
                $query .= " fd.active = '".FAQ_SQL_ACTIVE_YES."'";
                $needAndOp = true;
                break;
            default:
                if ($needAndOp) {
                    $query .= " AND";
                }
                $query .= " fd.active = '".FAQ_SQL_ACTIVE_YES."'";
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
                $query .= "\nORDER BY fd.thema";
                break;
            case FAQ_QUERY_TYPE_RSS_LATEST:
                $query .= "\nORDER BY fd.datum DESC";
                break;
            default:
                // Normal ordering
                $query .= "\nORDER BY fcr.category_id, fd.id";
                break;
        }

        return $query;
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

        global $sids;

        $now = date('YmdHis');
        $query = '
            SELECT
                fd.id AS id,
                fd.lang AS lang,
                fd.thema AS thema,
                fcr.category_id AS category_id,
                fv.visits AS visits
            FROM
                '.SQLPREFIX.'faqdata fd
            LEFT JOIN
                '.SQLPREFIX.'faqcategoryrelations fcr
            ON
                fd.id = fcr.record_id
            AND
                fd.lang = fcr.record_lang
            LEFT JOIN
                '.SQLPREFIX.'faqvisits fv
            ON
                fd.id = fv.id
            AND
                fv.lang = fd.lang
            LEFT JOIN
                '.SQLPREFIX.'faqdata_group fdg
            ON
                fd.id = fdg.record_id
            LEFT JOIN
                '.SQLPREFIX.'faqdata_user fdu
            ON
                fd.id = fdu.record_id
            WHERE
                fd.date_start <= \''.$now.'\'
            AND
                fd.date_end   >= \''.$now.'\'
            AND
                fd.active = \'yes\'
            AND
                fcr.category_id = '.$category.'
            AND
                fd.lang = \''.$this->language.'\'
            ORDER BY
                fd.id';

        $result = $this->db->query($query);

        $output = '<ul class="phpmyfaq_ul">';

        while (($row = $this->db->fetchObject($result))) {
            $title = $row->thema;
            $url   = sprintf('%saction=artikel&amp;cat=%d&amp;id=%d&amp;artlang=%s',
                        $sids,
                        $row->category_id,
                        $row->id,
                        $row->lang);
                        
            $oLink            = new PMF_Link(PMF_Link::getSystemRelativeUri().'?'.$url);
            $oLink->itemTitle = $row->thema;
            $oLink->text      = $title;
            $oLink->tooltip   = $title;
            $listItem         = sprintf('<li>%s</li>', $oLink->toHtmlAnchor(), $this->pmf_lang['msgViews']);
            $listItem         = '<li>'.$oLink->toHtmlAnchor().'</li>';
            
            $output .= $listItem;
        }
        
        $output .= '</ul>';

        return $output;
    }

    /**
     * Setter for the language
     * 
     * @param  string $language Language
     * @return void
     */
    public function setLanguage($language)
    {
    	$this->language = $language;
    }
    
    /**
     * Set or unset a faq item flag 
     *
     * @param integer $id   Record id
     * @param string  $lang language code which is valid with PMF_Language::isASupportedLanguage
     * @param boolean $flag weither or not the record is set to sticky
     * @param string  $type type of the flag to set, use the column name
     * 
     * @return boolean
     */
    public function updateRecordFlag($id, $lang, $flag, $type)
    {
        $retval = false;
        
        switch ($type) {
            case 'sticky':
                $flag = (int)$flag;
                break;
                
            case 'active':
                $flag = $flag ? "'yes'" : "'no'";
                break;
                
            default:
                /**
                 * This is because we would run into unknown db column
                 */
                $flag = null;
                break;
        }
        
        if (null !== $flag) {
        
            $update = sprintf("
                UPDATE 
                    %sfaqdata 
                SET 
                    %s = %s 
                WHERE 
                    id = %d 
                AND 
                    lang = '%s'",
                SQLPREFIX,
                $type,
                $flag, 
                $id, 
                $lang);
        
            $retval = (bool)$this->db->query($update);
        
        }
        
        return $retval;
    }
    
    /**
     * Returns the sticky records with URL and Title
     * 
     * @return array
     */
    private function getStickyRecordsData()
    {
        global $sids;
        
        if ($this->groupSupport) {
            $permPart = sprintf("AND
                ( fdg.group_id IN (%s)
            OR
                (fdu.user_id = %d AND fdg.group_id IN (%s)))",
                implode(', ', $this->groups),
                $this->user,
                implode(', ', $this->groups));
        } else {
            $permPart = sprintf("AND
                ( fdu.user_id = %d OR fdu.user_id = -1 )",
                $this->user);
        }
        
        
        $now = date('YmdHis');
        $query = sprintf("
            SELECT
                fd.id AS id,
                fd.lang AS lang,
                fd.thema AS thema,
                fcr.category_id AS category_id
            FROM
                %sfaqdata fd
            LEFT JOIN
                %sfaqcategoryrelations fcr
            ON
                fd.id = fcr.record_id
            AND
                fd.lang = fcr.record_lang
            LEFT JOIN
                %sfaqdata_group AS fdg
            ON
                fd.id = fdg.record_id
            LEFT JOIN
                %sfaqdata_user AS fdu
            ON
                fd.id = fdu.record_id
            WHERE
                fd.lang = '%s'
            AND 
                fd.date_start <= '%s'
            AND 
                fd.date_end   >= '%s'
            AND 
                fd.active = 'yes'
            AND 
                fd.sticky = 1
            %s",
            SQLPREFIX,
            SQLPREFIX,
            SQLPREFIX,
            SQLPREFIX,
            $this->language,
            $now,
            $now,
            $permPart);

        $result = $this->db->query($query);
        $sticky = array();
        $data   = array();

        $oldId = 0;
        while (($row = $this->db->fetchObject($result))) {
            if ($oldId != $row->id) {
                $data['thema'] = $row->thema;

                $title = $row->thema;
                $url   = sprintf('%saction=artikel&amp;cat=%d&amp;id=%d&amp;artlang=%s',
                            $sids,
                            $row->category_id,
                            $row->id,
                            $row->lang);
                $oLink = new PMF_Link(PMF_Link::getSystemRelativeUri().'?'.$url);
                $oLink->itemTitle = $row->thema;
                $oLink->tooltip = $title;
                $data['url'] = $oLink->toString();

                $sticky[] = $data;
            }
            $oldId = $row->id;
        }

        return $sticky;
    }
    
    /**
     * Prepares and returns the sticky records for the frontend
     * 
     * @return array
     */
    public function getStickyRecords()
    {
        $result = $this->getStickyRecordsData();
        $output = array();

        if (count($result) > 0) {
            foreach ($result as $row) {
                $shortTitle         = PMF_Utils::makeShorterText($row['thema'], 8);
                $output['title'][]  = $shortTitle;
                $output['url'][]    = $row['url'];
            }
        } else {
            $output['error'] = $this->pmf_lang['err_noTopTen'];
        }

        return $output;
    }
    
    /**
     * Builds the PDF delivery for the given faq.
     * 
     * @param  integer $currentCategory The category under which we want the PDF to be created.
     * @param  string  $pdfFile         The path to the PDF file. Optional, default: pdf/<faq_id>.pdf.
     * @return mixed
     */
    public function buildPDFFile($currentCategory, $pdfFile = null)
    {
        global $PMF_LANG;

        // Sanity check: stop here if getRecord() has not been called yet
        if (empty($this->faqRecord)) {
            return false;
        }
            
        // Default filename: pdf/<faq_id>.pdf
        if (empty($pdfFile)) {
            $pdfFile = 'pdf' . $this->faqRecord['id'] . '.pdf';
        }
        
        $faqconfig     = PMF_Configuration::getInstance();
        $categoryNode  = new PMF_Category_Node();
        $categoryData  = $categoryNode->fetch($currentCategory);
        
        $pdf      = new PMF_Export_Pdf_Wrapper();
        $pdf->faq = $this->faqRecord;
        
        $pdf->setCategory($categoryData);
        $pdf->setQuestion($this->faqRecord['title']);
        
        // Start building PDF...
        $pdf->Open();
        // Set any item
        $pdf->SetTitle($this->faqRecord['title']);
        $pdf->SetCreator($faqconfig->get('main.titleFAQ').' - powered by phpMyFAQ '.$faqconfig->get('main.currentVersion'));
        $pdf->AliasNbPages();
        $pdf->AddPage();
        $pdf->SetFont('arialunicid0', '', 12);
        $pdf->SetDisplayMode('real');
        $pdf->Ln();
        $pdf->WriteHTML(str_replace('../', '', $this->faqRecord['content']), true);
        $pdf->Ln();
        $pdf->Ln();
        $pdf->SetFont('arialunicid0', '', 11);
        $pdf->Write(5, $PMF_LANG['ad_entry_solution_id'].': #'.$this->faqRecord['solution_id']);
        $pdf->SetAuthor($this->faqRecord['author']);
        $pdf->Ln();
        $pdf->Write(5, $PMF_LANG['msgAuthor'].': '.$this->faqRecord['author']);
        $pdf->Ln();
        $pdf->Write(5, $PMF_LANG['msgLastUpdateArticle'].$this->faqRecord['date']);
        // Build it
        return $pdf->Output($pdfFile);
    }
}
