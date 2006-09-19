<?php
/**
* $Id: search.php,v 1.14 2006-09-19 21:28:32 matteo Exp $
*
* The fulltext search page
*
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @author       Periklis Tsirakidis <tsirakidis@phpdevel.de>
* @since        2002-09-16
* @copyright    (c) 2001-2006 phpMyFAQ Team
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
    header('Location: http://'.$_SERVER['HTTP_HOST]'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

Tracking("fulltext_search",0);

// Search only on current language (default)
$allLanguages = false;
if (isset($_GET['langs'])) {
    $allLanguages = ('all' == $_GET['langs']);
}

// HACK: (re)evaluate the Category object w/o passing the user language
//       so the result set of a Search will have the Category Path
//       for any of the multilanguage faq records and the Category list
//       on the left pane will not be affected
if ($allLanguages) {
    $tree = new PMF_Category();
    $tree->transform(0);
}

$searchCategory = isset($_GET['searchcategory']) && is_numeric($_GET['searchcategory']) ? $_GET['searchcategory'] : '%';
$suchbegriff = '';
$printResult = $PMF_LANG['help_search'];

//
// Handle the full text search stuff
if (isset($_GET['suchbegriff']) || isset($_GET['search'])) {
    if (isset($_GET['suchbegriff'])) {
        $suchbegriff = $db->escape_string(strip_tags($_GET['suchbegriff']));
    }
    if (isset($_GET['search'])) {
        $suchbegriff = $db->escape_string(strip_tags($_GET['search']));
    }
    $printResult = searchEngine($suchbegriff, $searchCategory, $allLanguages);
}

//
// Handle the Tagging ID
//
if (isset($_GET['tagging_id']) && is_numeric($_GET['tagging_id'])) {

}

// Change a little bit the $searchCategory value;
$searchCategory = ('%' == $searchCategory) ? 0 : $searchCategory;

Tracking('fulltext_search', $suchbegriff);

$tree->buildTree();

$baseUrl = PMF_Link::getSystemUri('/index.php');
$firefoxPluginTitle = '';
$MSIEPluginTitle = '';

if (@file_exists(dirname(__FILE__).'/'.$_SERVER['HTTP_HOST'].'.pmfsearch.src')) {
    $firefoxPluginTitle = '<p><a class="searchplugin" href="javascript:addEngine(\''.$baseUrl.'\', \''.$_SERVER['HTTP_HOST'].'.pmfsearch\', \'png\', \'Web\')">'.$PMF_LANG['ad_search_plugin_install'].'</a></p>';
}
if (@file_exists(dirname(__FILE__).'/'.$_SERVER['HTTP_HOST'].'.pmfsearch.xml')) {
    $MSIEPluginTitle = '<p><a class="searchplugin" href="#" onclick="window.external.AddSearchProvider(&quot;'.$baseUrl.'/'.$_SERVER['HTTP_HOST'].'.pmfsearch.xml&quot;);">'.$PMF_LANG['ad_msiesearch_plugin_install'].'</a></p>';
}

$tpl->processTemplate('writeContent',
                        array(
                        'msgSearch'             => $PMF_LANG['msgSearch'],
                        'searchString'          => $suchbegriff,
                        'searchOnAllLanguages'  => $PMF_LANG['msgSearchOnAllLanguages'],
                        'checkedAllLanguages'   => $allLanguages ? ' checked="checked"' : '',
                        'selectCategories'      => $PMF_LANG['msgSelectCategories'],
                        'allCategories'         => $PMF_LANG['msgAllCategories'],
                        'printCategoryOptions'  => $tree->printCategoryOptions($searchCategory),
                        'writeSendAdress'       => $_SERVER['PHP_SELF'].'?'.$sids.'action=search',
                        'msgSearchWord'         => $PMF_LANG['msgSearchWord'],
                        'printResult'           => $printResult,
                        'msgFirefoxPluginTitle' => $firefoxPluginTitle,
                        'msgMSIEPluginTitle'    => $MSIEPluginTitle
                        )
                    );

$tpl->includeTemplate('writeContent', 'index');
