<?php
/**
* $Id: show.php,v 1.6 2005-09-25 09:47:02 thorstenr Exp $
*
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @since        2002-08-27
* @copyright    (c) 2001-2005 phpMyFAQ Team
* 
* The contents of this file are subject to the Mozilla Public License
* Version 1.1 (the 'License'); you may not use this file except in
* compliance with the License. You may obtain a copy of the License at
* http://www.mozilla.org/MPL/
* 
* Software distributed under the License is distributed on an 'AS IS'
* basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
* License for the specific language governing rights and limitations
* under the License.
*/

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://'.$_SERVER['SERVER_NAME'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

if (isset($_REQUEST['cat']) && is_numeric($_REQUEST['cat']) == TRUE) {
	$category = $_REQUEST['cat'];
}

if (isset($category) && $category != 0) {
	Tracking('show_category', $category);
    $parent = $tree->categoryName[$category]['parent_id'];
    $name = $tree->categoryName[$category]['name'];
    
	if ($parent != 0) {
		$up = '<a href="'.$_SERVER['PHP_SELF'].'?'.$sids.'action=show&amp;cat='.$parent.'">'.$PMF_LANG['msgCategoryUp'].'</a>';
    } else {
        $up = '';
    }
    
    $tpl->processTemplate('writeContent', array(
				          'writeCategory' => $PMF_LANG['msgEntriesIn'].$name,
				          'writeThemes' => printThemes($category),
				          'writeOneThemeBack' => $up));
	$tpl->includeTemplate('writeContent', 'index');
} else {
	Tracking('show_all_categories', 0);
	$tpl->processTemplate('writeContent', array(
				          'writeCategory' => $PMF_LANG['msgFullCategories'],
				          'writeThemes' => $tree->viewTree(),
				          'writeOneThemeBack' => ''));
	$tpl->includeTemplate('writeContent', 'index');
}