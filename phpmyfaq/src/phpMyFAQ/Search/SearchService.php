<?php

/**
 * Search Service
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-01-02
 */

declare(strict_types=1);

namespace phpMyFAQ\Search;

use Exception;
use League\CommonMark\Exception\CommonMarkException;
use phpMyFAQ\Category;
use phpMyFAQ\Configuration;
use phpMyFAQ\Faq;
use phpMyFAQ\Faq\Permission;
use phpMyFAQ\Helper\SearchHelper;
use phpMyFAQ\Helper\TagsHelper;
use phpMyFAQ\Language\Plurals;
use phpMyFAQ\Pagination;
use phpMyFAQ\Search;
use phpMyFAQ\Tags;
use phpMyFAQ\User\CurrentUser;

/**
 * Service class for search-related business logic.
 */
final class SearchService
{
    private Faq $faq;

    private Category $category;

    private Tags $tags;

    private Search $faqSearch;

    private SearchResultSet $searchResultSet;

    public function __construct(
        private readonly Configuration $configuration,
        private readonly CurrentUser $currentUser,
        private readonly array $currentGroups,
    ) {
        $this->faq = new Faq($this->configuration);
        $this->faq->setUser($this->currentUser->getUserId());
        $this->faq->setGroups($this->currentGroups);

        $this->category = new Category($this->configuration, $this->currentGroups);

        $this->tags = new Tags($this->configuration);
        $this->tags->setUser($this->currentUser->getUserId())->setGroups($this->currentGroups);

        $this->faqSearch = new Search($this->configuration);

        $faqPermission = new Permission($this->configuration);
        $this->searchResultSet = new SearchResultSet($this->currentUser, $faqPermission, $this->configuration);
    }

    /**
     * Processes search input and returns search results with metadata.
     *
     * @throws CommonMarkException
     * @return array<string, mixed>
     */
    public function processSearch(
        string $inputSearchTerm,
        string $inputTag,
        int|string $inputCategory,
        bool $allLanguages,
        int $page,
    ): array {
        $tagSearch = false;
        $numOfResults = 0;
        $searchResults = [];
        $relTags = '';
        $tags = [];

        // Handle tag search
        if ($inputTag !== '') {
            $tagSearchData = $this->handleTagSearch($inputTag, $page, $allLanguages);
            $tagSearch = true;
            $numOfResults = $tagSearchData['numOfResults'];
            $searchResults = $tagSearchData['searchResults'];
            $relTags = $tagSearchData['relTags'];
            $tags = $tagSearchData['tags'];
            $baseUrl = $tagSearchData['baseUrl'];
        } else {
            // Set base URL for fulltext search
            $baseUrl = sprintf(
                '%ssearch.html?search=%s&seite=%d%s&pmf-search-category=%d',
                $this->configuration->getDefaultUrl(),
                urlencode($inputSearchTerm),
                $page,
                $allLanguages ? '&langs=all' : '',
                $inputCategory,
            );
        }

        // Handle fulltext search
        if ($inputSearchTerm !== '') {
            $fulltextData = $this->handleFulltextSearch($inputSearchTerm, (int) $inputCategory, $allLanguages, $page);
            $searchResults = $fulltextData['searchResults'];
            $numOfResults = $fulltextData['numOfResults'];
            $baseUrl = $fulltextData['baseUrl'];
        }

        // Change category value
        $inputCategory = '%' === $inputCategory ? 0 : $inputCategory;

        // Number of results
        if ($numOfResults === 0) {
            $numOfResults = $this->searchResultSet->getNumberOfResults();
        }

        // Build category tree
        if ($allLanguages) {
            $this->category->transform(0);
        }

        $this->category->buildCategoryTree();

        // Get most popular searches
        $mostPopularSearchData = $this->faqSearch->getMostPopularSearches((int) $this->configuration->get(
            'search.numberSearchTerms',
        ));

        // Setup pagination
        $confPerPage = $this->configuration->get('records.numberOfRecordsPerPage');
        $totalPages = (int) ceil($numOfResults / $confPerPage);

        $options = [
            'baseUrl' => $baseUrl,
            'total' => $numOfResults,
            'perPage' => $confPerPage,
            'pageParamName' => 'seite',
            'layoutTpl' => '<ul class="pagination justify-content-center">{LAYOUT_CONTENT}</ul>',
        ];

        $faqPagination = new Pagination($options);

        // Get formatted search results
        $formattedSearchResults = [];
        if ($numOfResults > 0 && $inputSearchTerm !== '') {
            $formattedSearchResults = $this->getFormattedSearchResults($inputSearchTerm, $page);
        }

        return [
            'tagSearch' => $tagSearch,
            'selectedCategory' => $inputCategory,
            'categories' => $this->category->getCategoryTree(),
            'numberOfSearchResults' => $numOfResults,
            'totalPages' => $totalPages,
            'currentPage' => $page,
            'searchTerm' => $inputSearchTerm,
            'searchTags' => $tagSearch ? $this->renderTagList($tags) : '',
            'searchResults' => $formattedSearchResults !== [] ? $formattedSearchResults : $searchResults,
            'allLanguages' => $allLanguages,
            'mostPopularSearches' => $mostPopularSearchData,
            'relatedTags' => $relTags,
            'tagList' => $this->tags->getPopularTags(),
            'pagination' => $faqPagination->render(),
        ];
    }

    /**
     * Handles tag-based search.
     *
     * @throws CommonMarkException
     * @return array<string, mixed>
     */
    private function handleTagSearch(string $inputTag, int $page, bool $allLanguages): array
    {
        $tags = [];
        $tagIds = explode(',', $inputTag);
        $relTags = '';
        $searchResults = [];

        $tagsHelper = new TagsHelper();
        $tagsHelper->setTaggingIds($tagIds);

        foreach ($tagIds as $tagId) {
            if (isset($tags[$tagId])) {
                continue;
            }
            if (!is_numeric($tagId)) {
                continue;
            }

            $tags[$tagId] = $this->tags->getTagNameById((int) $tagId);
        }

        $recordIds = $this->tags->getFaqsByIntersectionTags($tags);

        if (count($recordIds) > 0) {
            $relatedTags = $this->calculateRelatedTags($recordIds, $tags);

            uasort($relatedTags, static fn($a, $b): int => $b - $a);
            $numTags = 0;

            foreach ($relatedTags as $tagId => $relevance) {
                $relTags .= $tagsHelper->renderRelatedTag($tagId, $this->tags->getTagNameById($tagId), $relevance);
                if ($numTags++ > 20) {
                    break;
                }
            }

            $numOfResults = count($recordIds);

            // Apply pagination to record IDs for tag search
            $confPerPage = (int) $this->configuration->get('records.numberOfRecordsPerPage');
            $first = ($page - 1) * $confPerPage;
            $paginatedRecordIds = array_slice($recordIds, $first, $confPerPage);

            $searchResults = $this->faq->renderFaqsByFaqIds($paginatedRecordIds, 'fd.id', 'ASC', false);
        } else {
            $numOfResults = 0;
        }

        // Set base URL scheme for tag search
        $baseUrl = sprintf(
            '%ssearch.html?tagging_id=%s&seite=%d%s',
            $this->configuration->getDefaultUrl(),
            $inputTag,
            $page,
            $allLanguages ? '&langs=all' : '',
        );

        return [
            'tags' => $tags,
            'relTags' => $relTags,
            'searchResults' => $searchResults,
            'numOfResults' => $numOfResults,
            'baseUrl' => $baseUrl,
        ];
    }

    /**
     * Calculates related tags for the given record IDs.
     *
     * @param array<int> $recordIds
     * @param array<int, string> $tags
     * @return array<int, int>
     */
    private function calculateRelatedTags(array $recordIds, array $tags): array
    {
        $relatedTags = [];

        foreach ($recordIds as $recordId) {
            $resultTags = $this->tags->getAllTagsById($recordId);
            foreach (array_keys($resultTags) as $resultTagId) {
                if (isset($tags[$resultTagId])) {
                    continue;
                }

                if (isset($relatedTags[$resultTagId])) {
                    ++$relatedTags[$resultTagId];
                } else {
                    $relatedTags[$resultTagId] = 1;
                }
            }
        }

        return $relatedTags;
    }

    /**
     * Handles fulltext search.
     *
     * @return array<string, mixed>
     */
    private function handleFulltextSearch(
        string $inputSearchTerm,
        int $inputCategory,
        bool $allLanguages,
        int $page,
    ): array {
        $searchResults = [];

        $inputSearchTerm = $this->configuration->getDb()->escape(strip_tags($inputSearchTerm));

        $this->faqSearch->setCategory($this->category);
        $this->faqSearch->setCategoryId($inputCategory);

        try {
            $searchResults = $this->faqSearch->search($inputSearchTerm, $allLanguages);
        } catch (Exception $exception) {
            $this->configuration->getLogger()->debug($exception->getMessage());
        }

        foreach ($searchResults as $faqKey => $faqValue) {
            $checkedFaq = $this->faq->getFaqResult($faqValue->id, $faqValue->lang);
            if (0 === $this->configuration->getDb()->numRows($checkedFaq)) {
                unset($searchResults[$faqKey]);
            }
        }

        $this->searchResultSet->reviewResultSet($searchResults);

        $inputSearchTerm = stripslashes($inputSearchTerm);
        $numOfResults = $this->searchResultSet->getNumberOfResults();

        try {
            $this->faqSearch->logSearchTerm($inputSearchTerm);
        } catch (Exception $exception) {
            $this->configuration->getLogger()->debug($exception->getMessage());
        }

        // Set the base URL scheme for fulltext search
        $baseUrl = sprintf(
            '%ssearch.html?search=%s&seite=%d%s&pmf-search-category=%d',
            $this->configuration->getDefaultUrl(),
            urlencode($inputSearchTerm),
            $page,
            $allLanguages ? '&langs=all' : '',
            $inputCategory,
        );

        return [
            'searchResults' => $searchResults,
            'numOfResults' => $numOfResults,
            'baseUrl' => $baseUrl,
        ];
    }

    /**
     * Gets formatted search results using SearchHelper.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getFormattedSearchResults(string $searchTerm, int $page): array
    {
        $searchHelper = new SearchHelper($this->configuration);
        $searchHelper->setSearchTerm($searchTerm);
        $searchHelper->setCategory($this->category);
        $searchHelper->setPlurals(new Plurals());

        try {
            return $searchHelper->getSearchResult($this->searchResultSet, $page);
        } catch (Exception|CommonMarkException) {
            return [];
        }
    }

    /**
     * Renders the tag list for display.
     *
     * @param array<int, string> $tags
     */
    private function renderTagList(array $tags): string
    {
        $tagsHelper = new TagsHelper();
        $tagsHelper->setTaggingIds(array_keys($tags));
        return $tagsHelper->renderTagList($tags);
    }

    /**
     * Checks if search should redirect to solution ID.
     */
    public function shouldRedirectToSolutionId(string $inputSearchTerm, int $numOfResults): bool
    {
        return (
            is_numeric($inputSearchTerm)
            && PMF_SOLUTION_ID_START_VALUE <= $inputSearchTerm
            && 0 < $numOfResults
            && $this->configuration->get('search.searchForSolutionId')
        );
    }

    /**
     * Gets the solution ID redirect URL.
     */
    public function getSolutionIdRedirectUrl(string $inputSearchTerm): string
    {
        return $this->configuration->getDefaultUrl() . 'solution_id_' . $inputSearchTerm . '.html';
    }
}
