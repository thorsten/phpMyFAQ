<?php
/**
 * Contact page
 *
 * PHP Version 5.2
 *
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 *
 * @category  phpMyFAQ
 * @package   Frontend
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2002-2011 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2002-09-16
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

$faqsession->userTracking('contact', 0);

$captcha = new PMF_Captcha($db, $Language);
$captcha->setSessionId($sids);

if (!is_null($showCaptcha)) {
    $captcha->showCaptchaImg();
    exit;
}

$tpl->parse (
    'writeContent',
    array(
        'msgContact'         => $PMF_LANG['msgContact'],
        'msgContactOwnText'  => nl2br($faqconfig->get('main.contactInformations')),
        'msgContactEMail'    => $PMF_LANG['msgContactEMail'],
        'msgNewContentName'  => $PMF_LANG['msgNewContentName'],
        'msgNewContentMail'  => $PMF_LANG['msgNewContentMail'],
        'lang'               => $Language->getLanguage(),
        'defaultContentMail' => ($user instanceof PMF_User_CurrentUser) ? $user->getUserData('email') : '',
        'defaultContentName' => ($user instanceof PMF_User_CurrentUser) ? $user->getUserData('display_name') : '',
        'msgMessage'         => $PMF_LANG['msgMessage'],
        'msgS2FButton'       => $PMF_LANG['msgS2FButton'],
        'version'            => $faqconfig->get('main.currentVersion'),
        'captchaFieldset'    => PMF_Helper_Captcha::getInstance()->renderCaptcha(
            $captcha,
            'contact',
            $PMF_LANG['msgCaptcha']
        )
    )
);

$tpl->merge('writeContent', 'index');
