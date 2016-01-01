<?php

/**
 * Frontend for categories or list of records.
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 *
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2002-2016 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      http://www.phpmyfaq.de
 * @since     2002-08-27
 */
if (!defined('IS_VALID_PHPMYFAQ')) {
    $protocol = 'http';
    if (isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) === 'ON') {
        $protocol = 'https';
    }
    header('Location: '.$protocol.'://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

$currentCategory = PMF_Filter::filterInput(INPUT_GET, 'cat', FILTER_VALIDATE_INT);
$subCategoryContent = '';

if (!is_null($currentCategory) && isset($category->categoryName[$currentCategory])) {
    try {
        $faqsession->userTracking('show_category', $currentCategory);
    } catch (PMF_Exception $e) {
        // @todo handle the exception
    }

    $catParent = $category->categoryName[$currentCategory]['parent_id'];
    $catName = $category->categoryName[$currentCategory]['name'];
    $catDescription = $category->categoryName[$currentCategory]['description'];
    $records = $faq->showAllRecords(
        $currentCategory,
        $faqConfig->get('records.orderby'),
        $faqConfig->get('records.sortby')
    );

    if (empty($records) || $category->getChildNodes($currentCategory)) {
        $subCategory = new PMF_Category($faqConfig, $current_groups, true);
        $subCategory->setUser($current_user);
        $subCategory->transform($currentCategory);
        if (empty($records)) {
            $records = $subCategory->viewTree();
        }
        if (count($category->getChildNodes($currentCategory))) {
            $categoryFaqsHeader = $PMF_LANG['msgSubCategories'];
            $subCategoryContent = $subCategory->viewTree();
            $tpl->parseBlock(
                'writeContent',
                'subCategories',
                array(
                    'categorySubsHeader' => $categoryFaqsHeader,
                )
            );
        }
    }

    $up = '';
    if ($catParent != 0) {
        $url = sprintf(
            '%s?%saction=show&amp;cat=%d',
            PMF_Link::getSystemRelativeUri(),
            $sids,
            $catParent
        );
        $oLink = new PMF_Link($url, $faqConfig);
        $oLink->itemTitle = $category->categoryName[$catParent]['name'];
        $oLink->text = $PMF_LANG['msgCategoryUp'];
        $up = $oLink->toHtmlAnchor();
    }

    $tpl->parse(
        'writeContent',
        array(
            'categoryHeader' => $PMF_LANG['msgEntriesIn'].$catName,
            'categoryDescription' => $catDescription,
            'categoryFaqsHeader' => $PMF_LANG['msgEntries'],
            'categoryContent' => $records,
            'subCategoryContent' => $subCategoryContent,
            'categoryLevelUp' => $up,
        )
    );

    $tpl->parseBlock(
        'index',
        'breadcrumb',
        [
            'breadcrumbHeadline' => $PMF_LANG['msgEntriesIn'].$catName
        ]
    );

} else {
    try {
        $faqsession->userTracking('show_all_categories', 0);
    } catch (PMF_Exception $e) {
        // @todo handle the exception
    }

    $tpl->parse(
        'writeContent',
        array(
            'categoryHeader' => $PMF_LANG['msgFullCategories'],
            'categoryDescription' => '',
            'categoryFaqsHeader' => '',
            'categoryContent' => $category->viewTree(),
            'subCategoryContent' => $subCategoryContent,
            'categoryLevelUp' => '',
        )
    );

    $tpl->parseBlock(
        'index',
        'breadcrumb',
        [
            'breadcrumbHeadline' => $PMF_LANG['msgFullCategories']
        ]
    );
}
