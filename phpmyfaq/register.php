<?php

/**
 * This module is for user registration.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author Elger Thiele <elger@phpmyfaq.de>
 * @copyright 2008-2020 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2008-01-25
 */

use phpMyFAQ\Helper\CaptchaHelper;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

if (!$faqConfig->get('security.enableRegistration')) {
    header('Location: ' . $protocol . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

try {
    $faqSession->userTracking('registration', 0);
} catch (Exception $e) {
    // @todo handle the exception
}

$captcha = new phpMyFAQ\Captcha($faqConfig);
$captcha->setSessionId($sids);

if (!is_null($showCaptcha)) {
    $captcha->drawCaptchaImage();
    exit;
}

$captchaHelper = new CaptchaHelper($faqConfig);

$template->parse(
    'mainPageContent',
    [
        'pageHeader' => $PMF_LANG['msgRegistration'],
        'msgRegistration' => $PMF_LANG['msgRegistration'],
        'msgRegistrationCredentials' => $PMF_LANG['msgRegistrationCredentials'],
        'msgRegistrationNote' => $PMF_LANG['msgRegistrationNote'],
        'lang' => $faqLangCode,
        'loginname' => $PMF_LANG['ad_user_loginname'],
        'realname' => $PMF_LANG['ad_user_realname'],
        'email' => $PMF_LANG['ad_entry_email'],
        'is_visible' => $PMF_LANG['ad_user_data_is_visible'],
        'submitRegister' => $PMF_LANG['submitRegister'],
        'captchaFieldset' => $captchaHelper->renderCaptcha($captcha, 'register', $PMF_LANG['msgCaptcha'], $auth),
    ]
);
