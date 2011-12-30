<?php
/**
 * This module is for user registration.
 *
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
 * @author    Elger Thiele <elger@phpmyfaq.de>
 * @copyright 2008-2011 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2008-01-25
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

$faqsession->userTracking('registration', 0);

$captcha = new PMF_Captcha($db, $Language);
$captcha->setSessionId($sids);

if (!is_null($showCaptcha)) {
    $captcha->showCaptchaImg();
    exit;
}

$tpl->processTemplate('writeContent', array(
    'msgRegistration'            => $PMF_LANG['msgRegistration'],
    'msgRegistrationCredentials' => $PMF_LANG['msgRegistrationCredentials'],
    'msgRegistrationNote'        => $PMF_LANG['msgRegistrationNote'],
    'lang'                       => $PMF_LANG['msgUserData'],
    'loginname'                  => $PMF_LANG["ad_user_loginname"],
    'realname'                   => $PMF_LANG["ad_user_realname"],
    'email'                      => $PMF_LANG["ad_entry_email"],
    'submitRegister'             => $PMF_LANG['submitRegister'],
    'captchaFieldset'            => printCaptchaFieldset($PMF_LANG['msgCaptcha'], $captcha->printCaptcha('add'), $captcha->caplength)));

$tpl->includeTemplate('writeContent', 'index');