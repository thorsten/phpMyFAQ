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
 * @copyright 2008-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2008-01-25
 */

use phpMyFAQ\Captcha\Captcha;
use phpMyFAQ\Captcha\Helper\CaptchaHelper;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$request = Request::createFromGlobals();

if (!$faqConfig->get('security.enableRegistration')) {
    $redirect = new RedirectResponse($faqSystem->getSystemUri($faqConfig));
    $redirect->send();
}

try {
    $faqSession->userTracking('registration', 0);
} catch (Exception) {
    // @todo handle the exception
}

$captcha = Captcha::getInstance($faqConfig);
$captcha->setSessionId($sids);

if ($showCaptcha !== '') {
    $captcha->drawCaptchaImage();
    exit;
}

$captchaHelper = CaptchaHelper::getInstance($faqConfig);

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
        'captchaFieldset' =>
            $captchaHelper->renderCaptcha($captcha, 'register', Translation::get('msgCaptcha'), $user->isLoggedIn()),
    ]
);
