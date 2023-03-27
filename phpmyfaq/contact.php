<?php

/**
 * Contact page.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2002-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2002-09-16
 */

use phpMyFAQ\Captcha\Captcha;
use phpMyFAQ\Captcha\Helper\CaptchaHelper;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

try {
    $faqSession->userTracking('contact', 0);
} catch (Exception) {
    // @todo handle the exception
}

$captcha = Captcha::getInstance($faqConfig);
$captcha->setSessionId($sids);

if (!is_null($showCaptcha)) {
    $captcha->drawCaptchaImage();
    exit;
}

$captchaHelper = CaptchaHelper::getInstance($faqConfig);

$template->parse(
    'mainPageContent',
    [
        'pageHeader' => Translation::get('msgContact'),
        'msgContactOwnText' => nl2br(Strings::htmlspecialchars($faqConfig->get('main.contactInformations'))),
        'msgContactEMail' => Translation::get('msgContactEMail'),
        'msgContactPrivacyNote' => Translation::get('msgContactPrivacyNote'),
        'privacyURL' => Strings::htmlentities($faqConfig->get('main.privacyURL')),
        'msgPrivacyNote' => Translation::get('msgPrivacyNote'),
        'msgNewContentName' => Translation::get('msgNewContentName'),
        'msgNewContentMail' => Translation::get('msgNewContentMail'),
        'lang' => $Language->getLanguage(),
        'defaultContentMail' => ($user instanceof CurrentUser) ? $user->getUserData('email') : '',
        'defaultContentName' => ($user instanceof CurrentUser) ? $user->getUserData('display_name') : '',
        'msgMessage' => Translation::get('msgMessage'),
        'msgS2FButton' => Translation::get('msgS2FButton'),
        'version' => $faqConfig->getVersion(),
        'captchaFieldset' =>
            $captchaHelper->renderCaptcha($captcha, 'contact', Translation::get('msgCaptcha'), $user->isLoggedIn()),
    ]
);
