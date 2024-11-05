<?php

/**
 * Form to change the password of the current user.
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
 * @since     2003-02-23
 */

use phpMyFAQ\Auth;
use phpMyFAQ\Configuration;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Filter;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Template\TwigWrapper;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$faqConfig = $container->get('phpmyfaq.configuration');
$user = CurrentUser::getCurrentUser($faqConfig);

$twig = new TwigWrapper(PMF_ROOT_DIR . '/assets/templates');
$template = $twig->loadTemplate('./admin/user/password.change.twig');

if ($user->perm->hasPermission($user->getUserId(), PermissionType::PASSWORD_CHANGE->value)) {
    // If we have to save a new password, do that first
    $save = Filter::filterInput(INPUT_POST, 'save', FILTER_SANITIZE_SPECIAL_CHARS);
    $csrfToken = Filter::filterInput(INPUT_POST, 'csrf', FILTER_SANITIZE_SPECIAL_CHARS);
    $successMessage = $errorMessage = '';

    if (!is_null($save) && Token::getInstance($container->get('session'))->verifyToken('password', $csrfToken)) {
        // Define the (Local/Current) Authentication Source
        $auth = new Auth($faqConfig);
        $authSource = $auth->selectAuth($user->getAuthSource('name'));
        $authSource->getEncryptionContainer($user->getAuthData('encType'));
        $authSource->setReadOnly($user->getAuthData('readOnly'));

        $oldPassword = Filter::filterInput(INPUT_POST, 'faqpassword_old', FILTER_SANITIZE_SPECIAL_CHARS);
        $newPassword = Filter::filterInput(INPUT_POST, 'faqpassword', FILTER_SANITIZE_SPECIAL_CHARS);
        $retypedPassword = Filter::filterInput(INPUT_POST, 'faqpassword_confirm', FILTER_SANITIZE_SPECIAL_CHARS);

        if (strlen((string) $newPassword) <= 7 || strlen((string) $retypedPassword) <= 7) {
            $errorMessage = Translation::get('ad_passwd_fail');
        } else {
            if (
                ($authSource->checkCredentials(
                    $user->getLogin(),
                    $oldPassword
                )) && ($newPassword == $retypedPassword)
            ) {
                if (!$user->changePassword($newPassword)) {
                    $errorMessage = Translation::get('ad_passwd_fail');
                }
                $successMessage = Translation::get('ad_passwdsuc');
            } else {
                $errorMessage = Translation::get('ad_passwd_fail');
            }
        }
    }

    $templateVars = [
        'adminHeaderPasswordChange' => Translation::get('ad_passwd_cop'),
        'successMessage' => $successMessage,
        'errorMessage' => $errorMessage,
        'csrfToken' => Token::getInstance($container->get('session'))->getTokenString('password'),
        'adminMsgOldPassword' => Translation::get('ad_passwd_old'),
        'adminMsgNewPassword' => Translation::get('ad_passwd_new'),
        'adminMsgNewPasswordConfirm' => Translation::get('ad_passwd_con'),
        'adminMsgButtonNewPassword' => Translation::get('ad_passwd_change')
    ];

    echo $template->render($templateVars);
} else {
    require __DIR__ . '/no-permission.php';
}
