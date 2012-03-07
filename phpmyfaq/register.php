<?php
/**
 * This module is for user registration.
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
 * @author    Elger Thiele <elger@phpmyfaq.de>
 * @copyright 2008-2011 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2008-01-25
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

$faqsession->userTracking('registration', 0);

$captcha = new PMF_Captcha($faqConfig);
$captcha->setSessionId($sids);

if (!is_null($showCaptcha)) {
    $captcha->showCaptchaImg();
    exit;
}

$tpl->parse(
    'writeContent',
    array(
        'msgRegistration'            => $PMF_LANG['msgRegistration'],
        'msgRegistrationCredentials' => $PMF_LANG['msgRegistrationCredentials'],
        'msgRegistrationNote'        => $PMF_LANG['msgRegistrationNote'],
        'lang'                       => $LANGCODE,
        'loginname'                  => $PMF_LANG["ad_user_loginname"],
        'realname'                   => $PMF_LANG["ad_user_realname"],
        'email'                      => $PMF_LANG["ad_entry_email"],
        'submitRegister'             => $PMF_LANG['submitRegister'],
        'captchaFieldset'            => PMF_Helper_Captcha::getInstance()->renderCaptcha(
            $captcha,
            'register',
            $PMF_LANG['msgCaptcha']
        )
    )
);

$tpl->merge('writeContent', 'index');