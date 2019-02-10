<?php

/**
 * The send2friend page.
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 *
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2002-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      https://www.phpmyfaq.de
 * @since     2002-09-16
 */
if (!defined('IS_VALID_PHPMYFAQ')) {
    $protocol = 'http';
    if (isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) === 'ON') {
        $protocol = 'https';
    }
    header('Location: '.$protocol.'://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

$captcha = new PMF_Captcha($faqConfig);
$captcha->setSessionId($sids);

if (!is_null($showCaptcha)) {
    $captcha->showCaptchaImg();
    exit;
}

try {
    $faqsession->userTracking('send2friend', 0);
} catch (PMF_Exception $e) {
    // @todo handle the exception
}

$cat = PMF_Filter::filterInput(INPUT_GET, 'cat', FILTER_VALIDATE_INT);
$id = PMF_Filter::filterInput(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$artlang = PMF_Filter::filterInput(INPUT_GET, 'artlang', FILTER_SANITIZE_STRIPPED);

$send2friendLink = sprintf('%sindex.php?action=artikel&amp;cat=%d&amp;id=%d&amp;artlang=%s',
    $faqConfig->getDefaultUrl(),
    (int) $cat,
    (int) $id,
    urlencode($artlang));

$captchaHelper = new PMF_Helper_Captcha($faqConfig);

$tpl->parse(
    'writeContent',
    array(
        'lang' => $artlang,
        'msgSend2Friend' => $PMF_LANG['msgSend2Friend'],
        'msgS2FReferrer' => 'link',
        'msgS2FName' => $PMF_LANG['msgS2FName'],
        'msgS2FEMail' => $PMF_LANG['msgS2FEMail'],
        'defaultContentMail' => ($user instanceof PMF_User_CurrentUser) ? $user->getUserData('email') : '',
        'defaultContentName' => ($user instanceof PMF_User_CurrentUser) ? $user->getUserData('display_name') : '',
        'msgS2FFriends' => $PMF_LANG['msgS2FFriends'],
        'msgS2FEMails' => $PMF_LANG['msgS2FEMails'],
        'msgS2FText' => $PMF_LANG['msgS2FText'],
        'send2friend_text' => $faqConfig->get('main.send2friendText'),
        'msgS2FText2' => $PMF_LANG['msgS2FText2'],
        'send2friendLink' => $send2friendLink,
        'msgS2FMessage' => $PMF_LANG['msgS2FMessage'],
        'captchaFieldset' => $captchaHelper->renderCaptcha($captcha, 'send2friend', $PMF_LANG['msgCaptcha'], $auth),
        'msgS2FButton' => $PMF_LANG['msgS2FButton'],
    )
);

$tpl->parseBlock(
    'index',
    'breadcrumb',
    [
        'breadcrumbHeadline' => $PMF_LANG['msgSend2Friend']
    ]
);