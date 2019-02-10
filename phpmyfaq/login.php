<?php

/**
 * This is the page there a user can login.
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 *
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2012-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      https://www.phpmyfaq.de
 * @since     2012-02-12
 */
if (!defined('IS_VALID_PHPMYFAQ')) {
    $protocol = 'http';
    if (isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) === 'ON') {
        $protocol = 'https';
    }
    header('Location: '.$protocol.'://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

$loginMessage = '';
if (!is_null($error)) {
    $loginMessage = '<p class="alert alert-danger">'.$error.'<br>('.implode($user->errors, ' ').')</p>';
}

try {
    $faqsession->userTracking('login', 0);
} catch (PMF_Exception $e) {
    // @todo handle the exception
}

$tpl->parse(
    'writeContent',
    array(
        'registerUser' => $faqConfig->get('security.enableRegistration') ? '<a href="?action=register">'.$PMF_LANG['msgRegistration'].'</a>' : '',
        'sendPassword' => '<a href="?action=password">'.$PMF_LANG['lostPassword'].'</a>',
        'loginHeader' => $PMF_LANG['msgLoginUser'],
        'loginMessage' => $loginMessage,
        'writeLoginPath' => $faqSystem->getSystemUri($faqConfig),
        'faqloginaction' => $action,
        'login' => $PMF_LANG['ad_auth_ok'],
        'username' => $PMF_LANG['ad_auth_user'],
        'password' => $PMF_LANG['ad_auth_passwd'],
        'rememberMe' => $PMF_LANG['rememberMe'],
    )
);

$tpl->parseBlock(
    'index',
    'breadcrumb',
    [
        'breadcrumbHeadline' => $PMF_LANG['msgLoginUser']
    ]
);
