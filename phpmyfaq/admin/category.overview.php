<?php

/**
 * List all categories in the admin section with drag and drop support for sorting.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2013-12-30
 */

use phpMyFAQ\Category;
use phpMyFAQ\Category\Order;
use phpMyFAQ\Configuration;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Template\TwigWrapper;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\Request;
use Twig\Extension\DebugExtension;
use phpMyFAQ\Template\TranslateTwigExtension;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$request = Request::createFromGlobals();
$faqConfig = Configuration::getConfigurationInstance();

$category = new Category($faqConfig, [], false);
$category->buildCategoryTree();
$categoryInfo = $category->getAllCategories();

$categoryOrder = new Order($faqConfig);
$orderedCategories = $categoryOrder->getAllCategories();
$categoryTree = $categoryOrder->getCategoryTree($orderedCategories);

if (empty($categoryTree)) {
    // Fallback if no category order is available
    $categoryTree = $category->buildAdminCategoryTree($categoryInfo);
}

$twig = new TwigWrapper(PMF_ROOT_DIR . '/assets/templates');
$twig->addExtension(new DebugExtension());
$twig->addExtension(new TranslateTwigExtension());
$template = $twig->loadTemplate('./admin/content/category.overview.twig');

$templateVars = [
    'msgHeaderCategoryOverview' => Translation::get('ad_menu_categ_edit'),
    'msgAddCategory' => Translation::get('ad_kateg_add'),
    'msgCategoryMatrix' => Translation::get('ad_categ_show'),
    'csrfTokenInput' => Token::getInstance()->getTokenInput('category'),
    'categoryTree' => $categoryTree,
    'categoryInfo' => $categoryInfo,
    'msgCategoryRemark' => Translation::get('ad_categ_remark'),
];

echo $template->render($templateVars);
