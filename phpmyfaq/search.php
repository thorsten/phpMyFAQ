<?php
/**
 * The fulltext search page
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Periklis Tsirakidis <tsirakidis@phpdevel.de>
 * @since     2002-09-16
 * @copyright 2002-2008 phpMyFAQ Team
 * @version   CVS: $Id: search.php,v 1.28 2008-06-05 19:48:20 thorstenr Exp $
 *
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

$faqsession->userTracking('fulltext_search', 0);

// Search only on current language (default)
$allLanguages = false;
$langs        = PMF_Filter::filterInput(INPUT_GET, 'langs', FILTER_SANITIZE_STRING);
if (!is_null($langs)) {
    $allLanguages = true;
}

// HACK: (re)evaluate the Category object w/o passing the user language
//       so the result set of a Search will have the Category Path
//       for any of the multilanguage faq records and the Category list
//       on the left pane will not be affected
if ($allLanguages) {
    $category = new PMF_Category();
    $category->transform(0);
}

$faqsearch           = new PMF_Search();
$searchCategory      = PMF_Filter::filterInput(INPUT_GET, 'searchcategory', FILTER_VALIDATE_INT, '%');
$searchterm          = '';
$printResult         = $PMF_LANG['help_search'];
$tagSearch           = false;
$mostPopularSearches = 'n/a'; // to be implemented

//
// Handle the Tagging ID
//
$tag_id      = PMF_Filter::filterInput(INPUT_GET, 'tagging_id', FILTER_VALIDATE_INT);
if (!is_null($tag_id)) {
    $tagSearch   = true;
    $tagging     = new PMF_Tags();
    $record_ids  = $tagging->getRecordsByTagId($tag_id);
    $printResult = $faq->showAllRecordsByIds($record_ids);
}

//
// Handle the full text search stuff
//
$suchbegriff = PMF_Filter::filterInput(INPUT_GET, 'suchbegriff', FILTER_SANITIZE_STRIPPED);
$search      = PMF_Filter::filterInput(INPUT_GET, 'search', FILTER_SANITIZE_STRIPPED);
if (!is_null($suchbegriff) || !is_null($search)) {
    if (!is_null($suchbegriff)) {
        $searchterm = $db->escape_string(strip_tags($suchbegriff));
    }
    if (!is_null($search)) {
        $searchterm = $db->escape_string(strip_tags($search));
    }
    $printResult = searchEngine($searchterm, $searchCategory, $allLanguages);
    $searchterm  = stripslashes($searchterm);
    
    $faqsearch->logSearchTerm($searchterm);
}

// Change a little bit the $searchCategory value;
$searchCategory = ('%' == $searchCategory) ? 0 : $searchCategory;

$faqsession->userTracking('fulltext_search', $searchterm);

$category->buildTree();

$openSearchLink = sprintf('<a class="searchplugin" href="#" onclick="window.external.AddSearchProvider(\'%s/opensearch.php\');">%s</a>',
    PMF_Link::getSystemUri('/index.php'),
    $PMF_LANG['opensearch_plugin_install']);
    
$mostPopularSearches = '';
$mostPopularSearchData = $faqsearch->getMostPopularSearches();
foreach ($mostPopularSearchData as $searchItem) {
    $mostPopularSearches .= sprintf('<a href="?search=%s&submit=Search&action=search">%s</a> (%dx), ',
        urlencode($searchItem['searchterm']),
        $searchItem['searchterm'],
        $searchItem['number']);
}

$tpl->processTemplate('writeContent', array(
    'msgSearch'                => ($tagSearch ? $PMF_LANG['msgTagSearch'] : $PMF_LANG['msgSearch']),
    'searchString'             => $searchterm,
    'searchOnAllLanguages'     => $PMF_LANG['msgSearchOnAllLanguages'],
    'checkedAllLanguages'      => $allLanguages ? ' checked="checked"' : '',
    'selectCategories'         => $PMF_LANG['msgSelectCategories'],
    'allCategories'            => $PMF_LANG['msgAllCategories'],
    'printCategoryOptions'     => $category->printCategoryOptions($searchCategory),
    'writeSendAdress'          => '?'.$sids.'action=search',
    'msgSearchWord'            => $PMF_LANG['msgSearchWord'],
    'printResult'              => $printResult,
    'openSearchLink'           => $openSearchLink,
    'msgMostPopularSearches'   => $PMF_LANG['msgMostPopularSearches'],
    'printMostPopularSearches' => substr($mostPopularSearches, 0, -2)));

$tpl->includeTemplate('writeContent', 'index');
