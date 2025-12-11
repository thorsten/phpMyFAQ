<?php

/**
 * The fulltext search page.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2002-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2002-09-16
 */

use League\CommonMark\Exception\CommonMarkException;
use phpMyFAQ\Category;
use phpMyFAQ\Faq;
use phpMyFAQ\Faq\Permission;
use phpMyFAQ\Filter;
use phpMyFAQ\Helper\SearchHelper;
use phpMyFAQ\Helper\TagsHelper;
use phpMyFAQ\Language\Plurals;
use phpMyFAQ\Pagination;
use phpMyFAQ\Search;
use phpMyFAQ\Search\SearchResultSet;
use phpMyFAQ\Strings;
use phpMyFAQ\Tags;
use phpMyFAQ\Translation;
use phpMyFAQ\Twig\Extensions\TagNameTwigExtension;
use phpMyFAQ\Twig\TwigWrapper;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Twig\Extension\AttributeExtension;
use Twig\TwigFilter;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$faqConfig = $container->get('phpmyfaq.configuration');
$user = $container->get('phpmyfaq.user.current_user');

$faqSession = $container->get('phpmyfaq.user.session');
$faqSession->setCurrentUser($user);
$faqSession->userTracking('fulltext_search', 0);

$faq = new Faq($faqConfig);
$faq->setUser($currentUser);
$faq->setGroups($currentGroups);

$category = new Category($faqConfig, $currentGroups);

$plurals = new Plurals();

// Get possible user input
$request = Request::createFromGlobals();
$inputLanguage = Filter::filterVar($request->query->get('pmf-all-languages'), FILTER_SANITIZE_SPECIAL_CHARS);
$inputCategory = Filter::filterVar($request->query->get('pmf-search-category'), FILTER_VALIDATE_INT, '%');
$inputSearchTerm = Filter::filterVar($request->request->get('search'), FILTER_SANITIZE_SPECIAL_CHARS);
$inputSearchTerm = Strings::substr($inputSearchTerm, 0, 255);
$inputTag = Filter::filterVar($request->query->get('tagging_id'), FILTER_SANITIZE_SPECIAL_CHARS);

if (!is_null($inputTag)) {
    $inputTag = str_replace(' ', '', (string) $inputTag);
    $inputTag = str_replace(',,', ',', $inputTag);
}

$searchTerm = Filter::filterVar($request->attributes->get('search'), FILTER_SANITIZE_SPECIAL_CHARS);
$searchTerm = Strings::substr($searchTerm, 0, 255);
$page = Filter::filterVar($request->query->get('seite'), FILTER_VALIDATE_INT, 1);

// Search only on current language (default)
if ($inputLanguage !== '') {
    $allLanguages = true;
    $languages = '&langs=all';
} else {
    $allLanguages = false;
    $languages = '';
}

if ($allLanguages) {
    $category->transform(0);
}

$faqSearch = new Search($faqConfig);
$faqPermission = new Permission($faqConfig);
$faqSearchResult = new SearchResultSet($user, $faqPermission, $faqConfig);
$tagging = new Tags($faqConfig);
$tagging->setUser($currentUser)->setGroups($currentGroups);
$tagHelper = new TagsHelper();
$tagSearch = false;
$numOfResults = 0;
$searchResults = [];

//
// Handle the Tagging ID
//
if ('' !== $inputTag) {
    $tagSearch = true;
    $tags = [];
    $tagIds = explode(',', (string) $inputTag);
    $relTags = '';

    $tagHelper->setTaggingIds($tagIds);

    foreach ($tagIds as $tagId) {
        if (isset($tags[$tagId])) {
            continue;
        }

        if (!is_numeric($tagId)) {
            continue;
        }

        $tags[$tagId] = $tagging->getTagNameById($tagId);
    }

    $recordIds = $tagging->getFaqsByIntersectionTags($tags);

    $searchResults = [];
    if (0 === (is_countable($recordIds) ? count($recordIds) : 0)) {
        $searchResult = '';
    } else {
        $relatedTags = [];

        foreach ($recordIds as $recordId) {
            $resultTags = $tagging->getAllTagsById($recordId);
            foreach (array_keys($resultTags) as $resultTagId) {
                if (isset($tags[$resultTagId])) {
                    // if the given tag is in the search term, we don't want to list it
                    continue;
                }

                if (isset($relatedTags[$resultTagId])) {
                    ++$relatedTags[$resultTagId];
                } else {
                    $relatedTags[$resultTagId] = 1;
                }
            }
        }

        uasort($relatedTags, static fn($a, $b): int => $b - $a);
        $numTags = 0;

        foreach ($relatedTags as $tagId => $relevance) {
            $relTags .= $tagHelper->renderRelatedTag($tagId, $tagging->getTagNameById($tagId), $relevance);
            if ($numTags++ > 20) {
                break;
            }
        }

        $numOfResults = count($recordIds);

        // Apply pagination to record IDs for tag search
        $confPerPage = $faqConfig->get('records.numberOfRecordsPerPage');
        $first = ($page - 1) * $confPerPage;
        $paginatedRecordIds = array_slice($recordIds, $first, $confPerPage);

        $searchResults = $faq->renderFaqsByFaqIds($paginatedRecordIds, 'fd.id', 'ASC', false);

        // Set base URL scheme for tag search
        $baseUrl = sprintf(
            '%ssearch.html?tagging_id=%s&seite=%d%s',
            $faqConfig->getDefaultUrl(),
            $inputTag,
            $page,
            $languages,
        );
    }
} else {
    $searchResults = [];
    $searchResult = '';
    $relTags = '';
    $tags = [];

    // Set the base URL scheme for fulltext search
    $baseUrl = sprintf(
        '%ssearch.html?search=%s&seite=%d%s&pmf-search-category=%d',
        $faqConfig->getDefaultUrl(),
        urlencode($inputSearchTerm),
        $page,
        $languages,
        $inputCategory,
    );
}

//
// Handle the full text search stuff
//
if ($inputSearchTerm !== '' || $searchTerm !== '') {
    $searchResults = [];
    if ($inputSearchTerm !== '') {
        $inputSearchTerm = $faqConfig->getDb()->escape(strip_tags($inputSearchTerm));
    }

    if ($searchTerm !== '') {
        $inputSearchTerm = $faqConfig->getDb()->escape(strip_tags($searchTerm));
    }

    $faqSearch->setCategory($category);
    $faqSearch->setCategoryId((int) $inputCategory);

    try {
        $searchResults = $faqSearch->search($inputSearchTerm, $allLanguages);
    } catch (Exception $exception) {
        $faqConfig->getLogger()->debug($exception->getMessage());
    }

    foreach ($searchResults as $faqKey => $faqValue) {
        $checkedFaq = $faq->getFaqResult($faqValue->id, $faqValue->lang);
        if (0 === $faqConfig->getDb()->numRows($checkedFaq)) {
            unset($searchResults[$faqKey]);
        }
    }

    $faqSearchResult->reviewResultSet($searchResults);

    $inputSearchTerm = stripslashes((string) $inputSearchTerm);
    $numOfResults = $faqSearchResult->getNumberOfResults();

    try {
        $faqSearch->logSearchTerm($inputSearchTerm);
    } catch (Exception $exception) {
        $faqConfig->getLogger()->debug($exception->getMessage());
    }

    // Set the base URL scheme for fulltext search
    $baseUrl = sprintf(
        '%ssearch.html?search=%s&seite=%d%s&pmf-search-category=%d',
        $faqConfig->getDefaultUrl(),
        urlencode($inputSearchTerm),
        $page,
        $languages,
        $inputCategory,
    );
}

// Change a little the $searchCategory value;
$inputCategory = '%' == $inputCategory ? 0 : $inputCategory;

$faqSession->userTracking('fulltext_search', $inputSearchTerm);

// Number of results
if ($numOfResults === 0) {
    $numOfResults = $faqSearchResult->getNumberOfResults();
}

if (
    is_numeric($inputSearchTerm)
    && PMF_SOLUTION_ID_START_VALUE <= $inputSearchTerm
    && 0 < $numOfResults
    && $faqConfig->get('search.searchForSolutionId')
) {
    $response = new RedirectResponse($faqConfig->getDefaultUrl() . 'solution_id_' . $inputSearchTerm . '.html');
    $response->send();
    exit();
}

$category->buildCategoryTree();

$mostPopularSearchData = $faqSearch->getMostPopularSearches($faqConfig->get('search.numberSearchTerms'));

// Pagination options
$options = [
    'baseUrl' => $baseUrl,
    'total' => $numOfResults,
    'perPage' => (int) $faqConfig->get('records.numberOfRecordsPerPage'),
    'pageParamName' => 'seite',
    'layoutTpl' => '<ul class="pagination justify-content-center">{LAYOUT_CONTENT}</ul>',
];

$faqPagination = new Pagination($options);

$searchHelper = new SearchHelper($faqConfig);
$searchHelper->setSearchTerm($inputSearchTerm);
$searchHelper->setCategory($category);
$searchHelper->setPlurals(new Plurals());
$searchHelper->setSessionId($sids);

if ($numOfResults > 0 && $inputSearchTerm !== '') {
    try {
        $searchResults = $searchHelper->getSearchResult($faqSearchResult, $page);
    } catch (Exception|CommonMarkException) {
        // @todo handle exception
    }
}

$confPerPage = $faqConfig->get('records.numberOfRecordsPerPage');
$totalPages = (int) ceil($numOfResults / $confPerPage);

$twig = new TwigWrapper(PMF_ROOT_DIR . '/assets/templates/');
$twig->addExtension(new AttributeExtension(TagNameTwigExtension::class));
$twig->addFilter(new TwigFilter('repeat', fn($string, $times): string => str_repeat((string) $string, $times)));
$twigTemplate = $twig->loadTemplate('./search.twig');

$pageHeader = $tagSearch ? Translation::get(key: 'msgTagSearch') : Translation::get(key: 'msgAdvancedSearch');

$templateVars = [
    ...$templateVars,
    'title' => sprintf('%s - %s', $pageHeader, $faqConfig->getTitle()),
    'pageHeader' => $pageHeader,
    'isTagSearch' => $tagSearch,
    'selectedCategory' => $inputCategory,
    'categories' => $category->getCategoryTree(),
    'msgSearch' => Translation::get(key: 'msgSearch'),
    'msgAdvancedSearch' => $tagSearch
        ? Translation::get(key: 'msgTagSearch')
        : Translation::get(key: 'msgAdvancedSearch'),
    'msgCurrentTags' => Translation::get(key: 'msg_tags'),
    'numberOfSearchResults' => $numOfResults,
    'totalPages' => $totalPages,
    'msgPage' => Translation::get(key: 'msgPage'),
    'currentPage' => $page,
    'from' => Translation::get(key: 'msgVoteFrom'),
    'msgSearchResults' => $plurals->GetMsg('plmsgSearchAmount', $numOfResults ?? 0),
    'searchTerm' => $searchTerm,
    'searchTags' => $tagSearch ? $tagHelper->renderTagList($tags) : '',
    'msgSearchWord' => Translation::get(key: 'msgSearchWord'),
    'searchResults' => $searchResults,
    'formActionUrl' => '?action=search',
    'searchString' => $inputSearchTerm,
    'searchOnAllLanguages' => Translation::get(key: 'msgSearchOnAllLanguages'),
    'checkedAllLanguages' => $allLanguages ? ' checked' : '',
    'selectCategories' => Translation::get(key: 'msgSelectCategories'),
    'allCategories' => Translation::get(key: 'msgAllCategories'),
    'noSearchResults' => Translation::get(key: 'msgErrorNoRecords'),
    'pagination' => $faqPagination->render(),
    'msgMostPopularSearches' => Translation::get(key: 'msgMostPopularSearches'),
    'mostPopularSearches' => $mostPopularSearchData,
    'relatedTagsHeader' => Translation::get(key: 'msgRelatedTags'),
    'relatedTags' => $relTags,
    'msgTags' => Translation::get(key: 'msgPopularTags'),
    'tagList' => $tagging->getPopularTags(),
];

return $templateVars;
