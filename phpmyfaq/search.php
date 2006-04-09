<?php
/**
* $Id: search.php,v 1.8 2006-04-09 12:14:35 thorstenr Exp $
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
    header('Location: http://'.$_SERVER['SERVER_NAME'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

Tracking("fulltext_search",0);

// HACK: (re)evaluate the Category object w/o passing the user language
//       so the result set of a Search will have the Category Path
//       for any of the multilanguage faq records and the Category list
//       on the left pane will not be affected
$tree = new Category();
$tree->transform(0);

if (isset($_POST['suchbegriff']) || isset($_GET['search'])) {
	if (isset($_POST['suchbegriff'])) {
		$suchbegriff = $db->escape_string(strip_tags($_POST['suchbegriff']));
		$searchcategory = isset($_POST['searchcategory']) ? $db->escape_string(strip_tags($_POST['searchcategory'])) : '%';
	}
	if (isset($_GET['search'])) {
		$suchbegriff = $db->escape_string(strip_tags(urldecode($_GET['search'])));
		$searchcategory = isset($_POST['searchcategory']) ? $db->escape_string(strip_tags($_POST['searchcategory'])) : '%';
	}
	$printResult = searchEngine($suchbegriff, $searchcategory);
} else {
	$printResult = $PMF_LANG['help_search'];
    $suchbegriff = '';
}

$tree->buildTree();

if (!file_exists(dirname(__FILE__).'/'.$_SERVER['SERVER_NAME'].'.pmfsearch.src')) {
    $tpl->processTemplate('writeContent', array(
    				      'msgSearch' => $PMF_LANG['msgSearch'],
                          'searchString' => $suchbegriff,
                          'selectCategories' => $PMF_LANG['msgSelectCategories'],
                          'allCategories' => $PMF_LANG['msgAllCategories'],
                          'printCategoryOptions' => $tree->printCategoryOptions(0),
    				      'writeSendAdress' => $_SERVER['PHP_SELF'].'?'.$sids.'action=search',
    				      'msgSearchWord' => $PMF_LANG['msgSearchWord'],
    				      'printResult' => $printResult,
                          'msgFirefoxPluginTitle' => ''
    				));
} else {
    $tpl->processTemplate('writeContent', array(
    				      'msgSearch' => $PMF_LANG['msgSearch'],
                          'searchString' => $suchbegriff,
                          'selectCategories' => $PMF_LANG['msgSelectCategories'],
                          'allCategories' => $PMF_LANG['msgAllCategories'],
                          'printCategoryOptions' => $tree->printCategoryOptions(0),
    				      'writeSendAdress' => $_SERVER['PHP_SELF'].'?'.$sids.'action=search',
    				      'msgSearchWord' => $PMF_LANG['msgSearchWord'],
    				      'printResult' => $printResult,
                          'msgFirefoxPluginTitle' => '<p><a id="searchplugin" href="javascript:addEngine(\'http://'.$_SERVER['SERVER_NAME'].dirname($_SERVER['SCRIPT_NAME']).'\', \''.$_SERVER['SERVER_NAME'].'.pmfsearch\', \'png\', \'Web\')">'.$PMF_LANG['ad_search_plugin_install'].'</a></p>'
    				));
}

$tpl->includeTemplate('writeContent', 'index');