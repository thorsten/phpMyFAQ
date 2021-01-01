<?php

/**
 * This is the page there a user can login.
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2012-2021 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2012-02-12
 */

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
} catch (Exception $e) {
    // @todo handle the exception
}

$template->parse(
    'mainPageContent',
    [
        'registerUser' => $faqConfig->get(
            'security.enableRegistration'
        ) ? '<a href="?action=register">' . $PMF_LANG['msgRegistration'] . '</a>' : '',
        'sendPassword' => '<a href="?action=password">' . $PMF_LANG['lostPassword'] . '</a>',
        'loginHeader' => $PMF_LANG['msgLoginUser'],
        'loginMessage' => $loginMessage,
        'writeLoginPath' => $faqSystem->getSystemUri($faqConfig),
        'faqloginaction' => $action,
        'login' => $PMF_LANG['ad_auth_ok'],
        'username' => $PMF_LANG['ad_auth_user'],
        'password' => $PMF_LANG['ad_auth_passwd'],
        'rememberMe' => $PMF_LANG['rememberMe'],
    ]
);
