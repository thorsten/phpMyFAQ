<?php

/**
 * Contact page.
 *
 * PHP Version 5.6
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2002-2018 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2002-09-16
 */

use phpMyFAQ\Exception;
use phpMyFAQ\Captcha;
use phpMyFAQ\Helper\CaptchaHelper;
use phpMyFAQ\User\CurrentUser;

if (!defined('IS_VALID_PHPMYFAQ')) {
    $protocol = 'http';
    if (isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) === 'ON') {
        $protocol = 'https';
    }
    header('Location: '.$protocol.'://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

try {
    $faqsession->userTracking('contact', 0);
} catch (Exception $e) {
    // @todo handle the exception
}

$captcha = new Captcha($faqConfig);
$captcha->setSessionId($sids);

if (!is_null($showCaptcha)) {
    $captcha->showCaptchaImg();
    exit;
}

$captchaHelper = new CaptchaHelper($faqConfig);

$tpl->parse(
    'writeContent',
    array(
        'msgContact' => $PMF_LANG['msgContact'],
        'msgContactOwnText' => nl2br($faqConfig->get('main.contactInformations')),
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
        'version' => $faqConfig->get('main.currentVersion'),
        'captchaFieldset' => $captchaHelper->renderCaptcha($captcha, 'contact', $PMF_LANG['msgCaptcha'], $auth),
    )
);

$tpl->parseBlock(
    'index',
    'breadcrumb',
    [
        'breadcrumbHeadline' => $PMF_LANG['msgContact']
    ]
);
