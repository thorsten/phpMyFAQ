<?php

/**
 * The main FAQ class. Yes, it's very huge.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo Scaramuccia <matteo@scaramuccia.com>
 * @author    Georgi Korchev <korchev@yahoo.com>
 * @author    Adrianna Musiol <musiol@imageaccess.de>
 * @author    Peter Caesar <p.caesar@osmaco.de>
 * @copyright 2005-2020 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2005-12-20
 */

namespace phpMyFAQ;

use phpMyFAQ\Attachment\AttachmentFactory;
use phpMyFAQ\Helper\FaqHelper;
use phpMyFAQ\Instance\Elasticsearch;
use phpMyFAQ\Language\Plurals;

/*
 * SQL constants definitions
 */
define('FAQ_SQL_ACTIVE_YES', 'yes');
define('FAQ_SQL_ACTIVE_NO', 'no');

/*
 * Query type definitions
 */
define('FAQ_QUERY_TYPE_DEFAULT', 'faq_default');
define('FAQ_QUERY_TYPE_APPROVAL', 'faq_approval');
define('FAQ_QUERY_TYPE_EXPORT_PDF', 'faq_export_pdf');
define('FAQ_QUERY_TYPE_EXPORT_XHTML', 'faq_export_xhtml');
define('FAQ_QUERY_TYPE_EXPORT_XML', 'faq_export_xml');
define('FAQ_QUERY_TYPE_RSS_LATEST', 'faq_rss_latest');

/*
 * Sorting type definitions
 */
define('FAQ_SORTING_TYPE_NONE', 0);
define('FAQ_SORTING_TYPE_CATID_FAQID', 1);
define('FAQ_SORTING_TYPE_FAQTITLE_FAQID', 2);
define('FAQ_SORTING_TYPE_DATE_FAQID', 3);
define('FAQ_SORTING_TYPE_FAQID', 4);

/**
 * Class Faq
 *
 * @package phpMyFAQ
 */
class Faq
{
    /**
     * The current FAQ record.
     *
     * @var array
     */
    public $faqRecord = [];

    /**
     * All current FAQ records in an array.
     *
     * @var array
     */
    public $faqRecords = [];

    /**
     * @var Configuration
     */
    private $config;

    /**
     * Language strings.
     *
     * @var string
     */
    private $translation;

    /**
     * Plural form support.
     *
     * @var Plurals
     */
    private $plurals;

    /**
     * Users.
     *
     * @var int
     */
    private $user = -1;

    /**
     * Groups.
     *
     * @var array
     */
    private $groups = [-1];

    /**
     * Flag for Group support.
     *
     * @var bool
     */
    private $groupSupport = false;

    /**
     * Constructor.
     *
     * @param Configuration $config
     */
    public function __construct(Configuration $config)
    {
        global $PMF_LANG, $plr;

        $this->config = $config;
        $this->translation = $PMF_LANG;
        $this->plurals = $plr;

        if ($this->config->get('security.permLevel') !== 'basic') {
            $this->groupSupport = true;
        }
    }

    /**
     * @param int $userId
     */
    public function setUser($userId = -1)
    {
        $this->user = $userId;
    }

    /**
     * @param array $groups
     */
    public function setGroups(array $groups)
    {
        $this->groups = $groups;
    }

    /**
     * This function returns all not expired records from one category.
     *
     * @param int    $categoryId Entity ID
     * @param string $orderBy    Order by
     * @param string $sortBy     Sort by
     *
     * @return array
     * @throws \Exception
     */
    public function getAllRecordPerCategory(int $categoryId, string $orderBy = 'id', string $sortBy = 'ASC'): array
    {
        global $sids;

        $faqData = [];

        if ($orderBy == 'visits') {
            $currentTable = 'fv';
        } else {
            $currentTable = 'fd';
        }

        $now = date('YmdHis');
        $query = sprintf(
            "
            SELECT
                fd.id AS id,
                fd.lang AS lang,
                fd.thema AS thema,
                fd.content AS record_content,
                fd.updated AS updated,
                fcr.category_id AS category_id,
                fv.visits AS visits,
                fd.created AS created
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
                %s
            ORDER BY
                %s.%s %s",
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            $now,
            $now,
            $categoryId,
            $this->config->getLanguage()->getLanguage(),
            $this->queryPermission($this->groupSupport),
            $currentTable,
            $this->config->getDb()->escape($orderBy),
            $this->config->getDb()->escape($sortBy)
        );

        $result = $this->config->getDb()->query($query);
        $num = $this->config->getDb()->numRows($result);

        if ($num > 0) {
            $faqHelper = new FaqHelper($this->config);
            while (($row = $this->config->getDb()->fetchObject($result))) {
                if (empty($row->visits)) {
                    $visits = 0;
                } else {
                    $visits = $row->visits;
                }

                $url = sprintf(
                    '%sindex.php?%saction=faq&cat=%d&id=%d&artlang=%s',
                    $this->config->getDefaultUrl(),
                    $sids,
                    $row->category_id,
                    $row->id,
                    $row->lang
                );
                $oLink = new Link($url, $this->config);
                $oLink->itemTitle = $oLink->text = $oLink->tooltip = $row->thema;

                $faqData[] = array(
                    'record_id' => $row->id,
                    'record_lang' => $row->lang,
                    'category_id' => $row->category_id,
                    'record_title' => $row->thema,
                    'record_preview' => $faqHelper->renderAnswerPreview($row->record_content, 25),
                    'record_link' => $oLink->toString(),
                    'record_updated' => $row->updated,
                    'visits' => $visits,
                    'record_created' => $row->created,
                );
            }
        } else {
            return $faqData;
        }

        return $faqData;
    }

    /**
     * Returns a part of a query to check permissions.
     *
     * @param bool $hasGroupSupport
     *
     * @return string
     */
    protected function queryPermission(bool $hasGroupSupport = false): string
    {
        if ($hasGroupSupport) {
            if (-1 === $this->user) {
                return sprintf(
                    'AND fdg.group_id IN (%s)',
                    implode(', ', $this->groups)
                );
            } else {
                return sprintf(
                    'AND ( fdg.group_id IN (%s) OR (fdu.user_id = %d OR fdg.group_id IN (%s)) )',
                    implode(', ', $this->groups),
                    $this->user,
                    implode(', ', $this->groups)
                );
            }
        } else {
            if (-1 !== $this->user) {
                return sprintf(
                    'AND ( fdu.user_id = %d OR fdu.user_id = -1 )',
                    $this->user
                );
            } else {
                return sprintf(
                    'AND fdu.user_id = -1'
                );
            }
        }
    }

    /**
     * This function returns all not expired records from one category.
     *
     * @param int    $categoryId Entity ID
     * @param string $orderBy    Order by
     * @param string $sortBy     Sort by
     *
     * @return string
     */
    public function renderRecordsByCategoryId(int $categoryId, string $orderBy = 'id', string $sortBy = 'ASC'): string
    {
        global $sids;

        $numPerPage = $this->config->get('records.numberOfRecordsPerPage');
        $page = Filter::filterInput(INPUT_GET, 'seite', FILTER_VALIDATE_INT, 1);
        $output = '';
        $title = '';

        if ($orderBy == 'visits') {
            $currentTable = 'fv';
        } else {
            $currentTable = 'fd';
        }

        // If random FAQs are activated, we don't need an order
        if (true === $this->config->get('records.randomSort')) {
            $order = '';
        } else {
            $order = sprintf(
                'ORDER BY fd.sticky DESC, %s.%s %s',
                $currentTable,
                $this->config->getDb()->escape($orderBy),
                $this->config->getDb()->escape($sortBy)
            );
        }

        $now = date('YmdHis');
        $query = sprintf(
            "
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
            %s
            %s",
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            $now,
            $now,
            $categoryId,
            $this->config->getLanguage()->getLanguage(),
            $this->queryPermission($this->groupSupport),
            $order
        );

        $result = $this->config->getDb()->query($query);
        $num = $this->config->getDb()->numRows($result);
        $pages = (int)ceil($num / $numPerPage);

        if ($page == 1) {
            $first = 0;
        } else {
            $first = $page * $numPerPage - $numPerPage;
        }

        if ($num > 0) {
            if ($pages > 1) {
                $output .= sprintf(
                    '<p><strong>%s %s %s</strong></p>',
                    $this->translation['msgPage'] . $page,
                    $this->translation['msgVoteFrom'],
                    $pages . $this->translation['msgPages']
                );
            }
            $output .= '<ul class="phpmyfaq_ul">';

            $counter = 0;
            $displayedCounter = 0;
            $renderedItems = [];
            while (($row = $this->config->getDb()->fetchObject($result)) && $displayedCounter < $numPerPage) {
                ++$counter;
                if ($counter <= $first) {
                    continue;
                }
                ++$displayedCounter;

                if (empty($row->visits)) {
                    $visits = 0;
                } else {
                    $visits = $row->visits;
                }

                $title = $row->thema;
                $url = sprintf(
                    '%s?%saction=faq&amp;cat=%d&amp;id=%d&amp;artlang=%s',
                    $this->config->getDefaultUrl(),
                    $sids,
                    $row->category_id,
                    $row->id,
                    $row->lang
                );

                $oLink = new Link($url, $this->config);
                $oLink->itemTitle = $oLink->text = $oLink->tooltip = $title;

                // If random FAQs are activated, we don't need sticky FAQs
                if (true === $this->config->get('records.randomSort')) {
                    $row->sticky = 0;
                }

                $renderedItems[$row->id] = sprintf(
                    '<li%s>%s<span id="viewsPerRecord"><br><small>(%s)</small></span></li>',
                    ($row->sticky == 1) ? ' class="sticky-faqs"' : '',
                    $oLink->toHtmlAnchor(),
                    $this->plurals->GetMsg('plmsgViews', $visits)
                );
            }

            // If random FAQs are activated, shuffle the FAQs :-)
            if (true === $this->config->get('records.randomSort')) {
                shuffle($renderedItems);
            }

            $output .= implode("\n", $renderedItems);
            $output .= '</ul>';
        } else {
            return false;
        }

        if ($pages > 1) {
            // Set rewrite URL, if needed
            if ($this->config->get('main.enableRewriteRules')) {
                $link = new Link($this->config->getDefaultUrl(), $this->config);
                $useRewrite = true;
                $rewriteUrl = sprintf(
                    '%scategory/%d/%%d/%s.html',
                    $this->config->getDefaultUrl(),
                    $categoryId,
                    $link->getSEOItemTitle($title)
                );
            } else {
                $useRewrite = false;
                $rewriteUrl = '';
            }
            $baseUrl = sprintf(
                '%s?%saction=show&amp;cat=%d&amp;seite=%d',
                $this->config->getDefaultUrl(),
                (empty($sids) ? '' : $sids),
                $categoryId,
                $page
            );

            $options = array(
                'baseUrl' => $baseUrl,
                'total' => $num,
                'perPage' => $this->config->get('records.numberOfRecordsPerPage'),
                'useRewrite' => $useRewrite,
                'rewriteUrl' => $rewriteUrl,
                'pageParamName' => 'seite',
            );

            $pagination = new Pagination($this->config, $options);
            $output .= $pagination->render();
        }

        return $output;
    }

    /**
     * This function returns all not expired records from the given record ids.
     *
     * @param array  $recordIds Array of record ids
     * @param string $orderBy   Order by
     * @param string $sortBy    Sort by
     *
     * @return string
     */
    public function renderRecordsByFaqIds(array $recordIds, string $orderBy = 'fd.id', string $sortBy = 'ASC'): string
    {
        global $sids;

        $records = implode(', ', $recordIds);
        $page = Filter::filterInput(INPUT_GET, 'seite', FILTER_VALIDATE_INT, 1);
        $taggingId = Filter::filterInput(INPUT_GET, 'tagging_id', FILTER_DEFAULT);
        $output = '';

        $now = date('YmdHis');
        $query = sprintf(
            "
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
                %s
            ORDER BY
                %s %s",
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            $now,
            $now,
            $records,
            $this->config->getLanguage()->getLanguage(),
            $this->queryPermission($this->groupSupport),
            $this->config->getDb()->escape($orderBy),
            $this->config->getDb()->escape($sortBy)
        );

        $result = $this->config->getDb()->query($query);

        $num = $this->config->getDb()->numRows($result);
        $pages = ceil($num / $this->config->get('records.numberOfRecordsPerPage'));

        if ($page == 1) {
            $first = 0;
        } else {
            $first = ($page * $this->config->get('records.numberOfRecordsPerPage')) -
                $this->config->get('records.numberOfRecordsPerPage');
        }

        if ($num > 0) {
            if ($pages > 1) {
                $output .= sprintf(
                    '<p><strong>%s %s %s</strong></p>',
                    $this->translation['msgPage'] . $page,
                    $this->translation['msgVoteFrom'],
                    $pages . $this->translation['msgPages']
                );
            }
            $output .= '<ul class="phpmyfaq_ul">';
            $counter = 0;
            $displayedCounter = 0;

            $lastFaqId = 0;
            while (
                ($row = $this->config->getDb()->fetchObject($result)) &&
                $displayedCounter < $this->config->get('records.numberOfRecordsPerPage')
            ) {
                ++$counter;
                if ($counter <= $first) {
                    continue;
                }
                ++$displayedCounter;

                if ($lastFaqId == $row->id) {
                    continue; // Don't show multiple FAQs
                }

                if (empty($row->visits)) {
                    $visits = 0;
                } else {
                    $visits = $row->visits;
                }

                $title = $row->thema;
                $url = sprintf(
                    '%s?%saction=faq&amp;cat=%d&amp;id=%d&amp;artlang=%s',
                    $this->config->getDefaultUrl(),
                    $sids,
                    $row->category_id,
                    $row->id,
                    $row->lang
                );
                $oLink = new Link($url, $this->config);
                $oLink->itemTitle = $row->thema;
                $oLink->text = $title;
                $oLink->tooltip = $title;
                $listItem = sprintf(
                    '<li>%s<br><small>(%s)</small></li>',
                    $oLink->toHtmlAnchor(),
                    $this->plurals->GetMsg('plmsgViews', $visits)
                );

                $output .= $listItem;

                $lastFaqId = $row->id;
            }
            $output .= '</ul><span id="totFaqRecords" style="display: none;">' . $num . '</span>';
        } else {
            return false;
        }

        if ($num > $this->config->get('records.numberOfRecordsPerPage')) {
            $output .= '<p class="text-center"><strong>';
            if (!isset($page)) {
                $page = 1;
            }
            $vor = $page - 1;
            $next = $page + 1;
            if ($vor != 0) {
                $url = $sids . '&amp;action=search&amp;tagging_id=' . $taggingId . '&amp;seite=' . $vor;
                $oLink = new Link($this->config->getDefaultUrl() . '?' . $url, $this->config);
                $oLink->itemTitle = 'tag';
                $oLink->text = $this->translation['msgPrevious'];
                $oLink->tooltip = $this->translation['msgPrevious'];
                $output .= '[ ' . $oLink->toHtmlAnchor() . ' ]';
            }
            $output .= ' ';
            if ($next <= $pages) {
                $url = $sids . '&amp;action=search&amp;tagging_id=' . $taggingId . '&amp;seite=' . $next;
                $oLink = new Link($this->config->getDefaultUrl() . '?' . $url, $this->config);
                $oLink->itemTitle = 'tag';
                $oLink->text = $this->translation['msgNext'];
                $oLink->tooltip = $this->translation['msgNext'];
                $output .= '[ ' . $oLink->toHtmlAnchor() . ' ]';
            }
            $output .= '</strong></p>';
        }

        return $output;
    }

    /**
     * Returns an array with all data from a FAQ record.
     *
     * @param int  $faqId         FAQ ID
     * @param int  $faqRevisionId Revision ID
     * @param bool $isAdmin       Must be true if it is called by an admin/author context
     */
    public function getRecord(int $faqId, int $faqRevisionId = null, bool $isAdmin = false)
    {
        global $PMF_LANG;

        $currentLanguage = $this->config->getLanguage()->getLanguage();
        $defaultLanguage = $this->config->getDefaultLanguage();

        $result = $this->getRecordResult($faqId, $currentLanguage, $faqRevisionId, $isAdmin);

        if (0 === $this->config->getDb()->numRows($result)) {
            $result = $this->getRecordResult($faqId, $defaultLanguage, $faqRevisionId, $isAdmin);
        }

        if ($row = $this->config->getDb()->fetchObject($result)) {
            $question = nl2br($row->thema);
            $answer = $row->content;
            $active = ('yes' === $row->active);
            $expired = (date('YmdHis') > $row->date_end);

            if (!$isAdmin) {
                if (!$active) {
                    $answer = $this->translation['err_inactiveArticle'];
                }
                if ($expired) {
                    $answer = $this->translation['err_expiredArticle'];
                }
            }

            $this->faqRecord = [
                'id' => $row->id,
                'lang' => $row->lang,
                'solution_id' => $row->solution_id,
                'revision_id' => $row->revision_id,
                'active' => $row->active,
                'sticky' => $row->sticky,
                'keywords' => $row->keywords,
                'title' => $question,
                'content' => $answer,
                'author' => $row->author,
                'email' => $row->email,
                'comment' => $row->comment,
                'date' => Date::createIsoDate($row->updated),
                'dateStart' => $row->date_start,
                'dateEnd' => $row->date_end,
                'linkState' => $row->links_state,
                'linkCheckDate' => $row->links_check_date,
                'notes' => $row->notes,
                'created' => $row->created,
            ];
        } else {
            $this->faqRecord = [
                'id' => $faqId,
                'lang' => $currentLanguage,
                'solution_id' => 42,
                'revision_id' => $faqRevisionId,
                'active' => 'no',
                'sticky' => 0,
                'keywords' => '',
                'title' => '',
                'content' => $PMF_LANG['msgAccessDenied'],
                'author' => '',
                'email' => '',
                'comment' => '',
                'date' => Date::createIsoDate(date('YmdHis')),
                'dateStart' => '',
                'dateEnd' => '',
                'linkState' => '',
                'linkCheckDate' => '',
                'notes' => '',
                'created' => date('c'),
            ];
        }
    }

    /**
     * Executes a query to retrieve a single FAQ.
     *
     * @param int    $faqId
     * @param string $faqLanguage
     * @param int    $faqRevisionId
     * @param bool   $isAdmin
     *
     * @return mixed
     */
    public function getRecordResult($faqId, $faqLanguage, $faqRevisionId = null, $isAdmin = false)
    {
        $query = sprintf(
            "SELECT
                 id, lang, solution_id, revision_id, active, sticky, keywords,
                 thema, content, author, email, comment, updated, links_state,
                 links_check_date, date_start, date_end, created, notes
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
                %s",
            Database::getTablePrefix(),
            isset($faqRevisionId) ? 'faqdata_revisions' : 'faqdata',
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            $faqId,
            isset($faqRevisionId) ? 'AND revision_id = ' . $faqRevisionId : '',
            $faqLanguage,
            ($isAdmin) ? 'AND 1=1' : $this->queryPermission($this->groupSupport)
        );

        return $this->config->getDb()->query($query);
    }

    /**
     * Return records from given IDs
     *
     * @param  array $faqIds
     * @return array
     * @throws \Exception
     */
    public function getRecordsByIds(array $faqIds): array
    {
        $faqRecords = [];

        $query = sprintf(
            "SELECT
                 fd.id AS id,
                 fd.lang AS lang,
                 fd.thema AS question,
                 fd.content AS answer,
                 fd.updated AS updated,
                 fd.created AS created,
                 fcr.category_id AS category_id,
                 fv.visits AS visits
            FROM
                %sfaqdata fd
            LEFT JOIN
                %sfaqcategoryrelations fcr
            ON
                fd.id = fcr.record_id
            AND
                fd.lang = fcr.record_lang
            LEFT JOIN
                %sfaqdata_group fdg
            ON
                fd.id = fdg.record_id
            LEFT JOIN
                %sfaqvisits AS fv
            ON
                fd.id = fv.id
            AND
                fv.lang = fd.lang
            LEFT JOIN
                %sfaqdata_user fdu
            ON
                fd.id = fdu.record_id
            WHERE
                fd.id IN (%s)
            AND
                fd.lang = '%s'
                %s",
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            implode(',', $faqIds),
            $this->config->getLanguage()->getLanguage(),
            $this->queryPermission($this->groupSupport)
        );

        $result = $this->config->getDb()->query($query);

        $faqHelper = new FaqHelper($this->config);
        while ($row = $this->config->getDb()->fetchObject($result)) {
            if (empty($row->visits)) {
                $visits = 0;
            } else {
                $visits = $row->visits;
            }

            $url = sprintf(
                '%sindex.php?action=faq&cat=%d&id=%d&artlang=%s',
                $this->config->getDefaultUrl(),
                $row->category_id,
                $row->id,
                $row->lang
            );
            $oLink = new Link($url, $this->config);
            $oLink->itemTitle = $oLink->text = $oLink->tooltip = $row->question;

            $faqRecords[] = [
                'record_id' => (int)$row->id,
                'record_lang' => $row->lang,
                'category_id' => (int)$row->category_id,
                'record_title' => $row->question,
                'record_preview' => $faqHelper->renderAnswerPreview($row->answer, 25),
                'record_link' => $oLink->toString(),
                'record_updated' => Date::createIsoDate($row->updated) . ':00',
                'visits' => (int)$visits,
                'record_created' => $row->created
            ];
        }

        return $faqRecords;
    }

    /**
     * Adds a new record.
     *
     * @param array $data      Array of FAQ data
     * @param bool  $newRecord Do not create a new ID if false
     *
     * @return int
     */
    public function addRecord(array $data, $newRecord = true)
    {
        if ($newRecord) {
            $recordId = $this->config->getDb()->nextId(Database::getTablePrefix() . 'faqdata', 'id');
        } else {
            $recordId = $data['id'];
        }

        // Add new entry
        $query = sprintf(
            "INSERT INTO %sfaqdata VALUES
            (%d, '%s', %d, %d, '%s', %d, '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', %d, '%s', '%s', '%s', '%s')",
            Database::getTablePrefix(),
            $recordId,
            $data['lang'],
            $this->getSolutionId(),
            0,
            $data['active'],
            $data['sticky'],
            $this->config->getDb()->escape($data['keywords']),
            $this->config->getDb()->escape($data['thema']),
            $this->config->getDb()->escape($data['content']),
            $this->config->getDb()->escape($data['author']),
            $data['email'],
            $data['comment'],
            $data['date'],
            $data['linkState'],
            $data['linkDateCheck'],
            $data['dateStart'],
            $data['dateEnd'],
            date('Y-m-d H:i:s'),
            $data['notes']
        );

        $this->config->getDb()->query($query);

        return $recordId;
    }

    /**
     * Gets the latest solution id for a FAQ record.
     *
     * @return int
     */
    public function getSolutionId()
    {
        $latestId = 0;

        $query = sprintf(
            '
            SELECT
                MAX(solution_id) AS solution_id
            FROM
                %sfaqdata',
            Database::getTablePrefix()
        );

        $result = $this->config->getDb()->query($query);

        if ($result && $row = $this->config->getDb()->fetchObject($result)) {
            $latestId = $row->solution_id;
        }

        if ($latestId < PMF_SOLUTION_ID_START_VALUE) {
            $nextSolutionId = PMF_SOLUTION_ID_START_VALUE;
        } else {
            $nextSolutionId = $latestId + PMF_SOLUTION_ID_INCREMENT_VALUE;
        }

        return $nextSolutionId;
    }

    /**
     * Updates a record.
     *
     * @param array $data Array of FAQ data
     *
     * @return bool
     */
    public function updateRecord(array $data)
    {
        // Update entry
        $query = sprintf(
            "
            UPDATE
                %sfaqdata
            SET
                revision_id = %d,
                active = '%s',
                sticky = %d,
                keywords = '%s',
                thema = '%s',
                content = '%s',
                author = '%s',
                email = '%s',
                comment = '%s',
                updated = '%s',
                links_state = '%s',
                links_check_date = %d,
                date_start = '%s',
                date_end = '%s',
                notes = '%s'
            WHERE
                id = %d
            AND
                lang = '%s'",
            Database::getTablePrefix(),
            $data['revision_id'],
            $data['active'],
            $data['sticky'],
            $this->config->getDb()->escape($data['keywords']),
            $this->config->getDb()->escape($data['thema']),
            $this->config->getDb()->escape($data['content']),
            $this->config->getDb()->escape($data['author']),
            $data['email'],
            $data['comment'],
            $data['date'],
            $data['linkState'],
            $data['linkDateCheck'],
            $data['dateStart'],
            $data['dateEnd'],
            $data['notes'],
            $data['id'],
            $data['lang']
        );

        $this->config->getDb()->query($query);

        return true;
    }

    /**
     * Deletes a record and all the dependencies.
     *
     * @param int    $recordId   Record id
     * @param string $recordLang Record language
     *
     * @return bool
     * @throws Attachment\AttachmentException
     * @throws Attachment\Filesystem\File\FileException
     */
    public function deleteRecord($recordId, $recordLang)
    {
        $solutionId = $this->getSolutionIdFromId($recordId, $recordLang);

        $queries = array(
            sprintf(
                "DELETE FROM %sfaqchanges WHERE beitrag = %d AND lang = '%s'",
                Database::getTablePrefix(),
                $recordId,
                $recordLang
            ),
            sprintf(
                "DELETE FROM %sfaqcategoryrelations WHERE record_id = %d AND record_lang = '%s'",
                Database::getTablePrefix(),
                $recordId,
                $recordLang
            ),
            sprintf(
                "DELETE FROM %sfaqdata WHERE id = %d AND lang = '%s'",
                Database::getTablePrefix(),
                $recordId,
                $recordLang
            ),
            sprintf(
                "DELETE FROM %sfaqdata_revisions WHERE id = %d AND lang = '%s'",
                Database::getTablePrefix(),
                $recordId,
                $recordLang
            ),
            sprintf(
                "DELETE FROM %sfaqvisits WHERE id = %d AND lang = '%s'",
                Database::getTablePrefix(),
                $recordId,
                $recordLang
            ),
            sprintf(
                'DELETE FROM %sfaqdata_user WHERE record_id = %d',
                Database::getTablePrefix(),
                $recordId,
                $recordLang
            ),
            sprintf(
                'DELETE FROM %sfaqdata_group WHERE record_id = %d',
                Database::getTablePrefix(),
                $recordId,
                $recordLang
            ),
            sprintf(
                'DELETE FROM %sfaqdata_tags WHERE record_id = %d',
                Database::getTablePrefix(),
                $recordId
            ),
            sprintf(
                'DELETE FROM %sfaqdata_tags WHERE %sfaqdata_tags.record_id NOT IN (SELECT %sfaqdata.id FROM %sfaqdata)',
                Database::getTablePrefix(),
                Database::getTablePrefix(),
                Database::getTablePrefix(),
                Database::getTablePrefix()
            ),
            sprintf(
                'DELETE FROM %sfaqcomments WHERE id = %d',
                Database::getTablePrefix(),
                $recordId
            ),
            sprintf(
                'DELETE FROM %sfaqvoting WHERE artikel = %d',
                Database::getTablePrefix(),
                $recordId
            ),
        );

        foreach ($queries as $query) {
            $this->config->getDb()->query($query);
        }

        // Delete possible attachments
        $attId = AttachmentFactory::fetchByRecordId($this->config, $recordId);
        $attachment = AttachmentFactory::create($attId);
        $attachment->delete();

        // Delete possible Elasticsearch documents
        if ($this->config->get('search.enableElasticsearch')) {
            $esInstance = new Elasticsearch($this->config);
            $esInstance->delete($solutionId);
        }

        return true;
    }

    /**
     * Returns the solution ID from a given ID and language
     *
     * @param integer $faqId
     * @param string  $faqLang
     *
     * @return int
     */
    public function getSolutionIdFromId($faqId, $faqLang)
    {
        $query = sprintf(
            "
            SELECT
                solution_id
            FROM
                %sfaqdata
            WHERE
                id = %d
                AND
                lang = '%s'",
            Database::getTablePrefix(),
            (int)$faqId,
            $this->config->getDb()->escape($faqLang)
        );

        $result = $this->config->getDb()->query($query);

        if ($row = $this->config->getDb()->fetchObject($result)) {
            return $row->solution_id;
        }

        return $this->getSolutionId();
    }

    /**
     * Checks if a record is already translated.
     *
     * @param int    $recordId   Record id
     * @param string $recordLang Record language
     *
     * @return bool
     */
    public function hasTranslation($recordId, $recordLang)
    {
        $query = sprintf(
            "
            SELECT
                id, lang
            FROM
                %sfaqdata
            WHERE
                id = %d
            AND
                lang = '%s'",
            Database::getTablePrefix(),
            $recordId,
            $recordLang
        );

        $result = $this->config->getDb()->query($query);

        if ($this->config->getDb()->numRows($result)) {
            return true;
        }

        return false;
    }

    /**
     * Checks, if comments are disabled for the FAQ record.
     *
     * @param int    $recordId    Id of FAQ or news entry
     * @param string $recordLang  Language
     * @param string $commentType Type of comment: faq or news
     *
     * @return bool true, if comments are disabled
     */
    public function commentDisabled($recordId, $recordLang, $commentType = 'faq')
    {
        if ('news' == $commentType) {
            $table = 'faqnews';
        } else {
            $table = 'faqdata';
        }

        $query = sprintf(
            "
            SELECT
                comment
            FROM
                %s%s
            WHERE
                id = %d
            AND
                lang = '%s'",
            Database::getTablePrefix(),
            $table,
            $recordId,
            $recordLang
        );

        $result = $this->config->getDb()->query($query);

        if ($row = $this->config->getDb()->fetchObject($result)) {
            return ($row->comment === 'y') ? false : true;
        } else {
            return true;
        }
    }

    /**
     * Adds new category relations to a record.
     *
     * @param array  $categories Array of categories
     * @param int    $recordId   Record id
     * @param string $language   Language
     *
     * @return bool
     */
    public function addCategoryRelations(array $categories, $recordId, $language)
    {
        if (!is_array($categories)) {
            return false;
        }

        foreach ($categories as $_category) {
            $this->config->getDb()->query(
                sprintf(
                    "INSERT INTO
                    %sfaqcategoryrelations
                VALUES
                    (%d, '%s', %d, '%s')",
                    Database::getTablePrefix(),
                    $_category,
                    $language,
                    $recordId,
                    $language
                )
            );
        }

        return true;
    }

    /**
     * Deletes category relations to a record.
     *
     * @param int    $recordId   Record id
     * @param string $recordLang Language
     *
     * @return bool
     */
    public function deleteCategoryRelations($recordId, $recordLang)
    {
        $query = sprintf(
            "
            DELETE FROM
                %sfaqcategoryrelations
            WHERE
                record_id = %d
            AND
                record_lang = '%s'",
            Database::getTablePrefix(),
            $recordId,
            $recordLang
        );
        $this->config->getDb()->query($query);

        return true;
    }

    /**
     * Returns an array with all data from a FAQ record.
     *
     * @param int $solutionId Solution ID
     */
    public function getRecordBySolutionId($solutionId)
    {
        $query = sprintf(
            'SELECT
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
                %s',
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            $solutionId,
            $this->queryPermission($this->groupSupport)
        );

        $result = $this->config->getDb()->query($query);

        if ($row = $this->config->getDb()->fetchObject($result)) {
            $question = nl2br($row->thema);
            $content = $row->content;
            $active = ('yes' == $row->active);
            $expired = (date('YmdHis') > $row->date_end);

            if (!$active) {
                $content = $this->translation['err_inactiveArticle'];
            }
            if ($expired) {
                $content = $this->translation['err_expiredArticle'];
            }

            $this->faqRecord = array(
                'id' => $row->id,
                'lang' => $row->lang,
                'solution_id' => $row->solution_id,
                'revision_id' => $row->revision_id,
                'active' => $row->active,
                'sticky' => $row->sticky,
                'keywords' => $row->keywords,
                'title' => $question,
                'content' => $content,
                'author' => $row->author,
                'email' => $row->email,
                'comment' => $row->comment,
                'date' => Date::createIsoDate($row->updated),
                'dateStart' => $row->date_start,
                'dateEnd' => $row->date_end,
                'linkState' => $row->links_state,
                'linkCheckDate' => $row->links_check_date,
                'notes' => $row->notes
            );
        }
    }

    /**
     * Gets the record ID from a given solution ID.
     *
     * @param int $solutionId Solution ID
     *
     * @return array
     */
    public function getIdFromSolutionId($solutionId)
    {
        $query = sprintf(
            '
            SELECT
                fd.id,
                fd.lang,
                fd.thema AS question,
                fd.content,
                fcr.category_id AS category_id
            FROM
                %sfaqdata fd
            LEFT JOIN
                %sfaqcategoryrelations fcr
            ON
                fd.id = fcr.record_id
            AND
                fd.lang = fcr.record_lang
            WHERE
                fd.solution_id = %d',
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            $solutionId
        );

        $result = $this->config->getDb()->query($query);

        if ($row = $this->config->getDb()->fetchObject($result)) {
            return [
                'id' => $row->id,
                'lang' => $row->lang,
                'question' => $row->question,
                'content' => $row->content,
                'category_id' => $row->category_id
            ];
        }

        return [];
    }

    /**
     * Returns an array with all data from all FAQ records.
     *
     * @param int    $sortType  Sorting type
     * @param array  $condition Condition
     * @param string $sortOrder Sorting order
     */
    public function getAllRecords($sortType = FAQ_SORTING_TYPE_CATID_FAQID, array $condition = null, $sortOrder = 'ASC')
    {
        $where = '';
        if (!is_null($condition)) {
            $num = count($condition);
            $where = 'WHERE ';
            foreach ($condition as $field => $data) {
                --$num;
                $where .= $field;
                if (is_array($data)) {
                    $where .= ' IN (';
                    $separator = '';
                    foreach ($data as $value) {
                        $where .= $separator . "'" . $this->config->getDb()->escape($value) . "'";
                        $separator = ', ';
                    }
                    $where .= ')';
                } else {
                    $where .= " = '" . $this->config->getDb()->escape($data) . "'";
                }
                if ($num > 0) {
                    $where .= ' AND ';
                }
            }
        }

        switch ($sortType) {
            case FAQ_SORTING_TYPE_CATID_FAQID:
                $orderBy = sprintf(
                    '
            ORDER BY
                fcr.category_id,
                fd.id %s',
                    $sortOrder
                );
                break;

            case FAQ_SORTING_TYPE_FAQID:
                $orderBy = sprintf(
                    '
            ORDER BY
                fd.id %s',
                    $sortOrder
                );
                break;

            case FAQ_SORTING_TYPE_FAQTITLE_FAQID:
                $orderBy = sprintf(
                    '
            ORDER BY
                fcr.category_id,
                fd.thema %s',
                    $sortOrder
                );
                break;

            case FAQ_SORTING_TYPE_DATE_FAQID:
                $orderBy = sprintf(
                    '
            ORDER BY
                fcr.category_id,
                fd.updated %s',
                    $sortOrder
                );
                break;

            default:
                $orderBy = '';
                break;
        }

        // prevents multiple display of FAQ in case it is tagged under multiple groups.
        $groupBy = ' group by fd.id, fcr.category_id,fd.solution_id,fd.revision_id,fd.active,fd.sticky,fd.keywords,' .
            'fd.thema,fd.content,fd.author,fd.email,fd.comment,fd.updated,fd.links_state,fd.links_check_date,' .
            'fd.date_start,fd.date_end,fd.sticky,fd.created,fd.notes,fd.lang ';
        $query = sprintf(
            '
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
                fd.updated AS updated,
                fd.links_state AS links_state,
                fd.links_check_date AS links_check_date,
                fd.date_start AS date_start,
                fd.date_end AS date_end,
                fd.sticky AS sticky,
                fd.created AS created,
                fd.notes AS notes
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
            %s
            %s
            %s
            %s',
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            $where,
            $this->queryPermission($this->groupSupport),
            $groupBy,
            $orderBy
        );

        $result = $this->config->getDb()->query($query);

        while ($row = $this->config->getDb()->fetchObject($result)) {
            $content = $row->content;
            $active = ('yes' == $row->active);
            $expired = (date('YmdHis') > $row->date_end);

            if (!$active) {
                $content = $this->translation['err_inactiveArticle'];
            }
            if ($expired) {
                $content = $this->translation['err_expiredArticle'];
            }

            $this->faqRecords[] = [
                'id' => $row->id,
                'category_id' => $row->category_id,
                'lang' => $row->lang,
                'solution_id' => $row->solution_id,
                'revision_id' => $row->revision_id,
                'active' => $row->active,
                'sticky' => $row->sticky,
                'keywords' => $row->keywords,
                'title' => $row->thema,
                'content' => $content,
                'author' => $row->author,
                'email' => $row->email,
                'comment' => $row->comment,
                'updated' => Date::createIsoDate($row->updated, 'Y-m-d H:i:s'),
                'dateStart' => $row->date_start,
                'dateEnd' => $row->date_end,
                'created' => $row->created,
                'notes' => $row->notes
            ];
        }
    }

    /**
     * Returns the FAQ record title from the ID and language.
     *
     * @param int $id Record id
     *
     * @return string
     */
    public function getRecordTitle($id)
    {
        if (isset($this->faqRecord['id']) && ($this->faqRecord['id'] == $id)) {
            return $this->faqRecord['title'];
        }

        $question = '';

        $query = sprintf(
            "SELECT
                thema AS question
            FROM
                %sfaqdata
            WHERE
                id = %d AND lang = '%s'",
            Database::getTablePrefix(),
            $id,
            $this->config->getLanguage()->getLanguage()
        );
        $result = $this->config->getDb()->query($query);

        if ($this->config->getDb()->numRows($result) > 0) {
            while ($row = $this->config->getDb()->fetchObject($result)) {
                $question = Strings::htmlspecialchars($row->question);
            }
        } else {
            $question = $this->translation['no_cats'];
        }

        return $question;
    }

    /**
     * Gets all revisions from a given record ID.
     *
     * @param int    $recordId   Record id
     * @param string $recordLang Record language
     *
     * @return array
     */
    public function getRevisionIds($recordId, $recordLang)
    {
        $revisionData = [];

        $query = sprintf(
            "
            SELECT
                revision_id, updated, author
            FROM
                %sfaqdata_revisions
            WHERE
                id = %d
            AND
                lang = '%s'
            ORDER BY
                revision_id",
            Database::getTablePrefix(),
            $recordId,
            $recordLang
        );

        $result = $this->config->getDb()->query($query);

        if ($this->config->getDb()->numRows($result) > 0) {
            while ($row = $this->config->getDb()->fetchObject($result)) {
                $revisionData[] = [
                    'revision_id' => $row->revision_id,
                    'updated' => $row->updated,
                    'author' => $row->author,
                ];
            }
        }

        return $revisionData;
    }

    /**
     * Adds a new revision from a given record ID.
     *
     * @param int    $recordId   Record id
     * @param string $recordLang Record language
     *
     * @return bool
     */
    public function addNewRevision($recordId, $recordLang)
    {
        $query = sprintf(
            "
            INSERT INTO
                %sfaqdata_revisions
            SELECT * FROM
                %sfaqdata
            WHERE
                id = %d
            AND
                lang = '%s'",
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            $recordId,
            $recordLang
        );
        $this->config->getDb()->query($query);

        return true;
    }

    /**
     * Returns the keywords of a FAQ record from the ID and language.
     *
     * @param int $id record id
     *
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
            Database::getTablePrefix(),
            $id,
            $this->config->getLanguage()->getLanguage()
        );

        $result = $this->config->getDb()->query($query);

        if ($this->config->getDb()->numRows($result) > 0) {
            $row = $this->config->getDb()->fetchObject($result);

            return Strings::htmlspecialchars($row->keywords, ENT_QUOTES, 'utf-8');
        } else {
            return '';
        }
    }

    /**
     * Returns a answer preview of the FAQ record.
     *
     * @param int $recordId  FAQ record ID
     * @param int $wordCount Number of words, default: 12
     *
     * @return string
     */
    public function getRecordPreview($recordId, $wordCount = 12)
    {
        if (isset($this->faqRecord['id']) && ((int)$this->faqRecord['id'] === (int)$recordId)) {
            $answerPreview = $this->faqRecord['content'];

            return Utils::makeShorterText($answerPreview, $wordCount);
        }

        $query = sprintf(
            "
            SELECT
                content as answer
            FROM
                %sfaqdata
            WHERE 
                id = %d 
            AND 
                lang = '%s'",
            Database::getTablePrefix(),
            $recordId,
            $this->config->getLanguage()->getLanguage()
        );

        $result = $this->config->getDb()->query($query);

        if ($this->config->getDb()->numRows($result) > 0) {
            $row = $this->config->getDb()->fetchObject($result);
            $answerPreview = strip_tags($row->answer);
        } else {
            $answerPreview = $this->config->get('main.metaDescription');
        }

        return Utils::makeShorterText($answerPreview, $wordCount);
    }

    /**
     * Returns the number of activated and not expired records, optionally
     * not limited to the current language.
     *
     * @param string $language Language
     *
     * @return int
     */
    public function getNumberOfRecords($language = null)
    {
        $now = date('YmdHis');

        $query = sprintf(
            "
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
                date_end >= '%s'",
            Database::getTablePrefix(),
            null == $language ? '' : "AND lang = '" . $language . "'",
            $now,
            $now
        );

        $num = $this->config->getDb()->numRows($this->config->getDb()->query($query));

        if ($num > 0) {
            return $num;
        } else {
            return 0;
        }
    }

    /**
     * This function generates a list with the most voted or most visited records.
     *
     * @param  string $type Type definition visits/voted
     * @return array
     */
    public function getTopTen($type = 'visits')
    {
        if ('visits' == $type) {
            $result = $this->getTopTenData(PMF_NUMBER_RECORDS_TOPTEN, 0, $this->config->getLanguage()->getLanguage());
        } else {
            $result = $this->getTopVotedData(PMF_NUMBER_RECORDS_TOPTEN, $this->config->getLanguage()->getLanguage());
        }
        $output = [];

        if (count($result) > 0) {
            foreach ($result as $row) {
                if ('visits' == $type) {
                    $output['title'][] = Utils::makeShorterText($row['question'], 8);
                    $output['preview'][] = $row['question'];
                    $output['url'][] = $row['url'];
                    $output['visits'][] = $this->plurals->GetMsg('plmsgViews', $row['visits']);
                } else {
                    $output['title'][] = Utils::makeShorterText($row['question'], 8);
                    $output['preview'][] = $row['question'];
                    $output['url'][] = $row['url'];
                    $output['voted'][] = sprintf(
                        '%s %s 5 - %s',
                        round($row['avg'], 2),
                        $this->translation['msgVoteFrom'],
                        $this->plurals->GetMsg('plmsgVotes', $row['user'])
                    );
                }
            }
        } else {
            $output['error'] = $this->translation['err_noTopTen'];
        }

        return $output;
    }

    /**
     * This function generates the Top Ten data with the mosted viewed records.
     *
     * @param int    $count      Number of records
     * @param int    $categoryId Entity ID
     * @param string $language   Language
     *
     * @return array
     */
    public function getTopTenData($count = PMF_NUMBER_RECORDS_TOPTEN, $categoryId = 0, $language = null)
    {
        global $sids;

        $now = date('YmdHis');
        $query =
            '            SELECT
                fd.id AS id,
                fd.lang AS lang,
                fd.thema AS question,
                fd.content AS answer,
                fd.updated AS updated,
                fcr.category_id AS category_id,
                fv.visits AS visits,
                fv.last_visit AS last_visit,
                fdg.group_id AS group_id,
                fdu.user_id AS user_id
            FROM
                ' . Database::getTablePrefix() . 'faqvisits fv,
                ' . Database::getTablePrefix() . 'faqdata fd
            LEFT JOIN
                ' . Database::getTablePrefix() . 'faqcategoryrelations fcr
            ON
                fd.id = fcr.record_id
            AND
                fd.lang = fcr.record_lang
            LEFT JOIN
                ' . Database::getTablePrefix() . 'faqdata_group AS fdg
            ON
                fd.id = fdg.record_id
            LEFT JOIN
                ' . Database::getTablePrefix() . 'faqdata_user AS fdu
            ON
                fd.id = fdu.record_id
            WHERE
                    fd.date_start <= \'' . $now . '\'
                AND fd.date_end   >= \'' . $now . '\'
                AND fd.id = fv.id
                AND fd.lang = fv.lang
                AND fd.active = \'yes\'';

        if (isset($categoryId) && is_numeric($categoryId) && ($categoryId != 0)) {
            $query .= '
            AND
                fcr.category_id = \'' . $categoryId . '\'';
        }
        if (isset($language) && Language::isASupportedLanguage($language)) {
            $query .= '
            AND
                fd.lang = \'' . $language . '\'';
        }
        $query .= '
                ' . $this->queryPermission($this->groupSupport) . '

            GROUP BY
                fd.id,fd.lang,fcr.category_id,fv.visits,fv.last_visit,fdg.group_id,fdu.user_id
            ORDER BY
                fv.visits DESC';

        $result = $this->config->getDb()->query($query);
        $topTen = [];
        $data = [];

        if ($result) {
            while ($row = $this->config->getDb()->fetchObject($result)) {
                if ($this->groupSupport) {
                    if (!in_array($row->user_id, array(-1, $this->user)) || !in_array($row->group_id, $this->groups)) {
                        continue;
                    }
                } else {
                    if (!in_array($row->user_id, array(-1, $this->user))) {
                        continue;
                    }
                }

                $data['visits'] = (int)$row->visits;
                $data['question'] = $row->question;
                $data['answer'] = $row->answer;
                $data['date'] = Date::createIsoDate($row->updated, DATE_ISO8601, true);
                $data['last_visit'] = date('c', $row->last_visit);

                $title = $row->question;
                $url = sprintf(
                    '%sindex.php?%saction=faq&amp;cat=%d&amp;id=%d&amp;artlang=%s',
                    $this->config->getDefaultUrl(),
                    $sids,
                    $row->category_id,
                    $row->id,
                    $row->lang
                );
                $oLink = new Link($url, $this->config);
                $oLink->itemTitle = $row->question;
                $oLink->tooltip = $title;
                $data['url'] = $oLink->toString();

                $topTen[$row->id] = $data;

                if (count($topTen) === $count) {
                    break;
                }
            }

            array_multisort($topTen, SORT_DESC);
        }

        return $topTen;
    }

    /**
     * This function generates a data-set with the most voted FAQs.
     *
     * @param int    $count    Number of records
     * @param string $language Language
     *
     * @return array
     */
    public function getTopVotedData($count = PMF_NUMBER_RECORDS_TOPTEN, $language = null)
    {
        global $sids;

        $topten = $data = [];

        $now = date('YmdHis');
        $query =
            '            SELECT
                fd.id AS id,
                fd.lang AS lang,
                fd.thema AS thema,
                fd.updated AS updated,
                fcr.category_id AS category_id,
                (fv.vote/fv.usr) AS avg,
                fv.usr AS user
            FROM
                ' . Database::getTablePrefix() . 'faqvoting fv,
                ' . Database::getTablePrefix() . 'faqdata fd
            LEFT JOIN
                ' . Database::getTablePrefix() . 'faqcategoryrelations fcr
            ON
                fd.id = fcr.record_id
            AND
                fd.lang = fcr.record_lang
            LEFT JOIN
                ' . Database::getTablePrefix() . 'faqdata_group AS fdg
            ON
                fd.id = fdg.record_id
            LEFT JOIN
                ' . Database::getTablePrefix() . 'faqdata_user AS fdu
            ON
                fd.id = fdu.record_id
            WHERE
                    fd.date_start <= \'' . $now . '\'
                AND fd.date_end   >= \'' . $now . '\'
                AND fd.id = fv.artikel
                AND fd.active = \'yes\'';

        if (isset($categoryId) && is_numeric($categoryId) && ($categoryId != 0)) {
            $query .= '
            AND
                fcr.category_id = \'' . $categoryId . '\'';
        }
        if (isset($language) && Language::isASupportedLanguage($language)) {
            $query .= '
            AND
                fd.lang = \'' . $language . '\'';
        }
        $query .= '
                ' . $this->queryPermission($this->groupSupport) . '
            ORDER BY
                avg DESC';

        $result = $this->config->getDb()->query($query);

        $i = 1;
        $oldId = 0;
        while (($row = $this->config->getDb()->fetchObject($result)) && $i <= $count) {
            if ($oldId != $row->id) {
                $data['avg'] = $row->avg;
                $data['question'] = $row->thema;
                $data['date'] = $row->updated;
                $data['user'] = $row->user;

                $title = $row->thema;
                $url = sprintf(
                    '%s?%saction=faq&amp;cat=%d&amp;id=%d&amp;artlang=%s',
                    $this->config->getDefaultUrl(),
                    $sids,
                    $row->category_id,
                    $row->id,
                    $row->lang
                );
                $oLink = new Link($url, $this->config);
                $oLink->itemTitle = $row->thema;
                $oLink->tooltip = $title;
                $data['url'] = $oLink->toString();

                $topten[] = $data;
                ++$i;
            }
            $oldId = $row->id;
        }

        return $topten;
    }

    /**
     * This function generates the list with the latest published records.
     *
     * @return array
     * @throws \Exception
     */
    public function getLatest()
    {
        $date = new Date($this->config);
        $result = $this->getLatestData(PMF_NUMBER_RECORDS_LATEST, $this->config->getLanguage()->getLanguage());
        $output = [];

        if (count($result) > 0) {
            foreach ($result as $row) {
                $output['url'][] = $row['url'];
                $output['title'][] = Utils::makeShorterText($row['question'], 8);
                $output['preview'][] = $row['question'];
                $output['date'][] = $date->format($row['date']);
            }
        } else {
            $output['error'] = $this->translation['err_noArticles'];
        }

        return $output;
    }

    /**
     * This function generates an array with a specified number of most recent
     * published records.
     *
     * @param int    $count    Number of records
     * @param string $language Language
     *
     * @return array
     */
    public function getLatestData($count = PMF_NUMBER_RECORDS_LATEST, $language = null)
    {
        global $sids;

        $now = date('YmdHis');
        $query =
            '            SELECT
                fd.id AS id,
                fd.lang AS lang,
                fcr.category_id AS category_id,
                fd.thema AS question,
                fd.content AS content,
                fd.updated AS updated,
                fv.visits AS visits,
                fdg.group_id AS group_id,
                fdu.user_id AS user_id
            FROM
                ' . Database::getTablePrefix() . 'faqvisits fv,
                ' . Database::getTablePrefix() . 'faqdata fd
            LEFT JOIN
                ' . Database::getTablePrefix() . 'faqcategoryrelations fcr
            ON
                fd.id = fcr.record_id
            AND
                fd.lang = fcr.record_lang
            LEFT JOIN
                ' . Database::getTablePrefix() . 'faqdata_group AS fdg
            ON
                fd.id = fdg.record_id
            LEFT JOIN
                ' . Database::getTablePrefix() . 'faqdata_user AS fdu
            ON
                fd.id = fdu.record_id
            WHERE
                    fd.date_start <= \'' . $now . '\'
                AND fd.date_end   >= \'' . $now . '\'
                AND fd.id = fv.id
                AND fd.lang = fv.lang
                AND fd.active = \'yes\'';

        if (isset($language) && Language::isASupportedLanguage($language)) {
            $query .= '
            AND
                fd.lang = \'' . $language . '\'';
        }
        $query .= '
                ' . $this->queryPermission($this->groupSupport) . '
            GROUP BY
                fd.id,fd.lang,fcr.category_id,fv.visits,fdg.group_id,fdu.user_id
            ORDER BY
                fd.updated DESC';

        $result = $this->config->getDb()->query($query);
        $latest = [];
        $data = [];

        if ($result) {
            while (($row = $this->config->getDb()->fetchObject($result))) {
                if ($this->groupSupport) {
                    if (!in_array($row->user_id, array(-1, $this->user)) || !in_array($row->group_id, $this->groups)) {
                        continue;
                    }
                } else {
                    if (!in_array($row->user_id, array(-1, $this->user))) {
                        continue;
                    }
                }

                $data['date'] = Date::createIsoDate($row->updated, DATE_ISO8601, true);
                $data['question'] = $row->question;
                $data['answer'] = $row->content;
                $data['visits'] = (int)$row->visits;

                $title = $row->question;
                $url = sprintf(
                    '%sindex.php?%saction=faq&amp;cat=%d&amp;id=%d&amp;artlang=%s',
                    $this->config->getDefaultUrl(),
                    $sids,
                    $row->category_id,
                    $row->id,
                    $row->lang
                );
                $oLink = new Link($url, $this->config);
                $oLink->itemTitle = $row->question;
                $oLink->tooltip = $title;
                $data['url'] = $oLink->toString();

                $latest[$row->id] = $data;

                if (count($latest) === $count) {
                    break;
                }
            }
        }

        return $latest;
    }

    /**
     * Retrieve faq records according to the constraints provided.
     *
     * @param string $queryType
     * @param int    $nCatid
     * @param bool   $bDownwards
     * @param string $lang
     * @param string $date
     *
     * @return array
     */
    public function get($queryType = FAQ_QUERY_TYPE_DEFAULT, $nCatid = 0, $bDownwards = true, $lang = '', $date = '')
    {
        $faqs = [];

        $result = $this->config->getDb()->query($this->getSQLQuery($queryType, $nCatid, $bDownwards, $lang, $date));

        if ($this->config->getDb()->numRows($result) > 0) {
            $i = 0;
            while ($row = $this->config->getDb()->fetchObject($result)) {
                $faq = [];
                $faq['id'] = $row->id;
                $faq['solution_id'] = $row->solution_id;
                $faq['revision_id'] = $row->revision_id;
                $faq['lang'] = $row->lang;
                $faq['category_id'] = $row->category_id;
                $faq['active'] = $row->active;
                $faq['sticky'] = $row->sticky;
                $faq['keywords'] = $row->keywords;
                $faq['topic'] = $row->thema;
                $faq['content'] = $row->content;
                $faq['author_name'] = $row->author;
                $faq['author_email'] = $row->email;
                $faq['comment_enable'] = $row->comment;
                $faq['lastmodified'] = $row->updated;
                $faq['hits'] = $row->visits;
                $faq['hits_last'] = $row->last_visit;
                $faq['notes'] = $row->notes;
                $faqs[$i] = $faq;
                ++$i;
            }
        }

        return $faqs;
    }

    /**
     * Build the SQL query for retrieving faq records according to the constraints provided.
     *
     * @param string  $queryType
     * @param integer $categoryId
     * @param boolean $bDownwards
     * @param string  $lang
     * @param string  $date
     * @param $faqId
     *
     * @return string
     */
    private function getSQLQuery($queryType, $categoryId, $bDownwards, $lang, $date, $faqId = 0)
    {
        $now = date('YmdHis');
        $query = sprintf(
            "
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
                fd.updated AS updated,
                fd.notes AS notes,
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
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            $now,
            $now
        );
        // faqvisits data selection
        if (!empty($faqId)) {
            // Select ONLY the faq with the provided $faqid
            $query .= "fd.id = '" . $faqId . "' AND ";
        }
        $query .= 'fd.id = fv.id
            AND
                fd.lang = fv.lang';
        $needAndOp = true;
        if ((!empty($categoryId)) && is_int($categoryId) && $categoryId > 0) {
            if ($needAndOp) {
                $query .= ' AND';
            }
            $query .= ' (fcr.category_id = ' . $categoryId;
            if ($bDownwards) {
                $query .= $this->getCatidWhereSequence($categoryId, 'OR');
            }
            $query .= ')';
            $needAndOp = true;
        }
        if ((!empty($date)) && Utils::isLikeOnPMFDate($date)) {
            if ($needAndOp) {
                $query .= ' AND';
            }
            $query .= " fd.updated LIKE '" . $date . "'";
            $needAndOp = true;
        }
        if ((!empty($lang)) && Utils::isLanguage($lang)) {
            if ($needAndOp) {
                $query .= ' AND';
            }
            $query .= " fd.lang = '" . $lang . "'";
            $needAndOp = true;
        }
        switch ($queryType) {
            case FAQ_QUERY_TYPE_APPROVAL:
                if ($needAndOp) {
                    $query .= ' AND';
                }
                $query .= " fd.active = '" . FAQ_SQL_ACTIVE_NO . "'";
                break;
            case FAQ_QUERY_TYPE_EXPORT_PDF:
            case FAQ_QUERY_TYPE_EXPORT_XHTML:
            case FAQ_QUERY_TYPE_EXPORT_XML:
                if ($needAndOp) {
                    $query .= ' AND';
                }
                $query .= " fd.active = '" . FAQ_SQL_ACTIVE_YES . "'";
                break;
            default:
                if ($needAndOp) {
                    $query .= ' AND';
                }
                $query .= " fd.active = '" . FAQ_SQL_ACTIVE_YES . "'";
                break;
        }
        // Sort criteria
        switch ($queryType) {
            case FAQ_QUERY_TYPE_EXPORT_PDF:
            case FAQ_QUERY_TYPE_EXPORT_XHTML:
            case FAQ_QUERY_TYPE_EXPORT_XML:
                $query .= "\nORDER BY fcr.category_id, fd.id";
                break;
            case FAQ_QUERY_TYPE_RSS_LATEST:
                $query .= "\nORDER BY fd.updated DESC";
                break;
            default:
                // Normal ordering
                $query .= "\nORDER BY fcr.category_id, fd.id";
                break;
        }

        return $query;
    }

    /**
     * Build a logic sequence, for a WHERE statement, of those category IDs
     * children of the provided category ID, if any.
     *
     * @param integer $nCatid
     * @param $logicOp
     * @param $oCat
     *
     * @return string
     */
    private function getCatidWhereSequence($nCatid, $logicOp = 'OR', $oCat = null)
    {
        $sqlWhereFilter = '';

        if (!isset($oCat)) {
            $oCat = new Category($this->config);
        }
        $aChildren = array_values($oCat->getChildren($nCatid));

        foreach ($aChildren as $catid) {
            $sqlWhereFilter .= ' ' . $logicOp . ' fcr.category_id = ' . $catid;
            $sqlWhereFilter .= $this->getCatidWhereSequence($catid, 'OR', $oCat);
        }

        return $sqlWhereFilter;
    }

    /**
     * Adds the record permissions for users and groups.
     *
     * @param string $mode     'group' or 'user'
     * @param int    $recordId ID of the current record
     * @param array  $ids      Array of group or user IDs
     *
     * @return bool
     */
    public function addPermission($mode, $recordId, $ids)
    {
        if ('user' !== $mode && 'group' !== $mode) {
            return false;
        }

        foreach ($ids as $id) {
            $query = sprintf(
                '
            INSERT INTO
                %sfaqdata_%s
            (record_id, %s_id)
                VALUES
            (%d, %d)',
                Database::getTablePrefix(),
                $mode,
                $mode,
                $recordId,
                $id
            );

            $this->config->getDb()->query($query);
        }

        return true;
    }

    /**
     * Deletes the record permissions for users and groups.
     *
     * @param string $mode      'group' or 'user'
     * @param int    $record_id ID of the current record
     *
     * @return bool
     */
    public function deletePermission($mode, $record_id)
    {
        if (!($mode == 'user' || $mode == 'group')) {
            return false;
        }
        if (!is_int($record_id)) {
            return false;
        }

        $query = sprintf(
            '
            DELETE FROM
                %sfaqdata_%s
            WHERE
                record_id = %d',
            Database::getTablePrefix(),
            $mode,
            $record_id
        );
        $this->config->getDb()->query($query);

        return true;
    }

    /**
     * Returns the record permissions for users and groups.
     *
     * @param string $mode 'group' or 'user'
     * @param int $recordId
     *
     * @return array
     */
    public function getPermission(string $mode, int $recordId): array
    {
        $permissions = [];

        if (!($mode == 'user' || $mode == 'group')) {
            return $permissions;
        }

        if (0 === $recordId) {
            return [-1];
        }

        $query = sprintf(
            '
            SELECT
                %s_id AS permission
            FROM
                %sfaqdata_%s
            WHERE
                record_id = %d',
            $mode,
            Database::getTablePrefix(),
            $mode,
            (int)$recordId
        );

        $result = $this->config->getDb()->query($query);

        if ($this->config->getDb()->numRows($result) > 0) {
            while (($row = $this->config->getDb()->fetchObject($result))) {
                $permissions[] = (int)$row->permission;
            }
        }

        return $permissions;
    }

    /**
     * Returns all records of one category.
     *
     * @param int $categoryId
     *
     * @return string
     */
    public function getRecordsWithoutPagingByCategoryId(int $categoryId): string
    {
        global $sids;

        $output = '';
        $now = date('YmdHis');
        $query = sprintf(
            "
            SELECT
                fd.id AS id,
                fd.lang AS lang,
                fd.thema AS thema,
                fcr.category_id AS category_id,
                fv.visits AS visits
            FROM
                %sfaqdata fd
            LEFT JOIN
                %sfaqcategoryrelations fcr
            ON
                fd.id = fcr.record_id
            AND
                fd.lang = fcr.record_lang
            LEFT JOIN
                %sfaqvisits fv
            ON
                fd.id = fv.id
            AND
                fv.lang = fd.lang
            LEFT JOIN
                %sfaqdata_group fdg
            ON
                fd.id = fdg.record_id
            LEFT JOIN
                %sfaqdata_user fdu
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
                %s
            GROUP BY
                fd.id,fd.lang,fcr.category_id,fv.visits
            ORDER BY
                %s %s",
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            $now,
            $now,
            $categoryId,
            $this->config->getLanguage()->getLanguage(),
            $this->queryPermission($this->groupSupport),
            $this->config->get('records.orderby'),
            $this->config->get('records.sortby')
        );

        $result = $this->config->getDb()->query($query);

        if ($result) {
            $output = '<ul>';
            while (($row = $this->config->getDb()->fetchObject($result))) {
                $title = $row->thema;
                $url = sprintf(
                    '%s?%saction=faq&amp;cat=%d&amp;id=%d&amp;artlang=%s',
                    $this->config->getDefaultUrl(),
                    $sids,
                    $row->category_id,
                    $row->id,
                    $row->lang
                );

                $oLink = new Link($url, $this->config);
                $oLink->itemTitle = $row->thema;
                $oLink->text = $title;
                $oLink->tooltip = $title;
                $listItem = '<li>' . $oLink->toHtmlAnchor() . '</li>';

                $output .= $listItem;
            }
            $output .= '</ul>';
        }

        return $output;
    }

    /**
     * Prints the open questions as a HTML table.
     *
     * @todo   needs to be moved to a QuestionHelper class
     * @return string
     * @throws \Exception
     */
    public function renderOpenQuestions(): string
    {
        global $sids, $category;

        $date = new Date($this->config);
        $mail = new Mail($this->config);

        $query = sprintf(
            "
            SELECT
                COUNT(id) AS num
            FROM
                %sfaqquestions
            WHERE
                lang = '%s'
            AND
                is_visible != 'Y'",
            Database::getTablePrefix(),
            $this->config->getLanguage()->getLanguage()
        );

        $result = $this->config->getDb()->query($query);
        $row = $this->config->getDb()->fetchObject($result);
        $numOfInvisibles = $row->num;

        if ($numOfInvisibles > 0) {
            $extraout = sprintf(
                '<tr><td colspan="3"><small>%s %s</small></td></tr>',
                $this->translation['msgQuestionsWaiting'],
                $numOfInvisibles
            );
        } else {
            $extraout = '';
        }

        $query = sprintf(
            "
            SELECT
                *
            FROM
                %sfaqquestions
            WHERE
                lang = '%s'
            AND
                is_visible = 'Y'
            ORDER BY
                created ASC",
            Database::getTablePrefix(),
            $this->config->getLanguage()->getLanguage()
        );

        $result = $this->config->getDb()->query($query);
        $output = '';

        if ($result && $this->config->getDb()->numRows($result) > 0) {
            while ($row = $this->config->getDb()->fetchObject($result)) {
                $output .= '<tr class="openquestions">';
                $output .= sprintf(
                    '<td><small>%s</small><br><a href="mailto:%s">%s</a></td>',
                    $date->format(Date::createIsoDate($row->created)),
                    $mail->safeEmail($row->email),
                    $row->username
                );
                $output .= sprintf(
                    '<td><strong>%s:</strong><br>%s</td>',
                    isset($category->categoryName[$row->category_id]['name']) ?
                        $category->categoryName[$row->category_id]['name'] :
                        '',
                    strip_tags($row->question)
                );
                if ($this->config->get('records.enableCloseQuestion') && $row->answer_id) {
                    $output .= sprintf(
                        '<td><a id="PMF_openQuestionAnswered" href="?%saction=faq&amp;cat=%d&amp;id=%d">%s</a></td>',
                        $sids,
                        $row->category_id,
                        $row->answer_id,
                        $this->translation['msg2answerFAQ']
                    );
                } else {
                    $output .= sprintf(
                        '<td class="text-right">' .
                        '<a class="btn btn-primary" href="?%saction=add&amp;question=%d&amp;cat=%d">%s</a></td>',
                        $sids,
                        $row->id,
                        $row->category_id,
                        $this->translation['msg2answer']
                    );
                }
                $output .= '</tr>';
            }
        } else {
            $output = sprintf(
                '<tr><td colspan="3">%s</td></tr>',
                $this->translation['msgNoQuestionsAvailable']
            );
        }

        return $output . $extraout;
    }

    /**
     * Set or unset a faq item flag.
     *
     * @param int    $id   Record id
     * @param string $lang language code which is valid with Language::isASupportedLanguage
     * @param bool   $flag record is set to sticky or not
     * @param string $type type of the flag to set, use the column name
     *
     * @return bool
     */
    public function updateRecordFlag($id, $lang, $flag, $type)
    {
        $retval = false;

        switch ($type) {
            case 'sticky':
                $flag = ($flag === 'checked' ? 1 : 0);
                break;

            case 'active':
                $flag = ($flag === 'checked' ? "'yes'" : "'no'");
                break;

            default:
                // This is because we would run into unknown db column
                $flag = null;
                break;
        }

        if (null !== $flag) {
            $update = sprintf(
                "
                UPDATE 
                    %sfaqdata 
                SET 
                    %s = %s 
                WHERE 
                    id = %d 
                AND 
                    lang = '%s'",
                Database::getTablePrefix(),
                $type,
                $flag,
                $id,
                $lang
            );

            $retval = (bool)$this->config->getDb()->query($update);
        }

        return $retval;
    }

    /**
     * Prepares and returns the sticky records for the frontend.
     *
     * @return array
     */
    public function getStickyRecords()
    {
        $result = $this->getStickyRecordsData();
        $output = [];

        if (count($result) > 0) {
            foreach ($result as $row) {
                $output['title'][] = Utils::makeShorterText($row['question'], 8);
                $output['preview'][] = $row['question'];
                $output['url'][] = $row['url'];
            }
        } else {
            $output['error'] = sprintf('<li>%s</li>', $this->translation['err_noTopTen']);
        }

        return $output;
    }

    /**
     * Returns the sticky records with URL and Title.
     *
     * @return array
     */
    public function getStickyRecordsData()
    {
        global $sids;

        $now = date('YmdHis');
        $query = sprintf(
            "
            SELECT
                fd.id AS id,
                fd.lang AS lang,
                fd.thema AS thema,
                fcr.category_id AS category_id,
                fv.visits AS visits
            FROM
                %sfaqvisits fv,
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
            AND
                fd.id = fv.id
            AND 
                fd.lang = fv.lang
            %s
            GROUP BY
                fd.id, fd.lang, fd.thema, fcr.category_id, fv.visits
            ORDER BY
                fv.visits DESC",
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            $this->config->getLanguage()->getLanguage(),
            $now,
            $now,
            $this->queryPermission($this->groupSupport)
        );

        $result = $this->config->getDb()->query($query);
        $sticky = [];
        $data = [];

        $oldId = 0;
        while (($row = $this->config->getDb()->fetchObject($result))) {
            if ($oldId != $row->id) {
                $data['question'] = $row->thema;

                $title = $row->thema;
                $url = sprintf(
                    '%s?%saction=faq&amp;cat=%d&amp;id=%d&amp;artlang=%s',
                    $this->config->getDefaultUrl(),
                    $sids,
                    $row->category_id,
                    $row->id,
                    $row->lang
                );
                $oLink = new Link($url, $this->config);
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
     * Returns the inactive records with admin URL to edit the FAQ and title.
     *
     * @return array
     */
    public function getInactiveFaqsData()
    {
        $query = sprintf(
            "
            SELECT
                fd.id AS id,
                fd.lang AS lang,
                fd.thema AS thema
            FROM
                %sfaqdata fd
            WHERE
                fd.lang = '%s'
            AND 
                fd.active = 'no'
            GROUP BY
                fd.id, fd.lang, fd.thema
            ORDER BY
                fd.id DESC",
            Database::getTablePrefix(),
            $this->config->getLanguage()->getLanguage()
        );

        $result = $this->config->getDb()->query($query);
        $inactive = [];
        $data = [];

        $oldId = 0;
        while (($row = $this->config->getDb()->fetchObject($result))) {
            if ($oldId != $row->id) {
                $data['question'] = $row->thema;
                $data['url'] = sprintf(
                    '%s?action=editentry&id=%d&lang=%s',
                    $this->config->getDefaultUrl(),
                    $row->id,
                    $row->lang
                );
                $inactive[] = $data;
            }
            $oldId = $row->id;
        }

        return $inactive;
    }
}
