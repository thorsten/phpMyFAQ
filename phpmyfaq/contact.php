<?php
/**
 * Contact page
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @since     2002-09-16
 * @copyright 2002-2009 phpMyFAQ Team
 * @version   SVN: $Id$
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
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

$faqsession->userTracking('contact', 0);

$captcha = new PMF_Captcha($sids);

if (isset($_GET['gen'])) {
    $captcha->showCaptchaImg();
    exit;
}

$tpl->processTemplate ('writeContent', array(
    'msgContact'            => $PMF_LANG['msgContact'],
    'msgContactOwnText'     => nl2br(PMF_htmlentities($faqconfig->get('main.contactInformations'), ENT_QUOTES, $PMF_LANG['metaCharset'])),
    'msgContactEMail'       => $PMF_LANG['msgContactEMail'],
    'writeSendAdress'       => $_SERVER['PHP_SELF'].'?'.$sids.'action=sendmail',
    'msgNewContentName'     => $PMF_LANG['msgNewContentName'],
    'msgNewContentMail'     => $PMF_LANG['msgNewContentMail'],
    'defaultContentMail'    => getEmailAddress(),
    'defaultContentName'    => getFullUserName(),
    'msgMessage'            => $PMF_LANG['msgMessage'],
    'msgS2FButton'          => $PMF_LANG['msgS2FButton'],
    'version'               => $faqconfig->get('main.currentVersion'),
    'captchaFieldset'       => printCaptchaFieldset($PMF_LANG['msgCaptcha'], $captcha->printCaptcha('contact'), $captcha->caplength)));

$tpl->includeTemplate('writeContent', 'index');
