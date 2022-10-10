<?php

/**
 * The fulltext search page.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2002-2022 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2002-09-16
 */

use phpMyFAQ\Category;
use phpMyFAQ\Faq;
use phpMyFAQ\Faq\FaqPermission;
use phpMyFAQ\Filter;
use phpMyFAQ\Helper\CategoryHelper;
use phpMyFAQ\Helper\SearchHelper;
use phpMyFAQ\Helper\TagsHelper;
use phpMyFAQ\Pagination;
use phpMyFAQ\Search;
use phpMyFAQ\Search\SearchResultSet;
use phpMyFAQ\Strings;
use phpMyFAQ\Tags;
use phpMyFAQ\User\CurrentUser;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

try {
    $faqSession->userTracking('fulltext_search', 0);
} catch (Exception $e) {
    // @todo handle the exception
}

$faq = new Faq($faqConfig);
$faq->setUser($currentUser);
$faq->setGroups($currentGroups);

// Get possible user input
$inputLanguage = Filter::filterInput(INPUT_GET, 'langs', FILTER_UNSAFE_RAW);
$inputCategory = Filter::filterInput(INPUT_GET, 'searchcategory', FILTER_VALIDATE_INT, '%');
$inputSearchTerm = Filter::filterInput(INPUT_GET, 'search', FILTER_UNSAFE_RAW, '');
$inputTag = Filter::filterInput(INPUT_GET, 'tagging_id', FILTER_UNSAFE_RAW, '');
$inputTag = str_replace(' ', '', $inputTag);
$inputTag = str_replace(',,', ',', $inputTag);

$searchTerm = Filter::filterInput(INPUT_POST, 'search', FILTER_UNSAFE_RAW);
$page = Filter::filterInput(INPUT_GET, 'seite', FILTER_VALIDATE_INT, 1);

// Search only on current language (default)
if (!is_null($inputLanguage)) {
    $allLanguages = true;
    $languages = '&amp;langs=all';
} else {
    $allLanguages = false;
    $languages = '';
}

// HACK: (re)evaluate the Entity object w/o passing the user language
//       so the result set of a Search will have the Entity Path
//       for any of the multi language faq records and the Entity list
//       on the left pane will not be affected
if ($allLanguages) {
    $category = new Category($faqConfig);
    $category->transform(0);
}

if (is_null($user)) {
    $user = new CurrentUser($faqConfig);
}

$faqSearch = new Search($faqConfig);
$faqPermission = new FaqPermission($faqConfig);
$faqSearchResult = new SearchResultSet($user, $faqPermission, $faqConfig);
$tagging = new Tags($faqConfig);
$tagHelper = new TagsHelper();
$tagSearch = false;

//
// Handle the Tagging ID
//
if (!is_null($inputTag) && '' !== $inputTag) {
    $tagSearch = true;
    $tags = [];
    $tagIds = explode(',', $inputTag);

    $tagHelper->setTaggingIds($tagIds);

    foreach ($tagIds as $tagId) {
        if (!isset($tags[$tagId])) {
            $tags[$tagId] = $tagging->getTagNameById($tagId);
        }
    }

    $recordIds = $tagging->getFaqsByIntersectionTags($tags);

    if (0 === count($recordIds)) {
        $searchResult = '';
    } else {
        $relatedTags = [];

        foreach ($recordIds as $recordId) {
            $resultTags = $tagging->getAllTagsById($recordId);
            foreach ($resultTags as $resultTagId => $resultTagName) {
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

        uasort($relatedTags, function ($a, $b) {
            return ($b - $a);
        });
        $numTags = 0;
        $relTags = '';

        foreach ($relatedTags as $tagId => $relevance) {
            $relTags .= $tagHelper->renderRelatedTag((int)$tagId, $tagging->getTagNameById($tagId), $relevance);
            if ($numTags++ > 20) {
                break;
            }
        }
        $searchResult = $faq->renderRecordsByFaqIds($recordIds);
    }
} else {
    $searchResult = '';
}

//
// Handle the full text search stuff
//
if (!is_null($inputSearchTerm) || !is_null($searchTerm)) {
    if (!is_null($inputSearchTerm)) {
        $inputSearchTerm = $faqConfig->getDb()->escape(strip_tags($inputSearchTerm));
    }
    if (!is_null($searchTerm)) {
        $inputSearchTerm = $faqConfig->getDb()->escape(strip_tags($searchTerm));
    }

    $faqSearch->setCategory($category);
    $faqSearch->setCategoryId((int) $inputCategory);

    $searchResults = $faqSearch->search($inputSearchTerm, $allLanguages);

    foreach ($searchResults as $faqKey => $faqValue) {
        $checkedFaq = $faq->getRecordResult($faqValue->id, $faqValue->lang);
        if (0 === $checkedFaq->num_rows) {
            unset($searchResults[$faqKey]);
        }
    }

    $faqSearchResult->reviewResultSet($searchResults);

    $inputSearchTerm = stripslashes($inputSearchTerm);
    try {
        $faqSearch->logSearchTerm($inputSearchTerm);
    } catch (Exception $e) {
        // @todo handle exception
    }
}

// Change a little bit the $searchCategory value;
$inputCategory = ('%' == $inputCategory) ? 0 : $inputCategory;

try {
    $faqSession->userTracking('fulltext_search', $inputSearchTerm);
} catch (Exception $e) {
    // @todo handle the exception
}

if (
    is_numeric(
        $inputSearchTerm
    ) && PMF_SOLUTION_ID_START_VALUE <= $inputSearchTerm && 0 < $faqSearchResult->getNumberOfResults(
    ) && $faqConfig->get('search.searchForSolutionId')
) {
    if ($faqConfig->get('main.enableRewriteRules')) {
        $http->redirect($faqConfig->getDefaultUrl() . 'solution_id_' . $inputSearchTerm . '.html');
    } else {
        $http->redirect($faqConfig->getDefaultUrl() . 'index.php?solution_id=' . $inputSearchTerm);
    }
    exit();
}

$category->buildCategoryTree();

$mostPopularSearchData = $faqSearch->getMostPopularSearches($faqConfig->get('search.numberSearchTerms'));

// Set base URL scheme
if ($faqConfig->get('main.enableRewriteRules')) {
    $baseUrl = sprintf(
        '%ssearch.html?search=%s&amp;seite=%d%s&amp;searchcategory=%d',
        $faqConfig->getDefaultUrl(),
        urlencode($inputSearchTerm),
        $page,
        $languages,
        $inputCategory
    );
} else {
    $baseUrl = sprintf(
        '%s?%saction=search&amp;search=%s&amp;seite=%d%s&amp;searchcategory=%d',
        $faqConfig->getDefaultUrl(),
        empty($sids) ? '' : 'sids=' . $sids . '&amp;',
        urlencode($inputSearchTerm),
        $page,
        $languages,
        $inputCategory
    );
}

// Pagination options
$options = [
    'baseUrl' => $baseUrl,
    'total' => $faqSearchResult->getNumberOfResults(),
    'perPage' => $faqConfig->get('records.numberOfRecordsPerPage'),
    'pageParamName' => 'seite',
    'layoutTpl' => '<ul class="pagination justify-content-center">{LAYOUT_CONTENT}</ul>',
];

$faqPagination = new Pagination($faqConfig, $options);
$categoryHelper = new CategoryHelper();
$categoryHelper->setCategory($category);

$searchHelper = new SearchHelper($faqConfig);
if (!is_null($inputSearchTerm)) {
    $searchHelper->setSearchTerm($inputSearchTerm);
}
$searchHelper->setCategory($category);
$searchHelper->setPagination($faqPagination);
$searchHelper->setPlurals($plr);
$searchHelper->setSessionId($sids);

if ('' == $searchResult && !is_null($inputSearchTerm)) {
    try {
        $searchResult = $searchHelper->renderSearchResult($faqSearchResult, $page);
    } catch (Exception $e) {
        // @todo handle exception
    }
}

if ($tagSearch) {
    $template->parseBlock(
        'mainPageContent',
        'searchTagsSection',
        [
            'searchTags' => $tagHelper->renderTagList($tags),
        ]
    );
    $template->parseBlock(
        'mainPageContent',
        'relatedTags',
        [
            'relatedTagsHeader' => $PMF_LANG['msgRelatedTags'],
            'relatedTags' => $relTags,
        ]
    );
} else {
    if ('' === $searchTerm) {
        $template->parseBlock(
            'mainPageContent',
            'tagListSection',
            [
                'msgTags' => $PMF_LANG['msgPopularTags'],
                'tagList' => $tagging->renderPopularTags(0),
            ]
        );
    }

    $template->parseBlock(
        'mainPageContent',
        'searchBoxSection',
        [
            'writeSendAdress' => '?' . $sids . 'action=search',
            'searchString' => Strings::htmlspecialchars($inputSearchTerm, ENT_QUOTES, 'utf-8'),
            'searchOnAllLanguages' => $PMF_LANG['msgSearchOnAllLanguages'],
            'checkedAllLanguages' => $allLanguages ? ' checked' : '',
            'selectCategories' => $PMF_LANG['msgSelectCategories'],
            'allCategories' => $PMF_LANG['msgAllCategories'],
            'renderCategoryOptions' => $categoryHelper->renderOptions($inputCategory),
            'msgSearch' => $PMF_LANG['msgSearch']
        ]
    );

    $template->parseBlock(
        'mainPageContent',
        'popularSearchesSection',
        [
            'msgMostPopularSearches' => $PMF_LANG['msgMostPopularSearches'],
            'printMostPopularSearches' => $searchHelper->renderMostPopularSearches($mostPopularSearchData)
        ]
    );
}

$template->parse(
    'mainPageContent',
    [
        'pageHeader' => ($tagSearch ? $PMF_LANG['msgTagSearch'] : $PMF_LANG['msgAdvancedSearch']),
        'msgAdvancedSearch' => ($tagSearch ? $PMF_LANG['msgTagSearch'] : $PMF_LANG['msgAdvancedSearch']),
        'msgSearchWord' => $PMF_LANG['msgSearchWord'],
        'renderSearchResults' => $searchResult,
        'writeSendAdress' => '?' . $sids . 'action=search',
        'searchString' => Strings::htmlspecialchars($inputSearchTerm, ENT_QUOTES),
        'searchOnAllLanguages' => $PMF_LANG['msgSearchOnAllLanguages'],
        'checkedAllLanguages' => $allLanguages ? ' checked' : '',
        'selectCategories' => $PMF_LANG['msgSelectCategories'],
        'allCategories' => $PMF_LANG['msgAllCategories'],
        'renderCategoryOptions' => $categoryHelper->renderOptions($inputCategory),
        'msgSearch' => $PMF_LANG['msgSearch'],
        'msgMostPopularSearches' => $PMF_LANG['msgMostPopularSearches'],
        'printMostPopularSearches' => $searchHelper->renderMostPopularSearches($mostPopularSearchData)
    ]
);
