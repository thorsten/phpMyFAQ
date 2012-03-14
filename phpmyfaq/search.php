<?php
/**
 * The fulltext search page
 *
 * PHP Version 5.2
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Frontend
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2002-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2002-09-16
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

$faqsession->userTracking('fulltext_search', 0);

// Get possible user input
$inputLanguage   = PMF_Filter::filterInput(INPUT_GET, 'langs', FILTER_SANITIZE_STRING);
$inputCategory   = PMF_Filter::filterInput(INPUT_GET, 'searchcategory', FILTER_VALIDATE_INT, '%');
$inputTag        = PMF_Filter::filterInput(INPUT_GET, 'tagging_id', FILTER_VALIDATE_INT);
$inputSearchTerm = PMF_Filter::filterInput(INPUT_GET, 'suchbegriff', FILTER_SANITIZE_STRIPPED);
$search          = PMF_Filter::filterInput(INPUT_GET, 'search', FILTER_SANITIZE_STRIPPED);
$page            = PMF_Filter::filterInput(INPUT_GET, 'seite', FILTER_VALIDATE_INT, 1);

// Search only on current language (default)
if (!is_null($inputLanguage)) {
    $allLanguages = true;
    $languages    = '&amp;langs=all';
} else {
    $allLanguages = false;
    $languages    = '';
}

// HACK: (re)evaluate the Category object w/o passing the user language
//       so the result set of a Search will have the Category Path
//       for any of the multilanguage faq records and the Category list
//       on the left pane will not be affected
if ($allLanguages) {
    $category = new PMF_Category();
    $category->transform(0);
}

if (is_null($user)) {
    $user = new PMF_User_CurrentUser($faqConfig);
}

$faqSearch       = new PMF_Search($faqConfig);
$faqSearchResult = new PMF_Search_Resultset($user, $faq);
$tagSearch       = false;

//
// Handle the Tagging ID
//
if (!is_null($inputTag)) {
    $tagSearch    = true;
    $tagging      = new PMF_Tags($db, $Language);
    $recordIds    = $tagging->getRecordsByTagId($inputTag);
    $searchResult = $faq->showAllRecordsByIds($recordIds);
} else {
    $searchResult = '';
}

//
// Handle the full text search stuff
//
if (!is_null($inputSearchTerm) || !is_null($search)) {
    if (!is_null($inputSearchTerm)) {
        $inputSearchTerm = $db->escape(strip_tags($inputSearchTerm));
    }
    if (!is_null($search)) {
        $inputSearchTerm = $db->escape(strip_tags($search));
    }

    $faqSearch->setCategory($category);
    $faqSearch->setCategoryId($inputCategory);
    $searchResults = $faqSearch->search($inputSearchTerm, $allLanguages);
    
    $faqSearchResult->reviewResultset($searchResults);
    
    $inputSearchTerm = stripslashes($inputSearchTerm);
    $faqSearch->logSearchTerm($inputSearchTerm);
}

// Change a little bit the $searchCategory value;
$inputCategory = ('%' == $inputCategory) ? 0 : $inputCategory;

$faqsession->userTracking('fulltext_search', $inputSearchTerm);

if (is_numeric($inputSearchTerm) && PMF_SOLUTION_ID_START_VALUE <= $inputSearchTerm && 
    0 < $faqSearchResult->getNumberOfResults()) {
    
    // Before a redirection we must force the PHP session update for preventing data loss
    session_write_close();
    if ($faqConfig->get('main.enableRewriteRules')) {
        header('Location: ' . $faqConfig->get('main.referenceURL') . '/solution_id_' . $inputSearchTerm . '.html');
    } else {
        header('Location: ' . $faqConfig->get('main.referenceURL') . '/index.php?solution_id=' . $inputSearchTerm);
    }
    exit();
}

$category->buildTree();

$mostPopularSearchData = $faqSearch->getMostPopularSearches($faqConfig->get('search.numberSearchTerms'));

// Set base URL scheme
if ($faqConfig->get('main.enableRewriteRules')) {
    $baseUrl = sprintf("%ssearch.html?search=%s&amp;seite=%d%s&amp;searchcategory=%d",
        PMF_Link::getSystemRelativeUri('index.php'),
        urlencode($inputSearchTerm),
        $page,
        $languages,
        $inputCategory);
} else {
    $baseUrl = sprintf('%s?%saction=search&amp;search=%s&amp;seite=%d%s&amp;searchcategory=%d',
        PMF_Link::getSystemRelativeUri(),
        empty($sids) ? '' : '$sids&amp;',
        urlencode($inputSearchTerm),
        $page,
        $languages,
        $inputCategory);
}

// Pagination options
$options = array(
    'baseUrl'         => $baseUrl,
    'total'           => $faqSearchResult->getNumberOfResults(),
    'perPage'         => $faqConfig->get('records.numberOfRecordsPerPage'),
    'pageParamName'   => 'seite',
    'nextPageLinkTpl' => '<a href="{LINK_URL}">' . $PMF_LANG['msgNext'] . '</a>',
    'prevPageLinkTpl' => '<a href="{LINK_URL}">' . $PMF_LANG['msgPrevious'] . '</a>',
    'layoutTpl'       => '<p align="center"><strong>{LAYOUT_CONTENT}</strong></p>');

$faqPagination     = new PMF_Pagination($options);
$faqCategoryHelper = PMF_Helper_Category::getInstance();
$faqCategoryHelper->setCategory($category);

$faqSearchHelper = PMF_Helper_Search::getInstance();
$faqSearchHelper->setSearchterm($inputSearchTerm);
$faqSearchHelper->setCategory($category);
$faqSearchHelper->setPagination($faqPagination);
$faqSearchHelper->setPlurals($plr);
$faqSearchHelper->setSessionId($sids);

if ('' == $searchResult && !is_null($inputSearchTerm)) {
    $searchResult = $faqSearchHelper->renderSearchResult($faqSearchResult, $page);
}

$tpl->parse('writeContent', array(
    'msgAdvancedSearch'        => ($tagSearch ? $PMF_LANG['msgTagSearch'] : $PMF_LANG['msgAdvancedSearch']),
    'msgSearch'                => $PMF_LANG['msgSearch'],
    'searchString'             => PMF_String::htmlspecialchars($inputSearchTerm, ENT_QUOTES, 'utf-8'),
    'searchOnAllLanguages'     => $PMF_LANG['msgSearchOnAllLanguages'],
    'checkedAllLanguages'      => $allLanguages ? ' checked="checked"' : '',
    'selectCategories'         => $PMF_LANG['msgSelectCategories'],
    'allCategories'            => $PMF_LANG['msgAllCategories'],
    'printCategoryOptions'     => $faqCategoryHelper->renderCategoryOptions($inputCategory),
    'writeSendAdress'          => '?'.$sids.'action=search',
    'msgSearchWord'            => $PMF_LANG['msgSearchWord'],
    'printResult'              => $searchResult,
    'openSearchLink'           => $faqSearchHelper->renderOpenSearchLink(),
    'msgMostPopularSearches'   => $PMF_LANG['msgMostPopularSearches'],
    'printMostPopularSearches' => $faqSearchHelper->renderMostPopularSearches($mostPopularSearchData)));

$tpl->merge('writeContent', 'index');