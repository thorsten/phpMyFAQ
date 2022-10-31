<?php

/**
 * This module is for user registration.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Elger Thiele <elger@phpmyfaq.de>
 * @copyright 2008-2022 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2008-01-25
 */

use phpMyFAQ\Helper\CaptchaHelper;
use phpMyFAQ\Translation;

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
        'pageHeader' => Translation::get('msgRegistration'),
        'msgRegistration' => Translation::get('msgRegistration'),
        'msgRegistrationCredentials' => Translation::get('msgRegistrationCredentials'),
        'msgRegistrationNote' => Translation::get('msgRegistrationNote'),
        'lang' => $faqLangCode,
        'loginname' => Translation::get('ad_user_loginname'),
        'realname' => Translation::get('ad_user_realname'),
        'email' => Translation::get('ad_entry_email'),
        'is_visible' => Translation::get('ad_user_data_is_visible'),
        'submitRegister' => Translation::get('submitRegister'),
        'captchaFieldset' => $captchaHelper->renderCaptcha($captcha, 'register', Translation::get('msgCaptcha'), $auth),
    ]
);
