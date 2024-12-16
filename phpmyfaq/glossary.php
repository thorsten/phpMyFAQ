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
 * @copyright 2012-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2012-09-03
 */

use phpMyFAQ\Filter;
use phpMyFAQ\Glossary;
use phpMyFAQ\Pagination;
use phpMyFAQ\Template\TwigWrapper;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\Request;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$faqConfig = $container->get('phpmyfaq.configuration');
$user = $container->get('phpmyfaq.user.current_user');

$faqSession = $container->get('phpmyfaq.session');
$faqSession->setCurrentUser($user);
$faqSession->userTracking('glossary', 0);

$request = Request::createFromGlobals();
$page = Filter::filterVar($request->query->get('page'), FILTER_VALIDATE_INT, 1);

$glossary = new Glossary($faqConfig);
$glossaryItems = $glossary->fetchAll();
$numItems = is_countable($glossaryItems) ? count($glossaryItems) : 0;
$itemsPerPage = 8;

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

$twig = new TwigWrapper(PMF_ROOT_DIR . '/assets/templates/');
$twigTemplate = $twig->loadTemplate('./glossary.twig');

// Twig template variables
$templateVars = [
    ... $templateVars,
    'title' => sprintf('%s - %s', Translation::get('ad_menu_glossary'), $faqConfig->getTitle()),
    'metaDescription' => sprintf(Translation::get('msgGlossaryMetaDesc'), $faqConfig->getTitle()),
    'pageHeader' => Translation::get('ad_menu_glossary'),
    'glossaryItems' => array_slice($glossaryItems, ($page - 1) * $itemsPerPage, $itemsPerPage),
    'pagination' => $pagination->render(),
];

return $templateVars;
