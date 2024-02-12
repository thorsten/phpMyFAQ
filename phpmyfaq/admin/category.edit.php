<?php

/**
 * Edits a category.
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
 * @since     2003-03-10
 */

use phpMyFAQ\Category;
use phpMyFAQ\Category\CategoryPermission;
use phpMyFAQ\Configuration;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Filter;
use phpMyFAQ\Helper\UserHelper;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Strings;
use phpMyFAQ\Template\TwigWrapper;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$faqConfig = Configuration::getConfigurationInstance();
$currentUser = CurrentUser::getCurrentUser($faqConfig);

$currentUserId = $currentUser->getUserId();

if ($currentUser->perm->hasPermission($currentUser->getUserId(), PermissionType::CATEGORY_EDIT->value)) {
    $categoryId = Filter::filterInput(INPUT_GET, 'cat', FILTER_VALIDATE_INT, 0);

    $category = new Category($faqConfig, [], false);
    $category->setUser($currentAdminUser);
    $category->setGroups($currentAdminGroups);

    $categoryPermission = new CategoryPermission($faqConfig);

    $userHelper = new UserHelper($currentUser);

    $categoryData = $category->getCategoryData($categoryId);
    $userPermission = $categoryPermission->get(CategoryPermission::USER, [$categoryId]);

    if ($userPermission[0] == -1) {
        $allUsers = true;
        $restrictedUsers = false;
    } else {
        $allUsers = false;
        $restrictedUsers = true;
    }

    $groupPermission = $categoryPermission->get(CategoryPermission::GROUP, [$categoryId]);
    if ($groupPermission[0] == -1) {
        $allGroups = true;
        $restrictedGroups = false;
    } else {
        $allGroups = false;
        $restrictedGroups = true;
    }

    $header = Translation::get('ad_categ_edit_1') . ' "' . Strings::htmlentities($categoryData->getName()) . '" ' .
        Translation::get('ad_categ_edit_2');

    $templateVars = [
        'header' => $header,
        'categoryId' => $categoryId,
        'categoryLanguage' => $categoryData->getLang(),
        'parentId' => $categoryData->getParentId(),
        'csrfInputToken' => Token::getInstance()->getTokenInput('update-category'),
        'categoryImage' => $categoryData->getImage(),
        'categoryNameLabel' => Translation::get('ad_categ_titel'),
        'categoryName' => $categoryData->getName(),
        'categoryDescriptionLabel' => Translation::get('ad_categ_desc'),
        'categoryDescription' => $categoryData->getDescription(),
        'categoryActiveLabel' => Translation::get('ad_user_active'),
        'categoryActive' => 1 === (int)$categoryData->getActive() ? 'checked' : '',
        'categoryShowHomeLabel' => Translation::get('ad_user_show_home'),
        'categoryShowHome' => 1 === (int)$categoryData->getShowHome() ? 'checked' : '',
        'categoryImageLabel' => Translation::get('ad_category_image'),
        'categoryImageReset' => 'Reset category image',
        'categoryOwnerLabel' => Translation::get('ad_categ_owner'),
        'categoryOwnerOptions' => $userHelper->getAllUserOptions($categoryData->getUserId()),
        'isMediumPermission' => $faqConfig->get('security.permLevel') !== 'basic',
        'categoryModeratorLabel' => Translation::get('ad_categ_moderator'),
        'allGroupsOptions' => $currentUser->perm->getAllGroupsOptions([$categoryData->getGroupId()], $currentUser),
        'categoryGroupPermissionLabel' => Translation::get('ad_entry_grouppermission'),
        'allGroups' => $allGroups ? 'checked' : '',
        'categoryGroupPermissionAllLabel' => Translation::get('ad_entry_all_groups'),
        'restrictedGroups' => $restrictedGroups ? 'checked' : '',
        'restrictedGroupsLabel' => Translation::get('ad_entry_restricted_groups'),
        'restrictedGroupsOptions' => $currentUser->perm->getAllGroupsOptions($groupPermission, $currentUser),
        'userPermissionLabel' => Translation::get('ad_entry_userpermission'),
        'allUsers' => $allUsers ? 'checked' : '',
        'allUsersLabel' => Translation::get('ad_entry_all_users'),
        'restrictedUsers' => $restrictedUsers ? 'checked' : '',
        'restrictedUsersLabel' => Translation::get('ad_entry_restricted_users'),
        'allUsersOptions' => $userHelper->getAllUserOptions($categoryData->getUserId()),
        'buttonUpdate' => Translation::get('ad_categ_update'),
    ];


    $twig = new TwigWrapper(PMF_ROOT_DIR . '/assets/templates');
    $template = $twig->loadTemplate('./admin/content/category.edit.twig');

    echo $template->render($templateVars);
} else {
    require __DIR__ . '/no-permission.php';
}
