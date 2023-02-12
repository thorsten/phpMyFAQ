<?php

/**
 * Request removal page.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2018-2022 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2018-02-03
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
    $faqSession->userTracking('request_removal', 0);
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
        'pageHeader' => $PMF_LANG['msgUserRemoval'],
        'msgContact' => $PMF_LANG['msgContact'],
        'msgUserRemovalText' => $PMF_LANG['msgUserRemovalText'],
        'msgContactRemove' => $PMF_LANG['msgContactRemove'],
        'msgContactPrivacyNote' => $PMF_LANG['msgContactPrivacyNote'],
        'msgPrivacyNote' => $PMF_LANG['msgPrivacyNote'],
        'privacyURL' => Strings::htmlentities($faqConfig->get('main.privacyURL')),
        'msgNewContentName' => $PMF_LANG['msgNewContentName'],
        'msgNewContentMail' => $PMF_LANG['msgNewContentMail'],
        'ad_user_loginname' => $PMF_LANG['ad_user_loginname'],
        'lang' => $Language->getLanguage(),
        'defaultContentMail' => ($user instanceof CurrentUser) ? $user->getUserData('email') : '',
        'defaultContentName' => ($user instanceof CurrentUser) ? $user->getUserData('display_name') : '',
        'defaultLoginName' => ($user instanceof CurrentUser) ? $user->getLogin() : '',
        'msgMessage' => $PMF_LANG['msgMessage'],
        'msgS2FButton' => $PMF_LANG['msgS2FButton'],
        'version' => $faqConfig->getVersion(),
        'captchaFieldset' => $captchaHelper->renderCaptcha($captcha, 'contact', $PMF_LANG['msgCaptcha'], $auth),
    ]
);
