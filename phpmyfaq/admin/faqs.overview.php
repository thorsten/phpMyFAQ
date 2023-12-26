<?php

/**
 * List of records ordered by categories.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2020-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2020-11-21
 */

use phpMyFAQ\Category;
use phpMyFAQ\Category\CategoryRelation;
use phpMyFAQ\Configuration;
use phpMyFAQ\Template\TwigWrapper;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\Request;
use Twig\Extension\DebugExtension;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$request = Request::createFromGlobals();
$faqConfig = Configuration::getConfigurationInstance();

$category = new Category($faqConfig, $currentAdminGroups, true);
$category->setUser($currentAdminUser);
$category->setGroups($currentAdminGroups);
$category->buildCategoryTree();

$categoryRelation = new CategoryRelation($faqConfig, $category);
$categoryRelation->setGroups($currentAdminGroups);

$twig = new TwigWrapper(PMF_ROOT_DIR . '/assets/templates');
$twig->addExtension(new DebugExtension());
$template = $twig->loadTemplate('./admin/content/faq.overview.twig');

$templateVars = [
    'msgHeaderFAQOverview' => Translation::get('ad_entry_aor'),
    'errorNoRecords' => Translation::get('err_noArticles'),
    'categories' => $category->getCategoryTree(),
    'numberOfRecords' => $categoryRelation->getNumberOfFaqsPerCategory(),
    'msgRecords' => Translation::get('msgEntries')

];

echo $template->render($templateVars);
