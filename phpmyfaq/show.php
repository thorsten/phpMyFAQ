<?php

/**
 * Frontend for categories or list of records.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2002-2021 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2002-08-27
 */

use phpMyFAQ\Category;
use phpMyFAQ\Filter;
use phpMyFAQ\Helper\CategoryHelper;
use phpMyFAQ\Link;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$selectedCategoryId = Filter::filterInput(INPUT_GET, 'cat', FILTER_VALIDATE_INT);
$subCategoryContent = '';

if (!is_null($selectedCategoryId) && !isset($category->categoryName[$selectedCategoryId])) {
    $http->setStatus(404);
}

$categoryHelper = new CategoryHelper();

if (!is_null($selectedCategoryId) && isset($category->categoryName[$selectedCategoryId])) {
    try {
        $faqSession->userTracking('show_category', $selectedCategoryId);
    } catch (Exception $e) {
        // @todo handle the exception
    }

    $categoryData = $category->getCategoryData($selectedCategoryId);
    $records = $faq->renderRecordsByCategoryId(
        $selectedCategoryId,
        $faqConfig->get('records.orderby'),
        $faqConfig->get('records.sortby')
    );

    if (empty($records) || $category->getChildNodes((int) $selectedCategoryId)) {
        $subCategory = new Category($faqConfig, $currentGroups, true);
        $subCategory->setUser($currentUser);
        $subCategory->transform($selectedCategoryId);
        $categoryHelper
            ->setConfiguration($faqConfig)
            ->setCategory($subCategory);
        if (empty($records)) {
            $records = $categoryHelper->renderCategoryTree();
        }
        if (count($category->getChildNodes((int) $selectedCategoryId))) {
            $categoryFaqsHeader = $PMF_LANG['msgSubCategories'];
            $subCategoryContent = $categoryHelper->renderCategoryTree();
            $template->parseBlock(
                'mainPageContent',
                'subCategories',
                [
                    'categorySubsHeader' => $categoryFaqsHeader
                ]
            );
        }
    }

    $up = '';
    if ($categoryData->getParentId() !== 0) {
        $url = sprintf(
            '%s?%saction=show&amp;cat=%d',
            $faqConfig->getDefaultUrl(),
            $sids,
            $categoryData->getParentId()
        );
        $oLink = new Link($url, $faqConfig);
        $oLink->itemTitle = $category->categoryName[$categoryData->getParentId()]['name'];
        $oLink->text = $PMF_LANG['msgCategoryUp'];
        $up = $oLink->toHtmlAnchor();
    }

    if (!is_null($categoryData->getImage()) && strlen($categoryData->getImage()) > 0) {
        $template->parseBlock(
            'mainPageContent',
            'categoryImage',
            [
                'categoryImage' => $faqConfig->getDefaultUrl() . '/images/' . $categoryData->getImage(),
            ]
        );
    }

    $template->parse(
        'mainPageContent',
        [
            'categoryHeader' => $PMF_LANG['msgEntriesIn'] . $categoryData->getName(),
            'categoryDescription' => $categoryData->getDescription(),
            'categoryFaqsHeader' => $PMF_LANG['msgEntries'],
            'categoryContent' => $records,
            'subCategoryContent' => $subCategoryContent,
            'categoryLevelUp' => $up
        ]
    );
} else {
    try {
        $faqSession->userTracking('show_all_categories', 0);
    } catch (Exception $e) {
        // @todo handle the exception
    }

    $categoryHelper
        ->setConfiguration($faqConfig)
        ->setCategory($category);

    $template->parse(
        'mainPageContent',
        [
            'categoryHeader' => $PMF_LANG['msgFullCategories'],
            'categoryDescription' => '',
            'categoryFaqsHeader' => '',
            'categoryContent' => $categoryHelper->renderCategoryTree(),
            'subCategoryContent' => $subCategoryContent,
            'categoryLevelUp' => '',
        ]
    );
}
