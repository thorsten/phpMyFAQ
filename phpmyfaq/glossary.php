<?php

/**
 * This is the page there a user can view all glossary items.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2012-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2012-09-03
 */

use phpMyFAQ\Filter;
use phpMyFAQ\Glossary;
use phpMyFAQ\Pagination;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\Request;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

try {
    $faqSession->userTracking('glossary', 0);
} catch (Exception) {
    // @todo handle the exception
}

$request = Request::createFromGlobals();
$page = Filter::filterVar($request->query->get('page'), FILTER_VALIDATE_INT, 1);

$glossary = new Glossary($faqConfig);
$glossaryItems = $glossary->getAllGlossaryItems();
$numItems = is_countable($glossaryItems) ? count($glossaryItems) : 0;
$itemsPerPage = 10;

$baseUrl = sprintf(
    '%sindex.php?action=glossary&amp;page=%d',
    $faqConfig->getDefaultUrl(),
    $page
);

// Pagination options
$options = [
    'baseUrl' => $baseUrl,
    'total' => is_countable($glossaryItems) ? count($glossaryItems) : 0,
    'perPage' => $itemsPerPage,
    'pageParamName' => 'page',
];
$pagination = new Pagination($options);

if (0 < $numItems) {
    $output = [];
    $visibleItems = array_slice($glossaryItems, ($page - 1) * $itemsPerPage, $itemsPerPage);

    foreach ($visibleItems as $item) {
        $output['item'][] = $item['item'];
        $output['definition'][] = $item['definition'];
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
        'pageHeader' => Translation::get('ad_menu_glossary'),
        'pagination' => $pagination->render(),
    ]
);
