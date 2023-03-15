<?php

/**
 * This is the page there a user can log in.
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
 * @since     2012-02-12
 */

use phpMyFAQ\Strings;
use phpMyFAQ\Translation;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$loginMessage = '';

if (!is_null($error)) {
    $loginMessage = '<div class="alert alert-danger" role="alert">' . $error . '</div>';
}

try {
    $faqSession->userTracking('login', 0);
} catch (Exception) {
    // @todo handle the exception
}

if ($faqConfig->get('security.enableRegistration')) {
    $template->parseBlock(
        'mainPageContent',
        'enableRegistration',
        [
            'registerUser' => Translation::get('msgRegistration'),
        ]
    );
}

if ($faqConfig->isSignInWithMicrosoftActive()) {
    $template->parseBlock(
        'mainPageContent', 'useSignInWithMicrosoft', [
            'msgSignInWithMicrosoft' => Translation::get('msgSignInWithMicrosoft'),
        ]
    );
}

$template->parse(
    'mainPageContent',
    [
        'loginHeader' => Translation::get('msgLoginUser'),
        'sendPassword' => '<a href="?action=password">' . Translation::get('lostPassword') . '</a>',
        'loginMessage' => $loginMessage,
        'writeLoginPath' => Strings::htmlentities($faqSystem->getSystemUri($faqConfig)),
        'faqloginaction' => $action,
        'login' => Translation::get('ad_auth_ok'),
        'username' => Translation::get('ad_auth_user'),
        'password' => Translation::get('ad_auth_passwd'),
        'rememberMe' => Translation::get('rememberMe'),
        'msgTwofactorEnabled' => Translation::get('msgTwofactorEnabled'),
        'msgTwofactorTokenModelTitle' => Translation::get('msgTwofactorTokenModelTitle'),
        'msgEnterTwofactorToken' => Translation::get('msgEnterTwofactorToken'),
        'msgTwofactorCheck' => Translation::get('msgTwofactorCheck'),
        'userid' => $userid,
    ]
);
