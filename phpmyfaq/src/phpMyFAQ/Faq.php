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
 * @author    Jan Harms <model_railroader@gmx-topmail.de>
 * @copyright 2005-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2005-12-20
 */

namespace phpMyFAQ;

use Exception;
use phpMyFAQ\Attachment\AttachmentFactory;
use phpMyFAQ\Entity\FaqEntity;
use phpMyFAQ\Faq\QueryHelper;
use phpMyFAQ\Helper\FaqHelper;
use phpMyFAQ\Instance\Elasticsearch;
use phpMyFAQ\Language\Plurals;

/*
 * Query type definitions
 */
define('FAQ_QUERY_TYPE_DEFAULT', 'faq_default');

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
    public function __construct(private readonly Configuration $configuration)
    {
        $this->plurals = new Plurals();

        if ($this->configuration->get('security.permLevel') !== 'basic') {
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
    public function getAllAvailableFaqsByCategoryId(
        int $categoryId,
        string $orderBy = 'id',
        string $sortBy = 'ASC',
        bool $preview = true
    ): array {
        global $sids;

        $faqData = [];

        $currentTable = $orderBy == 'visits' ? 'fv' : 'fd';

        $now = date('YmdHis');
        $queryHelper = new QueryHelper($this->user, $this->groups);
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
            $this->configuration->getLanguage()->getLanguage(),
            $queryHelper->queryPermission($this->groupSupport),
            $currentTable,
            $this->configuration->getDb()->escape($orderBy),
            $this->configuration->getDb()->escape($sortBy)
        );

        $result = $this->configuration->getDb()->query($query);
        $num = $this->configuration->getDb()->numRows($result);

        if ($num > 0) {
            $faqHelper = new FaqHelper($this->configuration);
            while (($row = $this->configuration->getDb()->fetchObject($result))) {
                $visits = empty($row->visits) ? 0 : $row->visits;

                $url = sprintf(
                    '%sindex.php?%saction=faq&cat=%d&id=%d&artlang=%s',
                    $this->configuration->getDefaultUrl(),
                    $sids,
                    $row->category_id,
                    $row->id,
                    $row->lang
                );
                $oLink = new Link($url, $this->configuration);
                $oLink->itemTitle = $row->thema;
                $oLink->text = $row->thema;
                $oLink->tooltip = $row->thema;

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

    public function getAllFaqsByCategory(int $categoryId): array
    {
        $faqData = [];

        $query = sprintf(
            "
            SELECT
                fd.id AS id,
                fd.lang AS lang,
                fd.solution_id AS solution_id,
                fd.active AS active,
                fd.sticky AS sticky,
                fd.thema AS question,
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
                fcr.category_id = %d
            AND
                fd.lang = '%s'
            ORDER BY
                fd.id ASC",
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            $categoryId,
            $this->configuration->getLanguage()->getLanguage(),
        );

        $result = $this->configuration->getDb()->query($query);
        $num = $this->configuration->getDb()->numRows($result);

        if ($num > 0) {
            while (($row = $this->configuration->getDb()->fetchObject($result))) {
                $visits = empty($row->visits) ? 0 : $row->visits;

                $faqData[] = [
                    'id' => $row->id,
                    'language' => $row->lang,
                    'solution_id' => $row->solution_id,
                    'active' => $row->active,
                    'sticky' => $row->sticky ? 'yes' : 'no',
                    'category_id' => $row->category_id,
                    'question' => $row->question,
                    'updated' => $row->updated,
                    'visits' => $visits,
                    'created' => $row->created,
                ];
            }
        } else {
            return $faqData;
        }

        return $faqData;
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

        $numPerPage = $this->configuration->get('records.numberOfRecordsPerPage');
        $page = Filter::filterInput(INPUT_GET, 'seite', FILTER_VALIDATE_INT, 1);
        $output = '';
        $title = '';

        $currentTable = $orderBy == 'visits' ? 'fv' : 'fd';

        // If random FAQs are activated, we don't need an order
        if (true === $this->configuration->get('records.randomSort')) {
            $order = '';
        } else {
            $order = sprintf(
                'ORDER BY fd.sticky DESC, %s.%s %s',
                $currentTable,
                $this->configuration->getDb()->escape($orderBy),
                $this->configuration->getDb()->escape($sortBy)
            );
        }

        $now = date('YmdHis');
        $queryHelper = new QueryHelper($this->user, $this->groups);
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
            $this->configuration->getLanguage()->getLanguage(),
            $queryHelper->queryPermission($this->groupSupport),
            $order
        );

        $result = $this->configuration->getDb()->query($query);
        $num = $this->configuration->getDb()->numRows($result);
        $pages = (int)ceil($num / $numPerPage);

        $first = $page == 1 ? 0 : $page * $numPerPage - $numPerPage;

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
            while (($row = $this->configuration->getDb()->fetchObject($result)) && $displayedCounter < $numPerPage) {
                ++$counter;
                if ($counter <= $first) {
                    continue;
                }

                ++$displayedCounter;

                $visits = empty($row->visits) ? 0 : $row->visits;

                $title = Strings::htmlentities($row->question);
                $url = sprintf(
                    '%sindex.php?%saction=faq&amp;cat=%d&amp;id=%d&amp;artlang=%s',
                    $this->configuration->getDefaultUrl(),
                    $sids,
                    $row->category_id,
                    $row->id,
                    $row->lang
                );

                $oLink = new Link($url, $this->configuration);
                $oLink->itemTitle = $title;
                $oLink->text = $title;
                $oLink->tooltip = $title;
                $oLink->class = 'text-decoration-none';

                // If random FAQs are activated, we don't need sticky FAQs
                if (true === $this->configuration->get('records.randomSort')) {
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
            if (true === $this->configuration->get('records.randomSort')) {
                shuffle($renderedItems);
            }

            $output .= implode("\n", $renderedItems);
            $output .= '</ul>';
        } else {
            return false;
        }

        if ($pages > 1) {
            $link = new Link($this->configuration->getDefaultUrl(), $this->configuration);
            $rewriteUrl = sprintf(
                '%scategory/%d/%%d/%s.html',
                $this->configuration->getDefaultUrl(),
                $categoryId,
                $link->getSEOItemTitle($title)
            );

            $baseUrl = sprintf(
                '%sindex.php?%saction=show&amp;cat=%d&amp;seite=%d',
                $this->configuration->getDefaultUrl(),
                (empty($sids) ? '' : $sids),
                $categoryId,
                $page
            );

            $options = [
                'baseUrl' => $baseUrl,
                'total' => $num,
                'perPage' => $this->configuration->get('records.numberOfRecordsPerPage'),
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
        $queryHelper = new QueryHelper($this->user, $this->groups);
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
            $this->configuration->getLanguage()->getLanguage(),
            $queryHelper->queryPermission($this->groupSupport),
            $this->configuration->getDb()->escape($orderBy),
            $this->configuration->getDb()->escape($sortBy)
        );

        $result = $this->configuration->getDb()->query($query);

        $num = $this->configuration->getDb()->numRows($result);
        $pages = ceil($num / $this->configuration->get('records.numberOfRecordsPerPage'));

        if ($page == 1) {
            $first = 0;
        } else {
            $first = ($page * $this->configuration->get('records.numberOfRecordsPerPage')) -
                $this->configuration->get('records.numberOfRecordsPerPage');
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
                ($row = $this->configuration->getDb()->fetchObject($result)) &&
                $displayedCounter < $this->configuration->get('records.numberOfRecordsPerPage')
            ) {
                ++$counter;
                if ($counter <= $first) {
                    continue;
                }

                ++$displayedCounter;

                if ($lastFaqId == $row->id) {
                    continue; // Don't show multiple FAQs
                }

                $visits = empty($row->visits) ? 0 : $row->visits;

                $title = $row->thema;
                $url = sprintf(
                    '%sindex.php?%saction=faq&amp;cat=%d&amp;id=%d&amp;artlang=%s',
                    $this->configuration->getDefaultUrl(),
                    $sids,
                    $row->category_id,
                    $row->id,
                    $row->lang
                );
                $oLink = new Link($url, $this->configuration);
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

        if ($num > $this->configuration->get('records.numberOfRecordsPerPage')) {
            $output .= '<p class="text-center"><strong>';
            if (!isset($page)) {
                $page = 1;
            }

            $vor = $page - 1;
            $next = $page + 1;
            if ($vor != 0) {
                $url = $sids . '&amp;action=search&amp;tagging_id=' . $taggingId . '&amp;seite=' . $vor;
                $oLink = new Link($this->configuration->getDefaultUrl() . '?' . $url, $this->configuration);
                $oLink->itemTitle = 'tag';
                $oLink->text = Translation::get('msgPrevious');
                $oLink->tooltip = Translation::get('msgPrevious');
                $output .= '[ ' . $oLink->toHtmlAnchor() . ' ]';
            }

            $output .= ' ';
            if ($next <= $pages) {
                $url = $sids . '&amp;action=search&amp;tagging_id=' . $taggingId . '&amp;seite=' . $next;
                $oLink = new Link($this->configuration->getDefaultUrl() . '?' . $url, $this->configuration);
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
    public function getRecord(int $faqId, int $faqRevisionId = null, bool $isAdmin = false): void
    {
        $currentLanguage = $this->configuration->getLanguage()->getLanguage();
        $defaultLanguage = $this->configuration->getDefaultLanguage();

        $result = $this->getRecordResult($faqId, $currentLanguage, $faqRevisionId, $isAdmin);

        if (0 === $this->configuration->getDb()->numRows($result)) {
            $result = $this->getRecordResult($faqId, $defaultLanguage, $faqRevisionId, $isAdmin);
        }

        if ($row = $this->configuration->getDb()->fetchObject($result)) {
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
        $queryHelper = new QueryHelper($this->user, $this->groups);
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
            ($isAdmin) ? 'AND 1=1' : $queryHelper->queryPermission($this->groupSupport)
        );

        return $this->configuration->getDb()->query($query);
    }

    /**
     * Return records from given IDs
     *
     * @throws Exception
     */
    public function getRecordsByIds(array $faqIds): array
    {
        $faqRecords = [];

        $queryHelper = new QueryHelper($this->user, $this->groups);
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
            $this->configuration->getLanguage()->getLanguage(),
            $queryHelper->queryPermission($this->groupSupport)
        );

        $result = $this->configuration->getDb()->query($query);

        $faqHelper = new FaqHelper($this->configuration);
        while ($row = $this->configuration->getDb()->fetchObject($result)) {
            $visits = empty($row->visits) ? 0 : $row->visits;

            $url = sprintf(
                '%sindex.php?action=faq&cat=%d&id=%d&artlang=%s',
                $this->configuration->getDefaultUrl(),
                $row->category_id,
                $row->id,
                $row->lang
            );
            $oLink = new Link($url, $this->configuration);
            $oLink->itemTitle = $row->question;
            $oLink->text = $row->question;
            $oLink->tooltip = $row->question;

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
    public function create(FaqEntity $faqEntity): int
    {
        if (is_null($faqEntity->getId())) {
            $faqEntity->setId($this->configuration->getDb()->nextId(Database::getTablePrefix() . 'faqdata', 'id'));
        }

        $query = sprintf(
            "INSERT INTO %sfaqdata 
            (id, lang, solution_id, revision_id, active, sticky, keywords, thema, content, author, email, comment, 
            updated, date_start, date_end, created, notes)
            VALUES
            (%d, '%s', %d, %d, '%s', %d, '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')",
            Database::getTablePrefix(),
            $faqEntity->getId(),
            $this->configuration->getDb()->escape($faqEntity->getLanguage()),
            $this->getNextSolutionId(),
            0,
            $faqEntity->isActive() ? 'yes' : 'no',
            $faqEntity->isSticky() ? 1 : 0,
            $this->configuration->getDb()->escape($faqEntity->getKeywords()),
            $this->configuration->getDb()->escape($faqEntity->getQuestion()),
            $this->configuration->getDb()->escape($faqEntity->getAnswer()),
            $this->configuration->getDb()->escape($faqEntity->getAuthor()),
            $this->configuration->getDb()->escape($faqEntity->getEmail()),
            $faqEntity->isComment() ? 'y' : 'n',
            $faqEntity->getUpdatedDate()->format('YmdHis'),
            '00000000000000',
            '99991231235959',
            date('Y-m-d H:i:s'),
            $this->configuration->getDb()->escape($faqEntity->getNotes())
        );

        $this->configuration->getDb()->query($query);

        return $faqEntity->getId();
    }

    /**
     * Gets the latest solution id for a FAQ record.
     */
    public function getNextSolutionId(): int
    {
        $latestId = 0;

        $query = sprintf('SELECT MAX(solution_id) AS solution_id FROM %sfaqdata', Database::getTablePrefix());

        $result = $this->configuration->getDb()->query($query);

        if ($result && $row = $this->configuration->getDb()->fetchObject($result)) {
            $latestId = $row->solution_id;
        }

        if ($latestId < PMF_SOLUTION_ID_START_VALUE) {
            return PMF_SOLUTION_ID_START_VALUE;
        }

        return $latestId + PMF_SOLUTION_ID_INCREMENT_VALUE;
    }

    public function update(FaqEntity $faqEntity): bool
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
            $faqEntity->getRevisionId(),
            $faqEntity->isActive() ? 'yes' : 'no',
            $faqEntity->isSticky() ? 1 : 0,
            $this->configuration->getDb()->escape($faqEntity->getKeywords()),
            $this->configuration->getDb()->escape($faqEntity->getQuestion()),
            $this->configuration->getDb()->escape($faqEntity->getAnswer()),
            $this->configuration->getDb()->escape($faqEntity->getAuthor()),
            $this->configuration->getDb()->escape($faqEntity->getEmail()),
            $faqEntity->isComment() ? 'y' : 'n',
            $faqEntity->getUpdatedDate()->format('YmdHis'),
            $faqEntity->getValidFrom()->format('YmdHis'),
            $faqEntity->getValidTo()->format('YmdHis'),
            $this->configuration->getDb()->escape($faqEntity->getNotes()),
            $faqEntity->getId(),
            $this->configuration->getDb()->escape($faqEntity->getLanguage())
        );

        return (bool) $this->configuration->getDb()->query($query);
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

        $queries = [
            sprintf(
                'DELETE FROM %sfaqbookmarks WHERE faqid = %d',
                Database::getTablePrefix(),
                $recordId
            ),
            sprintf(
                "DELETE FROM %sfaqchanges WHERE beitrag = %d AND lang = '%s'",
                Database::getTablePrefix(),
                $recordId,
                $this->configuration->getDb()->escape($recordLang)
            ),
            sprintf(
                "DELETE FROM %sfaqcategoryrelations WHERE record_id = %d AND record_lang = '%s'",
                Database::getTablePrefix(),
                $recordId,
                $this->configuration->getDb()->escape($recordLang)
            ),
            sprintf(
                "DELETE FROM %sfaqdata WHERE id = %d AND lang = '%s'",
                Database::getTablePrefix(),
                $recordId,
                $this->configuration->getDb()->escape($recordLang)
            ),
            sprintf(
                "DELETE FROM %sfaqdata_revisions WHERE id = %d AND lang = '%s'",
                Database::getTablePrefix(),
                $recordId,
                $this->configuration->getDb()->escape($recordLang)
            ),
            sprintf(
                "DELETE FROM %sfaqvisits WHERE id = %d AND lang = '%s'",
                Database::getTablePrefix(),
                $recordId,
                $this->configuration->getDb()->escape($recordLang)
            ),
            sprintf(
                'DELETE FROM %sfaqdata_user WHERE record_id = %d',
                Database::getTablePrefix(),
                $recordId
            ),
            sprintf(
                'DELETE FROM %sfaqdata_group WHERE record_id = %d',
                Database::getTablePrefix(),
                $recordId
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
            )
        ];

        foreach ($queries as $query) {
            $this->configuration->getDb()->query($query);
        }

        // Delete possible attachments
        $attachments = AttachmentFactory::fetchByRecordId($this->configuration, $recordId);
        foreach ($attachments as $attachment) {
            $currentAttachment = AttachmentFactory::create($attachment->getId());
            $currentAttachment->delete();
        }

        // Delete possible Elasticsearch documents
        if ($this->configuration->get('search.enableElasticsearch')) {
            $elasticsearch = new Elasticsearch($this->configuration);
            $elasticsearch->delete($solutionId);
        }

        return true;
    }

    /**
     * Returns the solution ID from a given ID and language
     */
    public function getSolutionIdFromId(int $faqId, string $faqLang): int
    {
        $query = sprintf(
            "SELECT solution_id FROM %sfaqdata WHERE id = %d AND lang = '%s'",
            Database::getTablePrefix(),
            $faqId,
            $this->configuration->getDb()->escape($faqLang)
        );

        $result = $this->configuration->getDb()->query($query);

        if ($row = $this->configuration->getDb()->fetchObject($result)) {
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
            $this->configuration->getDb()->escape($recordLang)
        );

        $result = $this->configuration->getDb()->query($query);
        return (bool) $this->configuration->getDb()->numRows($result);
    }

    public function isActive(int $recordId, string $recordLang, string $commentType = 'faq'): bool
    {

        $table = 'news' === $commentType ? 'faqnews' : 'faqdata';

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
            $this->configuration->getDb()->escape($recordLang)
        );

        $result = $this->configuration->getDb()->query($query);

        if ($row = $this->configuration->getDb()->fetchObject($result)) {
            if (($row->active === 'y') || ($row->active === 'yes')) {
                return false;
            }
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
        $queryHelper = new QueryHelper($this->user, $this->groups);
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
            $queryHelper->queryPermission($this->groupSupport)
        );

        $result = $this->configuration->getDb()->query($query);

        if ($row = $this->configuration->getDb()->fetchObject($result)) {
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

        $result = $this->configuration->getDb()->query($query);

        if ($row = $this->configuration->getDb()->fetchObject($result)) {
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
                        $where .= $separator . "'" . $this->configuration->getDb()->escape($value) . "'";
                        $separator = ', ';
                    }

                    $where .= ')';
                } else {
                    $where .= " = '" . $this->configuration->getDb()->escape($data) . "'";
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
        $queryHelper = new QueryHelper($this->user, $this->groups);
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
            $queryHelper->queryPermission($this->groupSupport),
            $groupBy,
            $orderBy
        );

        $result = $this->configuration->getDb()->query($query);

        while ($row = $this->configuration->getDb()->fetchObject($result)) {
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
            $this->configuration->getLanguage()->getLanguage()
        );
        $result = $this->configuration->getDb()->query($query);

        if ($this->configuration->getDb()->numRows($result) > 0) {
            while ($row = $this->configuration->getDb()->fetchObject($result)) {
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
            $this->configuration->getLanguage()->getLanguage()
        );

        $result = $this->configuration->getDb()->query($query);

        if ($this->configuration->getDb()->numRows($result) > 0) {
            $row = $this->configuration->getDb()->fetchObject($result);

            return Strings::htmlspecialchars($row->keywords, ENT_QUOTES, 'utf-8');
        }
        return '';
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
            $this->configuration->getLanguage()->getLanguage()
        );

        $result = $this->configuration->getDb()->query($query);

        if ($this->configuration->getDb()->numRows($result) > 0) {
            $row = $this->configuration->getDb()->fetchObject($result);
            $answerPreview = strip_tags((string) $row->answer);
        } else {
            $answerPreview = $this->configuration->get('main.metaDescription');
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
            null === $language ? '' : "AND lang = '" . $this->configuration->getDb()->escape($language) . "'",
            $now,
            $now
        );

        $num = $this->configuration->getDb()->numRows($this->configuration->getDb()->query($query));

        if ($num > 0) {
            return $num;
        }
        return 0;
    }

    /**
     * This function generates a list with the most voted or most visited records.
     *
     * @param string $type Type definition visits/voted
     */
    public function getTopTen(string $type = 'visits'): array
    {
        if ('visits' === $type) {
            $result = $this->getTopTenData(
                PMF_NUMBER_RECORDS_TOPTEN,
                0,
                $this->configuration->getLanguage()->getLanguage()
            );
        } else {
            $result = $this->getTopVotedData(
                PMF_NUMBER_RECORDS_TOPTEN,
                $this->configuration->getLanguage()->getLanguage()
            );
        }

        $output = [];

        if ($result !== []) {
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
        $queryHelper = new QueryHelper($this->user, $this->groups);
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
                fcr.category_id = \'' . $categoryId . "'";
        }

        if (isset($language) && Language::isASupportedLanguage($language)) {
            $query .= '
            AND
                fd.lang = \'' . $this->configuration->getDb()->escape($language) . "'";
        }

        $query .= '
                ' . $queryHelper->queryPermission($this->groupSupport) . '

            GROUP BY
                fd.id,fd.lang,fcr.category_id,fv.visits,fv.last_visit,fdg.group_id,fdu.user_id
            ORDER BY
                fv.visits DESC';

        $result = $this->configuration->getDb()->query($query);
        $topTen = [];
        $data = [];

        if ($result) {
            while ($row = $this->configuration->getDb()->fetchObject($result)) {
                if ($this->groupSupport) {
                    if (!in_array($row->user_id, [-1, $this->user])) {
                        continue;
                    }
                    if (!in_array($row->group_id, $this->groups)) {
                        continue;
                    }
                } elseif (!in_array($row->user_id, [-1, $this->user])) {
                    continue;
                }

                $data['visits'] = (int)$row->visits;
                $data['question'] = Filter::filterVar($row->question, FILTER_SANITIZE_SPECIAL_CHARS);
                $data['answer'] = $row->answer;
                $data['date'] = Date::createIsoDate($row->updated, DATE_ISO8601, true);
                $data['last_visit'] = date('c', $row->last_visit);

                $title = $row->question;
                $url = sprintf(
                    '%sindex.php?%saction=faq&amp;cat=%d&amp;id=%d&amp;artlang=%s',
                    $this->configuration->getDefaultUrl(),
                    $sids,
                    $row->category_id,
                    $row->id,
                    $row->lang
                );
                $oLink = new Link($url, $this->configuration);
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
        $topten = [];
        $data = [];

        $now = date('YmdHis');
        $queryHelper = new QueryHelper($this->user, $this->groups);
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
                fcr.category_id = \'' . $categoryId . "'";
        }

        if (isset($language) && Language::isASupportedLanguage($language)) {
            $query .= '
            AND
                fd.lang = \'' . $this->configuration->getDb()->escape($language) . "'";
        }

        $query .= '
                ' . $queryHelper->queryPermission($this->groupSupport) . '
            ORDER BY
                avg DESC';

        $result = $this->configuration->getDb()->query($query);

        $i = 1;
        $oldId = 0;
        while (($row = $this->configuration->getDb()->fetchObject($result)) && $i <= $count) {
            if ($oldId != $row->id) {
                $data['avg'] = $row->avg;
                $data['question'] = $row->thema;
                $data['date'] = $row->updated;
                $data['user'] = $row->user;

                $title = $row->thema;
                $url = sprintf(
                    '%sindex.php?%saction=faq&amp;cat=%d&amp;id=%d&amp;artlang=%s',
                    $this->configuration->getDefaultUrl(),
                    $sids,
                    $row->category_id,
                    $row->id,
                    $row->lang
                );
                $oLink = new Link($url, $this->configuration);
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
        $date = new Date($this->configuration);
        $result = $this->getLatestData(PMF_NUMBER_RECORDS_LATEST, $this->configuration->getLanguage()->getLanguage());
        $output = [];

        if ($result !== []) {
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
        $queryHelper = new QueryHelper($this->user, $this->groups);
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
                fd.lang = \'' . $this->configuration->getDb()->escape($language) . "'";
        }

        $query .= '
                ' . $queryHelper->queryPermission($this->groupSupport) . '
            GROUP BY
                fd.id,fd.lang,fcr.category_id,fv.visits,fdg.group_id,fdu.user_id
            ORDER BY
                fd.updated DESC';

        $result = $this->configuration->getDb()->query($query);
        $latest = [];
        $data = [];

        if ($result) {
            while (($row = $this->configuration->getDb()->fetchObject($result))) {
                if ($this->groupSupport) {
                    if (!in_array($row->user_id, [-1, $this->user])) {
                        continue;
                    }
                    if (!in_array($row->group_id, $this->groups)) {
                        continue;
                    }
                } elseif (!in_array($row->user_id, [-1, $this->user])) {
                    continue;
                }

                $data['date'] = Date::createIsoDate($row->updated, DATE_ISO8601, true);
                $data['question'] = Filter::filterVar($row->question, FILTER_SANITIZE_SPECIAL_CHARS);
                $data['answer'] = $row->content;
                $data['visits'] = (int)$row->visits;

                $title = $row->question;
                $url = sprintf(
                    '%sindex.php?%saction=faq&amp;cat=%d&amp;id=%d&amp;artlang=%s',
                    $this->configuration->getDefaultUrl(),
                    $sids,
                    $row->category_id,
                    $row->id,
                    $row->lang
                );
                $oLink = new Link($url, $this->configuration);
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

        $queryHelper = new QueryHelper($this->user, $this->groups);
        $query = $queryHelper->getQuery($queryType, $nCatid, $bDownwards, $lang, $date);
        $result = $this->configuration->getDb()->query($query);

        if ($this->configuration->getDb()->numRows($result) > 0) {
            $i = 0;
            while ($row = $this->configuration->getDb()->fetchObject($result)) {
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
     * Returns all records of one category.
     *
     *
     */
    public function getRecordsWithoutPagingByCategoryId(int $categoryId): string
    {
        global $sids;

        $output = '';
        $now = date('YmdHis');
        $queryHelper = new QueryHelper($this->user, $this->groups);
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
            $this->configuration->getLanguage()->getLanguage(),
            $queryHelper->queryPermission($this->groupSupport),
            $this->configuration->get('records.orderby'),
            $this->configuration->get('records.sortby')
        );

        $result = $this->configuration->getDb()->query($query);

        if ($result) {
            $output = '<ul>';
            while (($row = $this->configuration->getDb()->fetchObject($result))) {
                $title = $row->thema;
                $url = sprintf(
                    '%sindex.php?%saction=faq&amp;cat=%d&amp;id=%d&amp;artlang=%s',
                    $this->configuration->getDefaultUrl(),
                    $sids,
                    $row->category_id,
                    $row->id,
                    $row->lang
                );

                $oLink = new Link($url, $this->configuration);
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

        $date = new Date($this->configuration);
        $mail = new Mail($this->configuration);

        $query = sprintf(
            "SELECT COUNT(id) AS num FROM %sfaqquestions WHERE lang = '%s' AND is_visible != 'Y'",
            Database::getTablePrefix(),
            $this->configuration->getLanguage()->getLanguage()
        );

        $result = $this->configuration->getDb()->query($query);
        $row = $this->configuration->getDb()->fetchObject($result);
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
            $this->configuration->getLanguage()->getLanguage()
        );

        $result = $this->configuration->getDb()->query($query);
        $output = '';

        if ($result && $this->configuration->getDb()->numRows($result) > 0) {
            while ($row = $this->configuration->getDb()->fetchObject($result)) {
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
                if ($this->configuration->get('records.enableCloseQuestion') && $row->answer_id) {
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
                $this->configuration->getDb()->escape($faqLanguage)
            );

            return (bool)$this->configuration->getDb()->query($update);
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

        if ($result !== []) {
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
        $queryHelper = new QueryHelper($this->user, $this->groups);
        $query = sprintf(
            "
            SELECT
                fd.id AS id,
                fd.lang AS lang,
                fd.thema AS thema,
                fd.sticky_order AS sticky_order,
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
            $this->configuration->getLanguage()->getLanguage(),
            $now,
            $now,
            $queryHelper->queryPermission($this->groupSupport)
        );

        $result = $this->configuration->getDb()->query($query);
        $sticky = [];
        $data = [];

        $oldId = 0;
        while (($row = $this->configuration->getDb()->fetchObject($result))) {
            if ($oldId != $row->id) {
                $data['question'] = $row->thema;

                $title = $row->thema;
                $url = sprintf(
                    '%sindex.php?%saction=faq&amp;cat=%d&amp;id=%d&amp;artlang=%s',
                    $this->configuration->getDefaultUrl(),
                    $sids,
                    $row->category_id,
                    $row->id,
                    $row->lang
                );
                $oLink = new Link($url, $this->configuration);
                $oLink->itemTitle = $row->thema;
                $oLink->tooltip = $title;
                $data['url'] = $oLink->toString();
                $data['id'] = $row->id;
                $data['order'] = $row->sticky_order;

                $sticky[] = $data;
            }

            $oldId = $row->id;
        }

        // Sort stickyData by order if activated
        if ($this->configuration->get('records.orderStickyFaqsCustom') === true) {
            usort($sticky, $this->sortStickyArrayByOrder(...));
        }

        return $sticky;
    }

    /**
    * Comparison function for usort() of sticky faqs.
    */
    private function sortStickyArrayByOrder($a, $b)
    {
        return $a['order'] - $b['order'];
    }

    /**
     * Returns true if saving the order of the sticky faqs was successfull.
     *
     * @param array $faqIds Order of record id's
     */
    public function setStickyFaqOrder(array $faqIds): bool
    {
        $count = 1;
        $counter = count($faqIds);
        for ($i = 0; $i < $counter; ++$i) {
            $query = sprintf(
                "UPDATE %sfaqdata SET sticky_order=%d WHERE id=%d",
                Database::getTablePrefix(),
                $count,
                $faqIds[$i]
            );
            $this->configuration->getDb()->query($query);
            ++$count;
        }

        return true;
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
            $this->configuration->getLanguage()->getLanguage()
        );

        $result = $this->configuration->getDb()->query($query);
        $inactive = [];
        $data = [];

        $oldId = 0;
        while (($row = $this->configuration->getDb()->fetchObject($result))) {
            if ($oldId != $row->id) {
                $data['question'] = $row->thema;
                $data['url'] = sprintf(
                    '%sadmin/?action=editentry&id=%d&lang=%s',
                    $this->configuration->getDefaultUrl(),
                    $row->id,
                    $row->lang
                );
                $inactive[] = $data;
            }

            $oldId = $row->id;
        }

        return $inactive;
    }

    public function hasTitleAHash(string $title): bool
    {
        return strpos($title, '#');
    }
}
