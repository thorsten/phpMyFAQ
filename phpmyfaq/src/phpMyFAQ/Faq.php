<?php

/**
 * The main FAQ class. Yes, it's very huge.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo Scaramuccia <matteo@scaramuccia.com>
 * @author    Georgi Korchev <korchev@yahoo.com>
 * @author    Adrianna Musiol <musiol@imageaccess.de>
 * @author    Peter Caesar <p.caesar@osmaco.de>
 * @copyright 2005-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2005-12-20
 */

namespace phpMyFAQ;

use DateTimeInterface;
use Exception;
use phpMyFAQ\Attachment\AttachmentFactory;
use phpMyFAQ\Entity\FaqEntity;
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
     */
    public array $faqRecord = [];

    /**
     * All current FAQ records in an array.
     */
    public array $faqRecords = [];

    /**
     * Plural form support.
     */
    private readonly Plurals $plurals;

    /**
     * Users.
     */
    private int $user = -1;

    /**
     * Groups.
     *
     * @var int[]
     */
    private array $groups = [-1];

    /**
     * Flag for Group support.
     */
    private bool $groupSupport = false;

    /**
     * Constructor.
     */
    public function __construct(private readonly Configuration $config)
    {
        $this->plurals = new Plurals();

        if ($this->config->get('security.permLevel') !== 'basic') {
            $this->groupSupport = true;
        }
    }

    public function setUser(int $userId = -1): Faq
    {
        $this->user = $userId;
        return $this;
    }

    /**
     * @param int[] $groups
     */
    public function setGroups(array $groups): Faq
    {
        $this->groups = $groups;
        return $this;
    }

    /**
     * This function returns all not expired records from one category.
     *
     * @param int    $categoryId Entity ID
     * @param string $orderBy    Order by
     * @param string $sortBy     Sort by
     *
     * @throws Exception
     */
    public function getAllFaqsByCategoryId(
        int $categoryId,
        string $orderBy = 'id',
        string $sortBy = 'ASC',
        bool $preview = true
    ): array {
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

                if ($preview) {
                    $faqData[] = [
                        'record_id' => $row->id,
                        'record_lang' => $row->lang,
                        'category_id' => $row->category_id,
                        'record_title' => $row->thema,
                        'record_preview' => $faqHelper->renderAnswerPreview($row->record_content, 25),
                        'record_link' => $oLink->toString(),
                        'record_updated' => $row->updated,
                        'visits' => $visits,
                        'record_created' => $row->created,
                    ];
                } else {
                    $faqData[] = [
                        'faq_id' => $row->id,
                        'faq_lang' => $row->lang,
                        'category_id' => $row->category_id,
                        'question' => $row->thema,
                        'answer' => $row->record_content,
                        'link' => $oLink->toString(),
                        'updated' => $row->updated,
                        'visits' => $visits,
                        'created' => $row->created,
                    ];
                }
            }
        } else {
            return $faqData;
        }

        return $faqData;
    }

    /**
     * Returns a part of a query to check permissions.
     *
     *
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
                    'AND ( fdu.user_id = %d OR fdg.group_id IN (%s) )',
                    $this->user,
                    implode(', ', $this->groups)
                );
            }
        }

        if (-1 !== $this->user) {
            return sprintf(
                'AND ( fdu.user_id = %d OR fdu.user_id = -1 )',
                $this->user
            );
        } else {
            return 'AND fdu.user_id = -1';
        }
    }

    /**
     * This function returns all not expired records from one category.
     *
     * @param int    $categoryId Entity ID
     * @param string $orderBy    Order by
     * @param string $sortBy     Sort by
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
                fd.thema AS question,
                fd.content as answer,
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
                    '<p>%s <strong>%d</strong> %s <strong>%d</strong> %s</p>',
                    Translation::get('msgPage'),
                    $page,
                    Translation::get('msgVoteFrom'),
                    $pages,
                    Translation::get('msgPages')
                );
            }
            $output .= '<ul class="list-group list-group-flush mb-4">';

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
                $oLink->itemTitle = $oLink->text = $oLink->tooltip = $title;
                $oLink->class = 'text-decoration-none';

                // If random FAQs are activated, we don't need sticky FAQs
                if (true === $this->config->get('records.randomSort')) {
                    $row->sticky = 0;
                }

                $renderedItems[$row->id] = sprintf(
                    '<li class="list-group-item d-flex justify-content-between align-items-start %s">',
                    ($row->sticky) ? 'list-group-item-primary rounded mb-3' : ''
                );
                $renderedItems[$row->id] .= sprintf(
                    '<div class="ms-2 me-auto"><div class="fw-bold">%s</div><div class="small">%s</div></div>',
                    $oLink->toHtmlAnchor(),
                    Utils::chopString(strip_tags((string) $row->answer), 20)
                );
                $renderedItems[$row->id] .= sprintf(
                    '<span id="viewsPerRecord" class="badge bg-primary rounded-pill">%s</span></li>',
                    $this->plurals->getMsg('plmsgViews', $visits)
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
                '%sindex.php?%saction=show&amp;cat=%d&amp;seite=%d',
                $this->config->getDefaultUrl(),
                (empty($sids) ? '' : $sids),
                $categoryId,
                $page
            );

            $options = [
                'baseUrl' => $baseUrl,
                'total' => $num,
                'perPage' => $this->config->get('records.numberOfRecordsPerPage'),
                'useRewrite' => $useRewrite,
                'rewriteUrl' => $rewriteUrl,
                'pageParamName' => 'seite'
            ];

            $pagination = new Pagination($options);
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
     */
    public function renderRecordsByFaqIds(array $recordIds, string $orderBy = 'fd.id', string $sortBy = 'ASC'): string
    {
        global $sids;

        $records = implode(', ', $recordIds);
        $page = Filter::filterInput(INPUT_GET, 'seite', FILTER_VALIDATE_INT, 1);
        $taggingId = Filter::filterInput(INPUT_GET, 'tagging_id', FILTER_VALIDATE_INT);
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
                    Translation::get('msgPage') . $page,
                    Translation::get('msgVoteFrom'),
                    $pages . Translation::get('msgPages')
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
                    '%sindex.php?%saction=faq&amp;cat=%d&amp;id=%d&amp;artlang=%s',
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
                $oLink->text = Translation::get('msgPrevious');
                $oLink->tooltip = Translation::get('msgPrevious');
                $output .= '[ ' . $oLink->toHtmlAnchor() . ' ]';
            }
            $output .= ' ';
            if ($next <= $pages) {
                $url = $sids . '&amp;action=search&amp;tagging_id=' . $taggingId . '&amp;seite=' . $next;
                $oLink = new Link($this->config->getDefaultUrl() . '?' . $url, $this->config);
                $oLink->itemTitle = 'tag';
                $oLink->text = Translation::get('msgNext');
                $oLink->tooltip = Translation::get('msgNext');
                $output .= '[ ' . $oLink->toHtmlAnchor() . ' ]';
            }
            $output .= '</strong></p>';
        }

        return $output;
    }

    /**
     * Returns an array with all data from a FAQ record.
     *
     * @param int      $faqId FAQ ID
     * @param int|null $faqRevisionId Revision ID
     * @param bool     $isAdmin Must be true if it is called by an admin/author context
     */
    public function getRecord(int $faqId, int $faqRevisionId = null, bool $isAdmin = false)
    {
        $currentLanguage = $this->config->getLanguage()->getLanguage();
        $defaultLanguage = $this->config->getDefaultLanguage();

        $result = $this->getRecordResult($faqId, $currentLanguage, $faqRevisionId, $isAdmin);

        if (0 === $this->config->getDb()->numRows($result)) {
            $result = $this->getRecordResult($faqId, $defaultLanguage, $faqRevisionId, $isAdmin);
        }

        if ($row = $this->config->getDb()->fetchObject($result)) {
            $question = nl2br((string) $row->thema);
            $answer = $row->content;
            $active = ('yes' === $row->active);
            $expired = (date('YmdHis') > $row->date_end);

            if (!$isAdmin) {
                if (!$active) {
                    $answer = Translation::get('err_inactiveArticle');
                }
                if ($expired) {
                    $answer = Translation::get('err_expiredArticle');
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
                'content' => Translation::get('msgAccessDenied'),
                'author' => '',
                'email' => '',
                'comment' => '',
                'date' => Date::createIsoDate(date('YmdHis')),
                'dateStart' => '',
                'dateEnd' => '',
                'notes' => '',
                'created' => date('c'),
            ];
        }
    }

    /**
     * Executes a query to retrieve a single FAQ.
     *
     * @param int|null $faqRevisionId
     */
    public function getRecordResult(
        int $faqId,
        string $faqLanguage,
        int $faqRevisionId = null,
        bool $isAdmin = false
    ): mixed {
        $query = sprintf(
            "SELECT
                 id, lang, solution_id, revision_id, active, sticky, keywords,
                 thema, content, author, email, comment, updated, date_start, 
                 date_end, created, notes
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
     * @throws Exception
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
     * Creates a new FAQ.
     */
    public function create(FaqEntity $faq): int
    {
        if (is_null($faq->getId())) {
            $faq->setId($this->config->getDb()->nextId(Database::getTablePrefix() . 'faqdata', 'id'));
        }

        $query = sprintf(
            "INSERT INTO %sfaqdata 
            (id, lang, solution_id, revision_id, active, sticky, keywords, thema, content, author, email, comment, 
            updated, date_start, date_end, created, notes)
            VALUES
            (%d, '%s', %d, %d, '%s', %d, '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')",
            Database::getTablePrefix(),
            $faq->getId(),
            $this->config->getDb()->escape($faq->getLanguage()),
            $this->getNextSolutionId(),
            0,
            $faq->isActive() ? 'yes' : 'no',
            $faq->isSticky() ? 1 : 0,
            $this->config->getDb()->escape($faq->getKeywords()),
            $this->config->getDb()->escape($faq->getQuestion()),
            $this->config->getDb()->escape($faq->getAnswer()),
            $this->config->getDb()->escape($faq->getAuthor()),
            $faq->getEmail(),
            $faq->isComment() ? 'y' : 'n',
            $faq->getUpdatedDate()->format('YmdHis'),
            '00000000000000',
            '99991231235959',
            date('Y-m-d H:i:s'),
            $faq->getNotes()
        );

        $this->config->getDb()->query($query);

        return $faq->getId();
    }

    /**
     * Gets the latest solution id for a FAQ record.
     */
    public function getNextSolutionId(): int
    {
        $latestId = 0;

        $query = sprintf('SELECT MAX(solution_id) AS solution_id FROM %sfaqdata', Database::getTablePrefix());

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

    public function update(FaqEntity $faq): bool
    {
        $query = sprintf(
            "UPDATE
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
                date_start = '%s',
                date_end = '%s',
                notes = '%s'
            WHERE
                id = %d
            AND
                lang = '%s'",
            Database::getTablePrefix(),
            $faq->getRevisionId(),
            $faq->isActive() ? 'yes' : 'no',
            $faq->isSticky(),
            $this->config->getDb()->escape($faq->getKeywords()),
            $this->config->getDb()->escape($faq->getQuestion()),
            $this->config->getDb()->escape($faq->getAnswer()),
            $this->config->getDb()->escape($faq->getAuthor()),
            $faq->getEmail(),
            $faq->isComment(),
            $faq->getUpdatedDate()->format('YmdHis'),
            $faq->getValidFrom()->format('YmdHis'),
            $faq->getValidTo()->format('YmdHis'),
            $faq->getNotes(),
            $faq->getId(),
            $faq->getLanguage()
        );

        return (bool) $this->config->getDb()->query($query);
    }

    /**
     * Deletes a record and all the dependencies.
     *
     * @param int    $recordId   Record id
     * @param string $recordLang Record language
     * @throws Attachment\AttachmentException
     * @throws Attachment\Filesystem\File\FileException
     */
    public function deleteRecord(int $recordId, string $recordLang): bool
    {
        $solutionId = $this->getSolutionIdFromId($recordId, $recordLang);

        $queries = [sprintf(
            "DELETE FROM %sfaqchanges WHERE beitrag = %d AND lang = '%s'",
            Database::getTablePrefix(),
            $recordId,
            $this->config->getDb()->escape($recordLang)
        ), sprintf(
            "DELETE FROM %sfaqcategoryrelations WHERE record_id = %d AND record_lang = '%s'",
            Database::getTablePrefix(),
            $recordId,
            $this->config->getDb()->escape($recordLang)
        ), sprintf(
            "DELETE FROM %sfaqdata WHERE id = %d AND lang = '%s'",
            Database::getTablePrefix(),
            $recordId,
            $this->config->getDb()->escape($recordLang)
        ), sprintf(
            "DELETE FROM %sfaqdata_revisions WHERE id = %d AND lang = '%s'",
            Database::getTablePrefix(),
            $recordId,
            $this->config->getDb()->escape($recordLang)
        ), sprintf(
            "DELETE FROM %sfaqvisits WHERE id = %d AND lang = '%s'",
            Database::getTablePrefix(),
            $recordId,
            $this->config->getDb()->escape($recordLang)
        ), sprintf(
            'DELETE FROM %sfaqdata_user WHERE record_id = %d',
            Database::getTablePrefix(),
            $recordId
        ), sprintf(
            'DELETE FROM %sfaqdata_group WHERE record_id = %d',
            Database::getTablePrefix(),
            $recordId
        ), sprintf(
            'DELETE FROM %sfaqdata_tags WHERE record_id = %d',
            Database::getTablePrefix(),
            $recordId
        ), sprintf(
            'DELETE FROM %sfaqdata_tags WHERE %sfaqdata_tags.record_id NOT IN (SELECT %sfaqdata.id FROM %sfaqdata)',
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            Database::getTablePrefix()
        ), sprintf(
            'DELETE FROM %sfaqcomments WHERE id = %d',
            Database::getTablePrefix(),
            $recordId
        ), sprintf(
            'DELETE FROM %sfaqvoting WHERE artikel = %d',
            Database::getTablePrefix(),
            $recordId
        )];

        foreach ($queries as $query) {
            $this->config->getDb()->query($query);
        }

        // Delete possible attachments
        $attachments = AttachmentFactory::fetchByRecordId($this->config, $recordId);
        foreach ($attachments as $attachment) {
            $currentAttachment = AttachmentFactory::create($attachment->getId());
            $currentAttachment->delete();
        }

        // Delete possible Elasticsearch documents
        if ($this->config->get('search.enableElasticsearch')) {
            $esInstance = new Elasticsearch($this->config);
            $esInstance->delete($solutionId);
        }

        return true;
    }

    /**
     * Returns the solution ID from a given ID and language
     */
    public function getSolutionIdFromId(int $faqId, string $faqLang): int
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
            $faqId,
            $this->config->getDb()->escape($faqLang)
        );

        $result = $this->config->getDb()->query($query);

        if ($row = $this->config->getDb()->fetchObject($result)) {
            return $row->solution_id;
        }

        return $this->getNextSolutionId();
    }

    /**
     * Checks if a record is already translated.
     *
     * @param int    $recordId   Record id
     * @param string $recordLang Record language
     */
    public function hasTranslation(int $recordId, string $recordLang): bool
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
            $this->config->getDb()->escape($recordLang)
        );

        $result = $this->config->getDb()->query($query);

        if ($this->config->getDb()->numRows($result)) {
            return true;
        }

        return false;
    }

    public function isActive(int $recordId, string $recordLang, string $commentType = 'faq'): bool
    {
        if ('news' === $commentType) {
            $table = 'faqnews';
        } else {
            $table = 'faqdata';
        }

        $query = sprintf(
            "
            SELECT
                active
            FROM
                %s%s
            WHERE
                id = %d
            AND
                lang = '%s'",
            Database::getTablePrefix(),
            $table,
            $recordId,
            $this->config->getDb()->escape($recordLang)
        );

        $result = $this->config->getDb()->query($query);

        if ($row = $this->config->getDb()->fetchObject($result)) {
            return !(($row->active === 'y' || $row->active === 'yes'));
        } else {
            return true;
        }
    }

    /**
     * Checks, if comments are disabled for the FAQ record.
     *
     * @param int    $recordId    Id of FAQ or news entry
     * @param string $recordLang  Language
     * @param string $commentType Type of comment: faq or news
     * @return bool true, if comments are disabled
     */
    public function commentDisabled(int $recordId, string $recordLang, string $commentType = 'faq'): bool
    {
        if ('news' === $commentType) {
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
            $this->config->getDb()->escape($recordLang)
        );

        $result = $this->config->getDb()->query($query);

        if ($row = $this->config->getDb()->fetchObject($result)) {
            return !(($row->comment === 'y'));
        } else {
            return true;
        }
    }

    /**
     * Returns an array with all data from a FAQ record.
     *
     * @param int $solutionId Solution ID
     */
    public function getRecordBySolutionId(int $solutionId): void
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
            $question = nl2br((string) $row->thema);
            $content = $row->content;
            $active = ('yes' == $row->active);
            $expired = (date('YmdHis') > $row->date_end);

            if (!$active) {
                $content = Translation::get('err_inactiveArticle');
            }
            if ($expired) {
                $content = Translation::get('err_expiredArticle');
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
                'content' => $content,
                'author' => $row->author,
                'email' => $row->email,
                'comment' => $row->comment,
                'date' => Date::createIsoDate($row->updated),
                'dateStart' => $row->date_start,
                'dateEnd' => $row->date_end,
                'notes' => $row->notes
            ];
        }
    }

    /**
     * Gets the record ID from a given solution ID.
     *
     * @param int $solutionId Solution ID
     */
    public function getIdFromSolutionId(int $solutionId): array
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
     * @param int        $sortType  Sorting type
     * @param array|null $condition Condition
     * @param ?string     $sortOrder Sorting order
     */
    public function getAllRecords(
        int $sortType = FAQ_SORTING_TYPE_CATID_FAQID,
        array $condition = null,
        ?string $sortOrder = 'ASC'
    ): void {
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

        $orderBy = match ($sortType) {
            FAQ_SORTING_TYPE_CATID_FAQID => sprintf('ORDER BY fcr.category_id, fd.id %s', $sortOrder),
            FAQ_SORTING_TYPE_FAQID => sprintf('ORDER BY fd.id %s', $sortOrder),
            FAQ_SORTING_TYPE_FAQTITLE_FAQID => sprintf('ORDER BY fcr.category_id, fd.thema %s', $sortOrder),
            FAQ_SORTING_TYPE_DATE_FAQID => sprintf('ORDER BY fcr.category_id, fd.updated %s', $sortOrder),
            default => '',
        };

        // prevents multiple display of FAQ in case it is tagged under multiple groups.
        $groupBy = ' group by fd.id, fcr.category_id,fd.solution_id,fd.revision_id,fd.active,fd.sticky,fd.keywords,' .
            'fd.thema,fd.content,fd.author,fd.email,fd.comment,fd.updated,' .
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
                $content = Translation::get('err_inactiveArticle');
            }
            if ($expired) {
                $content = Translation::get('err_expiredArticle');
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
     */
    public function getRecordTitle(int $id): string
    {
        if (isset($this->faqRecord['id']) && ($this->faqRecord['id'] == $id)) {
            return $this->faqRecord['title'];
        }

        $question = '';

        $query = sprintf(
            "SELECT thema AS question FROM %sfaqdata WHERE id = %d AND lang = '%s'",
            Database::getTablePrefix(),
            $id,
            $this->config->getLanguage()->getLanguage()
        );
        $result = $this->config->getDb()->query($query);

        if ($this->config->getDb()->numRows($result) > 0) {
            while ($row = $this->config->getDb()->fetchObject($result)) {
                $question = Strings::htmlentities($row->question);
            }
        } else {
            $question = Translation::get('no_cats');
        }

        return $question;
    }

    /**
     * Returns the keywords of a FAQ record from the ID and language.
     *
     * @param int $id record id
     */
    public function getRecordKeywords(int $id): string
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
     */
    public function getRecordPreview(int $recordId, int $wordCount = 12): string
    {
        if (isset($this->faqRecord['id']) && ((int)$this->faqRecord['id'] === $recordId)) {
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
            $answerPreview = strip_tags((string) $row->answer);
        } else {
            $answerPreview = $this->config->get('main.metaDescription');
        }

        return Utils::makeShorterText($answerPreview, $wordCount);
    }

    /**
     * Returns the number of activated and not expired records, optionally
     * not limited to the current language.
     *
     * @param string|null $language Language
     */
    public function getNumberOfRecords(string $language = null): int
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
            null === $language ? '' : "AND lang = '" . $this->config->getDb()->escape($language) . "'",
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
     * @param string $type Type definition visits/voted
     */
    public function getTopTen(string $type = 'visits'): array
    {
        if ('visits' === $type) {
            $result = $this->getTopTenData(PMF_NUMBER_RECORDS_TOPTEN, 0, $this->config->getLanguage()->getLanguage());
        } else {
            $result = $this->getTopVotedData(PMF_NUMBER_RECORDS_TOPTEN, $this->config->getLanguage()->getLanguage());
        }
        $output = [];

        if (count($result) > 0) {
            foreach ($result as $row) {
                if ('visits' == $type) {
                    $output['title'][] = Strings::htmlentities(Utils::makeShorterText($row['question'], 8));
                    $output['preview'][] = Strings::htmlentities($row['question']);
                    $output['url'][] = Strings::htmlentities($row['url']);
                    $output['visits'][] = $this->plurals->GetMsg('plmsgViews', $row['visits']);
                } else {
                    $output['title'][] = Strings::htmlentities(Utils::makeShorterText($row['question'], 8));
                    $output['preview'][] = Strings::htmlentities($row['question']);
                    $output['url'][] = Strings::htmlentities($row['url']);
                    $output['voted'][] = sprintf(
                        '%s %s 5 - %s',
                        round($row['avg'], 2),
                        Translation::get('msgVoteFrom'),
                        $this->plurals->GetMsg('plmsgVotes', $row['user'])
                    );
                }
            }
        } else {
            $output['error'] = Translation::get('err_noTopTen');
        }

        return $output;
    }

    /**
     * This function generates the Top Ten data with the most viewed records.
     *
     * @param int  $count Number of records
     * @param int  $categoryId Entity ID
     * @param string|null $language Language
     */
    public function getTopTenData(
        int $count = PMF_NUMBER_RECORDS_TOPTEN,
        int $categoryId = 0,
        string $language = null
    ): array {
        global $sids;

        $now = date('YmdHis');
        $query =
            'SELECT
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
                fd.lang = \'' . $this->config->getDb()->escape($language) . '\'';
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
                    if (!in_array($row->user_id, [-1, $this->user]) || !in_array($row->group_id, $this->groups)) {
                        continue;
                    }
                } else {
                    if (!in_array($row->user_id, [-1, $this->user])) {
                        continue;
                    }
                }

                $data['visits'] = (int)$row->visits;
                $data['question'] = Filter::filterVar($row->question, FILTER_SANITIZE_SPECIAL_CHARS);
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
     * @param string|null $language Language
     */
    public function getTopVotedData(int $count = PMF_NUMBER_RECORDS_TOPTEN, string $language = null): array
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
                fd.lang = \'' . $this->config->getDb()->escape($language) . '\'';
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
                    '%sindex.php?%saction=faq&amp;cat=%d&amp;id=%d&amp;artlang=%s',
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
     * @throws Exception
     */
    public function getLatest(): array
    {
        $date = new Date($this->config);
        $result = $this->getLatestData(PMF_NUMBER_RECORDS_LATEST, $this->config->getLanguage()->getLanguage());
        $output = [];

        if (count($result) > 0) {
            foreach ($result as $row) {
                $output['url'][] = Strings::htmlentities($row['url']);
                $output['title'][] = Strings::htmlentities(Utils::makeShorterText($row['question'], 8));
                $output['preview'][] = Strings::htmlentities($row['question']);
                $output['date'][] = $date->format($row['date']);
            }
        } else {
            $output['error'] = Translation::get('err_noArticles');
        }

        return $output;
    }

    /**
     * This function generates an array with a specified number of most recent
     * published records.
     *
     * @param int    $count    Number of records
     * @param string $language Language
     */
    public function getLatestData(int $count = PMF_NUMBER_RECORDS_LATEST, $language = null): array
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
                fd.lang = \'' . $this->config->getDb()->escape($language) . '\'';
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
                    if (!in_array($row->user_id, [-1, $this->user]) || !in_array($row->group_id, $this->groups)) {
                        continue;
                    }
                } else {
                    if (!in_array($row->user_id, [-1, $this->user])) {
                        continue;
                    }
                }

                $data['date'] = Date::createIsoDate($row->updated, DATE_ISO8601, true);
                $data['question'] = Filter::filterVar($row->question, FILTER_SANITIZE_SPECIAL_CHARS);
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
                $oLink->itemTitle = $title;
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
     */
    public function get(
        string $queryType = FAQ_QUERY_TYPE_DEFAULT,
        int $nCatid = 0,
        bool $bDownwards = true,
        string $lang = '',
        string $date = ''
    ): array {
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
     */
    private function getSQLQuery(
        string $queryType,
        int $categoryId,
        bool $bDownwards,
        string $lang,
        string $date,
        int $faqId = 0
    ): string {
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
        if ((!empty($categoryId)) && $categoryId > 0) {
            $query .= ' AND';
            $query .= ' (fcr.category_id = ' . $categoryId;
            if ($bDownwards) {
                $query .= $this->getCatidWhereSequence($categoryId, 'OR');
            }
            $query .= ')';
            $needAndOp = true;
        }
        if ((!empty($date)) && Utils::isLikeOnPMFDate($date)) {
            $query .= ' AND';
            $query .= " fd.updated LIKE '" . $date . "'";
            $needAndOp = true;
        }
        if ((!empty($lang)) && Utils::isLanguage($lang)) {
            $query .= ' AND';
            $query .= " fd.lang = '" . $this->config->getDb()->escape($lang) . "'";
            $needAndOp = true;
        }
        switch ($queryType) {
            case FAQ_QUERY_TYPE_APPROVAL:
                $query .= ' AND';
                $query .= " fd.active = '" . FAQ_SQL_ACTIVE_NO . "'";
                break;
            case FAQ_QUERY_TYPE_EXPORT_PDF:
            case FAQ_QUERY_TYPE_EXPORT_XHTML:
            case FAQ_QUERY_TYPE_EXPORT_XML:
            default:
                $query .= ' AND';
                $query .= " fd.active = '" . FAQ_SQL_ACTIVE_YES . "'";
                break;
        }
        match ($queryType) {
            FAQ_QUERY_TYPE_EXPORT_PDF,
            FAQ_QUERY_TYPE_EXPORT_XHTML,
            FAQ_QUERY_TYPE_EXPORT_XML => $query .= "\nORDER BY fcr.category_id, fd.id",
            default => $query .= "\nORDER BY fcr.category_id, fd.id",
        };

        return $query;
    }

    /**
     * Build a logic sequence, for a WHERE statement, of those category IDs
     * children of the provided category ID, if any.
     *
     * @param Category|null $oCat
     */
    private function getCatidWhereSequence(int $nCatid, string $logicOp = 'OR', Category $oCat = null): string
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
     * Returns all records of one category.
     *
     *
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
                fd.id,fd.lang,fd.thema,fcr.category_id,fv.visits
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
                    '%sindex.php?%saction=faq&amp;cat=%d&amp;id=%d&amp;artlang=%s',
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
     * @throws Exception
     */
    public function renderOpenQuestions(): string
    {
        global $sids, $category;

        $date = new Date($this->config);
        $mail = new Mail($this->config);

        $query = sprintf(
            "SELECT COUNT(id) AS num FROM %sfaqquestions WHERE lang = '%s' AND is_visible != 'Y'",
            Database::getTablePrefix(),
            $this->config->getLanguage()->getLanguage()
        );

        $result = $this->config->getDb()->query($query);
        $row = $this->config->getDb()->fetchObject($result);
        $numOfInvisibles = $row->num;

        if ($numOfInvisibles > 0) {
            $extraout = sprintf(
                '<tr><td colspan="3"><small>%s %s</small></td></tr>',
                Translation::get('msgQuestionsWaiting'),
                $numOfInvisibles
            );
        } else {
            $extraout = '';
        }

        $query = sprintf(
            "SELECT * FROM %sfaqquestions WHERE lang = '%s' AND is_visible = 'Y' ORDER BY created ASC",
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
                    Strings::htmlentities($row->username)
                );
                $output .= sprintf(
                    '<td><strong>%s:</strong><br>%s</td>',
                    isset($category->categoryName[$row->category_id]['name']) ?
                        Strings::htmlentities($category->categoryName[$row->category_id]['name']) :
                        '',
                    Strings::htmlentities($row->question)
                );
                if ($this->config->get('records.enableCloseQuestion') && $row->answer_id) {
                    $output .= sprintf(
                        '<td><a id="PMF_openQuestionAnswered" href="?%saction=faq&amp;cat=%d&amp;id=%d">%s</a></td>',
                        $sids,
                        $row->category_id,
                        $row->answer_id,
                        Translation::get('msg2answerFAQ')
                    );
                } else {
                    $output .= sprintf(
                        '<td class="text-end">' .
                        '<a class="btn btn-primary" href="?%saction=add&amp;question=%d&amp;cat=%d">%s</a></td>',
                        $sids,
                        $row->id,
                        $row->category_id,
                        Translation::get('msg2answer')
                    );
                }
                $output .= '</tr>';
            }
        } else {
            $output = sprintf(
                '<tr><td colspan="3">%s</td></tr>',
                Translation::get('msgNoQuestionsAvailable')
            );
        }

        return $output . $extraout;
    }

    /**
     * Set or unset a faq item flag.
     *
     * @param int    $faqId       FAQ id
     * @param string $faqLanguage Language code which is valid with Language::isASupportedLanguage
     * @param bool   $flag        FAQ is set to sticky or not
     * @param string $type        Type of the flag to set, use the column name
     */
    public function updateRecordFlag(int $faqId, string $faqLanguage, bool $flag, string $type): bool
    {
        $flag = match ($type) {
            'sticky' => $flag ? 1 : 0,
            'active' => $flag ? "'yes'" : "'no'",
            default => null,
        };

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
                $faqId,
                $this->config->getDb()->escape($faqLanguage)
            );

            return (bool)$this->config->getDb()->query($update);
        }

        return false;
    }

    /**
     * Prepares and returns the sticky records for the frontend.
     */
    public function getStickyRecords(): array
    {
        $result = $this->getStickyRecordsData();
        $output = [];

        if (count($result) > 0) {
            foreach ($result as $row) {
                $output['title'][] = Utils::makeShorterText(Strings::htmlentities($row['question']), 8);
                $output['preview'][] = Strings::htmlentities($row['question']);
                $output['url'][] = Strings::htmlentities($row['url']);
            }
        } else {
            $output['error'] = sprintf('<li>%s</li>', Translation::get('err_noTopTen'));
        }

        return $output;
    }

    /**
     * Returns the sticky records with URL and Title.
     */
    public function getStickyRecordsData(): array
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
                    '%sindex.php?%saction=faq&amp;cat=%d&amp;id=%d&amp;artlang=%s',
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
     */
    public function getInactiveFaqsData(): array
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
                    '%sadmin/?action=editentry&id=%d&lang=%s',
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
