<?php

/**
 * The send2friend page.
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

use phpMyFAQ\Captcha;
use phpMyFAQ\Filter;
use phpMyFAQ\Helper\CaptchaHelper;
use phpMyFAQ\Helper\HttpHelper;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$http = new HttpHelper();
$captcha = new Captcha($faqConfig);
$captchaHelper = new CaptchaHelper($faqConfig);

if (!$faqConfig->get('main.enableSendToFriend')) {
    $http->setStatus(403);
    $http->redirect($faqConfig->getDefaultUrl());
}

$captcha->setSessionId($sids);

if (!is_null($showCaptcha)) {
    $captcha->drawCaptchaImage();
    exit;
}

try {
    $faqSession->userTracking('send2friend', 0);
} catch (Exception) {
    // @todo handle the exception
}

$cat = Filter::filterInput(INPUT_GET, 'cat', FILTER_VALIDATE_INT);
$id = Filter::filterInput(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$faqLanguage = Filter::filterInput(INPUT_GET, 'artlang', FILTER_UNSAFE_RAW);

$send2friendLink = sprintf(
    '%sindex.php?action=faq&amp;cat=%d&amp;id=%d&amp;artlang=%s',
    $faqConfig->getDefaultUrl(),
    (int)$cat,
    (int)$id,
    urlencode($faqLanguage)
);


$template->parse(
    'mainPageContent',
    [
        'lang' => $faqLanguage,
        'msgSend2Friend' => Translation::get('msgSend2Friend'),
        'msgS2FReferrer' => 'link',
        'msgS2FName' => Translation::get('msgS2FName'),
        'msgS2FEMail' => Translation::get('msgS2FEMail'),
        'defaultContentMail' => ($user instanceof CurrentUser) ? $user->getUserData('email') : '',
        'defaultContentName' => ($user instanceof CurrentUser) ? $user->getUserData('display_name') : '',
        'msgS2FFriends' => Translation::get('msgS2FFriends'),
        'msgS2FEMails' => Translation::get('msgS2FEMails'),
        'msgS2FText' => Translation::get('msgS2FText'),
        'send2friend_text' => Strings::htmlentities($faqConfig->get('main.send2friendText')),
        'msgS2FText2' => Translation::get('msgS2FText2'),
        'send2friendLink' => $send2friendLink,
        'msgS2FMessage' => Translation::get('msgS2FMessage'),
        'captchaFieldset' => $captchaHelper->renderCaptcha(
            $captcha,
            'send2friend',
            Translation::get('msgCaptcha'),
            $auth
        ),
        'msgS2FButton' => Translation::get('msgS2FButton'),
    ]
);

$template->parseBlock(
    'index',
    'breadcrumb',
    [
        'breadcrumbHeadline' => Translation::get('msgSend2Friend')
    ]
);
