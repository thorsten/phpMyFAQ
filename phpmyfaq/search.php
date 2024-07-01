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
 * @copyright 2002-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2002-09-16
 */

use phpMyFAQ\Category;
use phpMyFAQ\Configuration;
use phpMyFAQ\Faq;
use phpMyFAQ\Faq\Permission;
use phpMyFAQ\Filter;
use phpMyFAQ\Helper\CategoryHelper;
use phpMyFAQ\Helper\SearchHelper;
use phpMyFAQ\Helper\TagsHelper;
use phpMyFAQ\Language\Plurals;
use phpMyFAQ\Pagination;
use phpMyFAQ\Search;
use phpMyFAQ\Search\SearchResultSet;
use phpMyFAQ\Strings;
use phpMyFAQ\Tags;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$faqSession->userTracking('fulltext_search', 0);

$faqConfig = Configuration::getConfigurationInstance();

$faq = new Faq($faqConfig);
$faq->setUser($currentUser);
$faq->setGroups($currentGroups);

// Get possible user input
$request = Request::createFromGlobals();
$inputLanguage = Filter::filterVar($request->query->get('pmf-all-languages'), FILTER_SANITIZE_SPECIAL_CHARS);
$inputCategory = Filter::filterVar($request->query->get('pmf-search-category'), FILTER_VALIDATE_INT, '%');
$inputSearchTerm = Filter::filterVar($request->query->get('search'), FILTER_SANITIZE_SPECIAL_CHARS);
$inputSearchTerm = Strings::substr($inputSearchTerm, 0, 255);
$inputTag = Filter::filterVar($request->query->get('tagging_id'), FILTER_SANITIZE_SPECIAL_CHARS);

if (!is_null($inputTag)) {
    $inputTag = str_replace(' ', '', (string) $inputTag);
    $inputTag = str_replace(',,', ',', $inputTag);
}

$searchTerm = Filter::filterVar($request->request->get('search'), FILTER_SANITIZE_SPECIAL_CHARS);
$searchTerm = Strings::substr($searchTerm, 0, 255);
$page = Filter::filterVar($request->query->get('seite'), FILTER_VALIDATE_INT, 1);

// Search only on current language (default)
if ($inputLanguage !== '') {
    $allLanguages = true;
    $languages = '&amp;langs=all';
} else {
    $allLanguages = false;
    $languages = '';
}

// HACK: (re)evaluate the Entity object w/o passing the user language
//       so the result set of a Search will have the Entity Path
//       for any of the multi-language faq records and the Entity list
//       on the left pane will not be affected
if ($allLanguages) {
    $category = new Category($faqConfig);
    $category->transform(0);
}

if (is_null($user)) {
    $user = new CurrentUser($faqConfig);
}

$faqSearch = new Search($faqConfig);
$faqPermission = new Permission($faqConfig);
$faqSearchResult = new SearchResultSet($user, $faqPermission, $faqConfig);
$tagging = new Tags($faqConfig);
$tagHelper = new TagsHelper();
$tagSearch = false;

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

    if (0 === (is_countable($recordIds) ? count($recordIds) : 0)) {
        $searchResult = '';
    } else {
        $relatedTags = [];

        foreach ($recordIds as $recordId) {
            $resultTags = $tagging->getAllTagsById($recordId);
            foreach (array_keys($resultTags) as $resultTagId) {
                if (isset($tags[$resultTagId])) {
                    // if the given tag is in the search term we don't want to list it
                    continue;
                }

                if (isset($relatedTags[$resultTagId])) {
                    ++$relatedTags[$resultTagId];
                } else {
                    $relatedTags[$resultTagId] = 1;
                }
            }
        }

        uasort($relatedTags, static fn($a, $b) => $b - $a);
        $numTags = 0;

        foreach ($relatedTags as $tagId => $relevance) {
            $relTags .= $tagHelper->renderRelatedTag($tagId, $tagging->getTagNameById($tagId), $relevance);
            if ($numTags++ > 20) {
                break;
            }
        }

        $searchResult = $faq->renderRecordsByFaqIds($recordIds);
    }
} else {
    $searchResult = '';
    $relTags = '';
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
        $checkedFaq = $faq->getRecordResult($faqValue->id, $faqValue->lang);
        if (0 === $faqConfig->getDb()->numRows($checkedFaq)) {
            unset($searchResults[$faqKey]);
        }
    }

    $faqSearchResult->reviewResultSet($searchResults);

    $inputSearchTerm = stripslashes($inputSearchTerm);
    try {
        $faqSearch->logSearchTerm($inputSearchTerm);
    } catch (Exception $exception) {
        $faqConfig->getLogger()->debug($exception->getMessage());
    }
} else {
    $inputSearchTerm = '';
}

// Change a little the $searchCategory value;
$inputCategory = ('%' == $inputCategory) ? 0 : $inputCategory;

$faqSession->userTracking('fulltext_search', $inputSearchTerm);

if (
    is_numeric(
        $inputSearchTerm
    ) && PMF_SOLUTION_ID_START_VALUE <= $inputSearchTerm && 0 < $faqSearchResult->getNumberOfResults(
    ) && $faqConfig->get('search.searchForSolutionId')
) {
    $response = new Response();
    $response->isRedirect($faqConfig->getDefaultUrl() . 'solution_id_' . $inputSearchTerm . '.html');
    $response->send();
    exit();
}

$category->buildCategoryTree();

$mostPopularSearchData = $faqSearch->getMostPopularSearches($faqConfig->get('search.numberSearchTerms'));

// Set base URL scheme
$baseUrl = sprintf(
    '%ssearch.html?search=%s&amp;seite=%d%s&amp;pmf-search-category=%d',
    $faqConfig->getDefaultUrl(),
    urlencode($inputSearchTerm),
    $page,
    $languages,
    $inputCategory
);

// Pagination options
$options = [
    'baseUrl' => $baseUrl,
    'total' => $faqSearchResult->getNumberOfResults(),
    'perPage' => $faqConfig->get('records.numberOfRecordsPerPage'),
    'pageParamName' => 'seite',
    'layoutTpl' => '<ul class="pagination justify-content-center">{LAYOUT_CONTENT}</ul>',
];

$faqPagination = new Pagination($options);
$categoryHelper = new CategoryHelper();
$categoryHelper->setCategory($category);

$searchHelper = new SearchHelper($faqConfig);
$searchHelper->setSearchTerm($inputSearchTerm);
$searchHelper->setCategory($category);
$searchHelper->setPagination($faqPagination);
$searchHelper->setPlurals(new Plurals());
$searchHelper->setSessionId($sids);

if ('' == $searchResult && !is_null($inputSearchTerm)) {
    try {
        $searchResult = $searchHelper->renderSearchResult($faqSearchResult, $page);
    } catch (Exception) {
        // @todo handle exception
    }
}

if ($tagSearch) {
    $template->parseBlock(
        'mainPageContent',
        'searchTagsSection',
        [
            'msgCurrentTags' => Translation::get('msg_tags'),
            'searchTags' => $tagHelper->renderTagList($tags),
        ]
    );
    $template->parseBlock(
        'mainPageContent',
        'relatedTags',
        [
            'relatedTagsHeader' => Translation::get('msgRelatedTags'),
            'relatedTags' => $relTags,
        ]
    );
} else {
    if ('' === $searchTerm) {
        $template->parseBlock(
            'mainPageContent',
            'tagListSection',
            [
                'msgTags' => Translation::get('msgPopularTags'),
                'tagList' => $tagging->renderPopularTags(0),
            ]
        );
    }

    $template->parseBlock(
        'mainPageContent',
        'searchBoxSection',
        [
            'formActionUrl' => '?' . $sids . 'action=search',
            'searchString' => Strings::htmlspecialchars($inputSearchTerm, ENT_QUOTES),
            'searchOnAllLanguages' => Translation::get('msgSearchOnAllLanguages'),
            'checkedAllLanguages' => $allLanguages ? ' checked' : '',
            'selectCategories' => Translation::get('msgSelectCategories'),
            'allCategories' => Translation::get('msgAllCategories'),
            'renderCategoryOptions' => $categoryHelper->renderOptions($inputCategory),
            'msgSearch' => Translation::get('msgSearch')
        ]
    );

    $template->parseBlock(
        'mainPageContent',
        'popularSearchesSection',
        [
            'msgMostPopularSearches' => Translation::get('msgMostPopularSearches'),
            'printMostPopularSearches' => $searchHelper->renderMostPopularSearches($mostPopularSearchData)
        ]
    );
}

$template->parse(
    'mainPageContent',
    [
        'pageHeader' => ($tagSearch ? Translation::get('msgTagSearch') : Translation::get('msgAdvancedSearch')),
        'msgAdvancedSearch' => ($tagSearch ? Translation::get('msgTagSearch') : Translation::get('msgAdvancedSearch')),
        'msgSearchWord' => Translation::get('msgSearchWord'),
        'renderSearchResults' => $searchResult,
        'formActionUrl' => '?' . $sids . 'action=search',
        'searchString' => Strings::htmlspecialchars($inputSearchTerm, ENT_QUOTES),
        'searchOnAllLanguages' => Translation::get('msgSearchOnAllLanguages'),
        'checkedAllLanguages' => $allLanguages ? ' checked' : '',
        'selectCategories' => Translation::get('msgSelectCategories'),
        'allCategories' => Translation::get('msgAllCategories'),
        'renderCategoryOptions' => $categoryHelper->renderOptions($inputCategory),
        'msgSearch' => Translation::get('msgSearch'),
        'msgMostPopularSearches' => Translation::get('msgMostPopularSearches'),
        'printMostPopularSearches' => $searchHelper->renderMostPopularSearches($mostPopularSearchData)
    ]
);
