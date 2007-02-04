<?php
/**
* $Id: show.php,v 1.13 2007-02-04 19:27:50 thorstenr Exp $
*
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @since        2002-08-27
* @copyright    (c) 2001-2007 phpMyFAQ Team
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
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

if (isset($_REQUEST['cat']) && is_numeric($_REQUEST['cat'])) {
    $_cat = (int)$_REQUEST['cat'];
}

if (isset($_cat) && $_cat != 0 && isset($category->categoryName[$_cat])) {
    Tracking('show_category', $_cat);
    $parent = $category->categoryName[$_cat]['parent_id'];
    $name = $category->categoryName[$_cat]['name'];

    $records = $faq->showAllRecords($_cat);
    if (!$records) {
        $categories = new PMF_Category($LANGCODE);
        $categories->transform($_cat);
        $categories->collapseAll();
        $records = $categories->viewTree();
    }

    $up = '';
    if ($parent != 0) {
        $url = sprintf('%saction=show&amp;cat=%d',
                    $sids,
                    $parent
                );
        $oLink = new PMF_Link(PMF_Link::getSystemRelativeUri().'?'.$url);
        $oLink->itemTitle = $category->categoryName[$parent]['name'];
        $oLink->text = $PMF_LANG['msgCategoryUp'];
        $up = $oLink->toHtmlAnchor();
    }

    $tpl->processTemplate('writeContent', array(
                        'writeCategory' => $PMF_LANG['msgEntriesIn'].$name,
                        'writeThemes' => $records,
                        'writeOneThemeBack' => $up));
    $tpl->includeTemplate('writeContent', 'index');
} else {
    Tracking('show_all_categories', 0);
    $tpl->processTemplate('writeContent', array(
                          'writeCategory' => $PMF_LANG['msgFullCategories'],
                          'writeThemes' => $category->viewTree(),
                          'writeOneThemeBack' => ''));
    $tpl->includeTemplate('writeContent', 'index');
}
