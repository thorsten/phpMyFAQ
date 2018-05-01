<?php

/**
 * Request removal page.
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2018 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2018-02-03
 */

use phpMyFAQ\Captcha;
use phpMyFAQ\Exception;
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
        'msgUserRemovalText' => $PMF_LANG['msgUserRemovalText'],
        'msgContactRemove' => $PMF_LANG['msgContactRemove'],
        'msgContactPrivacyNote' => $PMF_LANG['msgContactPrivacyNote'],
        'msgPrivacyNote' => $PMF_LANG['msgPrivacyNote'],
        'privacyURL' => $faqConfig->get('main.privacyURL'),
        'msgNewContentName' => $PMF_LANG['msgNewContentName'],
        'msgNewContentMail' => $PMF_LANG['msgNewContentMail'],
        'ad_user_loginname' => $PMF_LANG['ad_user_loginname'],
        'lang' => $Language->getLanguage(),
        'defaultContentMail' => ($user instanceof CurrentUser) ? $user->getUserData('email') : '',
        'defaultContentName' => ($user instanceof CurrentUser) ? $user->getUserData('display_name') : '',
        'defaultLoginName' => ($user instanceof CurrentUser) ? $user->getLogin() : '',
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
        'breadcrumbHeadline' => $PMF_LANG['msgUserRemoval']
    ]
);
