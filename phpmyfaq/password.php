<?php

/**
 * This is the page there a user can request a new password.
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
 * @since     2012-03-26
 */
if (!defined('IS_VALID_PHPMYFAQ')) {
    $protocol = 'http';
    if (isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) === 'ON') {
        $protocol = 'https';
    }
    header('Location: '.$protocol.'://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

try {
    $faqsession->userTracking('forgot_password', 0);
} catch (PMF_Exception $e) {
    // @todo handle the exception
}

$tpl->parse(
    'writeContent',
    array(
        'headerChangePassword' => $PMF_LANG['ad_passwd_cop'],
        'msgUsername' => $PMF_LANG['ad_auth_user'],
        'msgEmail' => $PMF_LANG['ad_entry_email'],
        'msgSubmit' => $PMF_LANG['msgNewContentSubmit'],
    )
);

$tpl->parseBlock(
    'index',
    'breadcrumb',
    [
        'breadcrumbHeadline' => $PMF_LANG['ad_passwd_cop']
    ]
);