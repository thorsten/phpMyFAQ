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
 * @copyright 2005-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2005-12-20
 */

declare(strict_types=1);

namespace phpMyFAQ;

use Exception;
use League\CommonMark\Exception\CommonMarkException;
use phpMyFAQ\Attachment\AttachmentFactory;
use phpMyFAQ\Entity\FaqEntity;
use phpMyFAQ\Faq\FaqRepository;
use phpMyFAQ\Faq\QueryHelper;
use phpMyFAQ\Helper\FaqHelper;
use phpMyFAQ\Instance\Search\Elasticsearch;
use phpMyFAQ\Language\Plurals;
use phpMyFAQ\Link\Util\TitleSlugifier;
use phpMyFAQ\Pagination\UrlConfig;
use phpMyFAQ\Tenant\TenantQuotaEnforcer;
use stdClass;

/**
 * Class Faq
 *
 * @todo Refactor this class and split it into smaller classes.
 *
 * @package phpMyFAQ
 */
/* @mago-expect lint:too-many-methods - legacy facade over FAQ retrieval; split into repository/services is in progress */
class Faq
{
    public const string QUERY_TYPE_DEFAULT = 'faq_default';
    public const int SORTING_TYPE_NONE = 0;
    public const int SORTING_TYPE_CATID_FAQID = 1;
    public const int SORTING_TYPE_FAQTITLE_FAQID = 2;
    public const int SORTING_TYPE_DATE_FAQID = 3;
    public const int SORTING_TYPE_FAQID = 4;

    /**
     * The current FAQ record.
     */
    /** @var array<string, mixed> */
    public array $faqRecord = [];

    /**
     * All current FAQ records in an array.
     */
    /** @var array<int, array<string, mixed>> */
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
    private ?TenantQuotaEnforcer $tenantQuotaEnforcer = null;

    private readonly FaqRepository $faqRepository;

    /**
     * Constructor.
     */
    public function __construct(
        private readonly Configuration $configuration,
    ) {
        $this->plurals = new Plurals();
        $this->faqRepository = new FaqRepository($this->configuration);

        if ($this->configuration->get(item: 'security.permLevel') !== 'basic') {
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
     * This function returns all not expired FAQs from one category.
     *
     * @param int    $categoryId Entity ID
     * @param string $orderBy    Order by
     * @param string $sortBy     Sort by
     * @throws Exception|CommonMarkException
     */
    public function getAllAvailableFaqsByCategoryId(
        int $categoryId,
        string $orderBy = 'id',
        string $sortBy = 'ASC',
        bool $preview = true,
    ): array {
        $faqData = [];
        [$currentTable, $orderColumn] = $this->normalizeCategoryOrder($orderBy);
        $sortDirection = $this->normalizeSortDirection($sortBy);

        $rows = $this->faqRepository->fetchAvailableFaqsByCategoryId(
            $categoryId,
            $currentTable,
            $orderColumn,
            $sortDirection,
            $this->user,
            $this->groups,
            $this->groupSupport,
        );

        if ($rows === []) {
            return $faqData;
        }

        $faqHelper = new FaqHelper($this->configuration);
        foreach ($rows as $row) {
            $faqId = (int) $row->id;
            $faqLanguage = (string) $row->lang;
            $faqCategoryId = (int) $row->category_id;
            $question = (string) $row->thema;
            $answer = (string) $row->record_content;
            $updated = (string) $row->updated;
            $created = (string) $row->created;
            $visits = (int) ($row->visits ?? 0);

            $url = sprintf(
                '%scontent/%d/%d/%s/%s.html',
                $this->configuration->getDefaultUrl(),
                $faqCategoryId,
                $faqId,
                $faqLanguage,
                TitleSlugifier::slug($question),
            );
            $oLink = new Link($url, $this->configuration);
            $oLink->setTitle($question);
            $oLink->text = $question;
            $oLink->tooltip = $question;

            if ($preview) {
                $faqData[] = [
                    'record_id' => $faqId,
                    'record_lang' => $faqLanguage,
                    'category_id' => $faqCategoryId,
                    'record_title' => $question,
                    'record_preview' => $faqHelper->renderAnswerPreview($answer, 25),
                    'record_link' => $oLink->toString(),
                    'record_updated' => $updated,
                    'visits' => $visits,
                    'record_created' => $created,
                ];
            }

            if (!$preview) {
                $faqData[] = [
                    'faq_id' => $faqId,
                    'faq_lang' => $faqLanguage,
                    'category_id' => $faqCategoryId,
                    'question' => $question,
                    'answer' => $answer,
                    'link' => $oLink->toString(),
                    'updated' => $updated,
                    'visits' => $visits,
                    'created' => $created,
                ];
            }
        }

        return $faqData;
    }

    /**
     * Returns the data needed to render the FAQ list of a category: pagination metadata, the
     * paginated items (each with its rendered link anchor, answer preview, views label and
     * sticky flag) and the pre-rendered pagination control. The markup itself lives in the
     * category-faq-list.twig template.
     *
     * @param int    $categoryId Entity ID
     * @param string $orderBy    Order by
     * @param string $sortBy     Sort by
     * @return array<string, mixed>
     * @throws Exception|CommonMarkException
     */
    public function getFaqsDataByCategoryId(
        int $categoryId,
        string $orderBy = 'id',
        string $sortBy = 'ASC',
        ?int $page = null,
    ): array {
        $numPerPage = (int) $this->configuration->get(item: 'records.numberOfRecordsPerPage');
        $page ??= (int) Filter::filterInput(INPUT_GET, 'seite', FILTER_VALIDATE_INT, 1);
        $page = max(1, (int) $page);
        $title = '';
        [$currentTable, $orderColumn] = $this->normalizeCategoryOrder($orderBy);
        $sortDirection = $this->normalizeSortDirection($sortBy);

        // If random FAQs are activated, we don't need an order
        $order = sprintf('ORDER BY fd.sticky DESC, %s.%s %s', $currentTable, $orderColumn, $sortDirection);

        $num = $this->faqRepository->countRenderableFaqsByCategoryId(
            $categoryId,
            $this->user,
            $this->groups,
            $this->groupSupport,
        );
        $pages = (int) ceil($num / $numPerPage);

        $first = ($page - 1) * $numPerPage;

        $items = [];
        if ($num > 0) {
            /* @mago-expect analysis:mixed-assignment - DB layer query results are untyped by design */
            $result = $this->faqRepository->queryRenderableFaqsByCategoryId(
                $categoryId,
                $order,
                $this->user,
                $this->groups,
                $this->groupSupport,
                $first,
                $numPerPage,
            );
            $renderedItems = [];
            while (true) {
                $row = $this->configuration->getDb()->fetchObject($result);
                if (!$row instanceof stdClass) {
                    break;
                }

                $faqId = (int) $row->id;
                $question = (string) $row->question;
                $visits = (int) ($row->visits ?? 0);

                $title = Strings::htmlentities($question);
                $url = sprintf(
                    '%scontent/%d/%d/%s/%s.html',
                    $this->configuration->getDefaultUrl(),
                    (int) $row->category_id,
                    $faqId,
                    (string) $row->lang,
                    TitleSlugifier::slug($question),
                );

                $oLink = new Link($url, $this->configuration);
                $oLink->setTitle($title);
                $oLink->text = $title;
                $oLink->tooltip = $title;
                $oLink->class = 'text-decoration-none';

                // If random FAQs are activated, we don't need sticky FAQs
                $isSticky =
                    (int) ($row->sticky ?? 0) !== 0 && true !== $this->configuration->get(item: 'records.randomSort');

                $renderedItems[$faqId] = [
                    'anchor' => $oLink->toHtmlAnchor(),
                    'preview' => Utils::chopString(strip_tags((string) $row->answer), 20),
                    'views' => $this->plurals->get(key: 'plmsgViews', number: $visits),
                    'sticky' => $isSticky,
                ];
            }

            // If random FAQs are activated, shuffle the FAQs :-)
            if (true === $this->configuration->get(item: 'records.randomSort')) {
                shuffle($renderedItems);
            }

            $items = array_values($renderedItems);
        }

        $pagination = '';
        if ($pages > 1) {
            $link = new Link($this->configuration->getDefaultUrl(), $this->configuration);
            $rewriteUrl = sprintf(
                '%scategory/%d/%%d/%s.html',
                $this->configuration->getDefaultUrl(),
                $categoryId,
                $link->getSEOTitle($title),
            );

            $category = new Category($this->configuration);

            $baseUrl = sprintf(
                '%scategory/%d/%s.html?seite=%d',
                $this->configuration->getDefaultUrl(),
                $categoryId,
                TitleSlugifier::slug($category->getCategoryName($categoryId)),
                $page,
            );

            $paginationControl = new Pagination(
                baseUrl: $baseUrl,
                total: $num,
                perPage: (int) $this->configuration->get(item: 'records.numberOfRecordsPerPage'),
                urlConfig: new UrlConfig(pageParamName: 'seite', rewriteUrl: $rewriteUrl),
            );
            $pagination = $paginationControl->render();
        }

        return [
            'page' => $page,
            'pages' => $pages,
            'msgPage' => Translation::get(key: 'msgPage'),
            'msgVoteFrom' => Translation::get(key: 'msgVoteFrom'),
            'msgPages' => Translation::get(key: 'msgPages'),
            'items' => $items,
            'pagination' => $pagination,
        ];
    }

    /**
     * Returns the search-result view model for the given, not-expired record ids: a list of
     * objects carrying the FAQ link, the (chopped) question and an answer preview. The markup
     * is rendered by search.twig, so this method only builds data.
     *
     * @param int[]  $faqIds Array of record ids
     * @param string $orderBy Order by
     * @param string $sortBy Sort by
     * @param bool   $usePagination Whether to use internal pagination
     * @return stdClass[]
     * @throws CommonMarkException
     */
    public function getFaqsDataByIds(
        array $faqIds,
        string $orderBy = 'fd.id',
        string $sortBy = 'ASC',
        bool $usePagination = true,
    ): array {
        $records = $this->normalizeFaqIds($faqIds);
        $orderExpression = $this->normalizeFaqOrderBy($orderBy);
        $sortDirection = $this->normalizeSortDirection($sortBy);
        $page = (int) Filter::filterInput(INPUT_GET, 'seite', FILTER_VALIDATE_INT, 1);

        /* @mago-expect analysis:mixed-assignment - DB layer query results are untyped by design */
        $result = $this->faqRepository->queryRenderableFaqsByIds(
            $records,
            $orderExpression,
            $sortDirection,
            $this->user,
            $this->groups,
            $this->groupSupport,
        );

        $num = $this->configuration->getDb()->numRows($result);
        $numberPerPage = (int) $this->configuration->get(item: 'records.numberOfRecordsPerPage');

        $first = $usePagination && $page > 1 ? ($page * $numberPerPage) - $numberPerPage : 0;

        $searchResults = [];
        if ($num > 0) {
            $counter = 0;
            $displayedCounter = 0;
            $lastFaqId = 0;
            $faqHelper = new FaqHelper($this->configuration);
            while (!$usePagination || $displayedCounter < $numberPerPage) {
                $row = $this->configuration->getDb()->fetchObject($result);
                if (!$row instanceof stdClass) {
                    break;
                }

                ++$counter;
                if ($usePagination && $counter <= $first) {
                    continue;
                }

                ++$displayedCounter;

                $faqId = (int) $row->id;
                if ($lastFaqId === $faqId) {
                    continue; // Don't show multiple FAQs
                }

                $rowResult = new stdClass();

                $title = (string) $row->question;
                $url = sprintf(
                    '%scontent/%d/%d/%s/%s.html',
                    $this->configuration->getDefaultUrl(),
                    (int) $row->category_id,
                    $faqId,
                    (string) $row->lang,
                    TitleSlugifier::slug($title),
                );

                $oLink = new Link($url, $this->configuration);
                $oLink->setTitle($title);
                $oLink->text = $title;
                $oLink->tooltip = $title;

                $rowResult->renderedScore = 0;
                $rowResult->question = Utils::chopString(Strings::htmlentities($title), 15);
                $rowResult->path = '';
                $rowResult->url = $oLink->toString();
                $rowResult->answerPreview = Strings::htmlentities($faqHelper->renderAnswerPreview(
                    (string) $row->answer,
                    20,
                ));

                $lastFaqId = $faqId;
                $searchResults[] = $rowResult;
            }
        }

        return $searchResults;
    }

    /**
     * Returns an array with all data from a FAQ record.
     *
     * @param int      $faqId FAQ ID
     * @param int|null $faqRevisionId Revision ID
     * @param bool     $isAdmin Must be true if it is called by an admin/author context
     */
    public function getFaq(int $faqId, ?int $faqRevisionId = null, bool $isAdmin = false): void
    {
        $currentLanguage = $this->configuration->getLanguage()->getLanguage();
        $defaultLanguage = $this->configuration->getDefaultLanguage();

        /* @mago-expect analysis:mixed-assignment - DB layer query results are untyped by design */
        $result = $this->getFaqResult($faqId, $currentLanguage, $faqRevisionId, $isAdmin);

        if (0 === $this->configuration->getDb()->numRows($result)) {
            /* @mago-expect analysis:mixed-assignment - DB layer query results are untyped by design */
            $result = $this->getFaqResult($faqId, $defaultLanguage, $faqRevisionId, $isAdmin);
        }

        $this->faqRecord = [
            'id' => $faqId,
            'lang' => $currentLanguage,
            'solution_id' => 42,
            'revision_id' => $faqRevisionId,
            'active' => 'no',
            'sticky' => 0,
            'keywords' => '',
            'title' => '',
            'content' => Translation::get(key: 'msgAccessDenied'),
            'author' => '',
            'email' => '',
            'comment' => '',
            'date' => Date::createIsoDate(date(format: 'YmdHis')),
            'dateStart' => '',
            'dateEnd' => '',
            'notes' => '',
            'created' => date(format: 'c'),
        ];

        $row = $this->configuration->getDb()->fetchObject($result);
        if ($row instanceof stdClass) {
            $question = nl2br((string) $row->thema);
            $answer = (string) $row->content;
            $active = 'yes' === $row->active;
            $expired = date(format: 'YmdHis') > (string) $row->date_end;

            if (!$isAdmin) {
                if (!$active) {
                    $answer = Translation::getString(key: 'err_inactiveArticle');
                }

                if ($expired) {
                    $answer = Translation::getString(key: 'err_expiredArticle');
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
                'date' => Date::createIsoDate((string) $row->updated),
                'dateStart' => $row->date_start,
                'dateEnd' => $row->date_end,
                'notes' => $row->notes,
                'created' => $row->created,
            ];
        }
    }

    /**
     * Executes a query to retrieve a single FAQ.
     */
    public function getFaqResult(
        int $faqId,
        string $faqLanguage,
        ?int $faqRevisionId = null,
        bool $isAdmin = false,
    ): mixed {
        return $this->faqRepository->getFaqResult(
            $faqId,
            $faqLanguage,
            $faqRevisionId,
            $isAdmin,
            $this->user,
            $this->groups,
            $this->groupSupport,
        );
    }

    /**
     * Return FAQs from given IDs
     *
     * @param int[] $faqIds
     * @throws Exception
     */
    public function getFaqsByIds(array $faqIds, bool $onlyActive = true): array
    {
        $faqRecords = [];
        $records = $this->normalizeFaqIds($faqIds);

        $rows = $this->faqRepository->fetchFaqsByIds(
            $records,
            $onlyActive,
            $this->user,
            $this->groups,
            $this->groupSupport,
        );

        $faqHelper = new FaqHelper($this->configuration);
        foreach ($rows as $row) {
            $question = (string) $row->question;
            $visits = (int) ($row->visits ?? 0);

            $url = sprintf(
                '%scontent/%d/%d/%s/%s.html',
                $this->configuration->getDefaultUrl(),
                (int) $row->category_id,
                (int) $row->id,
                (string) $row->lang,
                TitleSlugifier::slug($question),
            );

            $oLink = new Link($url, $this->configuration);
            $oLink->setTitle($question);
            $oLink->text = $question;
            $oLink->tooltip = $question;

            $faqRecords[] = [
                'record_id' => (int) $row->id,
                'record_lang' => (string) $row->lang,
                'category_id' => (int) $row->category_id,
                'record_title' => $question,
                'record_preview' => $faqHelper->renderAnswerPreview((string) $row->answer, 25),
                'record_link' => $oLink->toString(),
                'record_updated' => Date::createIsoDate((string) $row->updated) . ':00',
                'visits' => $visits,
                'record_created' => (string) $row->created,
            ];
        }

        return $faqRecords;
    }

    /**
     * Returns a FAQ by ID and category ID.
     *
     * @param int $faqId FAQ ID
     * @param int $categoryId Category ID
     * @return array<string, mixed>
     * @throws Exception
     */
    public function getFaqByIdAndCategoryId(int $faqId, int $categoryId, bool $onlyActive = true): array
    {
        $row = $this->faqRepository->fetchFaqByIdAndCategoryId(
            $faqId,
            $categoryId,
            $onlyActive,
            $this->user,
            $this->groups,
            $this->groupSupport,
        );

        if ($row instanceof stdClass) {
            $question = (string) $row->question;
            $url = sprintf(
                '%scontent/%d/%d/%s/%s.html',
                $this->configuration->getDefaultUrl(),
                (int) $row->category_id,
                (int) $row->id,
                (string) $row->lang,
                TitleSlugifier::slug($question),
            );

            $link = new Link($url, $this->configuration);
            $link->setTitle($question);

            return [
                'id' => (int) $row->id,
                'lang' => $row->lang,
                'solution_id' => (int) $row->solution_id,
                'revision_id' => (int) $row->revision_id,
                'active' => $row->active,
                'sticky' => (int) $row->sticky,
                'keywords' => $row->keywords,
                'question' => $question,
                'answer' => $row->answer,
                'author' => $row->author,
                'email' => $row->email,
                'comment' => $row->comment,
                'updated' => $row->updated,
                'date_start' => $row->date_start,
                'date_end' => $row->date_end,
                'created' => $row->created,
                'category_id' => (int) $row->category_id,
                'link' => $link->toString(),
            ];
        }

        return [];
    }

    /**
     * Creates a new FAQ.
     */
    public function create(FaqEntity $faqEntity): FaqEntity
    {
        $this->getTenantQuotaEnforcer()->assertCanCreateFaq();

        if (is_null($faqEntity->getId())) {
            $faqEntity->setId($this->configuration->getDb()->nextId(Database::getTablePrefix() . 'faqdata', 'id'));
        }

        // Only assign a new solutionId if none was provided (or invalid)
        $solutionId = $faqEntity->getSolutionId();
        if ($solutionId === null || $solutionId <= 0) {
            $faqEntity->setSolutionId($this->getNextSolutionId());
        }

        $faqEntity->setRevisionId(0);

        $this->faqRepository->insert($faqEntity);

        return $faqEntity;
    }

    private function getTenantQuotaEnforcer(): TenantQuotaEnforcer
    {
        return $this->tenantQuotaEnforcer ??= TenantQuotaEnforcer::createFromDatabaseDriver(
            $this->configuration->getDb(),
        );
    }

    /**
     * Gets the latest solution id for a FAQ record.
     */
    public function getNextSolutionId(): int
    {
        return $this->faqRepository->getNextSolutionId();
    }

    public function update(FaqEntity $faqEntity): FaqEntity
    {
        $this->faqRepository->update($faqEntity);

        return $faqEntity;
    }

    /**
     * Deletes a record and all the dependencies.
     *
     * @param int    $faqId   Record id
     * @param string $faqLang Record language
     * @throws Attachment\AttachmentException
     * @throws Attachment\Filesystem\File\FileException
     */
    public function delete(int $faqId, string $faqLang): bool
    {
        $solutionId = $this->getSolutionIdFromId($faqId, $faqLang);

        $this->faqRepository->deleteByIdAndLanguage($faqId, $faqLang);

        // Delete possible attachments
        $attachments = AttachmentFactory::fetchByRecordId($this->configuration, $faqId);
        foreach ($attachments as $attachment) {
            $currentAttachment = AttachmentFactory::create($attachment->getId());
            $currentAttachment->delete();
        }

        // Delete possible Elasticsearch documents
        if ($this->configuration->get(item: 'search.enableElasticsearch')) {
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
        return $this->faqRepository->getSolutionIdFromId($faqId, $faqLang);
    }

    /**
     * Checks if a FAQ is already translated.
     *
     * @param int    $faqId   FAQ ID
     * @param string $faqLang FAQ language
     */
    public function hasTranslation(int $faqId, string $faqLang): bool
    {
        return $this->faqRepository->hasTranslation($faqId, $faqLang);
    }

    public function isActive(int $faqId, string $faqLang, string $commentType = 'faq'): bool
    {
        return $this->faqRepository->isActive($faqId, $faqLang, $commentType);
    }

    /**
     * Returns an array with all data from a FAQ record.
     *
     * @param int $solutionId Solution ID
     */
    public function getFaqBySolutionId(int $solutionId): void
    {
        $row = $this->faqRepository->fetchRowBySolutionId($solutionId, $this->user, $this->groups, $this->groupSupport);

        $this->faqRecord = [
            // Ensure faqRecord has at least the requested solution_id to keep API stable
            'solution_id' => $solutionId,
        ];

        if ($row instanceof \stdClass) {
            $question = nl2br((string) $row->thema);
            $content = (string) $row->content;
            $active = 'yes' === $row->active;
            $expired = date(format: 'YmdHis') > (string) $row->date_end;

            if (!$active) {
                $content = Translation::getString(key: 'err_inactiveArticle');
            }

            if ($expired) {
                $content = Translation::getString(key: 'err_expiredArticle');
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
                'date' => Date::createIsoDate((string) $row->updated),
                'dateStart' => $row->date_start,
                'dateEnd' => $row->date_end,
                'notes' => $row->notes,
                'created' => $row->created,
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
        return $this->faqRepository->getIdFromSolutionId($solutionId, $this->user, $this->groups, $this->groupSupport);
    }

    /**
     * Returns an array with all data from all FAQ records.
     *
     * @param int        $sortType  Sorting type
     * @param array<string, mixed>|null $condition Condition
     * @param ?string    $sortOrder Sorting order
     */
    public function getAllFaqs(
        int $sortType = self::SORTING_TYPE_CATID_FAQID,
        ?array $condition = null,
        ?string $sortOrder = 'ASC',
    ): void {
        $sortDirection = $this->normalizeSortDirection((string) $sortOrder);
        $orderBy = match ($sortType) {
            self::SORTING_TYPE_CATID_FAQID => sprintf('ORDER BY fcr.category_id, fd.id %s', $sortDirection),
            self::SORTING_TYPE_FAQID => sprintf('ORDER BY fd.id %s', $sortDirection),
            self::SORTING_TYPE_FAQTITLE_FAQID => sprintf('ORDER BY fcr.category_id, fd.thema %s', $sortDirection),
            self::SORTING_TYPE_DATE_FAQID => sprintf('ORDER BY fcr.category_id, fd.updated %s', $sortDirection),
            default => '',
        };

        $rows = $this->faqRepository->fetchAllFaqs(
            $condition,
            $orderBy,
            $this->user,
            $this->groups,
            $this->groupSupport,
        );

        foreach ($rows as $row) {
            $content = (string) $row->content;
            $active = 'yes' === $row->active;
            $expired = date(format: 'YmdHis') > (string) $row->date_end;

            if (!$active) {
                $content = Translation::getString(key: 'err_inactiveArticle');
            }

            if ($expired) {
                $content = Translation::getString(key: 'err_expiredArticle');
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
                'updated' => Date::createIsoDate((string) $row->updated, 'Y-m-d H:i:s'),
                'dateStart' => $row->date_start,
                'dateEnd' => $row->date_end,
                'created' => $row->created,
                'notes' => $row->notes,
            ];
        }
    }

    /**
     * Returns the FAQ question from the ID.
     *
     * @param int $faqId Record id
     */
    public function getQuestion(int $faqId): string
    {
        if (array_key_exists('id', $this->faqRecord) && $this->faqRecord['id'] === $faqId) {
            return (string) $this->faqRecord['title'];
        }

        $question = $this->faqRepository->fetchQuestion($faqId, $this->configuration->getLanguage()->getLanguage());

        return $question ?? Translation::getString(key: 'no_cats');
    }

    /**
     * Returns the keywords of a FAQ from the ID.
     *
     * @param int $faqId record id
     */
    public function getKeywords(int $faqId): string
    {
        if (array_key_exists('id', $this->faqRecord) && $this->faqRecord['id'] === $faqId) {
            return (string) $this->faqRecord['keywords'];
        }

        $keywords = $this->faqRepository->fetchKeywords($faqId, $this->configuration->getLanguage()->getLanguage());

        return $keywords === null ? '' : Strings::htmlspecialchars($keywords, ENT_QUOTES);
    }

    /**
     * Retrieve faq records according to the constraints provided.
     */
    public function get(
        string $queryType = self::QUERY_TYPE_DEFAULT,
        int $categoryId = 0,
        bool $downwards = true,
        string $lang = '',
        string $date = '',
    ): array {
        $faqs = [];

        $queryHelper = new QueryHelper($this->user, $this->groups);
        $query = $queryHelper->getQuery($queryType, $categoryId, $downwards, $lang, $date);
        $result = $this->configuration->getDb()->query($query);

        if ($this->configuration->getDb()->numRows($result) > 0) {
            $i = 0;
            while (true) {
                $row = $this->configuration->getDb()->fetchObject($result);
                if (!$row instanceof stdClass) {
                    break;
                }

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
     * Returns the sticky records
     * with URL, Faq_ID, Category_ID, Language and Title.
     */
    public function getStickyFaqsData(): array
    {
        $rows = $this->faqRepository->fetchStickyFaqs($this->user, $this->groups, $this->groupSupport);
        $sticky = [];
        $data = [];

        $oldId = 0;
        foreach ($rows as $row) {
            $faqId = (int) $row->id;
            if ($oldId !== $faqId) {
                $question = (string) $row->thema;
                $data['question'] = $question;

                $url = sprintf(
                    '%scontent/%d/%d/%s/%s.html',
                    $this->configuration->getDefaultUrl(),
                    (int) $row->category_id,
                    $faqId,
                    (string) $row->lang,
                    TitleSlugifier::slug($question),
                );
                $oLink = new Link($url, $this->configuration);
                $oLink->setTitle($question);
                $oLink->tooltip = $question;
                $data['url'] = $oLink->toString();
                $data['id'] = $faqId;
                $data['order'] = (int) $row->sticky_order;
                $data['category_id'] = (int) $row->category_id;
                $data['lang'] = (string) $row->lang;

                $sticky[] = $data;
            }

            $oldId = $faqId;
        }

        // Sort stickyData by order if activated
        if ($this->configuration->get(item: 'records.orderStickyFaqsCustom') === true) {
            usort($sticky, $this->sortStickyArrayByOrder(...));
        }

        return $sticky;
    }

    /**
     * Comparison function for usort() of sticky faqs.
     */
    private function sortStickyArrayByOrder(array $first, array $second): int
    {
        return (int) $first['order'] - (int) $second['order'];
    }

    /**
     * @return array{string, string}
     */
    private function normalizeCategoryOrder(string $orderBy): array
    {
        return match ($orderBy) {
            'visits' => ['fv', 'visits'],
            'updated' => ['fd', 'updated'],
            'created' => ['fd', 'created'],
            'thema', 'question' => ['fd', 'thema'],
            'sticky' => ['fd', 'sticky'],
            'sticky_order' => ['fd', 'sticky_order'],
            default => ['fd', 'id'],
        };
    }

    private function normalizeFaqOrderBy(string $orderBy): string
    {
        return match ($orderBy) {
            'fv.visits', 'visits' => 'fv.visits',
            'fd.updated', 'updated' => 'fd.updated',
            'fd.created', 'created' => 'fd.created',
            'fd.thema', 'thema', 'question' => 'fd.thema',
            default => 'fd.id',
        };
    }

    private function normalizeSortDirection(string $sortBy): string
    {
        return strtoupper($sortBy) === 'DESC' ? 'DESC' : 'ASC';
    }

    /**
     * @param array<int|string> $faqIds
     */
    private function normalizeFaqIds(array $faqIds): string
    {
        $normalizedFaqIds = array_map(static fn($faqId): int => (int) $faqId, $faqIds);

        return $normalizedFaqIds === [] ? '0' : implode(', ', $normalizedFaqIds);
    }

    public function hasTitleAHash(string $title): bool
    {
        return (bool) strpos(haystack: $title, needle: '#');
    }
}
