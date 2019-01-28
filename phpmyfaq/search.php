<?php

/**
 * The fulltext search page.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2002-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2002-09-16
 */

use phpMyFAQ\Category;
use phpMyFAQ\Filter;
use phpMyFAQ\Helper\CategoryHelper;
use phpMyFAQ\Helper\SearchHelper;
use phpMyFAQ\Helper\TagsHelper;
use phpMyFAQ\Link;
use phpMyFAQ\Pagination;
use phpMyFAQ\Search;
use phpMyFAQ\Search\Resultset;
use phpMyFAQ\Strings;
use phpMyFAQ\Tags;
use phpMyFAQ\User\CurrentUser;

if (!defined('IS_VALID_PHPMYFAQ')) {
    $protocol = 'http';
    if (isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) === 'ON') {
        $protocol = 'https';
    }
    header('Location: '.$protocol.'://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

try {
    $faqSession->userTracking('fulltext_search', 0);
} catch (Exception $e) {
    // @todo handle the exception
}

// Get possible user input
$inputLanguage = Filter::filterInput(INPUT_GET, 'langs', FILTER_SANITIZE_STRING);
$inputCategory = Filter::filterInput(INPUT_GET, 'searchcategory', FILTER_VALIDATE_INT, '%');
$inputSearchTerm = Filter::filterInput(INPUT_GET, 'suchbegriff', FILTER_SANITIZE_STRIPPED);
$inputTag = Filter::filterInput(INPUT_GET, 'tagging_id', FILTER_SANITIZE_STRING);
$inputTag = str_replace(' ', '', $inputTag);
$inputTag = str_replace(',,', ',', $inputTag);

$searchTerm = Filter::filterInput(INPUT_GET, 'search', FILTER_SANITIZE_STRIPPED);
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
//       for any of the multilanguage faq records and the Entity list
//       on the left pane will not be affected
if ($allLanguages) {
    $category = new Category($faqConfig);
    $category->transform(0);
}

if (is_null($user)) {
    $user = new CurrentUser($faqConfig);
}

$faqSearch = new Search($faqConfig);
$faqSearchResult = new Resultset($user, $faq, $faqConfig);
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

    $recordIds = $tagging->getRecordsByIntersectionTags($tags);

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
            }
        );
        $numTags = 0;
        $relTags = '';

        foreach ($relatedTags as $tagId => $relevance) {
            $relTags .= $tagHelper->renderRelatedTag($tagId, $tagging->getTagNameById($tagId), $relevance);
            if ($numTags++ > 20) {
                break;
            }
        }
        $searchResult = $faq->showAllRecordsByIds($recordIds);
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
    $faqSearch->setCategoryId($inputCategory);

    $searchResults = [];

    try {
        $searchResults = $faqSearch->search($inputSearchTerm, $allLanguages);
    } catch (PMF_Search_Exception $e) {
        // @todo handle the exception
    }

    $faqSearchResult->reviewResultset($searchResults);

    $inputSearchTerm = stripslashes($inputSearchTerm);
    $faqSearch->logSearchTerm($inputSearchTerm);
}

// Change a little bit the $searchCategory value;
$inputCategory = ('%' == $inputCategory) ? 0 : $inputCategory;

try {
    $faqSession->userTracking('fulltext_search', $inputSearchTerm);
} catch (Exception $e) {
    // @todo handle the exception
}

if (is_numeric($inputSearchTerm) && PMF_SOLUTION_ID_START_VALUE <= $inputSearchTerm &&
    0 < $faqSearchResult->getNumberOfResults() && $faqConfig->get('search.searchForSolutionId')) {

    if ($faqConfig->get('main.enableRewriteRules')) {
        $http->redirect($faqConfig->getDefaultUrl().'solution_id_'.$inputSearchTerm.'.html');
    } else {
        $http->redirect($faqConfig->getDefaultUrl().'index.php?solution_id='.$inputSearchTerm);
    }
    exit();
}

$category->buildTree();

$mostPopularSearchData = $faqSearch->getMostPopularSearches($faqConfig->get('search.numberSearchTerms'));

// Set base URL scheme
if ($faqConfig->get('main.enableRewriteRules')) {
    $baseUrl = sprintf('%ssearch.html?search=%s&amp;seite=%d%s&amp;searchcategory=%d',
        Link::getSystemRelativeUri('index.php'),
        urlencode($inputSearchTerm),
        $page,
        $languages,
        $inputCategory
    );
} else {
    $baseUrl = sprintf('%s?%saction=search&amp;search=%s&amp;seite=%d%s&amp;searchcategory=%d',
        Link::getSystemRelativeUri(),
        empty($sids) ? '' : 'sids='.$sids.'&amp;',
        urlencode($inputSearchTerm),
        $page,
        $languages,
        $inputCategory
    );
}

// Pagination options
$options = array(
    'baseUrl' => $baseUrl,
    'total' => $faqSearchResult->getNumberOfResults(),
    'perPage' => $faqConfig->get('records.numberOfRecordsPerPage'),
    'pageParamName' => 'seite',
    'layoutTpl' => '<div class="text-center"><ul class="pagination">{LAYOUT_CONTENT}</ul></div>',
);

$faqPagination = new Pagination($faqConfig, $options);
$categoryHelper = new CategoryHelper();
$categoryHelper->setCategory($category);

$searchHelper = new SearchHelper($faqConfig);
if (!is_null($inputSearchTerm)) {
    $searchHelper->setSearchterm($inputSearchTerm);
}
$searchHelper->setCategory($category);
$searchHelper->setPagination($faqPagination);
$searchHelper->setPlurals($plr);
$searchHelper->setSessionId($sids);

if ('' == $searchResult && !is_null($inputSearchTerm)) {
    $searchResult = $searchHelper->renderSearchResult($faqSearchResult, $page);
}

if ($tagSearch) {
    $template->parseBlock(
        'writeContent',
        'searchTagsSection',
        [
            'searchTags' => $tagHelper->renderTagList($tags),
        ]
    );
    $template->parseBlock(
        'writeContent',
        'relatedTags',
        [
            'relatedTagsHeader' => $PMF_LANG['msgRelatedTags'],
            'relatedTags' => $relTags,
        ]
    );
} else {
    if ('' === $searchTerm) {
        $template->parseBlock(
            'writeContent',
            'tagListSection',
            [
                'msgTags' => $PMF_LANG['msgPopularTags'],
                'tagList' => $tagging->renderPopularTags(0),
            ]
        );
    }

    $template->parseBlock(
        'writeContent',
        'searchBoxSection',
        [
            'writeSendAdress' => '?'.$sids.'action=search',
            'searchString' => Strings::htmlspecialchars($inputSearchTerm, ENT_QUOTES, 'utf-8'),
            'searchOnAllLanguages' => $PMF_LANG['msgSearchOnAllLanguages'],
            'checkedAllLanguages' => $allLanguages ? ' checked' : '',
            'selectCategories' => $PMF_LANG['msgSelectCategories'],
            'allCategories' => $PMF_LANG['msgAllCategories'],
            'printCategoryOptions' => $categoryHelper->renderOptions($inputCategory),
            'msgSearch' => $PMF_LANG['msgSearch']
        ]
    );

    $template->parseBlock(
        'writeContent',
        'popularSearchesSection',
        [
            'msgMostPopularSearches' => $PMF_LANG['msgMostPopularSearches'],
            'printMostPopularSearches' => $searchHelper->renderMostPopularSearches($mostPopularSearchData)
        ]
    );
}

$template->parse(
    'writeContent',
    [
        'msgAdvancedSearch' => ($tagSearch ? $PMF_LANG['msgTagSearch'] : $PMF_LANG['msgAdvancedSearch']),
        'msgSearchWord' => $PMF_LANG['msgSearchWord'],
        /* @deprecated, the following variables will be removed with v3.0 */
        'printResult' => $searchResult,
        'writeSendAdress' => '?'.$sids.'action=search',
        'searchString' => Strings::htmlspecialchars($inputSearchTerm, ENT_QUOTES, 'utf-8'),
        'searchOnAllLanguages' => $PMF_LANG['msgSearchOnAllLanguages'],
        'checkedAllLanguages' => $allLanguages ? ' checked' : '',
        'selectCategories' => $PMF_LANG['msgSelectCategories'],
        'allCategories' => $PMF_LANG['msgAllCategories'],
        'printCategoryOptions' => $categoryHelper->renderOptions($inputCategory),
        'msgSearch' => $PMF_LANG['msgSearch'],
        'msgMostPopularSearches' => $PMF_LANG['msgMostPopularSearches'],
        'printMostPopularSearches' => $searchHelper->renderMostPopularSearches($mostPopularSearchData)
    ]
);

$template->parseBlock(
    'index',
    'breadcrumb',
    [
        'breadcrumbHeadline' => ($tagSearch ? $PMF_LANG['msgTagSearch'] : $PMF_LANG['msgAdvancedSearch'])
    ]
);
