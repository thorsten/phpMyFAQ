<?php
/**
 * This is the page there a user can login.
 *
 * PHP Version 5.3
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Frontend
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2012-2013 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2012-02-12
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

if (is_null($error)) {
    $loginMessage = '<p>' . $PMF_LANG['ad_auth_insert'] . '</p>';
} else {
    $loginMessage = '<p class="alert alert-error">' . $error . '</p>';
}

$tpl->parse(
    'writeContent',
    array(
        'registerUser'   => '<a href="?action=register">' . $PMF_LANG['msgRegistration'] . '</a>',
        'sendPassword'   => '<a href="?action=password">' . $PMF_LANG['lostPassword'] . '</a>',
        'loginHeader'    => $PMF_LANG['msgLoginUser'],
        'loginMessage'   => $loginMessage,
        'writeLoginPath' => $systemUri,
        'faqloginaction' => $action,
        'login'          => $PMF_LANG['ad_auth_ok'],
        'username'       => $PMF_LANG['ad_auth_user'],
        'password'       => $PMF_LANG['ad_auth_passwd'],
        'rememberMe'     => $PMF_LANG['rememberMe']
    )
);

$tpl->merge('writeContent', 'index');