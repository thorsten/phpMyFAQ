<?php

/**
 * This is the page there a user can view all glossary items.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2012-2021 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2012-09-03
 */

use phpMyFAQ\Filter;
use phpMyFAQ\Glossary;
use phpMyFAQ\Link;
use phpMyFAQ\Pagination;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

try {
    $faqSession->userTracking('glossary', 0);
} catch (Exception $e) {
    // @todo handle the exception
}

$page = Filter::filterInput(INPUT_GET, 'page', FILTER_VALIDATE_INT, 1);

$glossary = new Glossary($faqConfig);
$glossaryItems = $glossary->getAllGlossaryItems();
$numItems = count($glossaryItems);
$itemsPerPage = 10;

$baseUrl = sprintf(
    '%s?action=glossary&amp;page=%d',
    $faqConfig->getDefaultUrl(),
    $page
);

// Pagination options
$options = [
    'baseUrl' => $baseUrl,
    'total' => count($glossaryItems),
    'perPage' => $itemsPerPage,
    'pageParamName' => 'page',
];
$pagination = new Pagination($faqConfig, $options);

if (0 < $numItems) {
    $output = [];
    $visibleItems = array_slice($glossaryItems, ($page - 1) * $itemsPerPage, $itemsPerPage);

    foreach ($visibleItems as $item) {
        $output['item'][] = $item['item'];
        $output['definition'][] = $item['definition'];
        ++$i;
    }

    $template->parseBlock(
        'mainPageContent',
        'glossaryItems',
        [
            'item' => $output['item'],
            'desc' => $output['definition'],
        ]
    );
}

$template->parse(
    'mainPageContent',
    [
        'pageHeader' => $PMF_LANG['ad_menu_glossary'],
        'pagination' => $pagination->render(),
    ]
);
