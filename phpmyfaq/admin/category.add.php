<?php

/**
 * Adds a new (sub-)category, a new sub-category inherits the permissions from
 * its parent category.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2003-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2003-12-20
 */

use phpMyFAQ\Category\Permission;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Filter;
use phpMyFAQ\Helper\UserHelper;
use phpMyFAQ\Language\LanguageCodes;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;
use phpMyFAQ\Template\TwigWrapper;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$faqConfig = $container->get('phpmyfaq.configuration');
$currentUser = $container->get('phpmyfaq.user.current_user');

if ($currentUser->perm->hasPermission($currentUser->getUserId(), PermissionType::CATEGORY_ADD->value)) {
    $category = $container->get('phpmyfaq.admin.category');
    $category->setUser($currentUser->getUserId());
    $category->setGroups($currentAdminGroups);
    $category->setLanguage($faqConfig->getLanguage()->getLanguage());
    $category->loadCategories();

    $categoryPermission = $container->get('phpmyfaq.category.permission');
    $userHelper = new UserHelper($currentUser);

    $parentId = Filter::filterInput(INPUT_GET, 'cat', FILTER_VALIDATE_INT, 0);

    $templateVars = [
        'csrfTokenInput' => Token::getInstance()->getTokenInput('save-category'),
        'faqLangCode' => $faqLangCode,
        'parentId' => $parentId,
        'ad_categ_new' => Translation::get('ad_categ_new'),
        'msgCategoryMatrix' => Translation::get('msgCategoryMatrix'),
        'userOptions' => $userHelper->getAllUserOptions(),
        'ad_categ_title' => Translation::get('ad_categ_titel'),
        'ad_categ_owner' => Translation::get('ad_categ_owner'),
        'ad_categ_desc' => Translation::get('ad_categ_desc'),
        'ad_category_image' => Translation::get('ad_category_image'),
        'ad_user_active' => Translation::get('ad_user_active'),
        'ad_user_show_home' => Translation::get('ad_user_show_home'),
        'permLevel' => $faqConfig->get('security.permLevel'),
        'ad_entry_all_users' => Translation::get('ad_entry_all_users'),
        'ad_entry_restricted_users' => Translation::get('ad_entry_restricted_users'),
        'ad_entry_userpermission' => Translation::get('ad_entry_userpermission'),
        'ad_categ_add' => Translation::get('ad_categ_add'),
        'ad_entry_grouppermission' => Translation::get('ad_entry_grouppermission'),
        'ad_entry_all_groups' => Translation::get('ad_entry_all_groups'),
        'ad_entry_restricted_groups' => Translation::get('ad_entry_restricted_groups'),
        'msgSerpTitle' => Translation::get('msgSerpTitle'),
        'msgSerpDescription' => Translation::get('msgSerpDescription'),
        'restricted_groups' => ($faqConfig->get('security.permLevel') === 'medium') ?
            $currentUser->perm->getAllGroupsOptions([], $user) : '',
    ];

    if ($parentId > 0) {
        $userAllowed = $categoryPermission->get(Permission::USER, [$parentId]);
        $groupsAllowed = $categoryPermission->get(Permission::GROUP, [$parentId]);
        $templateVars = [
            ...$templateVars,
            'categoryNameLangCode' => LanguageCodes::get($category->categoryName[$parentId]['lang']),
            'userAllowed' => $categoryPermission->get(Permission::USER, [$parentId])[0],
            'groupsAllowed' => $categoryPermission->get(Permission::GROUP, [$parentId]),
            'categoryName' => $category->categoryName[$parentId]['name'],
            'msgMainCategory' => Translation::get('msgMainCategory'),
        ];
    }

    if ($faqConfig->get('security.permLevel') !== 'basic') {
        $templateVars = [
            ...$templateVars,
            'groupsOptions' => $currentUser->perm->getAllGroupsOptions([], $currentUser),
            'ad_categ_moderator' => Translation::get('ad_categ_moderator')
        ];
    }

    $twig = new TwigWrapper(PMF_ROOT_DIR . '/assets/templates');
    $template = $twig->loadTemplate('@admin/content/category.add.twig');

    echo $template->render($templateVars);
} else {
    require __DIR__ . '/no-permission.php';
}
