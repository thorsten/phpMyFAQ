<?php

/**
 * JSON, and PDF export
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @copyright 2003-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2003-04-17
 */

use phpMyFAQ\Category;
use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Helper\CategoryHelper;
use phpMyFAQ\Template\TwigWrapper;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use Symfony\Component\HttpFoundation\HeaderUtils;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$faqConfig = Configuration::getConfigurationInstance();
$user = CurrentUser::getCurrentUser($faqConfig);
[$currentAdminUser, $currentAdminGroups] = CurrentUser::getCurrentUserGroupId($user);

$twig = new TwigWrapper(PMF_ROOT_DIR . '/assets/templates');
$template = $twig->loadTemplate('@admin/import-export/export.twig');

if ($user->perm->hasPermission($user->getUserId(), PermissionType::EXPORT->value)) {
    $category = new Category($faqConfig, [], false);
    $category->setUser($currentAdminUser);
    $category->setGroups($currentAdminGroups);
    $category->buildCategoryTree();

    $categoryHelper = new CategoryHelper();
    $categoryHelper->setCategory($category);
    $templateVars = [
        'adminHeaderExport' => Translation::get('ad_menu_export'),
        'hasNoFaqs' => Database::checkOnEmptyTable('faqdata'),
        'errorMessageNoFaqs' => Translation::get('msgErrorNoRecords'),
        'hasCategories' => !Database::checkOnEmptyTable('faqcategories'),
        'headerCategories' => Translation::get('ad_export_which_cat'),
        'msgCategory' => Translation::get('ad_entry_category'),
        'msgAllCategories' => Translation::get('msgShowAllCategories'),
        'categoryOptions' => $categoryHelper->renderOptions(0),
        'msgWithSubCategories' => Translation::get('ad_export_cat_downwards'),
        'headerExportType' => Translation::get('ad_export_type'),
        'msgChooseExportType' => Translation::get('ad_export_type_choose'),
        'msgViewType' => Translation::get('ad_export_download_view'),
        'msgDownloadType' => HeaderUtils::DISPOSITION_ATTACHMENT,
        'msgDownload' => Translation::get('ad_export_download'),
        'msgInlineType' => HeaderUtils::DISPOSITION_INLINE,
        'msgInline' => Translation::get('ad_export_view'),
        'buttonReset' => Translation::get('ad_config_reset'),
        'buttonExport' => Translation::get('ad_menu_export'),
    ];

    echo $template->render($templateVars);
} else {
    require __DIR__ . '/no-permission.php';
}

