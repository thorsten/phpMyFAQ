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

use phpMyFAQ\Captcha\Captcha;
use phpMyFAQ\Captcha\Helper\CaptchaHelper;
use phpMyFAQ\Filter;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$captcha = Captcha::getInstance($faqConfig);
$captcha->setSessionId($sids);

$captchaHelper = CaptchaHelper::getInstance($faqConfig);

if (!$faqConfig->get('main.enableSendToFriend')) {
    $response = new Response();
    $response->setStatusCode(Response::HTTP_FORBIDDEN);
    $response->isRedirect($faqConfig->getDefaultUrl());
    $response->send();
    exit();
}

if ($showCaptcha !== '') {
    $captcha->drawCaptchaImage();
    exit;
}

try {
    $faqSession->userTracking('send2friend', 0);
} catch (Exception) {
    // @todo handle the exception
}

$request = Request::createFromGlobals();
$faqCategory = Filter::filterVar($request->query->get('cat'), FILTER_VALIDATE_INT);
$faqId = Filter::filterVar($request->query->get('id'), FILTER_VALIDATE_INT);
$faqLanguage = Filter::filterVar($request->query->get('artlang'), FILTER_SANITIZE_SPECIAL_CHARS);

$send2friendLink = sprintf(
    '%sindex.php?action=faq&amp;cat=%d&amp;id=%d&amp;artlang=%s',
    $faqConfig->getDefaultUrl(),
    $faqCategory,
    $faqId,
    urlencode((string) $faqLanguage)
);


$template->parse(
    'mainPageContent',
    [
        'lang' => Strings::htmlentities($faqLanguage),
        'msgSend2Friend' => Translation::get('msgSend2Friend'),
        'msgS2FReferrer' => 'link',
        'msgS2FName' => Translation::get('msgS2FName'),
        'msgS2FEMail' => Translation::get('msgS2FEMail'),
        'defaultContentMail' => ($user->getUserId() > 0) ? $user->getUserData('email') : '',
        'defaultContentName' =>
            ($user->getUserId() > 0) ? Strings::htmlentities($user->getUserData('display_name')) : '',
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
            $user->isLoggedIn()
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
