<?php
/**
 * Frontend for categories or list of records
 *
 * @package    phpMyFAQ
 * @subpackage Frontend
 * @author     Thorsten Rinne <thorsten@phpmyfaq.de>
 * @since      2002-08-27
 * @copyright  2002-2011 phpMyFAQ Team
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

$currentCategory = PMF_Filter::filterInput(INPUT_GET, 'cat', FILTER_VALIDATE_INT);

if (!is_null($currentCategory) && isset($category->categoryName[$currentCategory])) {

    $faqsession->userTracking('show_category', $currentCategory);
    $parent              = $category->categoryName[$currentCategory]['parent_id'];
    $name                = $category->categoryName[$currentCategory]['name'];
    $categoryDescription = $category->categoryName[$currentCategory]['description'];
    $records             = $faq->showAllRecords($currentCategory, 
                                                $faqconfig->get('records.orderby'), 
                                                $faqconfig->get('records.sortby'));
    
    if (!$records) {
        $subCategory = new PMF_Category($current_user, $current_groups, true);
        $subCategory->transform($currentCategory);
        $records = $subCategory->viewTree();
    }

    $up = '';
    if ($parent != 0) {
        $url = sprintf('%saction=show&amp;cat=%d',
                    $sids,
                    $parent);
        $oLink            = new PMF_Link(PMF_Link::getSystemRelativeUri().'?'.$url);
        $oLink->itemTitle = $category->categoryName[$parent]['name'];
        $oLink->text      = $PMF_LANG['msgCategoryUp'];
        $up               = $oLink->toHtmlAnchor();
    }

    $tpl->processTemplate('writeContent', array(
        'writeCategory'            => $PMF_LANG['msgEntriesIn'].$name,
        'writeCategoryDescription' => $categoryDescription,
        'writeThemes'              => $records,
        'writeOneThemeBack'        => $up));
    $tpl->includeTemplate('writeContent', 'index');

} else {

    $faqsession->userTracking('show_all_categories', 0);
    $tpl->processTemplate('writeContent', array(
        'writeCategory'            => $PMF_LANG['msgFullCategories'],
        'writeCategoryDescription' => '',
        'writeThemes'              => $category->viewTree(),
        'writeOneThemeBack'        => ''));
    $tpl->includeTemplate('writeContent', 'index');
}