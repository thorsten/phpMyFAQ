<?php

/**
 * Contact page.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2002-2022 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2002-09-16
 */

use phpMyFAQ\Captcha;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Helper\CaptchaHelper;
use phpMyFAQ\Strings;
use phpMyFAQ\User\CurrentUser;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

try {
    $faqSession->userTracking('contact', 0);
} catch (Exception $e) {
    // @todo handle the exception
}

$captcha = new Captcha($faqConfig);
$captcha->setSessionId($sids);

if (!is_null($showCaptcha)) {
    $captcha->drawCaptchaImage();
    exit;
}

$captchaHelper = new CaptchaHelper($faqConfig);

$template->parse(
    'mainPageContent',
    [
        'pageHeader' => $PMF_LANG['msgContact'],
        'msgContactOwnText' => nl2br(Strings::htmlspecialchars($faqConfig->get('main.contactInformations'))),
        'msgContactEMail' => $PMF_LANG['msgContactEMail'],
        'msgContactPrivacyNote' => $PMF_LANG['msgContactPrivacyNote'],
        'privacyURL' => $faqConfig->get('main.privacyURL'),
        'msgPrivacyNote' => $PMF_LANG['msgPrivacyNote'],
        'msgNewContentName' => $PMF_LANG['msgNewContentName'],
        'msgNewContentMail' => $PMF_LANG['msgNewContentMail'],
        'lang' => $Language->getLanguage(),
        'defaultContentMail' => ($user instanceof CurrentUser) ? $user->getUserData('email') : '',
        'defaultContentName' => ($user instanceof CurrentUser) ? $user->getUserData('display_name') : '',
        'msgMessage' => $PMF_LANG['msgMessage'],
        'msgS2FButton' => $PMF_LANG['msgS2FButton'],
        'version' => $faqConfig->getVersion(),
        'captchaFieldset' => $captchaHelper->renderCaptcha($captcha, 'contact', $PMF_LANG['msgCaptcha'], $auth),
    ]
);
