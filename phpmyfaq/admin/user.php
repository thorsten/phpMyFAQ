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
 * @copyright 2005-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2005-12-15
 */

use phpMyFAQ\Category;
use phpMyFAQ\Component\Alert;
use phpMyFAQ\Filter;
use phpMyFAQ\Pagination;
use phpMyFAQ\Permission;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use phpMyFAQ\User;
use phpMyFAQ\User\CurrentUser;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

if (
    $user->perm->hasPermission($user->getUserId(), 'edit_user') || $user->perm->hasPermission(
        $user->getUserId(),
        'delete_user'
    ) || $user->perm->hasPermission($user->getUserId(), 'add_user')
) {
    $userId = Filter::filterInput(INPUT_GET, 'user_id', FILTER_VALIDATE_INT);

    // set some parameters
    $selectSize = 10;
    $defaultUserAction = 'list';
    $defaultUserStatus = 'active';
    $userActionList = [
        'update_rights',
        'update_data',
        'delete_confirm',
        'delete',
        'addsave',
        'list',
        'listallusers'
    ];

    // what shall we do?
    // actions defined by url: user_action=
    $userAction = Filter::filterInput(INPUT_GET, 'user_action', FILTER_SANITIZE_SPECIAL_CHARS, $defaultUserAction);

    $currentUser = new CurrentUser($faqConfig);

    // actions defined by submit button
    if (isset($_POST['user_action_deleteConfirm'])) {
        $userAction = 'delete_confirm';
    }
    if (isset($_POST['cancel'])) {
        $userAction = $defaultUserAction;
    }

    // update user rights
    if ($userAction == 'update_rights' && $user->perm->hasPermission($user->getUserId(), 'edit_user')) {
        $message = '';
        $userAction = $defaultUserAction;
        $userId = Filter::filterInput(INPUT_POST, 'user_id', FILTER_VALIDATE_INT, 0);
        $csrfOkay = true;
        $csrfToken = Filter::filterInput(INPUT_POST, 'pmf-csrf-token', FILTER_SANITIZE_SPECIAL_CHARS);
        if (!Token::getInstance()->verifyToken('update-user-rights', $csrfToken)) {
            $csrfOkay = false;
        }

        if (0 === (int)$userId || !$csrfOkay) {
            $message .= Alert::danger('ad_user_error_noId');
        } else {
            $user = new User($faqConfig);
            $perm = $user->perm;
            // @todo: Add Filter::filterInput[]
            $userRights = $_POST['user_rights'] ?? [];
            if (!$perm->refuseAllUserRights($userId)) {
                $message .= Alert::danger('ad_msg_mysqlerr');
            }
            foreach ($userRights as $rightId) {
                $perm->grantUserRight($userId, $rightId);
            }
            $idUser = $user->getUserById($userId, true);
            $message .= sprintf(
                '<p class="alert alert-success">%s <strong>%s</strong> %s</p>',
                Translation::get('ad_msg_savedsuc_1'),
                Strings::htmlentities($user->getLogin(), ENT_QUOTES),
                Translation::get('ad_msg_savedsuc_2')
            );
            $user = new CurrentUser($faqConfig);
        }
    }

    // update user data
    if ($userAction == 'update_data' && $user->perm->hasPermission($user->getUserId(), 'edit_user')) {
        $message = '';
        $userAction = $defaultUserAction;
        $userId = Filter::filterInput(INPUT_POST, 'user_id', FILTER_VALIDATE_INT, 0);
        if ($userId === 0) {
            $message .= Alert::danger('ad_user_error_noId');
        } else {
            $userData = [];
            $userData['display_name'] = Filter::filterInput(INPUT_POST, 'display_name', FILTER_SANITIZE_SPECIAL_CHARS);
            $userData['email'] = Filter::filterInput(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
            $userData['last_modified'] = Filter::filterInput(INPUT_POST, 'last_modified', FILTER_SANITIZE_SPECIAL_CHARS);
            $userStatus = Filter::filterInput(INPUT_POST, 'user_status', FILTER_SANITIZE_SPECIAL_CHARS, $defaultUserStatus);
            $isSuperAdmin = Filter::filterInput(INPUT_POST, 'is_superadmin', FILTER_SANITIZE_SPECIAL_CHARS);
            $isSuperAdmin = $isSuperAdmin === 'on';
            $deleteTwofactor = Filter::filterInput(INPUT_POST, 'overwrite_twofactor', FILTER_SANITIZE_SPECIAL_CHARS);
            $deleteTwofactor = $deleteTwofactor === 'on';

            $user = new User($faqConfig);
            $user->getUserById($userId, true);

            $stats = $user->getStatus();

            // reset two-factor authentication if required
            if ($deleteTwofactor) {
                $user->setUserData(['secret' => '', 'twofactor_enabled' => 0]);
            }

            // set new password and sent email if a user is switched to active
            if ($stats == 'blocked' && $userStatus == 'active') {
                if (!$user->activateUser()) {
                    $userStatus = 'invalid_status';
                }
            }

            // Set super-admin flag
            $user->setSuperAdmin($isSuperAdmin);

            if (
                !$user->userdata->set(array_keys($userData), array_values($userData)) || !$user->setStatus(
                    $userStatus
                )
            ) {
                $message .= Alert::danger('ad_msg_mysqlerr');
            } else {
                $message .= sprintf(
                    '<p class="alert alert-success">%s <strong>%s</strong> %s</p>',
                    Translation::get('ad_msg_savedsuc_1'),
                    Strings::htmlentities($user->getLogin(), ENT_QUOTES),
                    Translation::get('ad_msg_savedsuc_2')
                );
            }
        }
    }

    // delete user confirmation
    if ($userAction == 'delete_confirm' && $user->perm->hasPermission($user->getUserId(), 'delete_user')) {
        $message = '';
        $user = new CurrentUser($faqConfig);

        $userId = Filter::filterInput(INPUT_GET, 'user_delete_id', FILTER_VALIDATE_INT, 0);
        if ($userId == 0) {
            $message .= Alert::danger('ad_user_error_noId');
            $userAction = $defaultUserAction;
        } else {
            $user->getUserById($userId, true);
            // account is protected
            if ($user->getStatus() == 'protected' || $userId == 1) {
                $message .= Alert::danger('ad_user_error_protectedAccount');
                $userAction = $defaultUserAction;
            } else {
                ?>

                <div
                    class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i aria-hidden="true" class="fa fa-user"></i>
                        <?= Translation::get('ad_user_deleteUser') ?> <?= Strings::htmlentities($user->getLogin(), ENT_QUOTES) ?>
                    </h1>
                </div>

                <p class="alert alert-danger">
                    <?= Translation::get('ad_user_del_3') . ' ' . Translation::get(
                        'ad_user_del_1'
                    ) . ' ' . Translation::get('ad_user_del_2') ?>
                </p>
                <form action="?action=user&amp;user_action=delete" method="post" accept-charset="utf-8">
                    <input type="hidden" name="user_id" value="<?= $userId ?>">
                    <?= Token::getInstance()->getTokenInput('delete-user') ?>
                    <p class="text-center">
                        <button class="btn btn-danger" type="submit">
                            <?= Translation::get('ad_gen_yes') ?>
                        </button>
                        <a class="btn btn-info" href="?action=user">
                            <?= Translation::get('ad_gen_no') ?>
                        </a>
                    </p>
                </form>
                <?php
            }
        }
    }

    // delete user
    if ($userAction == 'delete' && $user->perm->hasPermission($user->getUserId(), 'delete_user')) {
        $message = '';
        $user = new User($faqConfig);
        $userId = Filter::filterInput(INPUT_POST, 'user_id', FILTER_VALIDATE_INT, 0);
        $csrfOkay = true;
        $csrfToken = Filter::filterInput(INPUT_POST, 'csrf', FILTER_SANITIZE_SPECIAL_CHARS);
        $userAction = $defaultUserAction;

        if (!Token::getInstance()->verifyToken('user', $csrfToken)) {
            $csrfOkay = false;
        }
        $userAction = $defaultUserAction;
        if (0 === (int)$userId || !$csrfOkay) {
            $message .= Alert::danger('ad_user_error_noId');
        } else {
            if (!$user->getUserById($userId, true)) {
                $message .= Alert::danger('ad_user_error_noId');
            }
            if (!$user->deleteUser()) {
                $message .= Alert::danger('ad_user_error_delete');
            } else {
                // Move the categories ownership to admin (id == 1)
                $oCat = new Category($faqConfig, [], false);
                $oCat->setUser($currentAdminUser);
                $oCat->setGroups($currentAdminGroups);
                $oCat->moveOwnership((int)$userId, 1);

                // Remove the user from groups
                if ('basic' !== $faqConfig->get('security.permLevel')) {
                    $oPerm = Permission::selectPerm('medium', $faqConfig);
                    $oPerm->removeFromAllGroups($userId);
                }

                $message .= Alert::success('ad_user_deleted');
            }
            $userError = $user->error();
            if ($userError != '') {
                $message .= sprintf('<p class="alert alert-danger">%s</p>', $userError);
            }
        }
    }

    if (!isset($message)) {
        $message = '';
    }

    // show list of users
    if ($userAction === 'list') { ?>
        <div
            class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
            <h1 class="h2">
                <i aria-hidden="true" class="fa fa-user"></i>
                <?= Translation::get('ad_user') ?>
            </h1>
            <div class="btn-toolbar mb-2 mb-md-0">
                <div class="btn-group mr-2">
                    <?php
                    if ($currentUser->perm->hasPermission($user->getUserId(), 'add_user')) : ?>
                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal"
                                data-bs-target="#addUserModal">
                            <i class="fa fa-user-plus" aria-label="true"></i> <?= Translation::get('ad_user_add') ?>
                        </button>
                        <?php
                    endif ?>
                    <?php
                    if ($currentUser->perm->hasPermission($user->getUserId(), 'edit_user')) : ?>
                        <a class="btn btn-sm btn-secondary" href="?action=user&amp;user_action=listallusers">
                            <i class="fa fa-users" aria-label="true"></i> <?= Translation::get('list_all_users') ?>
                        </a>
                        <?php
                    endif ?>
                </div>
            </div>
        </div>

        <div id="pmf-user-message"><?= $message ?></div>

        <div class="row mb-2">
            <div class="col-lg-4">
                <form name="user_select" id="user_select" action="?action=user&amp;user_action=delete_confirm"
                      method="post" role="form" class="form_inline">
                    <input type="hidden" id="current_user_id" value="<?= $userId ?>">
                    <div class="card mb-4">
                        <h5 class="card-header py-3">
                            <i aria-hidden="true" class="fa fa-search"></i> <?= Translation::get('msgSearch') ?>
                        </h5>
                        <div class="card-body">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="pmf-user-list-autocomplete" aria-controls=""
                                       name="user_list_search" placeholder="<?= Translation::get('ad_auth_user') ?>"
                                       spellcheck="false" autocomplete="off" autocapitalize="off" maxlength="2048">
                                <label for="pmf-user-list-autocomplete"><?= Translation::get('ad_auth_user') ?></label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="col-lg-4">
                <div class="card mb-4">
                    <h5 class="card-header py-3" id="user_data_legend">
                        <i aria-hidden="true" class="fa fa-user"></i> <?= Translation::get('ad_user_profou') ?>
                    </h5>
                    <form action="?action=user&amp;user_action=update_data" method="post">
                        <div class="card-body">
                            <input type="hidden" id="last_modified" name="last_modified" value="">
                            <input id="update_user_id" type="hidden" name="user_id" value="0">
                            <?= Token::getInstance()->getTokenInput('update-user-data') ?>

                            <div class="row mb-2">
                                <label for="auth_source" class="col-lg-4 col-form-label">
                                    <?= Translation::get('msgAuthenticationSource') ?>
                                </label>
                                <div class="col-lg-8">
                                    <input id="auth_source" class="form-control-plaintext" type="text" value=""
                                           readonly>
                                </div>
                            </div>

                            <div class="row mb-2">
                                <label for="user_status" class="col-lg-4 col-form-label">
                                    <?= Translation::get('ad_user_status') ?>
                                </label>
                                <div class="col-lg-8">
                                    <select id="user_status" class="form-select" name="user_status" disabled>
                                        <option value="active"><?= Translation::get('ad_user_active') ?></option>
                                        <option value="blocked"><?= Translation::get('ad_user_blocked') ?></option>
                                        <option value="protected"><?= Translation::get('ad_user_protected') ?></option>
                                    </select>
                                </div>
                            </div>

                            <div class="row mb-2">
                                <label class="col-lg-4 col-form-label" for="display_name">
                                    <?= Translation::get('ad_user_realname') ?>
                                </label>
                                <div class="col-lg-8">
                                    <input type="text" id="display_name" name="display_name" value=""
                                           class="form-control" required disabled>
                                </div>
                            </div>

                            <div class="row mb-2">
                                <label class="col-lg-4 col-form-label" for="email">
                                    <?= Translation::get('ad_entry_email') ?>
                                </label>
                                <div class="col-lg-8">
                                    <input type="email" id="email" name="email" value="" class="form-control" required
                                           disabled>
                                </div>
                            </div>

                            <div class="row mb-2">
                                <div class="offset-lg-4 col-lg-8">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="is_superadmin"
                                               name="is_superadmin" disabled>
                                        <label class="form-check-label" for="is_superadmin">
                                            <?= Translation::get('ad_user_is_superadmin') ?>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="row mb-2">
                                <div class="offset-lg-4 col-lg-8">
                                    <a class="btn btn-danger pmf-admin-overwrite-password" data-bs-toggle="modal"
                                       href="#pmf-modal-user-password-overwrite">
                                        <?= Translation::get('ad_user_overwrite_passwd') ?>
                                    </a>
                                </div>
                            </div>

                            <div class="row mb-2">
                                <div class="offset-lg-4 col-lg-8">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="overwrite_twofactor" name="overwrite_twofactor">
                                        <label class="form-check-label" for="overwrite_twofactor">
                                            <?= Translation::get('ad_user_overwrite_twofactor') ?>
                                        </label>
                                    </div>
                                </div>
                            </div>

                        </div>
                        <div class="card-footer text-end">
                            <button class="btn btn-success" type="submit">
                                <?= Translation::get('ad_gen_save') ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-lg-4" id="userRights">
                <form id="rightsForm" action="?action=user&amp;user_action=update_rights" method="post"
                      accept-charset="utf-8">
                    <input type="hidden" name="user_id" id="rights_user_id" value="0">
                    <?= Token::getInstance()->getTokenInput('update-user-rights') ?>

                    <div class="card mb-4">
                        <h5 class="card-header py-3" id="user_rights_legend">
                            <i aria-hidden="true" class="fa fa-lock"></i> <?= Translation::get('ad_user_rights') ?>
                        </h5>
                        <div class="card-body">
                            <div class="text-center mb-3">
                                <button type="button" class="btn btn-primary btn-sm" id="checkAll">
                                    <?= Translation::get('ad_user_checkall') ?>
                                    /
                                    <?= Translation::get('ad_user_uncheckall') ?>
                                </button>
                            </div>
                            <?php
                            foreach ($user->perm->getAllRightsData() as $right) : ?>
                                <div class="form-check">
                                    <input id="user_right_<?= $right['right_id'] ?>" type="checkbox"
                                           name="user_rights[]" value="<?= $right['right_id'] ?>"
                                           class="form-check-input permission">
                                    <label class="form-check-label" for="user_right_<?= $right['right_id'] ?>">
                                        <?php
                                        try {
                                            echo Translation::get('rightsLanguage::' . $right['name']);
                                        } catch (ErrorException) {
                                            echo $right['description'];
                                        }
                                        ?>
                                    </label>
                                </div>
                                <?php
                            endforeach; ?>
                        </div>
                        <div class="card-footer">
                            <div class="card-button text-end">
                                <button class="btn btn-success" type="submit">
                                    <?= Translation::get('ad_gen_save') ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <?php
    }

    // show a list of all users
    if ($userAction == 'listallusers' && $user->perm->hasPermission($user->getUserId(), 'edit_user')) {
        $allUsers = $user->getAllUsers(false);
        $numUsers = is_countable($allUsers) ? count($allUsers) : 0;
        $page = Filter::filterInput(INPUT_GET, 'page', FILTER_VALIDATE_INT, 0);
        $perPage = 10;
        $numPages = ceil($numUsers / $perPage);
        $lastPage = $page * $perPage;
        $firstPage = $lastPage - $perPage;

        $baseUrl = sprintf(
            '%sadmin/?action=user&amp;user_action=listallusers&amp;page=%d',
            $faqConfig->getDefaultUrl(),
            $page
        );

        // Pagination options
        $options = [
            'baseUrl' => $baseUrl,
            'total' => $numUsers,
            'perPage' => $perPage,
            'useRewrite' => false,
            'pageParamName' => 'page',
        ];
        $pagination = new Pagination($options);
        ?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i aria-hidden="true" class="fa fa-user"></i>
        <?= Translation::get('ad_user') ?>
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group mr-2">
            <?php
            if ($currentUser->perm->hasPermission($user->getUserId(), 'add_user')) : ?>
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal"
                        data-bs-target="#addUserModal">
                    <i class="fa fa-user-plus" aria-label="true"></i> <?= Translation::get('ad_user_add') ?>
                </button>
                <?php
            endif ?>
            <button type="button" class="btn btn-sm btn-secondary" id="pmf-button-export-users">
                <i class="fa fa-download" aria-label="true"></i> Export users as CSV
            </button>
        </div>
    </div>
</div>

        <div id="pmf-user-message"><?= $message ?></div>

        <table class="table table-striped align-middle" id="pmf-admin-user-table">
            <thead class="thead-dark">
            <tr>
                <th><?= Translation::get('ad_entry_id') ?></th>
                <th><?= Translation::get('ad_user_status') ?></th>
                <th><?= Translation::get('ad_user_is_superadmin') ?></th>
                <th><?= Translation::get('ad_user_is_visible') ?></th>
                <th><?= Translation::get('msgNewContentName') ?></th>
                <th><?= Translation::get('ad_auth_user') ?></th>
                <th><?= Translation::get('msgNewContentMail') ?></th>
                <th colspan="3">&nbsp;</th>
            </tr>
            </thead>
            <?php
            if ($perPage < $numUsers) : ?>
                <tfoot>
                <tr>
                    <td colspan="8"><?= $pagination->render() ?></td>
                </tr>
                </tfoot>
                <?php
            endif;
            ?>
        <tbody>
        <?php
        $counter = $displayedCounter = 0;
        foreach ($allUsers as $listedUserId) {
            $user->getUserById($listedUserId, true);

                if ($displayedCounter >= $perPage) {
                    continue;
                }
                ++$counter;
                if ($counter <= $firstPage) {
                    continue;
                }
                ++$displayedCounter;

                ?>
                <tr class="row_user_id_<?= $user->getUserId() ?>">
                    <td><?= $user->getUserId() ?></td>
                    <td class="text-center"><i class="fa <?php
                    switch ($user->getStatus()) {
                        case 'active':
                            echo 'fa-check-circle-o';
                            break;
                        case 'blocked':
                            echo 'fa-ban';
                            break;
                        case 'protected':
                            echo 'fa-lock';
                            break;
                    }
                    ?> icon_user_id_<?= $user->getUserId() ?>"></i></td>
                    <td class="text-center">
                        <i class="fa <?= $user->isSuperAdmin() ? 'fa-user-secret' : 'fa-user-times' ?>"></i>
                    </td>
                    <td class="text-center">
                        <i class="fa <?= $user->getUserData('is_visible') ? 'fa-user' : 'fa-user-o' ?>"></i>
                    </td>
                    <td><?= Strings::htmlentities($user->getUserData('display_name')) ?></td>
                    <td><?= Strings::htmlentities($user->getLogin()) ?></td>
                    <td>
                        <a href="mailto:<?= $user->getUserData('email') ?>">
                            <?= $user->getUserData('email') ?>
                        </a>
                    </td>
                    <td>
                        <a href="?action=user&amp;user_id=<?= $user->getUserData('user_id') ?>"
                           class="btn btn-sm btn-info">
                            <i class="fa fa-pencil"></i> <?= Translation::get('ad_user_edit') ?>
                        </a>
                    </td>
                    <td>
                        <?php
                        if ($user->getStatus() === 'blocked') : ?>
                            <button type="button" class="btn btn-sm btn-success btn-activate-user"
                                    id="btn_activate_user_id_<?= $user->getUserData('user_id') ?>"
                                    data-csrf-token="<?= Token::getInstance()->getTokenString('activate-user') ?>"
                                    data-user-id="<?= $user->getUserData('user_id') ?>">
                                <?= Translation::get('ad_news_set_active') ?>
                            </button>
                            <?php
                        endif;
                        ?>
                    </td>
                    <td>
                        <?php
                        if ($user->getStatus() !== 'protected') {
                            $csrfToken = Token::getInstance()->getTokenString('delete-user');
                        ?>
                            <button type="button" class="btn btn-sm btn-danger btn-delete-user"
                                    id="btn_user_id_<?= $user->getUserData('user_id') ?>"
                                    data-csrf-token="<?= $csrfToken ?>"
                                    data-user-id="<?= $user->getUserData('user_id') ?>">
                                <i class="fa fa-trash" data-csrf-token="<?= $csrfToken ?>"
                                   data-user-id="<?= $user->getUserData('user_id') ?>"></i>
                                <?= Translation::get('ad_user_delete') ?>
                            </button>
                        <?php
                        }
                        ?>
                    </td>
                </tr>
                <?php
            }
            ?>
            </tbody>
        </table>
        <?php
    }

    $user = CurrentUser::getCurrentUser($faqConfig);
    ?>

    <!-- Modal to add a new user -->
    <div class="modal fade" id="addUserModal" tabindex="-1" role="dialog" aria-labelledby="addUserModalLabel"
         aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addUserModalLabel">
                        <i aria-hidden="true" class="fa fa-user-plus"></i> <?= Translation::get('ad_adus_adduser') ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="#" method="post" role="form" id="pmf-add-user-form" class="needs-validation"
                          autocomplete="off"
                          novalidate>

                        <input type="hidden" id="add_user_csrf" name="add_user_csrf"
                               value="<?= Token::getInstance()->getTokenString('add-user') ?>">

                        <div class="alert alert-danger d-none" id="pmf-add-user-error-message"></div>

                        <div class="row mb-2">
                            <label class="col-lg-4 col-form-label" for="add_user_name">
                                <?= Translation::get('ad_adus_name') ?>
                            </label>
                            <div class="col-lg-8">
                                <input type="text" name="add_user_name" id="add_user_name" required tabindex="1"
                                       class="form-control">
                            </div>
                        </div>

                        <div class="row mb-2">
                            <label class="col-lg-4 col-form-label"
                                   for="add_user_realname"><?= Translation::get('ad_user_realname') ?></label>
                            <div class="col-lg-8">
                                <input type="text" name="add_user_realname" id="add_user_realname" required tabindex="2"
                                       class="form-control">
                            </div>
                        </div>

                        <div class="row mb-2">
                            <label class="col-lg-4 col-form-label" for="add_user_email">
                                <?= Translation::get('ad_entry_email') ?>
                            </label>
                            <div class="col-lg-8">
                                <input type="email" name="user_email" id="add_user_email" required tabindex="3"
                                       class="form-control">
                            </div>
                        </div>

                        <div class="row mb-2">
                            <div class="col-lg-4"></div>
                            <div class="col-lg-8">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="add_user_automatic_password"
                                           name="add_user_automatic_password" value="">
                                    <label class="form-check-label" for="add_user_automatic_password">
                                        <?= Translation::get('ad_add_user_change_password') ?>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div id="add_user_show_password_inputs">
                            <div class="row mb-2">
                                <label class="col-lg-4 col-form-label"
                                       for="add_user_password"><?= Translation::get('ad_adus_password') ?></label>
                                <div class="col-lg-8">
                                    <input type="password" name="add_user_password" id="add_user_password"
                                           class="form-control" minlength="8"
                                           autocomplete="off" tabindex="4">
                                </div>
                            </div>

                            <div class="row mb-2">
                                <label class="col-lg-4 col-form-label"
                                       for="add_user_password_confirm"><?= Translation::get('ad_passwd_con') ?></label>
                                <div class="col-lg-8">
                                    <input type="password" name="add_user_password_confirm"
                                           id="add_user_password_confirm" minlength="8"
                                           class="form-control" autocomplete="off" tabindex="5">
                                </div>
                            </div>
                        </div>

                        <?php if ($user->isSuperAdmin()) { ?>
                        <div class="row mb-2">
                            <div class="col-lg-4"></div>
                            <div class="col-lg-8">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="add_user_is_superadmin"
                                           name="user_is_superadmin">
                                    <label class="form-check-label" for="add_user_is_superadmin">
                                        <?= Translation::get('ad_user_is_superadmin') ?>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <?php } ?>

                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <?= Translation::get('ad_gen_cancel') ?>
                    </button>
                    <button type="button" class="btn btn-primary" id="pmf-add-user-action">
                        <?= Translation::get('ad_gen_save') ?>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal to overwrite password -->
    <div class="modal fade" id="pmf-modal-user-password-overwrite">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4><?= Translation::get('ad_menu_passwd') ?></h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="#" method="post" accept-charset="utf-8" autocomplete="off">
                        <input type="hidden" name="csrf" id="modal_csrf"
                               value="<?= Token::getInstance()->getTokenString('overwrite-password') ?>">
                        <input type="hidden" name="user_id" id="modal_user_id" value="<?= $userId ?>">

                        <div class="row mb-2">
                            <label class="col-5 col-form-label" for="npass">
                                <?= Translation::get('ad_passwd_new') ?>
                            </label>
                            <div class="col-7">
                                <input type="password" autocomplete="off" name="npass" id="npass"
                                       class="form-control" required>
                            </div>
                        </div>

                        <div class="row mb-2">
                            <label class="col-5 col-form-label" for="bpass">
                                <?= Translation::get('ad_passwd_con') ?>
                            </label>
                            <div class="col-7">
                                <input type="password" autocomplete="off" name="bpass" id="bpass"
                                       class="form-control" required>
                            </div>
                        </div>

                    </form>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary" id="pmf-user-password-overwrite-action">
                        <?= Translation::get('ad_user_overwrite_passwd') ?>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <?php
} else {
    echo Translation::get('err_NotAuth');
}
