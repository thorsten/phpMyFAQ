<?php
/**
 * Contact page
 *
 * PHP Version 5.2
 *

 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Frontend
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2002-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2002-09-16
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

$faqsession->userTracking('contact', 0);

$captcha = new PMF_Captcha($faqConfig);
$captcha->setSessionId($sids);

if (!is_null($showCaptcha)) {
    $captcha->showCaptchaImg();
    exit;
}

$tpl->parse (
    'writeContent',
    array(
        'msgContact'         => $PMF_LANG['msgContact'],
        'msgContactOwnText'  => nl2br($faqConfig->get('main.contactInformations')),
        'msgContactEMail'    => $PMF_LANG['msgContactEMail'],
        'msgNewContentName'  => $PMF_LANG['msgNewContentName'],
        'msgNewContentMail'  => $PMF_LANG['msgNewContentMail'],
        'lang'               => $Language->getLanguage(),
        'defaultContentMail' => ($user instanceof PMF_User_CurrentUser) ? $user->getUserData('email') : '',
        'defaultContentName' => ($user instanceof PMF_User_CurrentUser) ? $user->getUserData('display_name') : '',
        'msgMessage'         => $PMF_LANG['msgMessage'],
        'msgS2FButton'       => $PMF_LANG['msgS2FButton'],
        'version'            => $faqConfig->get('main.currentVersion'),
        'captchaFieldset'    => PMF_Helper_Captcha::getInstance()->renderCaptcha(
            $captcha,
            'contact',
            $PMF_LANG['msgCaptcha']
        )
    )
);

$tpl->merge('writeContent', 'index');
