<?php

/**
 * Frontend for categories or list of records.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2002-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2002-08-27
 */

use phpMyFAQ\Category;
use phpMyFAQ\Filter;
use phpMyFAQ\Helper\CategoryHelper;
use phpMyFAQ\Language\Plurals;
use phpMyFAQ\Link;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$request = Request::createFromGlobals();
$selectedCategoryId = Filter::filterVar($request->query->get('cat'), FILTER_VALIDATE_INT);
$subCategoryContent = '';

if ($selectedCategoryId === 0) {
    $selectedCategoryId = null;
}

if (!is_null($selectedCategoryId) && !isset($category->categoryName[$selectedCategoryId])) {
    $response = new Response();
    $response->setStatusCode(Response::HTTP_NOT_FOUND);
}

$categoryHelper = new CategoryHelper();
$categoryHelper->setPlurals(new Plurals());

if (!is_null($selectedCategoryId) && isset($category->categoryName[$selectedCategoryId])) {
    try {
        $faqSession->userTracking('show_category', $selectedCategoryId);
    } catch (Exception) {
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
        if (is_countable($category->getChildNodes((int) $selectedCategoryId)) ? count($category->getChildNodes((int) $selectedCategoryId)) : 0) {
            $categoryFaqsHeader = Translation::get('msgSubCategories');
            $subCategoryContent = $categoryHelper->renderCategoryTree($selectedCategoryId);
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
    if ($categoryData->getId() !== 0) {
        $url = sprintf(
            '%sindex.php?%saction=show&amp;cat=%d',
            $faqConfig->getDefaultUrl(),
            $sids,
            $categoryData->getParentId()
        );
        $text = $category->categoryName[$categoryData->getParentId()]['name'] ?? Translation::get('msgCategoryUp');

        $link = new Link($url, $faqConfig);
        $link->itemTitle = $link->text = $text;
        $link->tooltip = Translation::get('msgCategoryUp');

        $up = sprintf('<i class="fa fa-level-up" aria-hidden="true"></i> %s', $link->toHtmlAnchor());
    }

    if (!is_null($categoryData->getImage()) && strlen((string) $categoryData->getImage()) > 0) {
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
            'categoryHeader' => Translation::get('msgEntriesIn') . $categoryData->getName(),
            'categoryFaqsHeader' => $categoryData->getName(),
            'categoryDescription' => Strings::htmlentities($categoryData->getDescription()),
            'categorySubsHeader' => Translation::get('msgSubCategories'),
            'categoryContent' => $records,
            'subCategoryContent' => $subCategoryContent,
            'categoryLevelUp' => $up
        ]
    );
} else {
    $selectedCategoryId = 0;
    try {
        $faqSession->userTracking('show_all_categories', 0);
    } catch (Exception) {
        // @todo handle the exception
    }

    $categoryHelper
        ->setConfiguration($faqConfig)
        ->setCategory($category);

    $template->parse(
        'mainPageContent',
        [
            'categoryHeader' => Translation::get('msgFullCategories'),
            'categoryFaqsHeader' => Translation::get('msgShowAllCategories'),
            'categoryDescription' => Translation::get('msgCategoryDescription'),
            'categorySubsHeader' => Translation::get('msgSubCategories'),
            'categoryContent' => $categoryHelper->renderCategoryTree($selectedCategoryId),
            'subCategoryContent' => Translation::get('msgSubCategoryContent'),
            'categoryLevelUp' => '',
        ]
    );
}
