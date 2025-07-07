<?php

/**
 * Displays the user management frontend.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Lars Tiedemann <php@larstiedemann.de>
 * @author    Uwe Pries <uwe.pries@digartis.de>
 * @author    Sarah Hermann <sayh@gmx.de>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2005-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2005-12-15
 */

use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Filter;
use phpMyFAQ\Pagination;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Template\Extensions\PermissionTranslationTwigExtension;
use phpMyFAQ\Template\TwigWrapper;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

if (
    !$user->perm->hasPermission($user->getUserId(), PermissionType::USER_EDIT->value) ||
    !$user->perm->hasPermission($user->getUserId(), PermissionType::USER_DELETE->value) ||
    !$user->perm->hasPermission($user->getUserId(), PermissionType::USER_ADD->value)
) {
    require __DIR__ . '/no-permission.php';
    exit();
}

$templateVars = [];

$userId = Filter::filterInput(INPUT_GET, 'user_id', FILTER_VALIDATE_INT);

// set some parameters
$selectSize = 10;
$defaultUserAction = 'list';
$userActionList = [
    'list',
    'listallusers'
];

// what shall we do?
// actions defined by url: user_action=
$userAction = Filter::filterInput(INPUT_GET, 'user_action', FILTER_SANITIZE_SPECIAL_CHARS, $defaultUserAction);
$currentUser = new CurrentUser($faqConfig);

// show a list of all users
if ($userAction == 'listallusers') {
    if (!$user->perm->hasPermission($user->getUserId(), PermissionType::USER_EDIT->value)) {
        require __DIR__ . '/no-permission.php';
        exit();
    }
    $allUsers = $user->getAllUsers(false);
    $numUsers = is_countable($allUsers) ? count($allUsers) : 0;
    $page = Filter::filterInput(INPUT_GET, 'page', FILTER_VALIDATE_INT, 0);
    $perPage = 10;
    $numPages = ceil($numUsers / $perPage);
    $lastPage = $page * $perPage;
    $firstPage = $lastPage - $perPage;

    $baseUrl = sprintf(
        '%sadmin/?action=user&user_action=listallusers&page=%d',
        $faqConfig->getDefaultUrl(),
        $page
    );

    // Pagination options
    $options = [
        'baseUrl' => $baseUrl,
        'total' => $numUsers,
        'perPage' => $perPage,
        'pageParamName' => 'page',
    ];
    $pagination = new Pagination($options);

    $counter = $displayedCounter = 0;
    $users = [];
    foreach ($allUsers as $listedUserId) {
        $user->getUserById($listedUserId, true);
        $tempUser = [];

        if ($displayedCounter >= $perPage) {
            continue;
        }
        ++$counter;
        if ($counter <= $firstPage) {
            continue;
        }
        ++$displayedCounter;

        $tempUser = [
            'display_name' => $user->getUserData('display_name'),
            'id' => $user->getUserId(),
            'email' => $user->getUserData('email'),
            'status' => $user->getStatus(),
            'isSuperAdmin' => $user->isSuperAdmin(),
            'isVisible' => $user->getUserData('is_visible'),
            'login' => $user->getLogin()
        ];

        $users[] = $tempUser;
    }

    $user = CurrentUser::getCurrentUser($faqConfig);

    $templateVars = [
        ...$templateVars,
        'perPage' => $perPage,
        'numUsers' => $numUsers,
        'pagination' => $pagination->render(),
        'users' => $users,
        'userIsSuperAdmin' => $user->isSuperAdmin()
    ];
}

$templateVars = [
    ...$templateVars,
    'userAction' => $userAction,
    'ad_user' => Translation::get('ad_user'),
    'permissionAddUser' => $currentUser->perm->hasPermission($user->getUserId(), PermissionType::USER_ADD->value),
    'ad_user_add' => Translation::get('ad_user_add'),
    'permissionEditUser' => $currentUser->perm->hasPermission($user->getUserId(), PermissionType::USER_EDIT->value),
    'list_all_users' => Translation::get('list_all_users'),
    'userId' => $userId,
    'msgSearch' => Translation::get('msgSearch'),
    'ad_auth_user' => Translation::get('ad_auth_user'),
    'ad_user_profou' => Translation::get('ad_user_profou'),
    'csrfToken_updateUserData' => Token::getInstance()->getTokenString('update-user-data'),
    'msgAuthenticationSource' => Translation::get('msgAuthenticationSource'),
    'ad_user_status' => Translation::get('ad_user_status'),
    'ad_user_active' => Translation::get('ad_user_active'),
    'ad_user_blocked' => Translation::get('ad_user_blocked'),
    'ad_user_protected' => Translation::get('ad_user_protected'),
    'msgRealname' => Translation::get('msgRealname'),
    'msgEmail' => Translation::get('msgEmail'),
    'ad_user_is_superadmin' => Translation::get('ad_user_is_superadmin'),
    'ad_user_overwrite_passwd' => Translation::get('ad_user_overwrite_passwd'),
    'ad_user_overwrite_twofactor' => Translation::get('ad_user_overwrite_twofactor'),
    'ad_user_delete' => Translation::get('ad_user_delete'),
    'ad_gen_save' => Translation::get('ad_gen_save'),
    'csrfToken_updateUserRights' => Token::getInstance()->getTokenString('update-user-rights'),
    'ad_user_rights' => Translation::get('ad_user_rights'),
    'ad_user_checkall' => Translation::get('ad_user_checkall'),
    'ad_user_uncheckall' => Translation::get('ad_user_uncheckall'),
    'userRights' => $user->perm->getAllRightsData(),
    'msgExportUsersAsCSV' => Translation::get('msgExportUsersAsCSV'),
    'msgNewContentName' => Translation::get('msgNewContentName'),
    'msgNewContentMail' => Translation::get('msgNewContentMail'),
    'ad_user_is_visible' => Translation::get('ad_user_is_visible'),
    'ad_user_edit' => Translation::get('ad_user_edit'),
    'csrfToken_activateUser' => Token::getInstance()->getTokenString('activate-user'),
    'ad_news_set_active' => Translation::get('ad_news_set_active'),
    'permissionDeleteUser' =>
        $currentUser->perm->hasPermission($user->getUserId(), PermissionType::USER_DELETE->value),
    'csrfToken_deleteUser' => Token::getInstance()->getTokenString('delete-user'),
    'ad_adus_adduser' => Translation::get('ad_adus_adduser'),
    'csrfToken_addUser' => Token::getInstance()->getTokenString('add-user'),
    'ad_adus_name' => Translation::get('ad_adus_name'),
    'ad_add_user_change_password' => Translation::get('ad_add_user_change_password'),
    'ad_adus_password' => Translation::get('ad_adus_password'),
    'ad_passwd_con' => Translation::get('ad_passwd_con'),
    'ad_gen_cancel' => Translation::get('ad_gen_cancel'),
    'ad_menu_passwd' => Translation::get('ad_menu_passwd'),
    'csrfToken_overwritePassword' => Token::getInstance()->getTokenString('overwrite-password'),
    'ad_passwd_new' => Translation::get('ad_passwd_new'),
    'msgWarning' => Translation::get('msgWarning'),
    'ad_gen_yes' => Translation::get('ad_gen_yes'),
    'ad_gen_no' => Translation::get('ad_gen_no'),
    'ad_user_deleteUser' => Translation::get('ad_user_deleteUser'),
    'msgUserList' => Translation::get('msgUserList')
];

$twig = new TwigWrapper(PMF_ROOT_DIR . '/assets/templates');
$twig->addExtension(new PermissionTranslationTwigExtension());
$template = $twig->loadTemplate('@admin/user/users.twig');

echo $template->render($templateVars);
