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
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2002-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
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

$selectedCategoryId = PMF_Filter::filterInput(INPUT_GET, 'cat', FILTER_VALIDATE_INT);
$subCategoryContent = '';

if (!is_null($selectedCategoryId) && isset($category->categoryName[$selectedCategoryId])) {
    try {
        $faqsession->userTracking('show_category', $selectedCategoryId);
    } catch (PMF_Exception $e) {
        // @todo handle the exception
    }

    $categoryData = $category->getCategoryData($selectedCategoryId);
    $records = $faq->showAllRecords(
        $selectedCategoryId,
        $faqConfig->get('records.orderby'),
        $faqConfig->get('records.sortby')
    );

    if (empty($records) || $category->getChildNodes($selectedCategoryId)) {
        $subCategory = new PMF_Category($faqConfig, $current_groups, true);
        $subCategory->setUser($current_user);
        $subCategory->transform($selectedCategoryId);
        if (empty($records)) {
            $records = $subCategory->viewTree();
        }
        if (count($category->getChildNodes($selectedCategoryId))) {
            $categoryFaqsHeader = $PMF_LANG['msgSubCategories'];
            $subCategoryContent = $subCategory->viewTree();
            $tpl->parseBlock(
                'writeContent',
                'subCategories',
                [
                    'categorySubsHeader' => $categoryFaqsHeader
                ]
            );
        }
    }

    $url = sprintf(
        '%s?%saction=show&amp;cat=%d',
        PMF_Link::getSystemRelativeUri(),
        $sids,
        $categoryData->getParentId()
    );
    $oLink = new PMF_Link($url, $faqConfig);
    $oLink->itemTitle = $categoryData->getName();
    $oLink->text = $PMF_LANG['msgCategoryUp'];
    $up = $oLink->toHtmlAnchor();

    $tpl->parse(
        'writeContent',
        [
            'categoryHeader' => $PMF_LANG['msgEntriesIn'].$categoryData->getName(),
            'categoryDescription' => $categoryData->getDescription(),
            'categoryFaqsHeader' => $PMF_LANG['msgEntries'],
            'categoryContent' => $records,
            'subCategoryContent' => $subCategoryContent,
            'categoryLevelUp' => $up
        ]
    );

    $tpl->parseBlock(
        'index',
        'breadcrumb',
        [
            'breadcrumbHeadline' => $PMF_LANG['msgEntriesIn'].$categoryData->getName()
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
        [
            'categoryHeader' => $PMF_LANG['msgFullCategories'],
            'categoryDescription' => '',
            'categoryFaqsHeader' => '',
            'categoryContent' => $category->viewTree(),
            'subCategoryContent' => $subCategoryContent,
            'categoryLevelUp' => '',
        ]
    );

    $tpl->parseBlock(
        'index',
        'breadcrumb',
        [
            'breadcrumbHeadline' => $PMF_LANG['msgFullCategories']
        ]
    );
}
