<?php
/**
 * Frontend for categories or list of records
 *
 * PHP Version 5.3
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
 * @since     2002-08-27
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
    $records             = $faq->showAllRecords(
        $currentCategory,
        $faqConfig->get('records.orderby'),
        $faqConfig->get('records.sortby')
    );
    
    if (!$records) {
        $subCategory = new PMF_Category($faqConfig, $current_groups, true);
        $subCategory->setUser($current_user);
        $subCategory->transform($currentCategory);
        $records = $subCategory->viewTree();
    }

    $up = '';
    if ($parent != 0) {
        $url = sprintf(
            '%s?%saction=show&amp;cat=%d',
            PMF_Link::getSystemRelativeUri(),
            $sids,
            $parent
        );
        $oLink            = new PMF_Link($url, $faqConfig);
        $oLink->itemTitle = $category->categoryName[$parent]['name'];
        $oLink->text      = $PMF_LANG['msgCategoryUp'];
        $up               = $oLink->toHtmlAnchor();
    }

    $tpl->parse(
        'writeContent',
        array(
            'writeCategory'            => $PMF_LANG['msgEntriesIn'].$name,
            'writeCategoryDescription' => $categoryDescription,
            'writeThemes'              => $records,
            'writeOneThemeBack'        => $up
        )
    );
    $tpl->merge('writeContent', 'index');

} else {

    $faqsession->userTracking('show_all_categories', 0);
    $tpl->parse(
        'writeContent',
        array(
            'writeCategory'            => $PMF_LANG['msgFullCategories'],
            'writeCategoryDescription' => '',
            'writeThemes'              => $category->viewTree(),
            'writeOneThemeBack'        => ''
        )
    );
    $tpl->merge('writeContent', 'index');
}